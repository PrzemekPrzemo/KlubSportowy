import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../../../shared/widgets/empty_view.dart';
import '../data/events_api.dart';
import '../domain/event.dart';

class EventsListScreen extends ConsumerWidget {
  const EventsListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(eventsListProvider);

    return Scaffold(
      appBar: AppBar(title: Text(context.tr('events_title'))),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(eventsListProvider.future),
        child: AsyncValueView<List<ClubEvent>>(
          value: value,
          onRetry: () => ref.invalidate(eventsListProvider),
          isEmpty: (l) => l.isEmpty,
          empty: EmptyView(
            message: context.tr('events_empty'),
            icon: Icons.event_busy,
          ),
          data: (list) => ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: list.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (_, i) => _EventCard(event: list[i]),
          ),
        ),
      ),
    );
  }
}

class _EventCard extends StatelessWidget {
  const _EventCard({required this.event});
  final ClubEvent event;

  @override
  Widget build(BuildContext context) {
    final fmt = DateFormat.yMMMMEEEEd('pl_PL').add_Hm();
    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.tertiaryContainer,
          child: Icon(
            Icons.event,
            color: Theme.of(context).colorScheme.onTertiaryContainer,
          ),
        ),
        title: Text(event.title),
        subtitle: Text(
          '${fmt.format(event.startsAt)}'
          '${event.location != null ? '\n${event.location}' : ''}',
        ),
        isThreeLine: event.location != null,
      ),
    );
  }
}
