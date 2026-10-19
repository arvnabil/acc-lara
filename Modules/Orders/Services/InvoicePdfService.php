<?php

namespace Modules\Orders\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Modules\Orders\Models\Order;

class InvoicePdfService
{
    public function render(Order $order): string
    {
        $tempDirectory = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDirectory);
        File::ensureDirectoryExists($tempDirectory.'/mpdf');

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontConfig = (new FontVariables())->getDefaults();
        $mpdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'tempDir' => $tempDirectory,
            'default_font' => 'dejavusans',
            'fontDir' => $defaultConfig['fontDir'],
            'fontdata' => $fontConfig['fontdata'],
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);

        $mpdf->SetTitle('Invoice '.$order->number);
        $mpdf->WriteHTML(view('orders.invoice', ['order' => $order])->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
