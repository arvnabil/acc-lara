<?php

return [
    'cart_estimate' => 350000,
    'rajaongkir' => [
        'enabled' => (bool) env('RAJAONGKIR_ENABLED', false),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1/'),
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'origin' => env('RAJAONGKIR_ORIGIN_ID'),
        'courier' => env('RAJAONGKIR_COURIERS', 'jne:jnt:sicepat:ide:sap:ninja:tiki:lion:pos:wahana'),
        'price' => env('RAJAONGKIR_PRICE', 'lowest'),
        'default_weight_grams' => (int) env('RAJAONGKIR_DEFAULT_WEIGHT_GRAMS', 1000),
        'timeout' => (int) env('RAJAONGKIR_TIMEOUT', 10),
    ],
    'biteship' => [
        'enabled' => (bool) env('BITESHIP_ENABLED', false),
        'base_url' => env('BITESHIP_BASE_URL', 'https://api.biteship.com'),
        'api_key' => env('BITESHIP_API_KEY'),
        'origin_postal_code' => env('BITESHIP_ORIGIN_POSTAL_CODE'),
        'couriers' => env('BITESHIP_COURIERS', 'jne,jnt,sicepat,tiki,pos'),
        'default_weight_grams' => (int) env('BITESHIP_DEFAULT_WEIGHT_GRAMS', 1000),
        'default_length' => (int) env('BITESHIP_DEFAULT_LENGTH', 10),
        'default_width' => (int) env('BITESHIP_DEFAULT_WIDTH', 10),
        'default_height' => (int) env('BITESHIP_DEFAULT_HEIGHT', 10),
        'cache_minutes' => (int) env('BITESHIP_CACHE_MINUTES', 10),
        'timeout' => (int) env('BITESHIP_TIMEOUT', 10),
    ],
    'options' => [
        ['id' => 'kurir-khusus', 'title' => 'Kurir Khusus IT & AV Accommerce', 'desc' => 'Estimasi 1-2 hari kerja, instalasi dasar gratis', 'price' => 350000],
        ['id' => 'jne-yes', 'title' => 'JNE YES (Yakin Esok Sampai)', 'desc' => 'Estimasi 1 hari kerja, asuransi penuh', 'price' => 450000],
        ['id' => 'sicepat', 'title' => 'SiCepat BEST (Besok Sampai)', 'desc' => 'Estimasi 1-2 hari kerja', 'price' => 280000],
        ['id' => 'pickup', 'title' => 'Ambil di Toko (BSD City)', 'desc' => 'Senin–Jumat 08:30–17:30 WIB', 'price' => 0],
    ],
];
