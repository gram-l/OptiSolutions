@extends('emails.layouts.notification')
@section('category', 'Chatbot conversation')
@section('title', 'Your conversation transcript')
@section('content')
    @php
        // Converts the same **bold** markdown-style markers used for
        // chat-bubble styling on the frontend (chatbot.js) into real
        // <strong> tags for this HTML email. Callers must pass text
        // that has ALREADY been HTML-escaped (e.g. via e()) â€” this
        // only inserts <strong>/</strong> around the escaped content,
        // it does not escape anything itself.
        if (!function_exists('polyclinic_transcript_boldify')) {
            function polyclinic_transcript_boldify(string $escapedText): string
            {
                return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escapedText);
            }
        }
    @endphp
<p style="font-size:14px; color:#111827; margin:0 0 16px;">
                                Hi {{ $patientName }}, here's a copy of your conversation with us:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                @foreach ($transcript as $entry)
                                    @php
                                        $isBot = ($entry['role'] ?? '') === 'Bot';
                                        $senderLabel = $isBot ? 'PolyClinic Bot' : $patientName;
                                        // Escape first, then turn **..** into <strong>..</strong>,
                                        // then let nl2br handle line breaks â€” same order the bot
                                        // uses on-screen (escape, then style, then wrap lines).
                                        $formattedText = nl2br(polyclinic_transcript_boldify(e($entry['text'])));
                                    @endphp
                                    <tr>
                                        {{--
                                            Row-level left/right split â€” this is the actual fix.
                                            Previously both roles rendered into the SAME left-hand
                                            cell (only the label/background color changed via the
                                            ternaries), so nothing ever moved to the right side no
                                            matter who sent it. Now the message block itself sits
                                            in a left OR right <td> depending on role, with an
                                            empty spacer <td> on the opposite side â€” the standard
                                            way to fake flex-style alignment in HTML email, since
                                            Gmail and most clients ignore flexbox/grid CSS.
                                        --}}
                                        <td style="padding:6px 0; vertical-align:top;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    @if ($isBot)
                                                        {{-- BOT â€” left column, white bubble (matches .chat-message.bot .chat-bubble in chatbot.css) --}}
                                                        <td align="left" style="width:75%; vertical-align:top;">
                                                            <div style="font-size:11px; font-weight:bold; color:#0F67B3; margin-bottom:3px;">
                                                                {{ $senderLabel }}
                                                                <span style="font-weight:normal; color:#9ca3af;">&nbsp;&middot;&nbsp;{{ $entry['time'] }}</span>
                                                            </div>
                                                            <div style="font-size:13px; line-height:1.5; color:#1F2A3A; background-color:#ffffff; border:1px solid #E4E9F0; padding:8px 14px; border-radius:18px 18px 18px 5px; display:inline-block; white-space:pre-line;">
                                                                {!! $formattedText !!}
                                                            </div>
                                                        </td>
                                                        <td style="width:25%;">&nbsp;</td>
                                                    @else
                                                        {{-- PATIENT â€” right column, solid blue bubble (matches .chat-message.user .chat-bubble in chatbot.css) --}}
                                                        <td style="width:25%;">&nbsp;</td>
                                                        <td align="right" style="width:75%; vertical-align:top;">
                                                            <div style="font-size:11px; font-weight:bold; color:#374151; margin-bottom:3px; text-align:right;">
                                                                {{ $senderLabel }}
                                                                <span style="font-weight:normal; color:#9ca3af;">&nbsp;&middot;&nbsp;{{ $entry['time'] }}</span>
                                                            </div>
                                                            <div style="font-size:13px; line-height:1.5; color:#ffffff; background-color:#0F67B3; padding:8px 14px; border-radius:18px 18px 5px 18px; display:inline-block; text-align:left; white-space:pre-line;">
                                                                {!! $formattedText !!}
                                                            </div>
                                                        </td>
                                                    @endif
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="font-size:12px; color:#9ca3af; margin-top:24px;">
                                If you have questions about your schedule visit, please contact us at 0985 475 5511.
                            </p>
@endsection
