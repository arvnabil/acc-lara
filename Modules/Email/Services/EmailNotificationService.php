<?php

namespace Modules\Email\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Email\Models\EmailSetting;
use Modules\Email\Notifications\OrderEmailNotification;
use Modules\Orders\Models\Order;

class EmailNotificationService
{
    public function orderCreated(Order $order): void
    {
        $this->sendOrderEvent($order, 'created', 'order_created_enabled');
    }

    public function paymentReceived(Order $order): void
    {
        $this->sendOrderEvent($order, 'paid', 'payment_received_enabled');
    }

    public function orderStatusChanged(Order $order): void
    {
        $this->sendOrderEvent($order, 'status', 'order_status_enabled');
    }

    public function pointsAwarded(Order $order, int $points): void
    {
        $this->sendOrderEvent($order, 'points_awarded', 'points_awarded_enabled', $points);
    }

    private function sendOrderEvent(Order $order, string $event, string $setting, ?int $points = null): void
    {
        $settings = EmailSetting::getInstance();
        if (! $settings->{$setting}) {
            return;
        }

        $order->loadMissing('user');
        if ($order->user?->email) {
            Notification::route('mail', $order->user->email)->notify((new OrderEmailNotification($order, $event, $points, $order->user->name))->afterCommit());
        }

        if ($settings->admin_email && $settings->admin_email !== $order->user?->email) {
            Notification::route('mail', $settings->admin_email)->notify((new OrderEmailNotification($order, $event, $points, 'Admin'))->afterCommit());
        }
    }
}
