import 'package:flutter/material.dart';

class AppColors {
  // ── New brand palette (light mode) ──
  static const primaryLight = Color(0xFF9DBCD4); // --primary-light
  static const primary      = Color(0xFF0E62AA); // --primary-main
  static const darkNavy     = Color(0xFF062744); // --primary-dark
  static const iconColor    = Color(0xFF062744); // --icon-color

  static const lightBlue    = Color(0xFF9DBCD4); // kept in sync with primaryLight
  static const cardBg       = Colors.white;
  static const textDark     = Color(0xFF1A1A2E);
  static const textGrey     = Color(0xFF8A8FA3);

  static const positive     = Color(0xFF0E62AA);
  static const neutral      = Color(0xFF9DBCD4);
  static const negative     = Color(0xFF062744);

  static const activeGreen      = Color(0xFF4CAF50);
  static const inactiveGrey     = Color(0xFF9E9E9E);
  static const deactivateOrange = Color(0xFFFF9800);
  static const deleteRed        = Color(0xFFE53935);
  static const editBlue         = Color(0xFF0E62AA);
  static const activateGreen    = Color(0xFF43A047);

  // Additional shared constants
  static const background = Color(0xFFF4F6FB);
  static const border     = Color(0xFFDDE3EE);
  static const viewTeal   = Color(0xFF00838F);

  // Chatbot screen
  static const patientBubble = Color(0xFFF0F2F8);
  static const botBubble     = Color(0xFF0E62AA);
  static const newBadge      = Color(0xFF43A047);
  static const activeBadge   = Color(0xFF43A047);
  static const onlineDot     = Color(0xFF43A047);
}

// ── Dark mode palette ──
// Used to build ThemeData.darkTheme in main.dart. Also exposed directly
// here so any screen that needs a dark-mode-specific one-off color
// (rather than pulling from Theme.of(context)) can reference it.
class AppColorsDark {
  static const background = Color(0xFF121826); // App background
  static const card        = Color(0xFF1C2433); // Cards
  static const primary     = Color(0xFF4D9BE6); // Primary buttons
  static const darkAccent  = Color(0xFF0A1B2C); // Dark accent
  static const textMain    = Color(0xFFF5F7FA); // Main text
  static const textGrey    = Color(0xFFB8C1D1); // Secondary text
  static const border      = Color(0xFF2D3748); // Borders
}

// ── Theme-aware color helper ──
// Lets widgets ask for "the right color for the current theme" without
// every screen needing its own Theme.of(context) brightness check.
// Usage: AppColorsAdaptive.cardBg(context)
class AppColorsAdaptive {
  static bool _isDark(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark;

  static Color background(BuildContext context) =>
      _isDark(context) ? AppColorsDark.background : AppColors.background;

  static Color cardBg(BuildContext context) =>
      _isDark(context) ? AppColorsDark.card : AppColors.cardBg;

  static Color textDark(BuildContext context) =>
      _isDark(context) ? AppColorsDark.textMain : AppColors.textDark;

  static Color textGrey(BuildContext context) =>
      _isDark(context) ? AppColorsDark.textGrey : AppColors.textGrey;

  static Color border(BuildContext context) =>
      _isDark(context) ? AppColorsDark.border : AppColors.border;

  static Color primary(BuildContext context) =>
      _isDark(context) ? AppColorsDark.primary : AppColors.primary;
}