<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Chatbot Inquiries</title>
    <!-- Vite CSS -->
    @vite(['resources/css/admin_css/chatbot_logs.css', 'resources/css/admin_css/sidebar.css'
    ])
</head>
<body>
    <!-- Header -->
@include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
       

        <div class="page-header">
            <h2>
                <span><i class="bi bi-chat-dots"></i></span> 
                Chatbot Inquiries
            </h2>
            <p>Review and respond to patient conversations from the AI chatbot</p>
             <!-- Sidebar Navigation -->
        @include('admin_acc.sidebar')
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
                        <div>
                            <span class="status-badge status-active" id="statusBadge">Active</span>
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
        // Mock data for chat logs (UI only, no complex backend)
        const chatLogs = [
            {
                id: 1,
                name: "CHAT-001",
                avatar: "MS",
                patientId: "P-12345",
                department: "Ophthalmology",
                lastMessage: "When will my eye consultation be scheduled?",
                timestamp: "2026-05-22T10:30:00",
                unread: true,
                status: "active",
                conversation: [
                    { sender: "patient", text: "Hello, I need information about my upcoming eye consultation.", time: "10:25 AM" },
                    { sender: "bot", text: "Hello Maria! I'd be happy to help. Could you please provide your patient ID?", time: "10:26 AM" },
                    { sender: "patient", text: "My ID is P-12345", time: "10:27 AM" },
                    { sender: "bot", text: "Thank you! I see you have a consultation scheduled with Dr. Reyes on May 28th. Would you like to reschedule or ask about preparation?", time: "10:28 AM" },
                    { sender: "patient", text: "When will my eye consultation be scheduled?", time: "10:30 AM" },
                    { sender: "bot", text: "Based on your records, the consultation is tentatively scheduled for June 15th. Would you like me to connect you with an admin for confirmation?", time: "10:31 AM" }
                ]
            },
            {
                id: 2,
                name: "CHAT-002",
                avatar: "JD",
                patientId: "P-67890",
                department: "Pediatrics",
                lastMessage: "My son has a fever, what should I do?",
                timestamp: "2026-05-22T09:15:00",
                unread: true,
                status: "pending",
                conversation: [
                    { sender: "patient", text: "My son is 5 years old and has jaw pain since last night. What should I do?", time: "09:00 AM" },
                    { sender: "bot", text: "I'm sorry to hear that. Has he taken any medication yet?", time: "09:02 AM" },
                    { sender: "patient", text: "We gave him paracetamol 2 hours ago.", time: "09:05 AM" },
                    { sender: "patient", text: "My son has a fever, what should I do?", time: "09:15 AM" },
                    { sender: "bot", text: "Please monitor his temperature. Would you like to schedule a consultation?", time: "09:17 AM" }
                ]
            },
            {
                id: 3,
                name: "CHAT-003",
                avatar: "AR",
                patientId: "P-24680",
                department: "ENT",
                lastMessage: "Thank you for the information!",
                timestamp: "2026-05-21T16:45:00",
                unread: false,
                status: "resolved",
                conversation: [
                    { sender: "patient", text: "I have been experiencing ringing in my ears for a week.", time: "04:20 PM" },
                    { sender: "bot", text: "That sounds like tinnitus. Have you had any recent exposure to loud noises?", time: "04:22 PM" },
                    { sender: "patient", text: "No, not really. Should I see a specialist?", time: "04:25 PM" },
                    { sender: "bot", text: "Yes, I recommend booking an appointment with our ENT department. Dr. Mendoza has availability next Tuesday.", time: "04:30 PM" },
                    { sender: "patient", text: "Thank you for the information!", time: "04:45 PM" }
                ]
            },
            {
                id: 4,
                name: "CHAT-004",
                avatar: "CG",
                patientId: "P-13579",
                department: "Cardiology",
                lastMessage: "Can I get a prescription refill?",
                timestamp: "2026-05-21T11:20:00",
                unread: false,
                status: "active",
                conversation: [
                    { sender: "patient", text: "I'm running out of my blood pressure medication.", time: "11:00 AM" },
                    { sender: "bot", text: "I can help with that. When was your last check-up with Dr. Santos?", time: "11:05 AM" },
                    { sender: "patient", text: "About 2 months ago.", time: "11:10 AM" },
                    { sender: "patient", text: "Can I get a prescription refill?", time: "11:20 AM" },
                    { sender: "bot", text: "I'll notify the clinic. Please expect a call within 24 hours for prescription renewal.", time: "11:22 AM" }
                ]
            },
            {
                id: 5,
                name: "CHAT-005",
                avatar: "EG",
                patientId: "P-97531",
                department: "Dermatology",
                lastMessage: "Is my appointment still confirmed?",
                timestamp: "2026-05-20T14:30:00",
                unread: true,
                status: "pending",
                conversation: [
                    { sender: "patient", text: "I have an appointment tomorrow at 2 PM with Dr. Lopez.", time: "02:15 PM" },
                    { sender: "bot", text: "Yes, I see that appointment. Would you like to confirm or reschedule?", time: "02:18 PM" },
                    { sender: "patient", text: "Is my appointment still confirmed?", time: "02:30 PM" },
                    { sender: "bot", text: "Yes, your appointment is confirmed for May 23rd at 2:00 PM. Please arrive 15 minutes early.", time: "02:32 PM" }
                ]
            }
        ];

        let currentChatId = null;

        // Render chat list
        function renderChatList(filterText = "") {
            const chatListEl = document.getElementById("chatList");
            const filteredLogs = chatLogs.filter(log => 
                log.name.toLowerCase().includes(filterText.toLowerCase()) ||
                log.department.toLowerCase().includes(filterText.toLowerCase()) ||
                log.lastMessage.toLowerCase().includes(filterText.toLowerCase())
            );
            
            chatListEl.innerHTML = filteredLogs.map(chat => `
                <li class="chat-item ${currentChatId === chat.id ? 'active' : ''}" data-id="${chat.id}">
                    <div class="chat-avatar">${chat.avatar}</div>
                    <div class="chat-info">
                        <div class="chat-name">
                            ${chat.name}
                            <span class="chat-time">${formatTime(chat.timestamp)}</span>
                        </div>
                        <div class="chat-preview">${chat.lastMessage.substring(0, 50)}${chat.unread ? '<span class="unread-badge">New</span>' : ''}</div>
                    </div>
                </li>
            `).join("");
            
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
        
        // Open a specific chat
        function openChat(chatId) {
            const chat = chatLogs.find(c => c.id === chatId);
            if (!chat) return;
            
            currentChatId = chatId;
            
            // Mark as read
            chat.unread = false;
            renderChatList(document.getElementById("searchInput").value);
            
            // Hide empty state, show conversation elements
            document.getElementById("emptyState").style.display = "none";
            document.getElementById("conversationHeader").style.display = "block";
            document.getElementById("replyArea").style.display = "flex";
            
            // Update header
            document.getElementById("patientName").innerText = chat.name;
            document.getElementById("patientInfo").innerHTML = `ID: ${chat.patientId} • ${chat.department}`;
            const statusBadge = document.getElementById("statusBadge");
            statusBadge.className = `status-badge status-${chat.status}`;
            statusBadge.innerText = chat.status === "active" ? "Active" : chat.status === "pending" ? "Pending" : "Resolved";
            
            // Render messages
            renderMessages(chat.conversation);
        }
        
        // Render conversation messages
        function renderMessages(messages) {
            const messagesArea = document.getElementById("messagesArea");
            messagesArea.innerHTML = messages.map(msg => `
                <div class="message">
                    <div class="message-avatar ${msg.sender === 'patient' ? 'patient' : ''}">
                        ${msg.sender === 'patient' ? '👤' : '🤖'}
                    </div>
                    <div class="message-content ${msg.sender === 'bot' ? 'bot' : ''}">
                        <div class="message-sender ${msg.sender === 'patient' ? 'patient' : ''}">
                            ${msg.sender === 'patient' ? 'Patient' : 'Chatbot'}
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
        
        // Send reply functionality (UI only)
        function sendReply() {
            const input = document.getElementById("replyInput");
            const messageText = input.value.trim();
            if (!messageText || currentChatId === null) return;
            
            const chat = chatLogs.find(c => c.id === currentChatId);
            if (!chat) return;
            
            // Add admin reply to conversation (UI only)
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            chat.conversation.push({
                sender: "bot",
                text: messageText,
                time: timeString
            });
            
            // Update last message preview
            chat.lastMessage = messageText;
            chat.timestamp = now.toISOString();
            
            // Re-render messages and chat list
            renderMessages(chat.conversation);
            renderChatList(document.getElementById("searchInput").value);
            
            // Clear input
            input.value = "";
            
            // Show temporary success indicator
            const sendBtn = document.getElementById("sendBtn");
            const originalText = sendBtn.innerText;
            sendBtn.innerText = "Sent!";
            setTimeout(() => {
                sendBtn.innerText = originalText;
            }, 1000);
        }
        
        // Navigation functions
        
        
        
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