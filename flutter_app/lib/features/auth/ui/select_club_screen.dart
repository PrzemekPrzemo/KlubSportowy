import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/exceptions.dart';
import '../../../localization/app_localizations.dart';
import '../data/auth_repository.dart';
import '../domain/auth_state.dart';

class SelectClubScreen extends ConsumerWidget {
  const SelectClubScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(authStateProvider);
    final clubs = state is MultiClubChoice ? state.clubs : const [];

    return Scaffold(
      appBar: AppBar(title: Text(context.tr('select_club_title'))),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                context.tr('select_club_subtitle'),
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 16),
              Expanded(
                child: ListView.separated(
                  itemCount: clubs.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, i) {
                    final club = clubs[i];
                    return Card(
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundImage: club.logoUrl != null
                              ? NetworkImage(club.logoUrl!)
                              : null,
                          child: club.logoUrl == null
                              ? Text(club.name.substring(0, 1))
                              : null,
                        ),
                        title: Text(club.name),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () async {
                          try {
                            await ref
                                .read(authStateProvider.notifier)
                                .selectClub(club.id);
                          } on AppException catch (e) {
                            if (!context.mounted) return;
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(e.message)),
                            );
                          }
                        },
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
