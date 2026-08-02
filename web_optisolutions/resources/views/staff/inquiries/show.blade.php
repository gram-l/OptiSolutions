@extends('staff.layouts.app')

@section('content')
@include('staff.inquiries.partials.inquiries-styles')

<div class="container">
    <div class="action-bar">
        <h3>Chatbot Inquiries</h3>
    </div>

    <div class="inquiries-split">
    
        @include('staff.inquiries.partials.conversations-list', [
            'inquiries' => $inquiries,
            'selectedId' => $inquiry->inquiry_id,
        ])

    
        <div class="conv-detail-panel">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--light-gray, #e0e0e0); padding-bottom: 1rem; margin-bottom: 1rem;">
                <div>
                    <h3 style="color: var(--text-dark, #2c3e50); margin: 0;">CHAT-{{ str_pad($inquiry->inquiry_id, 3, '0', STR_PAD_LEFT) }}</h3>
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

            
                <div style="display: flex; justify-content: flex-start; margin-bottom: 1rem;">
                    <div style="background: var(--light-gray, #ecf0f1); padding: 0.8rem 1.5rem; border-radius: 18px; max-width: 80%;">
                        <strong style="color: var(--text-dark, #2c3e50);">Patient</strong>
                        <p style="margin: 0.2rem 0 0 0; color: var(--text-dark, #2c3e50); font-size: 1rem;">
                            {{ $inquiry->log->user_message ?? 'No message found.' }}
                        </p>
                        <small style="color: #7f8c8d; font-size: 0.7rem;">
                            {{ $inquiry->log && $inquiry->log->chat_time ? \Carbon\Carbon::parse($inquiry->log->chat_time)->format('h:i A') : 'N/A' }}
                        </small>
                    </div>
                </div>

                <!-- Staff Reply -->
                @if($inquiry->inquiry_reply)
                <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                    <div style="background: var(--primary-deep-blue, #1a5fb4); padding: 0.8rem 1.5rem; border-radius: 18px; max-width: 80%;">
                        <strong style="color: white;">Staff</strong>
                        <p style="margin: 0.2rem 0 0 0; color: white; font-size: 1rem;">{{ $inquiry->inquiry_reply }}</p>
                        <small style="color: rgba(255,255,255,0.7); font-size: 0.7rem;">
                            {{ $inquiry->replied_at ? \Carbon\Carbon::parse($inquiry->replied_at)->format('h:i A') : 'N/A' }}
                        </small>
                    </div>
                </div>
                @endif
            </div>

            <!-- Reply Form -->
            <div style="border-top: 1px solid var(--light-gray, #e0e0e0); padding-top: 1rem; margin-top: 1rem;">
                @if($inquiry->resolved_status === 'Resolved')
                <p style="color: var(--primary-green, #2ecc71); font-size: 0.9rem; margin-bottom: 0.75rem;">
                    ✓ This inquiry has been marked as resolved. You can still reply if needed.
                </p>
                @endif
                <form method="POST" action="{{ route('staff.inquiries.reply', $inquiry->inquiry_id) }}">
                    @csrf
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="message" placeholder="Type your response..." value="{{ $inquiry->inquiry_reply }}" style="flex: 1; padding: 0.8rem 1rem; border: 1px solid var(--light-gray, #e0e0e0); border-radius: 25px; outline: none; font-family: 'Poppins', sans-serif; font-size: 1rem;" required>
                        <button type="submit" class="btn-sm btn-primary" style="padding: 0.8rem 2rem; border-radius: 25px; font-size: 1rem; background: var(--primary-deep-blue, #1a5fb4); color: white; border: none;">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection