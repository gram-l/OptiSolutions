@extends('emails.layouts.notification')
@section('category', 'Chatbot inquiry')
@section('title', $alertTitle)
@section('content')
<p style="margin:0 0 20px;">{{ $alertMessage }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef6fc;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px;">
    @if ($inquiryId !== null)
        <p style="margin:0 0 6px;color:#64748b;font-size:11px;font-weight:bold;letter-spacing:1px;">INQUIRY REFERENCE</p>
        <p style="margin:0 0 10px;color:#062744;font-size:20px;font-weight:bold;">INQUIRY-{{ $inquiryId }}</p>
    @endif
    <span style="display:inline-block;padding:3px 8px;background:#fff1d6;color:#854d0e;border-radius:4px;font-size:12px;font-weight:bold;">Needs response</span>
</td></tr></table>
<p style="margin:0 0 16px;">Sign in and open Chatbot Inquiries to view the conversation and reply.</p>
@include('emails.partials.action-button', ['url' => $loginUrl, 'label' => 'View inquiry'])
<p style="margin:0;color:#64748b;font-size:12px;">You may be asked to sign in to your account.</p>
@endsection
