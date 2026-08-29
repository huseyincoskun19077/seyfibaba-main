import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '/widgets/app_bar_leading.dart';
import '/widgets/fetch_error_text.dart';
import '/widgets/page_refresh.dart';

import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../core/router_name.dart';
import '../authentication/controller/login/login_bloc.dart';
import '../notification/services/buyer_notification_service.dart';
import '../../utils/constants.dart';
import '../../utils/utils.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/toggle_button_component.dart';
import '/modules/product_details/component/loader_screen.dart';
import '../home/component/home_app_bar.dart';
import '../cart/controllers/cart/cart_cubit.dart';
import 'component/bottom_sheet_widget.dart';
import 'component/description_component.dart';
import 'component/product_details_component.dart';
import 'component/product_header_component.dart';
import 'component/rating_list_component.dart';
import 'component/related_products_list.dart';
import 'component/variant_bottom_sheet.dart';
import 'controller/cubit/details_state_model.dart';
import 'controller/cubit/product_details_cubit.dart';
import 'model/product_details_model.dart';
import 'utils/product_share_helper.dart';

class ProductDetailsScreen extends StatefulWidget {
  const ProductDetailsScreen({super.key, required this.slug});

  final String slug;

  @override
  State<ProductDetailsScreen> createState() => _ProductDetailsScreenState();
}

class _ProductDetailsScreenState extends State<ProductDetailsScreen> {
  int selectedIndex = 0;
  late ProductDetailsCubit detailCubit;
  final _buyerNotificationService = BuyerNotificationService();
  int? _trackedProductId;

  @override
  void initState() {
    detailCubit = context.read<ProductDetailsCubit>();
    Future.microtask(() => detailCubit.getProductDetails(widget.slug));
    super.initState();
  }

  void _trackProductView(int productId) {
    if (_trackedProductId == productId) {
      return;
    }

    final loginBloc = context.read<LoginBloc>();
    if (!loginBloc.isLogedIn) {
      return;
    }

    _trackedProductId = productId;
    _buyerNotificationService
        .recordProductView(loginBloc.userInfo!.accessToken, productId)
        .catchError((_) {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: PageRefresh(
        onRefresh: () async {
          detailCubit.getProductDetails(widget.slug);
        },
        child: BlocConsumer<ProductDetailsCubit, DetailsStateModel>(
          listener: (context, states) {
            final state = states.detailsState;
            if (state is ProductDetailsStateError) {
              if (state.statusCode == 503 || detailCubit.details == null) {
                detailCubit.getProductDetails(widget.slug);
              }
            }else if(state is GuestAddProductError){
              detailCubit.initState();
              Utils.errorSnackBar(context, state.message,redColor,3000);
            }else if (state is GuestSaveProduct) {
              detailCubit.initState();
              Utils.showSnackBar(context, state.message);
              // detailCubit.addQty('');
              Future.delayed(const Duration(milliseconds: 600),(){
              detailCubit..initState()..addQty('')..getGuestSavedProduct();
              });
            }

          },
          builder: (context, states) {
            final state = states.detailsState;
            if (state is ProductDetailsStateLoading) {
              return const Center(child: DetailsPageLoading());
            }
            if (state is ProductDetailsStateError) {
              if (state.statusCode == 503 || detailCubit.details != null) {
                return _buildLoadedPage(detailCubit.details!);
              } else {
                return FetchErrorText(text: state.errorMessage);
              }
            }
            if (state is ProductDetailsStateLoaded) {
              _trackProductView(state.productDetailsModel.id);
              return _buildLoadedPage(state.productDetailsModel);
            }
            if (detailCubit.details != null) {
              _trackProductView(detailCubit.details!.id);
              return _buildLoadedPage(detailCubit.details!);
            } else {
              return FetchErrorText(text: Language.somethingWentWrong);
            }
          },
        ),
      ),
    );
  }

  Widget _buildLoadedPage(ProductDetailsModel productDetailsModel) {
    return Stack(
      alignment: Alignment.bottomCenter,
      children: [
        CustomScrollView(
          slivers: [
            SliverAppBar(
              backgroundColor: borderColor.withOpacity(0.2),
              leading: const AppbarLeading(),
              pinned: true,
              title: Text(
                productDetailsModel.product.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: blackColor,
                ),
              ),
              actions: [
                IconButton(
                  tooltip: Language.shareProduct,
                  onPressed: () => shareProduct(
                    name: productDetailsModel.product.name,
                    slug: productDetailsModel.product.slug,
                  ),
                  icon: const Icon(Icons.share_outlined, color: blackColor),
                ),
              ],
            ),
            SliverToBoxAdapter(
                child: ProductHeaderComponent(
                    productDetailsModel.product, productDetailsModel.gallery)),
            // const SliverToBoxAdapter(
            //   child: SizedBox(height: 0.0),
            // ),
            SliverToBoxAdapter(
              child: ProductDetailsComponent(
                detailsModel: productDetailsModel,
                product: productDetailsModel.product,
              ),
            ),
            const SliverToBoxAdapter(child: SizedBox(height: 25)),
            SliverToBoxAdapter(
              child: ToggleButtonComponent(
                textList: [
                  Language.description.capitalizeByWord(),
                  '${Language.reviews.capitalizeByWord()} (${productDetailsModel.productReviews.length})',
                ],
                initialLabelIndex: 0,
                onChange: (int i) {
                  setState(() {
                    selectedIndex = i;
                  });
                },
              ),
            ),
            const SliverToBoxAdapter(child: SizedBox(height: 20)),
            SliverToBoxAdapter(child: getChild(productDetailsModel)),
            if (productDetailsModel.relatedProducts.isNotEmpty) ...[
              SliverToBoxAdapter(
                  child:
                      RelatedProductsList(productDetailsModel.relatedProducts)),
            ],
            const SliverToBoxAdapter(child: SizedBox(height: 95)),
          ],
        ),
        _buildBottomButtons(productDetailsModel),
      ],
    );
  }

  Widget getChild(ProductDetailsModel productDetailsModel) {
    if (selectedIndex == 0) {
      return DescriptionComponent(productDetailsModel.product.longDescription);
    } else if (selectedIndex == 1) {
      return ReviewListComponent(productDetailsModel.productReviews);
    }
    return const SizedBox();
  }

  Widget _buildBottomButtons(ProductDetailsModel productDetailsModel) {
    final dCubit = context.read<ProductDetailsCubit>();
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: whiteColor,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(.2),
            offset: const Offset(-9, -1),
            blurRadius: 30,
            spreadRadius: 30,
          )
        ],
        borderRadius: const BorderRadius.vertical(top: Radius.circular(0)),
      ),
      child: Row(
        children: [
          /*if(loginBloc.userInfo?.accessToken.isNotEmpty??false)...[
            InkWell(
              onTap: () {
                if(loginBloc.userInfo?.accessToken.isNotEmpty??false){
                  Navigator.pushNamed(context, RouteNames.cartScreen);
                }else{
                  Navigator.pushNamed(context, RouteNames.guestCartScreen);
                }
              },
              child: Container(
                height: 50.0,
                width: 50.0,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: borderColor,
                  borderRadius: Utils.borderRadius(r: 4.0),
                ),
                child: BlocBuilder<CartCubit, CartState>(
                  builder: (context, state) {
                    return CartBadge(
                      iconColor: blackColor,
                      count: context.read<CartCubit>().cartCount.toString(),
                    );
                  },
                ),
              ),
            ),
          ]else...[
            InkWell(
              onTap: () {
                if(loginBloc.userInfo?.accessToken.isNotEmpty??false){
                  Navigator.pushNamed(context, RouteNames.cartScreen);
                }else{
                  Navigator.pushNamed(context, RouteNames.guestCartScreen);
                }
              },
              child:Container(
                height: 50.0,
                width: 50.0,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: borderColor,
                  borderRadius: Utils.borderRadius(r: 4.0),
                ),
                child: BlocBuilder<ProductDetailsCubit, DetailsStateModel>(
                  builder: (context, state) {
                    return CartBadge(
                      iconColor: blackColor,
                      count: state.count.toString(),
                    );
                  },
                ),
              ),
            ),
          ],*/
          InkWell(
            onTap: () {
              if (Utils.isLoggedIn(context)) {
                Navigator.pushNamed(context, RouteNames.cartScreen);
              } else {
                Navigator.pushNamed(context, RouteNames.authenticationScreen);
              }
            },
            child:Container(
              height: 50.0,
              width: 50.0,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: borderColor,
                borderRadius: Utils.borderRadius(r: 4.0),
              ),
              child: Utils.isLoggedIn(context)
                  ? BlocBuilder<CartCubit, CartState>(
                      builder: (context, _) {
                        return CartBadge(
                          iconColor: blackColor,
                          count: context
                              .read<CartCubit>()
                              .cartCount
                              .toString(),
                        );
                      },
                    )
                  : BlocBuilder<ProductDetailsCubit, DetailsStateModel>(
                      builder: (context, state) {
                        return CartBadge(
                          iconColor: blackColor,
                          count: state.count.toString(),
                        );
                      },
                    ),
            ),
          ),
          const SizedBox(width: 20),
          Flexible(
            child: PrimaryButton(
              text: Language.addToCart.capitalizeByWord(),
              onPressed: () {
                dCubit.initState();
                final hasVariants =
                    productDetailsModel.product.activeVariantModel.isNotEmpty;

                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  shape: const RoundedRectangleBorder(
                    borderRadius: BorderRadius.zero,
                  ),
                  builder: (BuildContext context) {
                    return SingleChildScrollView(
                      child: ConstrainedBox(
                        constraints: BoxConstraints(
                          maxHeight: Utils.mediaQuery(context).height * 0.8,
                        ),
                        child: IntrinsicHeight(
                          child: hasVariants
                              ? const VariantBottomSheet()
                              : BottomSheetWidget(
                                  product: productDetailsModel.product,
                                ),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
