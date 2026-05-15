import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../localization/app_localizations.dart';
import '../../auth/data/auth_repository.dart';
import '../data/settings_repository.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(settingsProvider);
    final notifier = ref.read(settingsProvider.notifier);

    return Scaffold(
      appBar: AppBar(title: Text(context.tr('settings_title'))),
      body: ListView(
        children: [
          ListTile(
            title: Text(context.tr('settings_theme')),
            subtitle: Text(_themeLabel(context, settings.themeMode)),
            leading: const Icon(Icons.palette_outlined),
            onTap: () => _pickTheme(context, notifier, settings.themeMode),
          ),
          ListTile(
            title: Text(context.tr('settings_language')),
            subtitle: Text(
              settings.locale?.languageCode == 'en'
                  ? context.tr('settings_language_en')
                  : context.tr('settings_language_pl'),
            ),
            leading: const Icon(Icons.language_outlined),
            onTap: () => _pickLocale(context, notifier, settings.locale),
          ),
          SwitchListTile(
            title: Text(context.tr('settings_notifications')),
            secondary: const Icon(Icons.notifications_outlined),
            value: settings.pushEnabled,
            onChanged: notifier.setPushEnabled,
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: Text(context.tr('settings_about')),
            subtitle: Text('${context.tr('settings_version')} 0.1.0'),
          ),
          const Divider(),
          ListTile(
            leading: Icon(Icons.logout,
                color: Theme.of(context).colorScheme.error),
            title: Text(
              context.tr('settings_logout'),
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
            onTap: () async {
              await ref.read(authStateProvider.notifier).logout();
            },
          ),
        ],
      ),
    );
  }

  String _themeLabel(BuildContext context, ThemeMode mode) => switch (mode) {
        ThemeMode.system => context.tr('settings_theme_system'),
        ThemeMode.light => context.tr('settings_theme_light'),
        ThemeMode.dark => context.tr('settings_theme_dark'),
      };

  Future<void> _pickTheme(
    BuildContext context,
    SettingsNotifier notifier,
    ThemeMode current,
  ) async {
    final pick = await showModalBottomSheet<ThemeMode>(
      context: context,
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            for (final mode in ThemeMode.values)
              RadioListTile<ThemeMode>(
                value: mode,
                groupValue: current,
                title: Text(_themeLabel(context, mode)),
                onChanged: (v) => Navigator.of(context).pop(v),
              ),
          ],
        ),
      ),
    );
    if (pick != null) notifier.setThemeMode(pick);
  }

  Future<void> _pickLocale(
    BuildContext context,
    SettingsNotifier notifier,
    Locale? current,
  ) async {
    final pick = await showModalBottomSheet<Locale?>(
      context: context,
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              value: 'pl',
              groupValue: current?.languageCode ?? 'pl',
              title: Text(context.tr('settings_language_pl')),
              onChanged: (_) =>
                  Navigator.of(context).pop(const Locale('pl')),
            ),
            RadioListTile<String>(
              value: 'en',
              groupValue: current?.languageCode ?? 'pl',
              title: Text(context.tr('settings_language_en')),
              onChanged: (_) =>
                  Navigator.of(context).pop(const Locale('en')),
            ),
          ],
        ),
      ),
    );
    if (pick != null) notifier.setLocale(pick);
  }
}
