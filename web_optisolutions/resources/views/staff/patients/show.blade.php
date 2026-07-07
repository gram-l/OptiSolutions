@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); max-width: 700px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--light-gray); padding-bottom: 1rem; margin-bottom: 1rem;">
            <h3 style="color: var(--text-dark); margin: 0;">Patient Details</h3>
            <div>
                <a href="{{ route('staff.patients') }}" class="btn-sm btn-secondary">← Back</a>
                <a href="{{ route('staff.patients.edit', $patient->patient_id) }}" class="btn-sm btn-warning">Edit</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Patient ID</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->patient_id }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Patient Name</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->patient_name }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Department</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->department }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Assigned Doctor</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->assigned_doctor }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Age</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->age }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Phone</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->phone }}</p>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <label style="font-weight: 600; color: var(--text-dark);">Last Visit</label>
            <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->last_visit ?? 'No record' }}</p>
        </div>
    </div>
</div>
@endsection