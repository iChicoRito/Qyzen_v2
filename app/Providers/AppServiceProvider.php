<?php

namespace App\Providers;

use App\Models\Notification;
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
        // capped to the recent set. Static Inbox/Team/Following tabs stay demo for now.
        View::composer('layouts.partials._demo1_topbar_icons', function ($view) {
            $userId = Auth::id();
            $view->with([
                'notifications' => $userId ? Notification::recentForBell($userId) : collect(),
                'unreadCount' => $userId
                    ? Notification::forRecipient($userId)->where('is_read', false)->count()
                    : 0,
            ]);
        });
    }
}
