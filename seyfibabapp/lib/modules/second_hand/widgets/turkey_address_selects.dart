import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'second_hand_ui.dart';

const _mahSep = '\u001e';

const _localUrl = 'https://seyfibaba.com/data/tr-turkiye-address.json';
const _remoteUrl =
    'https://raw.githubusercontent.com/hsndmr/turkiye-city-county-district-neighborhood/main/data.json';

class TurkeyAddressValue {
  const TurkeyAddressValue({
    this.province = '',
    this.district = '',
    this.locality = '',
    this.neighborhood = '',
  });

  final String province;
  final String district;
  final String locality;
  final String neighborhood;

  TurkeyAddressValue copyWith({
    String? province,
    String? district,
    String? locality,
    String? neighborhood,
  }) {
    return TurkeyAddressValue(
      province: province ?? this.province,
      district: district ?? this.district,
      locality: locality ?? this.locality,
      neighborhood: neighborhood ?? this.neighborhood,
    );
  }
}

class TurkeyAddressTree {
  TurkeyAddressTree._();

  static List<dynamic>? _cache;
  static Future<List<dynamic>>? _pending;

  static Future<List<dynamic>> load() {
    if (_cache != null) return Future.value(_cache);
    return _pending ??= _fetch();
  }

  static Future<List<dynamic>> _fetch() async {
    for (final url in [_localUrl, _remoteUrl]) {
      try {
        final res = await http.get(Uri.parse(url)).timeout(
              const Duration(seconds: 20),
            );
        if (res.statusCode != 200) continue;
        final decoded = json.decode(utf8.decode(res.bodyBytes));
        final tree = decoded is List
            ? decoded
            : (decoded is Map
                ? (decoded['cities'] ?? decoded['data'] ?? [])
                : []);
        if (tree is List && tree.isNotEmpty) {
          _cache = tree;
          return tree;
        }
      } catch (_) {}
    }
    _pending = null;
    return const [];
  }
}

class TurkeyAddressSelects extends StatefulWidget {
  const TurkeyAddressSelects({
    super.key,
    required this.value,
    required this.onChanged,
    this.showNeighborhood = true,
  });

  final TurkeyAddressValue value;
  final ValueChanged<TurkeyAddressValue> onChanged;
  final bool showNeighborhood;

  @override
  State<TurkeyAddressSelects> createState() => _TurkeyAddressSelectsState();
}

class _TurkeyAddressSelectsState extends State<TurkeyAddressSelects> {
  List<dynamic> _tree = [];
  bool _loading = true;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _failed = false;
    });
    final tree = await TurkeyAddressTree.load();
    if (!mounted) return;
    setState(() {
      _tree = tree;
      _loading = false;
      _failed = tree.isEmpty;
    });
  }

  Map<String, dynamic>? _city() {
    for (final c in _tree) {
      if (c is Map && '${c['name']}' == widget.value.province) {
        return Map<String, dynamic>.from(c);
      }
    }
    return null;
  }

  List<Map<String, dynamic>> _counties() {
    final city = _city();
    final raw = city?['counties'];
    if (raw is! List) return [];
    return raw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
  }

  Map<String, dynamic>? _county() {
    for (final c in _counties()) {
      if ('${c['name']}' == widget.value.district) return c;
    }
    return null;
  }

  List<_MahalleItem> _mahalleItems() {
    final county = _county();
    final districts = county?['districts'];
    if (districts is! List) return [];
    final pairs = <_MahalleItem>[];
    for (final d in districts) {
      if (d is! Map) continue;
      final localityName = '${d['name']}';
      final neighborhoods = d['neighborhoods'];
      if (neighborhoods is! List) continue;
      for (final n in neighborhoods) {
        if (n is! Map) continue;
        pairs.add(_MahalleItem(
          localityName: localityName,
          neighborhoodName: '${n['name']}',
        ));
      }
    }
    final counts = <String, int>{};
    for (final p in pairs) {
      counts[p.neighborhoodName] = (counts[p.neighborhoodName] ?? 0) + 1;
    }
    return pairs
        .map(
          (p) => p.copyWith(
            label: (counts[p.neighborhoodName] ?? 0) > 1
                ? '${p.neighborhoodName} (${p.localityName})'
                : p.neighborhoodName,
          ),
        )
        .toList();
  }

  bool get _hasMahalle {
    if (!widget.showNeighborhood) return false;
    if (widget.value.neighborhood.isNotEmpty) return true;
    return _mahalleItems().isNotEmpty;
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 8),
        child: Text(
          'Adres listesi yükleniyor…',
          style: TextStyle(fontSize: 13, color: ShTheme.muted),
        ),
      );
    }
    if (_failed) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Adres listesi yüklenemedi.',
            style: TextStyle(fontSize: 13, color: ShTheme.muted),
          ),
          TextButton(onPressed: _load, child: const Text('Tekrar dene')),
        ],
      );
    }

    final v = widget.value;
    final cityNames = _tree
        .whereType<Map>()
        .map((c) => '${c['name']}')
        .where((n) => n.isNotEmpty)
        .toList();
    final counties = _counties();
    final mahalle = _mahalleItems();
    final provinceValue =
        cityNames.contains(v.province) ? v.province : '';
    final districtNames = counties.map((c) => '${c['name']}').toList();
    final districtValue =
        districtNames.contains(v.district) ? v.district : '';
    final mahKey = v.locality.isNotEmpty && v.neighborhood.isNotEmpty
        ? '${v.locality}$_mahSep${v.neighborhood}'
        : '';

    return Column(
      children: [
        ShDropdownField<String>(
          label: 'İl',
          value: provinceValue.isEmpty ? '' : provinceValue,
          items: [
            const DropdownMenuItem(value: '', child: Text('Seçin')),
            ...cityNames.map(
              (n) => DropdownMenuItem(value: n, child: Text(n)),
            ),
          ],
          onChanged: (next) {
            widget.onChanged(
              TurkeyAddressValue(province: next ?? ''),
            );
          },
        ),
        const SizedBox(height: 10),
        ShDropdownField<String>(
          label: 'İlçe',
          value: districtValue.isEmpty ? '' : districtValue,
          enabled: provinceValue.isNotEmpty,
          items: [
            const DropdownMenuItem(value: '', child: Text('Seçin')),
            ...counties.map(
              (c) => DropdownMenuItem(
                value: '${c['name']}',
                child: Text('${c['name']}'),
              ),
            ),
          ],
          onChanged: (next) {
            widget.onChanged(
              TurkeyAddressValue(
                province: v.province,
                district: next ?? '',
              ),
            );
          },
        ),
        if (_hasMahalle) ...[
          const SizedBox(height: 10),
          ShDropdownField<String>(
            label: 'Mahalle',
            value: mahKey,
            enabled: districtValue.isNotEmpty,
            items: [
              const DropdownMenuItem(value: '', child: Text('Seçin')),
              if (mahKey.isNotEmpty &&
                  mahalle.every((m) => m.key != mahKey))
                DropdownMenuItem(
                  value: mahKey,
                  child: Text('${v.neighborhood} (kayıtlı)'),
                ),
              ...mahalle.map(
                (m) => DropdownMenuItem(
                  value: m.key,
                  child: Text(m.label),
                ),
              ),
            ],
            onChanged: (raw) {
                    if (raw == null || raw.isEmpty) {
                      widget.onChanged(
                        TurkeyAddressValue(
                          province: v.province,
                          district: v.district,
                        ),
                      );
                      return;
                    }
                    final i = raw.indexOf(_mahSep);
                    if (i < 0) return;
                    widget.onChanged(
                      TurkeyAddressValue(
                        province: v.province,
                        district: v.district,
                        locality: raw.substring(0, i),
                        neighborhood: raw.substring(i + _mahSep.length),
                      ),
                    );
                  },
          ),
        ],
      ],
    );
  }
}

class _MahalleItem {
  const _MahalleItem({
    required this.localityName,
    required this.neighborhoodName,
    this.label = '',
  });

  final String localityName;
  final String neighborhoodName;
  final String label;

  String get key => '$localityName$_mahSep$neighborhoodName';

  _MahalleItem copyWith({String? label}) {
    return _MahalleItem(
      localityName: localityName,
      neighborhoodName: neighborhoodName,
      label: label ?? this.label,
    );
  }
}
