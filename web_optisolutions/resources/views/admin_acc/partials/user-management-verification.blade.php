<script type="application/json" id="userManagementVerificationData">@json($verification)</script>
<dialog id="userManagementGate" aria-labelledby="userManagementGateTitle" aria-describedby="userManagementGateDescription">
    <div class="um-gate-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
    <h3 id="userManagementGateTitle">Verify your identity</h3>
    <p id="userManagementGateDescription">Confirm that you are <strong>{{ $verification['admin_name'] }}</strong>. A one-time code will be sent to <strong>{{ $verification['masked_email'] }}</strong>.</p>
    <p class="um-gate-note"><i class="fa-regular fa-clock" aria-hidden="true"></i><span>Once verified, you can use all User Management actions for 24 hours in this sign-in session.</span></p>
    <p id="userManagementGateError" role="alert" hidden></p>
    <p id="userManagementGateStatus" role="status" aria-live="polite"></p>
    <form id="userManagementVerifyForm" hidden>
        <label for="userManagementOtp">Email verification code</label>
        <input id="userManagementOtp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" required>
        <button type="submit" class="btn-save" id="userManagementVerifyCode">Verify &amp; Continue</button>
    </form>
    <div class="um-gate-actions">
        <button type="button" class="um-gate-back" id="userManagementCancelVerification">Cancel</button>
        <button type="button" class="btn-save" id="userManagementSendCode">Send OTP</button>
    </div>
</dialog>
<style>
    #userManagementGate { box-sizing:border-box; width:min(440px,calc(100% - 2rem)); max-height:90dvh; overflow:auto; margin:auto; border:0; border-radius:20px; padding:2rem 1.75rem 1.5rem; background:#fff; color:#062744; text-align:center; box-shadow:0 24px 60px #06274455; }
    #userManagementGate::backdrop { background:#06274488; backdrop-filter:blur(4px); }
    #userManagementGate .um-gate-icon { display:flex; align-items:center; justify-content:center; width:60px; height:60px; margin:0 auto 1rem; border-radius:50%; background:#e8f1fa; color:#0e62aa; font-size:1.6rem; }
    #userManagementGate h3 { margin:0 0 .6rem; font-size:1.3rem; }
    #userManagementGate p { line-height:1.6; font-size:.92rem; margin:.6rem 0; }
    #userManagementGate .um-gate-note { display:flex; align-items:flex-start; gap:.6rem; margin:1.1rem 0 1rem; padding:.75rem .9rem; border-radius:12px; background:#f1f6fb; color:#5f7385; font-size:.84rem; text-align:left; }
    #userManagementGate .um-gate-note i { margin-top:.2rem; color:#0e62aa; }
    #userManagementGateStatus:empty { display:none; }
    #userManagementGateStatus { color:#5f7385; font-size:.85rem; }
    #userManagementGateError { padding:.6rem .8rem; border-radius:10px; background:#fdeaea; color:#c0392b; font-size:.85rem; }
    #userManagementGate button { cursor:pointer; border:0; border-radius:12px; padding:.8rem 1.2rem; font:inherit; font-weight:600; transition:background .2s, box-shadow .2s; }
    #userManagementGate button:disabled { opacity:.6; cursor:wait; }
    #userManagementGate .btn-save { background:#0e62aa; color:#fff; box-shadow:0 3px 10px rgb(14 98 170 / 25%); }
    #userManagementGate .btn-save:hover:not(:disabled) { background:#0b4f8a; }
    #userManagementGate button:focus-visible, #userManagementGate input:focus-visible { outline:3px solid #0e62aa55; outline-offset:2px; }
    #userManagementVerifyForm { text-align:left; margin-top:1rem; }
    #userManagementVerifyForm label { display:block; font-size:.85rem; font-weight:600; }
    #userManagementVerifyForm .btn-save { width:100%; }
    #userManagementGate input { box-sizing:border-box; width:100%; border:1px solid #b7c9d8; border-radius:12px; padding:.8rem; font:inherit; font-size:1.3rem; font-weight:600; text-align:center; margin:.5rem 0 1rem; letter-spacing:.45em; }
    #userManagementGate input:focus { border-color:#0e62aa; outline:0; box-shadow:0 0 0 3px rgb(14 98 170 / 18%); }
    #userManagementGate .um-gate-actions { display:flex; gap:.75rem; margin-top:1.25rem; }
    #userManagementGate .um-gate-actions button { flex:1 1 0; }
    #userManagementGate .um-gate-actions .um-gate-back { order:-1; background:#edf0f2; color:#0e62aa; }
    #userManagementGate .um-gate-actions .um-gate-back:hover:not(:disabled) { background:#e0e6ea; }
    @media (max-width:420px) {
        #userManagementGate { padding:1.5rem 1.25rem 1.25rem; }
        #userManagementGate .um-gate-actions { flex-direction:column-reverse; }
    }
    body.dark-mode #userManagementGate .um-gate-icon, body.dark-mode #userManagementGate .um-gate-note { background:#222d3e; color:#8bc9ff; }
    body.dark-mode #userManagementGate .um-gate-note i { color:#8bc9ff; }
    body.dark-mode #userManagementGate input { background:#222d3e; border-color:#45556e; color:#e7edf5; }
</style>