<?php

namespace Modules\Email\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $recipientName) {}

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
        $accountEmail = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('mail')
            : ($notifiable->email ?? null);

        return (new MailMessage)
            ->subject('Akun Accommerce berhasil dibuat')
            ->view('email::emails.standard', [
                'subjectLine' => 'Akun Accommerce berhasil dibuat',
                'eyebrow' => 'Selamat Datang di Accommerce',
                'title' => 'Akun berhasil dibuat',
                'recipientName' => $this->recipientName,
                'intro' => 'Akun Anda sudah berhasil dibuat dan siap digunakan untuk berbelanja.',
                'details' => [
                    'Email akun' => $accountEmail,
                    'Status' => 'Aktif',
                ],
                'actionText' => 'Buka Akun',
                'actionUrl' => url('/akun'),
                'outro' => 'Terima kasih telah bergabung dengan Accommerce.',
            ]);
    }
}
