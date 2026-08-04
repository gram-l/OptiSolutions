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
  <style>
    /* Attachment bubble uses a light background instead of the big
       solid-blue chat bubble, so the blue only shows as a thin accent
       border, not a large colored block behind the file/image. */
    .chat-message.user .chat-bubble.attachment-bubble,
    .chat-message.bot .chat-bubble.attachment-bubble {
      background: #F8FAFE;
      border: 1px solid #DCE7F5;
      color: #1F2A3A;
      padding: 8px;
    }
    .chat-attachment-image {
      max-width: 220px;
      max-height: 220px;
      border-radius: 12px;
      display: block;
      object-fit: cover;
    }
    .chat-attachment-caption {
      font-size: 12px;
      color: #6b7280;
      margin-top: 6px;
      word-break: break-all;
    }
    /* The patient's typed question, shown above the file inside the
       same bubble, so text + attachment are sent/read as one message. */
    .chat-attachment-caption-text {
      font-size: 14px;
      line-height: 1.45;
      color: #1F2A3A;
      padding: 2px 4px 8px;
      word-break: break-word;
    }
    .attachment-preview {
      display: none;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      margin: 0 12px;
      background: #f3f4f6;
      border-radius: 10px;
      font-size: 13px;
      color: #374151;
    }
    .attachment-preview-thumb {
      width: 36px;
      height: 36px;
      object-fit: cover;
      border-radius: 6px;
      flex-shrink: 0;
    }
    .attachment-preview-name {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .attachment-preview-remove {
      background: none;
      border: none;
      font-size: 18px;
      line-height: 1;
      color: #6b7280;
      cursor: pointer;
      padding: 2px 6px;
    }
    .attachment-preview-remove:hover {
      color: #374151;
    }

    /* ---------- Star Rating ---------- */
    .star-rating-bubble {
      padding: 10px 6px;
      background: transparent;
      box-shadow: none;
    }
    .star-rating-row {
      display: flex;
      gap: 10px;
      justify-content: center;
      padding: 6px 4px;
    }
    .star-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 32px;
      line-height: 1;
      color: #d1d5db;
      padding: 2px;
      transition: color 0.15s ease, transform 0.15s ease;
    }
    .star-btn:hover {
      transform: scale(1.15);
    }
    .star-btn.filled {
      color: #fbbf24;
    }

    /* ---------- Per-message translate (WeChat/Messenger style) ---------- */
    .msg-translate-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 11px;
      font-weight: 600;
      color: #9ca3af;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 4px 0 4px;
      margin: 0;
    }
    .msg-translate-btn:hover {
      color: #1e3a8a;
    }
    .msg-translate-btn i {
      font-size: 11px;
    }
    .chat-message.user .msg-translate-btn {
      align-self: flex-end;
    }
    .chat-message.bot .msg-translate-btn {
      align-self: flex-start;
    }
    .msg-translation-box {
      display: none;
      position: absolute;
      top: calc(100% + 2px);
      padding: 8px 26px 8px 10px;
      background: #ffffff;
      border-radius: 10px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 6px 18px rgba(0,0,0,0.14);
      font-size: 13px;
      line-height: 1.4;
      color: #374151;
      max-width: 240px;
      width: max-content;
      z-index: 30;
    }
    .chat-message.user .msg-translation-box {
      right: 0;
    }
    .chat-message.bot .msg-translation-box {
      left: 0;
    }
    .msg-translation-box.visible {
      display: block;
    }
    .msg-translation-collapse {
      position: absolute;
      top: 6px;
      right: 8px;
      background: none;
      border: none;
      color: #9ca3af;
      cursor: pointer;
      font-size: 11px;
      padding: 0;
    }
    .msg-translation-collapse:hover {
      color: #374151;
    }
  </style>
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

  const APPT_CARD_PREFIX = 'APPT_CARD::';
  const INFO_CARD_PREFIX = 'INFO_CARD::';

  // PER-MESSAGE TRANSLATION (auto-detect, WeChat/Messenger style)
  // Backend auto-detects Tagalog vs English and translates to the
  // counterpart language using the free MyMemory API — no key needed.

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

  // Attaches a small "Translate" button under a bubble. Clicking it
  // fetches the translation once, then shows/hides it inline below
  // the original message — same behavior as the reference screenshots.
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

  function acknowledgeAttachment() {
    return new Promise((resolve) => {
      showTyping();
      setTimeout(() => {
        hideTyping();
        addBubble('Thank you for the attachment! Our staff will review it and get back to you shortly.', 'bot');
        resolve();
      }, 600);
    });
  }

  async function handleSend() {
    const text = input.value.trim();
    const attachment = pendingAttachment;

    if (!text && !attachment) return;

    if (attachment) {
      // Show the caption text together WITH the attachment, in a single
      // combined bubble (text on top, attachment below) — instead of two
      // separate messages — so the patient's question travels together
      // with the file.
      addAttachmentBubble(attachment.file.name, attachment.dataUrl, 'user', true, text || null);
      clearAttachmentPreview();
      input.value = '';

      if (text) {
        // Send that same caption text to the bot so it can read and
        // answer the patient's actual question. skipUserBubble avoids
        // re-adding a duplicate text bubble, since it's already shown
        // above inside the attachment bubble.
        await sendMessage(text, text, { skipUserBubble: true });
      } else {
        await acknowledgeAttachment();
      }
      return;
    }

    if (text) {
      sendMessage(text);
    }
  }

  // ---------- Panel open/close ----------
  trigger.addEventListener('click', () => {
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
      input.focus();
      scrollToBottom();
      pollForReplies(); // Check for new replies immediately whenever the panel is opened.
    }
  });

  closeBtn.addEventListener('click', () => {
    panel.classList.remove('open');
  });

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function addBubble(text, sender = 'bot', persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = `chat-message ${sender}`;
    messageWrap.style.display = 'flex';
    messageWrap.style.flexDirection = 'column';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.textContent = text;

    messageWrap.appendChild(bubble);
    attachTranslateControls(messageWrap, () => bubble.textContent);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'bubble', text, sender });
      persistSession();
    }

    return bubble;
  }

  // ---------- Star rating helpers ----------
  function isRatingButtons(buttons) {
    return Array.isArray(buttons) && buttons.length === 5 &&
      buttons.every((b, i) => String(b.value ?? b.text).trim() === String(i + 1));
  }

  function addStarRating(buttons, persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble star-rating-bubble';

    const row = document.createElement('div');
    row.className = 'star-rating-row';

    const stars = [];

    buttons.forEach((btn, idx) => {
      const starBtn = document.createElement('button');
      starBtn.type = 'button';
      starBtn.className = 'star-btn';
      starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';

      starBtn.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => s.classList.toggle('filled', i <= idx));
      });

      starBtn.addEventListener('click', () => {
        const filledLabel = '★'.repeat(idx + 1) + '☆'.repeat(5 - (idx + 1));
        sendMessage(btn.value ?? btn.text, filledLabel);
      });

      stars.push(starBtn);
      row.appendChild(starBtn);
    });

    row.addEventListener('mouseleave', () => {
      stars.forEach(s => s.classList.remove('filled'));
    });

    bubble.appendChild(row);
    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'star_rating', buttons });
      persistSession();
    }
  }

  function addButtons(buttons, persist = true) {
    if (isRatingButtons(buttons)) {
      addStarRating(buttons, persist);
      return;
    }

    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble menu-grid';

    buttons.forEach(btn => {
      const b = document.createElement('button');
      b.className = 'menu-btn';
      b.textContent = btn.text;
      b.addEventListener('click', () => {
        sendMessage(btn.value ?? btn.text, btn.text);
      });
      bubble.appendChild(b);
    });

    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

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
    title.textContent = data.title;
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

    // Caption text goes FIRST, above the file/image, inside the same
    // bubble — so the patient's question and the attachment appear as
    // one combined message instead of two separate bubbles.
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

    session.history.forEach(item => {
      if (item.kind === 'bubble') {
        addBubble(item.text, item.sender, false);
      } else if (item.kind === 'buttons') {
        addButtons(item.buttons, false);
      } else if (item.kind === 'star_rating') {
        addStarRating(item.buttons, false);
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

  // ---------- Typing indicator ----------
  function showTyping() {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';
    messageWrap.id = 'typingIndicator';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.textContent = '...';

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
    if (!text.trim()) return;

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
        addBubble('Something went wrong reaching the chatbot. Please try again.', 'bot');
        return;
      }

      const messages = Array.isArray(data) ? data : (data.messages || []);

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
        }
      });

    } catch (err) {
      hideTyping();
      console.error('Chat error:', err);
      addBubble('Unable to connect. Please check your internet connection and try again.', 'bot');
    }
  }

  // ---------- Event listeners ----------
  sendBtn.addEventListener('click', handleSend);

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleSend();
    }
  });

  // ---------- File attachment (optional) ----------
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

  // ---------- Global trigger: open chat + jump straight to Submit Feedback ----------
  // Ginagamit ito ng "Leave your Feedback here!" link sa home page para direktang
  // pumasok sa "Submit Review/Rating" flow, na parang na-click ang menu button.
  window.openChatbotAndSubmitFeedback = function () {
    if (!panel.classList.contains('open')) {
      panel.classList.add('open');
    }
    input.focus();
    scrollToBottom();
    pollForReplies();
    sendMessage('submit review/rating', 'Submit Review/Rating');
  };

  // ---------- Initialization ----------
  restoreConversation();
  persistSession();
  pollForReplies(); // Check immediately on load, in case a reply arrived while away.
});
</script>

</body>
</html>