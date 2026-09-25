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
                // Mirrors ChatbotInquiryController::toChatLogArray()'s exact
                // display-name / avatar-initials / code logic on the admin
                // side, so both pages label and initial the same inquiry
                // identically.
                $displayName = $inq->patient_id ? ('Patient #' . $inq->patient_id) : ($inq->guest_name ?: 'Guest');
                $initials = $inq->patient_id ? ('P' . $inq->patient_id) : strtoupper(substr($inq->guest_name ?: 'G', 0, 1));
                $avatarText = strtoupper(substr($initials, 0, 2));
                $inquiryCode = 'INQ-' . str_pad($inq->inquiry_id, 3, '0', STR_PAD_LEFT);
                $isSelected = isset($selectedId) && (string) $selectedId === (string) $inq->inquiry_id;
                $isResolved = $inq->resolved_status === 'Resolved';
                $preview = $inq->log ? Str::limit($inq->log->user_message, 40) : '—';
            @endphp
            <a
                href="{{ route('staff.inquiries.show', $inq->inquiry_id) }}"
                class="conv-item {{ $isSelected ? 'conv-item-active' : '' }} {{ $loop->iteration > 6 ? 'conv-item-extra' : '' }}"
                data-search="{{ strtolower($displayName . ' ' . $inquiryCode . ' ' . $preview) }}"
            >
                <div class="conv-avatar">
                    {{ $avatarText }}
                    @if(empty($inq->inquiry_reply))
                        <span class="conv-unread-dot"></span>
                    @endif
                </div>
                <div class="conv-item-body">
                    <div class="conv-item-top">
                        <span class="conv-item-name">{{ $displayName }}</span>
                        <span class="conv-status-label {{ $isResolved ? 'resolved' : 'pending' }}">
                            @unless($isResolved)
                                <span class="conv-status-dot pending"></span>
                            @endunless
                            {{ $isResolved ? 'Resolved' : 'In Progress' }}
                        </span>
                    </div>
                    <div class="conv-item-bottom">
                        <span class="conv-item-id">{{ $inquiryCode }}</span>
                        <span class="conv-item-date">
                            {{ $inq->log && $inq->log->chat_time ? \Carbon\Carbon::parse($inq->log->chat_time)->format('M d') : '' }}
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <p class="conv-empty">No inquiries found.</p>
        @endforelse

        @if($inquiries->count() > 6)
            <button type="button" class="conv-see-more-btn" id="convSeeMoreBtn">See More</button>
        @endif
    </div>
</div>

<style>
    .conv-item-extra { display: none; }
</style>

<script>
    function filterConversations(query) {
        query = query.toLowerCase();
        document.querySelectorAll('#conv-list .conv-item').forEach(function (el) {
            const match = el.dataset.search.includes(query);
            el.style.display = match ? '' : 'none';
        });
    }

    document.getElementById('convSeeMoreBtn')?.addEventListener('click', function () {
        document.querySelectorAll('#conv-list .conv-item-extra').forEach(el => el.classList.remove('conv-item-extra'));
        this.style.display = 'none';
    });
</script>