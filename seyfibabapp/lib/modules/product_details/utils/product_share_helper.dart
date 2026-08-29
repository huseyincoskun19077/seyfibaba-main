import 'package:share_plus/share_plus.dart';

import '../../../core/remote_urls.dart';

Future<void> shareProduct({
  required String name,
  required String slug,
}) async {
  final url = RemoteUrls.productShareUrl(slug);
  await Share.share('$name\n$url', subject: name);
}
