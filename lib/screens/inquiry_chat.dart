import 'package:flutter/material.dart';

class InquiryChatStorage {
  static final Map<String, List<Map<String, dynamic>>> _messages = {};
  static final Map<String, String> _lastMessages = {};

  static List<Map<String, dynamic>> getMessages(String inquiryId) {
    return _messages[inquiryId] ?? [];
  }

  static String getLastMessage(String inquiryId) {
    return _lastMessages[inquiryId] ?? '';
  }

  static void addMessage(String inquiryId, Map<String, dynamic> message) {
    if (!_messages.containsKey(inquiryId)) {
      _messages[inquiryId] = [];
    }
    _messages[inquiryId]!.add(message);
    _lastMessages[inquiryId] = message['message'];
  }

  static void initializeMessages(
    String inquiryId,
    List<Map<String, dynamic>> initialMessages,
  ) {
    if (!_messages.containsKey(inquiryId) || _messages[inquiryId]!.isEmpty) {
      _messages[inquiryId] = List.from(initialMessages);
      if (initialMessages.isNotEmpty) {
        _lastMessages[inquiryId] = initialMessages.last['message'];
      }
    }
  }
}

class InquiryChatPage extends StatefulWidget {
  final String inquiryId;
  final String patientId;
  final String department;
  final String initialMessage;
  final String date;
  final String time;
  final VoidCallback? onReplySent;

  const InquiryChatPage({
    super.key,
    required this.inquiryId,
    required this.patientId,
    required this.department,
    required this.initialMessage,
    required this.date,
    required this.time,
    this.onReplySent,
  });

  @override
  State<InquiryChatPage> createState() => _InquiryChatPageState();
}

class _InquiryChatPageState extends State<InquiryChatPage> {
  final TextEditingController _replyController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  List<Map<String, dynamic>> _messages = [];
  bool _hasReplied = false;
  bool _canSend = true;

  @override
  void initState() {
    super.initState();
    _loadMessages();
  }

  void _loadMessages() {
    final List<Map<String, dynamic>> initialMessages = [
      {
        'sender': 'Patient',
        'message': 'My ID is ${widget.patientId}',
        'time': '10:27 AM',
        'isStaff': false,
      },
      {
        'sender': 'System',
        'message':
            'Thank you! I see you have a consultation scheduled with Dr. Reyes on May 28th. Would you like to reschedule or ask about preparation?',
        'time': '10:28 AM',
        'isStaff': false,
      },
      {
        'sender': 'Patient',
        'message': widget.initialMessage,
        'time': widget.time,
        'isStaff': false,
      },
      {
        'sender': 'System',
        'message':
            'Based on your records, the consultation is tentatively scheduled for June 15th. Would you like me to connect you with an admin for confirmation?',
        'time': '10:31 AM',
        'isStaff': false,
      },
    ];

    InquiryChatStorage.initializeMessages(widget.inquiryId, initialMessages);
    _messages = InquiryChatStorage.getMessages(widget.inquiryId);
  }

  @override
  void dispose() {
    _replyController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _sendReply() {
    // ✅ SIMPLE LANG - kung hindi pwede magsend, return
    if (!_canSend) return;

    final String message = _replyController.text.trim();
    if (message.isEmpty) return;

    // ✅ I-off muna ang send
    _canSend = false;

    final String currentTime = _getCurrentTime();

    if (!_hasReplied) {
      _hasReplied = true;
      if (widget.onReplySent != null) {
        widget.onReplySent!();
      }
    }

    final newMessage = {
      'sender': 'Staff',
      'message': message,
      'time': currentTime,
      'isStaff': true,
    };

    InquiryChatStorage.addMessage(widget.inquiryId, newMessage);

    _replyController.clear();

    setState(() {
      _messages.add(newMessage);
    });

    _scrollToBottom();

    // ✅ I-on ulit ang send after 1 second
    Future.delayed(const Duration(seconds: 1), () {
      _canSend = true;
    });
  }

  String _getCurrentTime() {
    final now = DateTime.now();
    int hour = now.hour;
    if (hour > 12) hour -= 12;
    if (hour == 0) hour = 12;
    final minute = now.minute.toString().padLeft(2, '0');
    final ampm = now.hour >= 12 ? 'PM' : 'AM';
    return '$hour:$minute $ampm';
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1A237E)),
          onPressed: () => Navigator.pop(context, true),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.inquiryId,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: Color(0xFF1A237E),
              ),
            ),
            Text(
              'ID: ${widget.patientId} · ${widget.department}',
              style: const TextStyle(fontSize: 11, color: Colors.grey),
            ),
          ],
        ),
        backgroundColor: Colors.white,
        elevation: 1,
        foregroundColor: const Color(0xFF1A237E),
        iconTheme: const IconThemeData(color: Color(0xFF1A237E)),
        actions: [
          IconButton(
            icon: const Icon(Icons.more_vert, color: Color(0xFF1A237E)),
            onPressed: () {},
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                final isStaff = message['isStaff'] as bool;
                return _buildMessage(
                  message['sender'],
                  message['message'],
                  message['time'],
                  isStaff,
                );
              },
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withAlpha(25),
                  spreadRadius: 1,
                  blurRadius: 4,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: TextField(
                      controller: _replyController,
                      decoration: const InputDecoration(
                        hintText: 'Type a reply...',
                        hintStyle: TextStyle(color: Colors.grey),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(vertical: 10),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                // ✅ PINAKASIMPLE - ElevatedButton lang
                ElevatedButton(
                  onPressed: _canSend ? _sendReply : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _canSend
                        ? const Color(0xFF1A237E)
                        : Colors.grey.shade400,
                    foregroundColor: Colors.white,
                    shape: const CircleBorder(),
                    padding: const EdgeInsets.all(12),
                    minimumSize: const Size(44, 44),
                  ),
                  child: const Icon(Icons.send, size: 20),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMessage(
    String sender,
    String message,
    String time,
    bool isStaff,
  ) {
    final bool isPatient = sender == 'Patient';
    final bool isStaffMessage = isStaff;

    Color backgroundColor;
    Color textColor;
    CrossAxisAlignment alignment;
    bool showSender = false;

    if (isStaffMessage) {
      backgroundColor = const Color(0xFF1A237E);
      textColor = Colors.white;
      alignment = CrossAxisAlignment.end;
      showSender = false;
    } else if (isPatient) {
      backgroundColor = Colors.white;
      textColor = Colors.black87;
      alignment = CrossAxisAlignment.start;
      showSender = true;
    } else {
      backgroundColor = Colors.grey.shade200;
      textColor = Colors.black87;
      alignment = CrossAxisAlignment.start;
      showSender = true;
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: alignment,
        children: [
          if (showSender)
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Text(
                sender,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey,
                ),
              ),
            ),
          Container(
            constraints: BoxConstraints(
              maxWidth: MediaQuery.of(context).size.width * 0.80,
            ),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: backgroundColor,
              borderRadius: BorderRadius.circular(16).copyWith(
                bottomLeft: isStaffMessage
                    ? const Radius.circular(16)
                    : const Radius.circular(4),
                bottomRight: isStaffMessage
                    ? const Radius.circular(4)
                    : const Radius.circular(16),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message,
                  style: TextStyle(fontSize: 14, color: textColor, height: 1.4),
                ),
                const SizedBox(height: 4),
                Text(
                  time,
                  style: TextStyle(
                    fontSize: 10,
                    color: isStaffMessage ? Colors.white70 : Colors.grey,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
