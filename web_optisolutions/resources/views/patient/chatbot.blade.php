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

  <div class="chat-messages" id="chatMessages">
    <div class="chat-message bot">
      <div class="chat-bubble">Hello! Welcome to PolyClinic Lipa. How can I help you today?</div>
    </div>
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
  const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
  const botmanUrl  = '{{ route("botman.handle") }}';

  // Open/close panel
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

  function addBubble(text, sender = 'bot') {
    const wrap = document.createElement('div');