import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/exceptions.dart';
import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../data/fees_api.dart';
import '../domain/fee.dart';

class FeeDetailScreen extends ConsumerStatefulWidget {
  const FeeDetailScreen({super.key, required this.feeId});
  final int feeId;

  @override
  ConsumerState<FeeDetailScreen> createState() => _FeeDetailScreenState();
}

class _FeeDetailScreenState extends ConsumerState<FeeDetailScreen> {
  bool _launching = false;

  Future<void> _openCheckout(Fee fee) async {
    setState(() => _launching = true);
    try {
      final url = await ref.read(feesApiProvider).checkoutUrl(fee.id);
      final uri = Uri.parse(url);
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(context.tr('error_generic'))),
        );
      }
    } on AppException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _launching = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final list = ref.watch(feesListProvider);

    return Scaffold(
      appBar: AppBar(),
      body: AsyncValueView<List<Fee>>(
        value: list,
        data: (items) {
          final fee = items.firstWhere(
            (f) => f.id == widget.feeId,
            orElse: () => items.first,
          );
          final fmt = NumberFormat.currency(
              locale: 'pl_PL', symbol: fee.currency);
          final dateFmt = DateFormat.yMMMMd('pl_PL');

          return Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  fee.title,
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 8),
                Text(
                  fmt.format(fee.amount),
                  style: Theme.of(context).textTheme.displaySmall,
                ),
                const SizedBox(height: 12),
                Text(
                  context.tr('fees_due', {'date': dateFmt.format(fee.dueDate)}),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                if (fee.description != null) ...[
                  const SizedBox(height: 16),
                  Text(fee.description!),
                ],
                const Spacer(),
                if (fee.status != FeeStatus.paid)
                  FilledButton.icon(
                    onPressed: _launching ? null : () => _openCheckout(fee),
                    icon: const Icon(Icons.payment),
                    label: Text(context.tr('fees_pay')),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
