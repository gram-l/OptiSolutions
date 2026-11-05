@extends('emails.layouts.notification')
@section('category', 'Schedule confirmed')
@section('title', 'Your appointment is confirmed')
@section('content')
<p style="margin:0 0 16px;">Hello {{ $name }},</p>
<p style="margin:0 0 20px;">Your appointment has been confirmed.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef6fc;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px;">
<p style="margin:0;"><strong>Date:</strong> {{ $date }}<br><strong>Doctor:</strong> {{ $doctor }}</p>
</td></tr></table>
<p style="margin:0;">Thank you for choosing PolyClinic.</p>
@endsection
