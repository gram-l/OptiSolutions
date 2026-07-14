<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Conversation Transcript</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#0f766e; padding:20px 24px;">
                            <h1 style="color:#ffffff; font-size:18px; margin:0;">PolyClinic Lipa</h1>
                            <p style="color:#d1fae5; font-size:13px; margin:4px 0 0;">Conversation Transcript</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="font-size:14px; color:#111827; margin:0 0 16px;">
                                Hi {{ $patientName }}, here's a copy of your conversation with us:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                @foreach ($transcript as $entry)
                                    <tr>
                                        <td style="padding:6px 0; vertical-align:top;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                                <tr>
                                                    <td style="width:70%; vertical-align:top;">
                                                        <span style="display:inline-block; font-size:11px; font-weight:bold; color:{{ $entry['role'] === 'Bot' ? '#0f766e' : '#374151' }}; margin-bottom:2px;">
                                                            {{ $entry['role'] === 'Bot' ? 'PolyClinic Bot' : $patientName }}
                                                        </span>
                                                        <div style="font-size:13px; color:#111827; background-color:{{ $entry['role'] === 'Bot' ? '#ecfdf5' : '#f3f4f6' }}; padding:8px 12px; border-radius:6px; display:inline-block;">
                                                            {{ $entry['text'] }}
                                                        </div>
                                                    </td>
                                                    <td style="width:30%; text-align:right; vertical-align:top; padding-top:4px;">
                                                        <span style="font-size:11px; color:#9ca3af;">{{ $entry['time'] }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="font-size:12px; color:#9ca3af; margin-top:24px;">
                                If you have questions about your schedule visit, please contact us at 0985 475 5511.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9fafb; padding:16px 24px; text-align:center;">
                            <p style="font-size:11px; color:#9ca3af; margin:0;">© {{ date('Y') }} PolyClinic Lipa. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>