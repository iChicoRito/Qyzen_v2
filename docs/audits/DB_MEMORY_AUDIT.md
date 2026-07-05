# Qyzen v2 — Database Memory Efficiency & Performance Audit (Task 31)

> InnoDB buffer-pool (memory cache) efficiency for the `qyzen_v2` MySQL database.
> Reproduce anytime with **`php artisan db:memory-audit`** (read-only).
>
> Last run: 2026-07-05 · MySQL **8.4.3** (Laragon, Windows) · pool warm (uptime ~3m).
>
> **Status:** Recommendation #1 (table-open cache) **applied** 2026-07-05 —
> `table_open_cache`/`table_definition_cache` set to 2000, live via `SET GLOBAL`
> and persisted in `C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini`. Others left
> as recommendations.

---

## Summary (plain English)

**The database is not memory-starved. Nearly every read is already served from
memory, not disk.** The whole dataset is ~2.2 MB and the memory cache (InnoDB
buffer pool) is 128 MB — **58.9× more than needed**. After warm-up, disk reads
stop entirely; the raw "94%" hit-rate figure below is one-time startup warmup,
not a shortage of memory.

So there is **no buffer-pool sizing problem to fix.** The only genuinely useful,
immediately-applicable win the audit found is the **table-open cache**, which is
too small and is being churned (62% hit rate). Everything else is either already
healthy or a cheap dev-speed tweak.

**Top 3 actions (all no-hardware, ordered by value):**
1. Raise `table_open_cache` 256 → 2000 and `table_definition_cache` 256 → 2000.
   *Dynamic — takes effect with `SET GLOBAL`, no restart.*
2. Take cache/session churn off the database: `CACHE_STORE=file`,
   `SESSION_DRIVER=file` in `.env`. *`.env`-only, no DB restart.*
3. (Dev only) `innodb_flush_log_at_trx_commit = 2` for faster writes.

---

## Method & caveat

All figures come from `SHOW GLOBAL STATUS` / `SHOW GLOBAL VARIABLES` and
`information_schema`, collected by `app/Console/Commands/DbMemoryAudit.php`.
Status counters are **cumulative since server start (`Uptime`)**, so a freshly
started server shows a depressed hit rate until the cache warms. For a
representative number, exercise the app (browse pages or run `php artisan test`)
and re-run. Physical reads flatlining while logical reads keep climbing = warm.

---

## Findings

### 1. Memory-vs-disk hit rate — **effectively ~100% once warm**
| Metric | Value |
|--------|-------|
| Logical reads (served from memory+disk) | 19,395 |
| Physical reads (had to hit disk) | **1,109 — flat, stopped growing** |
| Cumulative hit rate | 94.28% |
| Interpretation | Warmup artifact. Disk reads have stopped; the rate converges toward 99.9%+ with continued use. |

The 1,109 physical reads are the one-time cost of loading the entire 2.2 MB
dataset into memory. They are not increasing, which means **new queries are
served 100% from memory.**

### 2. Buffer-pool sizing — **more than sufficient (58.9× headroom)**
| Metric | Value | Verdict |
|--------|-------|---------|
| `innodb_buffer_pool_size` | 128.00 MB | ample |
| Total data + index size | 2.17 MB | tiny |
| Pool covers dataset | **58.9×** | fits entirely, many times over |
| Pages free / total | 6,941 / 8,192 | 85% of pool unused |
| Waits for a free page | 0 | no pressure |
| Dirty pages | 0 | no write backlog |

There is nothing to gain by enlarging the pool — it already holds the whole
database with room to spare. Leave `innodb_buffer_pool_size` at the 128 MB
default. (If the dataset ever grows past ~100 MB, revisit: keep the pool ≥ the
data+index size, capped at ~50–70% of machine RAM.)

### 3. Temp tables spilling to disk — **healthy**
0 of 8 temp tables spilled to disk (0%). `tmp_table_size`/`max_heap_table_size`
are 16 MB each — adequate at this scale. No action.

### 4. Table-open cache — **the one real problem**
| Metric | Value |
|--------|-------|
| Hits / misses | 2,258 / 1,368 |
| Hit rate | **62.27%** |
| `Opened_tables` vs `table_open_cache` | 1,368 vs **256** |

`table_open_cache` (256) is smaller than the number of table opens, so table
handles are being evicted and reopened repeatedly — wasted file operations on
every request that touches many `tbl_*` tables. **This is the highest-value,
lowest-risk change** and it is dynamic (no restart).

### 5. Full-scan signals — **benign at this scale**
`Select_full_join` 2 · `Select_scan` 23 · `Handler_read_rnd_next` ~6k. Low
absolute numbers; expected for admin list/report pages over ≤560-row tables.
Not worth chasing now — revisit only if a specific page is slow (add an index
then, per the query's `EXPLAIN`).

### 6. Largest tables (what fills the pool)
| Table | MB | ~rows |
|-------|----|-------|
| tbl_scores | 0.33 | 400 |
| tbl_notifications | 0.22 | 560 |
| tbl_assessments | 0.14 | 21 |
| tbl_quizzes | 0.14 | 300 |
| tbl_users | 0.13 | 102 |
| tbl_subjects | 0.11 | 5 |
| tbl_learning_materials | 0.09 | 3 |
| tbl_enrolled | 0.08 | 100 |

Whole database ≈ 2.2 MB. Note: `CACHE_STORE`, `SESSION_DRIVER`, and
`QUEUE_CONNECTION` are all `database`, so the `cache`, `sessions`, `jobs` tables
also take live write traffic — see recommendation #2.

---

## Recommendations (prioritized)

| # | Change | Why | Value | Effort |
|---|--------|-----|-------|--------|
| 1 | `table_open_cache = 2000`, `table_definition_cache = 2000` | 62% → ~100% table-cache hit; stops handle churn | **High** | Dynamic, no restart |
| 2 | `.env`: `CACHE_STORE=file`, `SESSION_DRIVER=file` | Moves cache/session write churn off InnoDB | Medium | `.env` only |
| 3 | `innodb_flush_log_at_trx_commit = 2` *(dev only)* | Faster commits (flush redo once/sec vs every commit) | Medium (writes) | my.ini + restart |
| 4 | Leave `innodb_buffer_pool_size = 128M` | Already 58.9× the data; enlarging wastes RAM | — | None |
| 5 | **Do not** enable query cache | Removed in MySQL 8; not an option | — | — |

**Apply #1 right now without a restart** (persist it in my.ini too, step below):
```sql
SET GLOBAL table_open_cache = 2000;
SET GLOBAL table_definition_cache = 2000;
```

### my.ini snippet (persist across restarts)
Add under `[mysqld]` in your Laragon/XAMPP `my.ini`, then restart MySQL:
```ini
[mysqld]
# Task 31 audit — table-handle churn (was 256, 62% hit)
table_open_cache        = 2000
table_definition_cache  = 2000

# Dev-only: faster writes. Durability ceiling — up to ~1s of committed
# transactions can be lost on an OS/power crash. Use 1 (default) for production.
innodb_flush_log_at_trx_commit = 2

# Buffer pool: 128M is already ~59x the dataset — left at default on purpose.
# innodb_buffer_pool_size = 128M
```

*ponytail note: `innodb_flush_log_at_trx_commit=2` trades crash-durability for
write speed — appropriate for this dev box, revert to `1` in production.*

---

## Re-measure (verify the wins)
```bash
php artisan db:memory-audit
```
After applying #1: table-open-cache hit rate should climb toward ~100% and
`Opened_tables` stop exceeding `table_open_cache`. Buffer-pool hit rate should
sit ≥99% once warm, with free pages > 0 and 0 waits — confirming memory is not
the bottleneck.
