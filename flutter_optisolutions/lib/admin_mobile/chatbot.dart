import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/inquiry_service.dart';

enum MessageSender { patient, bot }

class ChatMessage {
  final MessageSender sender;
  final String text;
  final String time;
  final String label; // e.g. "Patient", "Admin", "Staff"
  const ChatMessage({
    required this.sender,
    required this.text,
    required this.time,
    this.label = '',
  });
}

/// One inquiry thread, built from the /admin/inquiries API response
/// (see Inquiry::toApiArray() on the Laravel side).
class ChatThread {
  final String id; // "INQ-003"
  final String dbId; // raw inquiry_id, used for API calls
  final String patientId;
  final String department; // inquiry_type
  final String lastMessage;
  final String date;
  final String time;
  final String status; // Pending | In Progress | Resolved
  final bool hasNew;

  const ChatThread({
    required this.id,
    required this.dbId,
    required this.patientId,
    required this.department,
    required this.lastMessage,
    required this.date,
    required this.time,
    required this.status,
    required this.hasNew,
  });

  String get patientName =>
      patientId.isEmpty || patientId == 'Guest' ? 'Guest Patient' : 'Patient #$patientId';

  String get initials {
    if (patientId.isEmpty || patientId == 'Guest') return 'G';
    return patientId.length >= 2 ? patientId.substring(0, 2).toUpperCase() : patientId.toUpperCase();
  }

  bool get isActive => status == 'In Progress';

  /// Deterministic color per thread so the same inquiry always gets the
  /// same avatar color across rebuilds, without needing server data for it.
  Color get avatarColor {
    const palette = [
      Color(0xFF2E5AAC),
      Color(0xFF3B6FC4),
      Color(0xFF16294D),
      Color(0xFF1B3B6F),
    ];
    final idx = dbId.hashCode.abs() % palette.length;
    return palette[idx];
  }

  factory ChatThread.fromApi(Map<String, dynamic> json) {
    return ChatThread(
      id: (json['id'] ?? '').toString(),
      dbId: (json['dbId'] ?? '').toString(),
      patientId: (json['patientId'] ?? '').toString(),
      department: (json['department'] ?? 'General').toString(),
      lastMessage: (json['message'] ?? '').toString(),
      date: (json['date'] ?? '').toString(),
      time: (json['time'] ?? '').toString(),
      status: (json['status'] ?? 'Pending').toString(),
      hasNew: json['isNew'] == true,
    );
  }
}

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
  List<ChatThread> _threads = [];
  bool _loading = true;
  String? _loadError;

  @override
  void initState() {
    super.initState();
    _loadInquiries();
  }

  Future<void> _loadInquiries() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final result = await InquiryService.fetchAll();
      setState(() {
        _threads = result.map((e) => ChatThread.fromApi(e)).toList();
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

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
            // ── Single merged header: menu + icon + title ──
            _buildHeader(),
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
                      _buildSearchBar(),
                      const Divider(height: 1, color: AppColors.border),
                      Expanded(
                        child: _loading
                            ? const Center(child: CircularProgressIndicator())
                            : _loadError != null
                                ? Center(
                                    child: Column(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Padding(
                                          padding: const EdgeInsets.all(16),
                                          child: Text(
                                            _loadError!,
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(color: Colors.red),
                                          ),
                                        ),
                                        ElevatedButton(
                                          onPressed: _loadInquiries,
                                          child: const Text('Retry'),
                                        ),
                                      ],
                                    ),
                                  )
                                : _filtered.isEmpty
                                    ? const Center(
                                        child: Text(
                                          'No inquiries yet.\nPatient messages the chatbot\ncan\'t answer will show up here.',
                                          textAlign: TextAlign.center,
                                          style: TextStyle(color: AppColors.textGrey),
                                        ),
                                      )
                                    : RefreshIndicator(
                                        onRefresh: _loadInquiries,
                                        child: ListView.separated(
                                          itemCount: _filtered.length,
                                          separatorBuilder: (_, _) => const Divider(
                                              height: 1, indent: 16, color: AppColors.border),
                                          itemBuilder: (_, i) => _ChatTile(
                                            thread: _filtered[i],
                                            onTap: () async {
                                              final result = await Navigator.push(
                                                context,
                                                MaterialPageRoute(
                                                  builder: (_) => ChatDetailScreen(thread: _filtered[i]),
                                                ),
                                              );
                                              if (result == true) {
                                                _loadInquiries();
                                              }
                                            },
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

  // ── Merged header: hamburger + chat icon + "Inquiries" ──
  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 16, 10),
      child: Row(
        children: [
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu_rounded, color: AppColors.iconColor),
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
          Container(
            width: 30, height: 30,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.chat_bubble_outline_rounded,
                color: AppColors.primary, size: 15),
          ),
          const SizedBox(width: 10),
          const Text('Inquiries',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: AppColors.darkNavy,
              )),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Container(
        height: 38,
        decoration: BoxDecoration(
          color: AppColors.background,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.border),
        ),
        child: TextField(
          onChanged: (v) => setState(() => _search = v),
          style: const TextStyle(fontSize: 13),
          decoration: const InputDecoration(
            isDense: true,
            prefixIcon: Icon(Icons.search, size: 16, color: AppColors.textGrey),
            hintText: 'Search...',
            hintStyle: TextStyle(color: AppColors.textGrey, fontSize: 13),
            border: InputBorder.none,
            contentPadding: EdgeInsets.symmetric(vertical: 9, horizontal: 4),
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
                              fontWeight: FontWeight.bold, fontSize: 13.5,
                              color: AppColors.darkNavy)),
                      const SizedBox(width: 8),
                      Text(thread.department,
                          style: const TextStyle(
                              fontSize: 11, color: AppColors.textGrey)),
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
                      ] else if (thread.status == 'Resolved') ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.green.shade600,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Text('RESOLVED',
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
  final _replyCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  List<ChatMessage> _messages = [];
  bool _loading = true;
  String? _loadError;
  bool _canSend = true;
  bool _hasReplied = false;

  @override
  void initState() {
    super.initState();
    _loadMessages();
  }

  Future<void> _loadMessages() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final result = await InquiryService.fetchMessages(widget.thread.dbId);
      setState(() {
        _messages = [
          // The original patient message lives on the inquiry itself
          // (Inquiry::toApiArray()), not in the replies thread.
          ChatMessage(
            sender: MessageSender.patient,
            text: widget.thread.lastMessage,
            time: widget.thread.time,
            label: 'Patient',
          ),
          ...result.map((m) => ChatMessage(
                sender: (m['isStaff'] == true) ? MessageSender.bot : MessageSender.patient,
                text: (m['message'] ?? '').toString(),
                time: (m['time'] ?? '').toString(),
                label: (m['sender'] ?? '').toString(),
              )),
        ];
        _loading = false;
      });
      _scrollToBottomSoon();
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _scrollToBottomSoon() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollCtrl.hasClients) {
        _scrollCtrl.animateTo(
          _scrollCtrl.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendReply() async {
    if (!_canSend) return;
    final text = _replyCtrl.text.trim();
    if (text.isEmpty) return;

    setState(() => _canSend = false);
    final optimistic = ChatMessage(
      sender: MessageSender.bot,
      text: text,
      time: _nowTime(),
      label: 'You',
    );
    setState(() => _messages.add(optimistic));
    _replyCtrl.clear();
    _scrollToBottomSoon();

    try {
      await InquiryService.sendReply(widget.thread.dbId, text);
      _hasReplied = true;
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to send: $e'), backgroundColor: Colors.red),
        );
      }
    }
    if (mounted) setState(() => _canSend = true);
  }

  Future<void> _resolve() async {
    try {
      await InquiryService.resolve(widget.thread.dbId);
      _hasReplied = true;
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Inquiry marked as resolved.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to resolve: $e'), backgroundColor: Colors.red),
        );
      }
    }
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
    return WillPopScope(
      onWillPop: () async {
        Navigator.pop(context, _hasReplied);
        return false;
      },
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: SafeArea(
          child: Column(
            children: [
              _buildHeader(),
              Expanded(
                child: _loading
                    ? const Center(child: CircularProgressIndicator())
                    : _loadError != null
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(_loadError!, style: const TextStyle(color: Colors.red)),
                                const SizedBox(height: 12),
                                ElevatedButton(onPressed: _loadMessages, child: const Text('Retry')),
                              ],
                            ),
                          )
                        : ListView.builder(
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
                size: 18, color: AppColors.iconColor),
            onPressed: () => Navigator.pop(context, _hasReplied),
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
                            fontWeight: FontWeight.bold, fontSize: 14,
                            color: AppColors.darkNavy)),
                    const SizedBox(width: 5),
                    Container(
                      width: 7, height: 7,
                      decoration: const BoxDecoration(
                          color: AppColors.primary, shape: BoxShape.circle),
                    ),
                  ],
                ),
                Text(
                  'ID: ${widget.thread.patientId}  •  ${widget.thread.department}',
                  style: const TextStyle(fontSize: 11, color: AppColors.textGrey),
                ),
              ],
            ),
          ),
          if (widget.thread.status != 'Resolved')
            TextButton.icon(
              onPressed: _resolve,
              icon: const Icon(Icons.check_circle_outline, size: 16, color: AppColors.primary),
              label: const Text('Resolve', style: TextStyle(fontSize: 12, color: AppColors.primary)),
            )
          else
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.green.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text('Resolved',
                  style: TextStyle(
                      fontSize: 11,
                      color: Colors.green.shade700,
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
                color: AppColors.background,
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
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: _canSend ? _sendReply : null,
            child: Container(
              width: 44, height: 44,
              decoration: BoxDecoration(
                color: _canSend ? AppColors.primary : Colors.grey.shade400,
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
    final senderLabel = msg.label.isNotEmpty ? msg.label : (_isPatient ? 'Patient' : 'You');
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Sender label ──
          Padding(
            padding: const EdgeInsets.only(bottom: 4, left: 44),
            child: Text(
              senderLabel,
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
                  _isPatient ? Icons.person_outline_rounded : Icons.support_agent_rounded,
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
                        : AppColors.botBubble,
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