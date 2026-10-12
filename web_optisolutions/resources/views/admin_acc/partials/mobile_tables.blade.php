<style>
    /* A contents wrapper adds no layout box outside the mobile breakpoint. */
    .admin-mobile-value { display: contents; }

    @media screen and (max-width: 768px) {
        body .admin-mobile-table-wrap {
            overflow: visible;
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }
        body table.admin-mobile-table,
        body table.admin-mobile-table > tbody {
            display: block;
            width: 100%;
            min-width: 0;
            border-collapse: separate;
        }
        body table.admin-mobile-table > thead { display: none; }
        body table.admin-mobile-table > tbody > tr {
            display: block;
            margin-bottom: 1rem;
            padding: 0.9rem 1.1rem;
            border: 1px solid #ecf0f1;
            border-left: 4px solid #0e62aa;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        body table.admin-mobile-table > tbody > tr:last-child { margin-bottom: 0; }
        body table.admin-mobile-table > tbody > tr > td {
            display: flex;
            width: 100%;
            min-width: 0;
            max-width: none;
            align-items: baseline;
            gap: 0.6rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid #f1f3f5;
            white-space: normal;
            text-align: left;
        }
        body table.admin-mobile-table td[data-label]:not([data-label=""])::before {
            content: attr(data-label);
            display: block;
            flex: 0 0 108px;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #95a5a6;
        }
        body table.admin-mobile-table .admin-mobile-value {
            display: block;
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
        }
        body table.admin-mobile-table td.admin-mobile-actions {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
            border-bottom: none;
            border-top: 1px dashed #ecf0f1;
            margin-top: 0.25rem;
            padding-top: 0.85rem;
            padding-bottom: 0;
        }
        body table.admin-mobile-table td.admin-mobile-actions > .action-buttons {
            display: flex;
            width: 100%;
            flex-wrap: wrap;
            gap: 0.6rem;
        }
        body table.admin-mobile-table td.admin-mobile-actions button,
        body table.admin-mobile-table td.admin-mobile-actions form {
            flex: 1 1 auto;
        }
        body table.admin-mobile-table td.admin-mobile-actions button {
            width: auto;
            min-height: 34px;
            border-radius: 20px;
            justify-content: center;
        }
        body table.admin-mobile-table td.admin-mobile-actions form button { width: 100%; }
        body table.admin-mobile-table td[colspan] { justify-content: center; border: none; }
    }
</style>
<script>
(() => {
    const mobile = window.matchMedia('(max-width: 768px)');
    // Feedback, complaints, and user management deliberately excluded.
    const selector = '.appointments-table, .commands-table, .patients-table, .visit-history-table';
    let scheduled = false;

    function updateMobileTables() {
        scheduled = false;
        if (!mobile.matches) return;
        document.querySelectorAll(selector).forEach(table => {
            if (table.closest('#patientPdfTemplate')) return;
            const headings = Array.from(table.tHead?.rows[0]?.cells || []).map(th => th.textContent.trim());
            if (!headings.length) return;
            table.classList.add('admin-mobile-table');
            const parent = table.parentElement;
            if (parent.matches('.day-panel, .commands-table-container, .patients-table-container')) {
                parent.classList.add('admin-mobile-table-wrap');
            }
            Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
                Array.from(row.cells).forEach((cell, index) => {
                    if (cell.colSpan > 1) return;
                    const label = headings[index] || '';
                    const actions = !label || /^actions?$/i.test(label);
                    if (actions) {
                        cell.classList.add('admin-mobile-actions');
                        return;
                    }
                    if (!cell.hasAttribute('data-label')) cell.setAttribute('data-label', label);
                    if (!cell.querySelector(':scope > .admin-mobile-value')) {
                        const value = document.createElement('span');
                        value.className = 'admin-mobile-value';
                        while (cell.firstChild) value.appendChild(cell.firstChild);
                        cell.appendChild(value);
                    }
                });
            }));
        });
    }

    function scheduleUpdate() {
        if (!mobile.matches || scheduled) return;
        scheduled = true;
        requestAnimationFrame(updateMobileTables);
    }
    function init() {
        updateMobileTables();
        new MutationObserver(scheduleUpdate).observe(document.body, { childList: true, subtree: true });
        mobile.addEventListener('change', scheduleUpdate);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
