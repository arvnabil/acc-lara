<?php

namespace Modules\Cart\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Clients\Models\Address;
use Modules\Orders\Models\Order;
use Throwable;

class BiteshipShippingService
{
    /** @return array<int, array<string, mixed>> */
    public function searchAreas(string $input): array
    {
        $input = trim($input);
        if (! $this->isConfigured() || mb_strlen($input) < 3) {
            return [];
        }

        $key = 'biteship:areas:'.sha1(mb_strtolower($input));

        $cached = Cache::get($key);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = $this->client()->get('/v1/maps/areas', ['countries' => 'ID', 'input' => $input, 'type' => 'single']);
        } catch (Throwable $exception) {
            Log::warning('Biteship area search failed.', ['message' => $exception->getMessage()]);
            return [];
        }

        if ($response->failed()) {
            Log::warning('Biteship area search failed.', ['status' => $response->status()]);
            return [];
        }

        $areas = collect($response->json('areas', []))->map(fn (array $area): array => [
                'id' => (string) ($area['id'] ?? ''),
                'name' => (string) ($area['name'] ?? ''),
                'postal_code' => (string) ($area['postal_code'] ?? ''),
                'province' => (string) ($area['administrative_division_level_1_name'] ?? ''),
                'city' => (string) ($area['administrative_division_level_2_name'] ?? ''),
                'district' => (string) ($area['administrative_division_level_3_name'] ?? ''),
            ])->filter(fn (array $area): bool => $area['id'] !== '')->values()->all();

        if ($areas !== []) {
            Cache::put($key, $areas, now()->addMinutes(10));
        }

        return $areas;
    }

    /** @param iterable<object> $items */
    public function optionsForAddress(?Address $address, iterable $items): array
    {
        if (! $address) {
            return config('shipping.options', []);
        }

        return $this->optionsForAddressData(['postal_code' => $address->postal_code, 'destination_area_id' => $address->biteship_area_id ?? null], $items);
    }

    /** @param array<string, mixed> $address @param iterable<object> $items */
    public function optionsForAddressData(array $address, iterable $items): array
    {
        if (! $this->isConfigured() || blank($address['postal_code'] ?? null)) {
            return config('shipping.options', []);
        }

        $items = collect($items);
        $payload = [
            'origin_postal_code' => (int) config('shipping.biteship.origin_postal_code'),
            'destination_postal_code' => (int) $address['postal_code'],
            'couriers' => (string) config('shipping.biteship.couriers'),
            'items' => $items->map(fn (object $item): array => [
                'name' => (string) ($item->variant?->name ?? $item->product?->name ?? 'Product'),
                'description' => 'Accommerce product',
                'value' => (int) ($item->variant?->sale_price ?: $item->variant?->regular_price ?: $item->product?->sale_price ?: $item->product?->regular_price ?: 0),
                'quantity' => (int) ($item->quantity ?? 1),
                'weight' => max(1, (int) ($item->variant?->weight_grams ?? $item->product?->weight_grams ?? config('shipping.biteship.default_weight_grams', 1000))),
                'length' => max(1, (int) ($item->variant?->length_cm ?? $item->product?->length_cm ?? config('shipping.biteship.default_length', 10))),
                'width' => max(1, (int) ($item->variant?->width_cm ?? $item->product?->width_cm ?? config('shipping.biteship.default_width', 10))),
                'height' => max(1, (int) ($item->variant?->height_cm ?? $item->product?->height_cm ?? config('shipping.biteship.default_height', 10))),
            ])->values()->all(),
        ];
        if (filled($address['destination_area_id'] ?? null)) {
            $payload['destination_area_id'] = (string) $address['destination_area_id'];
        }

        $key = 'biteship:rates:'.sha1(json_encode($payload, JSON_THROW_ON_ERROR));

        return Cache::remember($key, now()->addMinutes((int) config('shipping.biteship.cache_minutes', 10)), function () use ($payload): array {
            try {
                $response = $this->client()->post('/v1/rates/couriers', $payload);
            } catch (Throwable $exception) {
                Log::warning('Biteship shipping rate request failed.', ['message' => $exception->getMessage()]);
                return config('shipping.options', []);
            }

            if ($response->failed()) {
                Log::warning('Biteship shipping rate request failed.', ['status' => $response->status()]);
                return config('shipping.options', []);
            }

            return collect($response->json('pricing', []))->map(function (array $service): ?array {
                if (! is_numeric($service['price'] ?? null)) {
                    return null;
                }

                $company = (string) ($service['courier_code'] ?? $service['company'] ?? 'courier');
                $serviceCode = (string) ($service['courier_service_code'] ?? 'regular');

                return [
                    'id' => 'biteship-'.$company.'-'.$serviceCode,
                    'title' => trim((string) ($service['courier_name'] ?? strtoupper($company)).' '.($service['courier_service_name'] ?? strtoupper($serviceCode))),
                    'desc' => $service['duration'] ?? 'Estimasi pengiriman Biteship',
                    'price' => (int) $service['price'],
                    'courier' => $company,
                    'service' => $serviceCode,
                ];
            })->filter()->values()->all();
        });
    }

    public function syncTracking(Order $order): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $waybill = trim((string) ($order->biteship_waybill_id ?: $order->tracking_number));
        if ($waybill === '') {
            return false;
        }

        $trackingId = trim((string) $order->biteship_tracking_id);
        $courier = $this->courierCode($order);
        if ($trackingId === '' && $courier === '') {
            Log::warning('Biteship tracking skipped because courier code is missing.', ['order_id' => $order->id, 'waybill' => $waybill]);
            return false;
        }

        $cacheKey = 'biteship:tracking:'.sha1(implode('|', [$order->id, $trackingId, $waybill, $courier]));

        return Cache::remember($cacheKey, now()->addMinutes((int) config('shipping.biteship.cache_minutes', 10)), function () use ($order, $trackingId, $waybill, $courier): bool {
            try {
                $path = $trackingId !== ''
                    ? '/v1/trackings/'.rawurlencode($trackingId)
                    : '/v1/trackings/'.rawurlencode($waybill).'/couriers/'.rawurlencode($courier);
                $response = $this->client()->get($path);
            } catch (Throwable $exception) {
                Log::warning('Biteship tracking request failed.', ['order_id' => $order->id, 'message' => $exception->getMessage()]);
                return false;
            }

            if ($response->failed()) {
                Log::warning('Biteship tracking request failed.', ['order_id' => $order->id, 'status' => $response->status(), 'body' => $response->json()]);
                return false;
            }

            $payload = $response->json();
            $history = collect($payload['history'] ?? [])->map(fn (mixed $event): array => [
                'note' => (string) data_get($event, 'note', ''),
                'status' => (string) data_get($event, 'status', ''),
                'updated_at' => data_get($event, 'updated_at'),
            ])->filter(fn (array $event): bool => $event['note'] !== '' || $event['status'] !== '')->values()->all();

            $order->forceFill([
                'biteship_tracking_id' => $payload['id'] ?? $order->biteship_tracking_id,
                'biteship_waybill_id' => $payload['waybill_id'] ?? $order->biteship_waybill_id ?? $order->tracking_number,
                'biteship_courier_code' => data_get($payload, 'courier.company', $order->biteship_courier_code ?: $courier),
                'biteship_status' => $payload['status'] ?? $order->biteship_status,
                'biteship_tracking_url' => $payload['link'] ?? $order->biteship_tracking_url,
                'biteship_tracking_history' => $history,
                'biteship_last_synced_at' => now(),
            ])->saveQuietly();

            return true;
        });
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('shipping.biteship.base_url'))
            ->connectTimeout(3)
            ->timeout((int) config('shipping.biteship.timeout', 10))
            ->withHeaders(['authorization' => (string) config('shipping.biteship.api_key'), 'content-type' => 'application/json']);
    }

    private function isConfigured(): bool
    {
        return (bool) config('shipping.biteship.enabled') && filled(config('shipping.biteship.api_key')) && filled(config('shipping.biteship.origin_postal_code'));
    }

    private function courierCode(Order $order): string
    {
        $courier = strtolower(trim((string) $order->biteship_courier_code));
        if ($courier !== '') {
            return $courier;
        }

        $label = strtolower((string) $order->shipping_label);
        foreach (['sicepat', 'jne', 'jnt', 'tiki', 'ninja', 'lion', 'sap', 'ide', 'pos'] as $candidate) {
            if (str_contains($label, $candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}
