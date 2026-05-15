import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'config/theme.dart';
import 'features/settings/data/settings_repository.dart';
import 'localization/app_localizations.dart';
import 'routing/app_router.dart';

class ClubDeskApp extends ConsumerWidget {
  const ClubDeskApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    final settings = ref.watch(settingsProvider);

    return MaterialApp.router(
      title: 'ClubDesk',
      debugShowCheckedModeBanner: false,
      routerConfig: router,
      theme: AppTheme.light(settings.seedColor),
      darkTheme: AppTheme.dark(settings.seedColor),
      themeMode: settings.themeMode,
      locale: settings.locale,
      supportedLocales: const [Locale('pl'), Locale('en')],
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
    );
  }
}
