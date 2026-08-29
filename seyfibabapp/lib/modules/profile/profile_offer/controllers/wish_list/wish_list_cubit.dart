import 'package:dartz/dartz.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../../core/error/failure.dart';
import '../../../../authentication/controller/login/login_bloc.dart';
import '../../../../../utils/language_string.dart';
import '../../../controllers/repository/profile_repository.dart';
import '../../model/wish_list_model.dart';

part 'wish_list_state.dart';

class WishListCubit extends Cubit<WishListState> {
  WishListCubit({
    required LoginBloc loginBloc,
    required ProfileRepository profileRepository,
  })  : _loginBloc = loginBloc,
        _profileRepository = profileRepository,
        super(const WishListStateInitial());

  final LoginBloc _loginBloc;
  final ProfileRepository _profileRepository;

  List<WishListModel> wishList = [];

  List<int> selectedId = [];

  Future<void> getWishList() async {
    if (_loginBloc.userInfo == null) {
      emit(WishListStateError(Language.loginRequiredForCheckout, 1000));
      return;
    }
    emit(const WishListStateLoading());

    final result =
        await _profileRepository.wishList(_loginBloc.userInfo!.accessToken);

    result.fold(
      (failure) {
        emit(WishListStateError(failure.message, failure.statusCode));
      },
      (wishList) {
        this.wishList = wishList;
        emit(WishListStateLoaded(wishList));
      },
    );
  }

  Future<Either<Failure, String>> removeWishList(WishListModel item) async {
    if (_loginBloc.userInfo == null) {
      return left(ServerFailure(Language.loginRequiredForCheckout, 1000));
    }
    debugPrint('remove-wish-id ${item.id}');
    final result = await _profileRepository.removeWishList(
        item.wishId.toString(), _loginBloc.userInfo!.accessToken);

    result.fold((failure) {
      emit(WishListStateError(failure.message, failure.statusCode));
    }, (success) {
      wishList.removeWhere((element) => element.id == item.id);
      emit(WishListStateSuccess(
        message: success,
        productId: item.id.toString(),
        added: false,
      ));
    });

    return result;
  }

  Future<void> clearWishList() async {
    if (_loginBloc.userInfo == null) {
      emit(WishListStateError(Language.loginRequiredForCheckout, 1000));
      return;
    }
    emit(const WishListStateLoading());
    final result = await _profileRepository
        .clearWishList(_loginBloc.userInfo!.accessToken);

    result.fold(
      (failure) {
        emit(WishListStateError(failure.message, failure.statusCode));
      },
      (wishList) {
        this.wishList.clear();
        emit(const WishListStateLoaded([]));
      },
    );
  }

  Future<Either<Failure, String>> addWishList(String id) async {
    if (_loginBloc.userInfo == null) {
      return left(ServerFailure(Language.loginRequiredForCheckout, 1000));
    }
    debugPrint('add-wish-id $id');
    emit(const WishListStateLoading());
    final result = await _profileRepository.addWishList(
        id.toString(), _loginBloc.userInfo!.accessToken);

    result.fold((failure) {
      emit(WishListStateError(failure.message, failure.statusCode));
    }, (success) {
      emit(WishListStateSuccess(
        message: success,
        productId: id,
        added: true,
      ));
      getWishList();
    });
    return result;
  }

/*  Future<void> addWishList(int id) async {
    bool isExists = false;
    WishListModel? item;
    if (_loginBloc.userInfo == null) {
      // return left(const ServerFailure("Please login first", 1000));
      return;
    }
    emit(const WishListStateLoading());
    for (var element in wishList) {
      print("Element id: ${element.id}");
      print(" id: $id");

      if (element.productId == id.toString()) {
        item = element;
        break;
      }
    }
    Either<Failure, String> result;
    Future.delayed(const Duration(microseconds: 00)).then((value) async {
      print('ITEM ID: $item');
      if (item != null) {
        result = await _profileRepository.removeWishList(
            item.wishId, _loginBloc.userInfo!.accessToken);
        print("Item Not Null is Calling");
      } else {
        result = await _profileRepository.addWishList(
            id, _loginBloc.userInfo!.accessToken);
        print("Item Else is Calling");
      }
      getWishList();
      result.fold((failure) {
        emit(WishListStateError(failure.message, failure.statusCode));
      }, (success) {
        emit(WishListStateSuccess(success));
      });
      return result;
    });
  }*/
}

// import 'package:dartz/dartz.dart';
// import 'package:equatable/equatable.dart';
// import 'package:flutter/cupertino.dart';
// import 'package:flutter_bloc/flutter_bloc.dart';
//
// import '../../../../../core/error/failure.dart';
// import '../../../../authentication/controller/login/login_bloc.dart';
// import '../../../controllers/repository/profile_repository.dart';
// import '../../model/wish_list_model.dart';
//
// part 'wish_list_state.dart';
//
// class WishListCubit extends Cubit<WishListModel> {
//   WishListCubit({
//     required LoginBloc loginBloc,
//     required ProfileRepository profileRepository,
//   })  : _loginBloc = loginBloc,
//         _repository = profileRepository,
//         super(WishListModel.init());
//
//   final LoginBloc _loginBloc;
//   final ProfileRepository _repository;
//
//   List<WishListModel> wishList = [];
//
//   List<int> selectedId = [];
//
//   Future<void> getWishList() async {
//     if (_loginBloc.userInfo == null) {
//       const error = WishListStateError("Please login first", 1000);
//       emit(state.copyWith(wState: error));
//       return;
//     }
//     emit(state.copyWith(wState: const WishListStateLoading()));
//     final result = await _repository.wishList(_loginBloc.userInfo!.accessToken);
//
//     result.fold(
//       (failure) {
//         final error = WishListStateError(failure.message, failure.statusCode);
//         emit(state.copyWith(wState: error));
//       },
//       (wishList) {
//         this.wishList = wishList;
//         final loaded = WishListStateLoaded(wishList);
//         emit(state.copyWith(wState: loaded));
//         addWishIds();
//       },
//     );
//   }
//
//   Future<void> addRemoveClearWishlist(String id, WishType type) async {
//     if (_loginBloc.userInfo != null &&
//         _loginBloc.userInfo!.accessToken.isNotEmpty) {
//       emit(state.copyWith(wState: const WishListStateLoading()));
//       Either<Failure, String> result;
//       if (type == WishType.remove) {
//         debugPrint('wishlist-remove $id');
//         result = await _repository.removeWishList(
//             id, _loginBloc.userInfo!.accessToken);
//       } else if (type == WishType.clear) {
//         debugPrint('wishlist-clear $id');
//         result =
//             await _repository.clearWishList(_loginBloc.userInfo!.accessToken);
//       } else {
//         debugPrint('wishlist-add $id');
//         result =
//             await _repository.addWishList(id, _loginBloc.userInfo!.accessToken);
//       }
//
//       result.fold(
//         (failure) {
//           if (type == WishType.remove) {
//             wishList.removeWhere((wish) => wish.id.toString() == id);
//             final errors =
//                 WishListStateError(failure.message, failure.statusCode);
//             emit(state.copyWith(wState: errors));
//             return false;
//           } else {
//             final errors =
//                 WishListStateError(failure.message, failure.statusCode);
//             emit(state.copyWith(wState: errors));
//           }
//         },
//         (success) {
//           if (type == WishType.clear) {
//             wishList.clear();
//             emit(state.copyWith(wState: const WishListStateLoaded([])));
//           } else {
//             final loaded = WishListStateSuccess(success);
//             emit(state.copyWith(wState: loaded));
//           }
//         },
//       );
//     }
//   }
//
//   void addWishIds() {
//     // compareList.clear();
//     if (state.tempWishId.isNotEmpty) {
//       emit(state.copyWith(tempWishId: <int>[]));
//     }
//     if (wishList.isNotEmpty) {
//       for (var comId in wishList) {
//         //debugPrint('compare-id-added ${comId.id}');
//         final updatedComId = List.of(state.tempWishId)..add(comId.id);
//         emit(state.copyWith(tempWishId: updatedComId));
//       }
//     }
//   }
//
//   void addSingComId(int text) {
//     if (!state.tempWishId.contains(text)) {
//       //debugPrint('add-newComId $text');
//       final updatedComId = List.of(state.tempWishId)..add(text);
//       emit(state.copyWith(tempWishId: updatedComId));
//     } else {
//       //debugPrint('removed-oldComId $text');
//       final updatedComId = List.of(state.tempWishId)..remove(text);
//       emit(state.copyWith(tempWishId: updatedComId));
//     }
//   }
// }
//
// enum WishType { add, remove, clear }
