import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../../error/exception.dart';
import '../../../modules/home/controller/cubit/product/product_state_model.dart';
import 'remote_data_source_packages.dart';

Map<String, dynamic> myMap = {};

abstract class RemoteDataSource {
  Future<UserLoginResponseModel> signIn(Map<String, dynamic> body);

  Future<UserLoginResponseModel> socialSignInApi(String userInfo);

  Future<UserWithCountryResponse> userProfile(String token);

  Future<String> passwordChange(
      ChangePasswordStateModel changePassData, String token);

  Future<String> profileUpdate(ProfileEditStateModel user, String token);

  Future<UserProfileInfo> getProfileInfo(String token);

  Future<String> updateBuyerPersonalization(
      Map<String, String> dataMap, String token);

  Future<String> skipBuyerPersonalization(String token);

  Future<String> deleteUserAccount(String token);

  Future<EditAddressModel> editAddress(String id, String token);

  Future<String> sendForgotPassCode(Map<String, dynamic> body);

  Future<String> setPassword(SetPasswordModel body);

  Future<String> createDeliveryMan(DeliveryManStateModel body);

  Future<String> sendActiveAccountCode(String email);

  Future<String> activeAccountCodeSubmit(String code);

  Future<String> logOut(String tokne);

  Future<Map<String, dynamic>> refreshAccessToken(String token);

  Future<HomeModel> getHomeData();

  Future<String> updateProfileInformation(
      Map<String, String> dataMap, String token);

  Future<PagedProductsResult> getHighlightProducts(
    String page,
    String keyWord, {
    String? categorySlug,
    String? subCategorySlug,
  });

  Future<List<ProductModel>> loadMoreProducts(
      String keyword, int page, int perPage);

  Future<List<ProductModel>> getBrandProducts(String slug);

  Future<WebsiteSetupModel> websiteSetup();

  Future<String> userRegister(Map<String, dynamic> userInfo);

  Future<String> sendOtp(Map<String, dynamic> body);

  Future<String> verifyOtp(Map<String, dynamic> body);

  Future<dynamic> updateUserForPushNotification(Uri uri);

  Future<AboutInformationModel> getAboutUsData();

  Future<List<SellerDto>> getAllCharts(String token);

  Future<SendMessageResponseDto> sendMessageToSeller(
      Map<String, dynamic> mapBody, String token);

  Future<List<FaqModel>> getFaqList();

  Future<PrivacyPolicyAndTermConditionModel> getPrivacyPolicy();

  Future<PrivacyPolicyAndTermConditionModel> getTermsAndCondition();

  Future<ContactModel> getContactUsContent();

  Future<bool> getContactUsMessageSend(ContactUsMessageModel body);

  Future<String> sendProductInquiry(Map<String, dynamic> body);

  Future<ProductDetailsModel> getProductDetails(String slug);

  Future<SubmitReviewResponseModel> submitReivew(
      Map<String, dynamic> reviewInfo, String token);

  Future<List<CountryStateModel>> statesByCountryId(
      String countryID, String token);

  Future<List<CountryModel>> getCountryList(String token);

  Future<List<CityModel>> citiesByStateId(String countryID, String token);

  Future<List<OrderModel>> orderList(String page, String token);

  Future<OrderModel> showOrderTracking(String trackNumber, String token);

  Future<String> confirmOrderProductDelivery(
      String orderProductId, String token);

  Future<OrderModel> trackingOrderResponse(String trackNumber);

  Future deliveryLocation(String id, String token);

  Future<AddressBook> getShipingAndBillingAddress(String token);

  Future<AddressModel> getSingleAddress(String id, String token);

  Future<String> deleteAddress(String id, String token);

  Future<String> billingUpdate(Map<String, String> dataMap, String token);

  Future<String> updateAddress(
      String id, Map<String, String> dataMap, String token);

  Future<String> addAddress(Map<String, String> dataMap, String token);

  Future<String> shippingUpdate(Map<String, String> dataMap, String token);

  Future<List<WishListModel>> wishList(String token);

  Future<String> removeWishList(String id, String token);

  Future<String> clearWishList(String token);

  Future<String> becomeSellerRequest(String token, BecomeSellerStateModel body);

  Future<String> addWishList(String id, String token);

  Future<SearchResponseModel> searchProduct(Uri uri);

  Future<CartResponseModel> getCartProducts(String token);

  Future<SellerProductModel> getSellerProductLists(String slug);

  // Future<CartResponseModel> applyCoupon(String token, String coupon);

  Future<CheckoutResponseModel> getCheckoutData(
      String token, String couponCode);

  Future<CheckoutResponseModel> guestCheckout(String id);

  Future<String> incrementQuantity(String productId, String token);

  Future<String> removerCartItem(String productId, String token);

  Future<String> decrementQuantity(String productId, String token);

  Future<String> addToCart(AddToCartModel dataModel);

  Future<Map<String, dynamic>> draftCheckout(
      Uri uri, Map<String, dynamic>? body);

  Future<String> cashOnDeliveryPayment(Map<String, dynamic> body, Uri uri);

  Future<String> bankPay(Uri uri, Map<String, dynamic> body);

  Future<Map<String, dynamic>> payWithIyzico(
      Uri uri, Map<String, dynamic> body);

  Future<CouponResponseModel> applyCoupon(String coupon, String token);

  Future<ProductCategoriesModel> getCategoryProducts(String slug, int page);

  Future<ProductCategoriesModel> getSubCategoryProducts(String slug, int page);

  Future<ProductCategoriesModel> getChildCategoryProducts(String slug, int page);

  Future<FlashModel> getFlashSale();

  Future<List<HomePageCategoriesModel>> getCategoryLists();

  Future<List<BrandModel>> getBrandList();

  // Future<List<CategoriesModel>> getCategoryLists();

  Future<List<SubCategoryModel>> getSubCategoryLists(String id);

  Future<List<ChildCategoryModel>> getChildCategoryLists(String id);

  Future<List<ProductModel>> filterProducts(ProductStateModel dataModel);
}

typedef CallClientMethod = Future<http.Response> Function();

class RemoteDataSourceImpl implements RemoteDataSource {
  final http.Client client;

  RemoteDataSourceImpl({required this.client});

  final postHeader = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-Client-Platform': 'mobile',
  };
  final defaultHeader = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-Client-Platform': 'mobile',
  };

  @override
  Future<ProductDetailsModel> getProductDetails(String slug) async {
    final uri = Uri.parse(RemoteUrls.productDetail(slug));
    debugPrint('detail-url $uri');

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return ProductDetailsModel.fromMap(responseJsonBody);
  }

  @override
  Future<String> updateProfileInformation(
      Map<String, String> dataMap, String token) async {
    final uri = Uri.parse(RemoteUrls.updateProfile(token));

    final clientMethod = client.post(uri, headers: postHeader, body: dataMap);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'] as String;
  }

  @override
  Future<CouponResponseModel> applyCoupon(String coupon, String token) async {
    final uri = Uri.parse(RemoteUrls.applyCoupon(coupon, token));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return CouponResponseModel.fromMap(responseJsonBody['coupon']);
  }

  @override
  Future<String> decrementQuantity(String productId, String token) async {
    final uri = Uri.parse(RemoteUrls.decrementQuantity(productId, token));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'];
  }

  @override
  Future<String> addToCart(AddToCartModel dataModel) async {
    final uri = Uri.parse(RemoteUrls.addToCart)
        .replace(queryParameters: dataModel.toMap());
    // log(dataModel.toMap().toString(), name: "RDS");
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'] as String;
  }

  @override
  Future<String> cashOnDeliveryPayment(
      Map<String, dynamic> body, Uri uri) async {
    // final uri = Uri.parse(RemoteUrls.cashOnDelivery(token));
    final clientMethod =
        client.post(uri, headers: defaultHeader, body: jsonEncode(body));

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'] as String;
  }

  @override
  Future<Map<String, dynamic>> draftCheckout(
      Uri uri, Map<String, dynamic>? body) async {
    // final clientMethod = client.post(uri, headers: postHeader, body: json.encode(body ?? {}));
    final clientMethod = client.post(uri,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: jsonEncode(body ?? {}));

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody;
  }

  @override
  Future<String> incrementQuantity(String productId, String token) async {
    final uri = Uri.parse(RemoteUrls.incrementQuantity(productId, token));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'];
  }

  @override
  Future<String> removerCartItem(String productId, String token) async {
    final uri = Uri.parse(RemoteUrls.removeCartItem(productId, token));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'];
  }

  @override
  Future<CartResponseModel> getCartProducts(String token) async {
    final uri = Uri.parse(RemoteUrls.cartProduct(token));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return CartResponseModel.fromMap(responseJsonBody);
  }

  @override
  Future<SellerProductModel> getSellerProductLists(String slug) async {
    final uri = Uri.parse(RemoteUrls.sellerDetailsUrl(slug));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return SellerProductModel.fromMap(responseJsonBody);
  }

  @override
  Future<CheckoutResponseModel> getCheckoutData(
      String token, String couponCode) async {
    final uri = Uri.parse(RemoteUrls.cartCheckout(token, couponCode));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return CheckoutResponseModel.fromMap(responseJsonBody);
  }

  @override
  Future<CheckoutResponseModel> guestCheckout(String id) async {
    final uri = Uri.parse(RemoteUrls.guestCheckout(id));
    debugPrint('guest-checkout-data-url $uri');
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody = await NetworkParser.callClientWithCatchException(() => clientMethod);

    return CheckoutResponseModel.fromMap(responseJsonBody);
  }

  @override
  Future<SearchResponseModel> searchProduct(Uri uri) async {
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return SearchResponseModel.fromMap(responseJsonBody['products']);
  }

  @override
  Future<AddressBook> getShipingAndBillingAddress(String token) async {
    final uri = Uri.parse(RemoteUrls.address(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return AddressBook.fromMap(responseJsonBody);
  }

  @override
  Future<String> addAddress(Map<String, String> dataMap, String token) async {
    final uri = Uri.parse(RemoteUrls.addAddressUrl(token));

    final clientMethod = client.post(uri, headers: postHeader, body: dataMap);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'] as String;
  }

  @override
  Future<String> updateAddress(
      String id, Map<String, String> dataMap, String token) async {
    final uri = Uri.parse(RemoteUrls.updateAddress(id, token));

    final clientMethod = client.put(uri, headers: postHeader, body: dataMap);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'] as String;
  }

  @override
  Future<String> billingUpdate(
    Map<String, String> dataMap,
    String token,
  ) async {
    final uri = Uri.parse(RemoteUrls.billingAddress(token));

    final clientMethod = client.post(uri, headers: postHeader, body: dataMap);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'] as String;
  }

  @override
  Future<String> shippingUpdate(
      Map<String, String> dataMap, String token) async {
    final uri = Uri.parse(RemoteUrls.shippingAddress(token));

    final headers = postHeader;
    final clientMethod = client.post(uri, headers: headers, body: dataMap);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'] as String;
  }

  @override
  Future<List<WishListModel>> wishList(String token) async {
    final uri = Uri.parse(RemoteUrls.wishList(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    final wishlist = responseJsonBody['wishlists'] as List?;
    if (wishlist == null) {
      return [];
    } else {
      return wishlist.map((e) {
        final mapData = e['product'] as Map<String, dynamic>;
        mapData.addAll({"wish_id": e['id']?.toInt() ?? 0});
        return WishListModel.fromMap(mapData);
      }).toList();
    }
  }

  @override
  Future<String> removeWishList(String id, String token) async {
    final uri = Uri.parse(RemoteUrls.removeWish(id, token));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'] as String;
  }

  @override
  Future<String> clearWishList(String token) async {
    final uri = Uri.parse(RemoteUrls.clearWishList(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'] as String;
  }

  @override
  Future<String> addWishList(String id, String token) async {
    final uri = Uri.parse(RemoteUrls.addWish(id, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'] as String;
  }

  @override
  Future<HomeModel> getHomeData() async {
    final uri = Uri.parse(RemoteUrls.homeUrl);

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return HomeModel.fromMap(responseJsonBody);
  }

  @override
  Future<WebsiteSetupModel> websiteSetup() async {
    final uri = Uri.parse(RemoteUrls.websiteSetup);

    final clientMethod = client
        .get(uri, headers: defaultHeader)
        .timeout(const Duration(seconds: 12));
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    Map<String, dynamic> data = responseJsonBody['language'];
    data.forEach((key, value) {
      var newKey = key
          .toString()
          .replaceAll("-", " ")
          .replaceAll(",", "")
          .replaceAll(".", "")
          .replaceAll("'", "")
          .replaceAll("!", "")
          .replaceAll(' ', '_');
      myMap[newKey] = value;
    });
    return WebsiteSetupModel.fromMap(responseJsonBody);
  }

  @override
  Future<Map<String, dynamic>> refreshAccessToken(String token) async {
    final uri = Uri.parse(RemoteUrls.tokenRefresh);
    final clientMethod = client.post(
      uri,
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    if (responseJsonBody is Map<String, dynamic>) {
      return responseJsonBody;
    }
    return Map<String, dynamic>.from(responseJsonBody as Map);
  }

  @override
  Future<UserLoginResponseModel> signIn(Map body) async {
    final uri = Uri.parse(RemoteUrls.userLogin);

    final clientMethod = client.post(uri, headers: postHeader, body: body);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return UserLoginResponseModel.fromMap(responseJsonBody);
  }

  @override
  Future<dynamic> updateUserForPushNotification(Uri uri) async {
    final headers = {'Accept': 'application/json'};
    final clientMethod = client.post(uri, headers: headers);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    debugPrint('updateUserForPushNotification $responseJsonBody');
    return responseJsonBody;
  }

  @override
  Future<UserLoginResponseModel> socialSignInApi(String userInfo) async {
    final headers = {'Accept': 'application/json'};
    final uri = Uri.parse(RemoteUrls.socialSignUrl(userInfo));

    final clientMethod = client.get(uri, headers: headers);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return UserLoginResponseModel.fromMap(responseJsonBody);
  }

  @override
  Future<String> createDeliveryMan(DeliveryManStateModel body) async {
    final uri = Uri.parse(RemoteUrls.createDeliveryMan);

    final request = http.MultipartRequest('POST', uri);
    request.fields.addAll(body.toMap());

    request.headers.addAll(postHeader);
    if (body.manImage.isNotEmpty) {
      final file =
          await http.MultipartFile.fromPath('man_image', body.manImage);
      request.files.add(file);
    }
    if (body.idnImage.isNotEmpty) {
      final file =
          await http.MultipartFile.fromPath('idn_image', body.idnImage);
      request.files.add(file);
    }

    http.StreamedResponse response = await request.send();
    final clientMethod = http.Response.fromStream(response);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['message']['messege'] as String;
  }

  @override
  Future<String> logOut(String token) async {
    final uri = Uri.parse(RemoteUrls.userLogOut(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final notification = responseJsonBody['notification'];
    if (notification is String && notification.isNotEmpty) {
      return notification;
    }
    return notification?.toString() ?? 'Çıkış yapıldı';
  }

  @override
  Future<UserWithCountryResponse> userProfile(String token) async {
    final uri = Uri.parse(RemoteUrls.userProfile(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return UserWithCountryResponse.fromMap(responseJsonBody);
  }

  @override
  Future<String> passwordChange(
    ChangePasswordStateModel changePassData,
    String token,
  ) async {
    final uri = Uri.parse(RemoteUrls.changePassword(token));

    final clientMethod =
        client.post(uri, headers: postHeader, body: changePassData.toMap());
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'] as String;
  }

  @override
  Future<String> profileUpdate(ProfileEditStateModel user, String token) async {
    final uri = Uri.parse(RemoteUrls.updateProfile(token));

    final request = http.MultipartRequest('POST', uri);
    request.fields.addAll(user.toMap());

    request.headers.addAll(postHeader);
    if (user.image.isNotEmpty) {
      final file = await http.MultipartFile.fromPath('image', user.image);
      request.files.add(file);
    }

    http.StreamedResponse response = await request.send();
    final clientMethod = http.Response.fromStream(response);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'] as String;
  }

  @override
  Future<List<CountryStateModel>> statesByCountryId(
      String countryID, String token) async {
    final uri = Uri.parse(RemoteUrls.stateByCountryId(countryID, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['states'] as List;

    return List<CountryStateModel>.from(
        mapList.map((e) => CountryStateModel.fromMap(e)));
  }

  @override
  Future<List<CityModel>> citiesByStateId(String stateID, String token) async {
    final uri = Uri.parse(RemoteUrls.citiesByStateId(stateID, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['cities'] as List;

    return List<CityModel>.from(mapList.map((e) => CityModel.fromMap(e)));
  }

  @override
  Future<List<OrderModel>> orderList(String page, String token) async {
    final uri = Uri.parse(RemoteUrls.orderList(page, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    final mapList = responseJsonBody['orders']['data'] as List;

    return List<OrderModel>.from(mapList.map((e) => OrderModel.fromMap(e)));
  }

  @override
  Future<OrderModel> showOrderTracking(String trackNumber, String token) async {
    final uri = Uri.parse(RemoteUrls.showOrderTracking(trackNumber, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return OrderModel.fromMap(responseJsonBody['order']);
  }

  @override
  Future<String> confirmOrderProductDelivery(
      String orderProductId, String token) async {
    final uri =
        Uri.parse(RemoteUrls.confirmOrderProductDelivery(orderProductId, token));

    final clientMethod = client.post(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message']?.toString() ??
        'Ürün teslim onayınız alındı.';
  }

  @override
  Future<OrderModel> trackingOrderResponse(String trackNumber) async {
    final uri = Uri.parse(RemoteUrls.trackingOrderResponse(trackNumber));
    //debugPrint('order-tracking-uri $uri');
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    //print('response-type ${responseJsonBody['status']}');
    if (responseJsonBody['message'] == null) {
      // print('message-null');
      return OrderModel.fromMap(responseJsonBody['order']);
    } else {
      return OrderModel.fromMap(responseJsonBody);
    }
  }

  @override
  Future deliveryLocation(String id, String token) async {
    final uri = Uri.parse(RemoteUrls.deliveryLocation(id, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody;
  }

  @override
  Future<String> userRegister(Map<String, dynamic> userInfo) async {
    final uri = Uri.parse(RemoteUrls.userRegister);

    final clientMethod = client.post(uri, headers: postHeader, body: userInfo);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'];
  }

  @override
  Future<String> sendOtp(Map<String, dynamic> body) async {
    final uri = Uri.parse(RemoteUrls.sendOtp);
    final clientMethod = client.post(uri, headers: postHeader, body: body);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return (responseJsonBody['message'] ?? 'Doğrulama kodu gönderildi.')
        .toString();
  }

  @override
  Future<String> verifyOtp(Map<String, dynamic> body) async {
    final uri = Uri.parse(RemoteUrls.verifyOtp);
    final clientMethod = client.post(uri, headers: postHeader, body: body);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final token = responseJsonBody['token']?.toString() ?? '';
    if (token.isEmpty) {
      throw const ServerException('Doğrulama token alınamadı.', 422);
    }
    return token;
  }

  @override
  Future<String> sendForgotPassCode(Map<String, dynamic> body) async {
    final uri = Uri.parse(RemoteUrls.sendForgetPassword);

    final clientMethod = client.post(uri, headers: postHeader, body: body);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'];
  }

  @override
  Future<String> setPassword(SetPasswordModel body) async {
    final uri = Uri.parse(RemoteUrls.storeResetPassword(body.code));

    final clientMethod =
        client.post(uri, headers: postHeader, body: body.toMap());
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'];
  }

  @override
  Future<String> sendActiveAccountCode(String email) async {
    final uri = Uri.parse(RemoteUrls.resendRegisterCode);

    final clientMethod =
        client.post(uri, headers: postHeader, body: {'email': email});
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'];
  }

  @override
  Future<String> activeAccountCodeSubmit(String code) async {
    final uri = Uri.parse(RemoteUrls.userVerification(code));

    final clientMethod = client.get(uri, headers: postHeader);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'];
  }

  @override
  Future<AboutInformationModel> getAboutUsData() async {
    final uri = Uri.parse(RemoteUrls.aboutUs);

    final clientMethod = client.get(uri, headers: postHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return AboutInformationModel.fromMap(responseJsonBody);
  }

  @override
  Future<bool> getContactUsMessageSend(ContactUsMessageModel body) async {
    final uri = Uri.parse(RemoteUrls.sendContactMessage);

    final clientMethod =
        client.post(uri, body: body.toMap(), headers: postHeader);

    await NetworkParser.callClientWithCatchException(() => clientMethod);

    return true;
  }

  @override
  Future<String> sendProductInquiry(Map<String, dynamic> body) async {
    final uri = Uri.parse(RemoteUrls.productInquiry);
    final clientMethod =
        client.post(uri, body: body, headers: postHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification']?.toString() ??
        'Bilgi talebiniz alındı.';
  }

  @override
  Future<ContactModel> getContactUsContent() async {
    final uri = Uri.parse(RemoteUrls.contactUs);

    final clientMethod = client.get(uri, headers: defaultHeader);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return ContactModel.fromMap(responseJsonBody);

  }

  @override
  Future<PrivacyPolicyAndTermConditionModel> getPrivacyPolicy() async {
    final uri = Uri.parse(RemoteUrls.privacyPolicy);

    final clientMethod = client.get(uri, headers: defaultHeader);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return PrivacyPolicyAndTermConditionModel.fromMap(
        responseJsonBody['privacyPolicy']);
  }

  @override
  Future<PrivacyPolicyAndTermConditionModel> getTermsAndCondition() async {
    final uri = Uri.parse(RemoteUrls.termsAndConditions);

    final clientMethod = client.get(uri, headers: defaultHeader);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return PrivacyPolicyAndTermConditionModel.fromMap(
        responseJsonBody['terms_conditions']);
  }

  @override
  Future<List<FaqModel>> getFaqList() async {
    final uri = Uri.parse(RemoteUrls.faq);

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    final faqData = responseJsonBody['faqs'] as List?;
    return faqData != null
        ? faqData.map((e) => FaqModel.fromMap(e)).toList()
        : [];
  }

  @override
  Future<SubmitReviewResponseModel> submitReivew(
      Map<String, dynamic> reviewInfo, String token) async {
    final uri = Uri.parse(RemoteUrls.submitReviewUrl(token));

    final clientMethod =
        client.post(uri, headers: postHeader, body: reviewInfo);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return SubmitReviewResponseModel.fromMap(responseJsonBody);
  }

  @override
  Future<ProductCategoriesModel> getCategoryProducts(
      String slug, int page) async {
    final uri = Uri.parse(RemoteUrls.categoryProducts(slug, page));
    debugPrint('category-url $uri');
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return ProductCategoriesModel.fromMap(responseJsonBody);
  }

  @override
  Future<List<HomePageCategoriesModel>> getCategoryLists() async {
    final uri = Uri.parse(RemoteUrls.categoryLists);

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    final mapList = responseJsonBody['categories'];

    return List<HomePageCategoriesModel>.from(
        mapList.map((e) => HomePageCategoriesModel.fromMap(e)));
  }

  @override
  Future<List<BrandModel>> getBrandList() async {
    final uri = Uri.parse(RemoteUrls.brandList);

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    final mapList = responseJsonBody['brands'] as List;

    return List<BrandModel>.from(
      mapList.map((e) => BrandModel.fromMap(e as Map<String, dynamic>)),
    );
  }

  @override
  Future<AddressModel> getSingleAddress(String id, String token) async {
    final uri = Uri.parse(RemoteUrls.singleAddress(id, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return AddressModel.fromMap(responseJsonBody);
  }

  @override
  Future<String> deleteAddress(String id, String token) async {
    final uri = Uri.parse(RemoteUrls.singleAddress(id, token));

    final clientMethod = client.delete(uri, headers: postHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['notification'];
  }

  @override
  Future<List<CountryModel>> getCountryList(String token) async {
    final headers = defaultHeader;
    final uri = Uri.parse(RemoteUrls.countryListUrl(token));

    final clientMethod = client.get(uri, headers: headers);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['countries'] as List;

    return List<CountryModel>.from(mapList.map((e) => CountryModel.fromMap(e)));
  }

  @override
  Future<List<SubCategoryModel>> getSubCategoryLists(String id) async {
    final uri = Uri.parse(RemoteUrls.subCategoryLists(id));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['subCategories'] as List;

    return List<SubCategoryModel>.from(
        mapList.map((e) => SubCategoryModel.fromMap(e)));
  }

  @override
  Future<List<ChildCategoryModel>> getChildCategoryLists(String id) async {
    final uri = Uri.parse(RemoteUrls.childCategoryLists(id));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['childCategories'] as List;

    return List<ChildCategoryModel>.from(
        mapList.map((e) => ChildCategoryModel.fromMap(e)));
  }

  @override
  Future<String> bankPay(Uri uri, Map<String, dynamic> body) async {
    // final uri = Uri.parse(RemoteUrls.payWithBankUrl(token));//jsonEncode(body)
    final clientMethod = client.post(
      uri,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: jsonEncode(body),
    );
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return responseJsonBody['message'] as String;
  }

  @override
  Future<Map<String, dynamic>> payWithIyzico(
      Uri uri, Map<String, dynamic> body) async {
    final clientMethod = client.post(
      uri,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: jsonEncode(body),
    );
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    if (responseJsonBody['success'] == true &&
        responseJsonBody['data'] is Map<String, dynamic>) {
      return Map<String, dynamic>.from(
          responseJsonBody['data'] as Map<String, dynamic>);
    }

    final message = responseJsonBody['message']?.toString() ??
        responseJsonBody['error']?.toString() ??
        'Iyzico ödeme oturumu oluşturulamadı.';
    throw ServerException(message, 422);
  }

  @override
  Future<PagedProductsResult> getHighlightProducts(
    String page,
    String keyWord, {
    String? categorySlug,
    String? subCategorySlug,
  }) async {
    final uri = Uri.parse(
      RemoteUrls.searchHighlightProducts(
        highlight: keyWord,
        page: page,
        categorySlug: categorySlug,
        subCategorySlug: subCategorySlug,
      ),
    );

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final productsPayload = responseJsonBody['products'];
    final productsMap = productsPayload is Map
        ? Map<String, dynamic>.from(productsPayload)
        : <String, dynamic>{};
    final mapList = productsMap['data'] is List
        ? productsMap['data'] as List
        : (productsPayload is List ? productsPayload : const []);

    final total = int.tryParse('${productsMap['total'] ?? ''}') ?? mapList.length;

    return PagedProductsResult(
      products: List<ProductModel>.from(
        mapList.map((e) => ProductModel.fromMap(e as Map<String, dynamic>)),
      ),
      total: total,
    );
  }

  @override
  Future<List<ProductModel>> loadMoreProducts(
      String keyword, int page, int perPage) async {
    final uri = Uri.parse(RemoteUrls.loadMoreProducts(keyword, page, perPage));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['products']['data'] as List;

    return List<ProductModel>.from(mapList.map((e) => ProductModel.fromMap(e)));
  }

  @override
  Future<ProductCategoriesModel> getSubCategoryProducts(
      String slug, int page) async {
    final uri = Uri.parse(RemoteUrls.subCategoryProducts(slug, page));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return ProductCategoriesModel.fromMap(responseJsonBody);
  }

  @override
  Future<ProductCategoriesModel> getChildCategoryProducts(
      String slug, int page) async {
    final uri = Uri.parse(RemoteUrls.childCategoryProducts(slug, page));
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return ProductCategoriesModel.fromMap(responseJsonBody);
  }

  @override
  Future<List<ProductModel>> filterProducts(ProductStateModel dataModel) async {
    final uri = Uri.parse(RemoteUrls.filterUrl)
        .replace(queryParameters: dataModel.toFilterMap());
    debugPrint('filter-url: $uri');
    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['products']['data'] as List;

    return List<ProductModel>.from(mapList.map((e) => ProductModel.fromMap(e)));
  }

  @override
  Future<FlashModel> getFlashSale() async {
    final uri = Uri.parse(RemoteUrls.flashSaleUrl);

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return FlashModel.fromMap(responseJsonBody);
  }

  @override
  Future<List<ProductModel>> getBrandProducts(String slug) async {
    final uri = Uri.parse(RemoteUrls.brandProducts(slug));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['products']['data'] as List;

    return List<ProductModel>.from(mapList.map((e) => ProductModel.fromMap(e)));
  }

  @override
  Future<EditAddressModel> editAddress(String id, String token) async {
    final uri = Uri.parse(RemoteUrls.editAddress(id, token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return EditAddressModel.fromMap(responseJsonBody);
  }

  @override
  Future<UserProfileInfo> getProfileInfo(String token) async {
    final uri = Uri.parse(RemoteUrls.userProfile(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return UserProfileInfo.fromMap(responseJsonBody);
  }

  @override
  Future<String> updateBuyerPersonalization(
    Map<String, String> dataMap,
    String token,
  ) async {
    final uri = Uri.parse(RemoteUrls.updateBuyerPersonalization(token));
    final clientMethod = client.post(uri, headers: postHeader, body: dataMap);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification']?.toString() ?? 'Kaydedildi';
  }

  @override
  Future<String> skipBuyerPersonalization(String token) async {
    final uri = Uri.parse(RemoteUrls.skipBuyerPersonalization(token));
    final clientMethod = client.post(uri, headers: postHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification']?.toString() ?? 'Tamam';
  }

  @override
  Future<List<SellerDto>> getAllCharts(String token) async {
    final uri = Uri.parse(RemoteUrls.allChartUrl(token));

    final clientMethod = client.get(uri, headers: defaultHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    final mapList = responseJsonBody['seller_list'] as List;

    return List<SellerDto>.from(mapList.map((e) => SellerDto.fromMap(e)));
  }

  @override
  Future<SendMessageResponseDto> sendMessageToSeller(
      Map<String, dynamic> mapBody, String token) async {
    final uri = Uri.parse(RemoteUrls.sendMsgToSeller(token));

    final clientMethod = client.post(uri, body: mapBody, headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    });
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);

    return SendMessageResponseDto.fromMap(responseJsonBody);
  }

  @override
  Future<String> deleteUserAccount(String token) async {
    final uri = Uri.parse(RemoteUrls.deleteUserAccount(token));
    final clientMethod = client.delete(uri, headers: postHeader);
    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['message'];
  }

  @override
  Future<String> becomeSellerRequest(
      String token, BecomeSellerStateModel body) async {
    final uri = Uri.parse(RemoteUrls.becomeSellerRequest(token));

    final request = http.MultipartRequest('POST', uri);
    request.fields.addAll(body.toMap());

    request.headers.addAll(postHeader);
    if (body.bannerImage.isNotEmpty) {
      final file =
          await http.MultipartFile.fromPath('banner_image', body.bannerImage);
      request.files.add(file);
    }
    if (body.logo.isNotEmpty) {
      final file = await http.MultipartFile.fromPath('logo', body.logo);
      request.files.add(file);
    }

    http.StreamedResponse response = await request.send();
    final clientMethod = http.Response.fromStream(response);

    final responseJsonBody =
        await NetworkParser.callClientWithCatchException(() => clientMethod);
    return responseJsonBody['notification'] as String;
  }
}
