import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../../../shared/widgets/empty_view.dart';
import '../data/trainings_api.dart';
import '../domain/training.dart';

class TrainingsListScreen extends ConsumerWidget {
  const TrainingsListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(trainingsListProvider);

    return Scaffold(
      appBar: AppBar(title: Text(context.tr('trainings_title'))),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(trainingsListProvider.future),
        child: AsyncValueView<List<Training>>(
          value: value,
          onRetry: () => ref.invalidate(trainingsListProvider),
          isEmpty: (l) => l.isEmpty,
          empty: EmptyView(
            message: context.tr('trainings_empty'),
            icon: Icons.event_busy,
          ),
          data: (list) => _GroupedList(items: list),
        ),
      ),
    );
  }
}

class _GroupedList extends StatelessWidget {
  const _GroupedList({required this.items});
  final List<Training> items;

  @override
  Widget build(BuildContext context) {
    final groups = <String, List<Training>>{};
    final dateFmt = DateFormat.yMMMMEEEEd('pl_PL');
    for (final t in items) {
      final key = dateFmt.format(t.startsAt);
      groups.putIfAbsent(key, () => []).add(t);
    }
    final keys = groups.keys.toList();

    return ListView.builder(
      padding: const EdgeInsets.only(bottom: 24),
      itemCount: keys.length,
      itemBuilder: (context, idx) {
        final key = keys[idx];
        final group = groups[key]!;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding:
                  const EdgeInsets.fromLTRB(16, 16, 16, 8),
              child: Text(
                key,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      color: Theme.of(context).colorScheme.primary,
                    ),
              ),
            ),
            for (final t in group) _TrainingTile(training: t),
          ],
        );
      },
    );
  }
}

class _TrainingTile extends StatelessWidget {
  const _TrainingTile({required this.training});
  final Training training;

  @override
  Widget build(BuildContext context) {
    final timeFmt = DateFormat.Hm();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      child: Card(
        child: ListTile(
          leading: CircleAvatar(
            backgroundColor: Theme.of(context)
                .colorScheme
                .primaryContainer,
            child: Icon(
              Icons.fitness_center,
              color: Theme.of(context).colorScheme.onPrimaryContainer,
            ),
          ),
          title: Text(training.title),
          subtitle: Text(
            '${timeFmt.format(training.startsAt)} – ${timeFmt.format(training.endsAt)}'
            '${training.location != null ? ' • ${training.location}' : ''}',
          ),
          trailing: _RsvpBadge(status: training.rsvp),
          onTap: () => context.push('/trainings/${training.id}'),
        ),
      ),
    );
  }
}

class _RsvpBadge extends StatelessWidget {
  const _RsvpBadge({required this.status});
  final RsvpStatus status;

  @override
  Widget build(BuildContext context) {
    if (status == RsvpStatus.unknown) {
      return const Icon(Icons.chevron_right);
    }
    final (icon, color) = switch (status) {
      RsvpStatus.yes => (Icons.check_circle, Colors.green),
      RsvpStatus.maybe => (Icons.help, Colors.orange),
      RsvpStatus.no => (Icons.cancel, Colors.red),
      RsvpStatus.unknown => (Icons.help_outline, Colors.grey),
    };
    return Icon(icon, color: color);
  }
}
