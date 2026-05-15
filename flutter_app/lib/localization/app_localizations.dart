import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:flutter/widgets.dart';

/// Lekki helper l10n: ładuje JSON z `lib/localization/app_<lang>.json`.
/// Nie używamy `intl_translation` żeby uprościć build pipeline.
class AppLocalizations {
  AppLocalizations._(this.locale, this._strings);

  final Locale locale;
  final Map<String, String> _strings;

  static AppLocalizations of(BuildContext context) =>
      Localizations.of<AppLocalizations>(context, AppLocalizations)!;

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// Pobiera tłumaczenie. Wspiera proste interpolacje `{name}`.
  String t(String key, [Map<String, Object?>? args]) {
    var value = _strings[key] ?? key;
    if (args != null) {
      args.forEach((k, v) {
        value = value.replaceAll('{$k}', v?.toString() ?? '');
      });
    }
    return value;
  }

  static Future<AppLocalizations> _load(Locale locale) async {
    final fileName = 'lib/localization/app_${locale.languageCode}.json';
    String jsonStr;
    try {
      jsonStr = await rootBundle.loadString(fileName);
    } catch (_) {
      // Fallback: jeśli nie ma pliku, zwróć pustą mapę (klucze będą echo).
      if (kDebugMode) {
        // ignore: avoid_print
        print('[i18n] missing $fileName, falling back to keys');
      }
      jsonStr = '{}';
    }
    final map = (json.decode(jsonStr) as Map<String, dynamic>).map(
      (k, v) => MapEntry(k, v.toString()),
    );
    return AppLocalizations._(locale, map);
  }
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) =>
      ['pl', 'en'].contains(locale.languageCode);

  @override
  Future<AppLocalizations> load(Locale locale) =>
      AppLocalizations._load(locale);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

/// Skrót żeby nie pisać `AppLocalizations.of(context).t(...)`.
extension L10nX on BuildContext {
  String tr(String key, [Map<String, Object?>? args]) =>
      AppLocalizations.of(this).t(key, args);
}
