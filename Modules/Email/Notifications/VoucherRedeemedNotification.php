<?php

namespace Modules\Email\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Loyalty\Models\Redemption;

class VoucherRedeemedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Redemption $redemption) {}

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
        $voucher = $this->redemption->voucher;
        $subject = 'Voucher berhasil diklaim - '.$voucher->code;

        return (new MailMessage)
            ->subject($subject)
            ->view('email::emails.standard', [
                'subjectLine' => $subject,
                'eyebrow' => 'Loyalty Accommerce',
                'title' => 'Voucher berhasil diklaim',
                'recipientName' => data_get($notifiable, 'name', 'Pelanggan'),
                'intro' => 'Penukaran poin Anda berhasil. Berikut detail voucher yang sudah diterbitkan.',
                'details' => [
                    'Poin ditukar' => number_format((int) $this->redemption->points, 0, ',', '.').' poin',
                    'Kode voucher' => $voucher->code,
                    'Nilai voucher' => 'Rp '.number_format((int) $voucher->discount_value, 0, ',', '.'),
                    'Minimum belanja' => 'Rp '.number_format((int) $voucher->min_order, 0, ',', '.'),
                    'Berlaku sampai' => $voucher->expires_at?->format('d M Y') ?? 'Tidak terbatas',
                ],
                'actionText' => 'Lihat Voucher Saya',
                'actionUrl' => url('/akun?tab=vouchers'),
                'outro' => 'Gunakan kode voucher ini saat checkout sebelum masa berlakunya berakhir.',
            ]);
    }
}
