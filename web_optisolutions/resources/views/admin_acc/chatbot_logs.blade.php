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
    {{-- feedback.css intentionally NOT loaded here anymore: it redefines
         .container/.empty-state/.search-box with different rules, and since
         it was loaded last it silently overrode this page's own styles. --}}
    @vite(['resources/css/admin_css/chatbot_logs.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'
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
                        <button class="back-to-list-btn" id="backToListBtn" type="button" aria-label="Back to conversation list">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <div class="patient-details">
                            <h3 id="patientName">Maria Santos</h3>
                            <p id="patientInfo">ID: P-12345 • Ophthalmology</p>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="status-badge status-active" id="statusBadge">Active</span>
                            <button class="resolve-btn-modern" id="resolveBtn">
                                <i class="bi bi-check-circle"></i> Resolve
                            </button>
                            <button class="unresolve-btn-modern" id="unresolveBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Unresolve
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

    <!-- Custom confirm modal (replaces window.confirm) -->
    <div class="modal-overlay" id="confirmModalOverlay" style="display:none;">
        <div class="modal-box">
            <p id="confirmModalMessage"></p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" id="confirmModalCancel">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="confirmModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Toast container (replaces window.alert) -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Real data, passed in from ChatbotInquiryController@index (was
        // previously a hardcoded mock array with no backend connection).
        const chatLogs = @json($inquiriesData);
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        let currentChatId = null;

        // How many chat items are shown before a "See more" control appears.
        const PAGE_SIZE = 6;
        let visibleCount = PAGE_SIZE;

        // Status -> dot color / label, used for the top-right of each chat item.
        // Colors pulled from the site's own palette (chatbot_logs.css :root vars)
        // instead of arbitrary red/orange/green.
        function getStatusMeta(chat) {
            if (chat.rawStatus === 'Resolved') {
                return { color: '#7f8c8d', showDot: false, label: 'Resolved' };
            }
            if (chat.rawStatus === 'Pending') {
                return { color: 'var(--primary-dark)', showDot: true, label: 'Pending' };
            }
            return { color: 'var(--primary-main)', showDot: true, label: chat.rawStatus || 'In Progress' };
        }

        // Render chat list
        function renderChatList(filterText = "") {
            const chatListEl = document.getElementById("chatList");
            const filteredLogs = chatLogs.filter(log =>
                log.name.toLowerCase().includes(filterText.toLowerCase()) ||
                (log.department || '').toLowerCase().includes(filterText.toLowerCase()) ||
                log.lastMessage.toLowerCase().includes(filterText.toLowerCase())
            );

            if (filteredLogs.length === 0) {
                chatListEl.innerHTML = `<li style="padding:1.5rem; text-align:center; color:#7f8c8d;">No inquiries found.</li>`;
                return;
            }

            const itemsToShow = filteredLogs.slice(0, visibleCount);

            // Layout per item:
            //   Guest                      *  Pending
            //   INQ-007                   Aug 26
            // Top row: name (left) + status dot/label (right).
            // Bottom row: inquiry code (left) + date/time (right).
            let html = itemsToShow.map(chat => {
                const isResolved = chat.rawStatus === 'Resolved';
                const meta = getStatusMeta(chat);

                return `
                <li class="chat-item ${currentChatId === chat.id ? 'active' : ''} ${!isResolved ? 'unresolved' : ''}" data-id="${chat.id}">
                    <div class="chat-avatar">
                        ${chat.avatar}
                        ${chat.unread ? '<span class="avatar-unread-dot" title="Unread"></span>' : ''}
                    </div>
                    <div class="chat-info">
                        <div class="chat-name">
                            <span class="chat-name-text">${escapeHtml(chat.name)}</span>
                            <span class="chat-status-label" style="color:${meta.color};">
                                ${meta.showDot ? `<span class="status-dot-inline" style="background:${meta.color};"></span>` : ''}
                                ${meta.label}
                            </span>
                        </div>
                        <div class="chat-preview">
                            <span class="chat-code">${chat.inquiryCode}</span>
                            <span class="chat-time">${formatTime(chat.timestamp)}</span>
                        </div>
                    </div>
                </li>
            `;
            }).join("");

            if (filteredLogs.length > visibleCount) {
                html += `
                <li class="see-more-item">
                    <button class="see-more-btn" id="seeMoreBtn">See more (${filteredLogs.length - visibleCount} more)</button>
                </li>`;
            }

            chatListEl.innerHTML = html;

            // Add click event listeners to chat items
            document.querySelectorAll('.chat-item').forEach(item => {
                item.addEventListener('click', () => {
                    const id = parseInt(item.dataset.id);
                    openChat(id);
                });
            });

            const seeMoreBtn = document.getElementById('seeMoreBtn');
            if (seeMoreBtn) {
                seeMoreBtn.addEventListener('click', () => {
                    visibleCount += PAGE_SIZE;
                    renderChatList(document.getElementById("searchInput").value);
                });
            }
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
            document.getElementById("unresolveBtn").style.display = isResolved ? "inline-flex" : "none";
        }

        // Open a specific chat
        function openChat(chatId) {
            const chat = chatLogs.find(c => c.id === chatId);
            if (!chat) return;

            // Switching conversations — stop signalling "typing" on
            // whichever chat we were previously composing a reply for.
            if (typeof stopTypingHeartbeat === 'function') stopTypingHeartbeat();

            currentChatId = chatId;

            // On narrow/mobile layouts the list and conversation are two
            // separate screens (not stacked panes) — opening a chat swaps
            // to the conversation screen instead of pushing it below the
            // whole (possibly very long) list.
            document.getElementById("conversationArea").classList.add("mobile-active");

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
            // No department segment when the inquiry has no specific
            // type (department comes through as null/empty from the
            // backend instead of a placeholder like "General").
            const infoParts = [chat.inquiryCode, `ID: ${chat.patientId}`];
            if (chat.department) infoParts.push(chat.department);
            document.getElementById("patientInfo").innerHTML = infoParts.map(escapeHtml).join(' • ');
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
                        <i class="bi ${msg.sender === 'patient' ? 'bi-person-fill' : 'bi-headset'}"></i>
                    </div>
                    <div class="message-content ${msg.sender === 'bot' ? 'bot' : ''}">
                        <div class="message-sender ${msg.sender === 'patient' ? 'patient' : ''}">
                            ${escapeHtml(msg.senderLabel || (msg.sender === 'patient' ? 'Patient' : 'Admin'))}
                        </div>
                        ${renderAttachment(msg)}
                        ${msg.text && msg.text !== '(Sent an attachment)' ? `<div class="message-text">${escapeHtml(msg.text)}</div>` : ''}
                        <div class="message-time">${msg.time}</div>
                    </div>
                </div>
            `).join("");

            // Scroll to bottom
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Shows an image preview (or a download link for non-image files)
        // for any message that has a patient-sent attachment.
        function renderAttachment(msg) {
            if (!msg.attachmentUrl) return '';

            const isImage = /\.(jpe?g|png|gif|webp)$/i.test(msg.attachmentName || msg.attachmentUrl);
            const safeUrl = escapeHtml(msg.attachmentUrl);
            const safeName = escapeHtml(msg.attachmentName || 'Attachment');

            if (isImage) {
                return `<a href="${safeUrl}" target="_blank" rel="noopener">
                    <img src="${safeUrl}" alt="${safeName}" style="max-width:220px;max-height:220px;border-radius:8px;display:block;margin-bottom:0.4rem;">
                </a>`;
            }

            return `<a href="${safeUrl}" target="_blank" rel="noopener" style="display:inline-block;margin-bottom:0.4rem;">📎 ${safeName}</a>`;
        }

        // Simple escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Custom confirm modal — replaces window.confirm().
        // Resolves to true/false depending on which button was clicked.
        function showConfirm(message) {
            return new Promise((resolve) => {
                const overlay = document.getElementById('confirmModalOverlay');
                document.getElementById('confirmModalMessage').textContent = message;
                overlay.style.display = 'flex';

                const cancelBtn = document.getElementById('confirmModalCancel');
                const confirmBtn = document.getElementById('confirmModalConfirm');

                function cleanup(result) {
                    overlay.style.display = 'none';
                    cancelBtn.removeEventListener('click', onCancel);
                    confirmBtn.removeEventListener('click', onConfirm);
                    resolve(result);
                }
                function onCancel() { cleanup(false); }
                function onConfirm() { cleanup(true); }

                cancelBtn.addEventListener('click', onCancel);
                confirmBtn.addEventListener('click', onConfirm);
            });
        }

        // Toast notification — replaces window.alert().
        function showToast(message, type = 'error') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('toast-hide');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Send reply — now actually posts to the backend and saves into
        // the shared inquiry_replies thread (visible to Staff too).
        async function sendReply() {
            const input = document.getElementById("replyInput");
            const messageText = input.value.trim();
            if (!messageText || currentChatId === null) return;

            // The reply is going out now, so this client's own "typing"
            // signal should stop (the actual message will clear the flag
            // server-side too, but there's no reason to keep pinging it).
            stopTypingHeartbeat();

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
                showToast(e.message || 'Failed to send reply. Please try again.');
            } finally {
                sendBtn.disabled = false;
            }
        }

        // Mark the current inquiry as resolved.
        async function resolveInquiry() {
            if (currentChatId === null) return;
            const chat = chatLogs.find(c => c.id === currentChatId);
            if (!chat) return;

            const confirmed = await showConfirm('Mark this inquiry as resolved?');
            if (!confirmed) return;

            const resolveBtn = document.getElementById("resolveBtn");
            resolveBtn.disabled = true;
            const originalResolveText = resolveBtn.innerHTML;
            resolveBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> Resolving...`;

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
                showToast('Inquiry marked as resolved.', 'success');
            } catch (e) {
                showToast(e.message || 'Failed to resolve inquiry.');
            } finally {
                resolveBtn.disabled = false;
                resolveBtn.innerHTML = originalResolveText;
            }
        }

        // Undo a resolve — puts the inquiry back into the actionable
        // (In Progress) list and brings the reply box back.
        async function unresolveInquiry() {
            if (currentChatId === null) return;
            const chat = chatLogs.find(c => c.id === currentChatId);
            if (!chat) return;

            const confirmed = await showConfirm('Mark this inquiry as unresolved?');
            if (!confirmed) return;

            const unresolveBtn = document.getElementById("unresolveBtn");
            unresolveBtn.disabled = true;
            const originalText = unresolveBtn.innerHTML;
            unresolveBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> Updating...`;

            try {
                const res = await fetch(`/admin_acc/chatbot_logs/${currentChatId}/unresolve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to unresolve.');

                chat.rawStatus = data.status;
                chat.status = data.status === 'Pending' ? 'pending' : (data.status === 'Resolved' ? 'resolved' : 'active');
                openChat(currentChatId);
                renderChatList(document.getElementById("searchInput").value);
                showToast('Inquiry marked as unresolved.', 'success');
            } catch (e) {
                showToast(e.message || 'Failed to unresolve inquiry.');
            } finally {
                unresolveBtn.disabled = false;
                unresolveBtn.innerHTML = originalText;
            }
        }

        async function handleLogout() {
            const confirmed = await showConfirm('Are you sure you want to logout?');
            if (confirmed) {
                showToast('Logging out... Redirecting to login page.', 'success');
            }
        }

        // Search functionality
        document.getElementById("searchInput").addEventListener("input", (e) => {
            visibleCount = PAGE_SIZE; // reset pagination on a new search
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

        // --- Typing presence -------------------------------------------------
        // Tells the patient-facing widget "an Admin is actually typing a
        // reply right now" (via TypingStatusService on the backend), instead
        // of the old fake "..." that used to appear for every bot response.
        // The flag has a short server-side TTL, so we re-send it every few
        // seconds while the admin keeps typing, and clear it immediately on
        // pause/send/switching conversations.
        let typingHeartbeat = null;
        let typingIdleTimer = null;
        let typingActiveFor = null; // chat id the "typing: true" ping was last sent for

        function postTyping(chatId, isTyping) {
            if (chatId === null) return;
            fetch(`/admin_acc/chatbot_logs/${chatId}/typing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ typing: isTyping }),
            }).catch(() => {}); // best-effort; a missed heartbeat just lets the TTL expire
        }

        function stopTypingHeartbeat() {
            if (typingHeartbeat) { clearInterval(typingHeartbeat); typingHeartbeat = null; }
            if (typingIdleTimer) { clearTimeout(typingIdleTimer); typingIdleTimer = null; }
            if (typingActiveFor !== null) {
                postTyping(typingActiveFor, false);
                typingActiveFor = null;
            }
        }

        document.getElementById("replyInput").addEventListener("input", () => {
            if (currentChatId === null) return;

            if (typingActiveFor !== currentChatId) {
                typingActiveFor = currentChatId;
                postTyping(currentChatId, true);
                typingHeartbeat = setInterval(() => postTyping(currentChatId, true), 3000);
            }

            // Stop signalling "typing" if the admin pauses for a few seconds
            // without sending — otherwise the dots would sit there forever.
            clearTimeout(typingIdleTimer);
            typingIdleTimer = setTimeout(stopTypingHeartbeat, 4000);
        });

        // Resolve / Unresolve button clicks
        document.getElementById("resolveBtn").addEventListener("click", resolveInquiry);
        document.getElementById("unresolveBtn").addEventListener("click", unresolveInquiry);

        // Back button (mobile only) — return to the conversation list
        // without losing the currently loaded conversation.
        document.getElementById("backToListBtn").addEventListener("click", () => {
            document.getElementById("conversationArea").classList.remove("mobile-active");
        });

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