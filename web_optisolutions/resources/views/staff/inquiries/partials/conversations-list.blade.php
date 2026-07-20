<div class="conv-list-panel">
    <h4 class="conv-list-title">Recent Conversations</h4>
    <input
        type="text"
        class="conv-search"
        placeholder="Search patient or inquiry..."
        onkeyup="filterConversations(this.value)"
    >

    <div class="conv-list" id="conv-list">
        @forelse($inquiries as $inq)
            @php
            
                $rawId = (string) ($inq->patient_id ?? 'P');
                $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $rawId), 0, 2)) ?: 'P';
                $isSelected = isset($selectedId) && (string) $selectedId === (string) $inq->inquiry_id;
                $preview = $inq->log ? Str::limit($inq->log->user_message, 40) : '—';
            @endphp
            <a
                href="{{ route('staff.inquiries.show', $inq->inquiry_id) }}"
                class="conv-item {{ $isSelected ? 'conv-item-active' : '' }}"
                data-search="{{ strtolower(($inq->patient_id ?? '') . ' ' . $preview) }}"
            >
                <div class="conv-avatar">{{ $initials }}</div>
                <div class="conv-item-body">
                    <div class="conv-item-top">
                        <span class="conv-item-id">CHAT-{{ str_pad($inq->inquiry_id, 3, '0', STR_PAD_LEFT) }}</span>
                        <span class="conv-item-date">
                            {{ $inq->log && $inq->log->chat_time ? \Carbon\Carbon::parse($inq->log->chat_time)->format('M d') : '' }}
                        </span>
                    </div>
                    <div class="conv-item-preview">
                        {{ $preview }}
                        @if(empty($inq->inquiry_reply))
                            <span class="conv-unread-dot"></span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <p class="conv-empty">No inquiries found.</p>
        @endforelse
    </div>
</div>

<script>
    function filterConversations(query) {
        query = query.toLowerCase();
        document.querySelectorAll('#conv-list .conv-item').forEach(function (el) {
            const match = el.dataset.search.includes(query);
            el.style.display = match ? '' : 'none';
        });
    }
</script>