import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'second_hand_ui.dart';

const _mahSep = '\u001e';

const _localUrl = 'https://seyfibaba.com/data/tr-turkiye-address.json';
const _remoteUrl =
    'https://raw.githubusercontent.com/hsndmr/turkiye-city-county-district-neighborhood/main/data.json';

String normalizeTrPlaceName(String value) {
  var s = value.trim().toLowerCase();
  const from = 'ığüşöçİI';
  const to = 'igusocii';
  for (var i = 0; i < from.length; i++) {
    s = s.replaceAll(from[i], to[i]);
  }
  s = s.replaceAll(RegExp(r'\s+'), ' ');
  s = s.replaceAll(RegExp(r'\s*mah\.?$', caseSensitive: false), '');
  return s.trim();
}

String titleTrPlaceName(String value) {
  var cleaned = value.trim().replaceAll(RegExp(r'\s*MAH\.?$', caseSensitive: false), '');
  cleaned = cleaned.replaceAll(RegExp(r'\s+'), ' ');
  if (cleaned.isEmpty) return '';
  final lower = cleaned.toLowerCase();
  final buffer = StringBuffer();
  var capNext = true;
  for (final rune in lower.runes) {
    final ch = String.fromCharCode(rune);
    if (capNext && RegExp(r'\S').hasMatch(ch)) {
      buffer.write(ch.toUpperCase());
      capNext = false;
    } else {
      buffer.write(ch);
      if (ch == ' ' || ch == '/' || ch == '-') capNext = true;
    }
  }
  return buffer.toString();
}

bool _namesMatch(String a, String b) =>
    normalizeTrPlaceName(a) == normalizeTrPlaceName(b);

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

  static bool _hasMahalleData(List<dynamic> tree) {
    for (final c in tree) {
      if (c is! Map) continue;
      final counties = c['counties'];
      if (counties is! List) continue;
      for (final co in counties) {
        if (co is! Map) continue;
        final districts = co['districts'];
        if (districts is! List) continue;
        for (final d in districts) {
          if (d is! Map) continue;
          final neighborhoods = d['neighborhoods'];
          if (neighborhoods is List && neighborhoods.isNotEmpty) return true;
        }
      }
    }
    return false;
  }

  static Future<List<dynamic>> _fetch() async {
    for (final url in [_remoteUrl, _localUrl]) {
      try {
        final res = await http.get(Uri.parse(url)).timeout(
              const Duration(seconds: 25),
            );
        if (res.statusCode != 200) continue;
        final decoded = json.decode(utf8.decode(res.bodyBytes));
        final tree = decoded is List
            ? decoded
            : (decoded is Map
                ? (decoded['cities'] ?? decoded['data'] ?? [])
                : []);
        if (tree is List && tree.isNotEmpty && _hasMahalleData(tree)) {
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
    this.onlyNeighborhood = false,
  });

  final TurkeyAddressValue value;
  final ValueChanged<TurkeyAddressValue> onChanged;
  final bool showNeighborhood;

  /// İl/ilçe zaten seçiliyse yalnızca mahalle dropdown gösterir.
  final bool onlyNeighborhood;

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

  Map<String, dynamic>? _findCity(String province) {
    for (final c in _tree) {
      if (c is Map && _namesMatch('${c['name']}', province)) {
        return Map<String, dynamic>.from(c);
      }
    }
    return null;
  }

  List<Map<String, dynamic>> _countiesFor(Map<String, dynamic>? city) {
    final raw = city?['counties'];
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Map<String, dynamic>? _findCounty(String province, String district) {
    final city = _findCity(province);
    final counties = _countiesFor(city);
    for (final c in counties) {
      if (_namesMatch('${c['name']}', district)) return c;
    }
    if (district.trim().isEmpty) return null;
    for (final c in _tree) {
      if (c is! Map) continue;
      for (final co in _countiesFor(Map<String, dynamic>.from(c))) {
        if (_namesMatch('${co['name']}', district)) return co;
      }
    }
    return null;
  }

  List<_MahalleItem> _mahalleItems() {
    final county = _findCounty(widget.value.province, widget.value.district);
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
      final key = normalizeTrPlaceName(p.neighborhoodName);
      counts[key] = (counts[key] ?? 0) + 1;
    }
    final items = pairs
        .map((p) {
          final labelBase = titleTrPlaceName(p.neighborhoodName);
          final needsLocality =
              (counts[normalizeTrPlaceName(p.neighborhoodName)] ?? 0) > 1;
          return p.copyWith(
            label: needsLocality
                ? '$labelBase (${titleTrPlaceName(p.localityName)})'
                : labelBase,
          );
        })
        .toList();
    items.sort((a, b) => a.label.compareTo(b.label));
    return items;
  }

  String _resolveMahalleKey(List<_MahalleItem> mahalle) {
    final v = widget.value;
    if (v.neighborhood.isEmpty) return '';
    if (v.locality.isNotEmpty) {
      final key = '${v.locality}$_mahSep${v.neighborhood}';
      if (mahalle.any((m) => m.key == key)) return key;
    }
    for (final m in mahalle) {
      if (_namesMatch(m.neighborhoodName, v.neighborhood) ||
          _namesMatch(titleTrPlaceName(m.neighborhoodName), v.neighborhood)) {
        return m.key;
      }
    }
    return '';
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 8),
        child: Text(
          'Mahalle listesi yükleniyor…',
          style: TextStyle(fontSize: 13, color: ShTheme.muted),
        ),
      );
    }
    if (_failed) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Mahalle listesi yüklenemedi.',
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
    final matchedCity = _findCity(v.province);
    final counties = _countiesFor(matchedCity);
    final mahalle = widget.showNeighborhood ? _mahalleItems() : <_MahalleItem>[];
    final provinceValue = matchedCity != null ? '${matchedCity['name']}' : '';
    final matchedCounty = _findCounty(v.province, v.district);
    final districtValue =
        matchedCounty != null ? '${matchedCounty['name']}' : '';
    final mahKey = _resolveMahalleKey(mahalle);
    final districtReady = v.district.trim().isNotEmpty;

    final mahalleField = !widget.showNeighborhood
        ? const SizedBox.shrink()
        : Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (!widget.onlyNeighborhood) const SizedBox(height: 10),
              ShDropdownField<String>(
                label: 'Mahalle',
                value: mahKey,
                enabled: districtReady,
                items: [
                  DropdownMenuItem(
                    value: '',
                    child: Text(
                      !districtReady
                          ? 'Önce ilçe seçin'
                          : mahalle.isEmpty
                              ? 'Mahalle bulunamadı'
                              : 'Mahalle seçin',
                    ),
                  ),
                  if (v.neighborhood.isNotEmpty && mahKey.isEmpty)
                    DropdownMenuItem(
                      value: 'legacy$_mahSep${v.neighborhood}',
                      child: Text('${titleTrPlaceName(v.neighborhood)} (kayıtlı)'),
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
                  if (raw.startsWith('legacy$_mahSep')) {
                    widget.onChanged(
                      TurkeyAddressValue(
                        province: v.province,
                        district: v.district,
                        neighborhood: raw.substring('legacy$_mahSep'.length),
                      ),
                    );
                    return;
                  }
                  final i = raw.indexOf(_mahSep);
                  if (i < 0) return;
                  final neighborhood = raw.substring(i + _mahSep.length);
                  widget.onChanged(
                    TurkeyAddressValue(
                      province: v.province,
                      district: v.district,
                      locality: raw.substring(0, i),
                      neighborhood: titleTrPlaceName(neighborhood),
                    ),
                  );
                },
              ),
              if (!districtReady)
                const Padding(
                  padding: EdgeInsets.only(top: 6),
                  child: Text(
                    'Önce il ve ilçe seçin.',
                    style: TextStyle(fontSize: 12, color: ShTheme.muted),
                  ),
                ),
            ],
          );

    if (widget.onlyNeighborhood) {
      return mahalleField;
    }

    return Column(
      children: [
        ShDropdownField<String>(
          label: 'İl',
          value: provinceValue.isEmpty ? '' : provinceValue,
          items: [
            const DropdownMenuItem(value: '', child: Text('Seçin')),
            ...cityNames.map(
              (n) => DropdownMenuItem(
                value: n,
                child: Text(titleTrPlaceName(n)),
              ),
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
                child: Text(titleTrPlaceName('${c['name']}')),
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
        mahalleField,
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
