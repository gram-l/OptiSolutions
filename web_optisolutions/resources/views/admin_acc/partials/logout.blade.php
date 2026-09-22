{{--
    Logout confirmation overlay. Included once from admin_acc.header, same
    pattern as admin_acc.partials.profile and admin_acc.partials.settings.

    Starts hidden and is toggled by openLogoutModal() / closeLogoutModal(),
    which are global so both the header's avatar dropdown and the settings
    modal's "Logout" button can trigger the same overlay instead of each
    having its own confirm().
--}}
<div class="logout-modal-overlay" id="logoutModalOverlay" style="display:none;">
    <div class="logout-modal">
        <div class="logout-modal-icon"><i class="bi bi-box-arrow-right"></i></div>
        <h3>Log out?</h3>
        <p>Are you sure you want to log out?</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-modal-btn cancel" id="logoutCancelBtn">Cancel</button>
            <button type="button" class="logout-modal-btn confirm" id="logoutConfirmBtn">Log out</button>
        </div>
    </div>
</div>

{{-- Real logout request — submitted (not fetch'd) so the browser fully
     navigates to the redirect target instead of just reading it as a
     response body. Route name matches LoginController@logout in web.php. --}}
<form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display:none;">
    @csrf
</form>

<style>
    .logout-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(6, 39, 68, 0.45);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .logout-modal {
        background: var(--white);
        border-radius: 14px;
        padding: 1.75rem;
        width: 320px;
        max-width: 90vw;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    .logout-modal-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #fdecea;
        color: #e74c3c;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin: 0 auto 0.85rem;
    }
    .logout-modal h3 {
        margin: 0 0 0.4rem;
        font-size: 1.1rem;
        color: var(--text-dark);
    }
    .logout-modal p {
        margin: 0 0 1.25rem;
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    .logout-modal-actions {
        display: flex;
        gap: 0.75rem;
    }
    .logout-modal-btn {
        flex: 1;
        padding: 0.6rem 0;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        font-family: inherit;
    }
    .logout-modal-btn.cancel {
        background: var(--light-gray);
        color: var(--text-dark);
    }
    .logout-modal-btn.cancel:hover {
        background: #dfe4e8;
    }
    .logout-modal-btn.confirm {
        background: #e74c3c;
        color: var(--white);
    }
    .logout-modal-btn.confirm:hover {
        background: #c0392b;
    }
</style>

<script>
    // Global so any page/partial (header dropdown, settings modal, etc.)
    // can open the same confirmation instead of each rolling its own.
    function openLogoutModal() {
        document.getElementById('settingsOverlay')?.style.setProperty('display', 'none');
        document.getElementById('avatarDropdown')?.classList.remove('open');
        document.getElementById('logoutModalOverlay').style.display = 'flex';
    }

    function closeLogoutModal() {
        // Cancel simply hides the overlay — no history/navigation call of
        // any kind, so the admin stays exactly where they were.
        document.getElementById('logoutModalOverlay').style.display = 'none';
    }

    document.getElementById('logoutCancelBtn').addEventListener('click', closeLogoutModal);

    document.getElementById('logoutModalOverlay').addEventListener('click', (e) => {
        if (e.target.id === 'logoutModalOverlay') closeLogoutModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLogoutModal();
    });

    document.getElementById('logoutConfirmBtn').addEventListener('click', () => {
        // Real POST /logout via form submit so the browser fully navigates
        // to the redirect target (a fetch() would just follow the redirect
        // internally and hand back HTML without actually leaving the page).
        document.getElementById('logoutForm').submit();
    });
</script>