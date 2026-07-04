@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar">
        <h3>Manage Appointments</h3>
    </div>

    @if($appointments->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $apt)
                <tr>
                    <td>{{ $apt->patient_name }}</td>
                    <td>{{ $apt->doctor_name }}</td>
                    <td>{{ $apt->service_type }}</td>
                   <td>{{ $apt->appointment_date }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower($apt->status) }}">
                            {{ $apt->status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No appointments found.</p>
    @endif
</div>
@endsection