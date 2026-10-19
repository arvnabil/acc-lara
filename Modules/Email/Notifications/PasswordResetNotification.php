<?php

namespace Modules\Email\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token) {}

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
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Reset Password Accommerce')
            ->view('email::emails.standard', [
                'subjectLine' => 'Reset Password Accommerce',
                'eyebrow' => 'Keamanan Akun',
                'title' => 'Reset password akun',
                'recipientName' => $notifiable->name,
                'intro' => 'Kami menerima permintaan untuk mengatur ulang password akun Anda.',
                'details' => ['Masa berlaku' => 'Terbatas'],
                'actionText' => 'Reset Password',
                'actionUrl' => $url,
                'outro' => 'Jika Anda tidak meminta reset password, abaikan email ini.',
            ]);
    }
}
