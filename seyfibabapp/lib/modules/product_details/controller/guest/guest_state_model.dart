import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../../model/active_variant_items_model.dart';
import '../../model/active_variant_model.dart';
import 'guest_cubit.dart';

class GuestStateModel extends Equatable {
  final double totalPrice;
  final double totalSaved;
  final double totalDiscount;
  final double detailPrice;
  final List<ActiveVariantModel> variants;
  final List<ActiveVariantItemModel> variantItem;
  final List<double> itemPrice;
  final int productId;
  final int quantity;
  final String selectedIndex;
  final int count;
  // final GustCartProduct? product;
  final GuestState guestState;

  const GuestStateModel({
    this.productId = 0,
    this.quantity = 1,
    this.selectedIndex = '',
    this.count = 0,
    this.totalPrice = 0.0,
    this.totalSaved = 0.0,
    this.totalDiscount = 0.0,
    this.detailPrice = 0.0,
    this.itemPrice = const <double>[],
    this.variants = const <ActiveVariantModel>[],
    this.variantItem = const <ActiveVariantItemModel>[],
    // this.product,
    this.guestState = const GuestInitial(),
  });

  GuestStateModel copyWith({
    double? totalPrice,
    double? totalSaved,
    double? totalDiscount,
    double? detailPrice,
    int? productId,
    int? quantity,
    String? selectedIndex,
    int? count,
    List<double>? itemPrice,
    List<ActiveVariantModel>? variants,
    List<ActiveVariantItemModel>? variantItem,
    // GustCartProduct? product,
    GuestState? guestState,
  }) {
    return GuestStateModel(
      totalPrice: totalPrice ?? this.totalPrice,
      totalSaved: totalSaved ?? this.totalSaved,
      totalDiscount: totalDiscount ?? this.totalDiscount,
      detailPrice: detailPrice ?? this.detailPrice,
      itemPrice: itemPrice ?? this.itemPrice,
      variants: variants ?? this.variants,
      variantItem: variantItem ?? this.variantItem,
      productId: productId ?? this.productId,
      quantity: quantity ?? this.quantity,
      // product: product ?? this.product,
      selectedIndex: selectedIndex ?? this.selectedIndex,
      count: count ?? this.count,
      guestState: guestState ?? this.guestState,
    );
  }

  Map<String, String> toMap() {
    final result = <String, String>{};

    result.addAll({'product_id': productId.toString()});
    result.addAll({'quantity': quantity.toString()});
    
    return result;
  }

  String toJson() => json.encode(toMap());


  /*static DetailsStateModel init() {
    return const DetailsStateModel(
      totalPrice: 0.0,
      totalSaved: 0.0,
      totalDiscount: 0.0,
      detailPrice: 0.0,
      productId: 0,
      qty: 1,
      selectedIndex: '',
      count: 0,
      product: null,
      itemPrice: <double>[],
      variants: <ActiveVariantModel>[],
      variantItem: <ActiveVariantItemModel>[],
      detailsState: ProductDetailsInitial(),
    );
  }*/

  @override
  List<Object> get props {
    return [
      totalPrice,
      totalSaved,
      totalDiscount,
      detailPrice,
      itemPrice,
      variants,
      variantItem,
      productId,
      quantity,
      // product,
      selectedIndex,
      count,
      guestState,
    ];
  }

  // @override
  // bool get stringify => true;

}
