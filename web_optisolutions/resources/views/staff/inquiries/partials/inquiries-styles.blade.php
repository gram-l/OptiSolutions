<style>
    .inquiries-split {
        display: flex;
        gap: 1.25rem;
        align-items: stretch;
        background: white;
        border-radius: 20px;
        box-shadow: var(--shadow);
        overflow: hidden;
        height: 620px;
    }

    .conv-list-panel {
        width: 340px;
        flex-shrink: 0;
        border-right: 1px solid var(--light-gray);
        padding: 1.25rem 1rem;
        display: flex;
        flex-direction: column;
        /* Without this, a flex column child's default min-height:auto
           lets its content (the scrollable list below) refuse to
           shrink to fit, so it spills past this panel's height instead
           of scrolling within it — the "overlap" with the panel below. */
        min-height: 0;
    }

    .conv-list-title {
        margin: 0 0 0.5rem 0;
        color: var(--primary-dark);
        font-size: 1.2rem;
        font-weight: 600;
    }

    .conv-search {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 1px solid var(--light-gray);
        border-radius: 25px;
        outline: none;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }

    .conv-search:focus {
        border-color: var(--primary-main);
        box-shadow: 0 0 0 2px rgba(14, 98, 170, 0.2);
    }

    .conv-list {
        overflow-y: auto;
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        /* A row can end up sliced in half at the scroll boundary (the
           list's height is rarely an exact multiple of row height).
           Fading it out instead of hard-clipping makes that read as
           "more below, scroll for it" rather than a rendering glitch. */
        -webkit-mask-image: linear-gradient(to bottom, transparent 0, black 14px, black calc(100% - 14px), transparent 100%);
        mask-image: linear-gradient(to bottom, transparent 0, black 14px, black calc(100% - 14px), transparent 100%);
        scrollbar-width: thin;
        scrollbar-color: #cbd3da transparent;
    }

    .conv-list::-webkit-scrollbar {
        width: 6px;
    }

    .conv-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .conv-list::-webkit-scrollbar-thumb {
        background: #cbd3da;
        border-radius: 3px;
    }

    .conv-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.7rem 0.6rem;
        border-radius: 12px;
        text-decoration: none;
        color: inherit;
        transition: background 0.15s ease;
    }

    .conv-item:hover {
        background: #f4f6f8;
    }

    .conv-item-active {
        background: rgba(14, 98, 170, 0.1);
    }

    .conv-avatar {
        position: relative;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        flex-shrink: 0;
        line-height: 1;
    }

    .conv-item-body {
        min-width: 0;
        flex: 1;
    }

    .conv-item-top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
    }

    .conv-item-name {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.95rem;
    }

    .conv-item-bottom {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
        margin-top: 0.15rem;
    }

    .conv-item-id {
        font-size: 0.78rem;
        color: #7f8c8d;
    }

    .conv-item-date {
        font-size: 0.75rem;
        color: #7f8c8d;
        white-space: nowrap;
    }

    .conv-status-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .conv-status-label.pending { color: #f39c12; }
    .conv-status-label.resolved { color: #27ae60; }

    .conv-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
        display: inline-block;
    }

    .conv-status-dot.pending { background: #f39c12; }
    .conv-status-dot.resolved { background: #27ae60; }

    .conv-unread-dot {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--primary-main);
        border: 2px solid var(--white);
        flex-shrink: 0;
    }

    .conv-empty {
        text-align: center;
        color: #7f8c8d;
        padding: 2rem 0;
    }

    /* ===== "See More" control — matches admin's outline pill ===== */
    .conv-see-more-btn {
        display: block;
        margin: 0.5rem auto 0;
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

    .conv-see-more-btn:hover {
        background: var(--light-gray);
        border-color: var(--primary-main);
    }

    /* ===== Page header icon badge — matches admin's .page-header h2 span ===== */
    .inq-header-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.15rem;
        height: 2.15rem;
        flex-shrink: 0;
        border-radius: 10px;
        background: rgba(14, 98, 170, 0.1);
        color: var(--primary-main);
        font-size: 1.05rem;
        margin-right: 0.5rem;
    }

    /* ===== Status pill — matches admin's tinted .status-active / .status-resolved ===== */
    .inq-status-badge {
        padding: 0.25rem 0.85rem;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .inq-status-pending { background: #fdf2e3; color: #f39c12; }
    .inq-status-resolved { background: #e8f8f0; color: #27ae60; }

    /* ===== Resolve button — matches admin's .resolve-btn-modern pastel pill ===== */
    .inq-resolve-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1.5px solid #a8e2c4;
        border-radius: 20px;
        padding: 0.55rem 1.35rem;
        font-size: 0.88rem;
        font-weight: 700;
        color: #27ae60;
        background: #d3f1e2;
        box-shadow: 0 2px 8px rgba(39, 174, 96, 0.2);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .inq-resolve-btn:hover {
        background: #c0ebd6;
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        transform: translateY(-1px);
    }

    /* ===== Message bubbles — matches admin's .message / .message-content / .message-avatar ===== */
    .inq-message {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 1rem;
    }

    .inq-message.staff-row {
        flex-direction: row-reverse;
    }

    .inq-message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-main);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .inq-message-avatar.patient {
        background: var(--primary-dark);
    }

    .inq-message-content {
        flex: 1;
        background: var(--white);
        padding: 0.8rem 1rem;
        border-radius: 12px;
        border-top-left-radius: 4px;
        box-shadow: 0 1px 2px var(--shadow);
        max-width: 80%;
    }

    .inq-message-content.staff {
        background: var(--light-gray);
        border-top-left-radius: 12px;
        border-top-right-radius: 4px;
    }

    .inq-message-sender {
        font-weight: 600;
        font-size: 0.8rem;
        margin-bottom: 0.3rem;
        color: var(--primary-dark);
    }

    .inq-message-sender.staff {
        color: var(--primary-main);
    }

    .inq-message-text {
        color: var(--text-dark);
        line-height: 1.4;
        font-size: 0.9rem;
        margin: 0.2rem 0 0 0;
    }

    .inq-message-time {
        font-size: 0.7rem;
        color: #95a5a6;
        margin-top: 0.3rem;
        display: block;
    }

    .inq-message-attachment a {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.4rem;
        text-decoration: underline;
    }

    /* ===== Reply area — matches admin's pill input + pill send button ===== */
    .inq-reply-input {
        flex: 1;
        padding: 0.8rem 1rem;
        border: 1px solid var(--light-gray);
        border-radius: 25px;
        outline: none;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .inq-reply-input:focus {
        border-color: var(--primary-main);
        box-shadow: 0 0 0 2px rgba(14, 98, 170, 0.2);
    }

    .inq-send-btn {
        padding: 0.8rem 1.8rem;
        background: var(--primary-main);
        color: white;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .inq-send-btn:hover {
        background: #0b4f8a;
        transform: translateY(-2px);
    }


    .conv-detail-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1.25rem 1.5rem;
        min-width: 0;
    }

    .conv-detail-empty {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #7f8c8d;
        text-align: center;
        font-size: 1rem;
    }

    .conv-back-link {
        display: none;
    }

    @media (max-width: 768px) {
        .inquiries-split {
            flex-direction: column;
            /* The 620px min-height was meant for the two-column desktop
               layout; once the panels stack it just forced a tall,
               mostly-empty detail panel below a list that got clipped
               mid-row at 300px, making the last visible conversation
               look cut off / overlapping the panel boundary below it. */
            min-height: 0;
        }
        .conv-list-panel {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--light-gray);
            /* Fixed height (not max-height) so the list's own scrollbar
               is what clips rows, cleanly, at a consistent boundary
               instead of the last row being sliced by the panel edge. */
            height: 320px;
            flex-shrink: 0;
        }
        .conv-detail-panel {
            min-height: 260px;
        }

        /* Phone-sized screens behave like a real chat app: tapping a
           conversation replaces the list with the full conversation
           (instead of navigating to a page that still shows the list
           on top, forcing a scroll past it to reach the messages
           underneath), with a way back to the list. */
        .inquiries-split--detail-open .conv-list-panel {
            display: none;
        }
        .inquiries-split--detail-open .conv-detail-panel {
            min-height: 0;
        }
        .inquiries-split--list-only .conv-detail-panel {
            display: none;
        }
        .conv-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.5rem;
            color: var(--primary-main);
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }
        .conv-back-link:hover {
            text-decoration: underline;
        }
    }

    /* ===== DARK MODE ===== */
    body.dark-mode .inquiries-split {
        background: #1a2130;
        box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    }
    body.dark-mode .conv-search {
        background: #232b3d;
        color: #e4e8ee;
        border-color: #2a3346;
    }
    body.dark-mode .conv-search::placeholder {
        color: #7f8c93;
    }
    body.dark-mode .conv-item:hover {
        background: #232b3d;
    }
    body.dark-mode .conv-list {
        scrollbar-color: #3a4356 transparent;
    }
    body.dark-mode .conv-list::-webkit-scrollbar-thumb {
        background: #3a4356;
    }
    body.dark-mode .conv-item-active {
        background: rgba(14, 98, 170, 0.25);
        border-left-color: var(--primary-light);
    }
</style>