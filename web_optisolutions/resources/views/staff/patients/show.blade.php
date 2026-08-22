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
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->full_name }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Department (latest visit)</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->latestVisit->doctor->specialty ?? 'N/A' }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Assigned Doctor (latest visit)</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->latestVisit->doctor->doctor_name ?? 'N/A' }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Birthday</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ \Carbon\Carbon::parse($patient->patient_birthdate)->format('M d, Y') }}</p>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-dark);">Contact</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->patient_contact }}</p>
            </div>
            <div style="grid-column: span 2;">
                <label style="font-weight: 600; color: var(--text-dark);">Email</label>
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;">{{ $patient->patient_email }}</p>
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <label style="font-weight: 600; color: var(--text-dark);">Notes / Diagnosis</label>
            @forelse($patient->visits as $visit)
                <div style="padding: 0.75rem; background: var(--light-gray); border-radius: 8px; margin-top: 0.5rem;">
                    <div style="font-size: 0.8rem; color: #7f8c8d; margin-bottom: 0.3rem;">
                        {{ \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y') }} —
                        {{ $visit->service_type }} with {{ $visit->doctor->doctor_name ?? 'N/A' }}
                    </div>
                    <div style="color: var(--text-dark);">
                        {{ $visit->notes ?: 'No notes recorded for this visit.' }}
                    </div>
                </div>
            @empty
                <p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px; margin-top: 0.5rem;">No notes recorded.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection