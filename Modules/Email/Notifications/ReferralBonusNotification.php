<?php

namespace Modules\Email\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Orders\Models\Order;

class ReferralBonusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order, private readonly int $points) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function viaQueues(): array
    {
        return ['mail' => 'emails'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Bonus referral berhasil dicatat - '.$this->order->number;

        return (new MailMessage)->subject($subject)->view('email::emails.standard', [
            'subjectLine' => $subject,
            'eyebrow' => 'Loyalty Accommerce',
            'title' => 'Bonus referral berhasil dicatat',
            'recipientName' => data_get($notifiable, 'name', 'Pelanggan'),
            'intro' => 'Anda mendapatkan bonus karena kode referral Anda digunakan pada pesanan berikut.',
            'details' => [
                'Nomor pesanan' => $this->order->number,
                'Bonus referral' => '+'.number_format($this->points, 0, ',', '.').' poin',
                'Status' => 'Menunggu masa tunggu retur',
            ],
            'actionText' => 'Lihat Poin Saya',
            'actionUrl' => url('/akun?tab=loyalty'),
            'outro' => 'Bonus akan dapat digunakan setelah masa tunggu retur selesai.',
        ]);
    }
}
