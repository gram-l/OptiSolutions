@extends('emails.layouts.notification')
@section('category', 'Visit reminder')
@section('title', 'Your visit is coming up')
@section('content')
<p style="margin:0 0 16px;">Hi {{ $firstName }}, this is a reminder that your schedule visit starts soon. Please let us know if you are still coming.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef6fc;border-radius:8px;margin:0 0 8px;"><tr><td style="padding:16px;">
    <p style="margin:0;"><strong>Date and time:</strong> {{ $schedule }} (Philippine time)<br>
    <strong>Service:</strong> {{ $service }}<br>
    @if ($doctor !== '')<strong>Doctor:</strong> {{ $doctor }}<br>@endif
    <strong>Reference no.:</strong> {{ $reference }}</p>
</td></tr></table>

@include('emails.partials.action-button', ['url' => $confirmUrl, 'label' => 'Confirm my visit'])

<p style="margin:0 0 12px;font-size:14px;">The button opens a short page where you can choose <strong>Yes, I'm coming</strong> or <strong>I can't come</strong>. It takes a few seconds.</p>
<p style="margin:0;color:#64748b;font-size:12px;">
    If the button does not work, copy this link into your browser:<br>
    <span style="word-break:break-all;">{{ $confirmUrl }}</span>
    @if ($phone !== '')<br><br>Questions? Call the clinic at {{ $phone }}.@endif
</p>
@endsection