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
                <label>Patient Name</label>
                <input type="text" name="patient_name" value="{{ $patient->patient_name }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" value="{{ $patient->department }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Assigned Doctor</label>
                <input type="text" name="assigned_doctor" value="{{ $patient->assigned_doctor }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="birthday" value="{{ $patient->birthday }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="{{ $patient->contact_number }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">{{ $patient->remarks }}</textarea>
            </div>

            <button type="submit" class="btn-sm btn-success" style="padding: 0.7rem 2rem; margin-top: 0.5rem;">Update Patient</button>
        </form>
    </div>
</div>
@endsection