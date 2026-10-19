<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationMenu extends Model
{
    protected $fillable = ['label', 'target_type', 'target_value', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    protected $appends = ['href'];

    public function getHrefAttribute(): string
    {
        return match ($this->target_type) {
            'sale' => '/katalog?sort=sale',
            'category' => '/katalog?kategori='.urlencode((string) $this->target_value),
            'url' => (string) $this->target_value,
            default => (string) ($this->target_value ?: '/'),
        };
    }
}
