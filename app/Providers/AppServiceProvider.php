<?php

namespace App\Providers;

use App\Models\Notification;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Task 25 bell: feed real data to the notifications drawer (All tab). Owner-scoped,
        // capped to the recent set. Static Team/Following tabs stay demo for now.
        // Task 30: Inbox tab + chat drawer conversation list, same composer.
        View::composer('layouts.partials._demo1_topbar_icons', function ($view) {
            $user = Auth::user();
            $conversations = $user ? app(ConversationService::class)->conversationListFor($user) : collect();

            $view->with([
                'notifications' => $user ? Notification::recentForBell($user->id) : collect(),
                'unreadCount' => $user
                    ? Notification::forRecipient($user->id)->where('is_read', false)->count()
                    : 0,
                'conversations' => $conversations,
                'messageUnreadCount' => $conversations->sum('unreadCount'),
            ]);
        });
    }
}
