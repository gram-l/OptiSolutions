<style>
.visit-calendar { position:absolute; z-index:1200; width:300px; padding:14px; background:white; color:#123047; border:1px solid #dbe3eb; border-radius:12px; box-shadow:0 8px 28px #12304726; }
.visit-calendar[hidden] { display:none; }
.visit-calendar header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.visit-calendar button { border:0; border-radius:6px; background:#f1f5f9; color:#123047; padding:8px; cursor:pointer; font:inherit; }
.visit-calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:3px; text-align:center; }
.visit-calendar-grid small { padding:5px 0; }
.visit-calendar-grid .has-visits { background:#ffe082; color:#513d00; font-weight:700; }
.visit-calendar-grid .all-visited { background:#d1d5db; color:#374151; font-weight:700; }
.visit-calendar-grid button[aria-pressed="true"] { outline:2px solid #1063ad; outline-offset:-2px; }
.visit-calendar p { font-size:12px; margin:12px 0 0; }
</style>
<script>
(() => {
    const input = document.getElementById(@json($calendarInput));
    if (!input) return;
    const visits = @json(\Illuminate\Support\Facades\DB::table('schedule_visit')->get(['visit_id', 'visit_date', 'visited']));
    const dates = new Map();
    function updateDates() {
        dates.clear();
        visits.forEach(visit => {
            const date = String(visit.visit_date).slice(0,10);
            dates.set(date, (dates.get(date) ?? true) && Number(visit.visited) === 1);
        });
    }
    updateDates();
    input.type = 'text';
    input.readOnly = true;
    input.placeholder = 'Select date';
    input.setAttribute('aria-haspopup', 'dialog');
    const panel = document.createElement('div');
    panel.className = 'visit-calendar';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Scheduled visits calendar');
    document.body.append(panel);
    let month = new Date();
    const iso = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
    function choose(value) {
        input.value = value;
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.dispatchEvent(new Event('change', { bubbles:true }));
        input.focus();
    }
    function button(label, action) {
        const el = document.createElement('button');
        el.type = 'button'; el.textContent = label;
        // Rendering replaces the clicked button; stop bubbling before the
        // outside-click listener can mistake that detached button for outside.
        el.onclick = event => { event.stopPropagation(); action(); };
        return el;
    }
    function render() {
        panel.replaceChildren();
        const header = document.createElement('header');
        const title = document.createElement('strong');
        title.textContent = month.toLocaleDateString('en-US', {month:'long', year:'numeric'});
        header.append(button('‹', () => { month.setMonth(month.getMonth()-1); render(); }), title, button('›', () => { month.setMonth(month.getMonth()+1); render(); }));
        header.firstChild.setAttribute('aria-label','Previous month');
        header.lastChild.setAttribute('aria-label','Next month');
        panel.append(header);
        const grid = document.createElement('div'); grid.className = 'visit-calendar-grid';
        ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(day => { const el=document.createElement('small'); el.textContent=day; grid.append(el); });
        for(let i=0;i<month.getDay();i++) grid.append(document.createElement('span'));
        const count = new Date(month.getFullYear(),month.getMonth()+1,0).getDate();
        for(let day=1;day<=count;day++) {
            const date = new Date(month.getFullYear(),month.getMonth(),day);
            const value = iso(date);
            const el = button(String(day), () => choose(value));
            el.classList.toggle('has-visits',dates.has(value) && !dates.get(value));
            el.classList.toggle('all-visited',dates.get(value) === true);
            el.setAttribute('aria-pressed',String(input.value===value));
            el.setAttribute('aria-label',`${date.toDateString()}${dates.has(value)?(dates.get(value)?', all visits marked visited':', scheduled visits'):''}`);
            grid.append(el);
        }
        panel.append(grid);
        const legend = document.createElement('p'); legend.textContent='🟨 Scheduled visits · ◼ Grey: all visited'; panel.append(legend);
        const actions=document.createElement('header');
        actions.append(button('Clear',()=>choose('')),button('Today',()=> {
            const today = new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Manila',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
            choose(today);
        })); panel.append(actions);
    }
    function open() {
        const selected = /^\d{4}-\d{2}-\d{2}$/.test(input.value) ? new Date(input.value+'T12:00:00') : new Date();
        month = new Date(selected.getFullYear(),selected.getMonth(),1);
        render(); panel.hidden=false; input.setAttribute('aria-expanded','true');
        const rect=input.getBoundingClientRect();
        panel.style.left=`${Math.max(8, Math.min(rect.left,window.innerWidth-328))+window.scrollX}px`;
        panel.style.top=`${rect.bottom+window.scrollY+5}px`;
    }
    input.addEventListener('click',open);
    document.addEventListener('visit-completed', event => {
        const visit = visits.find(item => String(item.visit_id) === String(event.detail.id));
        if (visit) { visit.visited = 1; updateDates(); if (!panel.hidden) render(); }
    });
    input.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '||event.key==='ArrowDown'){event.preventDefault();open();panel.querySelector('button').focus();}});
    document.addEventListener('click',event=>{if(event.target!==input&&!panel.contains(event.target)){panel.hidden=true;input.setAttribute('aria-expanded','false');}});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!panel.hidden){panel.hidden=true;input.setAttribute('aria-expanded','false');input.focus();}});
})();
</script>
