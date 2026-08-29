import 'package:flutter/material.dart';

import '../../core/router_name.dart';
import '../../widgets/rounded_app_bar.dart';
import '../home/widgets/home_theme.dart';
import 'legal_documents_catalog.dart';

class LegalDocumentsHubScreen extends StatelessWidget {
  const LegalDocumentsHubScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: RoundedAppBar(titleText: 'Yasal Belgeler'),
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: LegalDocumentsCatalog.profileLinks.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          final item = LegalDocumentsCatalog.profileLinks[index];
          return ListTile(
            tileColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            title: Text(item.title),
            trailing: const Icon(Icons.open_in_new, size: 18),
            onTap: () {
              Navigator.pushNamed(
                context,
                RouteNames.legalDocumentScreen,
                arguments: {'slug': item.slug, 'title': item.title},
              );
            },
          );
        },
      ),
    );
  }
}
