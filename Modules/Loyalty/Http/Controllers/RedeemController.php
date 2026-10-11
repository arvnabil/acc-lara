<?php

namespace Modules\Loyalty\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Loyalty\Services\LoyaltyService;

class RedeemController
{
    public function store(Request $request, LoyaltyService $loyalty): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:ewallet,voucher'],
            'points' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:50'],
            'account' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:255'],
        ]);
        $result = $loyalty->redeem($request->user(), $data);

        return back()->with('success', $data['type'] === 'voucher' ? 'Voucher berhasil diterbitkan.' : 'Penukaran e-wallet sedang diproses.');
    }
}
