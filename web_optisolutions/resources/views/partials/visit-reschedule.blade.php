<script>
(() => {
    let activeId, slots = [], saved = false, busy = false;
    const base = @json($rescheduleBase);
    const overlay = document.createElement('div');
    overlay.className = 'visit-message-overlay';
    overlay.hidden = true;
    overlay.innerHTML = '<section class="visit-message-dialog visit-reschedule-dialog" role="dialog" aria-modal="true" aria-labelledby="visitRescheduleTitle"><h3 id="visitRescheduleTitle">Reschedule visit</h3><p id="visitRescheduleDoctor"></p><label for="visitRescheduleDate">New date</label><input type="date" id="visitRescheduleDate"><label for="visitRescheduleSlot">Doctor’s schedule</label><select id="visitRescheduleSlot"></select><p id="visitRescheduleStatus" role="status" aria-live="polite"></p><div class="visit-message-actions"><button type="button" class="visit-message-cancel">Cancel</button><button type="button" class="visit-message-ok">Confirm and email</button></div></section>';
    document.body.appendChild(overlay);
    const date = overlay.querySelector('input');
    const select = overlay.querySelector('select');
    const status = overlay.querySelector('#visitRescheduleStatus');
    const confirm = overlay.querySelector('.visit-message-ok');
    const cancel = overlay.querySelector('.visit-message-cancel');
    let opener, previousOverflow;
    function close() {
        if (busy) return;
        overlay.hidden = true;
        document.body.style.overflow = previousOverflow;
        opener?.focus();
        if (saved) location.reload();
    }
    function showSlots() {
        const day = date.value ? new Date(date.value + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'long' }) : '';
        const available = slots.filter(slot => slot.day === day && slot.end_time > slot.start_time);
        select.replaceChildren(new Option(available.length ? 'Choose a schedule' : 'No schedule for this date', ''), ...available.map(slot => new Option(`${slot.day}: ${slot.start_time.slice(0, 5)} – ${slot.end_time.slice(0, 5)}`, slot.schedule_id)));
        confirm.disabled = true;
        status.textContent = available.length ? 'The patient will receive the updated date and time by email.' : 'Choose a date when this doctor is scheduled.';
    }
    date.addEventListener('change', showSlots);
    select.addEventListener('change', () => confirm.disabled = !select.value || busy);
    cancel.addEventListener('click', close);
    overlay.addEventListener('click', event => { if (event.target === overlay) close(); });
    overlay.addEventListener('keydown', event => {
        if (event.key === 'Escape') close();
        if (event.key === 'Tab') {
            const elements = [...overlay.querySelectorAll('input,select,button')].filter(element => !element.disabled);
            const first = elements[0], last = elements.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.openVisitReschedule = async id => {
        if (busy) return;
        activeId = id; slots = []; saved = false; busy = true;
        opener = document.activeElement; previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        overlay.hidden = false; status.textContent = 'Loading doctor schedules…';
        overlay.querySelector('#visitRescheduleDoctor').textContent = '';
        confirm.hidden = false; cancel.textContent = 'Cancel';
        confirm.textContent = 'Confirm and email'; confirm.disabled = true; date.disabled = true; select.disabled = true;
        date.value = ''; select.replaceChildren(); cancel.focus();
        try {
            const response = await fetch(`${base}/${id}/schedules`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('load');
            const data = await response.json(); slots = data.schedules;
            overlay.querySelector('#visitRescheduleDoctor').textContent = data.doctor || 'Doctor';
            if (!data.doctor_available) {
                status.textContent = data.message || 'This doctor is unavailable for rescheduling.';
                return;
            }
            date.min = data.today;
            date.value = data.visit_date >= data.today ? data.visit_date : data.today;
            date.disabled = false; select.disabled = false; showSlots(); date.focus();
        } catch (_) { status.textContent = 'Could not load schedules. Close and try again.'; }
        finally { busy = false; }
    };
    confirm.addEventListener('click', async () => {
        if (busy || !select.value || !date.value) return;
        busy = true; confirm.disabled = true; cancel.disabled = true; date.disabled = true; select.disabled = true;
        status.textContent = 'Saving and sending email…';
        try {
            const response = await fetch(`${base}/${activeId}/reschedule`, {
                method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                body: JSON.stringify({ visit_date: date.value, schedule_id: Number(select.value) }),
            });
            const data = await response.json();
            status.textContent = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Unable to reschedule.');
            if (response.ok && data.success) {
                saved = true;
                cancel.textContent = 'Close';
                confirm.textContent = data.email_sent ? 'Email sent' : 'Retry email';
                confirm.disabled = !!data.email_sent;
                if (data.email_sent) confirm.hidden = true;
            }
        } catch (_) { status.textContent = 'Could not reach the server. Check the visit before retrying.'; }
        finally { busy = false; cancel.disabled = false; date.disabled = false; select.disabled = false; if (!confirm.hidden) confirm.disabled = false; }
    });
})();
</script>
