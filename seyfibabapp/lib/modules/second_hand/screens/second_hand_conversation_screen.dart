import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/error/exception.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import '../widgets/second_hand_ui.dart';

class SecondHandConversationScreen extends StatefulWidget {
  const SecondHandConversationScreen({super.key, required this.conversationId});

  final int conversationId;

  @override
  State<SecondHandConversationScreen> createState() =>
      _SecondHandConversationScreenState();
}

class _SecondHandConversationScreenState
    extends State<SecondHandConversationScreen> {
  final _service = SecondHandService();
  final _scrollController = ScrollController();
  final _messageController = TextEditingController();

  final List<SecondHandMessage> _messages = [];
  bool _loading = true;
  bool _sending = false;
  int? _currentUserId;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _currentUserId = context.read<LoginBloc>().userInfo?.user.id;
      _load();
    });
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  String get _token =>
      context.read<LoginBloc>().userInfo?.accessToken ?? '';

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      await _service.markConversationRead(
        token: _token,
        conversationId: widget.conversationId,
      );
      final result = await _service.fetchConversationMessages(
        token: _token,
        conversationId: widget.conversationId,
        page: 1,
      );
      if (!mounted) return;
      setState(() {
        _messages
          ..clear()
          ..addAll(result.items.reversed);
        _loading = false;
      });
      _scrollToBottom();
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      Utils.errorSnackBar(context, _errorMessage(e));
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  String _errorMessage(Object e) {
    if (e is ServerException) return e.message;
    if (e is UnauthorisedException) return e.message;
    if (e is BadRequestException) return e.message;
    return 'Mesajlar yüklenemedi.';
  }

  Future<void> _send() async {
    final text = _messageController.text.trim();
    if (text.isEmpty || _sending) return;

    setState(() => _sending = true);
    try {
      final message = await _service.sendToConversation(
        token: _token,
        conversationId: widget.conversationId,
        body: text,
      );
      if (!mounted) return;
      _messageController.clear();
      setState(() {
        _messages.add(message);
        _sending = false;
      });
      _scrollToBottom();
    } catch (e) {
      if (!mounted) return;
      setState(() => _sending = false);
      Utils.errorSnackBar(context, _errorMessage(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ShTheme.bg,
      appBar: const ShAppBar(
        title: 'Mesajlaşma',
      ),
      body: Column(
        children: [
          const ShMarketplaceNotice(compact: true),
          Expanded(
            child: _loading
                ? const ShLoading()
                : _messages.isEmpty
                    ? const ShEmptyState(
                        icon: Icons.chat_outlined,
                        title: 'Henüz mesaj yok',
                        subtitle: 'İlk mesajı siz gönderin.',
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                        itemCount: _messages.length,
                        itemBuilder: (context, index) {
                          final message = _messages[index];
                          final isMine = _currentUserId != null &&
                              message.senderId == _currentUserId;
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(
                              mainAxisAlignment: isMine
                                  ? MainAxisAlignment.end
                                  : MainAxisAlignment.start,
                              children: [
                                if (!isMine) ...[
                                  CircleAvatar(
                                    radius: 14,
                                    backgroundColor:
                                        ShTheme.border.withValues(alpha: 0.8),
                                    child: const Icon(
                                      Icons.person,
                                      size: 16,
                                      color: ShTheme.muted,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                ],
                                Flexible(
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 14,
                                      vertical: 10,
                                    ),
                                    decoration: BoxDecoration(
                                      color: isMine
                                          ? ShTheme.primary
                                          : ShTheme.card,
                                      borderRadius: BorderRadius.only(
                                        topLeft: const Radius.circular(16),
                                        topRight: const Radius.circular(16),
                                        bottomLeft: Radius.circular(
                                          isMine ? 16 : 4,
                                        ),
                                        bottomRight: Radius.circular(
                                          isMine ? 4 : 16,
                                        ),
                                      ),
                                      border: isMine
                                          ? null
                                          : Border.all(color: ShTheme.border),
                                    ),
                                    child: Text(
                                      message.body,
                                      style: const TextStyle(
                                        fontSize: 14,
                                        height: 1.4,
                                        color: ShTheme.dark,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
          ),
          Container(
            color: ShTheme.card,
            padding: EdgeInsets.fromLTRB(
              12,
              10,
              12,
              MediaQuery.of(context).padding.bottom + 10,
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _messageController,
                    textInputAction: TextInputAction.send,
                    decoration: InputDecoration(
                      hintText: 'Mesajınızı yazın…',
                      hintStyle: const TextStyle(color: ShTheme.muted),
                      filled: true,
                      fillColor: ShTheme.bg,
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(24),
                        borderSide: BorderSide.none,
                      ),
                    ),
                    onSubmitted: (_) => _send(),
                  ),
                ),
                const SizedBox(width: 8),
                Material(
                  color: _sending
                      ? ShTheme.primary.withValues(alpha: 0.5)
                      : ShTheme.primary,
                  shape: const CircleBorder(),
                  child: InkWell(
                    onTap: _sending ? null : _send,
                    customBorder: const CircleBorder(),
                    child: SizedBox(
                      width: 46,
                      height: 46,
                      child: Center(
                        child: _sending
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: ShTheme.dark,
                                ),
                              )
                            : const Icon(
                                Icons.send_rounded,
                                color: ShTheme.dark,
                                size: 22,
                              ),
                      ),
                    ),
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
