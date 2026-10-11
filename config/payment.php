<?php

return [
    'methods' => [
        ['id' => 'qris', 'title' => 'QRIS (Semua Bank & E-Wallet)', 'desc' => 'Scan & Bayar Instan'],
        ['id' => 'va', 'title' => 'Transfer Bank / Virtual Account', 'desc' => 'Verifikasi otomatis'],
        ['id' => 'cc', 'title' => 'Kartu Kredit / Debit', 'desc' => 'Visa · Mastercard · JCB'],
        ['id' => 'ewallet', 'title' => 'E-Wallet', 'desc' => 'GoPay / OVO / Dana / ShopeePay'],
    ],
    'banks' => [
        ['id' => 'Mandiri', 'name' => 'Bank Mandiri Virtual Account', 'va' => '889012348892910'],
        ['id' => 'BCA', 'name' => 'BCA Virtual Account', 'va' => '1270012348892910'],
        ['id' => 'BNI', 'name' => 'BNI Virtual Account', 'va' => '8898012348892910'],
        ['id' => 'BRI', 'name' => 'BRI Virtual Account', 'va' => '0088012348892910'],
    ],
];
