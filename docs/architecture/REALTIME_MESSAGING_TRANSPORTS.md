# Real-Time Messaging Transports — Reverb vs Pusher vs Polling

Implementation guide for the private 1:1 messaging feature (tasks 30–33). Covers the three
transports that can drive "new message appears without a manual refresh," how each maps onto the
**system as it exists today**, and when to pick which.

> **TL;DR for the current deployment target (Hostinger shared / hPanel):**
> Reverb is committed and works locally, but **cannot run on shared hosting** (no persistent daemon,
> no custom port). The two options that work on shared hosting are **Pusher** (instant, capped at 100
> concurrent connections on the free tier) and **Polling** (a few-seconds delay, no third-party, but
> consumes server request capacity). Reverb only becomes viable on a **VPS**.

---

## 1. The one thing that makes all three interchangeable

Task 33 was built on a deliberate seam:

> **The transport is only a *trigger*. The payload is always an existing server-rendered HTML fragment
> fetched over plain HTTP.**

When something changes in a thread, the client re-fetches `_conversation_thread` / `_conversation_list_items`
and swaps the HTML. Whether that re-fetch is kicked off by a **WebSocket ping** (Reverb/Pusher) or a
**timer** (Polling) is the *only* thing that differs between the three options. The controller, the
Blade fragments, the fetch-and-swap JS, and the database are identical in all three.

That is why switching transports is a small, low-risk change — not a rewrite.

Relevant files (shared by all three):

| File | Role |
|---|---|
| `app/Http/Controllers/MessagingController.php` | Write endpoints (send/edit/delete/read) + returns fragments |
| `app/Services/ConversationService.php` | Message persistence + thread/list builders |
| `resources/views/layouts/partials/_conversation_thread.blade.php` | The thread fragment |
| `resources/views/layouts/partials/_conversation_list_items.blade.php` | The inbox/list fragment |
| `resources/views/layouts/partials/_demo1_topbar_icons.blade.php` | The drawer UI + fetch-and-swap JS (`pollMessaging`, `pollThread`) |

---

## 2. Option A — Laravel Reverb (self-hosted WebSocket) — *current implementation*

### What it is
A WebSocket **server you run yourself** (`php artisan reverb:start`). It speaks the Pusher protocol, so
the browser talks to it with `pusher-js` and Laravel broadcasts to it with the standard broadcasting API.

### How it is wired today (already committed, task 33)

- **Event** — `app/Events/ConversationActivity.php`, `implements ShouldBroadcastNow` (synchronous, no
  queue worker). Carries no message body — just `conversationId` — and broadcasts on the **recipient's**
  private channel:
  ```php
  return new PrivateChannel('messaging.'.$this->recipientId);
  ```
- **Channel authorization** — `routes/channels.php` gates `messaging.{userId}` to that user:
  ```php
  Broadcast::channel('messaging.{userId}', fn ($user, $userId) => (int) $user->id === (int) $userId);
  ```
- **Emit points** — `MessagingController::pingOtherParticipant()` fires the event after every write
  (send / edit / delete / markRead). The acting user already gets fresh HTML in its own HTTP response,
  so only the counterparty is pinged.
- **Frontend** — `resources/js/echo.js` instantiates Echo (broadcaster `reverb`); `resources/js/app.js`
  imports it; the base layout (`resources/views/layouts/app.blade.php`) loads it via `@vite` and exposes
  `window.qyzenUserId = auth()->id()`. `subscribeRealtime()` in the topbar partial subscribes to
  `private-messaging.{userId}` and, on a `thread.updated` ping, calls the existing `pollMessaging()` /
  `pollThread()` fetches.
- **CSP** — `app/Http/Middleware/SecurityHeaders.php::reverbOrigin()` adds the WS origin to
  `connect-src`, built from config so dev (`ws://localhost:8080`) and prod (`wss://…`) differ
  automatically.
- **Config** — `config/broadcasting.php` `reverb` connection; env keys `REVERB_*` + `VITE_REVERB_*`
  (see `.env.example`).
- **Dev runner** — `composer dev` starts `php artisan reverb:start` alongside serve/queue/vite.

### Environment (dev — already working)
```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...          # any unique id
REVERB_APP_KEY=...         # any random string
REVERB_APP_SECRET=...      # any random string
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
# VITE_* mirror the above; they are baked into the JS bundle at `npm run build`.
```

### Deploying Reverb (requires a VPS — NOT shared hosting)
1. Run the daemon under a process supervisor so it restarts on crash/reboot:
   - **Supervisor** (`/etc/supervisor/conf.d/reverb.conf`) or a **systemd** unit running
     `php artisan reverb:start --host=0.0.0.0 --port=8080`.
2. **TLS + reverse proxy**: production pages are `https`, so browsers must use `wss://` (mixed-content
   blocks `ws://`). Proxy the WebSocket through nginx/Apache on 443 with `Upgrade`/`Connection` headers;
   do **not** expose a public `:8080`.
3. Prod env: `REVERB_SCHEME=https`, `REVERB_HOST=your.domain`, and **rebuild** so `VITE_REVERB_*` bake
   the production values into the bundle.
4. CSP is already handled — `reverbOrigin()` derives `wss://your.domain:443` from config.

### Pros / Cons
| ✅ Pros | ❌ Cons |
|---|---|
| Instant delivery | **Needs a VPS** — dies on shared hosting |
| No connection cap, no per-message cost | You run, secure, and babysit a daemon + TLS proxy |
| No third-party dependency | Build-time `VITE_*` divergence is easy to get wrong |
| Already built and browser-verified | |

**Verdict:** best if/when Qyzen moves to a **Hostinger KVM VPS**. Not an option on shared hosting.

---

## 3. Option B — Pusher Channels (hosted WebSocket)

### What it is
The **same protocol as Reverb**, but the WebSocket server runs in Pusher's cloud. Your app only makes
**outbound HTTPS** calls to broadcast — which shared hosting allows — so there is **no daemon, no port,
no proxy**. Sign up: <https://dashboard.pusher.com> → Channels → create app → copy `app_id`, `key`,
`secret`, `cluster`.

### The change from the current Reverb setup (small)
1. Install the server SDK:
   ```bash
   composer require pusher/pusher-php-server
   ```
2. Switch the broadcaster (env only — `config/broadcasting.php` already has a `pusher` connection):
   ```dotenv
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=...
   PUSHER_APP_KEY=...
   PUSHER_APP_SECRET=...
   PUSHER_APP_CLUSTER=ap1        # pick the cluster nearest your users
   VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
   VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
   ```
3. `resources/js/echo.js` — swap the transport (drop `wsHost`/`wsPort`, add `cluster`):
   ```js
   window.Echo = new Echo({
       broadcaster: 'pusher',
       key: import.meta.env.VITE_PUSHER_APP_KEY,
       cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
       forceTLS: true,
       enabledTransports: ['ws', 'wss'],
   });
   ```
4. `SecurityHeaders.php` — allow the Pusher origin in `connect-src` instead of `reverbOrigin()`, e.g.
   `wss://ws-<cluster>.pusher.com` (and `https://sockjs-<cluster>.pusher.com` for the SockJS fallback).
5. `npm run build` with the Pusher `VITE_*` values (they are baked at build time).
6. Optional: drop `reverb` from `composer dev` and remove `laravel/reverb` if you commit to Pusher.

**Everything else — event, channel auth, controller pings, the fetch-on-ping loop — is unchanged.**

### Free-tier limits and how they actually apply
Pusher free ("Sandbox"): **100 concurrent connections**, **200k messages/day**.

- A **connection** = one browser tab with Qyzen open, held for as long as the tab is open (even idle).
  **Not** per account, **not** per day — simultaneous open tabs.
- A **message** = one broadcast ping. One chat action ≈ ~1 message. For chat you will essentially never
  approach 200k/day — **the connection cap is the only real ceiling.**

Real-life scenarios (school chat):

| Scenario | Connections | Result |
|---|---|---|
| 1 class chatting during a lesson (30 + educator) | 31 | Fine |
| 3 classes with the app open at once | 120 | Connections 101–120 **refused** → those users lose live updates |
| Grades released, 400 students open Qyzen | 400 | First 100 get realtime, rest refused |

> **Connection-trigger gotcha (important):** as wired today, the WebSocket connects on **page load of any
> authenticated page** (Echo instantiates in `echo.js` on every page), **not** when the chat drawer opens.
> So a student who logs in **only to take a quiz** still consumes 1 connection.

### Lazy-connect optimization (strongly recommended with Pusher)
Move the Echo instantiation out of page-load and into the **first chat-drawer open**. Then:
- Quiz-only students consume **0 connections**.
- The 100-connection budget is spent only by people **actually using chat** — a small fraction on a
  quiz platform, hugely extending headroom.
- Trade-off: the unread badge won't tick live for someone who has never opened chat this session (they
  see the count on next page load). Acceptable for a quiz-taker.

### Pros / Cons
| ✅ Pros | ❌ Cons |
|---|---|
| Instant delivery | **100 concurrent connection** free-tier cap |
| Works on shared hosting (no daemon) | Third-party dependency + their uptime |
| ~95% code reuse from the Reverb build | Build-time `VITE_*` must be correct |
| Low server load (broadcast = 1 outbound call) | Overflow users silently lose live updates (no fallback today) |

**Verdict:** the way to get **instant** delivery on **shared hosting**. Pair with lazy-connect. If you
expect frequent >100-simultaneous spikes, consider **Ably** (<https://ably.com>, roomier free tier —
same swap) or a VPS + Reverb.

---

## 4. Option C — Polling (periodic HTTP fetch)

### What it is
No push at all. The client re-fetches the fragments on a timer. This is **what the system had before
task 33** — task 33 removed two `setInterval` timers (a ~30s badge/list poll and a ~5s open-thread poll)
and replaced them with the Reverb subscription. Going "polling only" means **restoring those timers** and
dropping the Echo/Reverb wiring (the messaging backend stays exactly as-is).

### Implementation (revert + tune)
1. In `_demo1_topbar_icons.blade.php`, reinstate the timers that call the existing `pollMessaging()` /
   `pollThread()` fetches, and remove `subscribeRealtime()` / the Echo listener.
2. Remove the realtime plumbing you no longer need: `resources/js/echo.js` import, the `@vite` Echo
   bundle, `window.qyzenUserId` (unless kept for later), `ConversationActivity` broadcasts in the
   controller, `reverbOrigin()` in CSP, and `reverb` from `composer dev`. (Or leave them dormant behind
   `BROADCAST_CONNECTION=log` if you want to keep the option open.)
3. **Tune to keep shared-hosting load low** (do better than the pre-task-33 version):
   - **Pause polling on hidden tabs** via the Page Visibility API (`document.visibilityState`). A
     background quiz tab then makes **zero** requests — this removes most of the load.
   - Poll the **thread only while the chat drawer is open** (the old code already did this).
   - Intervals: badge/list every ~30–60s, open-thread every ~5–8s.

### The cost on shared hosting
Polling's ceiling is **your own server's request capacity**, specifically Hostinger's **entry-process
limit** (concurrent PHP processes — often ~20–40 on shared plans). Many tabs polling at the same instant
can momentarily exhaust that and produce `508` errors. The visibility-pause + drawer-gated thread poll
keep concurrent requests low enough that realistic school load (a few dozen people with chat open) is a
trickle of tiny JSON fetches the plan handles fine.

### The trade-off to accept
Delivery is **not instant** — a message appears after up to one poll interval (a few seconds). For
student↔educator chat in an LMS, a 3–8s delay is imperceptible in practice. This is the honest reason
polling is a legitimate choice, not merely a fallback.

### Pros / Cons
| ✅ Pros | ❌ Cons |
|---|---|
| Works everywhere, including shared hosting | Not instant (delay = poll interval) |
| No daemon, no third party, no connection cap | Constant requests → server load / entry-process pressure |
| Already proven (pre-task-33 behavior) | Reverts task 33's transport |
| Degrades gracefully under load | Tuning needed to be cheap on shared hosting |

**Verdict:** the zero-infrastructure choice for shared hosting **if a few-seconds delay is acceptable**
— which for this app it is.

---

## 5. Decision matrix

| Question | → Reverb | → Pusher | → Polling |
|---|:---:|:---:|:---:|
| On Hostinger **shared** hosting? | ❌ | ✅ | ✅ |
| On a **VPS**? | ✅ | ✅ | ✅ |
| Need **sub-second** delivery? | ✅ | ✅ | ❌ |
| A **few-seconds** delay is fine? | ✅ | ✅ | ✅ |
| Avoid third-party dependency? | ✅ | ❌ | ✅ |
| Avoid running a daemon? | ❌ | ✅ | ✅ |
| Expect >100 simultaneous chat users? | ✅ | ⚠️ (paid/Ably) | ✅ |

### Recommendation by scenario
- **Staying on Hostinger shared, "instant" matters** → **Pusher** (+ lazy-connect). Watch the
  100-connection cap; move to Ably if you routinely exceed it.
- **Staying on Hostinger shared, a few-seconds delay is fine** → **Polling** (tuned with
  visibility-pause). Simplest, no dependencies, no caps.
- **Moving to a VPS** → **Reverb** (already built). No caps, no third party.

---

## 6. Current status & switch cost

| | Status | Cost to switch **to** this |
|---|---|---|
| **Polling** | ✅ **Active** — optimized (visibility-pause, adaptive 3s→10s, read-only peek polls), browser-verified | — (current) |
| **Reverb** | Built & browser-verified, kept **dormant** in the tree (`BROADCAST_CONNECTION=log`, `echo.js` not imported) | Flip env to `reverb` + restore `import './echo';` + rebuild; deploy daemon (VPS) |
| **Pusher** | Not implemented | ~1 dependency + env + `echo.js`/CSP edit + rebuild (small) |

> ✅ **Deployment note:** the app runs on optimized polling and is **safe to deploy to Hostinger
> shared hosting** as-is — no daemon, no third-party service, no connection cap. To re-enable real-time
> later, flip `BROADCAST_CONNECTION` (to `reverb` on a VPS, or `pusher` on shared) and restore the
> `import './echo';` line in `resources/js/app.js`; the event, channel auth, and config are all still
> in place.

---

*Related: `docs/architecture/ARCHITECTURE_TECHNICAL.md` (how the source Supabase Realtime worked),
`CLAUDE.md` → "Real-time" pillar, `prompts/tasks/03-messaging-notifications/33-realtime-chat-recommendations.md` (the task that added the WebSocket transport).*
