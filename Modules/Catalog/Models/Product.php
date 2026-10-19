<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Cart\Models\CartItem;
use Modules\Orders\Models\OrderItem;
use Modules\Review\Models\Review;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SEO\Traits\HasSeoMeta;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasSeoMeta;
    protected static function newFactory(): ProductFactory { return ProductFactory::new(); }

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'type',
        'product_type',
        'regular_price',
        'sale_price',
        'is_dummy_price',
        'is_price_outlier',
        'is_featured',
        'is_flash_sale',
        'is_addon',
        'stock',
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
        'brand_id',
        'sort_order',
        'description',
        'short_description',
        'source_id',
    ];

    protected $casts = [
        'is_dummy_price' => 'boolean',
        'is_price_outlier' => 'boolean',
        'is_featured' => 'boolean',
        'is_flash_sale' => 'boolean',
        'is_addon' => 'boolean',
        'weight_grams' => 'integer',
        'length_cm' => 'integer',
        'width_cm' => 'integer',
        'height_cm' => 'integer',
    ];

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = self::sanitizeRichText($value);
    }

    public function setShortDescriptionAttribute(?string $value): void
    {
        $this->attributes['short_description'] = self::sanitizeRichText($value);
    }

    private static function sanitizeRichText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$value.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach (iterator_to_array($document->getElementsByTagName('*')) as $element) {
            if (in_array(strtolower($element->nodeName), ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $element->parentNode?->removeChild($element);
                continue;
            }

            foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
                $name = strtolower($attribute->name);
                $attributeValue = trim($attribute->value);
                if (str_starts_with($name, 'on') || $name === 'style' || (($name === 'href' || $name === 'src') && preg_match('/^(?:javascript|vbscript|data):/i', $attributeValue))) {
                    $element->removeAttribute($attribute->name);
                }
            }
        }

        $container = $document->getElementsByTagName('div')->item(0);
        $html = '';
        foreach (iterator_to_array($container?->childNodes ?? []) as $child) {
            $html .= $document->saveHTML($child);
        }

        return trim($html);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function attributes(): MorphMany
    {
        return $this->morphMany(ProductAttribute::class, 'attributable');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function cartItems(): HasMany { return $this->hasMany(CartItem::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
}
