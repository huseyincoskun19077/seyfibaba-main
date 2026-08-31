import 'package:flutter/material.dart';

import '../../home/widgets/home_theme.dart';
import '../services/seller_api_service.dart';

class SellerAiFab extends StatefulWidget {
  const SellerAiFab({super.key, required this.token});

  final String token;

  @override
  State<SellerAiFab> createState() => _SellerAiFabState();
}

class _SellerAiFabState extends State<SellerAiFab> {
  final _service = SellerApiService();

  Future<void> _openChat() async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: HomeTheme.bg,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) {
        return _SellerAiChatSheet(
          token: widget.token,
          service: _service,
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton(
      onPressed: _openChat,
      backgroundColor: HomeTheme.brandYellow,
      foregroundColor: HomeTheme.textDark,
      tooltip: 'AI Asistan',
      child: const Icon(Icons.smart_toy_outlined),
    );
  }
}

class _SellerAiChatSheet extends StatefulWidget {
  const _SellerAiChatSheet({
    required this.token,
    required this.service,
  });

  final String token;
  final SellerApiService service;

  @override
  State<_SellerAiChatSheet> createState() => _SellerAiChatSheetState();
}

class _SellerAiChatSheetState extends State<_SellerAiChatSheet> {
  final _ctrl = TextEditingController();
  final _scroll = ScrollController();
  final List<Map<String, String>> _history = [
    {
      'role': 'intro',
      'content':
          'Merhaba, ben satıcı AI asistanınız. Mağazanız için şunlarda yardımcı olurum:\n'
          '• Sipariş ve bekleyen sipariş özeti\n'
          '• Stok ve ürün sayısı\n'
          '• Ürün adı, fiyat, indirimli fiyat ve stok güncelleme\n'
          '• Kısa/uzun açıklama yazma veya düzeltme\n'
          '• Ürünü yayına alma veya kapatma\n\n'
          'Ürün silmem. Toplu Excel ve hızlı ürün ekleme için paneldeki ilgili sayfaları kullanın.\n'
          'Örnek: “Bugün kaç sipariş var?” veya “X ürününün stoğunu 20 yap.”',
    },
  ];
  String? _actionTaken;
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _ctrl.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final message = _ctrl.text.trim();
    if (message.isEmpty || _sending) return;
    setState(() {
      _sending = true;
      _error = null;
      _history.add({'role': 'user', 'content': message});
      _ctrl.clear();
    });
    _scrollToEnd();
    try {
      final historyPayload = _history
          .where((m) => m['role'] == 'user' || m['role'] == 'assistant')
          .map((m) => {'role': m['role']!, 'content': m['content']!})
          .toList();
      // Exclude the just-added user message from history payload (API gets message + prior history)
      final prior = historyPayload.length > 1
          ? historyPayload.sublist(0, historyPayload.length - 1)
          : <Map<String, String>>[];

      final result = await widget.service.chatAiAssistant(
        token: widget.token,
        message: message,
        history: prior,
      );
      if (!mounted) return;
      final reply = '${result['reply'] ?? ''}'.trim();
      final action = result['action_taken']?.toString();
      setState(() {
        if (reply.isNotEmpty) {
          _history.add({'role': 'assistant', 'content': reply});
        }
        _actionTaken =
            (action != null && action.isNotEmpty && action != 'null')
                ? action
                : null;
        _sending = false;
      });
      _scrollToEnd();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _sending = false;
        _error = '$e';
      });
    }
  }

  void _scrollToEnd() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;
      _scroll.animateTo(
        _scroll.position.maxScrollExtent + 80,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.viewInsetsOf(context).bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: SafeArea(
        child: SizedBox(
          height: MediaQuery.sizeOf(context).height * 0.72,
          child: Column(
            children: [
              const SizedBox(height: 10),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: HomeTheme.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 8, 8),
                child: Row(
                  children: [
                    const Expanded(
                      child: Text(
                        'Satıcı AI Asistan',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
              ),
              if (_actionTaken != null && _actionTaken!.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: HomeTheme.brandYellow.withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'İşlem: $_actionTaken',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              Expanded(
                child: ListView.builder(
                        controller: _scroll,
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                        itemCount: _history.length,
                        itemBuilder: (context, index) {
                          final msg = _history[index];
                          final isUser = msg['role'] == 'user';
                          return Align(
                            alignment: isUser
                                ? Alignment.centerRight
                                : Alignment.centerLeft,
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 10,
                              ),
                              constraints: BoxConstraints(
                                maxWidth:
                                    MediaQuery.sizeOf(context).width * 0.78,
                              ),
                              decoration: BoxDecoration(
                                color: isUser
                                    ? HomeTheme.brandYellow
                                        .withValues(alpha: 0.45)
                                    : HomeTheme.card,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: HomeTheme.border.withValues(
                                    alpha: 0.5,
                                  ),
                                ),
                              ),
                              child: Text(
                                msg['content'] ?? '',
                                style: const TextStyle(height: 1.35),
                              ),
                            ),
                          );
                        },
                      ),
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Text(
                    _error!,
                    style: const TextStyle(color: Colors.redAccent, fontSize: 12),
                  ),
                ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _ctrl,
                        minLines: 1,
                        maxLines: 4,
                        textInputAction: TextInputAction.send,
                        onSubmitted: (_) => _send(),
                        decoration: const InputDecoration(
                          hintText: 'Mesaj yazın...',
                          border: OutlineInputBorder(),
                          isDense: true,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton.filled(
                      onPressed: _sending ? null : _send,
                      style: IconButton.styleFrom(
                        backgroundColor: HomeTheme.brandYellow,
                        foregroundColor: HomeTheme.textDark,
                      ),
                      icon: _sending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
