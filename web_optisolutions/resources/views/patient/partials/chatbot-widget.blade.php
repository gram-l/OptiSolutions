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
      };
    }

    if (!saved.shownReplyIds) saved.shownReplyIds = [];

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
    // disabled for the whole upload + typing-indicator window so the
    // patient can't fire off more messages while we're still waiting
    // on this one (avoids spamming the bot/admin/staff).
    setSendingState(true);
    showTyping();
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
      hideTyping();

      if (!res.ok || !data.success) {
        addErrorBubble(data.error || 'Something went wrong uploading the attachment. Please try again.');
        return;
      }

      addBubble("Thanks for the attachment! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.", 'bot');
    } catch (e) {
      hideTyping();
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

  // Renders text into a bubble/title element, converting **bold** markers
  // into real <strong> tags. HTML-escapes first so nothing from the
  // backend (or, indirectly, from patient input reflected back) can
  // inject markup — only the ** syntax we control from PHP is honored.
  // Combined with `white-space: pre-line` in CSS, plain \n line breaks
  // in the source text also render as real line breaks instead of being
  // collapsed into one paragraph.
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
    // Pass the original (un-rendered) text to the translator, not the
    // bubble's innerHTML/textContent, so ** markers don't leak into
    // what gets sent for translation.
    attachTranslateControls(messageWrap, () => text);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'bubble', text, sender });
      persistSession();
    }

    return bubble;
  }

  // Error bubble: same as addBubble() but with an inline "Refresh"
  // action so the patient has an easy way to recover from a sudden
  // error (dropped connection, server hiccup, failed upload) instead
  // of being stuck. Not persisted to history — it's a transient
  // notice, re-showing a stale "please refresh" bubble on reload
  // would be confusing.
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

  // Auto-refresh once connectivity is back. If the patient's connection
  // actually drops mid-chat, there's no reliable way to resume the
  // in-flight request, so the simplest, least-confusing recovery is to
  // reload the page as soon as the browser reports we're back online —
  // the chat history itself is preserved via localStorage (session.history).
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
    // Once a star is picked, the fill locks in and further hover/click
    // is ignored — this replaces the old behavior where mouseleave
    // wiped every star back to unfilled right after a choice was made.
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

  // Renders the current option set as a pinned "quick replies" row
  // sitting above the input box (Messenger-style), instead of inside
  // the scrolling message list — so the patient never has to scroll
  // up to find the buttons for the question that was just asked.
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

  // Clears the quick-replies bar (used when a new question comes in
  // with no button options at all, or when starting a fresh
  // conversation) — but the bar is no longer auto-cleared just
  // because the patient answered; it stays visible until the next
  // set of options replaces it.
  function clearQuickReplies() {
    quickRepliesEl.innerHTML = '';
    quickRepliesEl.classList.remove('visible');
  }

  // Recognizes the top-level 4-button main menu (Schedule Visit /
  // General Information / Submit Review/Rating / Submit Complaint),
  // regardless of which conversation produced it or which value
  // convention it uses under the hood. AppointmentConversation's
  // post-appointment menu, for example, reuses the same 4 labels but
  // with its own distinct values ('post_schedule_visit', etc.), so
  // this matches on the visible label text rather than the value —
  // any button set with these exact 4 labels always gets the pinned
  // "quick replies" treatment. Every other button set (service list,
  // doctor list, schedule confirmation, star rating, etc.) renders
  // inline instead, see addInlineButtons() below.
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

  // Renders a button set inline, as part of the scrolling message
  // list right under the question that was just asked — for service
  // lists, doctor lists, schedule confirmation, and any other
  // non-main-menu option set. Once one option is tapped, every button
  // in the set is disabled so an earlier, already-answered set can't
  // be re-clicked out of order.
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
        a.textContent = url;
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
        // Only the most recent, still-unanswered option set gets
        // restored into the live quick-replies bar. Earlier button
        // sets in the history were already answered (the next item
        // is always the patient's reply), so re-showing them would
        // offer stale, already-actioned choices.
        if (isLast) {
          addButtons(item.buttons, false);
        }
      } else if (item.kind === 'star_rating') {
        addStarRating(item.buttons, false, item.selectedIndex ?? null);
      } else if (item.kind === 'card') {
        addAppointmentCard(item.data, false);
      } else if (item.kind === 'info_card') {
        addInfoCard(item.data, false);
      } else if (item.kind === 'attachment') {
        addAttachmentBubble(item.fileName, item.dataUrl, item.sender, false, item.caption);
      }
    });
    scrollToBottom();
  }

  // Typing indicator
  function showTyping() {
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
    if (!text.trim() || isSending) return;

    setSendingState(true);

    if (!opts.skipUserBubble) {
      addBubble(displayText ?? text, 'user');
    }
    input.value = '';
    showTyping();

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
        }),
      });

      const data = await res.json();
      hideTyping();

      if (!res.ok) {
        if (res.status === 429) {
          addErrorBubble("You're sending messages a bit too quickly. Please wait a moment and try again.");
        } else {
          addErrorBubble('Something went wrong reaching the chatbot. Please try again.');
        }
        return;
      }

      const messages = Array.isArray(data) ? data : (data.messages || []);
      let hadButtons = false;

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

      // The step we just landed on has no button options of its own
      // (e.g. "Please provide your full name.") — clear the pinned
      // bar instead of leaving the previous step's buttons sitting
      // there looking clickable when they no longer apply.
      if (!hadButtons) {
        clearQuickReplies();
      }

    } catch (err) {
      hideTyping();
      console.error('Chat error:', err);
      addErrorBubble('Unable to connect. Please check your internet connection and try again.');
    } finally {
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


  const FALLBACK_TEXT = "Thanks for your message! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.";
  let shownReplyIds = new Set(session.shownReplyIds || []);

  function removeFallbackBubbles() {
    document.querySelectorAll('.chat-message.bot .chat-bubble').forEach(bubble => {
      if (bubble.textContent === FALLBACK_TEXT) {
        bubble.closest('.chat-message').remove();
      }
    });
    session.history = session.history.filter(
      item => !(item.kind === 'bubble' && item.text === FALLBACK_TEXT)
    );
    persistSession();
  }

  async function pollForReplies() {
    try {
      const res = await fetch(`/api/chat/${session.clientId}/updates`);
      if (!res.ok) return;
      const data = await res.json();
      const updates = data.updates || [];

      const newOnes = updates.filter(u => !shownReplyIds.has(u.replyId));
      if (newOnes.length === 0) return;

      removeFallbackBubbles();

      newOnes.forEach(u => {
        addBubble(u.message, 'bot');
        shownReplyIds.add(u.replyId);
      });

      session.shownReplyIds = Array.from(shownReplyIds);
      persistSession();
    } catch (err) {
      console.error('Poll error:', err);
    }
  }

  setInterval(pollForReplies, 5000);

  // Global trigger: open chat + jump straight to Submit Feedback
  window.openChatbotAndSubmitFeedback = function () {
    if (!panel.classList.contains('open')) {
      panel.classList.add('open');
    }
    input.focus();
    scrollToBottom();
    pollForReplies();
    sendMessage('submit review/rating', 'Submit Review/Rating');
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
});
</script>

</body>
</html>