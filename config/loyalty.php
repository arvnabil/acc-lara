<?php

return [
    'tiers' => [
        'classic' => ['min' => 0, 'max' => 499, 'bonus_percent' => 0],
        'bronze' => ['min' => 500, 'max' => 1499, 'bonus_percent' => 0],
        'silver' => ['min' => 1500, 'max' => 7499, 'bonus_percent' => 5],
        'gold' => ['min' => 7500, 'max' => 14999, 'bonus_percent' => 10],
        'platinum' => ['min' => 15000, 'max' => 39999, 'bonus_percent' => 20],
        'diamond' => ['min' => 40000, 'max' => null, 'bonus_percent' => 30],
    ],
    'point_value_idr' => 100,
    'spend_per_point_idr' => 10000,
    'referral' => ['enabled' => true, 'percent' => 0.5, 'max_idr' => 500000, 'registration_points' => 0],
    'redemption' => [
        'ewallet_points' => [500, 1000, 2500],
        'voucher_points' => [750, 1500, 3000],
        'voucher_validity_days' => 30,
        'voucher_min_order_per_point_idr' => 1000,
        'ewallet_monthly_limit' => 5000,
        'maturity_days' => 7,
        'expiry_months' => 24,
        'return_days' => 7,
    ],
    'shipping_subsidy_idr' => [
        'bronze' => 30000,
        'silver' => 50000,
        'gold' => 50000, // BELUM DIPUTUSKAN, O-16
        'platinum' => 50000, // BELUM DIPUTUSKAN, O-16
        'diamond' => 50000, // BELUM DIPUTUSKAN, O-16
    ],
];
