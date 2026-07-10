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
  const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
  const botmanUrl  = '{{ route("botman.handle") }}';

  // ============================================================
  // SESSION PERSISTENCE
  //
  // Chat history and the BotMan conversation state are persisted
  // in localStorage so that the conversation survives page
  // refreshes and tab switches. A session is considered expired
  // after a period of inactivity, at which point the history is
  // cleared and a new conversation begins.
  // ============================================================
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
        history: [], // Array of { kind: 'bubble' | 'buttons', ... }
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
        sendMessage(btn.value ?? btn.text);
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
   * Replays the persisted conversation on page load. If no history
   * exists (new or expired session), shows the initial greeting.
   */
  function restoreConversation() {
    if (session.history.length === 0) {
      addBubble('Hello! Welcome to PolyClinic Lipa. How can I help you today?', 'bot');
      return;
    }

    session.history.forEach(item => {
      if (item.kind === 'bubble') {
        addBubble(item.text, item.sender, false); // Already persisted — don't re-save.
      } else if (item.kind === 'buttons') {
        addButtons(item.buttons, false);
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
   * @param {string} text
   */
  async function sendMessage(text) {
    text = typeof text === 'string' ? text : String(text ?? '');
    if (!text.trim()) return;

    addBubble(text, 'user');
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
        if (msg.text) addBubble(msg.text, 'bot');

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
  sendBtn.addEventListener('click', () => sendMessage(input.value));

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      sendMessage(input.value);
    }
  });

  // ---------- File attachment (optional) ----------
  attachBtn.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
      addBubble(`📎 Attached: ${fileInput.files[0].name}`, 'user');
      // TODO: implement the actual upload logic here if needed.
    }
  });

  // ---------- Initialization ----------
  restoreConversation();
  persistSession(); // Refresh the activity timestamp on every page load.
});
</script>

</body>
</html>