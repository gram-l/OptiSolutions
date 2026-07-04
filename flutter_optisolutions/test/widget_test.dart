// widget_test.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_optisolutions/main.dart';

void main() {
  testWidgets('Polyclinic app smoke test', (WidgetTester tester) async {
    // FIX: was MyApp (undefined). The correct class name is PolyclinicApp.
    await tester.pumpWidget(const PolyclinicApp());
    expect(find.text('Polyclinic'), findsWidgets);
  });
}
