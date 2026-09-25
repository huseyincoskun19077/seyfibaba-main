import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../modules/home/widgets/home_theme.dart';

/// Web `simple_product_variants` ile uyumlu: Renk + Hacim/Boyut/Paket/Tip/manuel.
class SellerSimpleVariantsEditor extends StatefulWidget {
  const SellerSimpleVariantsEditor({
    super.key,
    this.initialColors = const [],
    this.initialGroups = const [],
    this.compact = false,
  });

  final List<SellerColorDraft> initialColors;
  final List<SellerOptionGroupDraft> initialGroups;
  final bool compact;

  @override
  State<SellerSimpleVariantsEditor> createState() =>
      SellerSimpleVariantsEditorState();
}

class SellerSimpleVariantsEditorState extends State<SellerSimpleVariantsEditor> {
  final List<SellerColorDraft> _colors = [];
  final List<SellerOptionGroupDraft> _groups = [];
  bool _open = false;
  bool _showColors = false;

  static const _presets = <(String, String)>[
    ('Hacim', 'Örn: 250 ml, 1 lt'),
    ('Boyut', 'Örn: S, M, L'),
    ('Paket', 'Örn: 5’li, 10’lu'),
    ('Tip', 'Örn: Soft, Sert'),
  ];

  @override
  void initState() {
    super.initState();
    for (final c in widget.initialColors) {
      _colors.add(c.copy());
    }
    for (final g in widget.initialGroups) {
      _groups.add(g.copy());
    }
    _showColors = _colors.isNotEmpty;
    _open = _showColors || _groups.isNotEmpty;
  }

  @override
  void dispose() {
    for (final c in _colors) {
      c.dispose();
    }
    for (final g in _groups) {
      g.dispose();
    }
    super.dispose();
  }

  List<Map<String, String>> get colorsPayload {
    return _colors
        .where((c) => c.nameCtrl.text.trim().isNotEmpty)
        .map(
          (c) => {
            'name': c.nameCtrl.text.trim(),
            'price': c.priceCtrl.text.trim(),
            'qty': c.qtyCtrl.text.trim(),
            if (c.imagePath != null && c.imagePath!.isNotEmpty)
              'image': c.imagePath!,
            if (c.keepImage != null && c.keepImage!.isNotEmpty)
              'keep_image': c.keepImage!,
          },
        )
        .toList();
  }

  List<Map<String, dynamic>> get optionGroupsPayload {
    final out = <Map<String, dynamic>>[];
    for (final g in _groups) {
      final name = g.nameCtrl.text.trim();
      if (name.isEmpty || RegExp(r'^renk$', caseSensitive: false).hasMatch(name)) {
        continue;
      }
      final items = <Map<String, String>>[];
      for (final item in g.items) {
        final iname = item.nameCtrl.text.trim();
        if (iname.isEmpty) continue;
        items.add({
          'name': iname,
          'price': item.priceCtrl.text.trim(),
        });
      }
      out.add({'name': name, 'items': items});
    }
    return out;
  }

  void _ensureOpen() {
    if (!_open) setState(() => _open = true);
  }

  Future<void> _pickColorImage(int index) async {
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1200,
    );
    if (file == null) return;
    setState(() => _colors[index].imagePath = file.path);
  }

  void _addColor() {
    _ensureOpen();
    setState(() {
      _showColors = true;
      _colors.add(SellerColorDraft());
    });
  }

  void _addGroup(String name, {String placeholder = ''}) {
    _ensureOpen();
    final exists = _groups.any(
      (g) => g.nameCtrl.text.trim().toLowerCase() == name.toLowerCase(),
    );
    if (exists && name.isNotEmpty) {
      setState(() {});
      return;
    }
    setState(() {
      _groups.add(
        SellerOptionGroupDraft(
          name: name,
          placeholder: placeholder,
          items: [SellerOptionItemDraft()],
        ),
      );
    });
  }

  InputDecoration _dec(String label, {String? hint}) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      border: const OutlineInputBorder(),
      isDense: true,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: HomeTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InkWell(
            onTap: () => setState(() => _open = !_open),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Varyantlar',
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
                            color: HomeTheme.textDark,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          widget.compact
                              ? 'Renk, hacim, boyut… opsiyonel'
                              : 'Renk, hacim, boyut, paket… İsterseniz açın; yoksa atlayın.',
                          style: const TextStyle(
                            fontSize: 12,
                            color: HomeTheme.textMuted,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Icon(
                    _open ? Icons.expand_less : Icons.expand_more,
                    color: HomeTheme.textMuted,
                  ),
                ],
              ),
            ),
          ),
          if (_open) ...[
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _chip('Renk', onTap: _addColor),
                      ..._presets.map(
                        (p) => _chip(
                          p.$1,
                          onTap: () => _addGroup(p.$1, placeholder: p.$2),
                        ),
                      ),
                      _chip(
                        '+ Manuel',
                        outlined: true,
                        onTap: () => _addGroup('', placeholder: 'Seçenek adı'),
                      ),
                    ],
                  ),
                  if (_showColors) ...[
                    const SizedBox(height: 14),
                    _blockHead(
                      title: 'Renk',
                      hint: 'Fiyat boşsa ürün fiyatı. Fotoğraf isteğe bağlı.',
                      onRemove: () {
                        setState(() {
                          for (final c in _colors) {
                            c.dispose();
                          }
                          _colors.clear();
                          _showColors = false;
                        });
                      },
                    ),
                    const SizedBox(height: 8),
                    ...List.generate(_colors.length, (i) {
                      final c = _colors[i];
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: HomeTheme.border),
                          ),
                          child: Column(
                            children: [
                              TextField(
                                controller: c.nameCtrl,
                                decoration: _dec('Renk adı', hint: 'Örn: Siyah'),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Expanded(
                                    child: TextField(
                                      controller: c.priceCtrl,
                                      keyboardType:
                                          const TextInputType.numberWithOptions(
                                        decimal: true,
                                      ),
                                      decoration: _dec('Fiyat', hint: 'Boş = aynı'),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: TextField(
                                      controller: c.qtyCtrl,
                                      keyboardType: TextInputType.number,
                                      decoration: _dec('Adet', hint: '10'),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  TextButton.icon(
                                    onPressed: () => _pickColorImage(i),
                                    icon: const Icon(Icons.photo_outlined, size: 18),
                                    label: Text(
                                      c.imagePath != null
                                          ? 'Fotoğraf seçildi'
                                          : (c.keepImage != null &&
                                                  c.keepImage!.isNotEmpty
                                              ? 'Mevcut fotoğraf'
                                              : 'Renk fotoğrafı'),
                                    ),
                                  ),
                                  const Spacer(),
                                  IconButton(
                                    onPressed: () {
                                      setState(() {
                                        _colors.removeAt(i).dispose();
                                        if (_colors.isEmpty) _showColors = false;
                                      });
                                    },
                                    icon: const Icon(Icons.delete_outline),
                                    color: Colors.redAccent,
                                  ),
                                ],
                              ),
                              if (c.imagePath != null)
                                Align(
                                  alignment: Alignment.centerLeft,
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.file(
                                      File(c.imagePath!),
                                      height: 56,
                                      width: 56,
                                      fit: BoxFit.cover,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      );
                    }),
                    OutlinedButton.icon(
                      onPressed: () =>
                          setState(() => _colors.add(SellerColorDraft())),
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('Renk ekle'),
                    ),
                  ],
                  ...List.generate(_groups.length, (gi) {
                    final g = _groups[gi];
                    return Padding(
                      padding: const EdgeInsets.only(top: 14),
                      child: Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: HomeTheme.border),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: g.nameCtrl,
                                    decoration: _dec('Grup adı'),
                                  ),
                                ),
                                IconButton(
                                  onPressed: () {
                                    setState(() => _groups.removeAt(gi).dispose());
                                  },
                                  icon: const Icon(Icons.close),
                                  color: Colors.redAccent,
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            ...List.generate(g.items.length, (ii) {
                              final item = g.items[ii];
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 8),
                                child: Row(
                                  children: [
                                    Expanded(
                                      flex: 3,
                                      child: TextField(
                                        controller: item.nameCtrl,
                                        decoration: _dec(
                                          'Seçenek',
                                          hint: g.placeholder.isEmpty
                                              ? null
                                              : g.placeholder,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      flex: 2,
                                      child: TextField(
                                        controller: item.priceCtrl,
                                        keyboardType:
                                            const TextInputType.numberWithOptions(
                                          decimal: true,
                                        ),
                                        decoration: _dec('+₺ ek'),
                                      ),
                                    ),
                                    IconButton(
                                      onPressed: () {
                                        setState(() {
                                          g.items.removeAt(ii).dispose();
                                          if (g.items.isEmpty) {
                                            g.items.add(SellerOptionItemDraft());
                                          }
                                        });
                                      },
                                      icon: const Icon(Icons.remove_circle_outline),
                                      color: Colors.redAccent,
                                    ),
                                  ],
                                ),
                              );
                            }),
                            OutlinedButton.icon(
                              onPressed: () => setState(
                                () => g.items.add(SellerOptionItemDraft()),
                              ),
                              icon: const Icon(Icons.add, size: 18),
                              label: const Text('Seçenek ekle'),
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _chip(String label, {required VoidCallback onTap, bool outlined = false}) {
    return ActionChip(
      label: Text(label),
      onPressed: onTap,
      backgroundColor: outlined ? Colors.white : const Color(0xFFF8FAFC),
      side: BorderSide(
        color: outlined ? HomeTheme.brandYellow : HomeTheme.border,
      ),
      labelStyle: const TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: HomeTheme.textDark,
      ),
    );
  }

  Widget _blockHead({
    required String title,
    required String hint,
    required VoidCallback onRemove,
  }) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: HomeTheme.textDark,
                ),
              ),
              Text(
                hint,
                style: const TextStyle(fontSize: 11, color: HomeTheme.textMuted),
              ),
            ],
          ),
        ),
        TextButton(
          onPressed: onRemove,
          style: TextButton.styleFrom(foregroundColor: Colors.redAccent),
          child: const Text('Kaldır'),
        ),
      ],
    );
  }
}

class SellerColorDraft {
  SellerColorDraft({
    String name = '',
    String price = '',
    String qty = '',
    this.imagePath,
    this.keepImage,
  })  : nameCtrl = TextEditingController(text: name),
        priceCtrl = TextEditingController(text: price),
        qtyCtrl = TextEditingController(text: qty);

  final TextEditingController nameCtrl;
  final TextEditingController priceCtrl;
  final TextEditingController qtyCtrl;
  String? imagePath;
  String? keepImage;

  SellerColorDraft copy() => SellerColorDraft(
        name: nameCtrl.text,
        price: priceCtrl.text,
        qty: qtyCtrl.text,
        imagePath: imagePath,
        keepImage: keepImage,
      );

  void dispose() {
    nameCtrl.dispose();
    priceCtrl.dispose();
    qtyCtrl.dispose();
  }
}

class SellerOptionItemDraft {
  SellerOptionItemDraft({String name = '', String price = ''})
      : nameCtrl = TextEditingController(text: name),
        priceCtrl = TextEditingController(text: price);

  final TextEditingController nameCtrl;
  final TextEditingController priceCtrl;

  SellerOptionItemDraft copy() => SellerOptionItemDraft(
        name: nameCtrl.text,
        price: priceCtrl.text,
      );

  void dispose() {
    nameCtrl.dispose();
    priceCtrl.dispose();
  }
}

class SellerOptionGroupDraft {
  SellerOptionGroupDraft({
    String name = '',
    this.placeholder = '',
    List<SellerOptionItemDraft>? items,
  })  : nameCtrl = TextEditingController(text: name),
        items = items ?? [SellerOptionItemDraft()];

  final TextEditingController nameCtrl;
  final String placeholder;
  final List<SellerOptionItemDraft> items;

  SellerOptionGroupDraft copy() => SellerOptionGroupDraft(
        name: nameCtrl.text,
        placeholder: placeholder,
        items: items.map((e) => e.copy()).toList(),
      );

  void dispose() {
    nameCtrl.dispose();
    for (final i in items) {
      i.dispose();
    }
  }
}
