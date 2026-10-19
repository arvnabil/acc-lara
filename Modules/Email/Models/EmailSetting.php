<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'mailer', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'from_address', 'from_name', 'admin_email', 'order_created_enabled',
        'payment_received_enabled', 'points_awarded_enabled', 'order_status_enabled', 'password_reset_enabled',
    ];

    protected $casts = [
        'smtp_password' => 'encrypted',
        'order_created_enabled' => 'boolean',
        'payment_received_enabled' => 'boolean',
        'points_awarded_enabled' => 'boolean',
        'order_status_enabled' => 'boolean',
        'password_reset_enabled' => 'boolean',
    ];

    public static function getInstance(): self
    {
        return static::query()->firstOrCreate([], [
            'mailer' => config('mail.default', 'log'),
            'from_name' => config('app.name'),
            'order_created_enabled' => true,
            'payment_received_enabled' => true,
            'points_awarded_enabled' => true,
            'order_status_enabled' => true,
            'password_reset_enabled' => true,
        ]);
    }
}
