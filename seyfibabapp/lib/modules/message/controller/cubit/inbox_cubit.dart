import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../product_details/model/product_details_model.dart';

part 'inbox_state.dart';

class InboxCubit extends Cubit<InboxState> {
  InboxCubit() : super(InboxInitial());

  loadProduct(ProductDetailsModel productDetailsModel){
    emit(InboxProductDataLoaded(productDetailsModel));
  }
  reset(){
    emit(InboxInitial());
  }

}
