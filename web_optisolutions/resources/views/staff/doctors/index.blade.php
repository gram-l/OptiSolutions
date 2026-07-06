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
                    <td>{{ $doc->name }}</td>
                    <td>{{ $doc->specialty }}</td>
                    <td>{{ $doc->schedule ?? 'N/A' }}</td>
                    <td>
                        <span class="status-badge {{ $doc->status === 'Available' ? 'status-confirmed' : 'status-pending' }}">
                            {{ $doc->status }}
                        </span>
                    </td>
                    <td>
                        <button class="btn-sm btn-warning" onclick="toggleStatus({{ $doc->id }})">Availability</button>
                        <a href="{{ route('staff.doctors.edit', $doc->id) }}" class="btn-sm btn-secondary">Edit</a>
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