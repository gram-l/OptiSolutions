@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0;">
            <i class="bi bi-calendar-plus"></i> Scheduled Visits
        </h3>
        <p class="page-subtitle">Review and manage scheduled patient visits</p>
    </div>

    @php
        $services = $appointments->pluck('service')->filter()->unique()->sort()->values();
    @endphp

    <div class="visits-filter-bar">
        <select id="serviceFilter" onchange="filterVisitsTable()">
            <option value="">All Services</option>
            @foreach($services as $service)
                <option value="{{ strtolower($service) }}">{{ $service }}</option>
            @endforeach
        </select>

        <div class="filter-bar-right">
            <div class="date-nav">
                <button type="button" class="date-nav-btn" onclick="shiftDate(-1)" aria-label="Previous day">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <input type="date" id="visitDate" onchange="handleDateInputChange()">
                <button type="button" class="date-nav-btn" onclick="shiftDate(1)" aria-label="Next day">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" id="btnTodayFilter" class="btn-sm btn-primary" onclick="setTodayFilter()">Today</button>
                <button type="button" id="btnClearFilter" class="btn-sm btn-secondary" onclick="clearVisitFilters()">Clear</button>
                <span id="dateLabel" class="date-label"></span>
            </div>

            <div class="download-wrap" style="position: relative;">
                <button
                    type="button"
                    id="downloadBtn"
                    onclick="toggleExportMenu()"
                    class="btn-primary"
                    style="display: flex; align-items: center; gap: 6px; padding: 0.55rem 1rem; border: none; border-radius: 8px; font-size: 0.9rem; cursor: pointer;"
                >
                    <i class="bi bi-download"></i> Download
                </button>

                <div
                    id="exportMenu"
                    class="dropdown-menu"
                    style="right: 0; left: auto; top: calc(100% + 6px); width: 200px; padding: 0.5rem 0;"
                >
                    <a href="{{ route('staff.visits.export', 'csv') }}" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-csv"></i> Export as CSV
                    </a>
                    <a href="{{ route('staff.visits.export', 'xlsx') }}" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-xlsx"></i> Export as Excel
                    </a>
                    <a href="{{ route('staff.visits.export', 'pdf') }}" target="_blank" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-pdf"></i> Export as PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($appointments->count() > 0)
        <table class="data-table" id="visitsTable">
            <thead>
                <tr>
                    <th>Visit ID</th>
                    <th>Doctor</th>
                    <th>Service</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $apt)
                @php $visitDate = \Carbon\Carbon::parse($apt->visit_date)->format('Y-m-d'); @endphp
                <tr data-date="{{ $visitDate }}" data-service="{{ strtolower($apt->service ?? '') }}">
                    <td>{{ $apt->visit_id }}</td>
                    <td>{{ $apt->doctor_name }}</td>
                    <td>{{ $apt->service }}</td>
                    <td>{{ $visitDate }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p id="noResultsMsg" style="display: none; padding: 2rem; text-align: center; background: white; border-radius: 20px;">No visits scheduled for this day.</p>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No appointments found.</p>
    @endif
</div>

<script>
    function toggleExportMenu() {
        document.getElementById('exportMenu').classList.toggle('show');
    }

    document.addEventListener('click', function (e) {
        const menu = document.getElementById('exportMenu');
        const btn = document.getElementById('downloadBtn');
        if (menu && !menu.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            menu.classList.remove('show');
        }
    });

    function todayISO() {
        const now = new Date();
        const offset = now.getTimezoneOffset();
        const local = new Date(now.getTime() - offset * 60000);
        return local.toISOString().slice(0, 10);
    }

    function parseDateInputValue(str) {
        const [y, m, d] = str.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function toISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function formatDateLabel(dateStr) {
        const d = parseDateInputValue(dateStr);
        return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function updateDateLabel() {
        const input = document.getElementById('visitDate');
        const label = document.getElementById('dateLabel');
        label.textContent = input.value ? formatDateLabel(input.value) : 'Showing all scheduled visits';
    }

    function updateTodayButtonState() {
        const input = document.getElementById('visitDate');
        const isToday = input.value === todayISO();
        const btn = document.getElementById('btnTodayFilter');
        btn.classList.toggle('btn-primary', isToday);
        btn.classList.toggle('btn-secondary', !isToday);
    }

    function setTodayFilter() {
        document.getElementById('visitDate').value = todayISO();
        handleDateInputChange();
    }

    function clearVisitFilters() {
        document.getElementById('visitDate').value = '';
        document.getElementById('serviceFilter').value = '';
        handleDateInputChange();
    }


    function shiftDate(days) {
        const input = document.getElementById('visitDate');
        const base = input.value ? parseDateInputValue(input.value) : new Date();
        base.setDate(base.getDate() + days);
        input.value = toISO(base);
        handleDateInputChange();
    }

    function handleDateInputChange() {
        updateDateLabel();
        updateTodayButtonState();
        filterVisitsTable();
    }

    function filterVisitsTable() {
        const selectedDate = document.getElementById('visitDate').value;
        const serviceFilter = document.getElementById('serviceFilter').value;

        const table = document.getElementById('visitsTable');
        if (!table) return;

        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowDate = row.getAttribute('data-date');
            const rowService = row.getAttribute('data-service') || '';


            const matchesDate = !selectedDate || rowDate === selectedDate;
            const matchesService = serviceFilter === '' || rowService === serviceFilter;

            const isMatch = matchesDate && matchesService;
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        }

        const noResultsMsg = document.getElementById('noResultsMsg');
        if (noResultsMsg) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }


    updateDateLabel();
    updateTodayButtonState();
    filterVisitsTable();
</script>
@endsection