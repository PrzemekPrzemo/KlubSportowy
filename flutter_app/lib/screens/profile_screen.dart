import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthService>(context);
    final member = auth.member!;
    final club = auth.club;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Profile header
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 40,
                  backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                  child: Text(
                    '${member.firstName[0]}${member.lastName[0]}',
                    style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(height: 12),
                Text(member.fullName, style: Theme.of(context).textTheme.headlineSmall),
                Text('#${member.memberNumber}', style: Theme.of(context).textTheme.bodySmall),
                if (club != null) ...[
                  const SizedBox(height: 4),
                  Text(club.name, style: TextStyle(color: Colors.grey[600])),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Info cards
        Card(
          child: Column(
            children: [
              _InfoRow(icon: Icons.email, label: 'E-mail', value: member.email ?? '—'),
              const Divider(height: 1),
              _InfoRow(icon: Icons.phone, label: 'Telefon', value: member.phone ?? '—'),
              const Divider(height: 1),
              _InfoRow(icon: Icons.cake, label: 'Data urodzenia', value: member.birthDate ?? '—'),
              const Divider(height: 1),
              _InfoRow(icon: Icons.calendar_today, label: 'Data wstąpienia', value: member.joinDate),
              const Divider(height: 1),
              _InfoRow(icon: Icons.person, label: 'Płeć', value: member.gender == 'M' ? 'Mężczyzna' : member.gender == 'K' ? 'Kobieta' : '—'),
              const Divider(height: 1),
              _InfoRow(
                icon: Icons.check_circle,
                label: 'Status',
                value: member.status,
                valueColor: member.status == 'aktywny' ? Colors.green : Colors.orange,
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Logout button
        FilledButton.tonal(
          onPressed: () async {
            await auth.logout();
          },
          style: FilledButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
          ),
          child: const Text('Wyloguj się'),
        ),
      ],
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  const _InfoRow({required this.icon, required this.label, required this.value, this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey),
          const SizedBox(width: 12),
          Expanded(child: Text(label, style: const TextStyle(color: Colors.grey))),
          Text(value, style: TextStyle(fontWeight: FontWeight.w500, color: valueColor)),
        ],
      ),
    );
  }
}
