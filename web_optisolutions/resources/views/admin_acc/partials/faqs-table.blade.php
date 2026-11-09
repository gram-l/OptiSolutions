<table class="patients-table">
    <thead><tr><th style="width:70px;">Order</th><th>Question</th><th>Answer</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse ($faqs as $faq)
        <tr>
            <td>
                <div class="action-buttons" style="gap:4px;">
                    <form action="{{ route('system_settings.faqs.move', $faq->faq_id) }}" method="POST" data-faq-action style="display:inline">
                        @csrf
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" class="btn-icon" title="Move up" @disabled($loop->first)>&uarr;</button>
                    </form>
                    <form action="{{ route('system_settings.faqs.move', $faq->faq_id) }}" method="POST" data-faq-action style="display:inline">
                        @csrf
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" class="btn-icon" title="Move down" @disabled($loop->last)>&darr;</button>
                    </form>
                </div>
            </td>
            <td>{{ $faq->question }}</td>
            <td>{{ \Illuminate\Support\Str::limit($faq->answer, 100) ?: '—' }}</td>
            <td><span class="status-badge {{ $faq->is_active ? 'status-active' : 'status-inactive' }}">{{ $faq->is_active ? 'Shown' : 'Hidden' }}</span></td>
            <td><div class="action-buttons">
                <button type="button" class="btn-icon btn-edit" onclick="openFaqModal({{ Js::from($faq) }})">Edit</button>
                <form action="{{ route('system_settings.faqs.destroy', $faq->faq_id) }}" method="POST" data-faq-action data-faq-delete style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-icon" style="background:var(--danger);color:#fff;">Delete</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty-state">No FAQs added yet.</td></tr>
    @endforelse
    </tbody>
</table>