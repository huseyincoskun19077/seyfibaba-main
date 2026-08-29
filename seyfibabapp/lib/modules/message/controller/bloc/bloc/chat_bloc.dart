import 'dart:async';
import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../authentication/controller/login/login_bloc.dart';
import '../../../../product_details/model/product_details_model.dart';
import '../../../models/inbox_seller.dart';
import '../../../models/seller_messages_dto.dart';
import '../../../models/send_message_model.dart';
import '../../../models/send_message_response_dto.dart';
import '../../repository/chat_respository.dart';

part 'chat_event.dart';
part 'chat_state.dart';

class ChatBloc extends Bloc<ChatEvent, ChatStateModel> {
  final ChatRepository _chatRepository;
  final LoginBloc _loginBloc;
  SellerDto? messagesDto;
  List<MessagesDto> messages = [];
  late InboxSelectedSeller seller;
  List<SellerDto> allParticipants = [];

  ChatBloc(
      {required ChatRepository chatRepository, required LoginBloc loginBloc})
      : _chatRepository = chatRepository,
        _loginBloc = loginBloc,
        super(const ChatStateModel()) {
    on<ChatStarted>(_loadChats);
    on<SendMsgData>(_submitMessage);
    on<SelectedSeller>(_store);
    on<SendProductToSeller>(_sendProductToSeller);
    on<AddNewMessage>(_addIncomingMessage);
    // on<SelectedChat>(_store);
    // on<SendProduct>(_sendProductMessage);
  }

  FutureOr<void> _submitMessage(SendMsgData event, Emitter<ChatStateModel> emit) async {
    final body = event.sendMsgModel.toMap();
    emit(state.copyWith(state: const ChatInitial()));
    final result = await _chatRepository.createChat(
        body, _loginBloc.userInfo!.accessToken);

    result.fold((l) {
      emit(state.copyWith(state: ChatError(l.message,l.statusCode)));
    }, (r) {
      // _loadChats;
      messages = r.messages;
      if (messagesDto == null) {
        if (allParticipants.isNotEmpty) {
          for (var i in allParticipants) {
            if (event.sendMsgModel.sellerId == i.shopOwnerId.toString()) {
              messagesDto = i;
              break;
            }
          }
        }
      }
      emit(state.copyWith(state: InboxMessages(r.messages)));
    });
  }

  FutureOr<void> _loadChats(ChatStarted event, Emitter<ChatStateModel> emit) async {
    if (_loginBloc.userInfo?.accessToken.isNotEmpty??false) {
      emit(state.copyWith(state: ChatLoading()));

      final result = await _chatRepository.getChats(_loginBloc.userInfo?.accessToken??'');

      result.fold((failure) {
        final errorState = ChatError(failure.message,failure.statusCode);
        emit(state.copyWith(state: errorState));
      }, (success) {
        final successState = ChatLoaded(success);
        allParticipants.addAll(success);
        emit(state.copyWith(state: successState));
      });
    }else{
      allParticipants.clear();
      const errorState = ChatError('',401);
      emit(state.copyWith(state: errorState));
    }
  }

  FutureOr<void> _store(SelectedSeller event, Emitter<ChatStateModel> emit) {
    seller = event.seller;
    if (allParticipants.isNotEmpty) {
      for (var i in allParticipants) {
        if (event.seller.id == i.shopOwnerId) {
          messagesDto = i;
          messages = i.messages;
          break;
        } else {
          messages = [];
        }
      }
      emit(state.copyWith(state: InboxMessages(messages)));
    }
    // if(event.selectChat.messages.isNotEmpty){
    //   messagesDto = event.selectChat;
    //   messages = event.selectChat.messages;
    //
    // }else{
    //   messagesDto = null;
    // }
  }

  Future<void> chatWithSeller(int id, ProductDetailsModel productModel) async {}

  // FutureOr<void> _sendProductMessage(SendProduct event, Emitter<ChatStateModel> emit) {
  //   emit(state.copyWith(state: ))
  // }

  FutureOr<void> _sendProductToSeller(SendProductToSeller event, Emitter<ChatStateModel> emit) {
    final r = state.state;
    // emit(state.copyWith(state: ChatProductMessage(event.productModel)));
    if (r is ChatLoaded) {
      for (var i in r.chatsData) {
        if (i.shopOwnerId == event.productModel.sellerProfile!.id) {
          messagesDto = i;
          break;
        } else {
          messagesDto = null;
        }
      }
    }
  }

  FutureOr<void> _addIncomingMessage(AddNewMessage event, Emitter<ChatStateModel> emit) {
    messages.add(event.newMessage);
    emit(state.copyWith(state: InboxMessages(messages)));
  }
}
