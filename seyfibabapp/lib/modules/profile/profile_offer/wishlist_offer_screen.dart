import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/widgets/fetch_error_text.dart';
import 'package:shop_o/widgets/loading_widget.dart';
import '../../../utils/k_images.dart';
import '../../../widgets/app_empty_state.dart';
import '../../../widgets/confirm_dialog.dart';
import '../../../widgets/custom_text.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';

import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/rounded_app_bar.dart';
import '../component/wish_list_card.dart';
import 'controllers/wish_list/wish_list_cubit.dart';
import 'model/wish_list_model.dart';

class WishlistOfferScreen extends StatefulWidget {
  const WishlistOfferScreen({super.key});

  @override
  State<WishlistOfferScreen> createState() => _WishlistOfferScreenState();
}

class _WishlistOfferScreenState extends State<WishlistOfferScreen> {
  late WishListCubit wCubit;

  @override
  void initState() {
    wCubit = context.read<WishListCubit>();
    super.initState();
    Future.microtask(() {
      wCubit.getWishList();
      wCubit.selectedId.clear();
      wCubit.wishList.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // backgroundColor: bgColor,
      appBar: RoundedAppBar(titleText: Language.wishlist.capitalizeByWord()),
      body: BlocConsumer<WishListCubit, WishListState>(
        listener: (context, state) {
          if (state is WishListStateAddRemoveError) {
            Utils.errorSnackBar(context, state.message);
          } else if (state is WishListStateSuccess) {
            Utils.showSnackBar(context, state.message);
          }
        },
        builder: (context, state) {
          if (state is WishListStateLoading) {
            return const LoadingWidget();
          } else if (state is WishListStateError) {
            return FetchErrorText(text: state.message);
          }
          if (wCubit.wishList.isNotEmpty) {
            return const _LoadedWidget();
          } else {
            return AppEmptyState(
              icon: Icons.favorite_border_rounded,
              title: Language.emptyWishlistTitle,
              subtitle: Language.emptyWishlistHint,
            );
          }
        },
      ),
      bottomNavigationBar: wCubit.wishList.isNotEmpty?  Container(
        height: 60,
        color: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 20),
        alignment: Alignment.centerRight,
        child: TextButton(
          style: TextButton.styleFrom(foregroundColor: redColor),
          onPressed: () async {
            await showDialog(
              context: context,
              barrierDismissible: false,
              builder: (context) => ConfirmDialog(
                icon: Kimages.deleteIcon2,
                message: Language.removeProductConfirm,
                confirmText: Language.yesRemove,
                cancelText: Language.cancel,
                onTap: () async {
                  context.read<WishListCubit>().clearWishList();
                },
              ),
            );
          },
          child: CustomText(
            text: Language.clearWishlist.capitalizeByWord(),
            color: redColor,
            fontWeight: FontWeight.w500,
          ),
        ),
      ):const SizedBox.shrink(),
    );
  }
}

class _LoadedWidget extends StatefulWidget {
  const _LoadedWidget();

  @override
  State<_LoadedWidget> createState() => __LoadedWidgetState();
}

class __LoadedWidgetState extends State<_LoadedWidget> {
  List<WishListModel> productList = [];

  @override
  void initState() {
    super.initState();
    productList = context.read<WishListCubit>().wishList;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (productList.isNotEmpty) ...[
          const SizedBox(height: 20),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                const Icon(Icons.favorite, color: redColor),
                const SizedBox(width: 10),
                CustomText(
                    text: _getText(productList.length),
                    fontSize: 16.0,
                    fontWeight: FontWeight.w600),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: CustomText(
              text: Language.swipeToDelete.capitalizeByWord(),
            ),
          ),
          Expanded(
            child: ListView.separated(
              separatorBuilder: (_, __) {
                return const SizedBox(height: 16);
              },
              padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
              itemBuilder: (context, index) {
                return Dismissible(
                  key: UniqueKey(),
                  direction: DismissDirection.horizontal,
                  confirmDismiss: (v) async {
                    return await showDialog(
                      context: context,
                      barrierDismissible: false,
                      builder: (context) => ConfirmDialog(
                        icon: Kimages.deleteIcon2,
                        message: Language.removeProductConfirm,
                        confirmText: Language.yesRemove,
                        cancelText: Language.cancel,
                        onTap: () async {
                          final item = productList.removeAt(index);
                          setState(() {});
                          final result = await context
                              .read<WishListCubit>()
                              .removeWishList(item);
                          result.fold(
                            (failure) {
                              productList.add(item);
                              setState(() {});
                              Utils.errorSnackBar(context, failure.message);
                            },
                            (success) {
                              Utils.showSnackBar(context, success);
                            },
                          );
                        },
                      ),
                    );
                  },
                  background: Container(
                    decoration: BoxDecoration(
                      color: redColor,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    padding: const EdgeInsets.only(right: 40),
                    alignment: Alignment.centerRight,
                    child: const Icon(
                      Icons.delete,
                      size: 30,
                      color: Colors.white,
                    ),
                  ),
                  child: WishListCard(product: productList[index]),
                );
              },
              itemCount: productList.length,
            ),
          ),
        ] else ...[
          Expanded(
            child: AppEmptyState(
              icon: Icons.favorite_border_rounded,
              title: Language.emptyWishlistTitle,
              subtitle: Language.emptyWishlistHint,
            ),
          ),
        ],
      ],
    );
  }

  //
  // Widget _buildBottomButtons() {
  //   return Container(
  //       alignment: Alignment.center,
  //       child: TextButton(onPressed: (){}, child: const Text("Clear Wishlist")));
  // }

  String _getText(int length) {
    if (length > 1) {
      return '$length ${Language.products}';
    }
    return '$length ${Language.product}';
  }
}
