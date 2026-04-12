import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/api_client.dart';
import '../config/api_config.dart';
import '../models/event.dart';
import '../widgets/stat_card.dart';
import '../widgets/event_tile.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  List<SportEvent> _upcomingEvents = [];
  double _totalPayments = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final eventsResp = await ApiClient.get(ApiConfig.eventsUpcomingUrl, queryParams: {'limit': '5'});
      final paymentResp = await ApiClient.get(ApiConfig.paymentsSummaryUrl);

      setState(() {
        _upcomingEvents = (eventsResp['data'] as List? ?? [])
            .map((e) => SportEvent.fromJson(e))
            .toList();
        _totalPayments = double.tryParse(paymentResp['data']?['total']?.toString() ?? '0') ?? 0;
        _loading = false;
      });
    } catch (e) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthService>(context);
    final member = auth.member!;
    final club = auth.club;

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Welcome card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Witaj, ${member.firstName}!',
                      style: Theme.of(context).textTheme.headlineSmall),
                  if (club != null) ...[
                    const SizedBox(height: 4),
                    Text('${club.name}${club.city != null ? " • ${club.city}" : ""}',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey)),
                  ],
                  const SizedBox(height: 4),
                  Text('#${member.memberNumber} • ${member.status}',
                      style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Stats row
          Row(
            children: [
              Expanded(
                child: StatCard(
                  icon: Icons.payments_outlined,
                  label: 'Wpłaty ${DateTime.now().year}',
                  value: '${_totalPayments.toStringAsFixed(2)} zł',
                  color: Colors.green,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: StatCard(
                  icon: Icons.calendar_month,
                  label: 'Nadchodzące',
                  value: '${_upcomingEvents.length}',
                  color: Colors.blue,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Upcoming events
          Text('Nadchodzące wydarzenia',
              style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          if (_loading)
            const Center(child: CircularProgressIndicator())
          else if (_upcomingEvents.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: Text('Brak nadchodzących wydarzeń', style: TextStyle(color: Colors.grey))),
              ),
            )
          else
            ..._upcomingEvents.map((e) => EventTile(event: e)),
        ],
      ),
    );
  }
}
