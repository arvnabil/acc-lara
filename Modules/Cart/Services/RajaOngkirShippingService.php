<?php

namespace Modules\Cart\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Clients\Models\Address;
use Throwable;

class RajaOngkirShippingService
{
    /**
     * Search RajaOngkir destination records for address selectors.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchDestinations(string $search, int $limit = 10): array
    {
        if (! $this->isConfigured() || mb_strlen(trim($search)) < 3) {
            return [];
        }

        try {
            $response = $this->client()->get($this->endpoint('destination/domestic-destination'), [
                'search' => trim($search),
                'limit' => min(max($limit, 1), 20),
                'offset' => 0,
            ]);
        } catch (Throwable $exception) {
            Log::warning('RajaOngkir destination lookup could not be completed.', [
                'message' => $exception->getMessage(),
                'query' => $search,
            ]);

            return [];
        }

        if ($response->failed()) {
            Log::warning('RajaOngkir destination lookup failed.', [
                'status' => $response->status(),
                'query' => $search,
            ]);

            return [];
        }

        return collect($response->json('data', []))
            ->map(fn (array $destination): array => [
                'id' => (int) ($destination['id'] ?? 0),
                'label' => (string) ($destination['label'] ?? $destination['name'] ?? ''),
                'province_id' => $destination['province_id'] ?? null,
                'province_name' => $destination['province_name'] ?? null,
                'city_id' => $destination['city_id'] ?? null,
                'city_name' => $destination['city_name'] ?? null,
                'district_id' => $destination['district_id'] ?? null,
                'district_name' => $destination['district_name'] ?? null,
                'subdistrict_id' => $destination['subdistrict_id'] ?? null,
                'subdistrict_name' => $destination['subdistrict_name'] ?? null,
                'zip_code' => $destination['zip_code'] ?? null,
            ])
            ->filter(fn (array $destination): bool => $destination['id'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  iterable<object>  $items
     * @return array<int, array<string, mixed>>
     */
    public function optionsForAddress(?Address $address, iterable $items): array
    {
        if (! $address) {
            return config('shipping.options', []);
        }

        return $this->optionsForAddressData([
            'province' => $address->province,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'district' => $address->district_name,
            'subdistrict' => $address->subdistrict_name,
            'destination_id' => $address->destination_id,
        ], $items);
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  iterable<object>  $items
     * @return array<int, array<string, mixed>>
     */
    public function optionsForAddressData(array $address, iterable $items): array
    {
        $fallback = config('shipping.options', []);

        if (! $this->isConfigured()) {
            return $fallback;
        }

        $destination = ! empty($address['destination_id'])
            ? ['id' => (int) $address['destination_id']]
            : $this->findDestination($address);

        if (! $destination) {
            return $fallback;
        }

        $weight = $this->weightForItems($items);
        try {
            $response = $this->client()
                ->asForm()
                ->post($this->endpoint('calculate/domestic-cost'), [
                    'origin' => (int) config('shipping.rajaongkir.origin'),
                    'destination' => (int) $destination['id'],
                    'weight' => $weight,
                    'courier' => (string) config('shipping.rajaongkir.courier'),
                    'price' => (string) config('shipping.rajaongkir.price', 'lowest'),
                ]);
        } catch (Throwable $exception) {
            Log::warning('RajaOngkir shipping cost request could not be completed.', [
                'message' => $exception->getMessage(),
                'destination' => $destination,
            ]);

            return $fallback;
        }

        if ($response->failed()) {
            Log::warning('RajaOngkir shipping cost request failed.', [
                'status' => $response->status(),
                'destination' => $destination,
            ]);

            return $fallback;
        }

        $options = collect($response->json('data', []))
            ->map(function (array $service): ?array {
                $cost = $service['cost'] ?? null;

                if (is_array($cost)) {
                    $cost = $cost[0]['value'] ?? null;
                }

                if (! is_numeric($cost)) {
                    return null;
                }

                $code = (string) ($service['code'] ?? 'courier');
                $serviceName = (string) ($service['service'] ?? 'Regular');

                return [
                    'id' => 'rajaongkir-'.$code.'-'.str()->slug($serviceName),
                    'title' => strtoupper($code).' '.$serviceName,
                    'desc' => ! empty($service['etd']) ? 'Estimasi '.$service['etd'].' hari kerja' : 'Estimasi pengiriman RajaOngkir',
                    'price' => (int) $cost,
                    'courier' => $code,
                    'service' => $serviceName,
                    'destination_id' => (int) $destination['id'],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $options !== [] ? $options : $fallback;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>|null
     */
    private function findDestination(array $address): ?array
    {
        $query = trim((string) ($address['postal_code'] ?: $address['city'] ?: $address['province']));

        if ($query === '') {
            return null;
        }

        try {
            $response = $this->client()->get($this->endpoint('destination/domestic-destination'), [
                'search' => $query,
                'limit' => 5,
                'offset' => 0,
            ]);
        } catch (Throwable $exception) {
            Log::warning('RajaOngkir destination search could not be completed.', [
                'message' => $exception->getMessage(),
                'query' => $query,
            ]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('RajaOngkir destination search failed.', ['status' => $response->status(), 'query' => $query]);

            return null;
        }

        $destination = collect($response->json('data', []))->first(function (array $result) use ($address): bool {
            $postalCode = (string) ($address['postal_code'] ?? '');

            return $postalCode !== '' && (string) ($result['zip_code'] ?? '') === $postalCode;
        });

        return $destination ?: collect($response->json('data', []))->first();
    }

    /**
     * @param  iterable<object>  $items
     */
    private function weightForItems(iterable $items): int
    {
        $quantity = collect($items)->sum(fn (object $item): int => (int) ($item->quantity ?? 1));

        return max(1, $quantity * (int) config('shipping.rajaongkir.default_weight_grams', 1000));
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('shipping.rajaongkir.base_url'), '/').'/'.$path;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::connectTimeout(3)
            ->timeout((int) config('shipping.rajaongkir.timeout', 10))
            ->withHeaders(['key' => (string) config('shipping.rajaongkir.api_key')]);
    }

    private function isConfigured(): bool
    {
        return (bool) config('shipping.rajaongkir.enabled')
            && filled(config('shipping.rajaongkir.api_key'))
            && filled(config('shipping.rajaongkir.origin'));
    }
}


