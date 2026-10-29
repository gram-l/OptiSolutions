@if($chartFilter === 'inquiry')
    <select id="inquiry-period-filter" class="filter-btn dashboard-chart-filter" aria-label="Inquiry volume period">
        <option value="this_week" @selected(($inquiryPeriod ?? 'this_week') === 'this_week')>This Week</option>
        <option value="last_week" @selected(($inquiryPeriod ?? 'this_week') === 'last_week')>Last Week</option>
    </select>
@else
    <select id="visit-weeks-filter" class="filter-btn dashboard-chart-filter" aria-label="Patient visits period">
        @foreach([4, 6, 12] as $weeks)
            <option value="{{ $weeks }}" @selected((int)($visitWeeks ?? 6) === $weeks)>Last {{ $weeks }} Weeks</option>
        @endforeach
    </select>
@endif
