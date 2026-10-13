<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Your visit review pass</title></head>
<body style="font-family:Arial,sans-serif;color:#16324a;line-height:1.6;">
    <h2>Thank you for visiting PolyClinic</h2>
    <p>Hello {{ $patientName }},</p>
    <p>Your visit on {{ \Carbon\Carbon::parse($visitDate)->format('M d, Y') }} has been marked completed.</p>
    <p><strong>Doctor:</strong> {{ $doctorName }}<br><strong>Service:</strong> {{ $service }}<br><strong>Visit reference:</strong> {{ $visitId }}</p>
    <p>Your review transaction code:</p>
    <p style="font-family:monospace;font-size:18px;padding:14px;background:#eef5fb;word-break:break-all;">{{ $transactionCode }}</p>
    <p>Scan this QR code to open the website and start Submit Review/Rating. Your code will be filled in automatically.</p>
    <p><a href="{{ $reviewUrl }}"><img src="{{ $message->embedData($qrCodePng, 'visit-review-pass.png', 'image/png') }}" width="240" height="240" alt="Scan to submit your visit review" style="display:block;max-width:100%;"></a></p>
    <p><a href="{{ $reviewUrl }}" style="display:inline-block;background:#1064ad;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;">Submit Review/Rating</a></p>
    <p>You can use this pass for one review of this visit.</p>
    <p>Keep this code private. It identifies this completed visit for your review.</p>
</body>
</html>
