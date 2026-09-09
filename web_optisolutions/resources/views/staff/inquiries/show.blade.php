@extends('staff.layouts.app')

@section('content')
@include('staff.inquiries.partials.inquiries-styles')

<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0;">
            <i class="bi bi-chat-square-text"></i> Chatbot Inquiries
        </h3>
        <p class="page-subtitle">View and respond to chatbot inquiries</p>
    </div>

    <div class="inquiries-split">
    
        @include('staff.inquiries.partials.conversations-list', [
            'inquiries' => $inquiries,
            'selectedId' => $inquiry->inquiry_id,
        ])

    
        <div class="conv-detail-panel">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--light-gray); padding-bottom: 1rem; margin-bottom: 1rem;">
                <div>
                    <h3 style="color: var(--text-dark); margin: 0;">CHAT-{{ str_pad($inquiry->inquiry_id, 3, '0', STR_PAD_LEFT) }}</h3>
                    <small style="color: #7f8c8d;">
                        ID: {{ $inquiry->patient_id ?? 'N/A' }}
                        @if($inquiry->inquiry_type)
                            &bull; {{ $inquiry->inquiry_type }}
                        @endif
                    </small>
                </div>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span class="status-badge {{ $inquiry->resolved_status === 'Pending' ? 'status-pending' : ($inquiry->resolved_status === 'Resolved' ? 'status-confirmed' : 'status-pending') }}">
                        {{ $inquiry->resolved_status }}
                    </span>
                    @if($inquiry->resolved_status !== 'Resolved')
                    <form action="{{ route('staff.inquiries.resolve', $inquiry->inquiry_id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn-sm btn-success">Resolve</button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Messages -->
            <div style="flex: 1; padding: 0.5rem 0; max-height: 500px; overflow-y: auto; min-height: 350px;">

            
                @php
                    // Same root cause as the Admin chatbot-inquiries view:
                    // BotManController::recordInquiryReply() logs a brand-new
                    // inquiry's first message both into chatbot_logs (what
                    // $inquiry->log is) and into inquiry_replies (as a
                    // "Patient" reply), so it would otherwise render twice
                    // here. Skip the standalone log bubble when the first
                    // reply is that same Patient message; inquiries from the
                    // standalone "Submit Inquiry" flow (no matching reply)
                    // are unaffected and still show the log bubble below.
                    $firstReply = $inquiry->replies->first();
                    $logDuplicatedByFirstReply = $inquiry->log
                        && $firstReply
                        && !$firstReply->is_staff
                        && trim((string) $firstReply->message) === trim((string) ($inquiry->log->user_message ?? ''));
                @endphp

                @unless($logDuplicatedByFirstReply)
                <div style="display: flex; justify-content: flex-start; margin-bottom: 1rem;">
                    <div style="background: var(--light-gray); padding: 0.8rem 1.5rem; border-radius: 18px; max-width: 80%;">
                        <strong style="color: var(--text-dark);">Patient</strong>
                        <p style="margin: 0.2rem 0 0 0; color: var(--text-dark); font-size: 1rem;">
                            {{ $inquiry->log->user_message ?? 'No message found.' }}
                        </p>
                        <small style="color: #7f8c8d; font-size: 0.7rem;">
                            {{ $inquiry->log && $inquiry->log->chat_time ? \Carbon\Carbon::parse($inquiry->log->chat_time)->format('h:i A') : 'N/A' }}
                        </small>
                    </div>
                </div>
                @endunless

                <!-- Full thread: every patient message/attachment and every Staff/Admin reply, in order -->
                @foreach($inquiry->replies as $reply)
                    @php $isStaffMsg = $reply->is_staff; @endphp
                    <div style="display: flex; justify-content: {{ $isStaffMsg ? 'flex-end' : 'flex-start' }}; margin-bottom: 1rem;">
                        <div style="background: {{ $isStaffMsg ? 'var(--primary-main)' : 'var(--light-gray)' }}; padding: 0.8rem 1.5rem; border-radius: 18px; max-width: 80%;">
                            <strong style="color: {{ $isStaffMsg ? 'white' : 'var(--text-dark)' }};">{{ $reply->sender }}</strong>

                            @if($reply->attachment_path)
                                @php
                                    $attExt = strtolower(pathinfo($reply->attachment_name ?? '', PATHINFO_EXTENSION));
                                    $isImageAtt = in_array($attExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                @endphp
                                @if($isImageAtt)
                                    <div style="margin-top: 0.4rem;">
                                        <a href="{{ $reply->attachment_url }}" target="_blank" rel="noopener">
                                            <img src="{{ $reply->attachment_url }}" alt="{{ $reply->attachment_name }}" style="max-width: 220px; max-height: 220px; border-radius: 8px; display: block;">
                                        </a>
                                    </div>
                                @else
                                    <div style="margin-top: 0.4rem;">
                                        <a href="{{ $reply->attachment_url }}" target="_blank" rel="noopener" style="color: {{ $isStaffMsg ? 'white' : 'var(--primary-main)' }}; text-decoration: underline;">
                                            <i class="bi bi-paperclip"></i> {{ $reply->attachment_name ?? 'Attachment' }}
                                        </a>
                                    </div>
                                @endif
                            @endif

                            @if($reply->message && $reply->message !== '(Sent an attachment)')
                                <p style="margin: 0.2rem 0 0 0; color: {{ $isStaffMsg ? 'white' : 'var(--text-dark)' }}; font-size: 1rem;">{{ $reply->message }}</p>
                            @endif

                            <small style="color: {{ $isStaffMsg ? 'rgba(255,255,255,0.7)' : '#7f8c8d' }}; font-size: 0.7rem;">
                                {{ $reply->created_at ? $reply->created_at->format('h:i A') : 'N/A' }}
                            </small>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Reply Form -->
            <div style="border-top: 1px solid var(--light-gray); padding-top: 1rem; margin-top: 1rem;">
                @if($inquiry->resolved_status === 'Resolved')
                <p style="color: var(--primary-main); font-size: 0.9rem; margin-bottom: 0.75rem;">
                    ✓ This inquiry has been marked as resolved. You can still reply if needed.
                </p>
                @endif
                <form method="POST" action="{{ route('staff.inquiries.reply', $inquiry->inquiry_id) }}" id="staffReplyForm">
                    @csrf
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="message" id="staffReplyInput" placeholder="Type your response..." value="{{ $inquiry->inquiry_reply }}" style="flex: 1; padding: 0.8rem 1rem; border: 1px solid var(--light-gray); border-radius: 25px; outline: none; font-family: 'Poppins', sans-serif; font-size: 1rem;" required>
                        <button type="submit" class="btn-sm btn-primary" style="padding: 0.8rem 2rem; border-radius: 25px; font-size: 1rem;">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // --- Typing presence --------------------------------------------------
    // Tells the patient-facing widget "a Staff member is actually typing a
    // reply right now" (via TypingStatusService on the backend), instead of
    // a fake indicator tied to the bot's own instant responses. The flag
    // has a short server-side TTL, so it's re-sent every few seconds while
    // the staff member keeps typing, and cleared on pause/submit/unload.
    (function () {
        const input = document.getElementById('staffReplyInput');
        const form = document.getElementById('staffReplyForm');
        if (!input || !form) return;

        const typingUrl = "{{ route('staff.inquiries.typing', $inquiry->inquiry_id) }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;

        let heartbeat = null;
        let idleTimer = null;
        let isActive = false;

        function postTyping(isTyping, useBeacon = false) {
            const body = JSON.stringify({ typing: isTyping });
            if (useBeacon && navigator.sendBeacon) {
                // Fires reliably even as the page is unloading (form submit
                // navigates away), unlike a regular fetch which can get cut off.
                navigator.sendBeacon(
                    typingUrl,
                    new Blob([body], { type: 'application/json' })
                );
                return;
            }
            fetch(typingUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body,
            }).catch(() => {}); // best-effort; a missed heartbeat just lets the TTL expire
        }

        function stopHeartbeat(useBeacon = false) {
            if (heartbeat) { clearInterval(heartbeat); heartbeat = null; }
            if (idleTimer) { clearTimeout(idleTimer); idleTimer = null; }
            if (isActive) {
                isActive = false;
                postTyping(false, useBeacon);
            }
        }

        input.addEventListener('input', () => {
            if (!isActive) {
                isActive = true;
                postTyping(true);
                heartbeat = setInterval(() => postTyping(true), 3000);
            }

            // Stop signalling "typing" if staff pauses for a few seconds
            // without sending — otherwise the dots would sit there forever.
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => stopHeartbeat(), 4000);
        });

        form.addEventListener('submit', () => stopHeartbeat(true));
        window.addEventListener('pagehide', () => stopHeartbeat(true));
    })();
</script>
@endsection