import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../utils/utils.dart';
import '../../../widgets/app_brand_logo.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../../home/widgets/home_theme.dart';
import '../../profile/controllers/updated_info/updated_info_cubit.dart';
import '../../salon_crm/services/salon_crm_service.dart';

/// Giriş yapılmışsa kullanıcı adı; kuaför/salon adı varsa altında gösterir.
class UserSalonHeader extends StatefulWidget {
  const UserSalonHeader({
    super.key,
    this.padding = const EdgeInsets.fromLTRB(20, 12, 20, 14),
    this.showBrand = true,
  });

  final EdgeInsets padding;
  final bool showBrand;

  @override
  State<UserSalonHeader> createState() => _UserSalonHeaderState();
}

class _UserSalonHeaderState extends State<UserSalonHeader> {
  final _service = SalonCrmService();
  String? _salonName;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadSalonName());
  }

  Future<void> _loadSalonName() async {
    if (!Utils.isLoggedIn(context)) return;
    final token = context.read<LoginBloc>().userInfo?.accessToken ?? '';
    if (token.isEmpty) return;
    try {
      final summary = await _service.patronSalonSummary(token);
      if (!mounted) return;
      final salon = summary['salon'];
      setState(() {
        _salonName = salon is Map ? '${salon['name'] ?? ''}' : null;
        if (_salonName != null && _salonName!.trim().isEmpty) {
          _salonName = null;
        }
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    context.watch<LoginBloc>();
    context.watch<UserProfileInfoCubit>();

    final isLoggedIn = Utils.isLoggedIn(context);
    final loginBloc = context.read<LoginBloc>();
    final profileCubit = context.read<UserProfileInfoCubit>();

    final userName = !isLoggedIn
        ? null
        : (profileCubit.updatedInfo?.updateUserInfo.name ??
            loginBloc.userInfo?.user.name);
    final hasName = userName?.trim().isNotEmpty ?? false;
    final hasSalon = _salonName?.trim().isNotEmpty ?? false;

    return DecoratedBox(
      decoration: const BoxDecoration(
        color: HomeTheme.header,
        border: Border(
          bottom: BorderSide(color: HomeTheme.headerBorder, width: 1),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: widget.padding,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (widget.showBrand)
                      const AppBrandLogo(height: 36, width: 140),
                    if (isLoggedIn && hasName) ...[
                      if (widget.showBrand) const SizedBox(height: 8),
                      Text(
                        userName!.trim(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: HomeTheme.textDark,
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if (hasSalon) ...[
                        const SizedBox(height: 2),
                        Text(
                          _salonName!.trim(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: HomeTheme.textMuted,
                            fontSize: 12.5,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ],
                  ],
                ),
              ),
              if (isLoggedIn)
                Container(
                  width: 42,
                  height: 42,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: HomeTheme.brandYellow.withValues(alpha: 0.35),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Text(
                    _initials(userName),
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      color: HomeTheme.textDark,
                      fontSize: 14,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  String _initials(String? name) {
    final parts = (name ?? '').trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return 'S';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }
}
