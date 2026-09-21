<header class="header">

    <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>

        {{-- Logo --}}
        <a class="logo-section" href="/admin_acc/dashboard">
            <img src="{{ asset('images/polyclinic_logo.png') }}" alt="Polyclinic Logo">
            <h1>Polyclinic Admin</h1>
        </a>
    </div>

    {{-- Right Controls --}}
    <div class="header-controls">

        {{-- Notification Bell --}}
        <div class="notif-wrapper">
            <button class="icon-btn" id="notifBtn" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot" id="notifDot"></span>
            </button>

            <div class="dropdown" id="notifDropdown">
                <div class="dropdown-label">Notifications</div>

                <div id="notifDropdownList">
                    <div class="notif-item notif-item-loading">
                        <div class="notif-text"><p>Loading notifications…</p></div>
                    </div>
                </div>

                <div class="dropdown-footer">
                    <a href="/admin_acc/notifications" onclick="openNotifPanel(); return false;">View all notifications</a>
                </div>
            </div>
        </div>

        {{-- Avatar / Profile --}}
        <div class="avatar-wrapper">
            <button class="avatar-btn" id="avatarBtn" aria-label="Profile menu">
    @if (Auth::user()->profile_photo)
        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="avatar-img">
    @else
        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
    @endif
</button>

            <div class="dropdown" id="avatarDropdown">
                <div class="profile-header">
                    <div class="profile-avatar-lg">
                        @if (Auth::user()->profile_photo)
                            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="avatar-img">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="profile-info">
                        <p class="profile-name">{{ Auth::user()->name }}</p>
                        <p class="profile-email">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <a class="menu-item" href="#" onclick="openProfileModal(); return false;">
    <i class="bi bi-person"></i> Profile
</a>
                <a class="menu-item" href="#" onclick="openSettingsModal(); return false;">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <button class="menu-item danger" onclick="openLogoutModal(); return false;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </div>
        </div>

    </div>
    @include('admin_acc.partials.profile')
    @include('admin_acc.partials.settings')
    @include('admin_acc.partials.logout')
</header>

{{-- Empty container the notifications panel gets injected into on demand --}}
<div id="notifPanelContainer"></div>

<script>
    // ── Toggle helper ──────────────────────────────────────────────
    function toggleDropdown(btnId, dropdownId, otherDropdownId) {
        const btn      = document.getElementById(btnId);
        const dropdown = document.getElementById(dropdownId);
        const other    = document.getElementById(otherDropdownId);

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            other.classList.remove('open');            // close the other one
            dropdown.classList.toggle('open');
        });
    }

    toggleDropdown('notifBtn',  'notifDropdown',  'avatarDropdown');
    toggleDropdown('avatarBtn', 'avatarDropdown', 'notifDropdown');

    // Close both when clicking outside
    document.addEventListener('click', () => {
        document.getElementById('notifDropdown').classList.remove('open');
        document.getElementById('avatarDropdown').classList.remove('open');
    });

    // ── Notification bell dropdown — real data ──────────────────────
    // Previously this dropdown was three hardcoded <div class="notif-item">
    // blocks with fake text (a made-up "CHAT-006", a fixed "June 15"
    // date, etc.) that never changed and never linked anywhere, while
    // the red badge's "unread" count was just however many of those
    // fake items happened to have a dot on them. It now pulls the
    // actual rows from the same `app_notifications` table the full
    // panel (partials/notifications_panel.blade.php) already reads.
    const NOTIF_TYPE_ICONS = {
        chat_inquiry: 'bi-chat-dots',
        appointment: 'bi-calendar-check',
        feedback: 'bi-star',
        complaint: 'bi-exclamation-circle',
        system: 'bi-exclamation-triangle',
    };

    function timeAgo(dateStr) {
        const seconds = Math.floor((Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000);
        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`;
        const days = Math.floor(hours / 24);
        if (days === 1) return 'Yesterday';
        if (days < 7) return `${days} days ago`;
        return new Date(dateStr.replace(' ', 'T')).toLocaleDateString();
    }

    function escapeHtmlNotif(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function loadNotifDropdown() {
        fetch('/admin_acc/notifications/api', { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                document.getElementById('notifDot').classList.toggle('visible', data.unread_count > 0);

                const list = document.getElementById('notifDropdownList');
                const items = data.notifications.slice(0, 5);

                if (items.length === 0) {
                    list.innerHTML = `<div class="notif-item"><div class="notif-text"><p>No notifications yet.</p></div></div>`;
                    return;
                }

                list.innerHTML = items.map(n => `
                    <div class="notif-item" data-id="${n.notification_id}" data-url="${n.url ?? ''}" style="cursor:${n.url ? 'pointer' : 'default'};">
                        <div class="notif-icon-circle"><i class="bi ${NOTIF_TYPE_ICONS[n.type] || 'bi-bell'}"></i></div>
                        <div class="notif-text">
                            <p>${escapeHtmlNotif(n.title || n.message)}</p>
                            <span>${timeAgo(n.created_at)}</span>
                        </div>
                        ${n.is_read ? '' : '<div class="notif-unread-dot"></div>'}
                    </div>
                `).join('');

                list.querySelectorAll('.notif-item[data-url]').forEach(item => {
                    const url = item.dataset.url;
                    if (!url) return;
                    item.addEventListener('click', () => {
                        fetch(`/admin_acc/notifications/${item.dataset.id}/read`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                        }).catch(() => {});
                        window.location.href = url;
                    });
                });
            })
            .catch(() => {});
    }

    loadNotifDropdown();
    // Refresh every time the bell is opened, so it doesn't go stale
    // while the admin sits on the page.
    document.getElementById('notifBtn').addEventListener('click', loadNotifDropdown);

    // ── Full notifications side panel ───────────────────────────────
    function openNotifPanel() {
        const container = document.getElementById('notifPanelContainer');
        fetch('/admin_acc/notifications', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                bindNotifPanelEvents();
            })
            .catch(() => {});

        // Close the small dropdown if it's open
        document.getElementById('notifDropdown').classList.remove('open');
    }

    function closeNotifPanel() {
        document.getElementById('notifPanelContainer').innerHTML = '';
    }

    function bindNotifPanelEvents() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        document.getElementById('notifCloseBtn')?.addEventListener('click', closeNotifPanel);
        document.getElementById('notifOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'notifOverlay') closeNotifPanel();
        });

        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeNotifPanel();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);

        const filterTabs = document.querySelectorAll('.notif-tab');
        const allGroups = document.querySelectorAll('#notifPanelContainer .notif-group');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                filterTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const filter = tab.dataset.filter;

                allGroups.forEach(group => {
                    const rowsInGroup = group.querySelectorAll('.notif-row');
                    let visibleCount = 0;

                    rowsInGroup.forEach(row => {
                        const matches = filter === 'all' || filter.split(',').includes(row.dataset.category);
                        row.style.display = matches ? '' : 'none';
                        if (matches) visibleCount++;
                    });

                    group.style.display = visibleCount > 0 ? '' : 'none';
                });
            });
        });

        const countAllEl = document.getElementById('count-all');
        if (countAllEl) {
            countAllEl.textContent = document.querySelectorAll('#notifPanelContainer .notif-row').length;
        }

        // ── Click a row → mark it read, then navigate ────────────────
        // (This has to live here, not in a <script> tag inside the panel
        // partial itself — script tags injected via innerHTML don't
        // execute, so binding has to happen from this persistent,
        // already-loaded script instead, same as everything else above.)
        document.querySelectorAll('#notifPanelContainer .notif-row').forEach(row => {
            const url = row.dataset.url;
            if (!url) return; // no destination for this type (e.g. system)

            row.addEventListener('click', () => {
                if (row.querySelector('.notif-action-link')) {
                    fetch(`/admin_acc/notifications/${row.dataset.id}/read`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                    }).catch(() => {});
                }
                window.location.href = url;
            });
        });

        document.getElementById('markAllReadBtn')?.addEventListener('click', function () {
            document.querySelectorAll('#notifPanelContainer .notif-row.unread').forEach(row => {
                row.classList.remove('unread');
                row.classList.add('read');
                row.querySelector('.notif-action-link')?.remove();
            });

            fetch('/admin_acc/notifications/mark-all-read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            }).then(() => loadNotifDropdown()).catch(() => {});
        });
    }

    function markSingleRead(id, btn) {
        const row = btn.closest('.notif-row');
        row.classList.remove('unread');
        row.classList.add('read');
        btn.remove();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        fetch(`/admin_acc/notifications/${id}/read`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(() => loadNotifDropdown()).catch(() => {});
    }
</script>