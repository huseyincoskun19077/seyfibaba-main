import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/primary_button.dart';
import '../../../widgets/rounded_app_bar.dart';
import '../authentication/controller/login/login_bloc.dart';
import '../home/widgets/home_theme.dart';
import 'component/buyer_personalization_form_fields.dart';
import 'controllers/buyer_personalization/buyer_personalization_cubit.dart';
import 'controllers/repository/profile_repository.dart';
import 'controllers/updated_info/updated_info_cubit.dart';
import 'model/buyer_personalization_constants.dart';
import 'model/buyer_personalization_data.dart';

class BuyerPersonalizationOnboardingScreen extends StatefulWidget {
  const BuyerPersonalizationOnboardingScreen({
    super.key,
    required this.initialData,
    this.isEditMode = false,
  });

  final BuyerPersonalizationData initialData;
  final bool isEditMode;

  @override
  State<BuyerPersonalizationOnboardingScreen> createState() =>
      _BuyerPersonalizationOnboardingScreenState();
}

class _BuyerPersonalizationOnboardingScreenState
    extends State<BuyerPersonalizationOnboardingScreen> {
  late final PageController _pageController;
  int _step = 0;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => BuyerPersonalizationCubit(
        profileRepository: context.read<ProfileRepository>(),
        loginBloc: context.read<LoginBloc>(),
        initial: widget.initialData,
      ),
      child: Scaffold(
        backgroundColor: HomeTheme.bg,
        appBar: widget.isEditMode
            ? RoundedAppBar(titleText: 'İşletme Bilgilerim')
            : null,
        body: SafeArea(
          child: BlocConsumer<BuyerPersonalizationCubit, BuyerPersonalizationState>(
            listener: (context, state) {
              if (state.errorMessage != null && state.errorMessage!.isNotEmpty) {
                Utils.errorSnackBar(context, state.errorMessage!);
              }
            },
            builder: (context, state) {
              final cubit = context.read<BuyerPersonalizationCubit>();

              if (widget.isEditMode) {
                return _buildEditBody(context, cubit, state);
              }

              return Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                    child: Row(
                      children: [
                        Expanded(
                          child: LinearProgressIndicator(
                            value: (_step + 1) / 3,
                            backgroundColor: Colors.grey.shade200,
                            color: HomeTheme.brandYellow,
                            minHeight: 5,
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        TextButton(
                          onPressed: state.isLoading
                              ? null
                              : () async {
                                  final ok = await cubit.skip();
                                  if (!context.mounted || !ok) return;
                                  Navigator.pop(context, false);
                                },
                          child: const Text(BuyerPersonalizationCopy.skip),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: PageView(
                      controller: _pageController,
                      physics: const NeverScrollableScrollPhysics(),
                      onPageChanged: (index) => setState(() => _step = index),
                      children: [
                        _stepPage(child: _shopStep(cubit, state), step: 0),
                        _stepPage(child: _typeStep(cubit, state), step: 1),
                        _stepPage(child: _statusStep(cubit, state), step: 2),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    child: state.isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : PrimaryButton(
                            text: _step == 2
                                ? BuyerPersonalizationCopy.save
                                : BuyerPersonalizationCopy.continueText,
                            onPressed: () async {
                              if (_step < 2) {
                                if (_step == 1 &&
                                    state.data.businessType.isEmpty) {
                                  Utils.errorSnackBar(
                                    context,
                                    'Lütfen çalışma alanınızı seçin',
                                  );
                                  return;
                                }
                                await _pageController.nextPage(
                                  duration: const Duration(milliseconds: 280),
                                  curve: Curves.easeOut,
                                );
                                return;
                              }

                              final ok = await cubit.submit();
                              if (!context.mounted || !ok) return;
                              await context
                                  .read<UserProfileInfoCubit>()
                                  .getUserProfileInfo();
                              Navigator.pop(context, true);
                            },
                          ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _buildEditBody(
    BuildContext context,
    BuyerPersonalizationCubit cubit,
    BuyerPersonalizationState state,
  ) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          BuyerPersonalizationFormFields(
            showIntro: false,
            shopName: state.data.shopName,
            businessType: state.data.businessType,
            businessTypeOther: state.data.businessTypeOther,
            businessStatus: state.data.businessStatus,
            onShopNameChanged: cubit.setShopName,
            onBusinessTypeChanged: cubit.setBusinessType,
            onBusinessTypeOtherChanged: cubit.setBusinessTypeOther,
            onBusinessStatusChanged: cubit.setBusinessStatus,
          ),
          const SizedBox(height: 24),
          PrimaryButton(
            text: BuyerPersonalizationCopy.save,
            onPressed: state.isLoading
                ? null
                : () async {
                    final ok = await cubit.submit();
                    if (!context.mounted || !ok) return;
                    await context.read<UserProfileInfoCubit>().getUserProfileInfo();
                    Utils.showSnackBar(
                      context,
                      BuyerPersonalizationCopy.saved,
                    );
                    Navigator.pop(context, true);
                  },
          ),
        ],
      ),
    );
  }

  Widget _stepPage({required Widget child, required int step}) {
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (step == 0) ...[
            Text(
              BuyerPersonalizationCopy.introTitle,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: blackColor,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              BuyerPersonalizationCopy.introBody,
              style: TextStyle(fontSize: 14, height: 1.45, color: Colors.grey.shade700),
            ),
            const SizedBox(height: 12),
          ],
          child,
        ],
      ),
    );
  }

  Widget _shopStep(BuyerPersonalizationCubit cubit, BuyerPersonalizationState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          BuyerPersonalizationCopy.shopNameTitle,
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(
          BuyerPersonalizationCopy.shopNameHelper,
          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
        ),
        const SizedBox(height: 14),
        TextFormField(
          initialValue: state.data.shopName,
          onChanged: cubit.setShopName,
          decoration: InputDecoration(
            hintText: BuyerPersonalizationCopy.shopNameHint,
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
      ],
    );
  }

  Widget _typeStep(BuyerPersonalizationCubit cubit, BuyerPersonalizationState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          BuyerPersonalizationCopy.businessTypeTitle,
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(
          BuyerPersonalizationCopy.whyWeAsk,
          style: TextStyle(fontSize: 13, height: 1.4, color: Colors.grey.shade700),
        ),
        const SizedBox(height: 14),
        ...BuyerBusinessType.options.entries.map(
          (entry) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: BorderSide(
                  color: state.data.businessType == entry.key
                      ? HomeTheme.brandYellow
                      : Colors.grey.shade300,
                ),
              ),
              tileColor: Colors.white,
              title: Text(entry.value),
              trailing: state.data.businessType == entry.key
                  ? const Icon(Icons.check_circle, color: blackColor)
                  : null,
              onTap: () => cubit.setBusinessType(entry.key),
            ),
          ),
        ),
        if (state.data.businessType == BuyerBusinessType.other) ...[
          const SizedBox(height: 8),
          TextFormField(
            initialValue: state.data.businessTypeOther,
            onChanged: cubit.setBusinessTypeOther,
            decoration: InputDecoration(
              hintText: BuyerPersonalizationCopy.otherHint,
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ],
    );
  }

  Widget _statusStep(BuyerPersonalizationCubit cubit, BuyerPersonalizationState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          BuyerPersonalizationCopy.businessStatusTitle,
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(
          BuyerPersonalizationCopy.businessStatusHelper,
          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
        ),
        const SizedBox(height: 14),
        ...BuyerBusinessStatus.options.entries.map(
          (entry) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: BorderSide(
                  color: state.data.businessStatus == entry.key
                      ? HomeTheme.brandYellow
                      : Colors.grey.shade300,
                ),
              ),
              tileColor: Colors.white,
              title: Text(entry.value),
              trailing: state.data.businessStatus == entry.key
                  ? const Icon(Icons.check_circle, color: blackColor)
                  : null,
              onTap: () => cubit.setBusinessStatus(entry.key),
            ),
          ),
        ),
      ],
    );
  }
}
