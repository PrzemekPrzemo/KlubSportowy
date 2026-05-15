import 'package:flutter/foundation.dart';

/// Stub-bootstrap. Prawdziwy `Firebase.initializeApp()` wymaga
/// platformowych plików konfiguracyjnych (`google-services.json`,
/// `GoogleService-Info.plist`), które są per-projekt. Patrz README.md.
///
/// Trzymamy to w osobnym pliku żeby push można było dorzucić bez
/// dotykania `main.dart`.
class FirebaseBootstrap {
  FirebaseBootstrap._();

  static Future<void> initialize() async {
    // TODO(maintainer): po dodaniu plików konfiguracyjnych:
    //   await Firebase.initializeApp(
    //     options: DefaultFirebaseOptions.currentPlatform,
    //   );
    //   await FirebaseMessaging.instance.requestPermission();
    if (kDebugMode) {
      // ignore: avoid_print
      print('[FirebaseBootstrap] skipped — add google-services.json to enable');
    }
  }
}
