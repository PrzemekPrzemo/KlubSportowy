import 'package:flutter/material.dart';
import '../models/event.dart';

class EventTile extends StatelessWidget {
  final SportEvent event;
  const EventTile({super.key, required this.event});

  @override
  Widget build(BuildContext context) {
    final typeColor = switch (event.type) {
      'mecz' => Colors.red,
      'zawody' => Colors.blue,
      'trening' => Colors.green,
      'turniej' => Colors.orange,
      _ => Colors.grey,
    };

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: typeColor.withValues(alpha: 0.1),
          child: Icon(_typeIcon(event.type), color: typeColor, size: 20),
        ),
        title: Text(event.name, maxLines: 1, overflow: TextOverflow.ellipsis),
        subtitle: Text(
          '${event.eventDate.substring(0, 16).replaceAll('T', ' ')}'
          '${event.location != null ? " • ${event.location}" : ""}'
          '${event.sportName != null ? " • ${event.sportName}" : ""}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 12),
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: typeColor.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(event.type, style: TextStyle(color: typeColor, fontSize: 11, fontWeight: FontWeight.w600)),
        ),
      ),
    );
  }

  IconData _typeIcon(String type) => switch (type) {
    'mecz' => Icons.sports_soccer,
    'zawody' => Icons.emoji_events,
    'trening' => Icons.fitness_center,
    'turniej' => Icons.flag,
    _ => Icons.event,
  };
}
