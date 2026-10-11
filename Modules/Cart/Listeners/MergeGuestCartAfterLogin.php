<?php

namespace Modules\Cart\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Cart\Models\Cart;

class MergeGuestCartAfterLogin
{
    public function handle(Login $event): void
    {
        $request = request();
        $sessionId = $request->session()->getId();
        $guest = Cart::query()->with(['items.product', 'items.variant'])->where('session_id', $sessionId)->whereNull('user_id')->first();

        if (! $guest) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $event->user->getAuthIdentifier()]);
        foreach ($guest->items as $guestItem) {
            $existing = $userCart->items()->where('product_id', $guestItem->product_id)->where('variant_id', $guestItem->variant_id)->where('rental_start', $guestItem->rental_start)->where('rental_end', $guestItem->rental_end)->first();
            $stock = $guestItem->variant?->stock ?? $guestItem->product?->stock ?? $guestItem->quantity;
            $quantity = min($stock, ($existing?->quantity ?? 0) + $guestItem->quantity);
            $existing ? $existing->update(['quantity' => $quantity]) : $userCart->items()->create($guestItem->only(['product_id', 'variant_id', 'quantity', 'rental_start', 'rental_end', 'rental_start_time', 'rental_end_time']));
        }

        $guest->delete();
    }
}
