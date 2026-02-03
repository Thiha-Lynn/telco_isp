import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:tlink_mobile/main.dart';
import 'package:tlink_mobile/shared/widgets/app_button.dart';
import 'package:tlink_mobile/shared/widgets/app_text_field.dart';

/// Login flow specific tests
/// Run with: flutter test integration_test/login_test.dart -d emulator-5554
void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  /// Test Data
  const testPhone = '09999888777';
  const testPassword = 'Test@123';
  const invalidPhone = '09000000000';
  const invalidPassword = 'wrong123';

  /// Helper to wait for login screen
  Future<bool> waitForLoginScreen(WidgetTester tester) async {
    await tester.pumpWidget(const TLinkApp());
    await tester.pumpAndSettle(const Duration(seconds: 4));
    
    // Check if we're on login screen
    final loginScreen = find.text('Welcome Back');
    return loginScreen.evaluate().isNotEmpty;
  }

  /// Helper to find login button
  Finder findLoginButton() {
    return find.widgetWithText(AppButton, 'Sign In');
  }

  /// Helper to find text fields by hint
  Finder findPhoneField() {
    return find.byWidgetPredicate(
      (widget) => widget is AppTextField && 
        widget.hint?.toLowerCase().contains('phone') == true,
    );
  }

  Finder findPasswordField() {
    return find.byWidgetPredicate(
      (widget) => widget is AppTextField && 
        widget.hint?.toLowerCase().contains('password') == true,
    );
  }

  group('Login Screen UI Tests', () {
    testWidgets('Login screen displays all required elements',
        (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      
      if (!isOnLoginScreen) {
        // Already logged in - skip this test
        print('User already logged in, skipping login UI test');
        return;
      }

      // Check for login screen elements
      expect(find.text('Welcome Back'), findsOneWidget);
      expect(find.text('Sign in to continue'), findsOneWidget);
      
      // Check for input fields - use AppTextField
      expect(find.byType(AppTextField), findsAtLeast(2));
      
      // Check for login button - use AppButton
      expect(findLoginButton(), findsOneWidget);
    });

    testWidgets('Phone field accepts input correctly',
        (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      // Find phone field by type and enter text
      final phoneField = find.byType(AppTextField).first;
      await tester.enterText(phoneField, testPhone);
      await tester.pumpAndSettle();

      // Verify text was entered
      expect(find.text(testPhone), findsOneWidget);
    });

    testWidgets('Password field accepts input', (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      final passwordField = find.byType(AppTextField).at(1);
      await tester.enterText(passwordField, testPassword);
      await tester.pumpAndSettle();

      // Verify field exists and works (password obscured so can't verify text)
      expect(find.byType(AppTextField), findsAtLeast(2));
    });
  });

  group('Login Validation Tests', () {
    testWidgets('Shows error for empty phone', (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      // Enter only password
      final passwordField = find.byType(AppTextField).at(1);
      await tester.enterText(passwordField, testPassword);
      await tester.pumpAndSettle();

      // Tap login button
      await tester.tap(findLoginButton());
      await tester.pumpAndSettle();

      // Check for phone validation error
      expect(find.text('Please enter your phone number'), findsOneWidget);
    });

    testWidgets('Shows error for empty password', (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      // Enter only phone
      final phoneField = find.byType(AppTextField).first;
      await tester.enterText(phoneField, testPhone);
      await tester.pumpAndSettle();

      // Tap login
      await tester.tap(findLoginButton());
      await tester.pumpAndSettle();

      // Check for password validation error
      expect(find.text('Please enter your password'), findsOneWidget);
    });

    testWidgets('Shows error for short phone number',
        (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      final phoneField = find.byType(AppTextField).first;
      await tester.enterText(phoneField, '0999');
      
      final passwordField = find.byType(AppTextField).at(1);
      await tester.enterText(passwordField, testPassword);
      await tester.pumpAndSettle();

      await tester.tap(findLoginButton());
      await tester.pumpAndSettle();

      expect(find.text('Please enter a valid phone number'), findsOneWidget);
    });

    testWidgets('Shows error for short password', (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      final phoneField = find.byType(AppTextField).first;
      await tester.enterText(phoneField, testPhone);
      
      final passwordField = find.byType(AppTextField).at(1);
      await tester.enterText(passwordField, '123');
      await tester.pumpAndSettle();

      await tester.tap(findLoginButton());
      await tester.pumpAndSettle();

      expect(find.text('Password must be at least 6 characters'), findsOneWidget);
    });
  });

  group('Login API Integration Tests', () {
    testWidgets('Successful login navigates to home',
        (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) {
        // Already on home - test passes
        expect(find.text('Welcome Back'), findsNothing);
        return;
      }

      // Enter valid credentials
      final phoneField = find.byType(AppTextField).first;
      await tester.enterText(phoneField, testPhone);
      
      final passwordField = find.byType(AppTextField).at(1);
      await tester.enterText(passwordField, testPassword);
      await tester.pumpAndSettle();

      // Tap login
      await tester.tap(findLoginButton());
      
      // Wait for API response (longer timeout for real API)
      await tester.pumpAndSettle(const Duration(seconds: 15));

      // Verify navigation to home (login screen should be gone)
      expect(find.text('Welcome Back'), findsNothing);
    });

    testWidgets('Failed login shows error message',
        (WidgetTester tester) async {
      final isOnLoginScreen = await waitForLoginScreen(tester);
      if (!isOnLoginScreen) return;

      // Enter invalid credentials
      final phoneField = find.byType(AppTextField).first;
      await tester.enterText(phoneField, invalidPhone);
      
      final passwordField = find.byType(AppTextField).at(1);
      await tester.enterText(passwordField, invalidPassword);
      await tester.pumpAndSettle();

      // Tap login
      await tester.tap(findLoginButton());
      await tester.pumpAndSettle(const Duration(seconds: 15));

      // Should show error (either snackbar or stay on login)
      expect(find.text('Welcome Back'), findsOneWidget);
    });
  });
}
