import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'services/auth_service.dart';
import 'screens/login_screen.dart';
import 'screens/main_screen.dart';

void main() {
  runApp(const ClubDeskApp());
}

class ClubDeskApp extends StatelessWidget {
  const ClubDeskApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthService()..init(),
      child: MaterialApp(
        title: 'ClubDesk',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorSchemeSeed: const Color(0xFFEE2C28),
          useMaterial3: true,
          fontFamily: 'Poppins',
        ),
        darkTheme: ThemeData(
          colorSchemeSeed: const Color(0xFFEE2C28),
          brightness: Brightness.dark,
          useMaterial3: true,
          fontFamily: 'Poppins',
        ),
        themeMode: ThemeMode.system,
        home: Consumer<AuthService>(
          builder: (context, auth, _) {
            if (auth.isAuthenticated) {
              return const MainScreen();
            }
            return const LoginScreen();
          },
        ),
      ),
    );
  }
}
