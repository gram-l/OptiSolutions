<style>
    .inquiries-split {
        display: flex;
        gap: 1.25rem;
        align-items: stretch;
        background: white;
        border-radius: 20px;
        box-shadow: var(--shadow);
        overflow: hidden;
        min-height: 620px;
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
        margin: 0 0 0.75rem 0;
        color: var(--text-dark);
    }

    .conv-search {
        width: 100%;
        padding: 0.6rem 1rem;
        border: 1px solid var(--light-gray);
        border-radius: 20px;
        outline: none;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        box-sizing: border-box;
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
        border-left: 3px solid transparent;
        transition: background 0.15s ease;
    }

    .conv-item:hover {
        background: #f4f6f8;
    }

    .conv-item-active {
        background: #eaf2fb;
        border-left: 3px solid var(--primary-main);
    }

    .conv-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--primary-main);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.72rem;
        letter-spacing: 0.02em;
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

    .conv-item-id {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.9rem;
    }

    .conv-item-date {
        font-size: 0.75rem;
        color: #7f8c8d;
        white-space: nowrap;
    }

    .conv-item-preview {
        font-size: 0.82rem;
        color: #7f8c8d;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .conv-unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--primary-main);
        flex-shrink: 0;
    }

    .conv-empty {
        text-align: center;
        color: #7f8c8d;
        padding: 2rem 0;
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