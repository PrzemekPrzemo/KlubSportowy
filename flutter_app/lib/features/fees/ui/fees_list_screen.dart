import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../../../shared/widgets/empty_view.dart';
import '../data/fees_api.dart';
import '../domain/fee.dart';

class FeesListScreen extends ConsumerWidget {
  const FeesListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(feesListProvider);

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: Text(context.tr('fees_title')),
          bottom: TabBar(
            tabs: [
              Tab(text: context.tr('fees_tab_overdue')),
              Tab(text: context.tr('fees_tab_pending')),
              Tab(text: context.tr('fees_tab_paid')),
            ],
          ),
        ),
        body: RefreshIndicator(
          onRefresh: () => ref.refresh(feesListProvider.future),
          child: AsyncValueView<List<Fee>>(
            value: value,
            onRetry: () => ref.invalidate(feesListProvider),
            data: (list) => TabBarView(
              children: [
                _FeesTab(
                  items: list.where((f) => f.status == FeeStatus.overdue).toList(),
                ),
                _FeesTab(
                  items: list.where((f) => f.status == FeeStatus.pending).toList(),
                ),
                _FeesTab(
                  items: list.where((f) => f.status == FeeStatus.paid).toList(),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _FeesTab extends StatelessWidget {
  const _FeesTab({required this.items});
  final List<Fee> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return EmptyView(
        message: context.tr('fees_empty'),
        icon: Icons.receipt_long_outlined,
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (_, i) => _FeeCard(fee: items[i]),
    );
  }
}

class _FeeCard extends StatelessWidget {
  const _FeeCard({required this.fee});
  final Fee fee;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat.currency(locale: 'pl_PL', symbol: fee.currency);
    final dateFmt = DateFormat.yMMMd('pl_PL');
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => context.push('/fees/${fee.id}'),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      fee.title,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  _StatusBadge(status: fee.status),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Text(
                    fmt.format(fee.amount),
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const Spacer(),
                  Text(
                    context.tr('fees_due', {'date': dateFmt.format(fee.dueDate)}),
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});
  final FeeStatus status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = switch (status) {
      FeeStatus.overdue => ('Zaległa', Colors.red),
      FeeStatus.pending => ('Do zapłaty', Colors.orange),
      FeeStatus.paid => ('Opłacona', Colors.green),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontWeight: FontWeight.w600),
      ),
    );
  }
}
