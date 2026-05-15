import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/exceptions.dart';
import '../../../localization/app_localizations.dart';
import '../../auth/data/auth_api.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/domain/auth_state.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() =>
      _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _phoneCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    final auth = ref.read(authStateProvider);
    if (auth is Authenticated) {
      _phoneCtrl.text = auth.member.phone ?? '';
      _addressCtrl.text = auth.member.address ?? '';
    }
  }

  @override
  void dispose() {
    _phoneCtrl.dispose();
    _addressCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final auth = ref.read(authStateProvider);
    if (auth is! Authenticated) return;

    setState(() => _submitting = true);
    try {
      final updated = await ref.read(authApiProvider).updateProfile(
            memberId: auth.member.id,
            phone: _phoneCtrl.text.trim(),
            address: _addressCtrl.text.trim(),
          );
      ref.read(authStateProvider.notifier).updateMember(updated);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(context.tr('profile_saved'))),
      );
      if (context.canPop()) context.pop();
    } on AppException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(context.tr('profile_edit'))),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextField(
              controller: _phoneCtrl,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: context.tr('profile_phone'),
                prefixIcon: const Icon(Icons.phone_outlined),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _addressCtrl,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: context.tr('profile_address'),
                prefixIcon: const Icon(Icons.home_outlined),
              ),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _submitting ? null : _save,
              child: _submitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(context.tr('profile_save')),
            ),
          ],
        ),
      ),
    );
  }
}
