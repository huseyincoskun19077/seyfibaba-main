import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';

class OnBordingModel extends Equatable {
  final String title;
  final String paragraph;
  final String badge;
  final int art;
  final Color accent;

  const OnBordingModel({
    required this.title,
    required this.paragraph,
    required this.badge,
    required this.art,
    required this.accent,
  });

  @override
  List<Object> get props => [title, paragraph, badge, art, accent];
}
