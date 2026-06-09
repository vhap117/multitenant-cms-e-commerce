<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use VHAP\Core\Models\Tenant;

class VerifyTenantEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public Tenant $tenant;
    
    /**
     * Create a new notification instance.
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

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
            ->subject('Verify Email Address')
            ->line('Please click the button below to verify your email address and provision your new store.')
            ->action('Verify Email Address', $this->verificationUrl($notifiable))
            ->line('If you did not create an account, no further action is required.');
    }

    protected function verificationUrl($notifiable)
    {
        // Use a temporary signed route mapped to the VerifyEmailController
        return URL::temporarySignedRoute(
            'api.tenant.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'tenant' => $this->tenant->id,
                'hash' => sha1($this->tenant->email),
            ]
        );
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
