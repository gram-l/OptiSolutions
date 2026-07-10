{{-- resources/views/admin_acc/system_settings/index.blade.php --}}
@extends('admin_acc.layout') {{-- adjust to whatever your admin layout is called --}}

@section('content')
<div class="settings-page">

    <h2 class="settings-title">System Settings</h2>

    @if (session('success'))
        <div class="settings-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Tabs --}}
    <div class="settings-tabs">
        <button class="tab-btn active" data-tab="hours">Clinic Hours</button>
        <button class="tab-btn" data-tab="about">About</button>
        <button class="tab-btn" data-tab="contact">Contact</button>
        <button class="tab-btn" data-tab="services">Services</button>
    </div>

    {{-- HOURS --}}
    <div class="tab-panel active" id="tab-hours">
        <form action="{{ route('system-settings.update', 'hours') }}" method="POST">
            @csrf
            <div class="settings-field">
                <label>Operating Hours</label>
                <textarea name="operating_hours" rows="3">{{ old('operating_hours', $clinic->operating_hours) }}</textarea>
                <p class="settings-hint">e.g. "Monday - Friday: 8:00 AM - 6:00 PM | Saturday: 9:00 AM - 1:00 PM"</p>
            </div>
            <button type="submit" class="settings-save-btn">Save Hours</button>
        </form>
    </div>

    {{-- ABOUT --}}
    <div class="tab-panel" id="tab-about">
        <form action="{{ route('system-settings.update', 'about') }}" method="POST">
            @csrf
            <div class="settings-field">
                <label>Description</label>
                <textarea name="about_us" rows="4">{{ old('about_us', $clinic->about_us) }}</textarea>
            </div>
            <div class="settings-field">
                <label>Mission</label>
                <textarea name="mission" rows="3">{{ old('mission', $clinic->mission) }}</textarea>
            </div>
            <div class="settings-field">
                <label>Vision</label>
                <textarea name="vision" rows="3">{{ old('vision', $clinic->vision) }}</textarea>
            </div>
            <div class="settings-field">
                <label>Core Values</label>
                <div id="coreValuesList">
                    @forelse (old('core_values', $clinic->core_values ?? []) as $value)
                        <div class="core-value-row">
                            <input type="text" name="core_values[]" value="{{ $value }}">
                            <button type="button" class="remove-value-btn" onclick="this.parentElement.remove()">&times;</button>
                        </div>
                    @empty
                        <div class="core-value-row">
                            <input type="text" name="core_values[]" value="">
                            <button type="button" class="remove-value-btn" onclick="this.parentElement.remove()">&times;</button>
                        </div>
                    @endforelse
                </div>
                <button type="button" class="add-value-btn" onclick="addCoreValueRow()">+ Add Core Value</button>
            </div>
            <button type="submit" class="settings-save-btn">Save About</button>
        </form>
    </div>

    {{-- CONTACT --}}
    <div class="tab-panel" id="tab-contact">
        <form action="{{ route('system-settings.update', 'contact') }}" method="POST">
            @csrf
            <div class="settings-field">
                <label>Address</label>
                <textarea name="address" rows="2">{{ old('address', $clinic->address) }}</textarea>
            </div>
            <div class="settings-field">
                <label>Contact Number</label>
                <input type="text" name="contact_no" value="{{ old('contact_no', $clinic->contact_no) }}">
            </div>
            <div class="settings-field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $clinic->email) }}">
            </div>
            <div class="settings-field">
                <label>Facebook Link</label>
                <input type="url" name="facebook_link" value="{{ old('facebook_link', $clinic->facebook_link) }}">
            </div>
            <div class="settings-field">
                <label>Website</label>
                <input type="url" name="website" value="{{ old('website', $clinic->website) }}">
            </div>
            <button type="submit" class="settings-save-btn">Save Contact</button>
        </form>
    </div>

    {{-- SERVICES --}}
    <div class="tab-panel" id="tab-services">

        <button class="settings-save-btn" onclick="openServiceModal()">+ Add Service</button>

        <table class="services-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td>{{ $service->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($service->description, 60) }}</td>
                        <td>{{ $service->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <button type="button" onclick='openServiceModal(@json($service))'>Edit</button>

                            <form action="{{ route('system-settings.services.destroy', $service) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('Delete this service?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Service Add/Edit Modal --}}
    <div class="service-modal-overlay" id="serviceModalOverlay">
        <div class="service-modal">
            <div class="service-modal-header">
                <h5 id="serviceModalTitle">Add Service</h5>
                <button type="button" onclick="closeServiceModal()">&times;</button>
            </div>
            <form id="serviceForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="serviceMethod" value="POST">

                <div class="settings-field">
                    <label>Name</label>
                    <input type="text" name="name" id="serviceName" required>
                </div>
                <div class="settings-field">
                    <label>Description</label>
                    <textarea name="description" id="serviceDescription" rows="3"></textarea>
                </div>
                <div class="settings-field">
                    <label>Icon (optional, e.g. bootstrap icon class)</label>
                    <input type="text" name="icon" id="serviceIcon">
                </div>
                <div class="settings-field" id="serviceActiveWrap" style="display:none">
                    <label>
                        <input type="checkbox" name="is_active" id="serviceActive" value="1">
                        Active
                    </label>
                </div>

                <button type="submit" class="settings-save-btn">Save Service</button>
            </form>
        </div>
    </div>

</div>

<script>
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });

    // Core values dynamic rows
    function addCoreValueRow() {
        const container = document.getElementById('coreValuesList');
        const row = document.createElement('div');
        row.className = 'core-value-row';
        row.innerHTML = `
            <input type="text" name="core_values[]" value="">
            <button type="button" class="remove-value-btn" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(row);
    }

    // Service modal
    const serviceModalOverlay = document.getElementById('serviceModalOverlay');
    const serviceForm         = document.getElementById('serviceForm');

    function openServiceModal(service = null) {
        document.getElementById('serviceActiveWrap').style.display = service ? 'block' : 'none';

        if (service) {
            document.getElementById('serviceModalTitle').textContent = 'Edit Service';
            document.getElementById('serviceName').value = service.name;
            document.getElementById('serviceDescription').value = service.description ?? '';
            document.getElementById('serviceIcon').value = service.icon ?? '';
            document.getElementById('serviceActive').checked = service.is_active;
            document.getElementById('serviceMethod').value = 'PUT';
            serviceForm.action = `/admin_acc/system-settings/services/${service.id}`;
        } else {
            document.getElementById('serviceModalTitle').textContent = 'Add Service';
            serviceForm.reset();
            document.getElementById('serviceMethod').value = 'POST';
            serviceForm.action = `/admin_acc/system-settings/services`;
        }

        serviceModalOverlay.classList.add('open');
    }

    function closeServiceModal() {
        serviceModalOverlay.classList.remove('open');
    }

    serviceModalOverlay.addEventListener('click', (e) => {
        if (e.target === serviceModalOverlay) closeServiceModal();
    });
</script>

<style>
.settings-tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #eee; }
.tab-btn { background: none; border: none; padding: 10px 16px; cursor: pointer; font-weight: 600; color: #666; }
.tab-btn.active { color: #1A237E; border-bottom: 2px solid #1A237E; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }
.settings-field { margin-bottom: 16px; }
.settings-field label { display: block; font-weight: 600; margin-bottom: 4px; }
.settings-field input, .settings-field textarea {
    width: 100%; padding: 8px 12px; border: 1px solid #d9d9e3; border-radius: 8px;
}
.settings-hint { font-size: 0.8rem; color: #888; margin-top: 4px; }
.settings-save-btn {
    background: #1A237E; color: #fff; border: none; padding: 10px 18px;
    border-radius: 8px; font-weight: 600; cursor: pointer; margin-bottom: 16px;
}
.settings-alert-success { background: #d1f7dc; color: #157347; padding: 8px 12px; border-radius: 8px; margin-bottom: 16px; }
.core-value-row { display: flex; gap: 8px; margin-bottom: 8px; }
.remove-value-btn { background: #dc3545; color: #fff; border: none; border-radius: 6px; width: 32px; cursor: pointer; }
.add-value-btn { background: none; border: 1px dashed #1A237E; color: #1A237E; padding: 6px 12px; border-radius: 8px; cursor: pointer; }
.services-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.services-table th, .services-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
.services-table button.danger { background: #dc3545; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; cursor: pointer; }
.service-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1000; align-items: center; justify-content: center; }
.service-modal-overlay.open { display: flex; }
.service-modal { background: #fff; width: 100%; max-width: 420px; border-radius: 16px; padding: 20px 24px; max-height: 90vh; overflow-y: auto; }
.service-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
</style>
@endsection