<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Content\Models\RfqRequest;

class ContentController extends Controller
{
    public function help(): Response
    {
        return Inertia::render('Catalog::BantuanPage');
    }

    public function storeRfq(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'company_name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $rfq = RfqRequest::create([...$data, 'status' => 'new']);

        Mail::raw(
            "RFQ #{$rfq->id}\n\n".collect($data)->map(fn ($value, $key): string => "{$key}: {$value}")->implode("\n"),
            static function ($mail): void {
                $mail->to(config('site.sales_email'))->subject('Permintaan RFQ Baru');
            },
        );

        return back()->with('success', 'Permintaan penawaran Anda (RFQ) telah kami terima. Tim sales kami akan merespons dalam waktu kurang dari 2 jam kerja.');
    }
}
