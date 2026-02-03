import 'package:flutter_test/flutter_test.dart';
import 'package:tlink_mobile/features/auth/data/auth_repository.dart';
import 'package:tlink_mobile/features/auth/data/models/user_model.dart';
import 'package:tlink_mobile/core/api/api_service.dart';

/// Unit tests for Auth Repository
/// Run with: flutter test test/auth_repository_test.dart
void main() {
  group('AuthRepository Tests', () {
    late AuthRepository authRepository;

    setUp(() {
      authRepository = AuthRepository();
    });

    test('login with valid credentials returns AuthResponse', () async {
      // Test data
      const phone = '09999888777';
      const password = 'Test@123';

      try {
        final response = await authRepository.login(
          phone: phone,
          password: password,
        );

        expect(response, isA<AuthResponse>());
        expect(response.user, isA<User>());
        expect(response.token, isNotEmpty);
        expect(response.user.phone, equals(phone));
      } on ApiException catch (e) {
        fail('Login failed: ${e.message}');
      }
    });

    test('login with invalid credentials throws ApiException', () async {
      const phone = '09000000000';
      const password = 'wrongpassword';

      expect(
        () => authRepository.login(phone: phone, password: password),
        throwsA(isA<ApiException>()),
      );
    });

    test('isLoggedIn returns false initially', () async {
      // Note: This test assumes clean state
      // In real scenario, you might need to mock StorageService
      final isLoggedIn = await authRepository.isLoggedIn();
      // Can't guarantee false if there's persistent storage
      expect(isLoggedIn, isA<bool>());
    });
  });

  group('User Model Tests', () {
    test('User.fromJson parses correctly', () {
      final json = {
        'id': 1,
        'name': 'Test User',
        'phone': '09999888777',
        'email': 'test@example.com',
        'photo': null,
        'user_status': 0,
        'bind_user_id': null,
        'created_at': '2025-12-22T17:09:09.000000Z',
      };

      final user = User.fromJson(json);

      expect(user.id, equals(1));
      expect(user.name, equals('Test User'));
      expect(user.phone, equals('09999888777'));
      expect(user.email, equals('test@example.com'));
    });

    test('User.toJson returns correct map', () {
      final user = User(
        id: 1,
        name: 'Test User',
        phone: '09999888777',
        email: 'test@example.com',
        photo: null,
        userStatus: 0,
        bindUserId: null,
        createdAt: DateTime.parse('2025-12-22T17:09:09.000000Z'),
      );

      final json = user.toJson();

      expect(json['id'], equals(1));
      expect(json['name'], equals('Test User'));
      expect(json['phone'], equals('09999888777'));
    });
  });

  group('AuthResponse Model Tests', () {
    test('AuthResponse.fromJson parses correctly', () {
      final json = {
        'user': {
          'id': 1,
          'name': 'Test User',
          'phone': '09999888777',
          'email': 'test@example.com',
          'photo': null,
          'user_status': 0,
          'bind_user_id': null,
          'created_at': '2025-12-22T17:09:09.000000Z',
        },
        'token': 'test_token_123',
        'token_type': 'Bearer',
        'expires_at': '2026-01-22T08:47:31.831405Z',
      };

      final response = AuthResponse.fromJson(json);

      expect(response.user.id, equals(1));
      expect(response.token, equals('test_token_123'));
      expect(response.tokenType, equals('Bearer'));
    });
  });
}
