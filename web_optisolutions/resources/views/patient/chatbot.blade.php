<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic · Schedule Visit</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  @vite(['resources/css/patient_css/chatbot.css', 'resources/css/patient_css/chatbot.css'])
</head>
<body>

<script>
    var botmanWidget = {
        aboutText: 'PolyClinic Assistant',
        introMessage: "✋ Hi! I'm your PolyClinic Assistant."
    };
</script>

<script src='https://cdn.jsdelivr.net/npm/botman-web-widget@0/build/js/widget.js'></script>

<div class="chat-wrapper">
  <!-- HEADER -->
  <div class="chat-header">
    <div class="chat-avatar"><i class="fas fa-headset"></i></div>
    <div class="chat-header-info">
      <h1>PolyClinic Assistant</h1>
      <p>Your healthcare companion</p>
      <span>● Online</span>
    </div>
  </div>

  <!-- MESSAGES -->
  <div class="chat-messages" id="chatMessages"></div>

  <!-- INPUT AREA -->
  <div class="chat-input-area">
    <button class="attach-btn" onclick="document.getElementById('fileInput').click()"><i class="fas fa-paperclip"></i></button>
    <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
    <button class="chat-send" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
    <input type="file" id="fileInput" class="file-input" accept="image/*" onchange="handleImageUpload(event)">
  </div>

  <!-- FOOTER -->
  <div class="footer-note">
    <a href="{{ route('home') }}">← Back to PolyClinic Lipa</a>
  </div>
</div>

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])
</body>
</html>