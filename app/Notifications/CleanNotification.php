<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notification in-app générique (stockée en base, affichée dans la cloche).
 * Un canal SMS / WhatsApp pourra être ajouté ici dans une prochaine version.
 */
class CleanNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $icon = 'bi-bell',
        public string $color = 'primary',
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
            'color' => $this->color,
        ];
    }
}
