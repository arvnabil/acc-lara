<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\BannerFactory;

class Banner extends Model
{
    use HasFactory;
    protected static function newFactory(): BannerFactory { return BannerFactory::new(); }
    protected $fillable = ['title', 'image_url', 'mobile_image_url', 'target_url', 'sort_order', 'is_active', 'starts_at', 'expires_at'];
    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'datetime', 'expires_at' => 'datetime'];
}
