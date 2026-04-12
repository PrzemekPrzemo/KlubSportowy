import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../config/api_config.dart';
import '../models/payment.dart';

class FeesScreen extends StatefulWidget {
  const FeesScreen({super.key});

  @override
  State<FeesScreen> createState() => _FeesScreenState();
}

class _FeesScreenState extends State<FeesScreen> {
  List<Payment> _payments = [];
  double _total = 0;
  int _year = DateTime.now().year;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final resp = await ApiClient.get(ApiConfig.paymentsUrl, queryParams: {'year': '$_year', 'page': '1'});
      final summResp = await ApiClient.get(ApiConfig.paymentsSummaryUrl);
      setState(() {
        _payments = (resp['data'] as List? ?? []).map((p) => Payment.fromJson(p)).toList();
        _total = double.tryParse(summResp['data']?['total']?.toString() ?? '0') ?? 0;
        _loading = false;
      });
    } catch (e) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Summary card
          Card(
            color: Theme.of(context).colorScheme.primaryContainer,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Text('Wpłaty $_year', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  Text('${_total.toStringAsFixed(2)} zł',
                      style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      IconButton(
                        onPressed: () { setState(() { _year--; }); _load(); },
                        icon: const Icon(Icons.chevron_left),
                      ),
                      Text('$_year', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      IconButton(
                        onPressed: () { setState(() { _year++; }); _load(); },
                        icon: const Icon(Icons.chevron_right),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator()))
          else if (_payments.isEmpty)
            const Card(child: Padding(padding: EdgeInsets.all(24), child: Center(child: Text('Brak wpłat w tym roku.'))))
          else
            ..._payments.map((p) => Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                leading: CircleAvatar(
                  radius: 18,
                  backgroundColor: Colors.green.withValues(alpha: 0.1),
                  child: const Icon(Icons.payments, size: 18, color: Colors.green),
                ),
                title: Text(p.feeName ?? 'Opłata', maxLines: 1, overflow: TextOverflow.ellipsis),
                subtitle: Text('${p.paymentDate} • ${p.method}', style: const TextStyle(fontSize: 12)),
                trailing: Text('${p.amount.toStringAsFixed(2)} zł',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            )),
        ],
      ),
    );
  }
}
