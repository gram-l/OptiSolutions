@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar">
        <h3>Patient Records</h3>
    </div>

    @if($patients->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Department</th>
                    <th>Assigned Doctor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patients as $p)
                <tr>
                    <td>{{ $p->patient_id }}</td>
                    <td>{{ $p->full_name }}</td>
                    <td>{{ $p->latestVisit->doctor->specialty ?? 'N/A' }}</td>
                    <td>{{ $p->latestVisit->doctor->doctor_name ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('staff.patients.show', $p->patient_id) }}" class="btn-sm btn-primary">View</a>
                        <a href="{{ route('staff.patients.edit', $p->patient_id) }}" class="btn-sm btn-warning">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No patients found.</p>
    @endif
</div>
@endsection