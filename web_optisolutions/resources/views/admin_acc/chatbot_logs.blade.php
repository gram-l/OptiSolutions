<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Chatbot Inquiries</title>
    <!-- Vite CSS -->
    @vite(['resources/css/admin_css/chatbot_logs.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css', 'resources/css/admin_css/feedback.css'
    ])
</head>
<body>
    <!-- Header -->
@include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
        @include('admin_acc.sidebar')
<div style="flex: 1; min-width: 0;">
        <div class="page-header">
            <h2>
                <span><i class="bi bi-chat-dots"></i></span> 
                Chatbot Inquiries
            </h2>
            <p>Review and respond to patient conversations from the AI chatbot</p>
             <!-- Sidebar Navigation -->
       
        </div>

        <!-- Chatbot Inquiries Interface -->
        <div class="inquiries-container">
            <!-- Chat List Sidebar -->
            <div class="chat-list">
                <div class="chat-list-header">
                    <h3>Recent Conversations</h3>
                    <input type="text" class="search-box" placeholder="Search patient or inquiry..." id="searchInput">
                </div>
                <ul class="chat-items" id="chatList">
                    <!-- Chat items will be dynamically populated -->
                </ul>
            </div>

            <!-- Chat Conversation Area -->
            <div class="chat-conversation" id="conversationArea">
                <!-- Default empty state -->
                <div class="empty-state" id="emptyState">
                    <div class="emoji">💬</div>
                    <p>Select a conversation to view details</p>
                </div>
                
                <!-- Conversation Header (hidden by default) -->
                <div id="conversationHeader" style="display: none;">
                    <div class="chat-conversation-header">
                        <div class="patient-details">
                            <h3 id="patientName">Maria Santos</h3>
                            <p id="patientInfo">ID: P-12345 • Ophthalmology</p>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="status-badge status-active" id="statusBadge">Active</span>
                            <button class="btn-sm btn-success" id="resolveBtn" style="display:none; border:none; border-radius:20px; padding:0.4rem 0.9rem; cursor:pointer;">
                                <i class="bi bi-check-circle"></i> Resolve
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="messages-area" id="messagesArea">
                    <!-- Messages will be dynamically populated -->
                </div>

                <!-- Reply Area (hidden by default) -->
                <div class="reply-area" id="replyArea" style="display: none;">
                    <input type="text" class="reply-input" placeholder="Type your response..." id="replyInput">
                    <button class="send-btn" id="sendBtn">Send Reply</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Real data, passed in from ChatbotInquiryController@index (was
        // previously a hardcoded mock array with no backend connection).
        const chatLogs = @json($inquiriesData);
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        let currentChatId = null;

        // Render chat list
        function renderChatList(filterText = "") {
            const chatListEl = document.getElementById("chatList");
            const filteredLogs = chatLogs.filter(log =>
                log.name.toLowerCase().includes(filterText.toLowerCase()) ||
                log.department.toLowerCase().includes(filterText.toLowerCase()) ||
                log.lastMessage.toLowerCase().includes(filterText.toLowerCase())
            );

            if (filteredLogs.length === 0) {
                chatListEl.innerHTML = `<li style="padding:1.5rem; text-align:center; color:#7f8c8d;">No inquiries found.</li>`;
                return;
            }

            // NOTE: `chat.name` is now the actual display name (Patient #ID
            // or the guest's given name / "Guest") instead of the INQ code.
            // The INQ code (chat.inquiryCode) is now shown in the preview
            // line instead, so the ticket reference isn't lost.
            chatListEl.innerHTML = filteredLogs.map(chat => {
                const isResolved = chat.rawStatus === 'Resolved';
                // Unresolved indicator: a small colored dot next to the name.
                // Shown regardless of read/unread state, since "unread" only
                // tracks whether an admin has opened it, not whether the
                // inquiry itself has been resolved.
                const statusDot = !isResolved
                    ? `<span class="unresolved-dot status-dot-${chat.status}" title="${chat.rawStatus}" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${chat.rawStatus === 'Pending' ? '#e74c3c' : '#f39c12'};margin-left:6px;flex-shrink:0;"></span>`
                    : '';

                return `
                <li class="chat-item ${currentChatId === chat.id ? 'active' : ''} ${!isResolved ? 'unresolved' : ''}" data-id="${chat.id}">
                    <div class="chat-avatar">${chat.avatar}</div>
                    <div class="chat-info">
                        <div class="chat-name" style="display:flex; align-items:center;">
                            ${escapeHtml(chat.name)}
                            ${statusDot}
                            <span class="chat-time">${formatTime(chat.timestamp)}</span>
                        </div>
                        <div class="chat-preview" style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1; min-width:0;">
                                ${chat.inquiryCode} • ${chat.lastMessage.substring(0, 40)}${chat.unread ? '<span class="unread-badge">New</span>' : ''}
                            </span>
                            ${!isResolved ? `<span class="unresolved-label" style="color:${chat.rawStatus === 'Pending' ? '#e74c3c' : '#f39c12'}; font-weight:600; flex-shrink:0; white-space:nowrap;">${chat.rawStatus}</span>` : ''}
                        </div>
                    </div>
                </li>
            `;
            }).join("");

            // Add click event listeners to chat items
            document.querySelectorAll('.chat-item').forEach(item => {
                item.addEventListener('click', () => {
                    const id = parseInt(item.dataset.id);
                    openChat(id);
                });
            });
        }

        // Format time for display
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const msgDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());

            if (msgDate.getTime() === today.getTime()) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else {
                return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            }
        }

        // Show/hide the reply box and Resolve button based on the chat's
        // current status. Pulled out into its own function so it can be
        // re-run after sending a reply or resolving, not just on initial
        // open — previously those actions could change chat.rawStatus
        // without the buttons ever updating to match.
        function updateActionVisibility(chat) {
            const isResolved = chat.rawStatus === 'Resolved';
            document.getElementById("replyArea").style.display = isResolved ? "none" : "flex";
            document.getElementById("resolveBtn").style.display = isResolved ? "none" : "inline-flex";
        }

        // Open a specific chat
        function openChat(chatId) {
            const chat = chatLogs.find(c => c.id === chatId);
            if (!chat) return;

            currentChatId = chatId;

            // Mark as read (locally — the actual "read" state is really
            // just resolved_status !== 'Pending' server-side, updated once
            // an Admin/Staff member actually replies).
            chat.unread = false;
            renderChatList(document.getElementById("searchInput").value);

            // Hide empty state, show conversation elements
            document.getElementById("emptyState").style.display = "none";
            document.getElementById("conversationHeader").style.display = "block";

            updateActionVisibility(chat);

            // Update header — now includes the INQ ticket code alongside
            // the patient ID and department, since chat.name shows the
            // actual person's name instead of the INQ code.
            document.getElementById("patientName").innerText = chat.name;
            document.getElementById("patientInfo").innerHTML = `${chat.inquiryCode} • ID: ${chat.patientId} • ${chat.department}`;
            const statusBadge = document.getElementById("statusBadge");
            statusBadge.className = `status-badge status-${chat.status}`;
            statusBadge.innerText = chat.status === "active" ? "In Progress" : chat.status === "pending" ? "Pending" : "Resolved";

            // Render messages
            renderMessages(chat.conversation);
        }

        // Render conversation messages
        function renderMessages(messages) {
            const messagesArea = document.getElementById("messagesArea");
            messagesArea.innerHTML = messages.map(msg => `
                <div class="message">
                    <div class="message-avatar ${msg.sender === 'patient' ? 'patient' : ''}">
                        ${msg.sender === 'patient' ? '👤' : '🧑‍💼'}
                    </div>
                    <div class="message-content ${msg.sender === 'bot' ? 'bot' : ''}">
                        <div class="message-sender ${msg.sender === 'patient' ? 'patient' : ''}">
                            ${escapeHtml(msg.senderLabel || (msg.sender === 'patient' ? 'Patient' : 'Admin'))}
                        </div>
                        <div class="message-text">${escapeHtml(msg.text)}</div>
                        <div class="message-time">${msg.time}</div>
                    </div>
                </div>
            `).join("");

            // Scroll to bottom
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Simple escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Send reply — now actually posts to the backend and saves into
        // the shared inquiry_replies thread (visible to Staff too).
        async function sendReply() {
            const input = document.getElementById("replyInput");
            const messageText = input.value.trim();
            if (!messageText || currentChatId === null) return;

            const chat = chatLogs.find(c => c.id === currentChatId);
            if (!chat) return;

            const sendBtn = document.getElementById("sendBtn");
            sendBtn.disabled = true;

            try {
                const res = await fetch(`/admin_acc/chatbot_logs/${currentChatId}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: messageText }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to send reply.');

                chat.conversation.push({
                    sender: 'bot',
                    senderLabel: 'Admin',
                    text: messageText,
                    time: data.time,
                });
                chat.lastMessage = messageText;
                chat.timestamp = new Date().toISOString();
                chat.rawStatus = data.status;
                chat.status = data.status === 'Pending' ? 'pending' : (data.status === 'Resolved' ? 'resolved' : 'active');

                renderMessages(chat.conversation);
                renderChatList(document.getElementById("searchInput").value);
                input.value = "";

                // Keep the header badge and reply/resolve buttons in sync
                // with the (possibly changed) status returned by the server.
                const statusBadge = document.getElementById("statusBadge");
                statusBadge.className = `status-badge status-${chat.status}`;
                statusBadge.innerText = chat.status === "active" ? "In Progress" : chat.status === "pending" ? "Pending" : "Resolved";
                updateActionVisibility(chat);

                const originalText = sendBtn.innerText;
                sendBtn.innerText = "Sent!";
                setTimeout(() => { sendBtn.innerText = originalText; }, 1000);
            } catch (e) {
                alert(e.message || 'Failed to send reply. Please try again.');
            } finally {
                sendBtn.disabled = false;
            }
        }

        // Mark the current inquiry as resolved.
        async function resolveInquiry() {
            if (currentChatId === null) return;
            const chat = chatLogs.find(c => c.id === currentChatId);
            if (!chat) return;

            if (!confirm('Mark this inquiry as resolved?')) return;

            const resolveBtn = document.getElementById("resolveBtn");
            resolveBtn.disabled = true;

            try {
                const res = await fetch(`/admin_acc/chatbot_logs/${currentChatId}/resolve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to resolve.');

                chat.rawStatus = 'Resolved';
                chat.status = 'resolved';
                chat.unread = false;
                openChat(currentChatId);
                renderChatList(document.getElementById("searchInput").value);
            } catch (e) {
                alert(e.message || 'Failed to resolve inquiry.');
            } finally {
                resolveBtn.disabled = false;
            }
        }

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }

        // Search functionality
        document.getElementById("searchInput").addEventListener("input", (e) => {
            renderChatList(e.target.value);
        });

        // Send button click
        document.getElementById("sendBtn").addEventListener("click", sendReply);

        // Enter key to send
        document.getElementById("replyInput").addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                sendReply();
            }
        });

        // Resolve button click
        document.getElementById("resolveBtn").addEventListener("click", resolveInquiry);

        // Initial render
        renderChatList("");

        // Auto-select first chat if exists
        if (chatLogs.length > 0) {
            setTimeout(() => {
                openChat(chatLogs[0].id);
            }, 100);
        }
    </script>
</body>
</html>