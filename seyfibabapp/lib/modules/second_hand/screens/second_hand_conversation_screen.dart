import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/error/exception.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import '../widgets/second_hand_ui.dart';

const _quickEmojis = [
  '😀',
  '😂',
  '😍',
  '👍',
  '🙏',
  '🔥',
  '✅',
  '❌',
  '💰',
  '📦',
  '📍',
  '👋',
];

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
  final _focusNode = FocusNode();

  final List<SecondHandMessage> _messages = [];
  SecondHandConversation? _conversation;
  final List<String> _pendingAttachments = [];
  bool _loading = true;
  bool _sending = false;
  bool _showEmojiBar = false;
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
    _focusNode.dispose();
    super.dispose();
  }

  String get _token =>
      context.read<LoginBloc>().userInfo?.accessToken ?? '';

  String _formatPrice(num? price) {
    if (price == null) return '';
    return NumberFormat.currency(
      locale: 'tr_TR',
      symbol: '₺',
      decimalDigits: 0,
    ).format(price);
  }

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
        _conversation = result.conversation;
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

  Future<void> _pickAttachment() async {
    final path = await Utils.pickImageFromCameraOrGallery(
      context,
      allowPdf: true,
    );
    if (path == null || path.isEmpty) return;
    if (!mounted) return;
    if (_pendingAttachments.length >= 5) {
      Utils.errorSnackBar(context, 'En fazla 5 ek gönderebilirsiniz.');
      return;
    }
    setState(() => _pendingAttachments.add(path));
  }

  void _insertEmoji(String emoji) {
    final text = _messageController.text;
    final selection = _messageController.selection;
    final start = selection.start >= 0 ? selection.start : text.length;
    final end = selection.end >= 0 ? selection.end : text.length;
    final next = text.replaceRange(start, end, emoji);
    _messageController.value = TextEditingValue(
      text: next,
      selection: TextSelection.collapsed(offset: start + emoji.length),
    );
  }

  Future<void> _send() async {
    final text = _messageController.text.trim();
    if ((text.isEmpty && _pendingAttachments.isEmpty) || _sending) return;

    setState(() => _sending = true);
    try {
      final message = await _service.sendToConversation(
        token: _token,
        conversationId: widget.conversationId,
        body: text,
        attachmentPaths: List<String>.from(_pendingAttachments),
      );
      if (!mounted) return;
      _messageController.clear();
      setState(() {
        _pendingAttachments.clear();
        _messages.add(message);
        _sending = false;
        _showEmojiBar = false;
      });
      _scrollToBottom();
    } catch (e) {
      if (!mounted) return;
      setState(() => _sending = false);
      Utils.errorSnackBar(context, _errorMessage(e));
    }
  }

  void _openListing() {
    final listingId = _conversation?.listingId;
    if (listingId == null || listingId <= 0) return;
    Navigator.pushNamed(
      context,
      RouteNames.secondHandDetailScreen,
      arguments: listingId,
    );
  }

  Future<void> _openAttachment(SecondHandMessageAttachment attachment) async {
    final url = SecondHandService.resolveAttachmentUrl(attachment);
    if (url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final title = _conversation?.counterpartyDisplay.trim().isNotEmpty == true
        ? _conversation!.counterpartyDisplay
        : 'Mesajlaşma';

    return Scaffold(
      backgroundColor: ShTheme.bg,
      appBar: ShAppBar(title: title),
      body: Column(
        children: [
          const ShMarketplaceNotice(compact: true),
          if (_conversation != null) _buildListingBanner(),
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
                          return _MessageBubble(
                            message: message,
                            isMine: isMine,
                            onOpenAttachment: _openAttachment,
                          );
                        },
                      ),
          ),
          if (_pendingAttachments.isNotEmpty) _buildPendingAttachments(),
          if (_showEmojiBar) _buildEmojiBar(),
          _buildComposer(),
        ],
      ),
    );
  }

  Widget _buildListingBanner() {
    final conv = _conversation!;
    final imageUrl = conv.listingImageId != null && conv.listingImageId! > 0
        ? SecondHandService.listingImageUrl(conv.listingImageId!)
        : '';
    final price = _formatPrice(conv.listingPrice);

    return Material(
      color: ShTheme.card,
      child: InkWell(
        onTap: _openListing,
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
          decoration: const BoxDecoration(
            border: Border(bottom: BorderSide(color: ShTheme.border)),
          ),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: SizedBox(
                  width: 56,
                  height: 56,
                  child: imageUrl.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: imageUrl,
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => _listingPlaceholder(),
                          placeholder: (_, __) => _listingPlaceholder(),
                        )
                      : _listingPlaceholder(),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      conv.listingTitle.isNotEmpty
                          ? conv.listingTitle
                          : 'İlan',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: ShTheme.dark,
                      ),
                    ),
                    if (price.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        price,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                          color: ShTheme.dark,
                        ),
                      ),
                    ],
                    const SizedBox(height: 2),
                    const Text(
                      'İlana git',
                      style: TextStyle(
                        fontSize: 12,
                        color: ShTheme.muted,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right, color: ShTheme.muted),
            ],
          ),
        ),
      ),
    );
  }

  Widget _listingPlaceholder() {
    return Container(
      color: ShTheme.border.withValues(alpha: 0.5),
      child: const Icon(Icons.image_outlined, color: ShTheme.muted),
    );
  }

  Widget _buildPendingAttachments() {
    return Container(
      width: double.infinity,
      color: ShTheme.card,
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 0),
      child: SizedBox(
        height: 72,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          itemCount: _pendingAttachments.length,
          separatorBuilder: (_, __) => const SizedBox(width: 8),
          itemBuilder: (context, index) {
            final path = _pendingAttachments[index];
            final isPdf = path.toLowerCase().endsWith('.pdf');
            return Stack(
              children: [
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    color: ShTheme.bg,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: ShTheme.border),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: isPdf
                      ? const Center(
                          child: Icon(Icons.picture_as_pdf, color: ShTheme.muted),
                        )
                      : Image.file(File(path), fit: BoxFit.cover),
                ),
                Positioned(
                  top: 2,
                  right: 2,
                  child: InkWell(
                    onTap: () {
                      setState(() => _pendingAttachments.removeAt(index));
                    },
                    child: Container(
                      decoration: const BoxDecoration(
                        color: Colors.black54,
                        shape: BoxShape.circle,
                      ),
                      padding: const EdgeInsets.all(2),
                      child: const Icon(Icons.close, size: 14, color: Colors.white),
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildEmojiBar() {
    return Container(
      width: double.infinity,
      color: ShTheme.card,
      padding: const EdgeInsets.fromLTRB(8, 6, 8, 0),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: _quickEmojis
              .map(
                (e) => InkWell(
                  onTap: () => _insertEmoji(e),
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                    child: Text(e, style: const TextStyle(fontSize: 22)),
                  ),
                ),
              )
              .toList(),
        ),
      ),
    );
  }

  Widget _buildComposer() {
    return Container(
      color: ShTheme.card,
      padding: EdgeInsets.fromLTRB(
        8,
        10,
        12,
        MediaQuery.of(context).padding.bottom + 10,
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: _sending ? null : _pickAttachment,
            icon: const Icon(Icons.attach_file_rounded, color: ShTheme.dark),
            tooltip: 'Fotoğraf / dosya',
          ),
          IconButton(
            onPressed: () {
              setState(() => _showEmojiBar = !_showEmojiBar);
              if (_showEmojiBar) {
                _focusNode.requestFocus();
              }
            },
            icon: Icon(
              _showEmojiBar
                  ? Icons.keyboard_alt_outlined
                  : Icons.emoji_emotions_outlined,
              color: ShTheme.dark,
            ),
            tooltip: 'Emoji',
          ),
          Expanded(
            child: TextField(
              controller: _messageController,
              focusNode: _focusNode,
              textInputAction: TextInputAction.send,
              minLines: 1,
              maxLines: 4,
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
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({
    required this.message,
    required this.isMine,
    required this.onOpenAttachment,
  });

  final SecondHandMessage message;
  final bool isMine;
  final Future<void> Function(SecondHandMessageAttachment) onOpenAttachment;

  @override
  Widget build(BuildContext context) {
    final hasText = message.body.trim().isNotEmpty;
    final attachments = message.attachments;

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        mainAxisAlignment:
            isMine ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          if (!isMine) ...[
            CircleAvatar(
              radius: 14,
              backgroundColor: ShTheme.border.withValues(alpha: 0.8),
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
                horizontal: 12,
                vertical: 10,
              ),
              decoration: BoxDecoration(
                color: isMine ? ShTheme.primary : ShTheme.card,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(isMine ? 16 : 4),
                  bottomRight: Radius.circular(isMine ? 4 : 16),
                ),
                border: isMine ? null : Border.all(color: ShTheme.border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (attachments.isNotEmpty)
                    ...attachments.map((att) {
                      final url =
                          SecondHandService.resolveAttachmentUrl(att);
                      if (att.isImage && url.isNotEmpty) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 6),
                          child: GestureDetector(
                            onTap: () => onOpenAttachment(att),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(12),
                              child: ConstrainedBox(
                                constraints: const BoxConstraints(
                                  maxWidth: 220,
                                  maxHeight: 220,
                                ),
                                child: CachedNetworkImage(
                                  imageUrl: url,
                                  fit: BoxFit.cover,
                                  placeholder: (_, __) => Container(
                                    height: 120,
                                    width: 160,
                                    color: ShTheme.bg,
                                    child: const Center(
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    ),
                                  ),
                                  errorWidget: (_, __, ___) => Container(
                                    height: 80,
                                    width: 140,
                                    color: ShTheme.bg,
                                    child: const Icon(Icons.broken_image),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        );
                      }
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: InkWell(
                          onTap: () => onOpenAttachment(att),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.insert_drive_file_outlined,
                                  size: 18),
                              const SizedBox(width: 6),
                              Flexible(
                                child: Text(
                                  att.originalName?.isNotEmpty == true
                                      ? att.originalName!
                                      : 'Dosya',
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    decoration: TextDecoration.underline,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  if (hasText)
                    Text(
                      message.body,
                      style: const TextStyle(
                        fontSize: 14,
                        height: 1.4,
                        color: ShTheme.dark,
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
