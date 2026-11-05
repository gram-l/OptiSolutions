<script type="application/json" id="userManagementVerificationData">@json($verification)</script>
<dialog id="userManagementGate" aria-labelledby="userManagementGateTitle" aria-describedby="userManagementGateDescription">
    <h3 id="userManagementGateTitle">Verify your identity</h3>
    <p id="userManagementGateDescription">Confirm that you are <strong>{{ $verification['admin_name'] }}</strong>. A one-time code will be sent to <strong>{{ $verification['masked_email'] }}</strong>.</p>
    <p class="um-gate-note">Once verified, you can use all User Management actions for 24 hours in this sign-in session.</p>
    <p id="userManagementGateError" role="alert" hidden></p>
    <p id="userManagementGateStatus" role="status" aria-live="polite"></p>
    <button type="button" class="btn-save" id="userManagementSendCode">Send OTP</button>
    <form id="userManagementVerifyForm" hidden>
        <label for="userManagementOtp">Email verification code</label>
        <input id="userManagementOtp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" required>
        <button type="submit" class="btn-save" id="userManagementVerifyCode">Verify &amp; Continue</button>
    </form>
    <button type="button" class="um-gate-back" id="userManagementCancelVerification">Cancel</button>
</dialog>
<style>
    #userManagementGate { box-sizing:border-box; width:min(440px,calc(100% - 2rem)); max-height:90dvh; overflow:auto; margin:auto; border:0; border-radius:18px; padding:1.75rem; color:#062744; box-shadow:0 20px 60px #06274444; }
    #userManagementGate::backdrop { background:#06274488; backdrop-filter:blur(4px); }
    #userManagementGate h3 { margin:0 0 1rem; }
    #userManagementGate p { line-height:1.6; font-size:.9rem; margin:.75rem 0; }
    #userManagementGate .um-gate-note { color:#64748b; }
    #userManagementGate button { cursor:pointer; border:0; border-radius:8px; padding:.75rem 1rem; font:inherit; font-weight:600; }
    #userManagementGate button:disabled { opacity:.6; cursor:wait; }
    #userManagementGate input { box-sizing:border-box; width:100%; border:1px solid #b7c9d8; border-radius:8px; padding:.75rem; font:inherit; margin:.5rem 0 1rem; letter-spacing:.2em; }
    #userManagementGateError { color:#c0392b; }
    .um-gate-back { display:block; margin-top:1.25rem; color:#0e62aa; font-size:.85rem; }
</style>
