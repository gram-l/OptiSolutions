@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar">
        <h3>Manage Schedule Visit</h3>
    </div>

    @if($appointments->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Visit ID</th>
                    <th>Doctor</th>
                    <th>Service</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $apt)
                <tr>
                    <td>{{ $apt->visit_id }}</td>
                    <td>{{ $apt->doctor_name }}</td>
                    <td>{{ $apt->service }}</td>
                    <td>{{ \Carbon\Carbon::parse($apt->visit_date)->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No appointments found.</p>
    @endif
</div>
@endsection