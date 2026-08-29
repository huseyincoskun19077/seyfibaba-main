import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '/modules/profile/profile_offer/controllers/wish_list/wish_list_cubit.dart';
import '../modules/profile/profile_offer/model/wish_list_model.dart';
import '../utils/constants.dart';
import '../utils/language_string.dart';
import '../utils/utils.dart';

class FavoriteButton extends StatefulWidget {
  const FavoriteButton({super.key, required this.productId, this.isBg = true});

  final String productId;
  final bool isBg;

  @override
  State<FavoriteButton> createState() => _FavoriteButtonState();
}

class _FavoriteButtonState extends State<FavoriteButton> {
  final double height = 30;

  WishListModel? wishItem;
  bool isFav = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _syncFromCubit();
  }

  void _syncFromCubit() {
    final wishListItems = context.read<WishListCubit>().wishList;
    final match = wishListItems
        .where((element) => element.id.toString() == widget.productId)
        .toList();
    if (!mounted) return;
    setState(() {
      isFav = match.isNotEmpty;
      wishItem = match.isNotEmpty ? match.first : null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<WishListCubit, WishListState>(
      listenWhen: (previous, current) {
        if (current is WishListStateSuccess) {
          return current.productId == widget.productId;
        }
        if (current is WishListStateLoaded) {
          return true;
        }
        if (current is WishListStateError) {
          return _busy;
        }
        return false;
      },
      listener: (context, state) {
        if (state is WishListStateSuccess) {
          setState(() {
            isFav = state.added;
            _busy = false;
          });
          Utils.showSnackBar(context, state.message);
          _syncFromCubit();
        } else if (state is WishListStateLoaded) {
          setState(() => _busy = false);
          _syncFromCubit();
        } else if (state is WishListStateError) {
          setState(() => _busy = false);
          Utils.errorSnackBar(context, state.message);
        }
      },
      child: InkWell(
        onTap: _busy ? null : _onTap,
        child: Container(
          height: height,
          width: height,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(4.0),
          ),
          child: Padding(
            padding: const EdgeInsets.all(4.0),
            child: _busy
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Icon(
                    isFav ? Icons.favorite : Icons.favorite_border,
                    color: redColor,
                  ),
          ),
        ),
      ),
    );
  }

  Future<void> _onTap() async {
    setState(() => _busy = true);
    if (isFav) {
      if (wishItem != null) {
        await context.read<WishListCubit>().removeWishList(wishItem!);
      } else {
        setState(() => _busy = false);
        Utils.showSnackBar(context, Language.somethingWentWrong);
      }
    } else {
      await context.read<WishListCubit>().addWishList(widget.productId);
    }
  }
}
