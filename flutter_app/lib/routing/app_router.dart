import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/data/auth_repository.dart';
import '../features/auth/domain/auth_state.dart';
import '../features/auth/ui/forgot_password_screen.dart';
import '../features/auth/ui/login_screen.dart';
import '../features/auth/ui/select_club_screen.dart';
import '../features/dashboard/ui/dashboard_screen.dart';
import '../features/events/ui/events_list_screen.dart';
import '../features/fees/ui/fee_detail_screen.dart';
import '../features/fees/ui/fees_list_screen.dart';
import '../features/notifications/ui/notifications_screen.dart';
import '../features/profile/ui/edit_profile_screen.dart';
import '../features/profile/ui/profile_screen.dart';
import '../features/settings/ui/settings_screen.dart';
import '../features/trainings/ui/training_detail_screen.dart';
import '../features/trainings/ui/trainings_list_screen.dart';
import '../shared/widgets/loading_view.dart';
import 'main_shell.dart';

/// Klucz refresh notifiera — go_router odświeża guardy gdy auth się zmieni.
class _GoRouterRefresh extends ChangeNotifier {
  _GoRouterRefresh(this.ref) {
    ref.listen<AuthState>(authStateProvider, (_, __) => notifyListeners());
  }
  final Ref ref;
}

final _rootNavigatorKey = GlobalKey<NavigatorState>();
final _shellNavigatorKey = GlobalKey<NavigatorState>();

final appRouterProvider = Provider<GoRouter>((ref) {
  final refresh = _GoRouterRefresh(ref);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    navigatorKey: _rootNavigatorKey,
    initialLocation: '/dashboard',
    refreshListenable: refresh,
    redirect: (context, state) {
      final auth = ref.read(authStateProvider);
      final loc = state.matchedLocation;
      final goingToLogin = loc.startsWith('/login') ||
          loc.startsWith('/forgot') ||
          loc.startsWith('/select-club');

      return switch (auth) {
        AuthLoading() => loc == '/splash' ? null : '/splash',
        Unauthenticated() => goingToLogin ? null : '/login',
        MultiClubChoice() =>
          loc == '/select-club' ? null : '/select-club',
        Authenticated() => goingToLogin || loc == '/splash'
            ? '/dashboard'
            : null,
      };
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (_, __) => const Scaffold(body: LoadingView()),
      ),
      GoRoute(
        path: '/login',
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: '/forgot',
        builder: (_, __) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/select-club',
        builder: (_, __) => const SelectClubScreen(),
      ),
      ShellRoute(
        navigatorKey: _shellNavigatorKey,
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          GoRoute(
            path: '/dashboard',
            builder: (_, __) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/trainings',
            builder: (_, __) => const TrainingsListScreen(),
            routes: [
              GoRoute(
                path: ':id',
                parentNavigatorKey: _rootNavigatorKey,
                builder: (_, state) => TrainingDetailScreen(
                  trainingId: int.parse(state.pathParameters['id']!),
                ),
              ),
            ],
          ),
          GoRoute(
            path: '/fees',
            builder: (_, __) => const FeesListScreen(),
            routes: [
              GoRoute(
                path: ':id',
                parentNavigatorKey: _rootNavigatorKey,
                builder: (_, state) => FeeDetailScreen(
                  feeId: int.parse(state.pathParameters['id']!),
                ),
              ),
            ],
          ),
          GoRoute(
            path: '/profile',
            builder: (_, __) => const ProfileScreen(),
            routes: [
              GoRoute(
                path: 'edit',
                parentNavigatorKey: _rootNavigatorKey,
                builder: (_, __) => const EditProfileScreen(),
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        path: '/events',
        builder: (_, __) => const EventsListScreen(),
      ),
      GoRoute(
        path: '/notifications',
        builder: (_, __) => const NotificationsScreen(),
      ),
      GoRoute(
        path: '/settings',
        builder: (_, __) => const SettingsScreen(),
      ),
    ],
  );
});
