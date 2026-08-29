import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../widgets/please_signin_widget.dart';
import '../models/seller_messages_dto.dart';
import '/utils/constants.dart';
import '/utils/language_string.dart';
import '/widgets/fetch_error_text.dart';
import '/widgets/loading_widget.dart';

import '../../../core/router_name.dart';
import '../../../utils/laravel_echo/laravel_echo.dart';
import '../../../utils/utils.dart';
import '../../../widgets/blank_content.dart';
import '../../../widgets/rounded_app_bar.dart';
import '../../../widgets/startup_container.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../controller/bloc/bloc/chat_bloc.dart';
import '../models/inbox_seller.dart';
import 'chat_list_item.dart';

class ChatListScreen extends StatefulWidget {
  const ChatListScreen({super.key});

  // static const routeName = "chat-list";

  @override
  State<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends State<ChatListScreen> {
  late LoginBloc authBloc;
  late ChatBloc chatBloc;

  @override
  void initState() {
    super.initState();
    _init();
  }

  _init(){
    authBloc = context.read<LoginBloc>();
    chatBloc = context.read<ChatBloc>();

    Future.microtask(()=>chatBloc.add(const ChatStarted()));
  }

  @override
  Widget build(BuildContext context) {


    return StartUpContainer(
      onInit: () async {
        // userBloc.add(const UserStarted());
        if(authBloc.userInfo?.accessToken.isNotEmpty??false){
          LaravelEcho.init(token: authBloc.userInfo?.accessToken??'');
        }
      },
      onDisposed: () {
        if(authBloc.userInfo?.accessToken.isNotEmpty??false){
          LaravelEcho.instance.disconnect();
        }
      },
      child: Scaffold(
        appBar: RoundedAppBar(
          titleText: Language.inbox,
          bgColor: scaBgColor,
          showBackButton: false,
        ),
        body: RefreshIndicator(
          onRefresh: () async {
            chatBloc.add(const ChatStarted());

            // userBloc.add(const UserStarted());
          },
          child: BlocConsumer<ChatBloc, ChatStateModel>(
            listener: (context, states) {
              final state = states.state;
              if(state is ChatError){
                if(state.statusCode == 503){

                }
              }

            },
            builder: (context, states) {
              final state = states.state;
             if(state is ChatLoading){
               //return const LoadingWidget();
             }else if(state is ChatError){
               if(state.statusCode == 401 && state.message.isEmpty){
                 return const PleaseSigninWidget();
               }else{
                 return FetchErrorText(text: state.message);
               }
             }else if(state is ChatLoaded){
               return LoadedChatView(sellers: state.chatsData);
             }
             if(chatBloc.allParticipants.isNotEmpty){
               return LoadedChatView(sellers: chatBloc.allParticipants);
             }else{
               return FetchErrorText(text: Language.loading);
             }
            },
          ),
        ),
      ),
    );
  }
}

class LoadedChatView extends StatelessWidget {
  const LoadedChatView({super.key, required this.sellers});
  final List<SellerDto> sellers;

  @override
  Widget build(BuildContext context) {
    final chatBloc = context.read<ChatBloc>();
    if(sellers.isNotEmpty){
      return ListView.builder(
        itemBuilder: (context, index) {
          final item = sellers[index];
          return ChatListItem(
            key: ValueKey(item.shopOwnerId),
            item: item,
            onPressed: (chat) {
              chatBloc.add(SelectedSeller(InboxSelectedSeller(
                id: chat.shopOwnerId,
                name: chat.shopName,
                image: chat.shopLogo,
              )));
              Navigator.pushNamed(context,RouteNames.chatScreen);
            },
          );
        },
        itemCount: sellers.length,
      );
    }else{
      return BlankContent(
        content: Language.emptyInboxTitle,
        icon: Icons.chat_rounded,
      );
    }
  }
}

