import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../../../cart/model/guest_cart_product.dart';
import '../../model/active_variant_items_model.dart';
import '../../model/active_variant_model.dart';
import 'product_details_cubit.dart';

class DetailsStateModel extends Equatable {
  final double totalPrice;
  final double totalSaved;
  final double totalDiscount;
  final double detailPrice;
  final double cartPrice;
  final double couponPrice;
  final double priceAfterCoupon;
  final double totalWithShipping;
  final List<ActiveVariantModel> variants;
  final List<ActiveVariantItemModel> variantItem;
  final List<GustCartProduct> savedProducts;
  final List<double> itemPrice;
  final List<int> vendorIds;
  final int productId;
  final int qty;
  final double totalWight;
  final int totalQty;
  final String selectedIndex;
  final int count;
  final GustCartProduct? product;
  final ProductDetailsState detailsState;

  const DetailsStateModel({
    this.productId = 0,
    this.qty = 1,
    this.selectedIndex = '',
    this.count = 0,
    this.totalPrice = 0.0,
    this.totalSaved = 0.0,
    this.totalDiscount = 0.0,
    this.cartPrice = 0.0,
    this.totalWithShipping = 0.0,
    this.detailPrice = 0.0,
    this.totalWight = 0.0,
    this.couponPrice = 0.0,
    this.priceAfterCoupon = 0.0,
    this.totalQty = 0,
    this.itemPrice = const <double>[],
    this.vendorIds = const <int>[],
    this.variants = const <ActiveVariantModel>[],
    this.savedProducts = const <GustCartProduct>[],
    this.variantItem = const <ActiveVariantItemModel>[],
    this.product,
    this.detailsState = const ProductDetailsInitial(),
  });

  DetailsStateModel copyWith({
    double? totalPrice,
    double? totalSaved,
    double? totalDiscount,
    double? detailPrice,
    double? cartPrice,
    double? couponPrice,
    double? priceAfterCoupon,
    double? totalWithShipping,
    int? productId,
    double? totalWight,
    int? totalQty,
    int? qty,
    String? selectedIndex,
    int? count,
    List<double>? itemPrice,
    List<int>? vendorIds,
    List<ActiveVariantModel>? variants,
    List<ActiveVariantItemModel>? variantItem,
    List<GustCartProduct>? savedProducts,
    GustCartProduct? product,
    ProductDetailsState? detailsState,
  }) {
    return DetailsStateModel(
      totalPrice: totalPrice ?? this.totalPrice,
      totalSaved: totalSaved ?? this.totalSaved,
      totalDiscount: totalDiscount ?? this.totalDiscount,
      detailPrice: detailPrice ?? this.detailPrice,
      itemPrice: itemPrice ?? this.itemPrice,
      vendorIds: vendorIds ?? this.vendorIds,
      cartPrice: cartPrice ?? this.cartPrice,
      totalWithShipping: totalWithShipping ?? this.totalWithShipping,
      variants: variants ?? this.variants,
      variantItem: variantItem ?? this.variantItem,
      productId: productId ?? this.productId,
      qty: qty ?? this.qty,
      totalWight: totalWight ?? this.totalWight,
      couponPrice: couponPrice ?? this.couponPrice,
      priceAfterCoupon: priceAfterCoupon ?? this.priceAfterCoupon,
      totalQty: totalQty ?? this.totalQty,
      savedProducts: savedProducts ?? this.savedProducts,
      product: product ?? this.product,
      selectedIndex: selectedIndex ?? this.selectedIndex,
      count: count ?? this.count,
      detailsState: detailsState ?? this.detailsState,
    );
  }

  Map<String, String> toMap() {
    final result = <String, String>{};

    // result.addAll({'product_id': productId.toString()});
    // result.addAll({'quantity': qty.toString()});
    return result;
  }

  String toJson() => json.encode(toMap());

  @override
  bool get stringify => true;

  static DetailsStateModel init() {
    return const DetailsStateModel(
      totalPrice: 0.0,
      totalSaved: 0.0,
      totalDiscount: 0.0,
      detailPrice: 0.0,
      cartPrice: 0.0,
      totalWithShipping: 0.0,
      productId: 0,
      totalWight: 0.0,
      couponPrice: 0.0,
      priceAfterCoupon: 0.0,
      totalQty: 1,
      qty: 1,
      selectedIndex: '',
      count: 0,
      product: null,
      itemPrice: <double>[],
      vendorIds: <int>[],
      variants: <ActiveVariantModel>[],
      savedProducts: <GustCartProduct>[],
      variantItem: <ActiveVariantItemModel>[],
      detailsState: ProductDetailsInitial(),
    );
  }

  @override
  List<Object?> get props {
    return [
      totalPrice,
      totalSaved,
      totalDiscount,
      detailPrice,
      itemPrice,
      vendorIds,
      cartPrice,
      couponPrice,
      priceAfterCoupon,
      variants,
      variantItem,
      productId,
      qty,
      totalWight,
      totalQty,
      product,
      savedProducts,
      selectedIndex,
      count,
      detailsState,
    ];
  }
}
