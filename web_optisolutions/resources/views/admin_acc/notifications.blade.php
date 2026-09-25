<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Notifications</title>

    @vite(['resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css', 'resources/css/admin_css/feedback.css'])

    <style>
        html, body { height: 100%; }
        body { overflow: hidden; }

        /* ── Backdrop ─────────────────────────────────────────────── */
        .notif-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 39, 68, 0.35);
            z-index: 900;
            display: flex;
            justify-content: flex-end;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* ── Side panel ───────────────────────────────────────────── */
        .notif-panel {
            width: 420px;
            max-width: 92vw;
            height: 100%;
            background: var(--white);
            box-shadow: -8px 0 30px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            animation: slideIn 0.25s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        /* ── Panel header ─────────────────────────────────────────── */
        .notif-panel-header {
            padding: 1.2rem 1.5rem 0.8rem;
            border-bottom: 1px solid var(--light-gray);
            flex-shrink: 0;
        }
        .notif-panel-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif-panel-header-top h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: 0.02em;
        }
        .notif-close-btn {
            background: none;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #7f8c8d;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .notif-close-btn:hover { background: var(--light-gray); }

        /* ── Tabs ─────────────────────────────────────────────────── */
        .notif-tabs {
            display: flex;
            gap: 1.4rem;
            margin-top: 0.9rem;
            overflow-x: auto;
        }
        .notif-tab {
            background: none;
            border: none;
            padding: 0 0 0.7rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #7f8c8d;
            cursor: pointer;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }
        .notif-tab:hover { color: var(--primary-main); }
        .notif-tab.active { color: var(--primary-main); font-weight: 600; }
        .notif-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-main);
            border-radius: 2px 2px 0 0;
        }
        .notif-tab .tab-count {
            background: var(--light-gray);
            color: var(--text-dark);
            font-size: 0.68rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
        }
        .notif-tab.active .tab-count { background: var(--primary-main); color: var(--white); }

        /* ── Toolbar ──────────────────────────────────────────────── */
        .notif-toolbar {
            display: flex;
            justify-content: flex-end;
            padding: 0.6rem 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            flex-shrink: 0;
        }
        .mark-all-link {
            background: none;
            border: none;
            color: var(--primary-main);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }
        .mark-all-link:hover { text-decoration: underline; }

        /* ── See more ─────────────────────────────────────────────── */
        .notif-see-more-wrap {
            display: flex;
            justify-content: center;
            padding: 1rem 1.5rem 1.4rem;
        }
        .see-more-btn {
            background: none;
            border: 1px solid var(--light-gray);
            color: var(--primary-main);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5rem 1.4rem;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .see-more-btn:hover { background: var(--light-gray); }

        /* ── Scrollable list ──────────────────────────────────────── */
        .notif-list-scroll {
            flex: 1;
            overflow-y: auto;
        }
        .notif-group-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #b2bac2;
            text-transform: uppercase;
            padding: 0.9rem 1.5rem 0.3rem;
        }

        /* ── Row ──────────────────────────────────────────────────── */
        .notif-row {
            display: flex;
            gap: 0.8rem;
            padding: 0.85rem 1.5rem;
            border-bottom: 1px solid #f1f3f5;
            transition: background 0.15s ease;
        }
        .notif-row:hover { background: #f8fafc; }
        .notif-row.unread { background: #f4f8fd; }
        .notif-row.unread:hover { background: #eef4fb; }

        .notif-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary-dark);
            font-size: 0.95rem;
            margin-top: 0.1rem;
        }
        .notif-icon.icon-appointments { background: #d7ecd9; color: #1f7a3d; }
        .notif-icon.icon-patients { background: #fbe6c8; color: #b3690b; }
        .notif-icon.icon-system { background: #fbdad7; color: var(--danger); }

        .notif-row-body { flex: 1; min-width: 0; }
        .notif-title-line {
            font-size: 0.85rem;
            color: var(--text-dark);
            line-height: 1.3;
        }
        .notif-title-line strong { font-weight: 600; color: var(--primary-dark); }
        .notif-row.unread .notif-title-line::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary-main);
            margin-right: 0.4rem;
            vertical-align: middle;
        }
        .notif-meta-line {
            font-size: 0.72rem;
            color: #9aa4ad;
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .notif-meta-tag {
            background: var(--light-gray);
            color: #6b7580;
            padding: 1px 7px;
            border-radius: 999px;
            font-size: 0.66rem;
            font-weight: 600;
        }
        .notif-message {
            font-size: 0.8rem;
            color: #7f8c8d;
            line-height: 1.4;
            margin-top: 0.3rem;
        }
        .notif-action-link {
            flex-shrink: 0;
            background: none;
            border: none;
            color: var(--primary-main);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            margin-top: 0.1rem;
        }
        .notif-action-link:hover { text-decoration: underline; }

        .notif-row-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.6rem;
        }

        .empty-state { padding: 3rem 1.5rem; }
    </style>
</head>
<body>
    <!-- Header -->
    @include('admin_acc.header')

    {{-- ── Backdrop + Side Panel ───────────────────────────────────── --}}
    <div class="notif-overlay" id="notifOverlay">
        <div class="notif-panel">

            <div class="notif-panel-header">
                <div class="notif-panel-header-top">
                    <h2>NOTIFICATIONS</h2>
                    <button class="notif-close-btn" id="notifCloseBtn" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="notif-tabs" id="notifFilters">
                    <button class="notif-tab active" data-filter="all">
                        All <span class="tab-count" id="count-all">0</span>
                    </button>
                    <button class="notif-tab" data-filter="chat_inquiry">
                        Chatbot <span class="tab-count" id="count-chat_inquiry">0</span>
                    </button>
                    <button class="notif-tab" data-filter="appointment">
                        Scheduled Visit <span class="tab-count" id="count-appointment">0</span>
                    </button>
                    <button class="notif-tab" data-filter="feedback,complaint">
                        Feedback <span class="tab-count" id="count-feedback">0</span>
                    </button>
                    <button class="notif-tab" data-filter="system">
                        System <span class="tab-count" id="count-system">0</span>
                    </button>
                </div>
            </div>

            <div class="notif-toolbar">
                <button class="mark-all-link" id="markAllReadBtn">Mark all as read</button>
            </div>

            <div class="notif-list-scroll" id="notifList">

                @php
                    // Where each notification type should navigate to on click.
                    $notifTypeUrls = [
                        'chat_inquiry' => '/admin_acc/chatbot_logs',
                        'appointment'  => '/admin_acc/appointments',
                        'feedback'     => '/admin_acc/feedback',
                        'complaint'    => '/admin_acc/feedback',
                    ];
                @endphp
                @forelse ($groupedNotifications as $group => $items)
                    <div class="notif-group" data-group="{{ \Illuminate\Support\Str::slug($group) }}">
                        <div class="notif-group-label">{{ $group }}</div>

                        @foreach ($items as $notification)
                            <div class="notif-row {{ $notification->is_read ? 'read' : 'unread' }}"
                                 data-category="{{ $notification->type }}"
                                 data-id="{{ $notification->notification_id }}"
                                 data-url="{{ $notifTypeUrls[$notification->type] ?? '' }}"
                                 style="cursor: {{ isset($notifTypeUrls[$notification->type]) ? 'pointer' : 'default' }};">

                                <div class="notif-icon icon-{{ $notification->type }}">
                                    <i class="bi {{ $notification->icon }}"></i>
                                </div>

                                <div class="notif-row-body">
                                    <div class="notif-row-top">
                                        <div>
                                            <div class="notif-title-line">
                                                <strong>{{ $notification->title }}</strong>
                                            </div>
                                            <div class="notif-meta-line">
                                                <span>{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                                                <span class="notif-meta-tag">{{ ucfirst($notification->type) }}</span>
                                                @if ($notification->reference_type && $notification->reference_id)
                                                    <span class="notif-meta-tag">{{ strtoupper($notification->reference_type) }}-{{ $notification->reference_id }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        @unless ($notification->is_read)
                                            <button class="notif-action-link" onclick="event.stopPropagation(); markSingleRead({{ $notification->notification_id }}, this)">Mark read</button>
                                        @endunless
                                    </div>

                                    <p class="notif-message">{{ $notification->message }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-bell-slash" style="font-size: 2.2rem;"></i>
                        <p style="margin-top: 1rem; font-weight: 600; color: var(--text-dark);">No notifications yet</p>
                        <span>You're all caught up. New activity will show up here.</span>
                    </div>
                @endforelse

                <div class="notif-see-more-wrap" id="notifSeeMoreWrap" style="display: none;">
                    <button class="see-more-btn" id="notifSeeMoreBtn">See more</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // ── Close panel ────────────────────────────────────────────
        function closePanel() {
            window.location.href = '/admin_acc/dashboard';
        }
        document.getElementById('notifCloseBtn').addEventListener('click', closePanel);
        document.getElementById('notifOverlay').addEventListener('click', (e) => {
            if (e.target.id === 'notifOverlay') closePanel();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePanel();
        });

        // ── Tab filtering + "See more" pagination ────────────────────
        const filterTabs = document.querySelectorAll('.notif-tab');
        const allRows = document.querySelectorAll('.notif-row');
        const allGroups = document.querySelectorAll('.notif-group');
        const seeMoreWrap = document.getElementById('notifSeeMoreWrap');
        const seeMoreBtn = document.getElementById('notifSeeMoreBtn');

        const PAGE_SIZE = 7;
        let currentFilter = 'all';
        let visibleLimit = PAGE_SIZE;

        function renderList() {
            let shown = 0;
            let totalMatching = 0;

            allGroups.forEach(group => {
                const rowsInGroup = group.querySelectorAll('.notif-row');
                let visibleInGroup = 0;

                rowsInGroup.forEach(row => {
                    const matches = currentFilter === 'all' || currentFilter.split(',').includes(row.dataset.category);
                    if (!matches) {
                        row.style.display = 'none';
                        return;
                    }
                    totalMatching++;
                    if (shown < visibleLimit) {
                        row.style.display = '';
                        shown++;
                        visibleInGroup++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                group.style.display = visibleInGroup > 0 ? '' : 'none';
            });

            seeMoreWrap.style.display = totalMatching > visibleLimit ? 'flex' : 'none';
        }

        filterTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                filterTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentFilter = tab.dataset.filter;
                visibleLimit = PAGE_SIZE;
                renderList();
            });
        });

        seeMoreBtn.addEventListener('click', () => {
            visibleLimit += PAGE_SIZE;
            renderList();
        });

        renderList();

        // ── Counts ──────────────────────────────────────────────────
        // Maps each tab's <span class="tab-count"> id to the notif type(s) it covers.
        const TAB_COUNT_MAP = {
            'count-chat_inquiry': ['chat_inquiry'],
            'count-appointment':  ['appointment'],
            'count-feedback':     ['feedback', 'complaint'],
            'count-system':       ['system'],
        };

        function updateCounts() {
            const unreadRows = document.querySelectorAll('.notif-row.unread');

            document.getElementById('count-all').textContent = unreadRows.length;

            Object.entries(TAB_COUNT_MAP).forEach(([elId, types]) => {
                const el = document.getElementById(elId);
                if (!el) return;
                let count = 0;
                unreadRows.forEach(row => {
                    if (types.includes(row.dataset.category)) count++;
                });
                el.textContent = count;
            });
        }

        updateCounts();

        // ── Click a row → mark it read, then navigate ────────────────
        allRows.forEach(row => {
            const url = row.dataset.url;
            if (!url) return; // no destination for this type (e.g. system)

            row.addEventListener('click', () => {
                const markReadBtn = row.querySelector('.notif-action-link');
                if (markReadBtn) {
                    row.classList.remove('unread');
                    row.classList.add('read');
                    markReadBtn.remove();
                    updateCounts();

                    fetch(`/admin_acc/notifications/${row.dataset.id}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        }
                    }).catch(() => {});
                }
                window.location.href = url;
            });
        });

        // ── Mark single as read ─────────────────────────────────────
        function markSingleRead(id, btn) {
            const row = btn.closest('.notif-row');
            row.classList.remove('unread');
            row.classList.add('read');
            btn.remove();
            updateCounts();

            fetch(`/admin_acc/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            }).catch(() => {});
        }

        // ── Mark all as read ────────────────────────────────────────
        document.getElementById('markAllReadBtn').addEventListener('click', function () {
            document.querySelectorAll('.notif-row.unread').forEach(row => {
                row.classList.remove('unread');
                row.classList.add('read');
                row.querySelector('.notif-action-link')?.remove();
            });
            updateCounts();

            fetch('/admin_acc/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            }).catch(() => {});
        });
    </script>
</body>
</html>