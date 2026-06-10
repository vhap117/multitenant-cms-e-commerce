<?php

namespace VHAP\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VHAP\Core\Models\Tenant;

class StoreReadyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $token,
        public string $email
    ) {}

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
        // Construct the reset password link pointing to the tenant's actual domain SPA
        // The exact path depends on the frontend SPA, but standard is /reset-password
        $url = "https://{$this->tenant->domain}/reset-password?token={$this->token}&email=" . urlencode($this->email);

        return (new MailMessage)
            ->subject('Your Store is Ready!')
            ->greeting('Hello!')
            ->line('Your new store environment has been successfully provisioned.')
            ->line('Click the button below to set up your administrator password and log in for the first time.')
            ->action('Set My Password', $url)
            ->line('Thank you for choosing our platform!');
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
