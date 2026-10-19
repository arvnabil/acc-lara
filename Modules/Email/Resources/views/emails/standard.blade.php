<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;background:#f4f7fc;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    @php
        $logoSource = asset('accommerce-white.png');
        if (isset($message) && method_exists($message, 'embed')) {
            $logoSource = $message->embed(public_path('accommerce-white.png'));
        }
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fc;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dce5f3;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#003b9f;padding:26px 32px;">
                            <img src="{{ $logoSource }}" alt="Accommerce" style="display:block;max-width:190px;height:auto;">
                            <div style="margin-top:5px;font-size:12px;color:#dbe8ff;">Enterprise IT &amp; Audio Visual Solutions</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 36px 28px;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#1261c9;">{{ $eyebrow ?? 'Notifikasi Accommerce' }}</div>
                            <h1 style="margin:12px 0 10px;font-size:26px;line-height:1.25;color:#172033;">{{ $title }}</h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#5c6b82;">Halo {{ $recipientName }},<br>{{ $intro }}</p>

                            @if (!empty($details))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f6ff;border:1px solid #dce8fb;border-radius:12px;margin:0 0 26px;">
                                    @foreach ($details as $label => $value)
                                        <tr>
                                            <td style="padding:12px 16px;border-bottom:1px solid #dce8fb;font-size:13px;color:#6b7890;">{{ $label }}</td>
                                            <td align="right" style="padding:12px 16px;border-bottom:1px solid #dce8fb;font-size:13px;font-weight:700;color:#172033;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if (!empty($actionUrl))
                                <p style="margin:0 0 24px;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#0046b8;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;padding:13px 22px;">{{ $actionText ?? 'Buka Accommerce' }}</a>
                                </p>
                            @endif

                            <p style="margin:0;font-size:14px;line-height:1.7;color:#5c6b82;">{{ $outro ?? 'Terima kasih telah menggunakan Accommerce.' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px;background:#f8faff;border-top:1px solid #e5ebf5;color:#8290a6;font-size:12px;line-height:1.6;">
                            Email ini dikirim otomatis oleh Accommerce. Mohon tidak membalas email ini.<br>
                            &copy; {{ date('Y') }} Accommerce. Semua hak dilindungi.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
