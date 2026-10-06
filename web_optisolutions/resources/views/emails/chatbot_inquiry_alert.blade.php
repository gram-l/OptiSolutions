<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ $alertTitle }}</title></head>
<body style="font-family:Arial,sans-serif;color:#16324a;line-height:1.6;">
    <h2>{{ $alertTitle }}</h2>
    <p>{{ $alertMessage }}</p>
    @if ($inquiryId !== null)
        <p>Reference: INQUIRY-{{ $inquiryId }}</p>
    @endif
    <p>A chatbot inquiry needs attention. Sign in and open Chatbot Inquiries to view the conversation and reply.</p>
    <p><a href="{{ $loginUrl }}">Open PolyClinic</a></p>
</body>
</html>
