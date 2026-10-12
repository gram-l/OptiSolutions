<style>
    /* Consistent spacing for Admin pages, including page styles loaded later. */
    body > .container { margin-top: 1.25rem; margin-bottom: 1.25rem; }
    body .container .page-header { margin-bottom: 1rem; }
    body .container .page-header p { margin-top: 0.3rem; line-height: 1.45; }
    body .container .stats-grid,
    body .container .stats-bar,
    body .container .analytics-grid { gap: 0.8rem; margin-bottom: 1rem; }
    body .container .stats-grid > .stat-card,
    body .container .stats-bar > .stat-card { padding: 0.75rem 1rem; }
    body .container .stats-grid .stat-number,
    body .container .stats-bar .stat-number { line-height: 1.2; }
    body .container .stats-grid .stat-label,
    body .container .stats-bar .stat-label { margin-top: 0.25rem; line-height: 1.35; }
    body .container .welcome-banner { margin-bottom: 1rem; }
    body .container .filter-bar,
    body .container .action-bar { padding: 0.8rem 1rem; gap: 0.75rem; margin-bottom: 1rem; }
    body .container .day-nav { flex-wrap: wrap; gap: 0.5rem; }

    @media (min-width: 769px) {
        body > .container { margin-top: 0; }
        body > .container > .sidebar { margin-top: 0; }
        body > .container > :not(.sidebar):not(.sidebar-overlay):not(script) {
            margin-top: 1.25rem;
        }
    }

    @media (max-width: 768px) {
        body > .container { margin-top: 1rem; }
        body .container .page-header { margin-bottom: 0.85rem; }
        body .container .stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; }
        body .container .stats-grid > .stat-card,
        body .container .stats-bar > .stat-card { padding: 0.65rem 0.45rem; min-width: 0; }
        body .container .stats-grid .stat-number { font-size: 1.4rem; }
        body .container .stats-grid .stat-label { font-size: 0.68rem; overflow-wrap: anywhere; }
        body .container .filter-bar,
        body .container .action-bar { padding: 0.75rem; gap: 0.6rem; }
        body .container .filter-group:has(.tab-btn) {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
            width: 100%;
        }
        body .container .filter-group .tab-btn { justify-content: center; padding: 0.6rem 0.7rem; }
        body .container .day-nav { max-width: 100%; min-width: 0; }
    }
</style>
