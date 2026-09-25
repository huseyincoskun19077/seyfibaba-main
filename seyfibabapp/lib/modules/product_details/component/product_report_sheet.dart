import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:http/http.dart' as http;

import '../../../core/remote_urls.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';

/// Ürün şikayet formu — web product-report ile aynı API.
Future<void> showProductReportSheet(
  BuildContext context, {
  required int productId,
}) async {
  final loginBloc = context.read<LoginBloc>();
  if (!loginBloc.isLogedIn) {
    Utils.errorSnackBar(context, 'Şikayet için giriş yapmalısınız');
    return;
  }

  final subjectCtrl = TextEditingController();
  final noteCtrl = TextEditingController();
  var submitting = false;

  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: whiteColor,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
    ),
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setLocal) {
          final bottom = MediaQuery.viewInsetsOf(ctx).bottom;
          return Padding(
            padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottom),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    const Expanded(
                      child: Text(
                        'Ürünü şikayet et',
                        style: TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF04334A),
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(ctx),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: subjectCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Başlık *',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: noteCtrl,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Açıklama *',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: submitting
                      ? null
                      : () async {
                          final subject = subjectCtrl.text.trim();
                          final note = noteCtrl.text.trim();
                          if (subject.isEmpty || note.isEmpty) {
                            Utils.errorSnackBar(
                                context, 'Başlık ve açıklama zorunlu');
                            return;
                          }
                          setLocal(() => submitting = true);
                          try {
                            final token = loginBloc.userInfo!.accessToken;
                            final uri =
                                Uri.parse(RemoteUrls.productReport(token));
                            final res = await http.post(
                              uri,
                              headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                              },
                              body: jsonEncode({
                                'subject': subject,
                                'description': note,
                                'product_id': productId,
                              }),
                            );
                            if (!context.mounted) return;
                            if (res.statusCode >= 200 &&
                                res.statusCode < 300) {
                              Navigator.pop(ctx);
                              Utils.showSnackBar(
                                  context, 'Şikayetiniz alındı');
                            } else {
                              Utils.errorSnackBar(
                                  context, 'Şikayet gönderilemedi');
                            }
                          } catch (e) {
                            if (context.mounted) {
                              Utils.errorSnackBar(context, '$e');
                            }
                          } finally {
                            if (ctx.mounted) {
                              setLocal(() => submitting = false);
                            }
                          }
                        },
                  style: FilledButton.styleFrom(
                    backgroundColor: yellowColor,
                    foregroundColor: const Color(0xFF04334A),
                    minimumSize: const Size.fromHeight(48),
                  ),
                  child: Text(
                    submitting ? 'Gönderiliyor…' : 'Şikayeti gönder',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ],
            ),
          );
        },
      );
    },
  );

  subjectCtrl.dispose();
  noteCtrl.dispose();
}
