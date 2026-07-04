@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); max-width: 600px; margin: 0 auto;">
        <h3 style="color: var(--text-dark);">Edit Doctor</h3>
        <a href="{{ route('staff.doctors') }}" class="btn-sm btn-secondary" style="margin-bottom: 1rem;">← Back</a>

        <form method="POST" action="{{ route('staff.doctors.update', $doctor->id) }}">
            @csrf
            @method('PUT')

            <!-- Name - DISPLAY ONLY (hindi pwedeng i-edit) -->
            <div class="form-group">
                <label>Name</label>
                <input type="text" value="{{ $doctor->doctor_name }}" disabled style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 8px; background: #f5f5f5; color: #999;">
                <small style="color: #999;">Name cannot be edited.</small>
            </div>

            <!-- Specialty - DISPLAY ONLY (hindi pwedeng i-edit) -->
            <div class="form-group">
                <label>Specialty</label>
                <input type="text" value="{{ $doctor->specialization }}" disabled style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 8px; background: #f5f5f5; color: #999;">
                <small style="color: #999;">Specialty cannot be edited.</small>
            </div>

            <!-- Schedule - Pwedeng i-edit -->
            <div class="form-group">
                <label>Schedule</label>
                <input type="text" name="schedule" value="{{ $doctor->schedule }}" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
            </div>

            <!-- Status - Pwedeng i-edit -->
            <div class="form-group">
                <label>Status</label>
                <select name="status" style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif;">
                    <option value="Available" {{ $doctor->status === 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="Unavailable" {{ $doctor->status === 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>
            </div>

            <button type="submit" class="btn-sm btn-success" style="padding: 0.7rem 2rem;">Update Doctor</button>
        </form>
    </div>
</div>
@endsection