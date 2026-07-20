 @extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <h3 style="margin: 0;">Scheduled Visits</h3>

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
                <tr>
                    <td>{{ $apt->visit_id }}</td>
                    <td>{{ $apt->doctor_name }}</td>
                    <td>{{ $apt->service }}</td>
                    <td>{{ \Carbon\Carbon::parse($apt->visit_date)->format('Y-m-d') }}</td>
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
    function filterVisitsTable() {
        const input = document.getElementById('visitSearch');
        const filter = input.value.trim().toLowerCase();
        const table = document.getElementById('visitsTable');
        if (!table) return;

        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const rowText = rows[i].textContent.toLowerCase();
            const isMatch = rowText.includes(filter);
            rows[i].style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        }

        const noResultsMsg = document.getElementById('noResultsMsg');
        if (noResultsMsg) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }
</script>
@endsection