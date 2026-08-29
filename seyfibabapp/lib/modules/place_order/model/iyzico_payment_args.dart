class IyzicoPaymentArgs {
  const IyzicoPaymentArgs({
    required this.checkoutUrl,
    required this.orderId,
  });

  final String checkoutUrl;
  final String orderId;
}
