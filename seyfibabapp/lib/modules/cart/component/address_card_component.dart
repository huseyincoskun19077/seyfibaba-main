import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../widgets/custom_text.dart';
import '../../profile/controllers/address/address_cubit.dart';
import '../../profile/controllers/map/map_cubit.dart';
import '/utils/language_string.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../profile/model/address_model.dart';

class AddressCardComponent extends StatelessWidget {
  const AddressCardComponent({
    super.key,
    required this.addressModel,
    required this.type,
    this.onTap,
    this.pWidth,
    this.selectAddress = 0,
    this.isEditButtonShow = true,
    this.showInvoice = true,
  });

  final AddressModel addressModel;
  final VoidCallback? onTap;
  final String type;
  final double? pWidth;
  final int selectAddress;
  final bool isEditButtonShow;
  final bool showInvoice;

  static bool isOfficeType(String type) {
    final value = type.trim().toLowerCase();
    return value == 'office' || value == '1';
  }

  static String typeLabel(String type) {
    return isOfficeType(type)
        ? Language.addressTypeOffice
        : Language.addressTypeHome;
  }

  @override
  Widget build(BuildContext context) {
    final width = pWidth ?? MediaQuery.of(context).size.width * 0.8;
    final invoiceLabel = addressModel.invoiceType == 'corporate'
        ? 'Kurumsal'
        : 'Bireysel';
    final invoiceExtra = addressModel.invoiceType == 'corporate'
        ? addressModel.companyName
        : (addressModel.tcIdentity.trim().length >= 4
            ? 'TC ****${addressModel.tcIdentity.trim().substring(addressModel.tcIdentity.trim().length - 4)}'
            : 'TC eksik');

    return Container(
      padding: Utils.symmetric(v: 8.0, h: 10.0),
      width: width,
      decoration: BoxDecoration(
          color: whiteColor,
          borderRadius: Utils.borderRadius(r: 12.0),
          border: Border.all(
              color:
                  selectAddress == addressModel.id ? greenColor : transparent),
          boxShadow: [
            BoxShadow(
                offset: const Offset(0.0, 0.0),
                spreadRadius: 0.0,
                blurRadius: 0.0,
                color: const Color(0xFF000000).withOpacity(0.4)),
          ]),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.only(top: 4, bottom: 10.0),
            child: Icon(Icons.location_on_outlined, color: blackColor),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: CustomText(
                          text: addressModel.name,
                          isTranslate: false,
                          maxLine: 1,
                          overflow: TextOverflow.ellipsis,
                          fontSize: 16,
                          fontWeight: FontWeight.w600),
                    ),
                    isEditButtonShow
                        ? const SizedBox()
                        : InkWell(
                            onTap: () async {
                              context.read<AddressCubit>().clearAddress();
                              context.read<MapCubit>().clear();
                              Navigator.pushNamed(
                                context,
                                RouteNames.editAddressScreen,
                                arguments: {
                                  "address_id": addressModel.id,
                                  "show_invoice": showInvoice,
                                },
                              );
                            },
                            child: CircleAvatar(
                              radius: 14.0,
                              backgroundColor: grayColor.withOpacity(0.2),
                              child: const Icon(Icons.edit,
                                  size: 14.0, color: blackColor),
                            ),
                          )
                  ],
                ),
                CustomText(
                  text: addressModel.email,
                  isTranslate: false,
                  color: iconGreyColor,
                ),
                CustomText(
                  text: addressModel.phone,
                  isTranslate: false,
                  color: iconGreyColor,
                ),
                Utils.verticalSpace(4.0),
                CustomText(
                  text: typeLabel(addressModel.type.isNotEmpty
                      ? addressModel.type
                      : type),
                  color: redColor,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  maxLine: 1,
                ),
                if (showInvoice) ...[
                  Utils.verticalSpace(2.0),
                  CustomText(
                    text: invoiceExtra.trim().isEmpty
                        ? invoiceLabel
                        : '$invoiceLabel · $invoiceExtra',
                    color: iconGreyColor,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    maxLine: 1,
                    isTranslate: false,
                  ),
                ],
                Row(
                  children: [
                    CustomText(
                      text: "${Language.address} : ",
                      color: iconGreyColor,
                      maxLine: 1,
                    ),
                    Flexible(
                      child: CustomText(
                        text: [
                          if (addressModel.neighborhood.trim().isNotEmpty)
                            addressModel.neighborhood.trim(),
                          addressModel.address,
                        ].where((e) => e.isNotEmpty).join(', '),
                        color: iconGreyColor,
                        isTranslate: false,
                        maxLine: 2,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
