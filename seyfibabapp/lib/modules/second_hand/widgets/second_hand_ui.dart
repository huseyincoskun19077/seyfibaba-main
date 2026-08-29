import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../home/widgets/home_theme.dart';

/// İkinci el modülü — sade, modern arayüz bileşenleri
class ShTheme {
  ShTheme._();

  static const bg = Color(0xFFF4F4F6);
  static const card = Colors.white;
  static const primary = Color(0xFFFFBB38);
  static const primaryDark = Color(0xFFE5A820);
  static const dark = Color(0xFF1C1C1E);
  static const muted = Color(0xFF8E8E93);
  static const border = Color(0xFFE5E5EA);
  static const success = Color(0xFF22C55E);
  static const warning = Color(0xFFF59E0B);
  static const danger = Color(0xFFEF4444);

  static const radius = 14.0;
  static const radiusSm = 10.0;

  static BoxDecoration cardDecoration({Color? color}) => BoxDecoration(
        color: color ?? card,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: border.withValues(alpha: 0.7)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      );
}

class ShAppBar extends StatelessWidget implements PreferredSizeWidget {
  const ShAppBar({
    super.key,
    required this.title,
    this.subtitle,
    this.actions,
    this.leading,
    this.showLogo = false,
  });

  final String title;
  final String? subtitle;
  final List<Widget>? actions;
  final Widget? leading;
  final bool showLogo;

  @override
  Size get preferredSize =>
      Size.fromHeight(showLogo ? 64 : (subtitle != null ? 72 : kToolbarHeight));

  @override
  Widget build(BuildContext context) {
    final Widget titleWidget;
    if (showLogo) {
      titleWidget = RichText(
        text: const TextSpan(
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.3,
            height: 1.1,
          ),
          children: [
            TextSpan(
              text: 'Seyfibaba',
              style: TextStyle(color: HomeTheme.textDark),
            ),
            TextSpan(
              text: '.com',
              style: TextStyle(color: HomeTheme.brandYellow),
            ),
          ],
        ),
      );
    } else if (subtitle != null) {
      titleWidget = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: ShTheme.dark,
            ),
          ),
          Text(
            subtitle!,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w400,
              color: ShTheme.muted,
            ),
          ),
        ],
      );
    } else {
      titleWidget = Text(
        title,
        style: const TextStyle(
          fontSize: 17,
          fontWeight: FontWeight.w700,
          color: ShTheme.dark,
        ),
      );
    }

    return AppBar(
      elevation: 0,
      scrolledUnderElevation: 0,
      backgroundColor: ShTheme.card,
      foregroundColor: ShTheme.dark,
      centerTitle: false,
      leading: leading,
      title: titleWidget,
      actions: actions,
    );
  }
}

/// İlan yükleme / yayına gönderme şartları diyaloğu. true = kabul edildi.
Future<bool> showSecondHandTermsDialog(
  BuildContext context, {
  required String title,
  required String content,
  String acceptLabel = 'Şartları okudum ve kabul ediyorum',
  String confirmLabel = 'Kabul Et ve Devam',
}) async {
  var accepted = false;
  final result = await showDialog<bool>(
    context: context,
    barrierDismissible: false,
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setLocal) {
          return AlertDialog(
            title: Text(title),
            content: SizedBox(
              width: double.maxFinite,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ConstrainedBox(
                    constraints: BoxConstraints(
                      maxHeight: MediaQuery.of(ctx).size.height * 0.35,
                    ),
                    child: SingleChildScrollView(
                      child: content.contains('<')
                          ? Html(
                              data: content,
                              style: {
                                'body': Style(
                                  fontSize: FontSize(13),
                                  color: ShTheme.dark,
                                ),
                              },
                            )
                          : Text(
                              content.isNotEmpty
                                  ? content
                                  : _defaultSecondHandListingTerms,
                              style: const TextStyle(
                                fontSize: 13,
                                height: 1.45,
                                color: ShTheme.dark,
                              ),
                            ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 24,
                        height: 24,
                        child: Checkbox(
                          value: accepted,
                          activeColor: ShTheme.primary,
                          onChanged: (v) =>
                              setLocal(() => accepted = v ?? false),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: GestureDetector(
                          onTap: () =>
                              setLocal(() => accepted = !accepted),
                          child: Text(
                            acceptLabel,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: ShTheme.dark,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx, false),
                child: const Text('Vazgeç'),
              ),
              TextButton(
                onPressed: accepted
                    ? () => Navigator.pop(ctx, true)
                    : null,
                child: Text(confirmLabel),
              ),
            ],
          );
        },
      );
    },
  );
  return result == true;
}

class ShMarketplaceNotice extends StatelessWidget {
  const ShMarketplaceNotice({
    super.key,
    this.compact = false,
  });

  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.symmetric(
        horizontal: 12,
        vertical: compact ? 8 : 10,
      ),
      color: const Color(0xFFFFF6E5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.info_outline_rounded,
            size: 16,
            color: Color(0xFFB45309),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              compact
                  ? 'Seyfibaba aracıdır. Ödeme, teslimat ve yazışmadan sorumlu değiliz.'
                  : 'Alıcı ve satıcı kendi aralarında anlaşır. Telefon, IBAN veya dosya paylaşılabilir. Ödeme ve teslimat taraflara aittir; Seyfibaba sorumlu değildir.',
              style: const TextStyle(
                fontSize: 11,
                height: 1.35,
                fontWeight: FontWeight.w600,
                color: Color(0xFF92400E),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

const _defaultSecondHandListingTerms = '''
İkinci El İlan Yükleme Şartları

1. Yüklediğiniz ilan bilgileri ve fotoğraflar gerçeğe uygun olmalıdır.
2. Çalıntı, yasaklı veya yanıltıcı ürünler yasaktır.
3. İlanınız yayına alınmadan önce Seyfibaba tarafından incelenebilir.
4. Seyfibaba yalnızca aracı platformdur. Alıcı ve satıcı kendi aralarında mesajlaşır; telefon, IBAN veya dosya paylaşabilir. Ödeme, teslimat ve anlaşmadan Seyfibaba sorumlu değildir.
5. Kurallara aykırı ilanlar kaldırılabilir; hesabınız kısıtlanabilir.

Bu şartları kabul ederek ilan yükleme ve yayına gönderme işlemini sürdürmüş sayılırsınız.
''';

class ShSearchBar extends StatelessWidget {
  const ShSearchBar({
    super.key,
    required this.controller,
    required this.hint,
    required this.onSearch,
    required this.onClear,
    this.onChanged,
  });

  final TextEditingController controller;
  final String hint;
  final VoidCallback onSearch;
  final VoidCallback onClear;
  final ValueChanged<String>? onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: ShTheme.cardDecoration(),
      child: TextField(
        controller: controller,
        textInputAction: TextInputAction.search,
        onSubmitted: (_) => onSearch(),
        onChanged: onChanged,
        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: ShTheme.muted, fontSize: 15),
          prefixIcon: const Icon(Icons.search_rounded, color: ShTheme.muted),
          suffixIcon: ListenableBuilder(
            listenable: controller,
            builder: (context, _) {
              if (controller.text.isEmpty) return const SizedBox.shrink();
              return IconButton(
                icon: const Icon(Icons.close_rounded, size: 20),
                onPressed: onClear,
              );
            },
          ),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 14),
        ),
      ),
    );
  }
}

class ShFilterChip extends StatelessWidget {
  const ShFilterChip({
    super.key,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? ShTheme.primary : ShTheme.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: selected ? ShTheme.primary : ShTheme.border,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: ShTheme.dark,
          ),
        ),
      ),
    );
  }
}

class ShPrimaryButton extends StatelessWidget {
  const ShPrimaryButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.loading = false,
    this.icon,
    this.expand = true,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? icon;
  final bool expand;

  @override
  Widget build(BuildContext context) {
    final child = loading
        ? const SizedBox(
            height: 22,
            width: 22,
            child: CircularProgressIndicator(
              strokeWidth: 2.2,
              color: ShTheme.dark,
            ),
          )
        : Row(
            mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (icon != null) ...[
                Icon(icon, size: 20, color: ShTheme.dark),
                const SizedBox(width: 8),
              ],
              Text(
                label,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: ShTheme.dark,
                ),
              ),
            ],
          );

    final button = Material(
      color: onPressed == null ? ShTheme.primary.withValues(alpha: 0.5) : ShTheme.primary,
      borderRadius: BorderRadius.circular(ShTheme.radiusSm),
      child: InkWell(
        onTap: loading ? null : onPressed,
        borderRadius: BorderRadius.circular(ShTheme.radiusSm),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          child: Center(child: child),
        ),
      ),
    );

    // Dikeyde şişmeyi engelle (Scaffold bottomNavigationBar max yükseklik verebiliyor)
    if (!expand) return button;
    return Align(
      alignment: Alignment.center,
      heightFactor: 1,
      child: SizedBox(width: double.infinity, child: button),
    );
  }
}

class ShOutlineButton extends StatelessWidget {
  const ShOutlineButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.small = false,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool small;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton(
      onPressed: onPressed,
      style: OutlinedButton.styleFrom(
        foregroundColor: ShTheme.dark,
        side: const BorderSide(color: ShTheme.border),
        padding: EdgeInsets.symmetric(
          horizontal: small ? 12 : 16,
          vertical: small ? 8 : 12,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(ShTheme.radiusSm),
        ),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: small ? 12 : 14,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class ShTextField extends StatelessWidget {
  const ShTextField({
    super.key,
    required this.controller,
    required this.label,
    this.hint,
    this.maxLines = 1,
    this.keyboardType,
    this.required = false,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final int maxLines;
  final TextInputType? keyboardType;
  final bool required;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          required ? '$label *' : label,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: ShTheme.dark,
          ),
        ),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          maxLines: maxLines,
          keyboardType: keyboardType,
          style: const TextStyle(fontSize: 15),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: ShTheme.muted),
            filled: true,
            fillColor: ShTheme.card,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 14,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(ShTheme.radiusSm),
              borderSide: const BorderSide(color: ShTheme.border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(ShTheme.radiusSm),
              borderSide: const BorderSide(color: ShTheme.border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(ShTheme.radiusSm),
              borderSide: const BorderSide(color: ShTheme.primary, width: 1.5),
            ),
          ),
        ),
      ],
    );
  }
}

class ShDropdownField<T> extends StatelessWidget {
  const ShDropdownField({
    super.key,
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
    this.hint = 'Seçin',
    this.enabled = true,
  });

  final String label;
  final T? value;
  final List<DropdownMenuItem<T>> items;
  final ValueChanged<T?> onChanged;
  final String hint;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: ShTheme.dark,
          ),
        ),
        const SizedBox(height: 6),
        DropdownButtonFormField<T>(
          key: ValueKey('$label-$value'),
          initialValue: value,
          isExpanded: true,
          items: items,
          onChanged: enabled ? onChanged : null,
          decoration: InputDecoration(
            hintText: hint,
            filled: true,
            fillColor: ShTheme.card,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(ShTheme.radiusSm),
              borderSide: const BorderSide(color: ShTheme.border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(ShTheme.radiusSm),
              borderSide: const BorderSide(color: ShTheme.border),
            ),
          ),
        ),
      ],
    );
  }
}

class ShSectionTitle extends StatelessWidget {
  const ShSectionTitle({super.key, required this.title, this.subtitle});

  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: ShTheme.dark,
            ),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(
              subtitle!,
              style: const TextStyle(fontSize: 13, color: ShTheme.muted, height: 1.4),
            ),
          ],
        ],
      ),
    );
  }
}

class ShEmptyState extends StatelessWidget {
  const ShEmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.action,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: ShTheme.primary.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 34, color: ShTheme.primaryDark),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w700,
                color: ShTheme.dark,
              ),
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 8),
              Text(
                subtitle!,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 14, color: ShTheme.muted, height: 1.4),
              ),
            ],
            if (action != null) ...[
              const SizedBox(height: 20),
              action!,
            ],
          ],
        ),
      ),
    );
  }
}

class ShStatusBadge extends StatelessWidget {
  const ShStatusBadge({super.key, required this.label, required this.status});

  final String label;
  final String status;

  Color get _color {
    switch (status) {
      case 'active':
      case 'approved':
        return ShTheme.success;
      case 'pending':
        return ShTheme.warning;
      case 'rejected':
      case 'sold':
        return ShTheme.danger;
      default:
        return ShTheme.muted;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: _color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: _color,
        ),
      ),
    );
  }
}

class ShInfoTile extends StatelessWidget {
  const ShInfoTile({
    super.key,
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: ShTheme.cardDecoration(),
      child: Row(
        children: [
          Icon(icon, size: 20, color: ShTheme.muted),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(fontSize: 11, color: ShTheme.muted)),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: ShTheme.dark,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class ShUploadTile extends StatelessWidget {
  const ShUploadTile({
    super.key,
    required this.label,
    required this.onPick,
    this.selected = false,
    this.icon = Icons.upload_file_rounded,
  });

  final String label;
  final VoidCallback onPick;
  final bool selected;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? ShTheme.primary.withValues(alpha: 0.1) : ShTheme.card,
      borderRadius: BorderRadius.circular(ShTheme.radiusSm),
      child: InkWell(
        onTap: onPick,
        borderRadius: BorderRadius.circular(ShTheme.radiusSm),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(ShTheme.radiusSm),
            border: Border.all(
              color: selected ? ShTheme.primary : ShTheme.border,
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: selected
                      ? ShTheme.primary.withValues(alpha: 0.2)
                      : ShTheme.bg,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  selected ? Icons.check_circle_rounded : icon,
                  color: selected ? ShTheme.success : ShTheme.muted,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  selected ? '$label yüklendi' : label,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: selected ? ShTheme.dark : ShTheme.muted,
                  ),
                ),
              ),
              Icon(Icons.chevron_right_rounded, color: ShTheme.muted.withValues(alpha: 0.6)),
            ],
          ),
        ),
      ),
    );
  }
}

class ShLoading extends StatelessWidget {
  const ShLoading({super.key});

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: CircularProgressIndicator(
        color: ShTheme.primary,
        strokeWidth: 2.5,
      ),
    );
  }
}

class ShVerifiedBadge extends StatelessWidget {
  const ShVerifiedBadge({
    super.key,
    this.label = 'Doğrulanmış satıcı',
    this.compact = false,
  });

  final String label;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 5 : 8,
        vertical: compact ? 2 : 4,
      ),
      decoration: BoxDecoration(
        color: const Color(0xFF166534),
        borderRadius: BorderRadius.circular(compact ? 4 : 6),
      ),
      child: Row(
        children: [
          Icon(
            Icons.verified_rounded,
            size: compact ? 11 : 14,
            color: Colors.white,
          ),
          SizedBox(width: compact ? 3 : 4),
          Expanded(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: compact ? 9 : 11,
                fontWeight: FontWeight.w700,
                color: Colors.white,
                height: 1.1,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class ShSegmentBar extends StatelessWidget {
  const ShSegmentBar({
    super.key,
    required this.labels,
    required this.selectedIndex,
    required this.onChanged,
  });

  final List<String> labels;
  final int selectedIndex;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: ShTheme.border.withValues(alpha: 0.45),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: List.generate(labels.length, (i) {
          final selected = selectedIndex == i;
          return Expanded(
            child: GestureDetector(
              onTap: () => onChanged(i),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: BoxDecoration(
                  color: selected ? ShTheme.card : Colors.transparent,
                  borderRadius: BorderRadius.circular(9),
                  boxShadow: selected
                      ? [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.06),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ]
                      : null,
                ),
                child: Text(
                  labels[i],
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                    color: selected ? ShTheme.dark : ShTheme.muted,
                  ),
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}

class ShBottomNavItem {
  const ShBottomNavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
  });

  final IconData icon;
  final IconData activeIcon;
  final String label;
}

/// Ana uygulama / CRM ile aynı dil: yüzen, yuvarlak menü çubuğu.
class ShBottomNav extends StatelessWidget {
  const ShBottomNav({
    super.key,
    required this.items,
    required this.currentIndex,
    required this.onTap,
  });

  final List<ShBottomNavItem> items;
  final int currentIndex;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      minimum: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 0, 14, 6),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: ShTheme.card,
            borderRadius: BorderRadius.circular(28),
            border: Border.all(
              color: ShTheme.border.withValues(alpha: 0.85),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.08),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: SizedBox(
              height: 64,
              child: Row(
                children: List.generate(items.length, (i) {
                  final item = items[i];
                  final selected = currentIndex == i;
                  return Expanded(
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => onTap(i),
                        borderRadius: BorderRadius.circular(22),
                        splashColor: ShTheme.primary.withValues(alpha: 0.18),
                        highlightColor: ShTheme.primary.withValues(alpha: 0.08),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 2,
                            vertical: 6,
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              AnimatedContainer(
                                duration: const Duration(milliseconds: 180),
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: selected
                                      ? ShTheme.primary.withValues(alpha: 0.22)
                                      : Colors.transparent,
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Icon(
                                  selected ? item.activeIcon : item.icon,
                                  size: 22,
                                  color: selected
                                      ? ShTheme.dark
                                      : ShTheme.muted,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                item.label,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: selected
                                      ? FontWeight.w800
                                      : FontWeight.w600,
                                  color: selected
                                      ? ShTheme.dark
                                      : ShTheme.muted,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
