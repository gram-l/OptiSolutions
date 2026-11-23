@extends('emails.layouts.notification')
@section('category', $pending ? 'Schedule received' : 'Schedule confirmed')
@section('title', $pending ? 'Your schedule visit request was received' : 'Your schedule visit is confirmed')
@section('content')
<p style="font-size:15px; color:#111827; margin:0 0 16px;">
                                @if ($pending)
                                Hi {{ $firstName }}, we received your schedule visit request. Your schedule is held for you, and the clinic will confirm it. Status: <strong>Pending</strong>.
                                @else
                                Hi {{ $firstName }}, your schedule visit is confirmed.
                                @endif
                            </p>

                            {{-- Visit details --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EAF0F9; border:1px solid #c7d6ee; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; color:#1F2A3A;">
                                            <tr>
                                                <td style="padding:5px 0; width:34%; color:#5b6b82;">Service</td>
                                                <td style="padding:5px 0; font-weight:bold;">{{ $service }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0; color:#5b6b82;">Doctor</td>
                                                <td style="padding:5px 0; font-weight:bold;">{{ $doctor }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0; color:#5b6b82;">Date and time</td>
                                                <td style="padding:5px 0; font-weight:bold;">{{ $schedule }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0; color:#5b6b82;">Patient</td>
                                                <td style="padding:5px 0; font-weight:bold;">{{ $patientName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0; color:#5b6b82;">Reference no.</td>
                                                <td style="padding:5px 0; font-weight:bold;">{{ $reference }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @unless ($pending)
                                <p style="font-size:14px; line-height:1.6; color:#1F2A3A; margin:16px 0 0;">
                                    We will email you a reminder shortly before your visit. Please use the button in that email to confirm that you are coming.
                                </p>
                            @endunless

                            {{-- Clinic info --}}
                            <h2 style="font-size:14px; color:#022D67; margin:24px 0 8px;">Clinic information</h2>
                            <p style="font-size:14px; line-height:1.6; color:#1F2A3A; margin:0;">
                                @if ($address !== '')<strong>Address:</strong> {{ $address }}<br>@endif
                                @if ($phone !== '')<strong>Phone:</strong> {{ $phone }}<br>@endif
                                @if (!empty($hours))
                                    <strong>Clinic hours:</strong>
                                    @foreach ($hours as $line)<br>&nbsp;&nbsp;{{ $line }}@endforeach
                                @endif
                            </p>

                            {{-- Reschedule / questions --}}
                            <h2 style="font-size:14px; color:#022D67; margin:24px 0 8px;">Need to reschedule or have a question?</h2>
                            <p style="font-size:14px; line-height:1.6; color:#1F2A3A; margin:0;">
                                @if ($phone !== '')Call the clinic at {{ $phone }}, or @endif
                                message our chatbot on the PolyClinic Lipa website anytime. Please have your reference number ready.
                            </p>

                            @if (!empty($reminders))
                                <h2 style="font-size:14px; color:#022D67; margin:24px 0 8px;">Reminders</h2>
                                <ul style="font-size:14px; line-height:1.6; color:#1F2A3A; margin:0; padding-left:20px;">
                                    @foreach ($reminders as $reminder)
                                        <li>{{ $reminder }}</li>
                                    @endforeach
                                </ul>
                            @endif
@endsection