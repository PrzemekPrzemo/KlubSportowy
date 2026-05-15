import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppSettings {
  const AppSettings({
    this.themeMode = ThemeMode.system,
    this.locale,
    this.pushEnabled = true,
    this.seedColor = const Color(0xFF1976D2),
  });

  final ThemeMode themeMode;
  final Locale? locale;
  final bool pushEnabled;
  final Color seedColor;

  AppSettings copyWith({
    ThemeMode? themeMode,
    Locale? locale,
    bool? pushEnabled,
    Color? seedColor,
  }) =>
      AppSettings(
        themeMode: themeMode ?? this.themeMode,
        locale: locale ?? this.locale,
        pushEnabled: pushEnabled ?? this.pushEnabled,
        seedColor: seedColor ?? this.seedColor,
      );
}

class SettingsNotifier extends Notifier<AppSettings> {
  static const _kTheme = 'settings.theme_mode';
  static const _kLocale = 'settings.locale';
  static const _kPush = 'settings.push_enabled';
  static const _kSeed = 'settings.seed_color';

  @override
  AppSettings build() {
    _loadFromPrefs();
    return const AppSettings();
  }

  Future<void> _loadFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    final themeIdx = prefs.getInt(_kTheme);
    final loc = prefs.getString(_kLocale);
    final push = prefs.getBool(_kPush);
    final seed = prefs.getInt(_kSeed);

    state = state.copyWith(
      themeMode: themeIdx != null ? ThemeMode.values[themeIdx] : null,
      locale: loc != null ? Locale(loc) : null,
      pushEnabled: push,
      seedColor: seed != null ? Color(seed) : null,
    );
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    state = state.copyWith(themeMode: mode);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_kTheme, mode.index);
  }

  Future<void> setLocale(Locale? locale) async {
    state = state.copyWith(locale: locale);
    final prefs = await SharedPreferences.getInstance();
    if (locale == null) {
      await prefs.remove(_kLocale);
    } else {
      await prefs.setString(_kLocale, locale.languageCode);
    }
  }

  Future<void> setPushEnabled(bool enabled) async {
    state = state.copyWith(pushEnabled: enabled);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_kPush, enabled);
  }

  /// Branding klubu — kolor z dashboard/profilu klubu. Zwykle ustawiane
  /// po loginie z danych z API.
  Future<void> setSeedColor(Color color) async {
    state = state.copyWith(seedColor: color);
    final prefs = await SharedPreferences.getInstance();
    // ignore: deprecated_member_use
    await prefs.setInt(_kSeed, color.value);
  }
}

final settingsProvider =
    NotifierProvider<SettingsNotifier, AppSettings>(SettingsNotifier.new);
