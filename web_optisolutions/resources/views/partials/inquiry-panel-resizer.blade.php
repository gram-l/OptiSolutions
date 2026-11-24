{{-- Drag handle between the conversation list and the chat panel (Chatbot Inquiries).
     Shared by the admin and staff pages. Desktop only; hidden on mobile where the
     list and chat are shown one at a time. Width is remembered per browser. --}}
<style>
    .inquiries-container > .chat-resizer {
        flex: 0 0 0.9rem;
        width: 0.9rem;
        margin: 0 -0.9rem;
        position: relative;
        z-index: 2;
        cursor: col-resize;
        touch-action: none;
        user-select: none;
        outline: none;
    }
    .inquiries-container > .chat-resizer::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 4px;
        height: 48px;
        transform: translate(-50%, -50%);
        border-radius: 4px;
        background: #b8c4d0;
        transition: background .15s ease, height .15s ease;
    }
    .inquiries-container > .chat-resizer:hover::before,
    .inquiries-container > .chat-resizer:focus-visible::before,
    .inquiries-container > .chat-resizer.dragging::before { background: #0e62aa; height: 72px; }
    body.dark-mode .inquiries-container > .chat-resizer::before { background: #4a5a70; }
    body.dark-mode .inquiries-container > .chat-resizer:hover::before,
    body.dark-mode .inquiries-container > .chat-resizer.dragging::before { background: #6fb1ea; }
    body.chat-resizing, body.chat-resizing * { cursor: col-resize !important; user-select: none !important; }
    /* ---- Responsive conversation list (admin + staff) ----
       The list reacts to ITS OWN width, so it stays uncrowded whether it is
       dragged narrow or sits in a small window. */
    .inquiries-container .chat-list { container-type: inline-size; container-name: chatlist; }
    @media (min-width: 769px) {
        .inquiries-container > .chat-list { flex: 0 0 clamp(16rem, 30%, 23rem); }
    }
    .inquiries-container .chat-info { grid-template-areas: "name time" "code status"; }
    .inquiries-container .chat-name-text { grid-area: name; }
    .inquiries-container .chat-time { grid-area: time; }
    .inquiries-container .chat-code { grid-area: code; }
    .inquiries-container .chat-status-label { grid-area: status; }
    .inquiries-container .chat-item-button { align-items: center; padding-block: 0.85rem; }

    /* Narrow list: give the name its own full-width line, then ID + date, then status. */
    @container chatlist (max-width: 21rem) {
        .inquiries-container .chat-info {
            grid-template-columns: minmax(0, 1fr) auto;
            grid-template-areas: "name name" "code time" "status status";
            row-gap: 0.2rem;
        }
        .inquiries-container .chat-info :is(.chat-time, .chat-status-label) { justify-self: start; }
        .inquiries-container .chat-info .chat-time { justify-self: end; }
        .inquiries-container .chat-avatar { width: 2rem; height: 2rem; font-size: 0.78rem; }
        .inquiries-container .chat-item-button { gap: 0.55rem; padding-inline: 0.6rem; }
    }
    /* Very narrow list: drop the avatar so text keeps the room. */
    @container chatlist (max-width: 16.5rem) {
        .inquiries-container .chat-avatar { display: none; }
    }
    @container chatlist (max-width: 19rem) {
        .inquiries-container .chat-list-header-top { gap: 0.4rem; }
    }
    @media (max-width: 768px) { .inquiries-container > .chat-resizer { display: none; } }
    /* ---- Desktop: "Select a conversation" placeholder + full-screen conversation ----
       Nothing is auto-opened. Clicking a conversation hides the list and the page title so
       the whole thread fills the page (sidebar and top header stay). The back arrow, or Esc,
       returns to the list. Mobile keeps its own list/conversation swap. */
    @media (min-width: 769px) {
        .inquiries-container > .chat-conversation:not(.mobile-active) { display: none; }
        .inquiries-container > .chat-placeholder {
            flex: 1 1 0; min-width: 0; display: flex; align-items: center; justify-content: center;
            padding: 2rem; text-align: center; color: var(--inquiry-muted, #6b7a8a); font-size: 1rem;
            border: 1px solid var(--inquiry-border, #dce2e8); border-radius: 14px; background: var(--inquiry-surface, #fff);
        }
        /* conversation open: only the thread (plus sidebar + header) is visible */
        body:has(#conversationArea.mobile-active) :is(.page-header, .inq-page-header) { display: none; }
        .inquiries-container:has(#conversationArea.mobile-active) > :is(.chat-list, .chat-resizer, .chat-placeholder) { display: none; }
        /* Breathing room under the whole inquiries area (list view and open conversation alike),
           so nothing sits on the bottom edge of the screen. */
        .inquiries-container { padding-bottom: 2rem; }
        /* The open thread uses the full page width. */
        .inquiries-container > .chat-conversation.mobile-active {
            display: flex; flex: 1 1 100%; width: 100%; max-width: none; min-height: 0;
            margin: 0; border-radius: 14px; box-shadow: 0 2px 14px rgba(15, 40, 70, 0.07);
        }
        .inquiries-container > .chat-conversation.mobile-active .chat-conversation-header { padding: 1rem 1.5rem; }
        .inquiries-container > .chat-conversation.mobile-active .patient-details h3 { font-size: 1.15rem; }
        .inquiries-container > .chat-conversation.mobile-active .messages-area { padding: 1.5rem 1.75rem; gap: 1.1rem; }
        .inquiries-container > .chat-conversation.mobile-active .message-day {
            align-self: center; padding: 0.2rem 0.85rem; border-radius: 999px; font-weight: 600; letter-spacing: 0.02em;
            background: color-mix(in srgb, var(--inquiry-border, #dce2e8) 65%, transparent);
        }
        .inquiries-container > .chat-conversation.mobile-active .message-content { max-width: min(75%, 34rem); }
        .inquiries-container > .chat-conversation.mobile-active .message-bubble { padding: 0.6rem 0.9rem; box-shadow: 0 1px 2px rgba(15, 40, 70, 0.05); }
        .inquiries-container > .chat-conversation.mobile-active .message-text { font-size: 0.92rem; }
        .inquiries-container > .chat-conversation.mobile-active .reply-area { gap: 0.75rem; padding: 1rem 1.5rem 1.35rem; background: var(--inquiry-surface, #fff); border-radius: 0 0 14px 14px; }
        .inquiries-container > .chat-conversation.mobile-active .reply-input { padding: 0.75rem 1rem; border-radius: 10px; }
        .inquiries-container > .chat-conversation.mobile-active .send-btn { padding: 0.75rem 1.25rem; border-radius: 10px; }
        .inquiries-container > .chat-conversation.mobile-active .back-to-list-btn { display: flex; }
    }
    @media (max-width: 768px) { .inquiries-container > .chat-placeholder { display: none !important; } }
    /* Back arrow: a small "Back" label appears instantly on hover / keyboard focus */
    .inquiries-container .back-to-list-btn { position: relative; z-index: 3; }
    .inquiries-container .back-to-list-btn::after {
        content: "Back"; position: absolute; top: calc(100% + 8px); left: 50%; transform: translateX(-50%);
        padding: 0.3rem 0.7rem; border-radius: 6px; background: #fff; border: 1px solid #dfe5ec;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.14); color: #12395c; font-size: 0.78rem; font-weight: 600; line-height: 1.2;
        white-space: nowrap; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .1s ease;
    }
    .inquiries-container .back-to-list-btn:focus-visible::after { opacity: 1; visibility: visible; }
    @media (hover: hover) { .inquiries-container .back-to-list-btn:hover::after { opacity: 1; visibility: visible; } }
    body.dark-mode .inquiries-container .back-to-list-btn::after { background: #1a2232; border-color: #344155; color: #e7edf5; }
</style>
<script>
(function () {
    const KEY = 'inquiryListWidth';
    const MIN_LIST = 240;     // keep the list readable (15rem)
    const MIN_CHAT = 360;     // keep the chat panel usable
    const mobile = window.matchMedia('(max-width: 768px)');

    function init() {
        const box = document.querySelector('.inquiries-container');
        const list = box && box.querySelector('.chat-list');
        if (!box || !list || box.querySelector('.chat-resizer')) return;

        const bar = document.createElement('div');
        bar.className = 'chat-resizer';
        bar.setAttribute('role', 'separator');
        bar.setAttribute('aria-orientation', 'vertical');
        bar.setAttribute('aria-label', 'Resize conversation list');
        bar.title = 'Drag to resize · double-click to reset';
        bar.tabIndex = 0;
        list.after(bar);

        const maxWidth = () => Math.max(MIN_LIST, box.clientWidth - MIN_CHAT);
        const clamp = w => Math.min(maxWidth(), Math.max(MIN_LIST, w));

        function apply(w) {
            if (mobile.matches) { list.style.flex = ''; return; }
            w = clamp(w);
            list.style.flex = '0 0 ' + w + 'px';
            bar.setAttribute('aria-valuenow', Math.round(w));
            bar.setAttribute('aria-valuemin', MIN_LIST);
            bar.setAttribute('aria-valuemax', Math.round(maxWidth()));
            return w;
        }
        function save(w) { try { localStorage.setItem(KEY, String(Math.round(w))); } catch (e) {} }
        function load() { try { return parseInt(localStorage.getItem(KEY), 10) || null; } catch (e) { return null; } }
        function reset() { list.style.flex = ''; try { localStorage.removeItem(KEY); } catch (e) {} }

        let saved = load();
        if (saved) apply(saved);

        let startX = 0, startW = 0;
        bar.addEventListener('pointerdown', function (e) {
            if (mobile.matches) return;
            startX = e.clientX;
            startW = list.getBoundingClientRect().width;
            bar.setPointerCapture(e.pointerId);
            bar.classList.add('dragging');
            document.body.classList.add('chat-resizing');
            e.preventDefault();
        });
        bar.addEventListener('pointermove', function (e) {
            if (!bar.classList.contains('dragging')) return;
            saved = apply(startW + (e.clientX - startX));
        });
        function stop(e) {
            if (!bar.classList.contains('dragging')) return;
            bar.classList.remove('dragging');
            document.body.classList.remove('chat-resizing');
            try { bar.releasePointerCapture(e.pointerId); } catch (err) {}
            if (saved) save(saved);
        }
        bar.addEventListener('pointerup', stop);
        bar.addEventListener('pointercancel', stop);
        bar.addEventListener('dblclick', function () { saved = null; reset(); });

        bar.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            const w = list.getBoundingClientRect().width + (e.key === 'ArrowRight' ? 24 : -24);
            saved = apply(w);
            if (saved) save(saved);
            e.preventDefault();
        });

        // Keep the width valid when the window changes size or crosses the mobile breakpoint.
        window.addEventListener('resize', function () {
            if (mobile.matches) { list.style.flex = ''; return; }
            if (saved) apply(saved);
        });
        mobile.addEventListener('change', function () {
            if (mobile.matches) list.style.flex = ''; else if (saved) apply(saved);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
(() => {
    const mobile = window.matchMedia('(max-width: 768px)');
    function init() {
        const box = document.querySelector('.inquiries-container');
        const area = document.getElementById('conversationArea');
        if (!box || !area || box.querySelector('.chat-placeholder')) return;

        // "Select a conversation" placeholder shown in the right pane when nothing is open
        const ph = document.createElement('div');
        ph.className = 'chat-placeholder';
        ph.textContent = 'Select a conversation to view messages.';
        area.after(ph);

        let lastTrigger = null;
        document.getElementById('chatList')?.addEventListener('click', e => {
            lastTrigger = e.target.closest('.chat-item-button') || lastTrigger;
        }, true);

        // Going back to the list: stop "typing" signal, forget the open chat and clear the highlight.
        function afterBack() {
            try { if (typeof stopTypingHeartbeat === 'function') stopTypingHeartbeat(); } catch (e) {}
            try { currentChatId = null; } catch (e) {}
            document.querySelectorAll('#chatList .chat-item.active').forEach(li => {
                li.classList.remove('active');
                li.querySelector('[aria-current]')?.removeAttribute('aria-current');
            });
            if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus();
        }
        document.getElementById('backToListBtn')?.addEventListener('click', () => { if (!mobile.matches) afterBack(); });
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape' || mobile.matches || !area.classList.contains('mobile-active')) return;
            if (document.querySelector('.visit-menu[open]') || document.querySelector('.modal-overlay[style*="flex"], .modal-overlay.show, .modal-overlay.open')) return;
            area.classList.remove('mobile-active');
            afterBack();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>