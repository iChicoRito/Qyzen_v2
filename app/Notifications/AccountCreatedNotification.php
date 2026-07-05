<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $temporaryPassword,
        public string $createdBy = 'Mr. Mark Adrianne Salunga',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $confirmUrl = URL::temporarySignedRoute(
            'account.activate',
            now()->addDays(7),
            ['user' => $notifiable]
        );

        return (new MailMessage)
            ->subject('Your Qyzen account is ready')
            ->view('emails.account-created', [
                'user' => $notifiable,
                'createdBy' => $this->createdBy,
                'temporaryPassword' => $this->temporaryPassword,
                'confirmUrl' => $confirmUrl,
            ]);
    }
}
