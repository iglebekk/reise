<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripInvitationNotification extends Notification
{
    public function __construct(
        private readonly string $tripDestination,
        private readonly string $rawToken,
        private readonly CarbonInterface $expiresAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('trip-invitations.subject', ['trip' => $this->tripDestination]))
            ->greeting(__('trip-invitations.greeting', ['trip' => $this->tripDestination]))
            ->line(__('trip-invitations.introduction'))
            ->action(
                __('trip-invitations.action'),
                route('trip-invitations.show', ['token' => $this->rawToken]),
            )
            ->line(__('trip-invitations.expires', [
                'expires_at' => $this->expiresAt->translatedFormat('M j, Y H:i'),
            ]));
    }
}
