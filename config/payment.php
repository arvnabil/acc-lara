<?php

return [
    'methods' => [
        ['id' => 'midtrans', 'title' => 'Midtrans', 'desc' => 'Kartu, e-wallet, dan transfer bank', 'icon' => 'payments', 'badge' => 'Rekomendasi'],
        ['id' => 'qris', 'title' => 'QRIS (Semua Bank & E-Wallet)', 'desc' => 'Scan & bayar manual', 'icon' => 'qr_code_scanner', 'badge' => 'Manual'],
        ['id' => 'bank_transfer', 'title' => 'Transfer Bank', 'desc' => 'Verifikasi manual oleh admin', 'icon' => 'account_balance', 'badge' => 'Manual'],
    ],
    'banks' => [
        ['id' => 'bca', 'name' => 'Bank Central Asia', 'account_name' => env('PAYMENT_BANK_BCA_NAME', 'BELUM DIISI'), 'account_number' => env('PAYMENT_BANK_BCA_NUMBER', 'BELUM DIISI')],
        ['id' => 'mandiri', 'name' => 'Bank Mandiri', 'account_name' => env('PAYMENT_BANK_MANDIRI_NAME', 'BELUM DIISI'), 'account_number' => env('PAYMENT_BANK_MANDIRI_NUMBER', 'BELUM DIISI')],
        ['id' => 'bni', 'name' => 'Bank Negara Indonesia', 'account_name' => env('PAYMENT_BANK_BNI_NAME', 'BELUM DIISI'), 'account_number' => env('PAYMENT_BANK_BNI_NUMBER', 'BELUM DIISI')],
        ['id' => 'bri', 'name' => 'Bank Rakyat Indonesia', 'account_name' => env('PAYMENT_BANK_BRI_NAME', 'BELUM DIISI'), 'account_number' => env('PAYMENT_BANK_BRI_NUMBER', 'BELUM DIISI')],
    ],
    'qris' => [
        'image' => env('PAYMENT_QRIS_IMAGE', '/method/qris-accommerce.png'),
        'nmid' => env('PAYMENT_QRIS_NMID', 'BELUM DIISI'),
        'merchant_name' => env('PAYMENT_QRIS_MERCHANT_NAME', 'Accommerce by ACTiV'),
    ],
    'legacy_method_aliases' => [
        'cc' => 'midtrans',
        'ewallet' => 'midtrans',
        'va' => 'bank_transfer',
    ],
];
