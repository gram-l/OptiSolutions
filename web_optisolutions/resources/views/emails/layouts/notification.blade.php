<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — PolyClinic</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;color:#334155;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f1f5f9;">
    <tr><td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
            <tr><td style="padding:24px 24px 20px;border-bottom:3px solid #0E62AA;">
                <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                    <td width="52" style="vertical-align:middle;">
                        <img src="{{ isset($message) ? $message->embed(public_path('images/polyclinic_logo_email.png')) : asset('images/polyclinic_logo_email.png') }}" width="44" height="44" alt="PolyClinic logo" style="display:block;width:44px;height:44px;border:0;">
                    </td>
                    <td style="vertical-align:middle;color:#062744;font-size:22px;font-weight:bold;line-height:1.2;">PolyClinic<br><span style="color:#0E62AA;font-size:13px;font-weight:normal;">Lipa</span></td>
                </tr></table>
            </td></tr>
            <tr><td style="padding:24px;font-size:15px;line-height:1.6;word-break:break-word;">
                @hasSection('category')
                    <p style="margin:0 0 8px;color:#0E62AA;font-size:11px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">@yield('category')</p>
                @endif
                <h1 style="margin:0 0 20px;color:#062744;font-size:24px;line-height:1.3;">@yield('title')</h1>
                @yield('content')
            </td></tr>
            <tr><td style="padding:16px 24px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;line-height:1.5;">
                <strong style="color:#062744;">PolyClinic · Lipa</strong><br>
                This is an automated notification. Please do not reply to this email.
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
