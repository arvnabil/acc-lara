<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payment:check-config')]
#[Description('Memeriksa konfigurasi pembayaran manual dan Midtrans')]
class CheckPaymentConfig extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $errors = [];

        if (app()->environment('production') && ! config('services.midtrans.server_key')) {
            $errors[] = 'MIDTRANS_SERVER_KEY wajib diisi pada production.';
        }

        foreach (config('payment.banks', []) as $bank) {
            if (in_array('BELUM DIISI', [$bank['account_name'] ?? null, $bank['account_number'] ?? null], true)) {
                $errors[] = "Rekening {$bank['id']} masih memakai placeholder BELUM DIISI.";
            }
        }

        if (config('payment.qris.nmid') === 'BELUM DIISI') {
            $errors[] = 'PAYMENT_QRIS_NMID masih memakai placeholder BELUM DIISI.';
        }

        if ($errors !== [] && app()->environment('production')) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        foreach ($errors as $error) {
            $this->warn($error);
        }

        $this->info('Konfigurasi pembayaran siap digunakan.');

        return self::SUCCESS;
    }
}
