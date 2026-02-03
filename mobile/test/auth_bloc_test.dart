import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:tlink_mobile/features/auth/presentation/bloc/auth_bloc.dart';
import 'package:tlink_mobile/features/auth/data/auth_repository.dart';
import 'package:tlink_mobile/features/auth/data/models/user_model.dart';
import 'package:tlink_mobile/core/api/api_service.dart';

/// Mock classes
class MockAuthRepository extends Mock implements AuthRepository {}

/// Unit tests for AuthBloc
/// Run with: flutter test test/auth_bloc_test.dart
void main() {
  group('AuthBloc Tests', () {
    late AuthBloc authBloc;
    late MockAuthRepository mockAuthRepository;

    final testUser = User(
      id: 1,
      name: 'Test User',
      phone: '09999888777',
      email: 'test@example.com',
      photo: null,
      userStatus: 0,
      bindUserId: null,
      createdAt: DateTime.now(),
    );

    final testAuthResponse = AuthResponse(
      user: testUser,
      token: 'test_token',
      tokenType: 'Bearer',
      expiresAt: DateTime.now().add(const Duration(days: 30)),
    );

    setUp(() {
      mockAuthRepository = MockAuthRepository();
      authBloc = AuthBloc(authRepository: mockAuthRepository);
    });

    tearDown(() {
      authBloc.close();
    });

    test('initial state is AuthInitial', () {
      expect(authBloc.state, isA<AuthInitial>());
    });

    blocTest<AuthBloc, AuthState>(
      'emits [AuthLoading, AuthAuthenticated] when login succeeds',
      build: () {
        when(() => mockAuthRepository.login(
          phone: any(named: 'phone'),
          password: any(named: 'password'),
        )).thenAnswer((_) async => testAuthResponse);
        return authBloc;
      },
      act: (bloc) => bloc.add(const AuthLoginRequested(
        phone: '09999888777',
        password: 'Test@123',
      )),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthAuthenticated>(),
      ],
      verify: (_) {
        verify(() => mockAuthRepository.login(
          phone: '09999888777',
          password: 'Test@123',
        )).called(1);
      },
    );

    blocTest<AuthBloc, AuthState>(
      'emits [AuthLoading, AuthError] when login fails',
      build: () {
        when(() => mockAuthRepository.login(
          phone: any(named: 'phone'),
          password: any(named: 'password'),
        )).thenThrow(ApiException(message: 'Invalid credentials'));
        return authBloc;
      },
      act: (bloc) => bloc.add(const AuthLoginRequested(
        phone: '09000000000',
        password: 'wrong',
      )),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthError>(),
      ],
    );

    blocTest<AuthBloc, AuthState>(
      'emits [AuthLoading, AuthUnauthenticated] when logout succeeds',
      build: () {
        when(() => mockAuthRepository.logout()).thenAnswer((_) async {});
        return authBloc;
      },
      act: (bloc) => bloc.add(AuthLogoutRequested()),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthUnauthenticated>(),
      ],
    );

    blocTest<AuthBloc, AuthState>(
      'emits [AuthLoading, AuthAuthenticated] when check finds logged in user',
      build: () {
        when(() => mockAuthRepository.initialize()).thenAnswer((_) async {});
        when(() => mockAuthRepository.isLoggedIn()).thenAnswer((_) async => true);
        when(() => mockAuthRepository.getSavedUser())
            .thenAnswer((_) async => testUser);
        return authBloc;
      },
      act: (bloc) => bloc.add(AuthCheckRequested()),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthAuthenticated>(),
      ],
    );

    blocTest<AuthBloc, AuthState>(
      'emits [AuthLoading, AuthUnauthenticated] when check finds no user',
      build: () {
        when(() => mockAuthRepository.initialize()).thenAnswer((_) async {});
        when(() => mockAuthRepository.isLoggedIn()).thenAnswer((_) async => false);
        return authBloc;
      },
      act: (bloc) => bloc.add(AuthCheckRequested()),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthUnauthenticated>(),
      ],
    );
  });
}
