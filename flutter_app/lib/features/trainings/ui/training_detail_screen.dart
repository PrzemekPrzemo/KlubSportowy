import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/exceptions.dart';
import '../../../localization/app_localizations.dart';
import '../../../shared/widgets/async_value_view.dart';
import '../data/trainings_api.dart';
import '../domain/training.dart';

class TrainingDetailScreen extends ConsumerStatefulWidget {
  const TrainingDetailScreen({super.key, required this.trainingId});
  final int trainingId;

  @override
  ConsumerState<TrainingDetailScreen> createState() =>
      _TrainingDetailScreenState();
}

class _TrainingDetailScreenState
    extends ConsumerState<TrainingDetailScreen> {
  bool _submittingRsvp = false;

  Future<void> _setRsvp(RsvpStatus status) async {
    setState(() => _submittingRsvp = true);
    try {
      await ref
          .read(trainingsApiProvider)
          .setRsvp(widget.trainingId, status);
      ref.invalidate(trainingDetailProvider(widget.trainingId));
      ref.invalidate(trainingsListProvider);
    } on AppException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _submittingRsvp = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final value = ref.watch(trainingDetailProvider(widget.trainingId));

    return Scaffold(
      appBar: AppBar(),
      body: AsyncValueView<Training>(
        value: value,
        onRetry: () =>
            ref.invalidate(trainingDetailProvider(widget.trainingId)),
        data: (t) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(t.title, style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 4),
            Text(
              '${DateFormat.yMMMMEEEEd('pl_PL').format(t.startsAt)} • '
              '${DateFormat.Hm().format(t.startsAt)}–${DateFormat.Hm().format(t.endsAt)}',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 16),
            if (t.location != null)
              _Row(
                icon: Icons.location_on_outlined,
                label: context.tr('training_location'),
                value: t.location!,
              ),
            if (t.coach != null)
              _Row(
                icon: Icons.person_outline,
                label: context.tr('training_coach'),
                value: t.coach!,
              ),
            _Row(
              icon: Icons.group_outlined,
              label: context.tr('training_participants'),
              value: '${t.participantsCount}',
            ),
            if (t.description != null && t.description!.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(t.description!),
            ],
            const SizedBox(height: 24),
            _RsvpButtons(
              current: t.rsvp,
              busy: _submittingRsvp,
              onSelect: _setRsvp,
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
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context)
                            .colorScheme
                            .onSurface
                            .withValues(alpha: 0.6),
                      ),
                ),
                Text(value, style: Theme.of(context).textTheme.bodyLarge),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RsvpButtons extends StatelessWidget {
  const _RsvpButtons({
    required this.current,
    required this.busy,
    required this.onSelect,
  });

  final RsvpStatus current;
  final bool busy;
  final ValueChanged<RsvpStatus> onSelect;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _btn(context, RsvpStatus.yes,
              context.tr('training_rsvp_yes'), Icons.check),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _btn(context, RsvpStatus.maybe,
              context.tr('training_rsvp_maybe'), Icons.help_outline),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _btn(context, RsvpStatus.no,
              context.tr('training_rsvp_no'), Icons.close),
        ),
      ],
    );
  }

  Widget _btn(BuildContext context, RsvpStatus s, String label, IconData icon) {
    final selected = current == s;
    return selected
        ? FilledButton.icon(
            onPressed: busy ? null : () => onSelect(s),
            icon: Icon(icon),
            label: Text(label),
          )
        : OutlinedButton.icon(
            onPressed: busy ? null : () => onSelect(s),
            icon: Icon(icon),
            label: Text(label),
          );
  }
}
