import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/domain/auth_state.dart';
import '../data/dashboard_api.dart';
import '../domain/dashboard_state.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dash = ref.watch(dashboardProvider);
    final auth = ref.watch(authStateProvider);
    final memberName =
        auth is Authenticated ? auth.member.firstName : 'ClubDesk';

    return Scaffold(
      appBar: AppBar(
        title: Text('Cześć, $memberName!'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => context.push('/notifications'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(dashboardProvider.future),
        child: AsyncValueView<DashboardData>(
          value: dash,
          onRetry: () => ref.invalidate(dashboardProvider),
          data: (data) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _TodaySection(trainings: data.todayTrainings),
              const SizedBox(height: 12),
              _FeesSection(
                count: data.overdueFeesCount,
                amount: data.overdueFeesAmount,
                currency: data.overdueFeesCurrency,
              ),
              const SizedBox(height: 12),
              _NotificationsSection(
                unread: data.unreadNotifications,
                lastHeadline: data.lastNotificationHeadline,
              ),
              if (data.statsLines.isNotEmpty) ...[
                const SizedBox(height: 12),
                _StatsSection(lines: data.statsLines),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.icon,
    required this.child,
    this.onTap,
    this.trailing,
  });

  final String title;
  final IconData icon;
  final Widget child;
  final VoidCallback? onTap;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Icon(icon, color: scheme.primary, size: 20),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      title,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  if (trailing != null) trailing!,
                ],
              ),
              const SizedBox(height: 12),
              child,
            ],
          ),
        ),
      ),
    );
  }
}

class _TodaySection extends StatelessWidget {
  const _TodaySection({required this.trainings});
  final List<DashboardTraining> trainings;

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: context.tr('dashboard_today'),
      icon: Icons.today_outlined,
      onTap: () => context.go('/trainings'),
      child: trainings.isEmpty
          ? Text(
              context.tr('dashboard_no_trainings'),
              style: Theme.of(context).textTheme.bodyMedium,
            )
          : Column(
              children: [
                for (final t in trainings.take(3))
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    dense: true,
                    leading: const Icon(Icons.fitness_center),
                    title: Text(t.title),
                    subtitle: Text(
                      '${DateFormat.Hm().format(t.startsAt)} • ${t.location ?? ''}',
                    ),
                    onTap: () => context.push('/trainings/${t.id}'),
                  ),
              ],
            ),
    );
  }
}

class _FeesSection extends StatelessWidget {
  const _FeesSection({
    required this.count,
    required this.amount,
    required this.currency,
  });

  final int count;
  final double amount;
  final String currency;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat.currency(locale: 'pl_PL', symbol: currency);
    return _SectionCard(
      title: context.tr('dashboard_fees'),
      icon: Icons.payments_outlined,
      onTap: () => context.go('/fees'),
      trailing: count > 0
          ? FilledButton.tonal(
              onPressed: () => context.go('/fees'),
              child: Text(context.tr('dashboard_fees_pay')),
            )
          : null,
      child: Text(
        count > 0
            ? context.tr('dashboard_fees_overdue', {
                'count': count,
                'amount': fmt.format(amount),
              })
            : 'Wszystko opłacone',
        style: Theme.of(context).textTheme.bodyLarge,
      ),
    );
  }
}

class _NotificationsSection extends StatelessWidget {
  const _NotificationsSection({
    required this.unread,
    required this.lastHeadline,
  });

  final int unread;
  final String? lastHeadline;

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: context.tr('dashboard_notifications'),
      icon: Icons.notifications_outlined,
      onTap: () => context.push('/notifications'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            context.tr('dashboard_notifications_unread', {'count': unread}),
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          if (lastHeadline != null) ...[
            const SizedBox(height: 4),
            Text(
              lastHeadline!,
              style: Theme.of(context).textTheme.bodyLarge,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ],
      ),
    );
  }
}

class _StatsSection extends StatelessWidget {
  const _StatsSection({required this.lines});
  final List<String> lines;

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: context.tr('dashboard_stats'),
      icon: Icons.bar_chart_outlined,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          for (final line in lines)
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Text(line),
            ),
        ],
      ),
    );
  }
}
