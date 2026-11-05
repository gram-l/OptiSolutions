@extends('emails.layouts.notification')
@section('category', 'Visit completed')
@section('title', 'Thank you for visiting PolyClinic')
@section('content')
<p style="margin:0 0 16px;">Hello {{ $patientName }},</p>
<p style="margin:0 0 20px;">Your visit on {{ \Carbon\Carbon::parse($visitDate)->format('M d, Y') }} has been marked completed. You can now share your experience with us.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef6fc;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px;">
    <p style="margin:0 0 8px;color:#062744;font-weight:bold;">Visit details</p>
    <p style="margin:0;"><strong>Doctor:</strong> {{ $doctorName }}<br><strong>Service:</strong> {{ $service }}<br><strong>Visit reference:</strong> {{ $visitId }}</p>
</td></tr></table>
<p style="margin:0 0 8px;color:#062744;font-weight:bold;">Your review transaction code</p>
<p style="margin:0 0 16px;padding:14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;color:#062744;font-family:monospace;font-size:17px;word-break:break-all;overflow-wrap:anywhere;">{{ $transactionCode }}</p>
<p style="margin:0 0 16px;">Use the button below, or scan the QR code to open Submit Review/Rating. Your code will be filled in automatically.</p>
@include('emails.partials.action-button', ['url' => $reviewUrl, 'label' => 'Submit Review/Rating'])
<p style="margin:0 0 20px;"><a href="{{ $reviewUrl }}"><img src="{{ $message->embedData($qrCodePng, 'visit-review-pass.png', 'image/png') }}" width="180" height="180" alt="Scan to submit your visit review" style="display:block;width:180px;height:180px;max-width:100%;border:0;"></a></p>
<p style="margin:0;color:#64748b;font-size:12px;">You can use this pass for one review of this visit. Keep this code private. It identifies this completed visit for your review.</p>
@endsection
