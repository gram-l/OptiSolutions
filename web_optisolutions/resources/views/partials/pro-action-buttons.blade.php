{{-- Admin "Manage" lists (User Management table, Manage Doctors cards):
     Edit / Deactivate / Activate keep their original coloured look (with icons).
     Only Delete / Remove is toned down: muted text, no background, and it only
     turns red when hovered, so it doesn't draw attention. --}}
<style>
    .users-table .action-buttons .btn-delete,
    .doctor-card .card-actions .btn-remove { background: transparent; color: #8a94a0; font-weight: 500; box-shadow: none; }
    .users-table .action-buttons .btn-delete:hover,
    .doctor-card .card-actions .btn-remove:hover { background: #fdf3f3; color: #b42318; }
    body.dark-mode .users-table .action-buttons .btn-delete,
    body.dark-mode .doctor-card .card-actions .btn-remove { background: transparent !important; color: #98a6b8 !important; }
    body.dark-mode .users-table .action-buttons .btn-delete:hover,
    body.dark-mode .doctor-card .card-actions .btn-remove:hover { background: #3a2530 !important; color: #ffb9c1 !important; }
</style>