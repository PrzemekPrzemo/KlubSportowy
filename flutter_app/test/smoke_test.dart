import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:clubdesk/app.dart';

void main() {
  testWidgets('app boots without crashing in mock mode', (tester) async {
    await tester.pumpWidget(
      const ProviderScope(child: ClubDeskApp()),
    );
    // Splash → po restore z storage powinno przejść na /login.
    await tester.pump(const Duration(milliseconds: 100));
    // Smoke: cokolwiek się wyrenderowało.
    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
