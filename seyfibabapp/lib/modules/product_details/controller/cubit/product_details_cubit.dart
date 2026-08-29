import 'package:collection/collection.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../utils/utils.dart';
import '../../../authentication/controller/login/login_bloc.dart';
import '../../../cart/controllers/cart_repository.dart';
import '../../../cart/model/coupon_response_model.dart';
import '../../../cart/model/guest_cart_product.dart';
import '../../model/active_variant_items_model.dart';
import '../../model/active_variant_model.dart';
import '../../model/product_details_model.dart';
import '../../model/product_details_product_model.dart';
import '../repository/product_details_repository.dart';
import 'details_state_model.dart';

part 'product_details_state.dart';

class ProductDetailsCubit extends Cubit<DetailsStateModel> {
  ProductDetailsCubit(
      {
        required ProductDetailsRepository repository,
        required LoginBloc loginBloc,
        required CartRepository cartRepository,
      }) : _repository = repository,_loginBloc = loginBloc,_cartRepository = cartRepository, super(DetailsStateModel.init());

  final ProductDetailsRepository _repository;
  final CartRepository _cartRepository;
  final LoginBloc _loginBloc;

  ProductDetailsModel? details;
  CouponResponseModel? couponResponse;
  List<GustCartProduct> savedProduct = [];

  Future<void> getProductDetails(String slug) async {
    final loaded = ProductDetailsStateLoading();
    emit(state.copyWith(detailsState: loaded));

    final result = await _repository.getProductDetails(slug);
    result.fold(
          (failure) {
        final loaded = ProductDetailsStateError(failure.message, failure.statusCode);
        emit(state.copyWith(detailsState: loaded));
      },(data) {
      details = data;
        final loaded = ProductDetailsStateLoaded(data);
        emit(state.copyWith(detailsState: loaded,productId: data.product.id));
      },
    );
    addBundleProduct();
    calculatePrices(details?.product);
  }


  void addCartPrice(double price){
    emit(state.copyWith(cartPrice: price));
    // debugPrint('state-cart-price ${state.cartPrice}');
  }

  bool isExist(){
    if(savedProduct.isNotEmpty){
      for(var p in savedProduct){
        if(p.productId == state.productId){
          debugPrint('found-id ${p.productId}');
          return true;
        }
      }
    }
    return false;
  }

  void addBundleProduct() {

    if (state.variants.isNotEmpty) {
      emit(state.copyWith(variants: <ActiveVariantModel>[]));
    }
    if (state.variantItem.isNotEmpty) {
      emit(state.copyWith(variantItem: <ActiveVariantItemModel>[]));
    }
    if (state.itemPrice.isNotEmpty) {
      emit(state.copyWith(itemPrice: <double>[]));
    }
    if (state.totalPrice > 0.0) {
      emit(state.copyWith(totalPrice: 0.0));
    }


    if(details?.product.activeVariantModel.isNotEmpty??false){
      // debugPrint('variant-exist ${details?.product.activeVariantModel.length}');
      final variants = details?.product.activeVariantModel??[];
      final updatedItem = List.of(state.variants)..addAll(variants);
      emit(state.copyWith(variants: updatedItem));
      // debugPrint('variant-added-state ${state.variants.length}');
    }

    for (var av in state.variants) {
      final updatedItem = List.of(state.variantItem)..add(av.activeVariantsItems.first);
      emit(state.copyWith(variantItem: updatedItem));
    }

    for (var av in state.variants) {
      // final vp = state.variants.map((p) => p.activeVariantsItems.first.price).toList();
      // debugPrint('all-variant-price $vp');
      final updatedItem = List.of(state.itemPrice)..add(av.activeVariantsItems.first.price);
      emit(state.copyWith(itemPrice: updatedItem));
      final prices = state.itemPrice.map((p) => p).toList();
      // debugPrint('all-variant-price $prices');
    }
  }

  void addQty(String type) {
    if (type == 'add') {
      emit(state.copyWith(qty: state.qty + 1));
      // debugPrint('added-one ${state.qty}');
    }else if(type.isEmpty){
      emit(state.copyWith(qty: 1));
    }  else {
      if (state.qty > 1) {
        emit(state.copyWith(qty: state.qty - 1));
        // debugPrint('minus-one ${state.qty}');
      }
    }
  }

  void addIndex(String index) {
    emit(state.copyWith(selectedIndex: index));
  }

  void updateVPItems(ActiveVariantItemModel item) {
    final updatedItem = List.of(state.variantItem);

    final updatedPrice = List.of(state.itemPrice);

    final index = int.parse(state.selectedIndex);

    updatedItem[index] = item;

    updatedPrice[index] = item.price;

    emit(state.copyWith(variantItem: updatedItem, itemPrice: updatedPrice));
    // calculatePrices(details!.detail);
    final ids = state.variantItem.map((e) => e.id).toList();
    final prices = state.itemPrice.map((e) => e).toList();
    // debugPrint('updated-item $ids');
    // debugPrint('updated-price $prices');

    calculatePrices(details?.product);
  }

  Future<void> addGuestProduct() async {
    GuestProduct? product;
    List<GuestVariant> variants = [];

    if (details != null) {
      final p = details?.product;
      product = GuestProduct(
        id: p?.id ?? 0,
        vendorId: p?.vendorId ?? -1,
        name: p?.name ?? '',
        shortName: p?.shortName ?? '',
        slug: p?.slug ?? '',
        weight: p?.weight ?? 0.0,
        thumbImage: p?.thumbImage ?? '',
        price: p?.price ?? 0.0,
        offerPrice: p?.offerPrice ?? 0.0,
      );
    }

    if (state.variants.isNotEmpty && state.variantItem.isNotEmpty) {
      variants = state.variantItem.map((variantItem) {
        // debugPrint('product-from-variant ${state.productId}');
        return GuestVariant(
          variantId: variantItem.productVariantId,
          variantItemId: variantItem.id,
          productId: details?.product.id ?? state.productId,
          variantItem: GuestVariantItem(
            id: variantItem.id,
            variantName: state.variants
                .firstWhere(
                    (variant) => variant.id == variantItem.productVariantId,
                orElse: () => const ActiveVariantModel(id: 0, name: '', activeVariantsItems: []))
                .name,
            name: variantItem.name,
            price: variantItem.price,
          ),
        );
      }).toList();
    }

    final guestProduct = GustCartProduct(
      productId: state.productId,
      qty: state.qty,
      product: product,
      variants: variants,
    );

    final isQtyValid = (details?.product.qty ?? 0) > 0;
    final isProductVendor = details?.product.vendorId ?? -1;
    final isCartVendor = state.vendorIds.isNotEmpty ? state.vendorIds.first : -1;
    final isCartVendorNonNegative = !isCartVendor.isNegative;
    final ids = state.vendorIds.toList();
    debugPrint('idsssss $ids');
    debugPrint('isProductVendor $isProductVendor');

    final isAllowedToAdd = isQtyValid && (state.vendorIds.isEmpty || (isCartVendor == isProductVendor && isCartVendorNonNegative));

   if(isQtyValid){
     if (isAllowedToAdd) {
       final result = await _repository.addGustCartProduct(guestProduct);
       result.fold((failure) {
         final error = GuestAddProductError(failure.message, failure.statusCode);
         emit(state.copyWith(detailsState: error));
       }, (success) {
         const loaded = GuestSaveProduct('Successfully added');
         emit(state.copyWith(detailsState: loaded, count: state.count + 1));
       });
     } else {
       const error = GuestAddProductError("Multiple Seller Product isn't allowed", 402);
       emit(state.copyWith(detailsState: error));
     }
   }else{
     const error = GuestAddProductError("Out of Stock", 402);
     emit(state.copyWith(detailsState: error));
   }
  }


  Future<void> deleteGuestProduct(BuildContext context,int id)async{

    final result = await _repository.deleteGuestProduct(id);
    result.fold((failure){
      final error = GuestProductError(failure.message, failure.statusCode);
      emit(state.copyWith(detailsState: error));
      return false;
    },(success){
      savedProduct.removeWhere((e)=> e.product?.id == id);
      const loaded = GuestSaveProductDeleted('Successfully Deleted');
      emit(state.copyWith(detailsState: loaded));
      emit(state.copyWith(detailsState: GuestAllSavedProduct(savedProduct),count: state.count - 1));
      // debugPrint('successfully deleted');
    });
  }

  Future<void> clearGuestProduct()async{
    // debugPrint('successfully clear');
     final result = await _repository.clearGuestProduct();
    result.fold((failure){
      final error = GuestProductError(failure.message, failure.statusCode);
      emit(state.copyWith(detailsState: error));
      return false;
    },(success){
      savedProduct.clear();
      debugPrint('before-vendors-id ${state.vendorIds}');
      emit(state.copyWith(vendorIds: <int>[]));
      debugPrint('after-vendors-id ${state.vendorIds}');
      //if(couponResponse != null){
        clearCoupon();
      //}
      const loaded = GuestSaveProductClear('Successfully Cleared');
      emit(state.copyWith(detailsState: loaded));
      emit(state.copyWith(detailsState: const GuestAllSavedProduct([]),count: 0));
    });
  }

  void getGuestSavedProduct(){
    // final loading = GuestProductLoading();
    // emit(state.copyWith(detailsState: loading));
    final result =  _repository.getSavedProduct();
    result.fold((failure){
      // debugPrint('getGuestSavedProduct-error called-after-delete');
      final error = GuestProductError(failure.message, failure.statusCode);
      emit(state.copyWith(detailsState: error));
    }, (success){
      // debugPrint('getGuestSavedProduct called-after-delete');
      savedProduct = success;

      emit(state.copyWith(vendorIds: []));

      if(success.isNotEmpty){
        for (final p in success) {
          final ids = List.of(state.vendorIds)..add(p.product?.vendorId??-1);
          emit(state.copyWith(vendorIds: ids));
        }
      }


      // final vendorIds = success.map((e)=>e.product?.vendorId).toList();
      // debugPrint('guest-saved $vendorIds');
      // final stateVendors = state.vendorIds.map((e)=>e).toList();
      // debugPrint('guest-stateVendors $stateVendors');

      double weight = 0.0;
      int totalQty = 0;

      for (int i = 0; i< success.length; i++) {
        totalQty += success[i].qty;
        weight += success[i].product?.weight??0.0;
      }

      final loaded = GuestAllSavedProduct(success);

      emit(state.copyWith(detailsState: loaded,count: savedProduct.length,totalWight: weight,totalQty: totalQty));
      // debugPrint('state-weight-qty ${state.totalWight} | ${state.totalQty}');
    });
  }

  Future<void> updateQty(int productId, bool increase) async {

    final products = savedProduct;

    final product = products.firstWhereOrNull((p) => p.productId == productId);

    if (product != null) {

      final newQty = increase ? product.qty + 1 : (product.qty > 1 ? product.qty - 1 : 1);

      final result = await _repository.updateGustCartProductQty(productId, newQty);
      result.fold((failure){
        final error = GuestProductError(failure.message, failure.statusCode);
        // debugPrint('qry-update-error ${failure.message}');
        emit(state.copyWith(detailsState: error));
      },(success){
        // cartCalculation(context, increase);
        const loaded = GuestUpdateProduct('Successfully Updated');
        emit(state.copyWith(detailsState: loaded));
        // debugPrint('successfully added to card');
      });
    } else {
      // debugPrint('Product with ID $productId not found in savedProduct.');
    }
  }

  void calculatePrices(ProductDetailsProductModel? product) {
      double vPrice = 0.0;
      double productPrice = 0.0;

      for (var i = 0; i < state.itemPrice.length; i++) {
        final price = state.itemPrice[i];
        vPrice += price;
      }
      productPrice = product!.offerPrice != 0.0 ? product.offerPrice : product.price;
      double basePrice = vPrice + productPrice;

      double result = basePrice;
      // double result = basePrice - discountAmount;
      emit(state.copyWith(detailPrice: result));
      // debugPrint('final-price ${state.detailPrice}');

  }


  Future<void> applyCoupon(String coupon) async {

    emit(state.copyWith(detailsState: CouponApplying()));

    final result = await _cartRepository.applyCoupon(coupon, _loginBloc.userInfo?.accessToken??'');

    result.fold((failure) {
        final error = CouponApplyError(failure.message, failure.statusCode);
        emit(state.copyWith(detailsState: error));
      },
        (success) {
        couponResponse = success;
        final loaded  = CouponApplied(success);
         emit(state.copyWith(detailsState: loaded));
      },
    );
  }

  Future<void> getCoupon() async {
    final result = _cartRepository.getAppliedCoupon();
    result.fold(
          (failure) {
            final error = CouponClearError(failure.message, failure.statusCode);
            emit(state.copyWith(detailsState: error));
      },
          (success) {
            couponResponse = success;
            //debugPrint('loaded-cache-coupon $success');
        final loaded  = CouponApplied(success);
            emit(state.copyWith(detailsState: loaded));
      },
    );
  }

  Future<void> clearCoupon() async {
    final result = await _repository.clearCoupon();
    result.fold(
          (failure) {
        final error = CouponClearError(failure.message, failure.statusCode);
        emit(state.copyWith(detailsState: error));
      },
          (success) {
        couponResponse = null;
        // debugPrint('coupon-clear-from-cubit');
        const loaded  = CouponCleared('Successfully cleared');
        emit(state.copyWith(priceAfterCoupon: 0.0,couponPrice: 0.0, detailsState: loaded));
      },
    );
  }

  void couponCalculate(){
    if(couponResponse != null){
      if(couponResponse?.offerType == 1){
        final discount = state.cartPrice * (couponResponse?.discount??1.0) / 100.0;
        final price = state.cartPrice - discount;
        emit(state.copyWith(couponPrice: discount,priceAfterCoupon: price));
      }else{
        final discount = couponResponse?.discount??0.0;
        final price = state.cartPrice - discount;

        emit(state.copyWith(couponPrice: discount,priceAfterCoupon: price));
      }
    }
  }

  void initState(){
    // debugPrint('initState-called');
    emit(state.copyWith(detailsState: const ProductDetailsInitial()));
  }

  void cartCalculation(BuildContext context) {
    double total = 0;
    for (var e in savedProduct) {
      total += Utils.guestCart(context, e) * e.qty.toDouble();
    }
    emit(state.copyWith(cartPrice: total));
  }

}
