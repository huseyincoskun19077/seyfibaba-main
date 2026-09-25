import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../controller/cubit/product/products_cubit.dart';
import '../model/story_model.dart';

class StoryProductItem {
  final int id;
  final String name;
  final String slug;
  final String thumb;
  final double price;
  final double offerPrice;

  const StoryProductItem({
    required this.id,
    required this.name,
    required this.slug,
    required this.thumb,
    required this.price,
    required this.offerPrice,
  });

  factory StoryProductItem.fromMap(Map<String, dynamic> map) {
    return StoryProductItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      name: '${map['short_name'] ?? map['name'] ?? ''}',
      slug: '${map['slug'] ?? ''}',
      thumb: '${map['thumb_image'] ?? ''}',
      price: double.tryParse('${map['price'] ?? 0}') ?? 0,
      offerPrice: double.tryParse('${map['offer_price'] ?? 0}') ?? 0,
    );
  }

  bool get hasOffer => offerPrice > 0 && offerPrice < price;
}

Future<List<StoryProductItem>> fetchStoryProducts(String feed) async {
  final uri = Uri.parse(
    '${RemoteUrls.baseUrl}story-products?feed=${Uri.encodeComponent(feed)}&limit=16',
  );
  final res = await http.get(uri, headers: {'Accept': 'application/json'});
  if (res.statusCode != 200) return [];
  final body = jsonDecode(res.body);
  final list = body is Map ? body['products'] : null;
  if (list is! List) return [];
  return list
      .whereType<Map>()
      .map((e) => StoryProductItem.fromMap(Map<String, dynamic>.from(e)))
      .toList();
}

Future<void> openStoryLink(BuildContext context, String raw) async {
  final href = raw.trim();
  if (href.isEmpty) return;

  final lower = href.toLowerCase();
  if (lower.contains('satici-kayit') ||
      lower.contains('become-seller') ||
      lower == '/satici' ||
      lower.endsWith('/satici') ||
      lower.contains('/satici?')) {
    Navigator.pushNamed(context, RouteNames.becomeSellerScreen);
    return;
  }

  if (lower.startsWith('http://') || lower.startsWith('https://')) {
    final uri = Uri.tryParse(href);
    if (uri != null && await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
    return;
  }

  final path = href.startsWith('/') ? href : '/$href';
  final uri = Uri.parse('https://kuafortedarik.com$path');
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

void openStorySeeAll(BuildContext context, StoryModel story) {
  final productCubit = context.read<ProductsCubit>();
  final feed = (story.feed ?? 'popular').trim();
  const map = <String, String>{
    'popular': 'popular_category',
    'bestseller': 'best_product',
    'discounted': 'discounted',
    'featured': 'featured_product',
    'new_arrival': 'new_arrival',
  };
  final keyword = map[feed] ?? 'popular_category';
  if (productCubit.state.initialPage > 1) {
    productCubit.initPage();
  }
  productCubit.nameChange(story.title);
  Navigator.pushNamed(
    context,
    RouteNames.allPopularProductScreen,
    arguments: keyword,
  );
}
