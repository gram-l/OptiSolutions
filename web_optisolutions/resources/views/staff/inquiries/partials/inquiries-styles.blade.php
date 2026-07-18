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

    /* ---------- LEFT PANEL ---------- */
    .conv-list-panel {
        width: 340px;
        flex-shrink: 0;
        border-right: 1px solid var(--light-gray);
        padding: 1.25rem 1rem;
        display: flex;
        flex-direction: column;
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
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
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
        border-left: 3px solid var(--primary-deep-blue);
    }

    .conv-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-deep-blue);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
        flex-shrink: 0;
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
        background: var(--primary-deep-blue);
        flex-shrink: 0;
    }

    .conv-empty {
        text-align: center;
        color: #7f8c8d;
        padding: 2rem 0;
    }

    /* ---------- RIGHT PANEL ---------- */
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

    @media (max-width: 768px) {
        .inquiries-split {
            flex-direction: column;
        }
        .conv-list-panel {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--light-gray);
            max-height: 300px;
        }
    }
</style>