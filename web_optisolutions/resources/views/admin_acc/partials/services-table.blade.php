<table class="patients-table">
    <thead><tr><th>Service</th><th>Room</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse ($services as $service)
        <tr>
            <td>{{ $service->title }}</td>
            <td>{{ $service->room ?: '—' }}</td>
            <td>{{ \Illuminate\Support\Str::limit($service->description, 100) ?: '—' }}</td>
            <td><span class="status-badge {{ $service->available ? 'status-active' : 'status-inactive' }}">{{ $service->available ? 'Available' : 'Unavailable' }}</span></td>
            <td><div class="action-buttons">
                <button type="button" class="btn-icon btn-edit" onclick="openServiceModal({{ Js::from($service) }})">Edit</button>
                <form action="{{ route('system_settings.services.destroy', $service->service_id) }}" method="POST" data-service-delete style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-icon" style="background:var(--danger);color:#fff;">Delete</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty-state">No services added yet.</td></tr>
    @endforelse
    </tbody>
</table>
