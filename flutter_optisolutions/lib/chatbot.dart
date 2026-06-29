import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;

enum MessageSender { patient, bot }

class ChatMessage {
  final MessageSender sender;
  final String text;
  final String time;
  const ChatMessage({required this.sender, required this.text, required this.time});
}

class ChatThread {
  final String id;           // CHAT-001
  final String patientName;
  final String initials;
  final Color  avatarColor;
  final String lastMessage;
  final String date;
  final bool   hasNew;
  final String specialty;
  final String patientId;
  final bool   isActive;
  final List<ChatMessage> messages;

  const ChatThread({
    required this.id,
    required this.patientName,
    required this.initials,
    required this.avatarColor,
    required this.lastMessage,
    required this.date,
    required this.specialty,
    required this.patientId,
    this.hasNew    = false,
    this.isActive  = false,
    this.messages  = const [],
  });
}

// ─────────────────────────────────────────────────────────────
//  SAMPLE DATA
// ─────────────────────────────────────────────────────────────
final _threads = <ChatThread>[
  ChatThread(
    id: 'CHAT-001', patientName: 'Maria Santos', initials: 'MS',
    avatarColor: const Color(0xFF1565C0),
    lastMessage: 'When will my eye consultation be scheduled?',
    date: 'May 22', specialty: 'Ophthalmology', patientId: 'P-12345',
    hasNew: false, isActive: true,
    messages: const [
      ChatMessage(sender: MessageSender.patient, text: 'Hello, I need information about my upcoming eye consultation.', time: '10:25 AM'),
      ChatMessage(sender: MessageSender.bot,     text: "Hello Maria! I'd be happy to help. Could you please provide your patient ID?", time: '10:26 AM'),
      ChatMessage(sender: MessageSender.patient, text: 'My ID is P-12345', time: '10:27 AM'),
      ChatMessage(sender: MessageSender.bot,     text: 'Thank you! I see you have a consultation scheduled with Dr. Reyes on May 28th. Would you like to reschedule or ask about preparation?', time: '10:28 AM'),
      ChatMessage(sender: MessageSender.patient, text: 'When will my eye consultation be scheduled?', time: '10:30 AM'),
      ChatMessage(sender: MessageSender.bot,     text: 'Based on your records, the consultation is tentatively scheduled for June 15th. Would you like me to connect you with an admin for confirmation?', time: '10:31 AM'),
    ],
  ),
  ChatThread(
    id: 'CHAT-002', patientName: 'John Dela Cruz', initials: 'JD',
    avatarColor: const Color(0xFF00838F),
    lastMessage: 'My son has a fever, what should I do?',
    date: 'May 22', specialty: 'Pediatrics', patientId: 'P-12346',
    hasNew: true, isActive: false,
    messages: const [
      ChatMessage(sender: MessageSender.patient, text: 'My son has a fever, what should I do?', time: '09:10 AM'),
      ChatMessage(sender: MessageSender.bot,     text: 'I understand your concern. For a fever, ensure he stays hydrated and rested. If it exceeds 38.5°C, please consult a doctor immediately.', time: '09:11 AM'),
    ],
  ),
  ChatThread(
    id: 'CHAT-003', patientName: 'Anna Rivera', initials: 'AR',
    avatarColor: const Color(0xFF5E35B1),
    lastMessage: 'Thank you for the information!',
    date: 'May 21', specialty: 'ENT', patientId: 'P-12347',
    hasNew: false, isActive: false,
    messages: const [
      ChatMessage(sender: MessageSender.patient, text: 'Can you tell me more about my ENT appointment?', time: '03:00 PM'),
      ChatMessage(sender: MessageSender.bot,     text: 'Your ENT appointment is scheduled with Dr. Garcia on May 25th at 10:00 AM.', time: '03:01 PM'),
      ChatMessage(sender: MessageSender.patient, text: 'Thank you for the information!', time: '03:02 PM'),
    ],
  ),
  ChatThread(
    id: 'CHAT-004', patientName: 'Carlos Gomez', initials: 'CG',
    avatarColor: const Color(0xFFB71C1C),
    lastMessage: 'Can I get a prescription refill?',
    date: 'May 21', specialty: 'Cardiology', patientId: 'P-12348',
    hasNew: false, isActive: false,
    messages: const [
      ChatMessage(sender: MessageSender.patient, text: 'Can I get a prescription refill?', time: '11:00 AM'),
      ChatMessage(sender: MessageSender.bot,     text: 'For prescription refills, please contact your attending physician Dr. Santos directly or visit the clinic.', time: '11:01 AM'),
    ],
  ),
  ChatThread(
    id: 'CHAT-005', patientName: 'Elena Guzman', initials: 'EG',
    avatarColor: const Color(0xFF2E7D32),
    lastMessage: 'Is my appointment still confirmed?',
    date: 'May 20', specialty: 'Dermatology', patientId: 'P-12349',
    hasNew: true, isActive: false,
    messages: const [
      ChatMessage(sender: MessageSender.patient, text: 'Is my appointment still confirmed?', time: '08:45 AM'),
      ChatMessage(sender: MessageSender.bot,     text: 'Yes, your appointment with Dr. Lopez is confirmed for May 22nd at 2:00 PM.', time: '08:46 AM'),
    ],
  ),
];

// ─────────────────────────────────────────────────────────────
//  1.  INQUIRIES LIST SCREEN
// ─────────────────────────────────────────────────────────────
class InquiriesScreen extends StatefulWidget {
  const InquiriesScreen({super.key});

  @override
  State<InquiriesScreen> createState() => _InquiriesScreenState();
}

class _InquiriesScreenState extends State<InquiriesScreen> {
  String _search = '';

  List<ChatThread> get _filtered => _threads.where((t) {
        final q = _search.toLowerCase();
        return t.patientName.toLowerCase().contains(q) ||
            t.id.toLowerCase().contains(q) ||
            t.lastMessage.toLowerCase().contains(q);
      }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/chatbot',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // ── Top bar ──
            _buildTopBar(),
            // ── Card with list ──
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.05),
                        blurRadius: 10,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      _buildListHeader(),
                      _buildSearchBar(),
                      const Divider(height: 1, color: AppColors.border),
                      Expanded(
                        child: ListView.separated(
                          itemCount: _filtered.length,
                          separatorBuilder: (_, _) =>
                              const Divider(height: 1, indent: 16, color: AppColors.border),
                          itemBuilder: (_, i) => _ChatTile(
                            thread: _filtered[i],
                            onTap: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => ChatDetailScreen(thread: _filtered[i]),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildTopBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu, color: AppColors.textDark),
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
          _AppIcon(),
          const SizedBox(width: 8),
          const Text('Polyclinic',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 4),
          CircleAvatar(
            radius: 16,
            backgroundColor: AppColors.primary,
            child: const Text('DL',
                style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildListHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
      child: Row(
        children: [
          Container(
            width: 32, height: 32,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.chat_bubble_outline_rounded,
                color: AppColors.primary, size: 16),
          ),
          const SizedBox(width: 10),
          const Text('Inquiries',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Container(
        height: 36,
        decoration: BoxDecoration(
          color: AppColors.background,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.border),
        ),
        child: TextField(
          onChanged: (v) => setState(() => _search = v),
          style: const TextStyle(fontSize: 13),
          decoration: const InputDecoration(
            prefixIcon: Icon(Icons.search, size: 16, color: AppColors.textGrey),
            hintText: 'Search...',
            hintStyle: TextStyle(color: AppColors.textGrey, fontSize: 13),
            border: InputBorder.none,
            contentPadding: EdgeInsets.symmetric(vertical: 9),
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  CHAT TILE  (list item)
// ─────────────────────────────────────────────────────────────
class _ChatTile extends StatelessWidget {
  final ChatThread thread;
  final VoidCallback onTap;
  const _ChatTile({required this.thread, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Avatar
            CircleAvatar(
              radius: 20,
              backgroundColor: thread.avatarColor,
              child: Text(thread.initials,
                  style: const TextStyle(
                      color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(thread.id,
                          style: const TextStyle(
                              fontWeight: FontWeight.bold, fontSize: 13.5)),
                      const Spacer(),
                      Text(thread.date,
                          style: const TextStyle(
                              fontSize: 11.5, color: AppColors.textGrey)),
                    ],
                  ),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          thread.lastMessage,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.textGrey),
                        ),
                      ),
                      if (thread.hasNew) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppColors.newBadge,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Text('NEW',
                              style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 9,
                                  fontWeight: FontWeight.bold,
                                  letterSpacing: 0.5)),
                        ),
                      ]
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  2.  CHAT DETAIL SCREEN
// ─────────────────────────────────────────────────────────────
class ChatDetailScreen extends StatefulWidget {
  final ChatThread thread;
  const ChatDetailScreen({super.key, required this.thread});

  @override
  State<ChatDetailScreen> createState() => _ChatDetailScreenState();
}

class _ChatDetailScreenState extends State<ChatDetailScreen> {
  final _replyCtrl   = TextEditingController();
  final _scrollCtrl  = ScrollController();
  late List<ChatMessage> _messages;

  @override
  void initState() {
    super.initState();
    _messages = List.from(widget.thread.messages);
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
  }

  void _scrollToBottom() {
    if (_scrollCtrl.hasClients) {
      _scrollCtrl.animateTo(
        _scrollCtrl.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  void _sendReply() {
    final text = _replyCtrl.text.trim();
    if (text.isEmpty) return;
    setState(() {
      _messages.add(ChatMessage(
        sender: MessageSender.patient,
        text: text,
        time: _nowTime(),
      ));
      _replyCtrl.clear();
    });
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
  }

  String _nowTime() {
    final now = DateTime.now();
    final h = now.hour > 12 ? now.hour - 12 : now.hour == 0 ? 12 : now.hour;
    final m = now.minute.toString().padLeft(2, '0');
    final ampm = now.hour >= 12 ? 'PM' : 'AM';
    return '$h:$m $ampm';
  }

  @override
  void dispose() {
    _replyCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FB),
      body: SafeArea(
        child: Column(
          children: [
            _buildHeader(),
            Expanded(
              child: ListView.builder(
                controller: _scrollCtrl,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                itemCount: _messages.length,
                itemBuilder: (_, i) => _MessageBubble(msg: _messages[i]),
              ),
            ),
            _buildReplyBar(),
          ],
        ),
      ),
    );
  }

  // ── Chat header ──
  Widget _buildHeader() {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_ios_new_rounded,
                size: 18, color: AppColors.textDark),
            onPressed: () => Navigator.pop(context),
          ),
          CircleAvatar(
            radius: 16,
            backgroundColor: widget.thread.avatarColor,
            child: Text(widget.thread.initials,
                style: const TextStyle(
                    color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(widget.thread.id,
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(width: 5),
                    Container(
                      width: 7, height: 7,
                      decoration: const BoxDecoration(
                          color: Colors.red, shape: BoxShape.circle),
                    ),
                  ],
                ),
                Text(
                  'ID: ${widget.thread.patientId}  •  ${widget.thread.specialty}',
                  style: const TextStyle(fontSize: 11, color: AppColors.textGrey),
                ),
              ],
            ),
          ),
          if (widget.thread.isActive)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.activeBadge.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Text('Active',
                  style: TextStyle(
                      fontSize: 11,
                      color: AppColors.activeBadge,
                      fontWeight: FontWeight.w600)),
            ),
        ],
      ),
    );
  }

  // ── Reply bar ──
  Widget _buildReplyBar() {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
      child: Row(
        children: [
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: const Color(0xFFF0F2F8),
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _replyCtrl,
                      style: const TextStyle(fontSize: 13.5),
                      onSubmitted: (_) => _sendReply(),
                      decoration: const InputDecoration(
                        hintText: 'Type a reply...',
                        hintStyle: TextStyle(color: AppColors.textGrey, fontSize: 13),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(vertical: 11),
                      ),
                    ),
                  ),
                  Container(
                    width: 6, height: 6,
                    decoration: const BoxDecoration(
                        color: Colors.red, shape: BoxShape.circle),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: _sendReply,
            child: Container(
              width: 44, height: 44,
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(22),
              ),
              child: const Icon(Icons.send_rounded, color: Colors.white, size: 18),
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  MESSAGE BUBBLE
// ─────────────────────────────────────────────────────────────
class _MessageBubble extends StatelessWidget {
  final ChatMessage msg;
  const _MessageBubble({required this.msg});

  bool get _isPatient => msg.sender == MessageSender.patient;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment:
            _isPatient ? CrossAxisAlignment.start : CrossAxisAlignment.start,
        children: [
          // ── Sender label ──
          Padding(
            padding: const EdgeInsets.only(bottom: 4, left: 44),
            child: Text(
              _isPatient ? 'Patient' : 'Chatbot',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: _isPatient ? AppColors.textGrey : AppColors.primary,
              ),
            ),
          ),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              // ── Avatar ──
              CircleAvatar(
                radius: 16,
                backgroundColor: _isPatient
                    ? const Color(0xFFE0E4ED)
                    : AppColors.primary.withValues(alpha: 0.15),
                child: Icon(
                  _isPatient ? Icons.person_outline_rounded : Icons.smart_toy_outlined,
                  size: 16,
                  color: _isPatient ? AppColors.textGrey : AppColors.primary,
                ),
              ),
              const SizedBox(width: 8),
              // ── Bubble ──
              Flexible(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: BoxDecoration(
                    color: _isPatient
                        ? AppColors.patientBubble
                        : const Color(0xFFE8F0FE),
                    borderRadius: BorderRadius.only(
                      topLeft: const Radius.circular(16),
                      topRight: const Radius.circular(16),
                      bottomLeft: Radius.circular(_isPatient ? 4 : 16),
                      bottomRight: Radius.circular(_isPatient ? 16 : 4),
                    ),
                  ),
                  child: Text(
                    msg.text,
                    style: TextStyle(
                      fontSize: 13.5,
                      color: _isPatient ? AppColors.textDark : AppColors.darkNavy,
                      height: 1.45,
                    ),
                  ),
                ),
              ),
            ],
          ),
          // ── Timestamp ──
          Padding(
            padding: const EdgeInsets.only(top: 4, left: 44),
            child: Text(msg.time,
                style: const TextStyle(fontSize: 10.5, color: AppColors.textGrey)),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  APP ICON (4-quadrant)
// ─────────────────────────────────────────────────────────────
class _AppIcon extends StatelessWidget {
  const _AppIcon();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 32, height: 32,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 4)],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: GridView.count(
          crossAxisCount: 2,
          padding: const EdgeInsets.all(5),
          mainAxisSpacing: 2, crossAxisSpacing: 2,
          physics: const NeverScrollableScrollPhysics(),
          children: const [
            Icon(Icons.favorite_outline,    color: Color(0xFFE53935), size: 10),
            Icon(Icons.medical_services,    color: Color(0xFF1565C0), size: 10),
            Icon(Icons.chat_bubble_outline, color: Color(0xFF43A047), size: 10),
            Icon(Icons.local_hospital,      color: Color(0xFFFF9800), size: 10),
          ],
        ),
      ),
    );
  }
}