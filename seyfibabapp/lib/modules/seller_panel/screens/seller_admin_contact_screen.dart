import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_admin_message_model.dart';
import '../services/seller_api_service.dart';

class SellerAdminContactScreen extends StatefulWidget {
  const SellerAdminContactScreen({super.key});

  @override
  State<SellerAdminContactScreen> createState() =>
      _SellerAdminContactScreenState();
}

class _SellerAdminContactScreenState extends State<SellerAdminContactScreen> {
  final _service = SellerApiService();
  final _subjectCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  List<SellerAdminMessage> _messages = const [];
  bool _loading = true;
  bool _sending = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _subjectCtrl.dispose();
    _messageCtrl.dispose();
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final messages = await _service.fetchAdminMessages(_token);
      if (!mounted) return;
      setState(() {
        _messages = messages;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _send() async {
    final subject = _subjectCtrl.text.trim();
    final message = _messageCtrl.text.trim();
    if (subject.isEmpty || message.isEmpty) {
      Utils.errorSnackBar(context, 'Konu ve mesaj zorunlu');
      return;
    }
    setState(() => _sending = true);
    Utils.loadingDialog(context);
    try {
      final msg = await _service.sendAdminMessage(
        token: _token,
        subject: subject,
        message: message,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, msg);
      _subjectCtrl.clear();
      _messageCtrl.clear();
      FocusScope.of(context).unfocus();
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Seyfibaba Destek'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              color: HomeTheme.brandYellow,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  const Text(
                    'Sorularınız ve talepleriniz için seyfibaba.com üzerinden bizimle iletişime geçebilirsiniz. '
                    'İsterseniz aşağıdaki formu da kullanabilirsiniz.',
                    style: TextStyle(color: HomeTheme.textMuted, height: 1.4),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: HomeTheme.cardDecoration(),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Yeni Talep',
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _subjectCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Konu *',
                            border: OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _messageCtrl,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            labelText: 'Mesaj *',
                            border: OutlineInputBorder(),
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton.icon(
                          onPressed: _sending ? null : _send,
                          icon: const Icon(Icons.send_outlined),
                          label: const Text('Gönder'),
                          style: FilledButton.styleFrom(
                            backgroundColor: HomeTheme.brandYellow,
                            foregroundColor: HomeTheme.textDark,
                            minimumSize: const Size.fromHeight(46),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 20),
                  const Text(
                    'Gönderilen Mesajlar',
                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  if (_messages.isEmpty)
                    const Padding(
                      padding: EdgeInsets.only(top: 24),
                      child: Center(
                        child: Text(
                          'Henüz mesaj göndermediniz.',
                          style: TextStyle(color: HomeTheme.textMuted),
                        ),
                      ),
                    )
                  else
                    ..._messages.map(
                      (m) => Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: HomeTheme.cardDecoration(),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    m.subject,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                                Text(
                                  m.createdAt.length >= 16
                                      ? m.createdAt.substring(0, 16)
                                      : m.createdAt,
                                  style: const TextStyle(
                                    fontSize: 11,
                                    color: HomeTheme.textMuted,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              m.message,
                              style: const TextStyle(
                                color: HomeTheme.textMuted,
                                height: 1.35,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}
