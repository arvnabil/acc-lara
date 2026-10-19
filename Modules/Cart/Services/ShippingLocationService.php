<?php

namespace Modules\Cart\Services;

use App\Models\ShippingLocation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShippingLocationService
{
    /** @return Collection<int, ShippingLocation> */
    public function locations(string $type, ?int $parentExternalId = null): Collection
    {
        $query = ShippingLocation::query()->where('type', $type);

        if ($parentExternalId === null) {
            $query->whereNull('parent_external_id');
        } else {
            $query->where('parent_external_id', $parentExternalId);
        }

        $locations = $query->orderBy('name')->get();

        return $locations->isNotEmpty() || ! $this->isConfigured()
            ? $locations
            : $this->syncLevel($type, $parentExternalId);
    }

    /** @return Collection<int, ShippingLocation> */
    public function syncAll(): Collection
    {
        $provinces = $this->locations('province');
        if ($provinces->isEmpty()) {
            $provinces = $this->syncLevel('province');
        }

        $cities = collect();
        $districts = collect();
        $subdistricts = collect();

        foreach ($provinces as $province) {
            $provinceCities = $this->locations('city', $province->external_id);
            if ($provinceCities->isEmpty()) {
                $provinceCities = $this->syncLevel('city', $province->external_id);
            }
            $cities = $cities->concat($provinceCities);

            foreach ($provinceCities as $city) {
                $cityDistricts = $this->locations('district', $city->external_id);
                if ($cityDistricts->isEmpty()) {
                    $cityDistricts = $this->syncLevel('district', $city->external_id);
                }
                $districts = $districts->concat($cityDistricts);

                foreach ($cityDistricts as $district) {
                    $districtSubdistricts = $this->locations('subdistrict', $district->external_id);
                    if ($districtSubdistricts->isEmpty()) {
                        $districtSubdistricts = $this->syncLevel('subdistrict', $district->external_id);
                    }
                    $subdistricts = $subdistricts->concat($districtSubdistricts);
                }
            }
        }

        return $provinces->concat($cities)->concat($districts)->concat($subdistricts);
    }

    /** @return Collection<int, ShippingLocation> */
    private function syncLevel(string $type, ?int $parentExternalId = null): Collection
    {
        $response = match ($type) {
            'province' => $this->client()->get($this->endpoint('destination/province')),
            'city' => $this->client()->get($this->endpoint("destination/city/{$parentExternalId}")),
            'district' => $this->client()->get($this->endpoint("destination/district/{$parentExternalId}")),
            'subdistrict' => $this->client()->get($this->endpoint("destination/sub-district/{$parentExternalId}")),
            default => null,
        };

        if (! $response || $response->failed()) {
            Log::warning('RajaOngkir location sync failed.', ['type' => $type, 'parent' => $parentExternalId, 'status' => $response?->status()]);

            return collect();
        }

        return collect($response->json('data', []))->map(function (array $item) use ($type, $parentExternalId): ShippingLocation {
            return ShippingLocation::query()->updateOrCreate(
                ['type' => $type, 'external_id' => (int) $item['id']],
                [
                    'parent_external_id' => $parentExternalId,
                    'name' => (string) ($item['name'] ?? $item['label'] ?? ''),
                    'postal_code' => $item['zip_code'] ?? null,
                ],
            );
        })->filter(fn (ShippingLocation $location): bool => $location->name !== '')->values();
    }

    private function client(): PendingRequest
    {
        return Http::connectTimeout(3)
            ->timeout((int) config('shipping.rajaongkir.timeout', 10))
            ->withHeaders(['key' => (string) config('shipping.rajaongkir.api_key')]);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('shipping.rajaongkir.base_url'), '/').'/'.$path;
    }

    private function isConfigured(): bool
    {
        return (bool) config('shipping.rajaongkir.enabled') && filled(config('shipping.rajaongkir.api_key'));
    }
}
