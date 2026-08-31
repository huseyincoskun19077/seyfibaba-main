import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

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
    this.isSheet = false,
  });

  final BuyerPersonalizationData initialData;
  final bool isEditMode;
  final bool isSheet;

  static Future<bool?> showPrompt(
    BuildContext context,
    BuyerPersonalizationData initialData,
  ) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.42),
      isDismissible: false,
      enableDrag: false,
      builder: (_) => BuyerPersonalizationOnboardingScreen(
        initialData: initialData,
        isSheet: true,
      ),
    );
  }

  @override
  State<BuyerPersonalizationOnboardingScreen> createState() =>
      _BuyerPersonalizationOnboardingScreenState();
}

class _BuyerPersonalizationOnboardingScreenState
    extends State<BuyerPersonalizationOnboardingScreen> {
  int _step = 0;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => BuyerPersonalizationCubit(
        profileRepository: context.read<ProfileRepository>(),
        loginBloc: context.read<LoginBloc>(),
        initial: widget.initialData,
      ),
      child: widget.isSheet
          ? _SheetShell(child: _buildContent())
          : Scaffold(
              backgroundColor: HomeTheme.bg,
              appBar: widget.isEditMode
                  ? RoundedAppBar(titleText: 'İşletme Bilgilerim')
                  : null,
              body: SafeArea(child: _buildContent()),
            ),
    );
  }

  Widget _buildContent() {
    return BlocConsumer<BuyerPersonalizationCubit, BuyerPersonalizationState>(
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

        return _buildOnboardingBody(context, cubit, state);
      },
    );
  }

  Widget _buildOnboardingBody(
    BuildContext context,
    BuyerPersonalizationCubit cubit,
    BuyerPersonalizationState state,
  ) {
    return Column(
      mainAxisSize: widget.isSheet ? MainAxisSize.min : MainAxisSize.max,
      children: [
        if (widget.isSheet) _sheetHeader(context, cubit, state),
        if (!widget.isSheet)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: _legacyProgressRow(context, cubit, state),
          ),
        if (widget.isSheet)
          Flexible(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 8),
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 260),
                switchInCurve: Curves.easeOutCubic,
                switchOutCurve: Curves.easeInCubic,
                child: KeyedSubtree(
                  key: ValueKey<int>(_step),
                  child: _stepContent(cubit, state),
                ),
              ),
            ),
          )
        else
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
              child: _stepContent(cubit, state),
            ),
          ),
        Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            8,
            20,
            widget.isSheet ? 20 + MediaQuery.paddingOf(context).bottom : 16,
          ),
          child: state.isLoading
              ? const Center(child: CircularProgressIndicator())
              : Column(
                  children: [
                    PrimaryButton(
                      text: _step == 2
                          ? BuyerPersonalizationCopy.save
                          : BuyerPersonalizationCopy.continueText,
                      onPressed: () => _onPrimaryAction(context, cubit, state),
                    ),
                    if (widget.isSheet && _step > 0) ...[
                      const SizedBox(height: 8),
                      TextButton(
                        onPressed: state.isLoading
                            ? null
                            : () => setState(() => _step -= 1),
                        child: const Text(
                          'Geri',
                          style: TextStyle(
                            color: HomeTheme.textMuted,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
        ),
      ],
    );
  }

  Future<void> _onPrimaryAction(
    BuildContext context,
    BuyerPersonalizationCubit cubit,
    BuyerPersonalizationState state,
  ) async {
    if (_step < 2) {
      if (_step == 1 && state.data.businessType.isEmpty) {
        Utils.errorSnackBar(context, 'Lütfen çalışma alanınızı seçin');
        return;
      }
      setState(() => _step += 1);
      return;
    }

    final ok = await cubit.submit();
    if (!context.mounted || !ok) return;
    await context.read<UserProfileInfoCubit>().getUserProfileInfo();
    if (!context.mounted) return;
    Navigator.pop(context, true);
  }

  Widget _sheetHeader(
    BuildContext context,
    BuyerPersonalizationCubit cubit,
    BuyerPersonalizationState state,
  ) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 12, 0),
      child: Column(
        children: [
          Container(
            width: 42,
            height: 4,
            decoration: BoxDecoration(
              color: HomeTheme.border,
              borderRadius: BorderRadius.circular(99),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      HomeTheme.brandYellow.withValues(alpha: 0.95),
                      HomeTheme.brandYellow.withValues(alpha: 0.55),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.storefront_rounded,
                  color: HomeTheme.textDark,
                  size: 26,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _stepTitle,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: HomeTheme.textDark,
                        letterSpacing: -0.2,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _stepSubtitle,
                      style: const TextStyle(
                        fontSize: 13,
                        height: 1.35,
                        color: HomeTheme.textMuted,
                      ),
                    ),
                  ],
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
                child: const Text(
                  BuyerPersonalizationCopy.skip,
                  style: TextStyle(
                    color: HomeTheme.textMuted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _stepIndicator(),
          const SizedBox(height: 8),
        ],
      ),
    );
  }

  String get _stepTitle {
    switch (_step) {
      case 0:
        return BuyerPersonalizationCopy.introTitle;
      case 1:
        return BuyerPersonalizationCopy.businessTypeTitle;
      default:
        return BuyerPersonalizationCopy.businessStatusTitle;
    }
  }

  String get _stepSubtitle {
    switch (_step) {
      case 0:
        return BuyerPersonalizationCopy.introBody;
      case 1:
        return BuyerPersonalizationCopy.businessTypeHelper;
      default:
        return BuyerPersonalizationCopy.businessStatusHelper;
    }
  }

  Widget _stepIndicator() {
    return Row(
      children: List.generate(3, (index) {
        final active = index == _step;
        final done = index < _step;
        return Expanded(
          child: Container(
            height: 5,
            margin: EdgeInsets.only(right: index == 2 ? 0 : 6),
            decoration: BoxDecoration(
              color: active || done
                  ? HomeTheme.brandYellow
                  : HomeTheme.border,
              borderRadius: BorderRadius.circular(99),
            ),
          ),
        );
      }),
    );
  }

  Widget _legacyProgressRow(
    BuildContext context,
    BuyerPersonalizationCubit cubit,
    BuyerPersonalizationState state,
  ) {
    return Row(
      children: [
        Expanded(child: _stepIndicator()),
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
    );
  }

  Widget _stepContent(
    BuyerPersonalizationCubit cubit,
    BuyerPersonalizationState state,
  ) {
    switch (_step) {
      case 0:
        return _shopStep(cubit, state);
      case 1:
        return _typeStep(cubit, state);
      default:
        return _statusStep(cubit, state);
    }
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
                    await context
                        .read<UserProfileInfoCubit>()
                        .getUserProfileInfo();
                    if (!context.mounted) return;
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

  Widget _shopStep(BuyerPersonalizationCubit cubit, BuyerPersonalizationState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (!widget.isSheet) ...[
          Text(
            BuyerPersonalizationCopy.introTitle,
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: HomeTheme.textDark,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            BuyerPersonalizationCopy.introBody,
            style: const TextStyle(
              fontSize: 14,
              height: 1.45,
              color: HomeTheme.textMuted,
            ),
          ),
          const SizedBox(height: 16),
        ],
        _infoChip(BuyerPersonalizationCopy.whyWeAsk),
        const SizedBox(height: 14),
        Text(
          BuyerPersonalizationCopy.shopNameTitle,
          style: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: HomeTheme.textDark,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          BuyerPersonalizationCopy.shopNameHelper,
          style: const TextStyle(fontSize: 12, color: HomeTheme.textMuted),
        ),
        const SizedBox(height: 12),
        TextFormField(
          initialValue: state.data.shopName,
          onChanged: cubit.setShopName,
          decoration: _inputDecoration(BuyerPersonalizationCopy.shopNameHint),
        ),
      ],
    );
  }

  Widget _typeStep(BuyerPersonalizationCubit cubit, BuyerPersonalizationState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (!widget.isSheet) ...[
          Text(
            BuyerPersonalizationCopy.businessTypeTitle,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          Text(
            BuyerPersonalizationCopy.whyWeAsk,
            style: const TextStyle(fontSize: 13, height: 1.4, color: HomeTheme.textMuted),
          ),
          const SizedBox(height: 14),
        ],
        ...BuyerBusinessType.options.entries.map(
          (entry) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: _selectCard(
              label: entry.value,
              icon: _businessTypeIcon(entry.key),
              selected: state.data.businessType == entry.key,
              onTap: () => cubit.setBusinessType(entry.key),
            ),
          ),
        ),
        if (state.data.businessType == BuyerBusinessType.other) ...[
          const SizedBox(height: 4),
          TextFormField(
            initialValue: state.data.businessTypeOther,
            onChanged: cubit.setBusinessTypeOther,
            decoration: _inputDecoration(BuyerPersonalizationCopy.otherHint),
          ),
        ],
      ],
    );
  }

  Widget _statusStep(BuyerPersonalizationCubit cubit, BuyerPersonalizationState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (!widget.isSheet) ...[
          Text(
            BuyerPersonalizationCopy.businessStatusTitle,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          Text(
            BuyerPersonalizationCopy.businessStatusHelper,
            style: const TextStyle(fontSize: 13, color: HomeTheme.textMuted),
          ),
          const SizedBox(height: 14),
        ],
        ...BuyerBusinessStatus.options.entries.map(
          (entry) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: _selectCard(
              label: entry.value,
              icon: _statusIcon(entry.key),
              selected: state.data.businessStatus == entry.key,
              onTap: () => cubit.setBusinessStatus(entry.key),
            ),
          ),
        ),
      ],
    );
  }

  Widget _selectCard({
    required String label,
    required IconData icon,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: selected
                ? HomeTheme.brandYellow.withValues(alpha: 0.18)
                : HomeTheme.header,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: selected ? HomeTheme.brandYellow : HomeTheme.border,
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: selected
                      ? HomeTheme.brandYellow.withValues(alpha: 0.35)
                      : HomeTheme.bg,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, size: 18, color: HomeTheme.textDark),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                    color: HomeTheme.textDark,
                  ),
                ),
              ),
              if (selected)
                const Icon(
                  Icons.check_circle_rounded,
                  color: HomeTheme.textDark,
                  size: 20,
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _infoChip(String text) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: HomeTheme.brandYellow.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: HomeTheme.brandYellow.withValues(alpha: 0.35),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.info_outline_rounded, size: 16, color: HomeTheme.textDark),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                fontSize: 12,
                height: 1.4,
                color: HomeTheme.textDark,
              ),
            ),
          ),
        ],
      ),
    );
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      filled: true,
      fillColor: HomeTheme.header,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: HomeTheme.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(
          color: HomeTheme.brandYellow.withValues(alpha: 0.9),
          width: 1.5,
        ),
      ),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
    );
  }

  IconData _businessTypeIcon(String key) {
    switch (key) {
      case BuyerBusinessType.femaleHairdresser:
        return Icons.face_retouching_natural_outlined;
      case BuyerBusinessType.maleHairdresser:
        return Icons.content_cut_rounded;
      case BuyerBusinessType.barber:
        return Icons.storefront_outlined;
      case BuyerBusinessType.beautySalon:
        return Icons.spa_outlined;
      default:
        return Icons.more_horiz_rounded;
    }
  }

  IconData _statusIcon(String key) {
    switch (key) {
      case BuyerBusinessStatus.ownShop:
        return Icons.store_mall_directory_outlined;
      case BuyerBusinessStatus.openingSoon:
        return Icons.event_available_outlined;
      case BuyerBusinessStatus.employedInSalon:
        return Icons.groups_outlined;
      default:
        return Icons.lightbulb_outline_rounded;
    }
  }
}

class _SheetShell extends StatelessWidget {
  const _SheetShell({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final maxHeight = MediaQuery.sizeOf(context).height * 0.78;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: Align(
        alignment: Alignment.bottomCenter,
        child: Container(
          constraints: BoxConstraints(maxHeight: maxHeight),
          width: double.infinity,
          decoration: BoxDecoration(
            color: HomeTheme.bg,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.14),
                blurRadius: 28,
                offset: const Offset(0, -6),
              ),
            ],
          ),
          child: child,
        ),
      ),
    );
  }
}
