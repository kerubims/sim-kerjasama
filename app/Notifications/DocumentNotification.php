<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentNotification extends Notification
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected string $icon;
    protected ?string $url;

    public function __construct(string $title, string $message, string $icon = 'fa-file-lines', ?string $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->url = $url;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'icon'    => $this->icon,
            'url'     => $this->url,
        ];
    }
}
