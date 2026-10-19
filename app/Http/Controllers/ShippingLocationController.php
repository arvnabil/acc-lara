<?php

namespace App\Http\Controllers;

use App\Models\ShippingLocation;
use Illuminate\Http\JsonResponse;
use Modules\Cart\Services\ShippingLocationService;

class ShippingLocationController extends Controller
{
    public function provinces(ShippingLocationService $locations): JsonResponse
    {
        return response()->json(['locations' => $locations->locations('province')->map($this->serialize(...))->values()]);
    }

    public function children(string $type, int $parent, ShippingLocationService $locations): JsonResponse
    {
        abort_unless(in_array($type, ['city', 'district', 'subdistrict'], true), 404);

        return response()->json(['locations' => $locations->locations($type, $parent)->map($this->serialize(...))->values()]);
    }

    private function serialize(ShippingLocation $location): array
    {
        return ['id' => $location->external_id, 'name' => $location->name, 'postal_code' => $location->postal_code];
    }
}
