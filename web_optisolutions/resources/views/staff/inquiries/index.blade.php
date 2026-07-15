@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar">
        <h3>Patient Inquiries</h3>
    </div>

    @if($inquiries->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Inquiry ID</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inquiries as $inq)
                <tr>
                    <td>{{ $inq->inquiry_id }}</td>
                    <td>
                        {{ $inq->log ? Str::limit($inq->log->user_message, 50) : '—' }}
                    </td>
                    <td>
                        <span class="status-badge {{ $inq->resolved_status === 'Pending' ? 'status-pending' : ($inq->resolved_status === 'Resolved' ? 'status-confirmed' : 'status-pending') }}">
                            {{ $inq->resolved_status }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('staff.inquiries.show', $inq->inquiry_id) }}" class="btn-sm btn-primary">
                            <i class="bi bi-chat"></i> Reply
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No inquiries found.</p>
    @endif
</div>
@endsection