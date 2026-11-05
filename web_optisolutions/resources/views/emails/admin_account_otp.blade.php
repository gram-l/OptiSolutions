@extends('emails.layouts.otp')

@section('title', 'Verify User Management access')

@section('content')
<p style="margin:0 0 16px;">Use this code to confirm your identity and unlock User Management for 24 hours in your current sign-in session.</p>
<p style="margin:0 0 16px;">Your verification code is:</p>
@include('emails.partials.otp-code', ['value' => $code])
<p style="margin:0 0 16px;">This code expires in 10 minutes and can be used only once. Do not share it.</p>
<p style="margin:0 0 24px;">If you did not request this change, do not approve it and contact your system administrator.</p>
@endsection
