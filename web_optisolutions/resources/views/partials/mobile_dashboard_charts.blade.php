<style>
/* Both dashboard donuts use the compact chart with its legend beside it. */
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
<script>
(() => {
    function updateChartTheme() {
        if (!window.Chart) return;
        const dark = document.body.classList.contains('dark-mode');
        const text = dark ? '#c8d5e5' : '#66727f';
        const grid = dark ? '#344155' : '#e5e7eb';
        Chart.defaults.color = text;
        document.querySelectorAll('canvas').forEach(canvas => {
            const chart = Chart.getChart(canvas);
            if (!chart) return;
            if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = text;
            Object.values(chart.options.scales || {}).forEach(scale => {
                if (scale.ticks) scale.ticks.color = text;
                if (scale.grid) scale.grid.color = grid;
                if (scale.border) scale.border.color = grid;
                if (scale.title) scale.title.color = text;
            });
            chart.update('none');
        });
    }
    document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(updateChartTheme));
    new MutationObserver(() => requestAnimationFrame(updateChartTheme)).observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
</script>
