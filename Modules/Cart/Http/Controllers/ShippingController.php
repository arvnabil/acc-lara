<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Clients\Models\Address;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\PricingService;
use Modules\Cart\Services\BiteshipShippingService;
use Modules\Cart\Services\RajaOngkirShippingService;

class ShippingController
{
    public function areas(Request $request, BiteshipShippingService $shipping): JsonResponse
    {
        $data = $request->validate(['input' => ['required', 'string', 'min:3', 'max:100']]);

        return response()->json(['areas' => $shipping->searchAreas($data['input'])]);
    }

    public function destinations(Request $request, RajaOngkirShippingService $shipping): JsonResponse
    {
        $data = $request->validate(['search' => ['required', 'string', 'min:3', 'max:100']]);

        return response()->json(['destinations' => $shipping->searchDestinations($data['search'])]);
    }

    public function quote(Request $request, BiteshipShippingService $shipping, PricingService $pricing): JsonResponse
    {
        $data = $request->validate(['address_id' => ['nullable', 'integer'], 'destination_area_id' => ['nullable', 'string'], 'postal_code' => ['nullable', 'string']]);
        $address = ! empty($data['address_id'])
            ? Address::query()->where('user_id', $request->user()->id)->findOrFail($data['address_id'])
            : null;
        $cart = Cart::query()->where('user_id', $request->user()->id)->with(['items.product', 'items.variant', 'promoCode', 'voucher'])->first();
        $items = $cart?->items ?? collect();
        $options = $shipping->optionsForAddressData([
            'postal_code' => $data['postal_code'] ?? $address?->postal_code,
            'destination_area_id' => $data['destination_area_id'] ?? null,
        ], $items);

        return response()->json([
            'options' => $options,
            'quotes' => $pricing->quoteForOptions($items, $cart?->promoCode, $options, $cart?->voucher),
        ]);
    }
}


