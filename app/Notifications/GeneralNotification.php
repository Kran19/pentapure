<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class GeneralNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $type;

    public function __construct($title, $message, $type = 'info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        $channels = ['database'];
        try {
            if (method_exists($notifiable, 'pushSubscriptions') && $notifiable->pushSubscriptions()->count() > 0) {
                $channels[] = WebPushChannel::class;
            }
        } catch (\Exception $e) {
            \Log::error('Notification Channel Error: ' . $e->getMessage());
        }
        return $channels;
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->message)
            ->icon('https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png')
            ->data(['url' => url('/')]);
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'url' => url('/'),
        ];
    }
}
