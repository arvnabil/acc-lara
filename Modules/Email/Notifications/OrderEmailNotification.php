<?php

namespace Modules\Email\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Orders\Models\Order;

class OrderEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $event,
        private readonly ?int $points = null,
        private readonly ?string $recipientName = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'emails'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = $this->recipientName ?? data_get($notifiable, 'name', 'Pelanggan');
        $title = match ($this->event) {
            'created' => 'Pesanan berhasil dibuat',
            'paid' => 'Pembayaran berhasil diterima',
            'points_awarded' => 'Poin pesanan berhasil ditambahkan',
            default => match ($this->order->status?->value) {
                'awaiting_verification' => 'Pesanan menunggu verifikasi pembayaran',
                'processing' => 'Pesanan sedang diproses',
                'packed' => 'Pesanan sudah dikemas',
                'shipped' => 'Pesanan sedang dikirim',
                'completed' => 'Pesanan telah selesai',
                'returned' => 'Pesanan dikembalikan',
                'cancelled' => 'Pesanan dibatalkan',
                default => 'Status pesanan diperbarui',
            },
        };

        return (new MailMessage)
            ->subject($title.' - '.$this->order->number)
            ->view('email::emails.standard', [
                'subjectLine' => $title.' - '.$this->order->number,
                'eyebrow' => 'Informasi Pesanan',
                'title' => $title,
                'recipientName' => $recipientName,
                'intro' => 'Berikut adalah pembaruan terbaru untuk pesanan Anda.',
                'details' => [
                    'Nomor pesanan' => $this->order->number,
                    'Status' => $this->event === 'points_awarded'
                        ? '+'.number_format((int) $this->points).' poin'
                        : $this->statusLabel(),
                    'Total' => 'Rp '.number_format((int) $this->order->grand_total, 0, ',', '.'),
                ],
                'actionText' => 'Buka Akun',
                'actionUrl' => url('/akun'),
                'outro' => 'Terima kasih telah berbelanja di Accommerce.',
            ]);
    }

    private function statusLabel(): string
    {
        return match ($this->order->status?->value) {
            'pending_payment' => 'Menunggu Pembayaran',
            'awaiting_verification' => 'Menunggu Verifikasi Pembayaran',
            'paid' => 'Pembayaran Berhasil Diterima',
            'processing' => 'Pesanan Sedang Diproses',
            'packed' => 'Pesanan Sudah Dikemas',
            'shipped' => 'Pesanan Sedang Dikirim',
            'completed' => 'Pesanan Selesai',
            'returned' => 'Pesanan Dikembalikan',
            'cancelled' => 'Pesanan Dibatalkan',
            default => 'Status Pesanan Diperbarui',
        };
    }
}
