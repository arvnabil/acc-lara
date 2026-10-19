<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentSetting extends Model
{
    protected $fillable = ['midtrans_enabled', 'qris_enabled', 'bank_transfer_enabled', 'qris_image', 'qris_nmid', 'merchant_name', 'bank_accounts'];

    protected $casts = ['midtrans_enabled' => 'boolean', 'qris_enabled' => 'boolean', 'bank_transfer_enabled' => 'boolean', 'bank_accounts' => 'array'];

    public static function getInstance(): self
    {
        return static::query()->firstOrCreate([], [
            'midtrans_enabled' => true,
            'qris_enabled' => true,
            'bank_transfer_enabled' => true,
            'qris_image' => config('payment.qris.image'),
            'qris_nmid' => config('payment.qris.nmid'),
            'merchant_name' => config('payment.qris.merchant_name'),
            'bank_accounts' => config('payment.banks'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function methods(): array
    {
        return collect(config('payment.methods', []))->filter(function (array $method): bool {
            return match ($method['id']) {
                'midtrans' => $this->midtrans_enabled,
                'qris' => $this->qris_enabled,
                'bank_transfer' => $this->bank_transfer_enabled,
                default => false,
            };
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function banks(): array
    {
        return $this->bank_accounts ?: config('payment.banks', []);
    }

    /** @return array<string, mixed> */
    public function qris(): array
    {
        $image = $this->qris_image ?: config('payment.qris.image');

        if ($image && ! str_starts_with($image, 'http') && ! str_starts_with($image, '/')) {
            $image = Storage::disk('public')->url($image);
        }

        return [
            'image' => $image,
            'nmid' => $this->qris_nmid ?: config('payment.qris.nmid'),
            'merchant_name' => $this->merchant_name ?: config('payment.qris.merchant_name'),
        ];
    }
}
