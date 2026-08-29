part of 'product_details_cubit.dart';

abstract class ProductDetailsState extends Equatable {
  const ProductDetailsState();

  @override
  List<Object> get props => [];
}

class ProductDetailsInitial extends ProductDetailsState {
  const ProductDetailsInitial();
}

class ProductDetailsStateLoading extends ProductDetailsState {}

class ProductDetailsStateError extends ProductDetailsState {
  final String errorMessage;
  final int statusCode;
  const ProductDetailsStateError(this.errorMessage, this.statusCode);

  @override
  List<Object> get props => [errorMessage, statusCode];
}

class ProductDetailsStateLoaded extends ProductDetailsState {
  final ProductDetailsModel productDetailsModel;
  const ProductDetailsStateLoaded(this.productDetailsModel);

  @override
  List<Object> get props => [productDetailsModel];
}


class GuestProductLoading extends ProductDetailsState {}

class GuestProductError extends ProductDetailsState {
  final String message;
  final int statusCode;
  const GuestProductError(this.message, this.statusCode);

  @override
  List<Object> get props => [message, statusCode];
}


class GuestAddProductError extends ProductDetailsState {
  final String message;
  final int statusCode;
  const GuestAddProductError(this.message, this.statusCode);

  @override
  List<Object> get props => [message, statusCode];
}

class GuestSaveProduct extends ProductDetailsState {
  final String message;
  const GuestSaveProduct(this.message);

  @override
  List<Object> get props => [message];
}

class GuestUpdateProduct extends ProductDetailsState {
  final String message;
  const GuestUpdateProduct(this.message);

  @override
  List<Object> get props => [message];
}

class GuestAllSavedProduct extends ProductDetailsState {
  final List<GustCartProduct> products;
  const GuestAllSavedProduct(this.products);

  @override
  List<Object> get props => [products];
}

class GuestSaveProductDeleted extends ProductDetailsState {
  final String message;
  const GuestSaveProductDeleted(this.message);

  @override
  List<Object> get props => [message];
}

class GuestSaveProductClear extends ProductDetailsState {
  final String message;
  const GuestSaveProductClear(this.message);

  @override
  List<Object> get props => [message];
}

class GuestProductQtyLoaded extends ProductDetailsState {
  final String message;
  const GuestProductQtyLoaded(this.message);

  @override
  List<Object> get props => [message];
}


class CouponApplying extends ProductDetailsState {}

class CouponApplyError extends ProductDetailsState {
  final String message;
  final int statusCode;
  const CouponApplyError(this.message, this.statusCode);

  @override
  List<Object> get props => [message, statusCode];
}

class CouponApplied extends ProductDetailsState {
  final CouponResponseModel coupon;
  const CouponApplied(this.coupon);

  @override
  List<Object> get props => [coupon];
}

class CouponCleared extends ProductDetailsState {
  final String message;
  const CouponCleared(this.message);

  @override
  List<Object> get props => [message];
}

class CouponClearError extends ProductDetailsState {
  final String message;
  final int statusCode;
  const CouponClearError(this.message, this.statusCode);

  @override
  List<Object> get props => [message, statusCode];
}
