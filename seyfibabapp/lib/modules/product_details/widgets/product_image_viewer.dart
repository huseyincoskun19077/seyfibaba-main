import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/constants.dart';
import '../../../widgets/custom_image.dart';

class ProductImageViewer extends StatefulWidget {
  const ProductImageViewer({
    super.key,
    required this.images,
    this.initialIndex = 0,
  });

  final List<String> images;
  final int initialIndex;

  static Future<void> open(
    BuildContext context, {
    required List<String> images,
    int initialIndex = 0,
  }) {
    if (images.isEmpty) return Future.value();
    return Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => ProductImageViewer(
          images: images,
          initialIndex: initialIndex.clamp(0, images.length - 1),
        ),
      ),
    );
  }

  @override
  State<ProductImageViewer> createState() => _ProductImageViewerState();
}

class _ProductImageViewerState extends State<ProductImageViewer> {
  late final PageController _controller;
  late int _index;

  @override
  void initState() {
    super.initState();
    _index = widget.initialIndex;
    _controller = PageController(initialPage: widget.initialIndex);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: whiteColor,
        title: Text('${_index + 1}/${widget.images.length}'),
      ),
      body: PageView.builder(
        controller: _controller,
        itemCount: widget.images.length,
        onPageChanged: (i) => setState(() => _index = i),
        itemBuilder: (context, index) {
          return InteractiveViewer(
            child: Center(
              child: CustomImage(
                path: RemoteUrls.imageUrl(widget.images[index]),
                fit: BoxFit.contain,
              ),
            ),
          );
        },
      ),
    );
  }
}
