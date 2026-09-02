<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdministratorTemporaryPasswordAssigned extends Notification
{
    use Queueable;

    public function __construct(public string $temporaryPassword) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your temporary Gym CRM Portal password')
            ->greeting('Hello,')
            ->line('An administrator has assigned a temporary password to your Gym CRM Portal account.')
            ->line("Login email: {$notifiable->email}")
            ->line("Temporary password: {$this->temporaryPassword}")
            ->action('Sign in and change password', route('login'))
            ->line('You must replace this temporary password immediately after signing in.')
            ->line('If you did not expect this change, contact your gym administrator.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
