import 'package:flutter/material.dart';

import '../../../utils/language_string.dart';
import '../../../widgets/app_empty_state.dart';

class EmptyNotification extends StatelessWidget {
  const EmptyNotification({
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return AppEmptyState(
      icon: Icons.notifications_none_outlined,
      title: Language.emptyNotificationsTitle,
      subtitle: Language.emptyNotificationsHint,
    );
  }
}
