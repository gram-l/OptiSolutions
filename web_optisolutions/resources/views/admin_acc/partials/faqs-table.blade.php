<table class="patients-table faq-table">
    <thead><tr><th style="width:64px;">#</th><th>Question</th><th>Answer</th><th>Status</th><th scope="col" aria-label="Actions"></th></tr></thead>
    <tbody>
    @forelse ($faqs as $faq)
        <tr>
            <td><span class="faq-order">{{ $loop->iteration }}</span></td>
            <td class="faq-q">{{ $faq->question }}</td>
            <td class="faq-a">{{ \Illuminate\Support\Str::limit($faq->answer, 100) ?: '—' }}</td>
            <td><span class="status-badge {{ $faq->is_active ? 'status-active' : 'status-inactive' }}">{{ $faq->is_active ? 'Shown' : 'Hidden' }}</span></td>
            <td><div class="action-buttons">
                <button type="button" class="btn-icon btn-edit" onclick="openFaqModal({{ Js::from($faq) }})"><i class="fa-solid fa-edit" aria-hidden="true"></i> Edit</button>
                <form action="{{ route('system_settings.faqs.destroy', $faq->faq_id) }}" method="POST" data-faq-action data-faq-delete style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-icon btn-remove"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty-state">No FAQs added yet.</td></tr>
    @endforelse
    </tbody>
</table>