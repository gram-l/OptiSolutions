@extends('emails.layouts.notification')
@section('category', 'Schedule updated')
@section('title', 'Your updated PolyClinic visit')
@section('content')
<p style="margin:0 0 16px;">Hello {{ $patientName }},</p>
<p style="margin:0 0 20px;">Your scheduled visit has been updated. Please use these details:</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef6fc;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px;">
<p style="margin:0;"><strong>Doctor:</strong> {{ $doctorName }}<br><strong>Service:</strong> {{ $service }}<br>
<strong>Date and time:</strong> {{ $scheduleDetails }} (Philippine time)<br><strong>Visit reference:</strong> {{ $visitId }}</p>
</td></tr></table>
@if (!empty($remarks))
<p style="margin:0 0 20px;white-space:pre-line;"><strong>Remarks:</strong><br>{{ $remarks }}</p>
@endif
<p style="margin:0;">Please contact the clinic if you need help with your new schedule.</p>
@endsection
