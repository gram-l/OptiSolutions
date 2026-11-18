<style>
/* Both dashboard donuts use the compact chart with its legend beside it. */
@media screen and (max-width: 768px) {
    .portal-dashboard {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .portal-dashboard :is(.charts-grid, .charts-section) {
        grid-template-columns: minmax(0, 1fr);
        min-width: 0;
        gap: var(--dashboard-card-gap, 1rem);
    }
    .portal-dashboard .chart-card {
        min-width: 0;
        max-width: 100%;
        padding: 0.8rem;
        box-sizing: border-box;
    }
    .portal-dashboard .chart-card canvas {
        max-width: 100%;
        min-width: 0;
    }
    .portal-dashboard .chart-header {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .portal-dashboard .chart-header-title {
        min-width: 0;
        max-width: 100%;
    }
    .portal-dashboard .chart-header h3 {
        font-size: 1rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }
    .portal-dashboard .chart-subtitle {
        margin-left: 0;
        line-height: 1.45;
    }
    /* Donut and legend sit side by side when there is room, and stack when not. */
    .portal-dashboard .donut-layout {
        flex-flow: row wrap;
        align-items: center;
        justify-content: center;
        gap: 0.6rem 1rem;
        min-width: 0;
    }
    .portal-dashboard .donut-chart-wrap {
        width: min(150px, 100%);
        max-width: 100%;
        margin: 0;
        min-width: 0;
    }
    .portal-dashboard .donut-legend {
        flex: 1 1 11rem;
        width: auto;
        min-width: 0;
    }
    .portal-dashboard .donut-legend-row {
        gap: 0.75rem;
        align-items: flex-start;
    }
    .portal-dashboard .donut-legend-label {
        flex: 1;
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .portal-dashboard .donut-legend-dot { margin-top: 0.3rem; }
    .portal-dashboard .donut-legend-value {
        flex-shrink: 0;
        white-space: nowrap;
    }
    .portal-dashboard .sentiment-row-inner {
        display: grid;
        grid-template-columns: 26px 62px minmax(0, 1fr) 36px;
        gap: 0.45rem;
    }
    .portal-dashboard .sentiment-avatar { width: 26px; height: 26px; }
    .portal-dashboard .sentiment-label { width: auto; font-size: 0.8rem; }
    .portal-dashboard .bar-container { min-width: 0; width: 100%; }
    .portal-dashboard .sentiment-value { width: auto; font-size: 0.8rem; }
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