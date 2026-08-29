import '../../../state_packages_names.dart';
import '../../../utils/utils.dart';
import '../../animated_splash_screen/controller/currency/currency_cubit.dart';
import '../../animated_splash_screen/controller/currency/currency_state_model.dart';
import '../../home/widgets/home_theme.dart';

class PriceCardWidget extends StatelessWidget {
  const PriceCardWidget({
    super.key,
    required this.price,
    required this.offerPrice,
    this.textSize = 16.0,
    this.saleUnitQty = 1,
  });

  final String price;
  final double textSize;
  final String offerPrice;
  final int saleUnitQty;

  int get _units => saleUnitQty > 1 ? saleUnitQty : 1;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CurrencyCubit, CurrencyStateModel>(
      builder: (context, state) {
        final offerValue = double.tryParse(offerPrice) ?? 0;
        final regularValue = double.tryParse(price) ?? 0;
        final hasOffer = offerValue > 0 &&
            (regularValue <= 0 || offerValue < regularValue);
        final packStr = hasOffer ? offerPrice : price;
        final pack = double.tryParse(packStr) ?? 0;
        final unit = _units > 0 ? pack / _units : pack;
        final unitLabel = Utils.formatPrice(unit.toString(), context);
        final packLabel = Utils.formatPrice(packStr, context);
        final compact = textSize < 18;
        const saleColor = Color(0xFFE11D48);
        const regularPriceColor = Color(0xFF0F766E);

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                packLabel,
                maxLines: 1,
                style: TextStyle(
                  color: hasOffer ? saleColor : regularPriceColor,
                  fontSize: compact ? 13 : textSize + 2,
                  fontWeight: FontWeight.w800,
                  height: 1.1,
                  letterSpacing: -0.3,
                ),
              ),
            ),
            if (hasOffer)
              Text(
                Utils.formatPrice(price, context),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: HomeTheme.textMuted,
                  fontSize: compact ? 10 : 12,
                  decoration: TextDecoration.lineThrough,
                  height: 1.15,
                ),
              ),
            if (hasOffer && regularValue > 0) ...[
              const SizedBox(height: 1),
              Builder(
                builder: (context) {
                  final pct =
                      (((regularValue - offerValue) / regularValue) * 100)
                          .round();
                  if (pct < 1) return const SizedBox.shrink();
                  return Text(
                    '%$pct indirim',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: saleColor,
                      fontSize: compact ? 9 : 11,
                      fontWeight: FontWeight.w700,
                      height: 1.1,
                    ),
                  );
                },
              ),
            ],
            if (_units > 1) ...[
              const SizedBox(height: 2),
              Text(
                'Birim $unitLabel  ·  x$_units adet',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: HomeTheme.textMuted,
                  fontSize: compact ? 9 : 11,
                  fontWeight: FontWeight.w600,
                  height: 1.15,
                ),
              ),
            ],
          ],
        );
      },
    );
  }
}

class ProductPackBadge extends StatelessWidget {
  const ProductPackBadge({super.key, required this.qty});

  final int qty;

  @override
  Widget build(BuildContext context) {
    if (qty <= 1) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: HomeTheme.brandYellow,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: HomeTheme.textDark, width: 1),
      ),
      child: Text(
        'x$qty adet',
        style: const TextStyle(
          color: HomeTheme.textDark,
          fontSize: 10,
          fontWeight: FontWeight.w800,
          height: 1.1,
        ),
      ),
    );
  }
}
