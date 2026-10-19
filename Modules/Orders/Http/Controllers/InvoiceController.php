<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\InvoicePdfService;

class InvoiceController
{
    public function download(Order $order, InvoicePdfService $invoicePdf): Response
    {
        $order->load(['user', 'client', 'items.product', 'items.variant']);

        return response($invoicePdf->render($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$order->number.'.pdf"',
        ]);
    }
}


