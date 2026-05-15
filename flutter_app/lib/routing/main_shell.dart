import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../localization/app_localizations.dart';

/// Bottom navigation + drawer shell dla zalogowanych ekranów.
class MainShell extends StatelessWidget {
  const MainShell({super.key, required this.child});
  final Widget child;

  static const _tabs = <_TabInfo>[
    _TabInfo('/dashboard', Icons.home_outlined, Icons.home, 'tab_dashboard'),
    _TabInfo('/trainings', Icons.sports_outlined, Icons.sports,
        'tab_trainings'),
    _TabInfo('/fees', Icons.payments_outlined, Icons.payments, 'tab_fees'),
    _TabInfo('/profile', Icons.person_outline, Icons.person, 'tab_profile'),
  ];

  int _currentIndex(BuildContext context) {
    final loc = GoRouterState.of(context).matchedLocation;
    for (var i = 0; i < _tabs.length; i++) {
      if (loc.startsWith(_tabs[i].path)) return i;
    }
    return 0;
  }

  @override
  Widget build(BuildContext context) {
    final idx = _currentIndex(context);
    return Scaffold(
      drawer: const _AppDrawer(),
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: idx,
        onDestinationSelected: (i) => context.go(_tabs[i].path),
        destinations: [
          for (final t in _tabs)
            NavigationDestination(
              icon: Icon(t.icon),
              selectedIcon: Icon(t.selectedIcon),
              label: context.tr(t.labelKey),
            ),
        ],
      ),
    );
  }
}

class _TabInfo {
  const _TabInfo(this.path, this.icon, this.selectedIcon, this.labelKey);
  final String path;
  final IconData icon;
  final IconData selectedIcon;
  final String labelKey;
}

class _AppDrawer extends StatelessWidget {
  const _AppDrawer();

  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: SafeArea(
        child: ListView(
          padding: const EdgeInsets.symmetric(vertical: 8),
          children: [
            ListTile(
              leading: const Icon(Icons.event_outlined),
              title: Text(context.tr('events_title')),
              onTap: () {
                Navigator.of(context).pop();
                context.push('/events');
              },
            ),
            ListTile(
              leading: const Icon(Icons.notifications_outlined),
              title: Text(context.tr('notifications_title')),
              onTap: () {
                Navigator.of(context).pop();
                context.push('/notifications');
              },
            ),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.settings_outlined),
              title: Text(context.tr('settings_title')),
              onTap: () {
                Navigator.of(context).pop();
                context.push('/settings');
              },
            ),
          ],
        ),
      ),
    );
  }
}
