import 'dart:convert';

import 'package:equatable/equatable.dart';

class GustCartProduct extends Equatable {
  final int productId;
  final int qty;
  final GuestProduct? product;
  final List<GuestVariant>? variants;
  const GustCartProduct({
    required this.productId,
    required this.qty,
    required this.product,
    required this.variants,
  });

  GustCartProduct copyWith({
    int? productId,
    int? qty,
    GuestProduct? product,
    List<GuestVariant>? variants,
  }) {
    return GustCartProduct(
      productId: productId ?? this.productId,
      qty: qty ?? this.qty,
      product: product ?? this.product,
      variants: variants ?? this.variants,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{};

    result.addAll({'product_id': productId});
    result.addAll({'qty': qty});
    result.addAll({'product': product?.toMap()??{}});
    if(variants?.isNotEmpty??false){
      result.addAll({'variants': variants?.isNotEmpty??false? variants?.map((x) => x.toMap()).toList():[]});
    }

    return result;
  }

  factory GustCartProduct.fromMap(Map<String, dynamic> map) {
    return GustCartProduct(
      productId: map['product_id'] != null? int.parse(map['product_id'].toString()):0,
      qty: map['qty'] != null? int.parse(map['qty'].toString()) :0,
      product: map['product'] != null ? GuestProduct.fromMap(map['product'] as Map<String,dynamic>) : null,
      variants: map['variants'] != null ? List<GuestVariant>.from((map['variants'] as List<dynamic>).map<GuestVariant?>((x) => GuestVariant.fromMap(x as Map<String,dynamic>),),) : null,
    );
  }

  String toJson() => json.encode(toMap());

  factory GustCartProduct.fromJson(String source) => GustCartProduct.fromMap(json.decode(source) as Map<String, dynamic>);


  static List<GustCartProduct> fromJsonList(String source) {
    final List<dynamic> data = json.decode(source) as List<dynamic>;
    return data.map((json) => GustCartProduct.fromMap(json as Map<String, dynamic>)).toList();
  }

  static String toJsonList(List<GustCartProduct> models) {
    final List<Map<String, dynamic>> data = models.map((model) => model.toMap()).toList();
    return json.encode(data);
  }


  @override
  bool get stringify => true;

  @override
  List<Object?> get props => [productId, qty, product, variants];
}

class GuestProduct extends Equatable {
  final int id;
  final int vendorId;
  final String name;
  final String shortName;
  final String slug;
  final double weight;
  final String thumbImage;
  final double price;
  final double offerPrice;
  const GuestProduct({
    required this.id,
    required this.vendorId,
    required this.name,
    required this.shortName,
    required this.slug,
    required this.weight,
    required this.thumbImage,
    required this.price,
    required this.offerPrice,
  });

  GuestProduct copyWith({
    int? id,
    int? vendorId,
    String? name,
    String? shortName,
    String? slug,
    double? weight,
    String? thumbImage,
    double? price,
    double? offerPrice,
  }) {
    return GuestProduct(
      id: id ?? this.id,
      vendorId: vendorId ?? this.vendorId,
      name: name ?? this.name,
      shortName: shortName ?? this.shortName,
      slug: slug ?? this.slug,
      weight: weight ?? this.weight,
      thumbImage: thumbImage ?? this.thumbImage,
      price: price ?? this.price,
      offerPrice: offerPrice ?? this.offerPrice,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'vendor_id': vendorId,
      'name': name,
      'short_name': shortName,
      'slug': slug,
      'weight': weight,
      'thumb_image': thumbImage,
      'price': price,
      'offer_price': offerPrice,
    };
  }

  factory GuestProduct.fromMap(Map<String, dynamic> map) {
    return GuestProduct(
      id: map['id'] ?? 0 ,
      vendorId: map['vendor_id'] != null? int.parse(map['vendor_id'].toString()):0,
      name: map['name'] ?? '',
      shortName: map['short_name'] ?? '',
      slug: map['slug'] ?? '',
      weight: map['weight'] != null ? double.parse(map['weight'].toString()) : 0.0,
      thumbImage: map['thumb_image'] ?? '',
      price: map['price'] != null? double.parse(map['price'].toString()):0.0,
      offerPrice: map['offer_price'] != null? double.parse(map['offer_price'].toString()):0.0,
    );
  }

  String toJson() => json.encode(toMap());

  factory GuestProduct.fromJson(String source) => GuestProduct.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props {
    return [
      id,
      vendorId,
      name,
      shortName,
      slug,
      weight,
      thumbImage,
      price,
      offerPrice,
    ];
  }
}

class GuestVariant extends Equatable {
  final int productId;
  final int variantId;
  final int variantItemId;
  final GuestVariantItem? variantItem;

  const GuestVariant({
    required this.productId,
    required this.variantId,
    required this.variantItemId,
    required this.variantItem,
  });

  GuestVariant copyWith({
    int? productId,
    int? variantId,
    int? variantItemId,
    GuestVariantItem? variantItem,
  }) {
    return GuestVariant(
      productId: productId ?? this.productId,
      variantId: variantId ?? this.variantId,
      variantItemId: variantItemId ?? this.variantItemId,
      variantItem: variantItem ?? this.variantItem,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'product_id': productId,
      'variant_id': variantId,
      'variant_item_id': variantItemId,
      'variant_item': variantItem?.toMap(),
    };
  }

  factory GuestVariant.fromMap(Map<String, dynamic> map) {
    return GuestVariant(
      productId: map['product_id'] != null
          ? int.parse(map['product_id'].toString())
          : 0,
      variantId: map['variant_id'] != null
          ? int.parse(map['variant_id'].toString())
          : 0,
      variantItemId: map['variant_item_id'] != null
          ? int.parse(map['variant_item_id'].toString())
          : 0,
      variantItem: map['variant_item'] != null
          ? GuestVariantItem.fromMap(
          map['variant_item'] as Map<String, dynamic>)
          : null,
    );
  }

  String toJson() => json.encode(toMap());

  factory GuestVariant.fromJson(String source) =>
      GuestVariant.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object?> get props {
    return [
      productId,
      variantId,
      variantItemId,
      variantItem,
    ];
  }
}

class GuestVariantItem extends Equatable {
  final int id;
  final String variantName;
  final String name;
  final double price;

  const GuestVariantItem({
    required this.id,
    required this.variantName,
    required this.name,
    required this.price,
  });

  GuestVariantItem copyWith({
    int? id,
    String? variantName,
    String? name,
    double? price,
  }) {
    return GuestVariantItem(
      id: id ?? this.id,
      variantName: variantName ?? this.variantName,
      name: name ?? this.name,
      price: price ?? this.price,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'product_variant_name': variantName,
      'name': name,
      'price': price,
    };
  }

  factory GuestVariantItem.fromMap(Map<String, dynamic> map) {
    return GuestVariantItem(
      id: map['id'] != null?int.parse(map['id'].toString()): 0,
      variantName: map['product_variant_name'] ?? '',
      name: map['name'] ?? '',
      price: map['price'] != null ? double.parse(map['price'].toString()) : 0.0,
    );
  }

  String toJson() => json.encode(toMap());

  factory GuestVariantItem.fromJson(String source) =>
      GuestVariantItem.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [id, variantName, name, price];
}