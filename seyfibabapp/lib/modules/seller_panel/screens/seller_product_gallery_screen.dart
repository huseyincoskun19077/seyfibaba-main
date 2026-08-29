import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/remote_urls.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../services/seller_api_service.dart';

class SellerProductGalleryScreen extends StatefulWidget {
  const SellerProductGalleryScreen({super.key, required this.productId});

  final int productId;

  @override
  State<SellerProductGalleryScreen> createState() =>
      _SellerProductGalleryScreenState();
}

class _SellerProductGalleryScreenState
    extends State<SellerProductGalleryScreen> {
  final _service = SellerApiService();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<List<Map<String, dynamic>>> _load() =>
      _service.fetchProductGallery(_token, widget.productId);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _upload() async {
    final files = await ImagePicker().pickMultiImage(
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (files.isEmpty) return;
    if (!mounted) return;
    try {
      Utils.loadingDialog(context);
      await _service.uploadProductGallery(
        token: _token,
        productId: widget.productId,
        imagePaths: files.map((e) => e.path).toList(),
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Görseller yüklendi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _delete(int id) async {
    try {
      Utils.loadingDialog(context);
      await _service.deleteProductGalleryImage(_token, id);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Görsel silindi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Ürün Galerisi'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
        actions: [
          IconButton(
            onPressed: _upload,
            icon: const Icon(Icons.add_photo_alternate_outlined),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _upload,
        backgroundColor: HomeTheme.brandYellow,
        child: const Icon(Icons.add, color: HomeTheme.textDark),
      ),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('${snapshot.error}'));
          }
          final items = snapshot.data ?? const [];
          if (items.isEmpty) {
            return RefreshIndicator(
              onRefresh: _refresh,
              child: ListView(
                children: const [
                  SizedBox(height: 120),
                  Center(child: Text('Galeri boş')),
                ],
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: _refresh,
            child: GridView.builder(
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
              ),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final id = int.tryParse('${item['id']}') ?? 0;
                final image = '${item['image'] ?? ''}';
                return Stack(
                  fit: StackFit.expand,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: Image.network(
                        RemoteUrls.imageUrl(image),
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          color: HomeTheme.bg,
                          child: const Icon(Icons.broken_image_outlined),
                        ),
                      ),
                    ),
                    Positioned(
                      right: 6,
                      top: 6,
                      child: Material(
                        color: Colors.black54,
                        borderRadius: BorderRadius.circular(20),
                        child: InkWell(
                          onTap: id > 0 ? () => _delete(id) : null,
                          borderRadius: BorderRadius.circular(20),
                          child: const Padding(
                            padding: EdgeInsets.all(6),
                            child: Icon(
                              Icons.delete_outline,
                              color: Colors.white,
                              size: 18,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
          );
        },
      ),
    );
  }
}
