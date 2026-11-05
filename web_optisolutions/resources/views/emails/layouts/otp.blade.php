<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — PolyClinic</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;color:#333333;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;border-collapse:collapse;text-align:left;">
                    <tr>
                        <td style="padding-bottom:24px;border-bottom:1px solid #e5e7eb;">
                            <img src="{{ isset($message) ? $message->embed(public_path('images/polyclinic_logo.png')) : asset('images/polyclinic_logo.png') }}" width="64" height="64" alt="PolyClinic logo" style="display:block;width:64px;height:64px;border:0;">
                            <p style="margin:12px 0 0;color:#062744;font-size:20px;font-weight:bold;">PolyClinic</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:24px;font-size:15px;line-height:1.6;">
                            <h2 style="margin:0 0 20px;color:#062744;font-size:24px;line-height:1.3;">@yield('title')</h2>
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:24px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.5;">
                            This is an automated message from PolyClinic. Please do not reply.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
