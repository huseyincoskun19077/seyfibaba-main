import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/favorite_button.dart';
import '../../category/component/price_card_widget.dart';
import '../model/product_details_product_model.dart';
import '../widgets/product_image_viewer.dart';
import '../../home/model/product_model.dart';

class ProductHeaderComponent extends StatefulWidget {
  const ProductHeaderComponent(this.product, this.gallery, {super.key});

  final ProductDetailsProductModel product;
  final List<GalleryModel?> gallery;

  @override
  State<ProductHeaderComponent> createState() => _ProductHeaderComponentState();
}

class _ProductHeaderComponentState extends State<ProductHeaderComponent> {
  late String productThumb;
  int _selectedGalleryIndex = 0;

  @override
  void initState() {
    productThumb = widget.product.thumbImage;
    super.initState();
  }

  List<String> get _allImages {
    final images = <String>[widget.product.thumbImage];
    for (final item in widget.gallery) {
      if (item != null && item.image.isNotEmpty && !images.contains(item.image)) {
        images.add(item.image);
      }
    }
    return images;
  }

  @override
  Widget build(BuildContext context) {
    final images = _allImages;
    return Column(
      children: [
        SizedBox(
          height: 310,
          child: Stack(
            alignment: Alignment.topCenter,
            children: [
              Container(
                height: 270,
                decoration: BoxDecoration(
                  color: borderColor.withOpacity(.2),
                  borderRadius:
                      const BorderRadius.vertical(bottom: Radius.circular(20)),
                ),
              ),
              _buildThumbImage(images),
              _buildDiscountBadge(context),
              _buildPackBadge(),
              _buildFavBtn(widget.product.id),
              if (widget.gallery.isNotEmpty) _buildGalleryStrip(images),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildDiscountBadge(BuildContext context) {
    if (widget.product.offerPrice == 0) return const SizedBox.shrink();
    final badge = Utils.dorpPricePercentage(
      widget.product.price,
      widget.product.offerPrice,
    );
    if (badge.isEmpty) return const SizedBox.shrink();

    return Positioned(
      left: 16,
      top: 12,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: redColor,
          borderRadius: BorderRadius.circular(6),
        ),
        child: Text(
          badge,
          style: const TextStyle(
            color: whiteColor,
            fontWeight: FontWeight.w700,
            fontSize: 12,
          ),
        ),
      ),
    );
  }

  Widget _buildGalleryStrip(List<String> images) {
    return Positioned(
      left: 0,
      right: 0,
      bottom: 0,
      child: SizedBox(
        height: 72,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: images.length > 6 ? 6 : images.length,
          separatorBuilder: (_, __) => const SizedBox(width: 10),
          itemBuilder: (context, index) {
            final image = images[index];
            final selected = productThumb == image;
            return GestureDetector(
              onTap: () => setState(() {
                productThumb = image;
                _selectedGalleryIndex = index;
              }),
              child: Container(
                width: 64,
                decoration: BoxDecoration(
                  color: whiteColor,
                  border: Border.all(
                    color: selected ? Utils.dynamicPrimaryColor(context) : grayBorderColor,
                    width: selected ? 2 : 1,
                  ),
                ),
                padding: const EdgeInsets.all(4),
                child: CustomImage(
                  path: RemoteUrls.imageUrl(image),
                  fit: BoxFit.contain,
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildPackBadge() {
    return Positioned(
      left: 24,
      bottom: 86,
      child: ProductPackBadge(qty: widget.product.saleUnitQty),
    );
  }

  Widget _buildFavBtn(int id) {
    return Positioned(
      right: 20,
      top: 0,
      child: FavoriteButton(productId: id.toString()),
    );
  }

  Widget _buildThumbImage(List<String> images) {
    return Positioned(
      top: 24,
      left: 20,
      right: 20,
      bottom: 78,
      child: GestureDetector(
        onTap: () => ProductImageViewer.open(
          context,
          images: images,
          initialIndex: _selectedGalleryIndex,
        ),
        child: Hero(
          tag: 'product-image-${widget.product.id}-$productThumb',
          child: CustomImage(
            path: RemoteUrls.imageUrl(productThumb),
            fit: BoxFit.contain,
          ),
        ),
      ),
    );
  }
}
