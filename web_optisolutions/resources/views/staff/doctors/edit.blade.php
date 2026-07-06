@extends('staff.layouts.app')

@section('content')
<div class="container" style="max-width: 100%; padding: 0 1rem;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">

        <h3 style="color: var(--text-dark); margin-bottom: 1.5rem;">Edit Doctor</h3>

        <form method="POST" action="{{ route('staff.doctors.update', $doctor->id) }}">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.3rem;">Name</label>
                <input type="text" value="{{ $doctor->name }}" disabled
                    style="width:100%; padding:0.7rem 1rem; border:1px solid var(--light-gray); border-radius:10px; background:#f5f5f5;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.3rem;">Specialty</label>
                <input type="text" value="{{ $doctor->specialty }}" disabled
                    style="width:100%; padding:0.7rem 1rem; border:1px solid var(--light-gray); border-radius:10px; background:#f5f5f5;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.3rem;">Schedule</label>
                <input type="text" name="schedule" value="{{ old('schedule', $doctor->schedule) }}"
                    style="width:100%; padding:0.7rem 1rem; border:1px solid var(--light-gray); border-radius:10px;" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.3rem;">Status</label>
                <select name="status" style="width:100%; padding:0.7rem 1rem; border:1px solid var(--light-gray); border-radius:10px;">
                    <option value="Available" {{ $doctor->status === 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="Unavailable" {{ $doctor->status === 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="btn-sm btn-primary" style="padding:0.8rem 2rem;">Save Changes</button>
                <a href="{{ route('staff.doctors') }}" class="btn-sm btn-secondary" style="padding:0.8rem 2rem;">Cancel</a>
            </div>
        </form>

    </div>
</div>
@endsection