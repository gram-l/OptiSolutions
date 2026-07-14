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
  <!--
    NOTE: These attachment-preview rules are kept here inline since
    chatbot.css isn't part of this file. Feel free to move them into
    chatbot.css instead — the class names are namespaced (chat-attachment-*)
    so they won't collide with anything existing.
  -->
  <style>
    .chat-bubble.attachment-bubble {
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
  </style>
</head>
<body>

<!-- Trigger button -->
<button class="chatbot-trigger" id="chatbotTrigger">
  <i class="fa-solid fa-comment-medical"></i>
</button>

<!-- Chat panel -->
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

  <!-- Shown when a file has been selected but not yet sent, so the
       patient can still type a message and/or remove the attachment
       before sending. -->
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

<div class="footer-note">
  <a href="{{ route('home') }}">← Back to PolyClinic Lipa</a>
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


  // SESSION PERSISTENCE
  
  const STORAGE_KEY = 'polyclinic_chat_state';
  const SESSION_TIMEOUT_MS = 2 * 60 * 60 * 1000; // 2 hours

  /**
   * Generates a unique client identifier, used to associate this
   * browser session with its corresponding BotMan conversation
   * state on the server.
   */
  function generateClientId() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
      const r = Math.random() * 16 | 0;
      const v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  /**
   * Loads the persisted session from localStorage. If no session
   * exists, or the last recorded activity exceeds the inactivity
   * timeout, a fresh session is created instead.
   */
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
        history: [], // Array of { kind: 'bubble' | 'buttons' | 'card', ... }
      };
    }

    return saved;
  }

  let session = loadSession();

  /**
   * Persists the current session state and refreshes the
   * last-activity timestamp.
   */
  function persistSession() {
    session.lastActivity = Date.now();
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
  }

  // ============================================================
  // PENDING ATTACHMENT (staged, not yet sent)
  //
  // Selecting a file no longer sends it immediately. It's held here
  // until the patient hits send, so they can still type a message
  // before or after attaching a photo, or remove it before sending.
  // ============================================================
  let pendingAttachment = null; // { file, dataUrl } | null

  function showAttachmentPreview(file, dataUrl) {
    pendingAttachment = { file, dataUrl };

    if (dataUrl) {
      attachmentPreviewImg.src = dataUrl;
      attachmentPreviewImg.style.display = 'block';
    } else {
      // Non-image file: nothing to thumbnail, just hide the image slot.
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
    fileInput.value = ''; // Allows re-selecting the same file later.
  }

  attachmentPreviewRemove.addEventListener('click', clearAttachmentPreview);

  /**
   * Shows the "staff will review" acknowledgment for an attachment.
   * Returns a Promise that resolves once the bubble has been shown,
   * so callers (handleSend) can await it before continuing on to a
   * follow-up backend call — this keeps the two typing indicators
   * from overlapping when both an attachment AND typed text are
   * sent together.
   */
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

  /**
   * Called on send. Sends whatever combination of typed text and a
   * staged attachment is currently present:
   *   - attachment (with or without text) → local attachment bubble,
   *     ALWAYS followed by the "Our staff will review..." ack.
   *   - if text is also present, it's sent afterwards through the
   *     normal backend (BotMan) flow via sendMessage().
   *   - text only → normal sendMessage() flow (hits backend), same
   *     as before.
   */
  async function handleSend() {
    const text = input.value.trim();
    const attachment = pendingAttachment;

    if (!text && !attachment) return;

    if (attachment) {
      addAttachmentBubble(attachment.file.name, attachment.dataUrl, 'user');
      clearAttachmentPreview();
      await acknowledgeAttachment(); // Always shown when there's an attachment, text or no text.
    }

    if (text) {
      sendMessage(text); // Existing flow: renders bubble + calls the backend.
    }
  }

  // ---------- Panel open/close ----------
  trigger.addEventListener('click', () => {
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
      input.focus();
      scrollToBottom();
    }
  });

  closeBtn.addEventListener('click', () => {
    panel.classList.remove('open');
  });

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  /**
   * Renders a single chat bubble.
   *
   * Uses textContent rather than innerHTML so that:
   *   1. Newlines are respected via the `white-space: pre-line` CSS rule.
   *   2. User-supplied text cannot introduce an XSS vector.
   *
   * @param {string}  text
   * @param {string}  sender  'bot' | 'user'
   * @param {boolean} persist Whether to record this bubble in the
   *                          session history. Set to false when
   *                          replaying history that is already stored.
   */
  function addBubble(text, sender = 'bot', persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = `chat-message ${sender}`;

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.textContent = text;

    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'bubble', text, sender });
      persistSession();
    }

    return bubble;
  }

  /**
   * Renders a set of quick-reply buttons, as sent by a BotMan
   * Question object.
   *
   * @param {Array}   buttons
   * @param {boolean} persist Whether to record these buttons in the
   *                          session history.
   */
  function addButtons(buttons, persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble menu-grid';

    buttons.forEach(btn => {
      const b = document.createElement('button');
      b.className = 'menu-btn';
      b.textContent = btn.text;
      b.addEventListener('click', () => {
        // FIX: previously called sendMessage(btn.value ?? btn.text), which
        // used the SAME string both as the value sent to the backend AND
        // as the text shown in the user's chat bubble. For buttons like
        // the doctor list, `value` is a numeric doctor_id (needed by the
        // backend to look up the doctor), while `text` is the human-
        // readable label ("Dr. ... — Pediatrics"). That mismatch is why
        // the chat bubble showed a bare number ("2") instead of the
        // doctor's name.
        //
        // sendMessage() now takes an optional second "displayText"
        // argument so the bubble can show the button's label while the
        // backend still receives the value it actually needs.
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

  /**
   * Renders a single row inside a card section. Row shape:
   *   { label, value, type?, icon? }
   * type 'text'      (default): "Label: value" with a bold label.
   * type 'multiline': value is an array of strings, each shown on its
   *                    own indented line under a single bold label
   *                    (used for Operating Hours' Mon-Fri / Sat lines).
   * type 'link':      value is a URL, rendered as a real clickable
   *                    anchor that opens in a new tab.
   * `icon`, when present (appointment cards), is prefixed to the label.
   */
  function renderCardRow(container, row) {
    const type = row.type || 'text';
    const labelText = (row.icon ? row.icon + ' ' : '') + row.label;

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

    // default: plain "Label: value" row with a bold label
    const p = document.createElement('p');
    const strong = document.createElement('strong');
    strong.textContent = labelText + ':';
    p.appendChild(strong);
    p.appendChild(document.createTextNode(' ' + row.value));
    container.appendChild(p);
  }

  /**
   * Renders a structured card built from a JSON payload sent by the
   * backend — used for both the appointment confirmation (see
   * submitAppointment() in AppointmentConversation.php) and the
   * general clinic info reply (see sendClinicInfo() in
   * BotManController.php). Displays a bold title followed by grouped
   * sections, each rendered as its own visually-separated block of
   * labeled rows — instead of a single long run-on message.
   *
   * @param {Object}  data       { title, sections: [{ rows: [{icon, label, value}] }], footer }
   * @param {string}  titleIcon  Emoji prefixed to the title (e.g. '✅' or 'ℹ️').
   * @param {string}  kind       History-persist tag: 'card' | 'info_card'.
   * @param {boolean} persist    Whether to record this card in the session history.
   */
  function addCard(data, titleIcon, kind, persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = 'chat-message bot';

    const card = document.createElement('div');
    card.className = 'post-appt-card';

    const title = document.createElement('div');
    title.className = 'post-appt-title';
    title.textContent = (titleIcon ? titleIcon + ' ' : '') + data.title;
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
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind, data });
      persistSession();
    }
  }

  function addAppointmentCard(data, persist = true) {
    addCard(data, '✅', 'card', persist);
  }

  function addInfoCard(data, persist = true) {
    addCard(data, 'ℹ️', 'info_card', persist);
  }

  /**
   * Renders an attachment bubble. For image files, shows an actual
   * inline preview of the image (via a base64 data URL) with the
   * filename as a small caption underneath. For non-image files,
   * falls back to a 📎 filename bubble since there's nothing visual
   * to preview.
   *
   * @param {string}  fileName
   * @param {string|null} dataUrl  Base64 data URL of the file (image
   *                               previews only), or null for non-image
   *                               attachments.
   * @param {string}  sender       'bot' | 'user'
   * @param {boolean} persist      Whether to record this in session history.
   */
  function addAttachmentBubble(fileName, dataUrl, sender = 'user', persist = true) {
    const messageWrap = document.createElement('div');
    messageWrap.className = `chat-message ${sender}`;

    const bubble = document.createElement('div');

    if (dataUrl) {
      bubble.className = 'chat-bubble attachment-bubble';

      const img = document.createElement('img');
      img.src = dataUrl;
      img.alt = fileName;
      img.className = 'chat-attachment-image';
      bubble.appendChild(img);

      const caption = document.createElement('div');
      caption.className = 'chat-attachment-caption';
      caption.textContent = fileName;
      bubble.appendChild(caption);
    } else {
      // Non-image file: nothing to preview, so just show the filename.
      bubble.className = 'chat-bubble';
      bubble.textContent = `📎 Attached: ${fileName}`;
    }

    messageWrap.appendChild(bubble);
    messagesEl.appendChild(messageWrap);
    scrollToBottom();

    if (persist) {
      session.history.push({ kind: 'attachment', fileName, dataUrl, sender });
      persistSession();
    }

    return bubble;
  }

  function restoreConversation() {
    if (session.history.length === 0) {
      addBubble('Hello! Welcome to PolyClinic Lipa. How can I help you today?', 'bot');
      // Show the main menu buttons right away instead of waiting for the
      // user to type something first. These values ('schedule visit' /
      // 'general information') match exactly what the backend's
      // sendMainMenu() / isGlobalCommand() already expect, so clicking
      // either button behaves the same as if the backend had sent them.
      addButtons([
        { text: '📋 Schedule Visit', value: 'schedule visit' },
        { text: 'ℹ️ General Information', value: 'general information' },
      ]);
      return;
    }

    session.history.forEach(item => {
      if (item.kind === 'bubble') {
        addBubble(item.text, item.sender, false); // Already persisted — don't re-save.
      } else if (item.kind === 'buttons') {
        addButtons(item.buttons, false);
      } else if (item.kind === 'card') {
        addAppointmentCard(item.data, false);
      } else if (item.kind === 'info_card') {
        addInfoCard(item.data, false);
      } else if (item.kind === 'attachment') {
        addAttachmentBubble(item.fileName, item.dataUrl, item.sender, false);
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

  /**
   * Sends a message to the BotMan backend and renders the reply.
   *
   * Bot text messages are inspected for the APPT_CARD_PREFIX marker.
   * If present, the remainder of the string is parsed as JSON and
   * rendered as a structured card via addAppointmentCard(); otherwise
   * the text is rendered as a normal chat bubble.
   *
   * @param {string} text        The value actually sent to the BotMan
   *                              backend (e.g. a button's value, which
   *                              may be an ID or an internal keyword).
   * @param {string} [displayText] What to show in the user's chat
   *                              bubble. Falls back to `text` when
   *                              omitted — e.g. when the user typed a
   *                              message directly into the input box,
   *                              where the sent text and displayed
   *                              text are the same thing.
   */
  async function sendMessage(text, displayText) {
    text = typeof text === 'string' ? text : String(text ?? '');
    if (!text.trim()) return;

    addBubble(displayText ?? text, 'user');
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
          userId: session.clientId, // Lets BotMan resume the correct conversation state.
        }),
      });

      const data = await res.json();
      hideTyping();

      if (!res.ok) {
        addBubble('⚠️ Something went wrong reaching the chatbot. Please try again.', 'bot');
        return;
      }

      // The /botman endpoint returns: { status: 200, messages: [ { text, actions? }, ... ] }
      const messages = Array.isArray(data) ? data : (data.messages || []);

      messages.forEach(msg => {
        if (msg.text) {
          if (msg.text.startsWith(APPT_CARD_PREFIX)) {
            try {
              const cardData = JSON.parse(msg.text.slice(APPT_CARD_PREFIX.length));
              addAppointmentCard(cardData);
            } catch (e) {
              console.error('Failed to parse appointment card payload:', e);
              addBubble(msg.text, 'bot'); // Fallback: show raw text rather than silently dropping it.
            }
          } else if (msg.text.startsWith(INFO_CARD_PREFIX)) {
            try {
              const cardData = JSON.parse(msg.text.slice(INFO_CARD_PREFIX.length));
              addInfoCard(cardData);
            } catch (e) {
              console.error('Failed to parse info card payload:', e);
              addBubble(msg.text, 'bot'); // Fallback: show raw text rather than silently dropping it.
            }
          } else {
            addBubble(msg.text, 'bot');
          }
        }

        // Buttons may arrive as msg.actions (BotMan Question object)
        // or the legacy msg.attachment.buttons format.
        const buttons = msg.actions || (msg.attachment && msg.attachment.buttons);
        if (buttons && buttons.length) {
          addButtons(buttons);
        }
      });

    } catch (err) {
      hideTyping();
      console.error('Chat error:', err);
      addBubble('⚠️ Unable to connect. Please check your internet connection and try again.', 'bot');
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

  // ---------- Initialization ----------
  restoreConversation();
  persistSession(); // Refresh the activity timestamp on every page load.
});
</script>

</body>
</html>