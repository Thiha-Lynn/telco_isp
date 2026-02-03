import 'package:flutter/material.dart';

/// T-Link Group brand colors
class AppColors {
  AppColors._();

  // Primary Colors
  static const Color primary = Color(0xFF6699FF);  // Blue pale
  static const Color primaryLight = Color(0xFF99BBFF);
  static const Color primaryDark = Color(0xFF3366CC);

  // Text Colors
  static const Color textPrimary = Color(0xFF333333);  // Dark brown
  static const Color textSecondary = Color(0xFF666666);
  static const Color textLight = Color(0xFF999999);
  static const Color textOnPrimary = Colors.white;

  // Background Colors
  static const Color background = Color(0xFFFAFAFA);
  static const Color surface = Colors.white;
  static const Color card = Colors.white;

  // Status Colors
  static const Color success = Color(0xFF4CAF50);
  static const Color error = Color(0xFFE53935);
  static const Color warning = Color(0xFFFFA726);
  static const Color info = Color(0xFF29B6F6);

  // Other
  static const Color divider = Color(0xFFE0E0E0);
  static const Color border = Color(0xFFE8E8E8);
  static const Color disabled = Color(0xFFBDBDBD);
  static const Color shadow = Color(0x1A000000);

  // Gradient
  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primary, primaryDark],
  );

  static const LinearGradient backgroundGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFFF5F9FF), Colors.white],
  );
}
