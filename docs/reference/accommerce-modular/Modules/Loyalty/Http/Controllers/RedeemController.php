<?php

namespace Modules\Loyalty\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Services\LoyaltyService;

class RedeemController extends Controller
{
    public function store(Request $request, LoyaltyService $svc)
    {
        $user = $request->user();
        abort_unless($user->company_role === 'admin', 403);      // sub-akun tidak boleh menukar

        $data = $request->validate([
            'type' => ['required', 'in:ewallet,voucher'],
            'points' => ['required', 'integer'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'provider' => ['nullable', 'string'],
            'account' => ['nullable', 'string'],
        ]);

        $account = LoyaltyAccount::where('company_id', $user->company_id)->firstOrFail();
        $svc->redeem($account, $user->id, $data['type'], $data['points'], $data['idempotency_key'], $data);

        return back()->with('success', 'Penukaran poin diproses.');   // flash -> toast
    }
}
