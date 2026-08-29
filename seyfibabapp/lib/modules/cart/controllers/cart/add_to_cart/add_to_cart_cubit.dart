import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../authentication/controller/login/login_bloc.dart';
import '../../../../../utils/language_string.dart';
import '../../../model/add_to_cart_model.dart';
import '../../cart_repository.dart';

part 'add_to_cart_state.dart';

class AddToCartCubit extends Cubit<AddToCartModel> {
  AddToCartCubit({
    required LoginBloc loginBloc,
    required CartRepository cartRepository,
  })  : _loginBloc = loginBloc, _cartRepository = cartRepository,
        super(AddToCartModel.init());

  final LoginBloc _loginBloc;
  final CartRepository _cartRepository;

  Future<void> addToCart(AddToCartModel dataModel) async {
    if (_loginBloc.userInfo == null) {
      final error = AddToCartStateError(Language.loginRequiredForCheckout, 401);
      emit(state.copyWith(addToState: error));
      return;
    }

    emit(state.copyWith(addToState: const AddToCartStateLoading()));

    dataModel = dataModel.copyWith(token: _loginBloc.userInfo!.accessToken);
    final result = await _cartRepository.addToCart(dataModel);

    result.fold(
      (failure) {
        final error = AddToCartStateError(failure.message, failure.statusCode);
        emit(state.copyWith(addToState: error));
      },
      (success) {
        final loaded = AddToCartStateAdded(success);
        emit(state.copyWith(addToState: loaded));
      },
    );
  }
}
