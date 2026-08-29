import 'dart:convert';
import 'dart:developer';

import 'package:equatable/equatable.dart';
import '../../place_order/model/payment_status/bank_status.dart';
import '../../place_order/model/payment_status/bkash_status.dart';
import '../../place_order/model/payment_status/flutter_wave_status.dart';
import '../../place_order/model/payment_status/instamojo_status.dart';
import '../../place_order/model/payment_status/my_fatoorah_status.dart';
import '../../place_order/model/payment_status/paypal_status.dart';
import '../../place_order/model/payment_status/paystact_mollie_status.dart';
import '../../place_order/model/payment_status/razorpay_status.dart';
import '../../place_order/model/payment_status/sslcommerz_status.dart';
import '../../place_order/model/payment_status/iyzico_status.dart';
import '../../place_order/model/payment_status/stripe_status.dart';
import '/modules/profile/model/address_model.dart';
import 'cart_product_model.dart';
import 'coupon_response_model.dart';
import 'shipping_response_model.dart';
import 'vendor_location_model.dart';

class CheckoutResponseModel extends Equatable {
  final List<CartProductModel>? cartProducts;
  final List<AddressModel>? addresses;
  final List<ShippingResponseModel>? shippings;
  final CouponResponseModel? couponOffer;
  final PayStackAndMollieStatus? payStackMolliStatus;
  final BankStatus? bankStatus;
  final FlutterWaveStatus? flutterwave;
  final StripeStatus? stripe;
  final SslCommerzStatus? sslCommerzStatus;
  final RazorPayStatus? razorPayStatus;
  final PaypalStatus? paypalStatus;
  final InstamojoStatus? instamojoStatus;
  final MyFatoorahStatus? myFatoorahStatus;
  final BKashStatus? bKash;
  final IyzicoStatus? iyzico;
  final VendorLocation? vendorLocation;

  const CheckoutResponseModel({
    required this.cartProducts,
    required this.addresses,
    required this.shippings,
    required this.couponOffer,
    required this.payStackMolliStatus,
    required this.bankStatus,
    required this.flutterwave,
    required this.stripe,
    required this.sslCommerzStatus,
    required this.razorPayStatus,
    required this.paypalStatus,
    required this.instamojoStatus,
    required this.myFatoorahStatus,
    required this.bKash,
    this.iyzico,
    required this.vendorLocation,
  });

  CheckoutResponseModel copyWith({
    List<CartProductModel>? cartProducts,
    List<AddressModel>? addresses,
    List<ShippingResponseModel>? shippings,
    CouponResponseModel? couponOffer,
    PayStackAndMollieStatus? payStackMolliStatus,
    StripeStatus? stripe,
    BankStatus? bankStatus,
    FlutterWaveStatus? flutterwave,
    SslCommerzStatus? sslCommerzStatus,
    PaypalStatus? paypalStatus,
    RazorPayStatus? razorPayStatus,
    InstamojoStatus? instamojoStatus,
    MyFatoorahStatus? myFatoorahStatus,
    BKashStatus? bKash,
    IyzicoStatus? iyzico,
    VendorLocation? vendorLocation,
  }) {
    return CheckoutResponseModel(
      cartProducts: cartProducts ?? this.cartProducts,
      addresses: addresses ?? this.addresses,
      shippings: shippings ?? this.shippings,
      couponOffer: couponOffer ?? this.couponOffer,
      payStackMolliStatus: payStackMolliStatus ?? this.payStackMolliStatus,
      bankStatus: bankStatus ?? this.bankStatus,
      flutterwave: flutterwave ?? this.flutterwave,
      sslCommerzStatus: sslCommerzStatus ?? this.sslCommerzStatus,
      paypalStatus: paypalStatus ?? this.paypalStatus,
      razorPayStatus: razorPayStatus ?? this.razorPayStatus,
      instamojoStatus: instamojoStatus ?? this.instamojoStatus,
      stripe: stripe ?? this.stripe,
      myFatoorahStatus: myFatoorahStatus ?? this.myFatoorahStatus,
      bKash: bKash ?? this.bKash,
      iyzico: iyzico ?? this.iyzico,
      vendorLocation: vendorLocation ?? this.vendorLocation,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'cartProducts': cartProducts?.map((x) => x.toMap()).toList(),
      'addresses': addresses?.map((x) => x.toMap()).toList(),
      'shippings': shippings?.map((x) => x.toMap()).toList(),
      'couponOffer': couponOffer?.toMap(),
      'paystackAndMollie': payStackMolliStatus?.toMap(),
      'bankPaymentInfo': bankStatus?.toMap(),
      'flutterwavePaymentInfo': flutterwave!.toMap(),
      'stripePaymentInfo': stripe?.toMap(),
      'sslcommerz': sslCommerzStatus?.toMap(),
      'razorpayPaymentInfo': razorPayStatus?.toMap(),
      'paypalPaymentInfo': paypalStatus?.toMap(),
      'instamojo': instamojoStatus?.toMap(),
      'myfatoorah': myFatoorahStatus?.toMap(),
      'iyzico': iyzico?.toMap(),
      'vendor_lat_long': vendorLocation?.toMap(),
    };
  }

  factory CheckoutResponseModel.fromMap(Map<String, dynamic> map) {
    return CheckoutResponseModel(
      cartProducts:map['cartProducts']!= null? List<CartProductModel>.from(
        (map['cartProducts'] as List<dynamic>).map<CartProductModel>(
          (x) => CartProductModel.fromMap(x as Map<String, dynamic>),
        ),
      ):[],
      addresses: map['addresses']!= null? List<AddressModel>.from(
        (map['addresses'] as List<dynamic>).map<AddressModel>(
          (x) => AddressModel.fromMap(x as Map<String, dynamic>),
        ),
      ):[],
      shippings:map['shippings']!= null?  List<ShippingResponseModel>.from(
        (map['shippings'] as List<dynamic>).map<ShippingResponseModel>(
          (x) => ShippingResponseModel.fromMap(x as Map<String, dynamic>),
        ),
      ):[],
      couponOffer: map['couponOffer'] != ""
          ? CouponResponseModel.fromMap(
              map['couponOffer'] as Map<String, dynamic>)
          : null,
      payStackMolliStatus: map['paystackAndMollie'] != null
          ? PayStackAndMollieStatus.fromMap(
              map['paystackAndMollie'] as Map<String, dynamic>)
          : null,
      bankStatus: map['bankPaymentInfo'] != null
          ? BankStatus.fromMap(map['bankPaymentInfo'] as Map<String, dynamic>)
          : null,
      razorPayStatus: map['razorpayPaymentInfo'] != null
          ? RazorPayStatus.fromMap(
              map['razorpayPaymentInfo'] as Map<String, dynamic>)
          : null,
      paypalStatus: map['paypalPaymentInfo'] != null
          ? PaypalStatus.fromMap(
              map['paypalPaymentInfo'] as Map<String, dynamic>)
          : null,
      sslCommerzStatus: map['sslcommerz'] != null
          ? SslCommerzStatus.fromMap(map['sslcommerz'] as Map<String, dynamic>)
          : null,
      instamojoStatus: map['instamojo'] != null
          ? InstamojoStatus.fromMap(map['instamojo'] as Map<String, dynamic>)
          : null,
      stripe: map['stripePaymentInfo'] != null
          ? StripeStatus.fromMap(
              map['stripePaymentInfo'] as Map<String, dynamic>)
          : null,
      flutterwave: map['flutterwavePaymentInfo'] != null
          ? FlutterWaveStatus.fromMap(
              map['flutterwavePaymentInfo'] as Map<String, dynamic>)
          : null,
      myFatoorahStatus: map['myfatoorah'] != null
          ? MyFatoorahStatus.fromMap(map['myfatoorah'] as Map<String, dynamic>)
          : null,
      bKash: map['bkash'] != null
          ? BKashStatus.fromMap(map['bkash'] as Map<String, dynamic>)
          : null,
      iyzico: map['iyzico'] != null
          ? IyzicoStatus.fromMap(map['iyzico'] as Map<String, dynamic>)
          : null,
      vendorLocation: map['vendor_lat_long'] != null
          ? VendorLocation.fromMap(map['vendor_lat_long'] as Map<String, dynamic>)
          : null,
    );
  }

  String toJson() => json.encode(toMap());

  factory CheckoutResponseModel.fromJson(String source) =>
      CheckoutResponseModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object?> get props => [
    cartProducts,
    addresses,
    shippings,
    couponOffer,
    payStackMolliStatus,
    bankStatus,
    flutterwave,
    stripe,
    sslCommerzStatus,
    razorPayStatus,
    paypalStatus,
    instamojoStatus,
    myFatoorahStatus,
    bKash,
    iyzico,
    vendorLocation,
  ];
}
