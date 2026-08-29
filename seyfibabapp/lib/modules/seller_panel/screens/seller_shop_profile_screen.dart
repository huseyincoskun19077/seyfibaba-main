import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/remote_urls.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../services/seller_api_service.dart';

class SellerShopProfileScreen extends StatefulWidget {
  const SellerShopProfileScreen({super.key});

  @override
  State<SellerShopProfileScreen> createState() =>
      _SellerShopProfileScreenState();
}

class _SellerShopProfileScreenState extends State<SellerShopProfileScreen> {
  final _service = SellerApiService();
  final _shopNameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _opensCtrl = TextEditingController();
  final _closedCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();
  final _greetingCtrl = TextEditingController();
  final _seoTitleCtrl = TextEditingController();
  final _seoDescCtrl = TextEditingController();

  bool _loading = true;
  bool _saving = false;
  String? _error;
  String? _logoUrl;
  String? _bannerUrl;
  String? _logoPath;
  String? _bannerPath;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _shopNameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _opensCtrl.dispose();
    _closedCtrl.dispose();
    _addressCtrl.dispose();
    _greetingCtrl.dispose();
    _seoTitleCtrl.dispose();
    _seoDescCtrl.dispose();
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await _service.fetchShopProfile(_token);
      final seller = data['seller'];
      if (seller is! Map) throw Exception('Mağaza bulunamadı');
      final s = Map<String, dynamic>.from(seller);
      _shopNameCtrl.text = '${s['shop_name'] ?? ''}';
      _emailCtrl.text = '${s['email'] ?? ''}';
      _phoneCtrl.text = '${s['phone'] ?? ''}';
      _opensCtrl.text = '${s['open_at'] ?? s['opens_at'] ?? ''}';
      _closedCtrl.text = '${s['closed_at'] ?? ''}';
      _addressCtrl.text = '${s['address'] ?? ''}';
      _greetingCtrl.text = '${s['greeting_msg'] ?? ''}';
      _seoTitleCtrl.text = '${s['seo_title'] ?? ''}';
      _seoDescCtrl.text = '${s['seo_description'] ?? ''}';
      _logoUrl = '${s['logo'] ?? ''}';
      _bannerUrl = '${s['banner_image'] ?? ''}';
      if (!mounted) return;
      setState(() => _loading = false);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _pick(bool logo) async {
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() {
      if (logo) {
        _logoPath = file.path;
      } else {
        _bannerPath = file.path;
      }
    });
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    Utils.loadingDialog(context);
    try {
      final msg = await _service.updateShopProfile(
        token: _token,
        fields: {
          'shop_name': _shopNameCtrl.text.trim(),
          'email': _emailCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
          'opens_at': _opensCtrl.text.trim(),
          'closed_at': _closedCtrl.text.trim(),
          'address': _addressCtrl.text.trim(),
          'greeting_msg': _greetingCtrl.text.trim(),
          'seo_title': _seoTitleCtrl.text.trim(),
          'seo_description': _seoDescCtrl.text.trim(),
        },
        logoPath: _logoPath,
        bannerPath: _bannerPath,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, msg);
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Mağaza Profili'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    _imageCard(
                      title: 'Logo',
                      networkPath: _logoUrl,
                      localPath: _logoPath,
                      onPick: () => _pick(true),
                    ),
                    const SizedBox(height: 12),
                    _imageCard(
                      title: 'Banner',
                      networkPath: _bannerUrl,
                      localPath: _bannerPath,
                      onPick: () => _pick(false),
                    ),
                    const SizedBox(height: 12),
                    _field(_shopNameCtrl, 'Mağaza adı *'),
                    _field(_emailCtrl, 'E-posta *'),
                    _field(_phoneCtrl, 'Telefon *'),
                    Row(
                      children: [
                        Expanded(child: _field(_opensCtrl, 'Açılış *')),
                        const SizedBox(width: 12),
                        Expanded(child: _field(_closedCtrl, 'Kapanış *')),
                      ],
                    ),
                    _field(_addressCtrl, 'Adres *', maxLines: 2),
                    _field(_greetingCtrl, 'Karşılama mesajı *', maxLines: 3),
                    _field(_seoTitleCtrl, 'SEO başlık'),
                    _field(_seoDescCtrl, 'SEO açıklama', maxLines: 3),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: _saving ? null : _save,
                      style: FilledButton.styleFrom(
                        backgroundColor: HomeTheme.brandYellow,
                        foregroundColor: HomeTheme.textDark,
                        minimumSize: const Size.fromHeight(48),
                      ),
                      child: const Text(
                        'Kaydet',
                        style: TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _field(TextEditingController c, String label, {int maxLines = 1}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        controller: c,
        maxLines: maxLines,
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          alignLabelWithHint: maxLines > 1,
        ),
      ),
    );
  }

  Widget _imageCard({
    required String title,
    required String? networkPath,
    required String? localPath,
    required VoidCallback onPick,
  }) {
    Widget preview;
    if (localPath != null) {
      preview = Image.file(File(localPath), height: 120, fit: BoxFit.cover);
    } else if (networkPath != null && networkPath.isNotEmpty) {
      preview = Image.network(
        RemoteUrls.imageUrl(networkPath),
        height: 120,
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) =>
            const SizedBox(height: 120, child: Icon(Icons.image_outlined)),
      );
    } else {
      preview = const SizedBox(
        height: 120,
        child: Center(child: Icon(Icons.image_outlined)),
      );
    }
    return Container(
      decoration: HomeTheme.cardDecoration(),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          SizedBox(width: double.infinity, child: preview),
          TextButton.icon(
            onPressed: onPick,
            icon: const Icon(Icons.photo_library),
            label: Text('$title değiştir'),
          ),
        ],
      ),
    );
  }
}
