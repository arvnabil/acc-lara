<?php

namespace Modules\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    protected $fillable = ['is_enabled', 'phone', 'message', 'tooltip', 'position', 'show_online_badge', 'show_pulse_animation', 'open_in_new_tab', 'offset_bottom', 'offset_side', 'icon', 'button_color', 'active_pages'];

    protected $casts = ['is_enabled' => 'boolean', 'show_online_badge' => 'boolean', 'show_pulse_animation' => 'boolean', 'open_in_new_tab' => 'boolean', 'active_pages' => 'array'];

    public static function getInstance(): self
    {
        return static::query()->first() ?? static::create([
            'is_enabled' => true,
            'phone' => '6287780116800',
            'message' => 'Halo ACTiV, saya ingin konsultasi mengenai produk.',
            'tooltip' => 'Chat CS (24 Jam)',
            'active_pages' => ['all'],
        ]);
    }
}
