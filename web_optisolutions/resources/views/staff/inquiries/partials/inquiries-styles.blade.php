<style>
    /* Admin "Chatbot Inquiries" look, scoped under .inq-admin so it cannot
       clash with the staff stylesheet (.status-badge, .modal-overlay, .toast…). */
    .inq-admin {
        --pending: #86530d;
        --resolved-green: #16733c;
        --inquiry-border: #dce2e8;
        --inquiry-muted: #526377;
        --inquiry-surface: #fff;
        --inquiry-thread: #f5f7fa;
        --light-gray: #ECF0F1;
        --primary-dark: #062744;
        display: flex;
        flex-direction: column;
        /* Fixed height so the list and messages scroll inside their panels
           (flex:1 on .inquiries-container would otherwise ignore a height set on it). */
        height: calc(100dvh - 125px);
        min-height: 520px;
    }
    .inq-admin .inq-page-header { margin-bottom: 1rem; }
    .inq-admin .inq-page-header h2 { font-size: 1.75rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.65rem; margin: 0; }
    .inq-admin .inq-page-header h2 span { display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; flex-shrink: 0; border-radius: 10px; background: rgba(14, 98, 170, 0.1); color: var(--primary-main); font-size: 1.05rem; }
    .inq-admin .inq-page-header p { color: var(--inquiry-muted); margin: 0.35rem 0 0; }
    body.dark-mode .inq-admin .inq-page-header h2 { color: #e7edf5; }
    .inq-admin .inq-page-header { position: relative; }
    .inq-admin .inq-page-header::after { content: ''; position: absolute; inset-inline: 0; inset-block-end: -0.45rem; height: 1px; background: #d1d5db; pointer-events: none; }
    body.dark-mode .inq-admin .inq-page-header::after { background: #4b5563; }
    .inq-admin ul { list-style: none; margin: 0; padding: 0; }
    @media (max-width: 768px) {
        .inq-admin .inq-page-header h2 { font-size: 1.4rem; gap: 0.55rem; }
        .inq-admin .inq-page-header h2 span { width: 1.9rem; height: 1.9rem; font-size: 0.9rem; border-radius: 8px; }
        .inq-admin .inq-page-header p { font-size: 0.85rem; }
    }
.inq-admin .inquiries-container {
            display: flex;
            gap: 0.9rem;
            flex: 1;
            min-height: 0;
        }
.inq-admin .chat-list, .inq-admin .chat-conversation {
            min-width: 0;
            border: 1px solid var(--inquiry-border);
            border-radius: 14px;
            background: var(--inquiry-surface);
        }
.inq-admin .chat-list {
            flex: 0 0 clamp(13rem, 27%, 20rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
.inq-admin .back-to-list-btn {
            display: none;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 50%;
            background: var(--light-gray);
            color: var(--primary-dark);
            font-size: 1rem;
            cursor: pointer;
            flex-shrink: 0;
        }
.inq-admin .back-to-list-btn:hover {
            background: #dfe4e8;
        }
.inq-admin .chat-list-header {
            padding: 0.75rem;
            border-bottom: 1px solid var(--inquiry-border);
        }
.inq-admin .chat-list-header-top {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
.inq-admin .inquiry-search {
            position: relative;
            flex: 1;
            min-width: 0;
        }
.inq-admin .inquiry-search i {
            position: absolute;
            top: 50%;
            left: 0.65rem;
            transform: translateY(-50%);
            color: var(--inquiry-muted);
            pointer-events: none;
        }
.inq-admin .select-mode-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: var(--light-gray);
            color: var(--primary-main);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            flex-shrink: 0;
            transition: background 0.15s ease, color 0.15s ease;
        }
.inq-admin .select-mode-btn:hover {
            background: #dfe4e8;
        }
.inq-admin .select-mode-btn.active {
            background: #e74c3c;
            color: var(--white);
        }
.inq-admin .selection-actions-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            margin-top: 0.7rem;
        }
.inq-admin .selection-actions-bar.visible {
            display: flex;
        }
.inq-admin .selection-count {
            font-size: 0.85rem;
            color: var(--text-dark);
            font-weight: 600;
        }
.inq-admin .selection-cancel-btn {
            background: none;
            border: none;
            color: #7f8c8d;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }
.inq-admin .selection-delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #e74c3c;
            color: var(--white);
            border: none;
            border-radius: 20px;
            padding: 0.45rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }
.inq-admin .selection-delete-btn:hover:not(:disabled) {
            opacity: 0.85;
        }
.inq-admin .selection-delete-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
.inq-admin .search-box {
            width: 100%;
            padding: 0.62rem 0.5rem 0.62rem 2rem;
            border: 1px solid var(--inquiry-border);
            border-radius: 8px;
            color: var(--text-dark);
            font: inherit;
            font-size: 0.82rem;
        }
.inq-admin .search-box:focus {
            border-color: var(--primary-main);
            box-shadow: 0 0 0 2px rgba(14, 98, 170, 0.2);
        }
.inq-admin .chat-items {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            list-style: none;
        }
.inq-admin .chat-item {
            border-bottom: 1px solid var(--inquiry-border);
            border-inline-start: 3px solid transparent;
        }
.inq-admin .chat-item:last-child { border-bottom: 0; }
.inq-admin .chat-item:hover { background: #f0f5fa; }
.inq-admin .chat-item.active { background: #dcebf9; border-inline-start-color: var(--primary-main); }
.inq-admin .chat-item.selected { background: #fcebed; border-inline-start-color: #b91c1c; }
.inq-admin .chat-item-button {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            width: 100%;
            min-width: 0;
            padding: 0.8rem 0.7rem;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            text-align: left;
            cursor: pointer;
        }
.inq-admin .chat-item-button:focus-visible {
            outline: 2px solid var(--primary-main);
            outline-offset: -3px;
        }
.inq-admin .chat-item-checkbox {
            flex-shrink: 0;
            align-self: center;
            color: var(--primary-main);
        }
.inq-admin .chat-avatar, .inq-admin .message-avatar {
            display: grid;
            place-items: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: #bed7e9;
            color: #123e61;
            font-size: 0.85rem;
            font-weight: 600;
            flex-shrink: 0;
        }
.inq-admin .chat-info {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: baseline;
            gap: 0.18rem 0.4rem;
            flex: 1;
            min-width: 0;
        }
.inq-admin .chat-name-text {
            font-weight: 600;
            color: var(--primary-dark);
        }
.inq-admin .chat-name-text, .inq-admin .chat-code {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
.inq-admin .chat-code, .inq-admin .chat-time, .inq-admin .chat-list-empty {
            color: var(--inquiry-muted);
            font-size: 0.78rem;
        }
.inq-admin .chat-time { flex-shrink: 0; font-weight: 400; }
.inq-admin .chat-list-empty { padding: 1.2rem; text-align: center; }
.inq-admin .chat-status-label, .inq-admin .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
.inq-admin .chat-status-label::before, .inq-admin .status-badge::before {
            content: '';
            width: 0.38rem;
            height: 0.38rem;
            border-radius: 50%;
            background: currentColor;
        }
.inq-admin .chat-status-label.resolved { color: var(--resolved-green); }
.inq-admin .chat-status-label.in-progress { color: var(--pending); }
.inq-admin .chat-info :is(.chat-time, .inq-admin .chat-status-label) { justify-self: end; }
.inq-admin .chat-unread {
            flex-shrink: 0;
            width: 0.5rem;
            height: 0.5rem;
            margin-top: 0.35rem;
            border-radius: 50%;
            background: var(--primary-main);
        }
.inq-admin .chat-conversation {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
.inq-admin .chat-conversation-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.55rem 0.9rem;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--inquiry-border);
        }
.inq-admin .patient-details {
            flex: 1 1 8rem;
            min-width: 0;
            overflow-wrap: anywhere;
        }
.inq-admin .patient-details h3 {
            color: var(--primary-dark);
            font-size: 1.1rem;
        }
.inq-admin .patient-details p {
            margin-top: 0.15rem;
            color: var(--inquiry-muted);
            font-size: 0.8rem;
        }
.inq-admin .conversation-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
        }
.inq-admin .status-badge { padding: 0.3rem 0.6rem; border-radius: 20px; }
.inq-admin .status-active, .inq-admin .status-pending { background: #fce8bf; color: var(--pending); }
.inq-admin .status-resolved { background: #e4f4e9; color: var(--resolved-green); }
.inq-admin .messages-area {
            flex: 1;
            min-height: 0;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            background: var(--inquiry-thread);
        }
.inq-admin .message-day {
            color: var(--inquiry-muted);
            font-size: 0.75rem;
            text-align: center;
        }
.inq-admin .message {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
        }
.inq-admin .message.outgoing { flex-direction: row-reverse; }
.inq-admin .message.outgoing .message-avatar { background: #103250; color: #fff; }
.inq-admin .message-content {
            min-width: 0;
            max-width: min(82%, 36rem);
        }
.inq-admin .message-sender, .inq-admin .message-time {
            color: var(--inquiry-muted);
            font-size: 0.75rem;
        }
.inq-admin .message-sender { margin-bottom: 0.22rem; }
.inq-admin .message-time { margin-top: 0.25rem; }
.inq-admin .message.outgoing :is(.message-sender, .inq-admin .message-time) { text-align: right; }
.inq-admin .message-bubble {
            display: grid;
            gap: 0.5rem;
            min-width: 0;
            padding: 0.55rem 0.7rem;
            border: 1px solid var(--inquiry-border);
            border-radius: 4px 12px 12px 12px;
            background: var(--inquiry-surface);
        }
.inq-admin .message.outgoing .message-bubble {
            border-color: transparent;
            border-radius: 12px 4px 12px 12px;
            background: #0e62aa;
            color: #fff;
        }
.inq-admin .message-text {
            font-size: 0.9rem;
            line-height: 1.5;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
.inq-admin .message-image img {
            display: block;
            max-width: min(100%, 18rem);
            max-height: 15rem;
            border-radius: 8px;
            object-fit: contain;
        }
.inq-admin .message-file {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
            padding: 0.55rem;
            border: 1px solid var(--inquiry-border);
            border-radius: 8px;
            background: var(--inquiry-thread);
            color: var(--primary-dark);
            text-decoration: none;
        }
.inq-admin .message-file:hover .message-file-name { text-decoration: underline; }
.inq-admin .message-file-icon {
            display: grid;
            place-items: center;
            width: 2rem;
            height: 2rem;
            flex-shrink: 0;
            border-radius: 7px;
            background: #e2ecf7;
            color: #17568e;
            font-size: 0.75rem;
            font-weight: 700;
        }
.inq-admin .message-file-icon.pdf { background: #fbe2e2; color: #9c2626; }
.inq-admin .message-file-details { display: grid; min-width: 0; gap: 0.12rem; }
.inq-admin .message-file-name { font-size: 0.85rem; font-weight: 600; overflow-wrap: anywhere; }
.inq-admin .message-file-type { color: var(--inquiry-muted); font-size: 0.72rem; }
.inq-admin .reply-area {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--inquiry-border);
        }
.inq-admin .reply-input {
            flex: 1;
            min-width: 0;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--inquiry-border);
            border-radius: 8px;
            font: inherit;
            font-size: 0.9rem;
        }
.inq-admin .reply-input:focus-visible, .inq-admin .search-box:focus-visible {
            outline: 2px solid var(--primary-main);
            outline-offset: 1px;
        }
.inq-admin .send-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.65rem 0.85rem;
            border: 0;
            border-radius: 8px;
            background: var(--primary-main);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
        }
.inq-admin .send-btn:hover:not(:disabled) { background: #0b4f8a; }
.inq-admin .send-btn:disabled { opacity: 0.65; cursor: wait; }
.inq-admin .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100%;
            gap: 0.65rem;
            color: var(--inquiry-muted);
        }
.inq-admin .empty-chat-icon { font-size: 1.8rem; color: var(--primary-main); }
body.dark-mode .inq-admin {
            --inquiry-border: #344155;
            --inquiry-muted: #afbdce;
            --inquiry-surface: #1a2232;
            --inquiry-thread: #141d2b;
        }
body.dark-mode .inq-admin :is(.chat-name-text, .message-file, .search-box) { color: #e7edf5; }
body.dark-mode .inq-admin .chat-item:hover { background: #26374d; }
body.dark-mode .inq-admin .chat-item.selected { background: #472e39 !important; }
body.dark-mode .inq-admin .chat-unread { background: #8bc9ff; }
body.dark-mode .inq-admin .chat-status-label.resolved { color: #a5f0bf; }
body.dark-mode .inq-admin .chat-status-label.in-progress { color: #ffda98; }
body.dark-mode .inq-admin .message-content { background: transparent !important; }
.inq-admin ::-webkit-scrollbar {
            width: 8px;
        }
.inq-admin ::-webkit-scrollbar-thumb {
            border-radius: 10px;
        }
.inq-admin .see-more-item {
            list-style: none;
            text-align: center;
            padding: 0.7rem 0.5rem;
        }
.inq-admin .see-more-btn {
            background: none;
            border: 1px solid var(--primary-light);
            color: var(--primary-main);
            border-radius: 20px;
            padding: 0.45rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
.inq-admin .see-more-btn:hover {
            background: var(--light-gray);
            border-color: var(--primary-main);
        }
.inq-admin .resolve-btn-modern, .inq-admin .unresolve-btn-modern {
            display: none;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid #72b487;
            border-radius: 8px;
            background: transparent;
            color: var(--resolved-green);
            cursor: pointer;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 600;
        }
.inq-admin .unresolve-btn-modern { border-color: #d4a65d; color: var(--pending); }
.inq-admin .resolve-btn-modern:hover:not(:disabled) { background: #e4f4e9; }
.inq-admin .unresolve-btn-modern:hover:not(:disabled) { background: #fce8bf; }
.inq-admin :is(.resolve-btn-modern, .inq-admin .unresolve-btn-modern):disabled { opacity: 0.65; cursor: wait; }
.inq-admin .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 39, 68, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: modalFadeIn 0.15s ease;
        }
@keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
.inq-admin .modal-box {
            background: var(--white);
            border-radius: 14px;
            padding: 1.5rem;
            width: min(90vw, 360px);
            box-shadow: 0 10px 40px var(--shadow);
            animation: modalPopIn 0.15s ease;
            text-align: center;
        }
@keyframes modalPopIn {
            from { transform: translateY(8px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
.inq-admin .modal-box h3 {
            margin: 0 0 0.4rem;
            font-size: 1.1rem;
            color: var(--text-dark);
        }
.inq-admin .modal-box p {
            margin: 0 0 1.25rem 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
.inq-admin .modal-actions {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }
.inq-admin .modal-btn {
            flex: 1;
            border: none;
            border-radius: 20px;
            padding: 0.5rem 1.1rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }
.inq-admin .modal-btn:hover { opacity: 0.85; }
.inq-admin .modal-btn-cancel {
            background: var(--light-gray);
            color: var(--text-dark);
        }
.inq-admin .modal-btn-confirm {
            background: var(--primary-main);
            color: #fff;
        }
.inq-admin .modal-btn-confirm-resolve {
            background: var(--resolved-green);
        }
.inq-admin .modal-btn-confirm-unresolve {
            background: var(--pending);
        }
.inq-admin .modal-btn-confirm-delete {
            background: #e74c3c;
        }
.inq-admin .toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1100;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.6rem;
        }
.inq-admin .toast {
            min-width: 220px;
            max-width: 320px;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            color: var(--white);
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 6px 20px var(--shadow);
            animation: toastSlideIn 0.2s ease;
        }
.inq-admin .toast-error { background: #e74c3c; }
.inq-admin .toast-success { background: var(--primary-main); }
@keyframes toastSlideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
.inq-admin .toast-hide {
            animation: toastSlideOut 0.3s ease forwards;
        }
@keyframes toastSlideOut {
            to { transform: translateY(-20px); opacity: 0; }
        }
@media (max-width: 768px) {
  .inq-admin .chat-list {
                flex: 1 1 100%;
            }
  .inq-admin .chat-conversation {
                display: none;
            }
  .inq-admin .inquiries-container:has(#conversationArea.mobile-active) .chat-list {
                display: none;
            }
  .inq-admin .inquiries-container:has(#conversationArea.mobile-active) .chat-conversation {
                display: flex;
            }
  .inq-admin .back-to-list-btn {
                display: flex;
            }
  .inq-admin .message-content {
                max-width: 90%;
            }
}
@media (max-width: 768px) {
  body .inq-admin .modal-overlay { padding: 12px; }
  body .inq-admin .modal-overlay .modal-box {
        width: 100%; max-width: 360px; padding: 1rem;
        max-height: calc(100dvh - 32px); overflow-y: auto;
        border-radius: 14px; box-sizing: border-box;
    }
  body .inq-admin .modal-overlay .modal-actions { gap: 0.5rem; flex-wrap: wrap; }
  body .inq-admin .modal-overlay .modal-btn { flex: 1; padding: 0.65rem 0.8rem; }
}
    /* ---- Neutralise staff.css rules that share class names with the admin look ---- */
    .inq-admin .message-bubble { max-width: none; margin-bottom: 0; font-size: inherit; }
    .inq-admin .modal-box { max-width: 360px; max-height: none; overflow: visible; }
    .inq-admin .toast { position: static; top: auto; left: auto; transform: none; opacity: 1; pointer-events: auto; transition: none; display: block; z-index: auto; }
    .inq-admin .chat-conversation-header, .inq-admin .reply-area { background: transparent; }
</style>