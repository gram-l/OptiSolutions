@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <h3 style="margin: 0;">Scheduled Visits</h3>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="dateFrom" style="font-size: 0.85rem; color: #64748b; white-space: nowrap;">From</label>
                <input
                    type="date"
                    id="dateFrom"
                    style="padding: 0.5rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; outline: none;"
                    onchange="filterVisitsTable()"
                >
                <label for="dateTo" style="font-size: 0.85rem; color: #64748b; white-space: nowrap;">To</label>
                <input
                    type="date"
                    id="dateTo"
                    style="padding: 0.5rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; outline: none;"
                    onchange="filterVisitsTable()"
                >
                <button
                    type="button"
                    onclick="clearVisitFilters()"
                    class="btn-sm btn-primary"
                    style="padding: 0.5rem 1.2rem; font-size: 0.85rem; line-height: 1.2; border: none; box-sizing: border-box; cursor: pointer;"
                >Clear</button>
            </div>

            <div style="position: relative; min-width: 260px;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input
                    type="text"
                    id="visitSearch"
                    placeholder="Search visit ID, doctor, service ..."
                    style="width: 100%; padding: 0.55rem 0.75rem 0.55rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none;"
                    onkeyup="filterVisitsTable()"
                >
            </div>

            <div style="position: relative;">
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
                <tr data-date="{{ $visitDate }}">
                    <td>{{ $apt->visit_id }}</td>
                    <td>{{ $apt->doctor_name }}</td>
                    <td>{{ $apt->service }}</td>
                    <td>{{ $visitDate }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p id="noResultsMsg" style="display: none; padding: 2rem; text-align: center; background: white; border-radius: 20px;">No matching visits found.</p>
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

    function filterVisitsTable() {
        const searchInput = document.getElementById('visitSearch');
        const filter = searchInput.value.trim().toLowerCase();

        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;

        const table = document.getElementById('visitsTable');
        if (!table) return;

        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowText = row.textContent.toLowerCase();
            const rowDate = row.getAttribute('data-date');

            const matchesSearch = rowText.includes(filter);
            const matchesFrom = !dateFrom || rowDate >= dateFrom;
            const matchesTo = !dateTo || rowDate <= dateTo;

            const isMatch = matchesSearch && matchesFrom && matchesTo;
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        }

        const noResultsMsg = document.getElementById('noResultsMsg');
        if (noResultsMsg) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function clearVisitFilters() {
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        document.getElementById('visitSearch').value = '';
        filterVisitsTable();
    }
</script>
@endsection