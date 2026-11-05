@extends('emails.layouts.otp')

@section('title', 'Reset your password')

@section('content')
<p style="margin:0 0 16px;">Hello,</p>
<p style="margin:0 0 16px;">We received a request to reset your PolyClinic staff account password. Use this code to continue.</p>
<p style="margin:0 0 16px;">Your verification code is:</p>
@include('emails.partials.otp-code', ['value' => $otp])
<p style="margin:0 0 16px;">This code expires in 10 minutes and can be used only once. Do not share it.</p>
<p style="margin:0 0 24px;">If you did not request a password reset, you can safely ignore this email. Your password will not change.</p>
@endsection
