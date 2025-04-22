<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoMessage;

class ExpoNotification extends Notification
{
    use Queueable;

    public $title;
    public $body;
    /**
     * Create a new notification instance.
     */
    public function __construct($title, $body)
    {
        //
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['expo'];
    }

    public function toExpo($notifiable): ExpoMessage
    {
        return ExpoMessage::create($this->title)
            ->body($this->body)
            ->data($notifiable->only('email', 'id'))
            ->expiresAt(now()->addHour())
            ->priority('high')
            ->playSound();
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
