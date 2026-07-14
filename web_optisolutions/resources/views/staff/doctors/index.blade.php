@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar">
        <h3>Doctors</h3>
    </div>

    @if($doctors->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Specialty</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doctors as $doc)
                <tr>
                    <td>{{ $doc->doctor_name }}</td>
                    <td>{{ $doc->specialty }}</td>
                    <td>
                        @forelse($doc->schedules as $sched)
                            {{ $sched->day }} {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}<br>
                        @empty
                            N/A
                        @endforelse
                    </td>
                    <td>
                        <span class="status-badge {{ $doc->available ? 'status-confirmed' : 'status-pending' }}">
                            {{ $doc->available ? 'Available' : 'Unavailable' }}
                        </span>
                    </td>
                    <td>
                        <button class="btn-sm btn-warning" onclick="toggleStatus({{ $doc->doctor_id }})">Availability</button>
                        <a href="{{ route('staff.doctors.edit', $doc->doctor_id) }}" class="btn-sm btn-secondary">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No doctors found.</p>
    @endif
</div>

<script>
function toggleStatus(id) {
    fetch(`/staff/doctors/${id}/toggle`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
@endsection