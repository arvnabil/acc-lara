<?php

return [
    'methods' => [
        ['id' => 'qris', 'title' => 'QRIS (Semua Bank & E-Wallet)', 'desc' => 'Scan & Bayar Instan', 'icon' => 'qr_code_scanner', 'badge' => 'Instan & Bebas Biaya'],
        ['id' => 'va', 'title' => 'Transfer Bank / Virtual Account', 'desc' => 'Verifikasi otomatis', 'icon' => 'account_balance'],
        ['id' => 'cc', 'title' => 'Kartu Kredit / Debit', 'desc' => 'Visa · Mastercard · JCB', 'icon' => 'credit_card'],
        ['id' => 'ewallet', 'title' => 'E-Wallet', 'desc' => 'GoPay / OVO / Dana / ShopeePay', 'icon' => 'smartphone'],
    ],
    'banks' => [
        ['id' => 'Mandiri', 'name' => 'Bank Mandiri Virtual Account', 'va' => '889012348892910'],
        ['id' => 'BCA', 'name' => 'BCA Virtual Account', 'va' => '1270012348892910'],
        ['id' => 'BNI', 'name' => 'BNI Virtual Account', 'va' => '8898012348892910'],
        ['id' => 'BRI', 'name' => 'BRI Virtual Account', 'va' => '0088012348892910'],
    ],
];
