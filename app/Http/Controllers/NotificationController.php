<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Task 25 read/delivery side: owner-scoped list, unread count, and mark-read.
// GET /notifications doubles as the poll endpoint (request/response first — no live transport yet).
// The forRecipient() scope is the correctness boundary: every query is filtered to the caller.
class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = (int) Auth::id();

        // Poll endpoint: unread count + a server-rendered items fragment (reuses the bell's Blade,
        // so the client never re-templates notifications). Swapped into the drawer every ~30s.
        return response()->json([
            'unread_count' => Notification::forRecipient($userId)->where('is_read', false)->count(),
            'html' => view('layouts.partials._notification_items', [
                'notifications' => Notification::recentForBell($userId),
            ])->render(),
        ]);
    }

    public function markRead(int $notification): JsonResponse
    {
        $userId = (int) Auth::id();
        // Scoped find — another user's row 404s. No Policy needed; the scope is the boundary.
        $row = Notification::forRecipient($userId)->findOrFail($notification);
        $row->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'unread_count' => Notification::forRecipient($userId)->where('is_read', false)->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        Notification::forRecipient((int) Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // JSON for the poll/AJAX caller; plain form post (the bell footer) just bounces back.
        return $request->expectsJson()
            ? response()->json(['unread_count' => 0])
            : back();
    }
}
