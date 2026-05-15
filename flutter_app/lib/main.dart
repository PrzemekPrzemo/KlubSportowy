import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'app.dart';
import 'core/firebase_bootstrap.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // Inicjalizacja danych lokalizacyjnych dla `intl` (DateFormat z 'pl_PL').
  await initializeDateFormatting('pl_PL');
  await initializeDateFormatting('en_US');

  // Best-effort Firebase init (no-op when not configured)
  await FirebaseBootstrap.initialize();

  runApp(const ProviderScope(child: ClubDeskApp()));
}
