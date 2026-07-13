<?php

namespace App\Providers;

use App\Models\Notification;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject('Verify your email address')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verifyUrl' => $url,
            ]));

        Event::listen(Verified::class, function (Verified $event): void {
            if (! $event->user instanceof User || ! $event->user->hasRole('student')) {
                return;
            }

            $adminIds = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query
                    ->where('name', 'admin')
                    ->where('tbl_roles.is_active', true))
                ->pluck('id')
                ->all();

            app(NotificationService::class)->emitToMany($event->user, 'student_email_verified', $adminIds, [
                'title' => 'Student email confirmed',
                'message' => "{$event->user->name} confirmed {$event->user->email}.",
                'link_path' => route('admin.users.show', $event->user, false),
                'metadata' => ['student_id' => $event->user->id, 'email' => $event->user->email],
            ]);
        });

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
