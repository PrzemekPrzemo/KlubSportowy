import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../../../shared/widgets/empty_view.dart';
import '../data/notifications_api.dart';
import '../domain/notification.dart';

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(notificationsListProvider);

    return Scaffold(
      appBar: AppBar(title: Text(context.tr('notifications_title'))),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(notificationsListProvider.future),
        child: AsyncValueView<List<AppNotification>>(
          value: value,
          onRetry: () => ref.invalidate(notificationsListProvider),
          isEmpty: (l) => l.isEmpty,
          empty: EmptyView(
            message: context.tr('notifications_empty'),
            icon: Icons.notifications_off_outlined,
          ),
          data: (list) => ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: list.length,
            separatorBuilder: (_, __) => const SizedBox(height: 4),
            itemBuilder: (_, i) {
              final n = list[i];
              return Dismissible(
                key: ValueKey('notif-${n.id}'),
                direction: DismissDirection.endToStart,
                background: Container(
                  alignment: Alignment.centerRight,
                  padding: const EdgeInsets.only(right: 24),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.error,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(Icons.delete, color: Colors.white),
                ),
                onDismissed: (_) {
                  ref.read(notificationsApiProvider).markRead(n.id);
                },
                child: Card(
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: n.read
                          ? Theme.of(context).colorScheme.surfaceContainerHigh
                          : Theme.of(context).colorScheme.primaryContainer,
                      child: Icon(
                        Icons.notifications,
                        color: n.read
                            ? Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: 0.5)
                            : Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                    ),
                    title: Text(
                      n.title,
                      style: TextStyle(
                        fontWeight:
                            n.read ? FontWeight.normal : FontWeight.w600,
                      ),
                    ),
                    subtitle: Text(
                      '${n.body}\n${DateFormat.yMMMd('pl_PL').add_Hm().format(n.createdAt)}',
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                    isThreeLine: true,
                    onTap: () {
                      ref.read(notificationsApiProvider).markRead(n.id);
                      ref.invalidate(notificationsListProvider);
                    },
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
