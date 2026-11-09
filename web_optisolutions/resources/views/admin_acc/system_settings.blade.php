<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/admin_css/user_management.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css', 'resources/css/admin_css/system_settings.css'])

    <style>
        .ss-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .ss-modal.open { display: flex; }
        .ss-modal-content {
            background: var(--white);
            border-radius: 12px;
            padding: 1.75rem;
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 12px 32px var(--shadow);
        }
    </style>
    @include('admin_acc.partials.centered-toast-styles')
</head>
<body>
    @include('admin_acc.header')

    <div id="settingsToast" class="toast" role="status" aria-atomic="true"></div>

    <div class="container">
        @include('admin_acc.sidebar')
        <main style="flex: 1; min-width: 0;">
            <div class="page-header">
            <h2>
                <span><i class="bi bi-gear"></i></span>
                System Settings
            </h2>
            <p>Manage clinic hours, about page, contact info, services, and FAQs</p>
        </div>

        {{-- Tabs --}}
        <div class="filter-bar">
            <div class="filter-group">
                <button class="add-patient-btn tab-btn active" data-tab="hours" type="button">Clinic Hours</button>
                <button class="add-patient-btn tab-btn" data-tab="about" type="button">About</button>
                <button class="add-patient-btn tab-btn" data-tab="contact" type="button">Contact</button>
                <button class="add-patient-btn tab-btn" data-tab="services" type="button">Services</button>
                <button class="add-patient-btn tab-btn" data-tab="faqs" type="button">FAQs</button>
            </div>
            <button class="add-patient-btn" id="addServiceBtn" type="button" onclick="openServiceModal()" style="display:none;">
                <i class="bi bi-plus-lg"></i> Add Service
            </button>
            <button class="add-patient-btn" id="addFaqBtn" type="button" onclick="openFaqModal()" style="display:none;">
                <i class="bi bi-plus-lg"></i> Add FAQ
            </button>
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
                                    <input type="text" name="core_values[]" value="{{ $value }}" placeholder="Name|Short description (description optional)">
                                    <button type="button" class="btn-icon btn-remove" aria-label="Remove core value" onclick="this.parentElement.remove()">&times;</button>
                                </div>
                            @empty
                                <div class="core-value-row">
                                    <input type="text" name="core_values[]" value="" placeholder="Name|Short description (description optional)">
                                    <button type="button" class="btn-icon btn-remove" aria-label="Remove core value" onclick="this.parentElement.remove()">&times;</button>
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

            <div class="patients-table-container" id="servicesTableContainer">
                @include('admin_acc.partials.services-table')
            </div>
        </div>

        {{-- FAQS --}}
        <div class="tab-panel" id="tab-faqs">
            <div class="patients-table-container" id="faqsTableContainer">
                @include('admin_acc.partials.faqs-table')
            </div>
        </div>

        {{-- FAQ Add/Edit Modal --}}
        <div class="ss-modal" id="faqModal" role="dialog" aria-modal="true" aria-labelledby="faqModalTitle">
            <div class="ss-modal-content">
                <h3 id="faqModalTitle">Add FAQ</h3>
                <form id="faqForm" method="POST">
                    <p id="faqFormError" role="alert" hidden style="color:#c0392b;margin:.75rem 0;"></p>
                    @csrf
                    <input type="hidden" name="_method" id="faqMethod" value="POST">

                    <div class="form-group">
                        <label for="faqQuestion">Question</label>
                        <input type="text" name="question" id="faqQuestion" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label for="faqAnswer">Answer</label>
                        <textarea name="answer" id="faqAnswer" rows="5" required maxlength="5000"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <label>
                            <input type="checkbox" name="is_active" id="faqActive" value="1" style="width:auto;">
                            Show on the website
                        </label>
                    </div>

                    <div class="modal-buttons">
                        <button type="button" class="btn-cancel" onclick="closeFaqModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save FAQ</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Service Add/Edit Modal (scoped .ss-modal — see <style> above) --}}
        <div class="ss-modal" id="serviceModal" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle">
            <div class="ss-modal-content">
                <h3 id="serviceModalTitle">Add Service</h3>
                <form id="serviceForm" method="POST" enctype="multipart/form-data">
                    <p id="serviceFormError" role="alert" hidden style="color:#c0392b;margin:.75rem 0;"></p>
                    @csrf
                    <input type="hidden" name="_method" id="serviceMethod" value="POST">

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="serviceTitle" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="serviceRoom">Room</label>
                        <input type="text" name="room" id="serviceRoom" placeholder="Room 201" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="serviceDescription" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="serviceFocusAreas">Clinical focus areas (one per line)</label>
                        <textarea name="focus_areas" id="serviceFocusAreas" rows="4" maxlength="2000" placeholder="Well-child checkups&#10;Childhood illnesses"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="serviceIcon">Icon</label>
                        <select name="icon" id="serviceIcon">
                            <option value="">Automatic (based on the service name)</option>
                            @foreach (\App\Support\ServiceCardStyle::ICONS as $iconClass => $iconLabel)
                                <option value="{{ $iconClass }}">{{ $iconLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="serviceColor">Card color</label>
                        <select name="card_color" id="serviceColor">
                            <option value="">Automatic</option>
                            @foreach (\App\Support\ServiceCardStyle::COLORS as $colorClass => $colorLabel)
                                <option value="{{ $colorClass }}">{{ $colorLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="serviceCardImage">Card picture (JPG, PNG or WEBP, max 2 MB)</label>
                        <input type="file" name="card_image" id="serviceCardImage" accept="image/png,image/jpeg,image/webp">
                        <div id="serviceCardImageCurrent" hidden style="margin-top:.5rem;">
                            <img id="serviceCardImagePreview" src="" alt="" style="width:120px;height:48px;object-fit:cover;border-radius:6px;display:block;">
                            <label style="display:flex;gap:.4rem;align-items:center;margin-top:.4rem;font-weight:normal;">
                                <input type="hidden" name="remove_card_image" value="0">
                                <input type="checkbox" name="remove_card_image" id="serviceRemoveCardImage" value="1" style="width:auto;">
                                Remove this picture
                            </label>
                        </div>
                    </div>
                    <div class="form-group" id="serviceAvailableWrap">
                        <input type="hidden" name="available" value="0">
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
    function activateSettingsTab(tab) {
        if (!['hours', 'about', 'contact', 'services', 'faqs'].includes(tab)) tab = 'hours';
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.toggle('active', panel.id === 'tab-' + tab));
        document.getElementById('addServiceBtn').style.display = tab === 'services' ? 'flex' : 'none';
        document.getElementById('addFaqBtn').style.display = tab === 'faqs' ? 'flex' : 'none';
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        history.replaceState(null, '', url);
    }
    document.querySelectorAll('.tab-btn').forEach(btn => btn.addEventListener('click', () => activateSettingsTab(btn.dataset.tab)));
    activateSettingsTab(new URL(window.location.href).searchParams.get('tab') || 'hours');

    // Core values dynamic rows
    function addCoreValueRow() {
        const container = document.getElementById('coreValuesList');
        const row = document.createElement('div');
        row.className = 'core-value-row';
        row.innerHTML = `
            <input type="text" name="core_values[]" value="" placeholder="Name|Short description (description optional)">
            <button type="button" class="btn-icon btn-remove" aria-label="Remove core value" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(row);
    }

    // Service modal (scoped: uses the .ss-modal "open" class, not display toggling
    // shared with any other modal on the page)
    const serviceModal = document.getElementById('serviceModal');
    const serviceForm   = document.getElementById('serviceForm');
    let savingService = false;

    // Shows the picture currently saved for the service being edited (if any).
    function setServiceImage(path) {
        const wrap = document.getElementById('serviceCardImageCurrent');
        document.getElementById('serviceCardImage').value = '';
        document.getElementById('serviceRemoveCardImage').checked = false;
        wrap.hidden = !path;
        document.getElementById('serviceCardImagePreview').src = path || '';
    }

    function openServiceModal(service = null) {
        if (savingService) return;
        document.getElementById('serviceFormError').hidden = true;

        if (service) {
            document.getElementById('serviceModalTitle').textContent = 'Edit Service';
            document.getElementById('serviceTitle').value = service.title;
            document.getElementById('serviceRoom').value = service.room ?? '';
            document.getElementById('serviceDescription').value = service.description ?? '';
            document.getElementById('serviceFocusAreas').value = service.focus_areas ?? '';
            document.getElementById('serviceAvailable').checked = !!service.available;
            document.getElementById('serviceIcon').value = (service.icon && !service.icon.includes(' ')) ? service.icon : '';
            document.getElementById('serviceColor').value = service.card_color ?? '';
            setServiceImage(service.card_image);
            document.getElementById('serviceMethod').value = 'PUT';
            serviceForm.action = `/admin_acc/system_settings/services/${service.service_id}`;
        } else {
            document.getElementById('serviceModalTitle').textContent = 'Add Service';
            serviceForm.reset();
            document.getElementById('serviceAvailable').checked = true;
            setServiceImage(null);
            document.getElementById('serviceMethod').value = 'POST';
            serviceForm.action = `/admin_acc/system_settings/services`;
        }

        serviceModal.classList.add('open');
        document.getElementById('serviceTitle').focus();
    }

    function closeServiceModal() {
        if (savingService) return;
        serviceModal.classList.remove('open');
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeServiceModal(); });

    async function submitService(form, deleting = false) {
        if (savingService) return;
        if (deleting && !confirm('Delete this service? It will also be removed from linked doctors.')) return;
        const body = new FormData(form);
        savingService = true;
        const buttons = [...document.querySelectorAll('#serviceForm button, #servicesTableContainer button, #addServiceBtn')];
        buttons.forEach(button => button.disabled = true);
        const errorBox = document.getElementById('serviceFormError');
        errorBox.hidden = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body, credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }, signal: AbortSignal.timeout(20000),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.html) {
                const message = response.status === 419 ? 'Your session expired. Refresh this page and sign in again.'
                    : Object.values(data.errors || {}).flat().join(' ') || data.message || 'Could not save your changes. Please try again.';
                throw new Error(message);
            }
            document.getElementById('servicesTableContainer').innerHTML = data.html;
            serviceModal.classList.remove('open');
            activateSettingsTab('services');
            document.getElementById('addServiceBtn').focus();
            ssShowToast('success', data.message);
        } catch (error) {
            const message = error.name === 'TimeoutError' ? 'The request timed out. Please try again.' : error.message;
            if (deleting) ssShowToast('error', message);
            else { errorBox.textContent = message; errorBox.hidden = false; }
        } finally {
            savingService = false;
            buttons.forEach(button => button.disabled = false);
        }
    }
    serviceForm.addEventListener('submit', event => { event.preventDefault(); submitService(serviceForm); });
    document.getElementById('servicesTableContainer').addEventListener('submit', event => {
        if (!event.target.matches('[data-service-delete]')) return;
        event.preventDefault();
        submitService(event.target, true);
    });

    serviceModal.addEventListener('click', (e) => {
        if (e.target === serviceModal) closeServiceModal();
    });

    // FAQ modal + actions (same pattern as the service modal above)
    const faqModal = document.getElementById('faqModal');
    const faqForm  = document.getElementById('faqForm');
    let savingFaq = false;

    function openFaqModal(faq = null) {
        if (savingFaq) return;
        document.getElementById('faqFormError').hidden = true;
        if (faq) {
            document.getElementById('faqModalTitle').textContent = 'Edit FAQ';
            document.getElementById('faqQuestion').value = faq.question ?? '';
            document.getElementById('faqAnswer').value = faq.answer ?? '';
            document.getElementById('faqActive').checked = !!faq.is_active;
            document.getElementById('faqMethod').value = 'PUT';
            faqForm.action = `/admin_acc/system_settings/faqs/${faq.faq_id}`;
        } else {
            document.getElementById('faqModalTitle').textContent = 'Add FAQ';
            faqForm.reset();
            document.getElementById('faqActive').checked = true;
            document.getElementById('faqMethod').value = 'POST';
            faqForm.action = `/admin_acc/system_settings/faqs`;
        }
        faqModal.classList.add('open');
        document.getElementById('faqQuestion').focus();
    }

    function closeFaqModal() {
        if (savingFaq) return;
        faqModal.classList.remove('open');
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFaqModal(); });
    faqModal.addEventListener('click', e => { if (e.target === faqModal) closeFaqModal(); });

    async function submitFaq(form, deleting = false) {
        if (savingFaq) return;
        if (deleting && !confirm('Delete this FAQ?')) return;
        const body = new FormData(form);
        savingFaq = true;
        const buttons = [...document.querySelectorAll('#faqForm button, #faqsTableContainer button, #addFaqBtn')];
        buttons.forEach(button => button.disabled = true);
        const errorBox = document.getElementById('faqFormError');
        errorBox.hidden = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body, credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }, signal: AbortSignal.timeout(20000),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.html) {
                const message = response.status === 419 ? 'Your session expired. Refresh this page and sign in again.'
                    : Object.values(data.errors || {}).flat().join(' ') || data.message || 'Could not save your changes. Please try again.';
                throw new Error(message);
            }
            document.getElementById('faqsTableContainer').innerHTML = data.html;
            faqModal.classList.remove('open');
            activateSettingsTab('faqs');
            ssShowToast('success', 'Saved', data.message);
        } catch (error) {
            const message = error.name === 'TimeoutError' ? 'The request timed out. Please try again.' : error.message;
            if (form === faqForm) { errorBox.textContent = message; errorBox.hidden = false; }
            else ssShowToast('error', 'Unsuccessful', message);
        } finally {
            savingFaq = false;
            buttons.forEach(button => button.disabled = false);
        }
    }
    faqForm.addEventListener('submit', event => { event.preventDefault(); submitFaq(faqForm); });
    document.getElementById('faqsTableContainer').addEventListener('submit', event => {
        if (!event.target.matches('[data-faq-action]')) return;
        event.preventDefault();
        submitFaq(event.target, event.target.matches('[data-faq-delete]'));
    });

    // ── Popup toast notifications ──
    function ssShowToast(type, title, message) {
        const container = document.getElementById('ssToastContainer');
        const toast = document.createElement('div');
        toast.className = `ss-toast ss-toast-${type}`;

        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
        toast.innerHTML = `
            <i class="bi ${icon} ss-toast-icon"></i>
            <div class="ss-toast-body">
                <div class="ss-toast-title"></div>
                <div class="ss-toast-message"></div>
            </div>
            <button type="button" class="ss-toast-close" aria-label="Close">&times;</button>
        `;
        toast.querySelector('.ss-toast-title').textContent = title;
        toast.querySelector('.ss-toast-message').textContent = message;

        const remove = () => {
            toast.classList.add('ss-toast-hide');
            setTimeout(() => toast.remove(), 200);
        };

        toast.querySelector('.ss-toast-close').addEventListener('click', remove);
        container.appendChild(toast);
        setTimeout(remove, 4500);
    }

    document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
            ssShowToast('success', @json(session('success')));
        @endif

        @if (session('error'))
            ssShowToast('error', @json(session('error')));
        @endif

        @if ($errors->any())
            ssShowToast('error', @json($errors->first()));
        @endif
    });
</script>
        </main>
    </div>
</body>
</html>
