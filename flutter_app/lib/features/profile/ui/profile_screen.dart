import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../localization/app_localizations.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/domain/auth_state.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authStateProvider);
    if (auth is! Authenticated) {
      return const Scaffold(body: SizedBox.shrink());
    }
    final m = auth.member;
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(context.tr('profile_title')),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined),
            onPressed: () => context.push('/settings'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(authStateProvider.notifier).refreshMe(),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Center(
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 48,
                    backgroundColor: scheme.primaryContainer,
                    backgroundImage: m.avatarUrl != null
                        ? NetworkImage(m.avatarUrl!)
                        : null,
                    child: m.avatarUrl == null
                        ? Text(
                            m.initials,
                            style: TextStyle(
                              fontSize: 28,
                              color: scheme.onPrimaryContainer,
                              fontWeight: FontWeight.bold,
                            ),
                          )
                        : null,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    m.fullName,
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  Text(
                    m.club.name,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: scheme.onSurface.withValues(alpha: 0.6),
                        ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Card(
              child: Column(
                children: [
                  if (m.evidenceNumber != null)
                    _Row(
                      icon: Icons.badge_outlined,
                      label: context.tr('profile_evidence_no'),
                      value: m.evidenceNumber!,
                    ),
                  if (m.sport != null)
                    _Row(
                      icon: Icons.sports_outlined,
                      label: context.tr('profile_sport'),
                      value: m.sport!,
                    ),
                  if (m.phone != null)
                    _Row(
                      icon: Icons.phone_outlined,
                      label: context.tr('profile_phone'),
                      value: m.phone!,
                    ),
                  if (m.address != null)
                    _Row(
                      icon: Icons.home_outlined,
                      label: context.tr('profile_address'),
                      value: m.address!,
                    ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              icon: const Icon(Icons.edit_outlined),
              label: Text(context.tr('profile_edit')),
              onPressed: () => context.push('/profile/edit'),
            ),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.icon, required this.label, required this.value});

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon),
      title: Text(label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context)
                    .colorScheme
                    .onSurface
                    .withValues(alpha: 0.6),
              )),
      subtitle: Text(value, style: Theme.of(context).textTheme.bodyLarge),
    );
  }
}
