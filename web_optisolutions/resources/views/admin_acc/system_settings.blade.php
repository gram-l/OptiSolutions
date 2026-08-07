<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Polyclinic Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/admin_css/user_management.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css', 'resources/css/admin_css/system_settings.css'])
</head>
<body>
    @include('admin_acc.header')
    <div class="container">
        @include('admin_acc.sidebar')
        <main style="flex: 1; min-width: 0;">
            <div class="page-header">
            <h2><i class="bi bi-gear"></i> System Settings</h2>
            <p>Manage clinic hours, about page, contact info, and services</p>
        </div>

        @if (session('success'))
            <div class="status-badge status-active" style="display:block; padding: 0.8rem 1.2rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tabs --}}
        <div class="filter-bar">
            <div class="filter-group">
                <button class="add-patient-btn tab-btn active" data-tab="hours" type="button">Clinic Hours</button>
                <button class="add-patient-btn tab-btn" data-tab="about" type="button">About</button>
                <button class="add-patient-btn tab-btn" data-tab="contact" type="button">Contact</button>
                <button class="add-patient-btn tab-btn" data-tab="services" type="button">Services</button>
            </div>
        </div>

        {{-- HOURS --}}
        <div class="tab-panel active" id="tab-hours">
            <div class="patients-table-container" style="padding: 1.5rem;">
                <form action="{{ route('system_settings.update', 'hours') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Operating Hours</label>
                        <textarea name="operating_hours" rows="3">{{ old('operating_hours', $clinic->operating_hours) }}</textarea>
                        <p style="font-size:0.8rem; color:#7f8c8d; margin-top:0.4rem;">
                            e.g. "Monday - Friday: 8:00 AM - 6:00 PM | Saturday: 9:00 AM - 1:00 PM"
                        </p>
                    </div>
                    <button type="submit" class="add-patient-btn">Save Hours</button>
                </form>
            </div>
        </div>

        {{-- ABOUT --}}
        <div class="tab-panel" id="tab-about">
            <div class="patients-table-container" style="padding: 1.5rem;">
                <form action="{{ route('system_settings.update', 'about') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="about_us" rows="4">{{ old('about_us', $clinic->about_us) }}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mission</label>
                            <textarea name="mission" rows="3">{{ old('mission', $clinic->mission) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Vision</label>
                            <textarea name="vision" rows="3">{{ old('vision', $clinic->vision) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Core Values</label>
                        <div id="coreValuesList">
                            @forelse (old('core_values', $clinic->core_values ?? []) as $value)
                                <div class="core-value-row">
                                    <input type="text" name="core_values[]" value="{{ $value }}">
                                    <button type="button" class="btn-icon" style="background:var(--danger); color:#fff;" onclick="this.parentElement.remove()">&times;</button>
                                </div>
                            @empty
                                <div class="core-value-row">
                                    <input type="text" name="core_values[]" value="">
                                    <button type="button" class="btn-icon" style="background:var(--danger); color:#fff;" onclick="this.parentElement.remove()">&times;</button>
                                </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn-icon btn-edit" onclick="addCoreValueRow()">+ Add Core Value</button>
                    </div>
                    <button type="submit" class="add-patient-btn">Save About</button>
                </form>
            </div>
        </div>

        {{-- CONTACT --}}
        <div class="tab-panel" id="tab-contact">
            <div class="patients-table-container" style="padding: 1.5rem;">
                <form action="{{ route('system_settings.update', 'contact') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="2">{{ old('address', $clinic->address) }}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_no" value="{{ old('contact_no', $clinic->contact_no) }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $clinic->email) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Facebook Link</label>
                            <input type="url" name="facebook_link" value="{{ old('facebook_link', $clinic->facebook_link) }}">
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" value="{{ old('website', $clinic->website) }}">
                        </div>
                    </div>
                    <button type="submit" class="add-patient-btn">Save Contact</button>
                </form>
            </div>
        </div>

        {{-- SERVICES --}}
        <div class="tab-panel" id="tab-services">

            <div class="filter-bar">
                <div></div>
                <button class="add-patient-btn" type="button" onclick="openServiceModal()">
                    <i class="bi bi-plus-lg"></i> Add Service
                </button>
            </div>

            <div class="patients-table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Room</th>
                            <th>Schedule</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.6rem;">
                                        <i class="{{ $service->icon }}"></i> {{ $service->title }}
                                    </div>
                                </td>
                                <td>{{ $service->room ?? '—' }}</td>
                                <td>{{ $service->schedule ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($service->description, 60) }}</td>
                                <td>
                                    <span class="status-badge {{ $service->available ? 'status-active' : 'status-inactive' }}">
                                        {{ $service->available ? 'Available' : 'Unavailable' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-icon btn-edit" onclick='openServiceModal(@json($service))'>Edit</button>

                                        <form action="{{ route('system_settings.services.destroy', $service->service_id) }}" method="POST"
                                              style="display:inline" onsubmit="return confirm('Delete this service?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" style="background:var(--danger); color:#fff;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">No services added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Service Add/Edit Modal --}}
        <div class="modal" id="serviceModal">
            <div class="modal-content">
                <h3 id="serviceModalTitle">Add Service</h3>
                <form id="serviceForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="serviceMethod" value="POST">

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="serviceTitle" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Icon (Font Awesome class)</label>
                            <input type="text" name="icon" id="serviceIcon" placeholder="fa-child">
                        </div>
                        <div class="form-group">
                            <label>Room</label>
                            <input type="text" name="room" id="serviceRoom" placeholder="Room 201">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Schedule</label>
                        <input type="text" name="schedule" id="serviceSchedule" placeholder="Monday - Saturday: 10am - 3pm">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="serviceDescription" rows="3"></textarea>
                    </div>
                    <div class="form-group" id="serviceAvailableWrap" style="display:none">
                        <label>
                            <input type="checkbox" name="available" id="serviceAvailable" value="1" style="width:auto;">
                            Available
                        </label>
                    </div>

                    <div class="modal-buttons">
                        <button type="button" class="btn-cancel" onclick="closeServiceModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Service</button>
                    </div>
                </form>
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
            <button type="button" class="btn-icon" style="background:var(--danger); color:#fff;" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(row);
    }

    // Service modal
    const serviceModal = document.getElementById('serviceModal');
    const serviceForm   = document.getElementById('serviceForm');

    function openServiceModal(service = null) {
        document.getElementById('serviceAvailableWrap').style.display = service ? 'block' : 'none';

        if (service) {
            document.getElementById('serviceModalTitle').textContent = 'Edit Service';
            document.getElementById('serviceTitle').value = service.title;
            document.getElementById('serviceIcon').value = service.icon ?? '';
            document.getElementById('serviceRoom').value = service.room ?? '';
            document.getElementById('serviceSchedule').value = service.schedule ?? '';
            document.getElementById('serviceDescription').value = service.description ?? '';
            document.getElementById('serviceAvailable').checked = !!service.available;
            document.getElementById('serviceMethod').value = 'PUT';
            serviceForm.action = `/admin_acc/system_settings/services/${service.service_id}`;
        } else {
            document.getElementById('serviceModalTitle').textContent = 'Add Service';
            serviceForm.reset();
            document.getElementById('serviceMethod').value = 'POST';
            serviceForm.action = `/admin_acc/system_settings/services`;
        }

        serviceModal.style.display = 'flex';
    }

    function closeServiceModal() {
        serviceModal.style.display = 'none';
    }

    serviceModal.addEventListener('click', (e) => {
        if (e.target === serviceModal) closeServiceModal();
    });
</script>
        </main>
    </div>
</body>
</html>