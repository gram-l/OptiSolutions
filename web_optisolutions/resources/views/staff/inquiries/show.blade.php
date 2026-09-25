@extends('staff.layouts.app')

@section('content')
@include('staff.inquiries.partials.inquiries-styles')

<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0; font-size: 1.75rem; color: var(--primary-dark); display: flex; align-items: center;">
            <span class="inq-header-icon"><i class="bi bi-chat-square-text"></i></span> Chatbot Inquiries
        </h3>
        <p class="page-subtitle">Review and respond to patient conversations from the AI chatbot</p>
    </div>

    <div class="inquiries-split inquiries-split--detail-open">
    
        @include('staff.inquiries.partials.conversations-list', [
            'inquiries' => $inquiries,
            'selectedId' => $inquiry->inquiry_id,
        ])

    
        <div class="conv-detail-panel">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--light-gray); padding-bottom: 1rem; margin-bottom: 1rem;">
                <div>
                    <a href="{{ route('staff.inquiries') }}" class="conv-back-link">
                        <i class="bi bi-arrow-left"></i> Back to conversations
                    </a>
                    @php
                        $displayName = $inquiry->patient_id ? ('Patient #' . $inquiry->patient_id) : ($inquiry->guest_name ?: 'Guest');
                        $patientLabel = $inquiry->patient_id ? 'P-' . $inquiry->patient_id : 'Guest';
                    @endphp
                    <h3 style="color: var(--text-dark); margin: 0;">{{ $displayName }}</h3>
                    <small style="color: #7f8c8d;">
                        INQ-{{ str_pad($inquiry->inquiry_id, 3, '0', STR_PAD_LEFT) }} &bull; ID: {{ $patientLabel }}
                        @if($inquiry->inquiry_type)
                            &bull; {{ $inquiry->inquiry_type }}
                        @endif
                    </small>
                </div>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span class="inq-status-badge {{ $inquiry->resolved_status === 'Resolved' ? 'inq-status-resolved' : 'inq-status-pending' }}">
                        {{ $inquiry->resolved_status === 'Resolved' ? 'Resolved' : 'In Progress' }}
                    </span>
                    @if($inquiry->resolved_status !== 'Resolved')
                    <form action="{{ route('staff.inquiries.resolve', $inquiry->inquiry_id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="inq-resolve-btn"><i class="bi bi-check2-circle"></i> Mark as Resolve</button>
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
                <div class="inq-message">
                    <div class="inq-message-avatar patient"><i class="bi bi-person"></i></div>
                    <div class="inq-message-content">
                        <div class="inq-message-sender">Patient</div>
                        <p class="inq-message-text">
                            {{ $inquiry->log->user_message ?? 'No message found.' }}
                        </p>
                        <small class="inq-message-time">
                            {{ $inquiry->log && $inquiry->log->chat_time ? \Carbon\Carbon::parse($inquiry->log->chat_time)->format('h:i A') : 'N/A' }}
                        </small>
                    </div>
                </div>
                @endunless

                <!-- Full thread: every patient message/attachment and every Staff/Admin reply, in order -->
                @foreach($inquiry->replies as $reply)
                    @php $isStaffMsg = $reply->is_staff; @endphp
                    <div class="inq-message {{ $isStaffMsg ? 'staff-row' : '' }}">
                        <div class="inq-message-avatar {{ $isStaffMsg ? '' : 'patient' }}">
                            <i class="bi {{ $isStaffMsg ? 'bi-headset' : 'bi-person' }}"></i>
                        </div>
                        <div class="inq-message-content {{ $isStaffMsg ? 'staff' : '' }}">
                            <div class="inq-message-sender {{ $isStaffMsg ? 'staff' : '' }}">{{ $reply->sender }}</div>

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
                                    <div class="inq-message-attachment">
                                        <a href="{{ $reply->attachment_url }}" target="_blank" rel="noopener" style="color: {{ $isStaffMsg ? 'var(--primary-main)' : 'var(--primary-dark)' }};">
                                            <i class="bi bi-paperclip"></i> {{ $reply->attachment_name ?? 'Attachment' }}
                                        </a>
                                    </div>
                                @endif
                            @endif

                            @if($reply->message && $reply->message !== '(Sent an attachment)')
                                <p class="inq-message-text">{{ $reply->message }}</p>
                            @endif

                            <small class="inq-message-time">
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
                        <input type="text" name="message" id="staffReplyInput" class="inq-reply-input" placeholder="Type your response..." value="{{ $inquiry->inquiry_reply }}" required>
                        <button type="submit" class="inq-send-btn">Send Reply</button>
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