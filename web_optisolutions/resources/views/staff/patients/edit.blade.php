@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); max-width: 700px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--light-gray); padding-bottom: 1rem; margin-bottom: 1rem;">
            <h3 style="color: var(--text-dark); margin: 0;">Edit Patient</h3>
            <a href="{{ route('staff.patients') }}" class="btn-sm btn-secondary">← Back</a>
        </div>

        <form method="POST" action="{{ route('staff.patients.update', $patient->patient_id) }}">
            @csrf
            @method('PUT')

            <!-- Patient ID - READ ONLY -->
            <div class="form-group">
                <label>Patient ID</label>
                <input type="text" value="{{ $patient->patient_id }}" disabled style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 8px; background: #f5f5f5; color: #999;">
                <small style="color: #999;">Patient ID cannot be edited.</small>
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="patient_fname" value="{{ $patient->patient_fname }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="patient_lname" value="{{ $patient->patient_lname }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="patient_birthdate" value="{{ \Carbon\Carbon::parse($patient->patient_birthdate)->format('Y-m-d') }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="patient_email" value="{{ $patient->patient_email }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="patient_contact" value="{{ $patient->patient_contact }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <!-- NOTES (diagnosis) - tied to the patient's latest visit -->
            <div class="form-group">
                <label>Notes / Diagnosis</label>
                @if($patient->latestVisit)
                    <input type="hidden" name="visit_id" value="{{ $patient->latestVisit->visit_id }}">
                    <textarea
                        name="notes"
                        rows="4"
                        placeholder="Enter the diagnosis or notes for this patient's latest visit..."
                        style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif; resize: vertical;"
                    >{{ $patient->latestVisit->notes }}</textarea>
                    <small style="color: #999;">This updates the notes for the latest visit ({{ \Carbon\Carbon::parse($patient->latestVisit->visit_date)->format('M d, Y') }}).</small>
                @else
                    <p style="color: #999; font-size: 0.9rem;">No visit on record yet — notes can be added once this patient has a visit.</p>
                @endif
            </div>

            <button type="submit" class="btn-sm btn-success" style="padding: 0.7rem 2rem; margin-top: 0.5rem;">Update Patient</button>
        </form>
    </div>
</div>
@endsection