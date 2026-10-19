<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { color: #111827; font-family: dejavusans, sans-serif; font-size: 10px; line-height: 1.5; margin: 0; }
        .header { border-bottom: 1px solid #d1d5db; padding-bottom: 18px; }
        .logo-image { height: 34px; width: auto; }
        .logo-fallback { color: #003599; font-size: 22px; font-weight: bold; letter-spacing: -0.5px; }
        .muted { color: #6b7280; }
        .title { color: #111827; font-size: 24px; font-weight: bold; letter-spacing: 1px; margin: 24px 0 18px; }
        .columns { width: 100%; }
        .columns td { vertical-align: top; width: 50%; }
        .columns td:last-child { text-align: right; }
        .customer-name { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        .meta td { padding: 1px 0 1px 16px; }
        .meta td:first-child { color: #6b7280; padding-left: 0; }
        .meta td:last-child { font-weight: bold; }
        .items { border-collapse: collapse; margin-top: 22px; width: 100%; }
        .items th { background: #111827; color: white; font-size: 9px; padding: 9px 10px; text-align: left; text-transform: uppercase; }
        .items th:nth-child(2) { text-align: center; width: 14%; }
        .items th:last-child { text-align: right; width: 24%; }
        .items td { border-bottom: 1px solid #e5e7eb; padding: 11px 10px; vertical-align: top; }
        .items td:nth-child(2) { text-align: center; }
        .items td:last-child { text-align: right; }
        .product-name { font-weight: bold; }
        .product-meta { color: #6b7280; font-size: 8px; }
        .summary { margin-left: auto; margin-top: 18px; width: 44%; }
        .summary table { border-collapse: collapse; width: 100%; }
        .summary td { border-bottom: 1px solid #f3f4f6; padding: 5px 0; }
        .summary td:last-child { text-align: right; }
        .total td { border-bottom: 2px solid #111827; border-top: 2px solid #111827; font-size: 12px; font-weight: bold; padding: 8px 0; }
        .note { border-top: 1px dashed #d1d5db; color: #9ca3af; font-size: 8px; margin-top: 52px; padding-top: 12px; text-align: center; }
    </style>
</head>
<body>
    @php
        $address = $order->shipping_address ?? [];
        $items = $order->items;
        $subtotal = (int) ($order->goods_subtotal ?: $items->sum('subtotal'));
        $shipping = (int) $order->shipping_cost;
        $discount = (int) $order->discount_on_goods;
        $ppn = (int) $order->ppn;
        $adminFee = (int) $order->admin_fee;
        $recipient = $address['recipient'] ?? $order->company?->name ?? $order->user?->name ?? '-';
        $logoPath = public_path('accommerce-blue.png');
        $logoData = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    @endphp

    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    @if ($logoData)
                        <img class="logo-image" src="{{ $logoData }}" alt="Accommerce">
                    @else
                        <div class="logo-fallback">Accommerce</div>
                    @endif
                    <div class="muted">Enterprise IT &amp; Audio Visual Solutions</div>
                </td>
                <td align="right"><strong>PT Alfa Cipta Teknologi Virtual</strong><br><span class="muted">Invoice resmi pembelian</span></td>
            </tr>
        </table>
    </div>

    <div class="title">INVOICE</div>

    <table class="columns">
        <tr>
            <td>
                <div class="customer-name">{{ $recipient }}</div>
                <div>{{ $address['address'] ?? '-' }}</div>
                <div>{{ $address['city'] ?? '-' }}</div>
                <div>{{ $address['province'] ?? '' }} {{ $address['postal_code'] ?? '' }}</div>
                <div class="muted">{{ $order->user?->email ?? '-' }}</div>
                <div class="muted">{{ $address['phone'] ?? $order->user?->phone ?? '-' }}</div>
            </td>
            <td>
                <table class="meta" align="right">
                    <tr><td>Nomor invoice:</td><td>{{ $order->number }}</td></tr>
                    <tr><td>Jumlah order:</td><td>{{ $order->number }}</td></tr>
                    <tr><td>Tanggal pemesanan:</td><td>{{ $order->created_at?->format('d F Y') }}</td></tr>
                    <tr><td>Metode pembayaran:</td><td>{{ $order->payment_method ?: '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th>Produk</th><th>Kuantitas</th><th>Harga</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td><div class="product-name">{{ $item->product_name }}</div><div class="product-meta">SKU: {{ $item->sku ?: '-' }}</div></td>
                    <td>{{ $item->quantity }}</td>
                    <td>Rp {{ number_format((int) $item->unit_price * (int) $item->quantity, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr><td><strong>Subtotal</strong></td><td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td></tr>
            @if ($discount > 0)<tr><td>Diskon</td><td>- Rp {{ number_format($discount, 0, ',', '.') }}</td></tr>@endif
            <tr><td>Pengiriman</td><td>Rp {{ number_format($shipping, 0, ',', '.') }}</td></tr>
            @if ($adminFee > 0)<tr><td>Biaya admin</td><td>Rp {{ number_format($adminFee, 0, ',', '.') }}</td></tr>@endif
            @if ($ppn > 0)<tr><td>PPN</td><td>Rp {{ number_format($ppn, 0, ',', '.') }}</td></tr>@endif
            <tr class="total"><td>TOTAL</td><td>Rp {{ number_format((int) $order->grand_total, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div class="note">Invoice ini sah dan diproses secara otomatis oleh sistem komputer PT Alfa Cipta Teknologi Virtual.</div>
</body>
</html>
