{{-- Admin "Manage" lists (User Management table, Manage Doctors cards):
     Edit / Deactivate / Activate keep their original coloured look (with icons).
     Delete / Remove is always red (soft red pill), darker red on hover. --}}
<style>
    .users-table .action-buttons .btn-delete,
    .doctor-card .card-actions .btn-remove { background: #fdeaea; color: #e74c3c; font-weight: 600; box-shadow: none; }
    .users-table .action-buttons .btn-delete:hover,
    .doctor-card .card-actions .btn-remove:hover { background: #fbd8d8; color: #c0392b; }
    body.dark-mode .users-table .action-buttons .btn-delete,
    body.dark-mode .doctor-card .card-actions .btn-remove { background: #4a2831 !important; color: #ffb9c1 !important; }
    body.dark-mode .users-table .action-buttons .btn-delete:hover,
    body.dark-mode .doctor-card .card-actions .btn-remove:hover { background: #5a313c !important; color: #ffd0d5 !important; }
</style>