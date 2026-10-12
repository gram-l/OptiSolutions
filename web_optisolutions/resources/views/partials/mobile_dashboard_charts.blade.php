<style>
/* Desktop inquiry-status chart uses its card's available space rather
   than the small side-by-side service-distribution layout. */
@media screen and (min-width: 769px) {
    :is(.admin-dashboard, .dash-page) .inquiry-status-card {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) .inquiry-status-card .donut-layout {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 1.25rem;
        padding: 0.5rem 0;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) .inquiry-status-card .donut-chart-wrap {
        width: min(100%, 340px);
        max-width: 100%;
        flex-shrink: 0;
    }
    :is(.admin-dashboard, .dash-page) .inquiry-status-card .donut-legend {
        flex: 0 0 auto;
        width: min(100%, 340px);
    }
    :is(.admin-dashboard, .dash-page) .inquiry-status-card .donut-legend-row { gap: 1rem; }
}
@media screen and (max-width: 768px) {
    :is(.admin-dashboard, .dash-page) {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) :is(.charts-grid, .charts-section) {
        grid-template-columns: minmax(0, 1fr);
        min-width: 0;
        gap: 1rem;
    }
    :is(.admin-dashboard, .dash-page) .chart-card {
        min-width: 0;
        max-width: 100%;
        padding: 1rem;
        box-sizing: border-box;
    }
    :is(.admin-dashboard, .dash-page) .chart-card canvas {
        max-width: 100%;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) .chart-header {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    :is(.admin-dashboard, .dash-page) .chart-header-title {
        min-width: 0;
        max-width: 100%;
    }
    :is(.admin-dashboard, .dash-page) .chart-header h3 {
        font-size: 1rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }
    :is(.admin-dashboard, .dash-page) .chart-subtitle {
        margin-left: 0;
        line-height: 1.45;
    }
    :is(.admin-dashboard, .dash-page) .donut-layout {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) .donut-chart-wrap {
        width: min(200px, 100%);
        max-width: 100%;
        margin: 0 auto;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) .donut-legend {
        width: 100%;
        min-width: 0;
    }
    :is(.admin-dashboard, .dash-page) .donut-legend-row {
        gap: 0.75rem;
        align-items: flex-start;
    }
    :is(.admin-dashboard, .dash-page) .donut-legend-label {
        flex: 1;
        min-width: 0;
        overflow-wrap: anywhere;
    }
    :is(.admin-dashboard, .dash-page) .donut-legend-dot { margin-top: 0.3rem; }
    :is(.admin-dashboard, .dash-page) .donut-legend-value {
        flex-shrink: 0;
        white-space: nowrap;
    }
    :is(.admin-dashboard, .dash-page) .sentiment-row-inner {
        display: grid;
        grid-template-columns: 26px 62px minmax(0, 1fr) 36px;
        gap: 0.45rem;
    }
    :is(.admin-dashboard, .dash-page) .sentiment-avatar { width: 26px; height: 26px; }
    :is(.admin-dashboard, .dash-page) .sentiment-label { width: auto; font-size: 0.8rem; }
    :is(.admin-dashboard, .dash-page) .bar-container { min-width: 0; width: 100%; }
    :is(.admin-dashboard, .dash-page) .sentiment-value { width: auto; font-size: 0.8rem; }
}
</style>
