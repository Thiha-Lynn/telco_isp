import 'package:integration_test/integration_test_driver.dart';

/// Test driver for running integration tests on a device
/// 
/// Run with:
/// flutter drive \
///   --driver=test_driver/integration_test.dart \
///   --target=integration_test/app_test.dart
Future<void> main() => integrationDriver();
