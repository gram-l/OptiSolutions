<style>
    .visit-actions { display:flex; align-items:center; gap:.5rem; flex-wrap:nowrap; }
    .visit-menu { position:relative; display:inline-block; }
    .visit-menu summary { display:flex; align-items:center; justify-content:center; width:32px; height:34px; padding:0; border:0; border-radius:6px; background:transparent; color:#526477; cursor:pointer; list-style:none; font:700 24px/1 Arial,sans-serif; }
    .visit-menu summary::-webkit-details-marker { display:none; }
    .visit-menu summary:hover { background:#edf2f7; }
    .visit-menu summary:focus-visible { outline:2px solid #0e62aa; outline-offset:2px; }
    .visit-menu-items { position:fixed; z-index:1500; min-width:140px; padding:4px; border:1px solid #dbe3eb; border-radius:8px; background:#fff; box-shadow:0 4px 12px #0627441a; }
    .visit-menu:not([open]) .visit-menu-items { display:none; }
    .visit-menu-items button { display:block; width:100%; padding:9px 12px; border:0; border-radius:4px; background:transparent; color:#26384a; text-align:left; font:inherit; font-size:13px; cursor:pointer; white-space:nowrap; }
    .visit-menu-items button:hover, .visit-menu-items button:focus-visible { background:#edf2f7; }
    .visit-menu-items .visit-menu-delete { color:#c0392b; }
    body.dark-mode .visit-menu summary { color:#c8d5e5; }
    body.dark-mode .visit-menu-items { background:#1a2232; border-color:#45556e; }
    body.dark-mode .visit-menu-items button { color:#e7edf5; }
    body.dark-mode .visit-menu summary:hover, body.dark-mode .visit-menu-items button:hover, body.dark-mode .visit-menu-items button:focus-visible { background:#293b53; }
    body.dark-mode .visit-menu-items .visit-menu-delete { color:#ffb9c1; }
</style>
<script>
(() => {
    const closeMenus = except => document.querySelectorAll('.visit-menu[open]').forEach(menu => { if (menu !== except) menu.open = false; });
    document.addEventListener('toggle', event => {
        const menu = event.target;
        if (!menu.matches?.('.visit-menu') || !menu.open) return;
        closeMenus(menu);
        const trigger = menu.querySelector('summary').getBoundingClientRect();
        const items = menu.querySelector('.visit-menu-items');
        items.style.left = Math.max(8, Math.min(trigger.right - items.offsetWidth, innerWidth - items.offsetWidth - 8)) + 'px';
        items.style.top = Math.max(8, trigger.bottom + items.offsetHeight + 8 > innerHeight ? trigger.top - items.offsetHeight - 4 : trigger.bottom + 4) + 'px';
    }, true);
    document.addEventListener('click', event => {
        if (event.target.closest('.visit-menu-items button')) closeMenus();
        else if (!event.target.closest('.visit-menu')) closeMenus();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const menu = document.querySelector('.visit-menu[open]');
        if (menu) { menu.open = false; menu.querySelector('summary').focus(); }
    });
    document.addEventListener('scroll', () => closeMenus(), true);
    window.addEventListener('resize', () => closeMenus());
})();
</script>
