<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic · Schedule Visit</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  @vite(['resources/css/patient_css/chatbot.css'])
</head>
<body>

<button class="chatbot-trigger" id="chatbotTrigger">
  <i class="fa-solid fa-comment-medical"></i>
</button>

<div class="chatbot-panel" id="chatbotPanel">

  <div class="chat-header">
    <div class="chat-header-left">
      <div class="chat-avatar">
        <i class="fa-solid fa-headset"></i>
      </div>
      <div class="chat-header-info">
        <strong>PolyClinic Assistant</strong>
        <span>● Online</span>
      </div>
    </div>
    <button class="chat-close" id="chatClose">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="chat-messages" id="chatMessages"></div>

  <div class="quick-replies" id="quickReplies"></div>

  <div class="attachment-preview" id="attachmentPreview">
    <img id="attachmentPreviewImg" class="attachment-preview-thumb" alt="">
    <span id="attachmentPreviewName" class="attachment-preview-name"></span>
    <button type="button" id="attachmentPreviewRemove" class="attachment-preview-remove" aria-label="Remove attachment">&times;</button>
  </div>

  <div class="chat-input-area">
    <button class="attach-btn" id="attachBtn"><i class="fa-solid fa-paperclip"></i></button>
    <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
    <button class="chat-send" id="chatSend"><i class="fa-solid fa-paper-plane"></i></button>
    <input type="file" class="file-input" id="fileInput">
  </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const trigger    = document.getElementById('chatbotTrigger');
  const panel      = document.getElementById('chatbotPanel');
  const closeBtn   = document.getElementById('chatClose');
  const messagesEl = document.getElementById('chatMessages');
  const quickRepliesEl = document.getElementById('quickReplies');
  const input      = document.getElementById('chatInput');
  const sendBtn    = document.getElementById('chatSend');
  const attachBtn  = document.getElementById('attachBtn');
  const fileInput  = document.getElementById('fileInput');
  const attachmentPreview     = document.getElementById('attachmentPreview');
  const attachmentPreviewImg  = document.getElementById('attachmentPreviewImg');
  const attachmentPreviewName = document.getElementById('attachmentPreviewName');
  const attachmentPreviewRemove = document.getElementById('attachmentPreviewRemove');
  const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
  const botmanUrl  = '{{ route("botman.handle") }}';
  const attachmentUrl = '{{ route("chatbot.attachment") }}';

  const APPT_CARD_PREFIX = 'APPT_CARD::';
  const INFO_CARD_PREFIX = 'INFO_CARD::';
  const FORM_CARD_PREFIX = 'FORM_CARD::';
  const FORM_SUBMIT_PREFIX = 'FORM_SUBMIT::';

  // Guards against overlapping requests — rapid clicking a quick-reply
  // button or mashing Enter before the first response comes back used
  // to fire multiple parallel /botman calls. Input, send button, and
  // all quick-reply buttons are disabled while a request is in flight.
  let isSending = false;

  function setSendingState(sending) {
    isSending = sending;
    input.disabled = sending;
    sendBtn.disabled = sending;
    if (attachBtn) attachBtn.disabled = sending;
    quickRepliesEl.querySelectorAll('.quick-reply-btn').forEach(b => {
      b.disabled = sending;
    });

    // A disabled <input> loses focus, so once the request finishes the
    // patient used to have to click back into the message box before
    // they could type again. Give focus back automatically so they can
    // just keep typing and hit Enter.
    if (!sending) focusInput();
  }

  function focusInput() {
    if (!panel.classList.contains('open') || input.disabled) return;
    // Don't steal focus while the patient is typing inside the form card.
    const active = document.activeElement;
    if (active && active.closest && active.closest('.chat-form')) return;
    // preventScroll: don't let the page jump when focus returns.
    input.focus({ preventScroll: true });
  }

  // PER-MESSAGE TRANSLATION

  async function translateCounterpart(text) {
    if (!text || !text.trim()) return { text, lang: 'en' };
    try {
      const res = await fetch('/api/translate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ text }),
      });
      if (!res.ok) return { text, lang: 'en' };
      const data = await res.json();
      return {
        text: data.translatedText || text,
        lang: data.detectedSourceLanguage === 'tl' ? 'en' : 'tl',
      };
    } catch (e) {
      console.error('Translation error:', e);
      return { text, lang: 'en' };
    }
  }

  function attachTranslateControls(messageWrap, getOriginalText) {
    const anchor = document.createElement('div');
    anchor.style.position = 'relative';
    anchor.style.display = 'inline-block';
    anchor.style.maxWidth = '100%';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'msg-translate-btn';
    btn.innerHTML = '<i class="fa-solid fa-language"></i> Translate';

    const box = document.createElement('div');
    box.className = 'msg-translation-box';

    let loaded = false;
    let visible = false;

    btn.addEventListener('click', async () => {
      if (visible) {
        box.classList.remove('visible');
        btn.innerHTML = '<i class="fa-solid fa-language"></i> Translate';
        visible = false;
        return;
      }

      if (!loaded) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Translating...';
        const originalText = getOriginalText();
        const result = await translateCounterpart(originalText);

        box.innerHTML = '';
        const span = document.createElement('span');
        span.textContent = result.text;

        const collapseBtn = document.createElement('button');
        collapseBtn.type = 'button';
        collapseBtn.className = 'msg-translation-collapse';
        collapseBtn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
        collapseBtn.addEventListener('click', () => btn.click());

        box.appendChild(span);
        box.appendChild(collapseBtn);
        loaded = true;
      }

      box.classList.add('visible');
      btn.innerHTML = '<i class="fa-solid fa-language"></i> Hide translation';
      visible = true;
    });

    anchor.appendChild(btn);
    anchor.appendChild(box);
    messageWrap.appendChild(anchor);
  }

  // SESSION PERSISTENCE

  const STORAGE_KEY = 'polyclinic_chat_state';
  const SESSION_TIMEOUT_MS = 2 * 60 * 60 * 1000; // 2 hours

  function generateClientId() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
      const r = Math.random() * 16 | 0;
      const v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  function loadSession() {
    let saved;
    try {
      saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
    } catch (e) {
      saved = null;
    }

    const now = Date.now();
    const isExpired = !saved || (now - saved.lastActivity) > SESSION_TIMEOUT_MS;

    if (isExpired) {
      return {
        clientId: generateClientId(),
        lastActivity: now,
        history: [],
        shownReplyIds: [],
        shownResolvedIds: [],
      };
    }

    if (!saved.shownReplyIds) saved.shownReplyIds = [];
    if (!saved.shownResolvedIds) saved.shownResolvedIds = [];

    return saved;
  }

  let session = loadSession();

  function persistSession() {
    session.lastActivity = Date.now();
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
  }

  // PENDING ATTACHMENT (staged, not yet sent)

  let pendingAttachment = null;

  function showAttachmentPreview(file, dataUrl) {
    pendingAttachment = { file, dataUrl };

    if (dataUrl) {
      attachmentPreviewImg.src = dataUrl;
      attachmentPreviewImg.style.display = 'block';
    } else {
      attachmentPreviewImg.style.display = 'none';
      attachmentPreviewImg.src = '';
    }

    attachmentPreviewName.textContent = file.name;
    attachmentPreview.style.display = 'flex';
    input.focus();
  }

  function clearAttachmentPreview() {
    pendingAttachment = null;
    attachmentPreview.style.display = 'none';
    attachmentPreviewImg.src = '';
    attachmentPreviewName.textContent = '';
    fileInput.value = '';
  }

  attachmentPreviewRemove.addEventListener('click', clearAttachmentPreview);

  async function uploadAttachment(file, caption) {
    // Same guard as sendMessage(): the input/send/attach controls stay
    // disabled for the whole upload window so the patient can't fire
    // off more messages while we're still waiting on this one (avoids
    // spamming the bot/admin/staff). No typing dots here — this reply
    // is generated immediately, it isn't a human composing a message.
    setSendingState(true);
    try {
      const formData = new FormData();
      formData.append('attachment', file);
      formData.append('userId', session.clientId);
      if (caption) formData.append('caption', caption);

      const res = await fetch(attachmentUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: formData,
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        addErrorBubble(data.error || 'Something went wrong uploading the attachment. Please try again.');
        return;
      }

      addBubble("Thanks for the attachment! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.", 'bot');
    } catch (e) {
      console.error('Failed to upload attachment:', e);
      addErrorBubble('Something went wrong uploading the attachment. Please try again.');
    } finally {
      setSendingState(false);
    }
  }

  async function handleSend() {
    const text = input.value.trim();
    const attachment = pendingAttachment;

    if (!text && !attachment) return;

    if (attachment) {
      addAttachmentBubble(attachment.file.name, attachment.dataUrl, 'user', true, text || null);
      clearAttachmentPreview();
      input.value = '';

      await uploadAttachment(attachment.file, text || null);
      return;
    }

    if (text) {
      sendMessage(text);
    }
  }

  // Panel open/close
  trigger.addEventListener('click', () => {
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
      input.focus();
      setTimeout(focusInput, 250);
      scrollToBottom();
      pollForReplies();
    }
  });

  closeBtn.addEventListener('click', () => {
    panel.classList.remove('open');
  });

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  // Formats text with bold styling and preserves line breaks.
  function renderBubbleText(el, text) {
    const escaped = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
    el.innerHTML = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  }

  function addBubble(text, sender = 'bot', persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = `chat-message ${sender}`;
    messageWrap.style.display = 'flex';
    messageWrap.style.flexDirection = 'column';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    renderBubbleText(bubble, text);

    messageWrap.appendChild(bubble);
    // Sends the original text for translation without formatting markers.
    attachTranslateControls(messageWrap, () => text);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'bubble', text, sender });
      persistSession();
    }

    return bubble;
  }

  // Displays an error message with a refresh option.
  function addErrorBubble(text) {
    const bubble = addBubble(text, 'bot', false);

    const refreshBtn = document.createElement('button');
    refreshBtn.type = 'button';
    refreshBtn.className = 'msg-translate-btn';
    refreshBtn.innerHTML = '<i class="fa-solid fa-arrow-rotate-right"></i> Refresh';
    refreshBtn.style.marginTop = '6px';
    refreshBtn.addEventListener('click', () => window.location.reload());

    bubble.parentElement.appendChild(refreshBtn);
    scrollToBottom();
    return bubble;
  }

  // Reloads the page automatically when the connection is restored.
  let wasOffline = false;
  window.addEventListener('offline', () => {
    wasOffline = true;
    addErrorBubble("You've lost your internet connection. I'll refresh automatically once you're back online.");
    setSendingState(true);
  });
  window.addEventListener('online', () => {
    if (wasOffline) {
      window.location.reload();
    }
  });

  // Star rating helpers
  function isRatingButtons(buttons) {
    return Array.isArray(buttons) && buttons.length === 5 &&
      buttons.every((b, i) => String(b.value ?? b.text).trim() === String(i + 1));
  }

  function addStarRating(buttons, persist = true, selectedIndex = null) {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble star-rating-bubble';

    const row = document.createElement('div');
    row.className = 'star-rating-row';

    const stars = [];
    // Keeps the selected star rating filled and prevents further changes.
    let selectedIdx = selectedIndex;

    function paintUpTo(idx) {
      stars.forEach((s, i) => s.classList.toggle('filled', i <= idx));
    }

    buttons.forEach((btn, idx) => {
      const starBtn = document.createElement('button');
      starBtn.type = 'button';
      starBtn.className = 'star-btn';
      starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';

      starBtn.addEventListener('mouseenter', () => {
        if (selectedIdx !== null) return;
        paintUpTo(idx);
      });

      starBtn.addEventListener('click', () => {
        if (selectedIdx !== null) return;
        selectedIdx = idx;

        paintUpTo(idx);
        stars.forEach(s => { s.disabled = true; });

        const ratingLabel = `${idx + 1}/5`;
        sendMessage(btn.value ?? btn.text, ratingLabel);
      });

      stars.push(starBtn);
      row.appendChild(starBtn);
    });

    row.addEventListener('mouseleave', () => {
      if (selectedIdx !== null) return; // keep the chosen stars colored
      stars.forEach(s => s.classList.remove('filled'));
    });

    // Restoring a previously-answered rating (e.g. after a page reload)
    // — lock it in immediately instead of showing it blank/interactive.
    if (selectedIdx !== null) {
      paintUpTo(selectedIdx);
      stars.forEach(s => { s.disabled = true; });
    }

    bubble.appendChild(row);
    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'star_rating', buttons, selectedIndex: selectedIdx });
      persistSession();
    }
  }

  // Displays the current options above the message input.
  function renderQuickReplies(buttons) {
    quickRepliesEl.innerHTML = '';

    if (!buttons || !buttons.length) {
      quickRepliesEl.classList.remove('visible');
      return;
    }

    buttons.forEach(btn => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'quick-reply-btn';
      b.textContent = btn.text;
      b.addEventListener('click', () => {
        sendMessage(btn.value ?? btn.text, btn.text);
      });
      quickRepliesEl.appendChild(b);
    });

    quickRepliesEl.classList.add('visible');
  }

 // Clears the quick-replies bar when needed.
  function clearQuickReplies() {
    quickRepliesEl.innerHTML = '';
    quickRepliesEl.classList.remove('visible');
  }

 // Identifies the main menu buttons and displays them as pinned quick replies.
  const MAIN_MENU_LABELS = new Set([
    'schedule visit',
    'general information',
    'submit review/rating',
    'submit complaint',
  ]);

  function isMainMenuButtons(buttons) {
    return Array.isArray(buttons) && buttons.length === 4 &&
      buttons.every(b => MAIN_MENU_LABELS.has(String(b.text ?? b.value).trim().toLowerCase()));
  }

  // Displays inline options and prevents previously answered choices from being selected again.
  function addInlineButtons(buttons) {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble inline-buttons-bubble';

    buttons.forEach(btn => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'inline-reply-btn';
      b.textContent = btn.text;
      b.addEventListener('click', () => {
        // Once answered, remove the whole button set instead of
        // leaving a muted/disabled copy sitting in the message
        // history — avoids a stale-looking duplicate when the very
        // next reply shows a fresh (and sometimes near-identical) set
        // of options, e.g. the post-appointment menu followed by a
        // Complaint/Review conversation's own main menu.
        messageWrap.remove();
        sendMessage(btn.value ?? btn.text, btn.text);
      });
      bubble.appendChild(b);
    });

    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();
  }

  function addButtons(buttons, persist = true) {
    if (isRatingButtons(buttons)) {
      addStarRating(buttons, persist);
      return;
    }

    if (isMainMenuButtons(buttons)) {
      renderQuickReplies(buttons);
    } else {
      clearQuickReplies();
      addInlineButtons(buttons);
    }

    if (persist) {
      session.history.push({ kind: 'buttons', buttons });
      persistSession();
    }
  }

  function renderCardRow(container, row) {
    const type = row.type || 'text';
    const labelText = row.label;

    if (type === 'multiline') {
      const labelP = document.createElement('p');
      const strong = document.createElement('strong');
      strong.textContent = labelText + ':';
      labelP.appendChild(strong);
      container.appendChild(labelP);

      (Array.isArray(row.value) ? row.value : [row.value]).forEach(line => {
        const lineP = document.createElement('p');
        lineP.className = 'post-appt-subline';
        lineP.textContent = line;
        container.appendChild(lineP);
      });
      return;
    }

    if (type === 'link') {
      const p = document.createElement('p');
      const strong = document.createElement('strong');
      strong.textContent = labelText + ': ';
      p.appendChild(strong);

      const url = String(row.value || '');
      if (/^https?:\/\//i.test(url)) {
        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.className = 'post-appt-link';
        // Long URLs (e.g. Google Maps) read better as a short label.
        a.textContent = row.text || url;
        p.appendChild(a);
      } else {
        p.appendChild(document.createTextNode(url));
      }
      container.appendChild(p);
      return;
    }

    const p = document.createElement('p');
    const strong = document.createElement('strong');
    strong.textContent = labelText + ':';
    p.appendChild(strong);
    p.appendChild(document.createTextNode(' ' + row.value));
    container.appendChild(p);
  }

  function addCard(data, kind, persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';
    messageWrap.style.display = 'flex';
    messageWrap.style.flexDirection = 'column';

    const card = document.createElement('div');
    card.className = 'post-appt-card';

    const title = document.createElement('div');
    title.className = 'post-appt-title';
    // Title can also carry \n / **bold** now (e.g. multi-line card
    // headers), same rendering path as chat bubbles.
    renderBubbleText(title, data.title);
    card.appendChild(title);

    (data.sections || []).forEach(section => {
      const sectionEl = document.createElement('div');
      sectionEl.className = 'post-appt-section';

      (section.rows || []).forEach(row => {
        renderCardRow(sectionEl, row);
      });

      card.appendChild(sectionEl);
    });

    if (data.footer) {
      const footer = document.createElement('p');
      footer.className = 'post-appt-footer-note';
      footer.textContent = data.footer;
      card.appendChild(footer);
    }

    messageWrap.appendChild(card);
    attachTranslateControls(messageWrap, () => card.innerText);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind, data });
      persistSession();
    }
  }

  function addAppointmentCard(data, persist = true) {
    addCard(data, 'card', persist);
  }

  function addInfoCard(data, persist = true) {
    addCard(data, 'info_card', persist);
  }


  // Displays the schedule visit form with personal and visit details.

  let activeForm = null; // { el, entry } for the form currently on screen

  function removeActiveForm() {
    if (!activeForm) return;
    if (activeForm.timer) clearInterval(activeForm.timer);
    activeForm.el.remove();
    if (activeForm.entry) {
      const i = session.history.indexOf(activeForm.entry);
      if (i !== -1) session.history.splice(i, 1);
      persistSession();
    }
    activeForm = null;
  }

  function addFormCard(data, persist = true) {
    // Only one live form at a time (a re-sent form replaces the old one).
    removeActiveForm();

    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot chat-form-wrap';
    messageWrap.style.display = 'flex';
    messageWrap.style.flexDirection = 'column';

    const card = document.createElement('div');
    card.className = 'post-appt-card chat-form-card';

    if (data.step) {
      const step = document.createElement('div');
      step.className = 'chat-form-step';
      step.textContent = data.step;
      card.appendChild(step);
    }

    const title = document.createElement('div');
    title.className = 'post-appt-title';
    title.textContent = data.title || 'Details';
    card.appendChild(title);

    if (data.notice) {
      const notice = document.createElement('p');
      notice.className = 'chat-form-notice';
      notice.textContent = data.notice;
      card.appendChild(notice);
    }

    const form = document.createElement('form');
    form.className = 'chat-form';
    form.noValidate = true; // the server does the validating
    form.autocomplete = 'on';

    const controls = {};   // field name -> input/select element
    const selectDefs = {}; // field name -> field definition (selects only)

    function clearError(group) {
      group.classList.remove('has-error');
      const err = group.querySelector('.chat-form-error');
      if (err) err.remove();
    }

    // Updates dropdown options based on the selected value.
    function fillSelect(name) {
      const def = selectDefs[name];
      const el = controls[name];
      const parentName = def.depends_on;
      const parentVal = parentName ? controls[parentName].value : '';
      const prev = el.value || el.dataset.initial || '';
      el.dataset.initial = '';

      el.innerHTML = '';
      const ph = document.createElement('option');
      ph.value = '';
      ph.textContent = def.placeholder || 'Select';
      el.appendChild(ph);

      const opts = (def.options || []).filter(o => !parentName || (parentVal && o.parent === parentVal));
      opts.forEach(o => {
        const opt = document.createElement('option');
        opt.value = o.value;
        opt.textContent = o.label;
        el.appendChild(opt);
      });

      el.disabled = !!parentName && !parentVal;

      if (opts.length === 1) {
        el.value = opts[0].value;
      } else if (opts.some(o => o.value === prev)) {
        el.value = prev;
      } else {
        el.value = '';
      }

      cascadeFrom(name);
    }

    function cascadeFrom(name) {
      Object.keys(selectDefs)
        .filter(n => selectDefs[n].depends_on === name)
        .forEach(fillSelect);
    }

    (data.fields || []).forEach(f => {
      const id = 'chatForm_' + f.name;
      const group = document.createElement('div');

      if (f.type === 'checkbox') {
        group.className = 'chat-form-check';
        const label = document.createElement('label');
        label.htmlFor = id;
        const box = document.createElement('input');
        box.type = 'checkbox';
        box.id = id;
        box.name = f.name;
        box.checked = f.value === true;
        const text = document.createElement('span');
        text.textContent = f.label;
        label.appendChild(box);
        label.appendChild(text);
        group.appendChild(label);
        controls[f.name] = box;
        form.appendChild(group);
        return;
      }

      group.className = 'chat-form-group' + (f.error ? ' has-error' : '');

      const label = document.createElement('label');
      label.className = 'chat-form-label';
      label.htmlFor = id;
      label.textContent = f.label;

      let field;
      if (f.type === 'select') {
        field = document.createElement('select');
        field.className = 'chat-form-input chat-form-select';
        field.dataset.initial = f.value || '';
        selectDefs[f.name] = f;
      } else {
        field = document.createElement('input');
        field.className = 'chat-form-input';
        field.type = f.type || 'text';
        field.placeholder = f.placeholder || '';
        field.value = f.value || '';
        if (f.maxlength) field.maxLength = f.maxlength;
        if (f.min) field.min = f.min;
        if (f.max) field.max = f.max;
      }
      field.id = id;
      field.name = f.name;
      if (f.autocomplete) field.autocomplete = f.autocomplete;
      if (f.error) field.setAttribute('aria-invalid', 'true');

      // Clear the error styling as soon as the patient edits the field.
      const onEdit = () => {
        clearError(group);
        if (f.type === 'select') cascadeFrom(f.name);
      };
      field.addEventListener(f.type === 'select' ? 'change' : 'input', onEdit);

      group.appendChild(label);
      group.appendChild(field);

      if (f.error) {
        const err = document.createElement('div');
        err.className = 'chat-form-error';
        err.setAttribute('role', 'alert');
        err.textContent = f.error;
        group.appendChild(err);
      }

      controls[f.name] = field;
      form.appendChild(group);
    });

    // Populate the selects (roots first; fillSelect cascades to the rest).
    Object.keys(selectDefs)
      .filter(n => !selectDefs[n].depends_on)
      .forEach(fillSelect);

    // Read-only review of everything the patient entered (review step).
    if (Array.isArray(data.summary)) {
      const summary = document.createElement('div');
      summary.className = 'chat-form-summary';
      data.summary.forEach(sec => {
        const section = document.createElement('div');
        section.className = 'chat-form-summary-section';
        if (sec.heading) {
          const h = document.createElement('div');
          h.className = 'chat-form-summary-heading';
          h.textContent = sec.heading;
          section.appendChild(h);
        }
        (sec.rows || []).forEach(r => {
          const row = document.createElement('div');
          row.className = 'chat-form-summary-row';
          const k = document.createElement('span');
          k.className = 'chat-form-summary-label';
          k.textContent = r.label;
          const v = document.createElement('span');
          v.className = 'chat-form-summary-value';
          v.textContent = r.value;
          row.appendChild(k);
          row.appendChild(v);
          section.appendChild(row);
        });
        summary.appendChild(section);
      });
      form.appendChild(summary);
    }

    const actions = document.createElement('div');
    actions.className = 'chat-form-actions';

    const submit = document.createElement('button');
    submit.type = 'submit';
    submit.className = 'chat-form-submit';
    submit.textContent = data.submit || 'Next';
    actions.appendChild(submit);

    // Secondary buttons (Back / Edit details / Change visit) sit under the main button.
    if (Array.isArray(data.secondary) && data.secondary.length) {
      const row = document.createElement('div');
      row.className = 'chat-form-secondary';
      data.secondary.forEach(btn => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chat-form-back';
        b.textContent = btn.label;
        b.addEventListener('click', () => {
          if (isSending) return;
          sendMessage(btn.value, '', { skipUserBubble: true });
        });
        row.appendChild(b);
      });
      actions.appendChild(row);
    }

    form.appendChild(actions);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (isSending) return;

      const values = {};
      Object.keys(controls).forEach(k => {
        const el = controls[k];
        values[k] = el.type === 'checkbox' ? el.checked : (el.value || '').trim();
      });
      if (data.confirm) values.confirm = true;

      form.querySelectorAll('input, select, button').forEach(el => { el.disabled = true; });

      // Prevents duplicate patient messages when the next step or form is displayed.
      const ok = await sendMessage(FORM_SUBMIT_PREFIX + JSON.stringify(values), '', {
        skipUserBubble: true,
        showSaving: !!data.saving,
      });
      if (!ok && activeForm && activeForm.el === messageWrap) {
        // Request failed: let the patient try again.
        form.querySelectorAll('input, select, button').forEach(el => { el.disabled = false; });
      }
    });

    card.appendChild(form);

    if (data.footer) {
      const footer = document.createElement('p');
      footer.className = 'chat-form-footer';
      footer.textContent = data.footer;
      card.appendChild(footer);
    }

    messageWrap.appendChild(card);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    let entry = null;
    if (persist) {
      entry = { kind: 'form', data };
      session.history.push(entry);
      persistSession();
    }

    activeForm = { el: messageWrap, entry };

    // Updates the available schedule visit options while the form is open.
    if (selectDefs.service && selectDefs.doctor && selectDefs.slot) {
      const thisForm = activeForm;
      let lastSig = JSON.stringify([selectDefs.service.options, selectDefs.doctor.options, selectDefs.slot.options]);

      const showNotice = (text) => {
        let n = card.querySelector('.chat-form-notice');
        if (!n) {
          n = document.createElement('p');
          n.className = 'chat-form-notice';
          card.insertBefore(n, form);
        }
        n.textContent = text;
      };

      const refreshCatalog = async () => {
        if (document.hidden || activeForm !== thisForm || submit.disabled || isSending) return;
        try {
          const res = await fetch('/api/schedule-visit-catalog', { cache: 'no-store', headers: { 'Accept': 'application/json' } });
          if (!res.ok) return;
          const cat = await res.json();

          const sig = JSON.stringify([cat.services, cat.doctors, cat.slots]);
          if (sig === lastSig) return;
          lastSig = sig;

          const before = { service: controls.service.value, doctor: controls.doctor.value, slot: controls.slot.value };

          selectDefs.service.options = cat.services || [];
          selectDefs.doctor.options  = cat.doctors  || [];
          selectDefs.slot.options    = cat.slots    || [];
          fillSelect('service'); // cascades to doctor -> slot, keeps the choice if it is still valid

          const changed = ['service', 'doctor', 'slot'].some(k => before[k] && controls[k].value !== before[k]);
          if (changed) {
            showNotice('The available doctors and schedules were just updated. Please review your choice.');
          }
        } catch (e) { /* offline o server error: subukan ulit sa susunod */ }
      };

      thisForm.timer = setInterval(refreshCatalog, 8000);
      document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshCatalog(); });
    }

    // Put the cursor in the first field with an error, else the first field.
    const firstBad = form.querySelector('.has-error input, .has-error select') ||
      form.querySelector('input:not([type=checkbox]), select:not(:disabled)');
    if (firstBad && panel.classList.contains('open')) {
      setTimeout(() => firstBad.focus({ preventScroll: true }), 50);
    }
  }

  function addAttachmentBubble(fileName, dataUrl, sender = 'user', persist = true, caption = null) {
    const messageWrap = document.createElement('div');
    messageWrap.className = `chat-message ${sender}`;

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble attachment-bubble';

    if (caption) {
      const captionEl = document.createElement('div');
      captionEl.className = 'chat-attachment-caption-text';
      captionEl.textContent = caption;
      bubble.appendChild(captionEl);
    }

    if (dataUrl) {
      const img = document.createElement('img');
      img.src = dataUrl;
      img.alt = fileName;
      img.className = 'chat-attachment-image';
      bubble.appendChild(img);

      const fileLabel = document.createElement('div');
      fileLabel.className = 'chat-attachment-caption';
      fileLabel.textContent = fileName;
      bubble.appendChild(fileLabel);
    } else {
      const fileLabel = document.createElement('div');
      fileLabel.className = 'chat-attachment-caption';
      fileLabel.textContent = `Attached: ${fileName}`;
      bubble.appendChild(fileLabel);
    }

    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'attachment', fileName, dataUrl, sender, caption });
      persistSession();
    }

    return bubble;
  }


  function restoreConversation() {
    if (session.history.length === 0) {
      addBubble('Hello! Welcome to PolyClinic Lipa. How can I help you today?', 'bot');
      addButtons([
        { text: 'Schedule Visit', value: 'schedule visit' },
        { text: 'General Information', value: 'general information' },
        { text: 'Submit Review/Rating', value: 'submit review/rating' },
        { text: 'Submit Complaint', value: 'submit complaint' },
      ]);
      return;
    }

    session.history.forEach((item, idx) => {
      const isLast = idx === session.history.length - 1;

      if (item.kind === 'bubble') {
        addBubble(item.text, item.sender, false);
      } else if (item.kind === 'buttons') {
        // Restores only the latest unanswered quick-reply options.
        if (isLast) {
          addButtons(item.buttons, false);
        }
      } else if (item.kind === 'star_rating') {
        addStarRating(item.buttons, false, item.selectedIndex ?? null);
      } else if (item.kind === 'card') {
        addAppointmentCard(item.data, false);
      } else if (item.kind === 'info_card') {
        addInfoCard(item.data, false);
      } else if (item.kind === 'form') {
        // Only an unanswered form (the latest item) is worth restoring.
        if (isLast) {
          addFormCard(item.data, false);
          if (activeForm) activeForm.entry = item;
        }
      } else if (item.kind === 'attachment') {
        addAttachmentBubble(item.fileName, item.dataUrl, item.sender, false, item.caption);
      }
    });
    scrollToBottom();
  }

  // Shows the typing indicator only when Admin/Staff is actively composing a reply.
  function showTyping() {
    if (document.getElementById('typingIndicator')) return;

    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';
    messageWrap.id = 'typingIndicator';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';

    const dotsWrap = document.createElement('div');
    dotsWrap.className = 'typing-dots';
    for (let i = 0; i < 3; i++) {
      const dot = document.createElement('span');
      dot.className = 'dot';
      dotsWrap.appendChild(dot);
    }
    bubble.appendChild(dotsWrap);

    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();
  }

  function hideTyping() {
    const el = document.getElementById('typingIndicator');
    if (el) el.remove();
  }

  async function sendMessage(text, displayText, opts = {}) {
    text = typeof text === 'string' ? text : String(text ?? '');
    if (!text.trim() || isSending) return false;

    setSendingState(true);

    if (!opts.skipUserBubble) {
      addBubble(displayText ?? text, 'user');
    }
    input.value = '';

    // Shows the saving message only while the schedule visit is being saved.
    let savingEl = null;
    if (text === 'confirm_yes' || opts.showSaving) {
      const b = addBubble('Saving your schedule visit...', 'bot', false);
      savingEl = b.closest('.chat-message');
    }
    const removeSaving = () => { if (savingEl) { savingEl.remove(); savingEl = null; } };

    // Prevents the typing indicator from appearing for automated bot replies.

    try {
      const res = await fetch(botmanUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          driver: 'web',
          message: text,
          userId: session.clientId,
          startReviewFromPass: opts.startReviewFromPass === true,
        }),
      });

      const data = await res.json();
      removeSaving();

      if (!res.ok) {
        if (res.status === 429) {
          addErrorBubble("You're sending messages a bit too quickly. Please wait a moment and try again.");
        } else {
          addErrorBubble('Something went wrong reaching the chatbot. Please try again.');
        }
        return false;
      }

      const messages = Array.isArray(data) ? data : (data.messages || []);
      let hadButtons = false;
      let hadForm = false;

      messages.forEach(msg => {
        if (msg.text) {
          if (msg.text.startsWith(APPT_CARD_PREFIX)) {
            try {
              const cardData = JSON.parse(msg.text.slice(APPT_CARD_PREFIX.length));
              addAppointmentCard(cardData);
            } catch (e) {
              console.error('Failed to parse appointment card payload:', e);
              addBubble(msg.text, 'bot');
            }
          } else if (msg.text.startsWith(FORM_CARD_PREFIX)) {
            try {
              const formData = JSON.parse(msg.text.slice(FORM_CARD_PREFIX.length));
              addFormCard(formData);
              hadForm = true;
            } catch (e) {
              console.error('Failed to parse form payload:', e);
              addBubble('Sorry, the form could not be loaded. Please try again.', 'bot');
            }
          } else if (msg.text.startsWith(INFO_CARD_PREFIX)) {
            try {
              const cardData = JSON.parse(msg.text.slice(INFO_CARD_PREFIX.length));
              addInfoCard(cardData);
            } catch (e) {
              console.error('Failed to parse info card payload:', e);
              addBubble(msg.text, 'bot');
            }
          } else {
            addBubble(msg.text, 'bot');
          }
        }

        const buttons = msg.actions || (msg.attachment && msg.attachment.buttons);
        if (buttons && buttons.length) {
          addButtons(buttons);
          hadButtons = true;
        }
      });

      // Removes the outdated form when the conversation moves to another step.
      if (!hadForm) {
        removeActiveForm();
      }

      // Clears the pinned buttons when the current step has no options.
      if (!hadButtons) {
        clearQuickReplies();
      }

      return true;

    } catch (err) {
      removeSaving();
      console.error('Chat error:', err);
      addErrorBubble('Unable to connect. Please check your internet connection and try again.');
      return false;
    } finally {
      removeSaving();
      setSendingState(false);
    }
  }

  // Event listeners
  sendBtn.addEventListener('click', handleSend);

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleSend();
    }
  });

  // Allows patients to type messages without clicking the message box first.
  document.addEventListener('keydown', (e) => {
    if (!panel.classList.contains('open')) return;
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key.length !== 1) return; // ignore Tab, arrows, Enter, etc.

    const active = document.activeElement;
    const typingElsewhere = active && active !== input &&
      (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' ||
       active.tagName === 'SELECT' || active.isContentEditable);
    if (typingElsewhere) return; // don't hijack other form fields on the page

    if (!input.disabled && active !== input) input.focus({ preventScroll: true });
  });

  // Prevents buttons inside the panel from keeping focus after being clicked.
  panel.addEventListener('click', (e) => {
    if (e.target.closest('a, .msg-translate-btn, .attach-btn, #chatClose, .chat-form')) return;
    setTimeout(focusInput, 0);
  });

  // File attachment (optional)
  attachBtn.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    if (fileInput.files.length === 0) return;
    const file = fileInput.files[0];

    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = () => showAttachmentPreview(file, reader.result);
      reader.readAsDataURL(file);
    } else {
      showAttachmentPreview(file, null);
    }
  });


  // Removes the pending message placeholders after an Admin/Staff reply arrives.
  const FALLBACK_TEXTS = new Set([
    "Thanks for your message! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.",
    "Thanks for the attachment! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.",
  ]);
  let shownReplyIds = new Set(session.shownReplyIds || []);
  let shownResolvedIds = new Set(session.shownResolvedIds || []);

  function removeFallbackBubbles() {
    document.querySelectorAll('.chat-message.bot .chat-bubble').forEach(bubble => {
      if (FALLBACK_TEXTS.has(bubble.textContent)) {
        bubble.closest('.chat-message').remove();
      }
    });
    session.history = session.history.filter(
      item => !(item.kind === 'bubble' && FALLBACK_TEXTS.has(item.text))
    );
    persistSession();
  }

  // Restores the main menu after the inquiry is resolved.
  function showMainMenu() {
    addButtons([
      { text: 'Schedule Visit', value: 'schedule visit' },
      { text: 'General Information', value: 'general information' },
      { text: 'Submit Review/Rating', value: 'submit review/rating' },
      { text: 'Submit Complaint', value: 'submit complaint' },
    ]);
  }

  // Prevents multiple polling requests from running at the same time.
  let isPolling = false;

  async function pollForReplies() {
    if (isPolling) return;
    isPolling = true;
    try {
      const res = await fetch(`/api/chat/${session.clientId}/updates`);
      if (!res.ok) return;
      const data = await res.json();
      const updates = data.updates || [];
      const resolvedIds = data.resolvedInquiryIds || [];

      const newOnes = updates.filter(u => !shownReplyIds.has(u.replyId));
      const newlyResolved = resolvedIds.filter(id => !shownResolvedIds.has(id));
      const resolvedTypeById = {};
      (data.resolvedInquiries || []).forEach(r => { resolvedTypeById[r.id] = r.type; });

      // Shows the typing indicator only when Admin/Staff is actively composing a reply.
      if (data.staffTyping && newOnes.length === 0) {
        showTyping();
      } else {
        hideTyping();
      }

      if (newOnes.length === 0 && newlyResolved.length === 0) return;

      if (newOnes.length > 0) {
        removeFallbackBubbles();

        newOnes.forEach(u => {
          addBubble(u.message, 'bot');
          shownReplyIds.add(u.replyId);
        });

        session.shownReplyIds = Array.from(shownReplyIds);
      }

      // Returns the patient to the main menu after the inquiry is resolved.
      if (newlyResolved.length > 0) {
        newlyResolved.forEach(id => shownResolvedIds.add(id));
        session.shownResolvedIds = Array.from(shownResolvedIds);

        // Shows the scheduling thank-you message after the inquiry is resolved.
        const fromSchedule = newlyResolved.some(id => resolvedTypeById[id] === 'Schedule Visit Follow-up');
        // Combines the resolved notice and follow-up prompt into one message.
        addBubble((fromSchedule
          ? "Thank you for scheduling your visit with us."
          : "Your inquiry has been resolved.") + " Is there anything else you need?", 'bot');
        addButtons([
          { text: "Yes, I need help", value: 'anything_else_yes' },
          { text: "No, I'm all set",  value: 'anything_else_no' },
        ]);
      }

      persistSession();
    } catch (err) {
      console.error('Poll error:', err);
    } finally {
      isPolling = false;
    }
  }

// Updates the typing indicator every 3 seconds.
  setInterval(pollForReplies, 3000);

  // Global trigger: open chat + jump straight to Submit Feedback
  window.openChatbotAndSubmitFeedback = async function (transactionCode = '') {
    if (!panel.classList.contains('open')) {
      panel.classList.add('open');
    }
    input.focus();
    scrollToBottom();
    pollForReplies();
    const started = await sendMessage('submit review/rating', 'Submit Review/Rating', { startReviewFromPass: !!transactionCode });
    if (transactionCode) {
      if (started) {
        const verified = await sendMessage(transactionCode, 'Review transaction code entered');
        if (!verified) input.value = transactionCode;
      } else {
        input.value = transactionCode;
      }
    }
  };

  // Global trigger: open chat + jump straight to Schedule Visit
  window.openChatbotAndScheduleVisit = function () {
    if (!panel.classList.contains('open')) {
      panel.classList.add('open');
    }
    input.focus();
    scrollToBottom();
    pollForReplies();
    sendMessage('schedule visit', 'Schedule Visit');
  };

  // Initialization
  restoreConversation();
  persistSession();
  pollForReplies();
  const reviewLink = new URLSearchParams(window.location.hash.slice(1));
  const reviewCode = (reviewLink.get('review-code') || '').toUpperCase();
  if (/^(?:[A-Z0-9]{20}|[A-F0-9]{32})$/.test(reviewCode)) {
    // Remove the private pass from the visible URL before opening the review flow.
    history.replaceState(history.state, '', window.location.pathname + window.location.search);
    window.openChatbotAndSubmitFeedback(reviewCode);
  }
});
</script>

</body>
</html>