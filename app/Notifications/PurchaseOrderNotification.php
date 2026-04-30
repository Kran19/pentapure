<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PurchaseOrderNotification extends Notification
{
    use Queueable;

    public $poId;
    public $message;

    public function __construct($poId, $message)
    {
        $this->poId = $poId;
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Purchase Request')
            ->icon('https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png')
            ->body($this->message)
            ->data(['url' => '/admin/po']);
    }

    public function toArray($notifiable): array
    {
        return [
            'po_id' => $this->poId,
            'message' => $this->message
        ];
    }
}
