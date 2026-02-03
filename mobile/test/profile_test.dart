import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:tlink_mobile/features/profile/presentation/bloc/profile_bloc.dart';
import 'package:tlink_mobile/features/profile/data/profile_repository.dart';
import 'package:tlink_mobile/features/profile/data/models/bind_user_model.dart';
import 'package:tlink_mobile/core/api/api_service.dart';

/// Mock classes
class MockProfileRepository extends Mock implements ProfileRepository {}

/// Unit tests for Profile feature
/// Run with: flutter test test/profile_test.dart
void main() {
  group('BindUser Model Tests', () {
    test('parses JSON correctly with all fields', () {
      final json = {
        'id': 1,
        'account_id': 1,
        'user_name': 'tlink_user_001',
        'real_name': 'Aung Kyaw',
        'phone': '09999888777',
        'address': 'No. 123, Pyay Road, Hlaing Township, Yangon',
        'package': '50 Mbps Fiber',
        'monthly_cost': '35000',
        'expire_time': '2026-01-31',
        'status': 0,
        'balance': '50000.00',
        'bandwidth': '50 Mbps',
        'service_type': 'Fiber',
        'sub_company': 'T-Link Main Branch',
      };

      final bindUser = BindUser.fromJson(json);

      expect(bindUser.id, 1);
      expect(bindUser.accountId, 1);
      expect(bindUser.userName, 'tlink_user_001');
      expect(bindUser.realName, 'Aung Kyaw');
      expect(bindUser.phone, '09999888777');
      expect(bindUser.address, 'No. 123, Pyay Road, Hlaing Township, Yangon');
      expect(bindUser.package, '50 Mbps Fiber');
      expect(bindUser.monthlyCost, '35000');
      expect(bindUser.expireTime, '2026-01-31');
      expect(bindUser.status, 0);
      expect(bindUser.balance, '50000.00');
      expect(bindUser.bandwidth, '50 Mbps');
      expect(bindUser.serviceType, 'Fiber');
      expect(bindUser.subCompany, 'T-Link Main Branch');
    });

    test('parses JSON correctly with null address', () {
      final json = {
        'id': 1,
        'account_id': 1,
        'user_name': 'tlink_user_001',
        'real_name': 'Aung Kyaw',
        'phone': '09999888777',
        'address': null,
        'status': 0,
      };

      final bindUser = BindUser.fromJson(json);

      expect(bindUser.id, 1);
      expect(bindUser.address, isNull);
    });

    test('isActive returns true when status is 0', () {
      final bindUser = BindUser(id: 1, status: 0);
      expect(bindUser.isActive, isTrue);
    });

    test('isActive returns false when status is not 0', () {
      final bindUser = BindUser(id: 1, status: 1);
      expect(bindUser.isActive, isFalse);
    });

    test('statusText returns correct text for each status', () {
      expect(BindUser(id: 1, status: 0).statusText, 'Active');
      expect(BindUser(id: 1, status: 1).statusText, 'Suspended');
      expect(BindUser(id: 1, status: 2).statusText, 'Expired');
      expect(BindUser(id: 1, status: 99).statusText, 'Unknown');
    });
  });

  group('ProfileBloc Tests', () {
    late ProfileBloc profileBloc;
    late MockProfileRepository mockRepository;

    final testBindUser = BindUser(
      id: 1,
      accountId: 1,
      userName: 'tlink_user_001',
      realName: 'Aung Kyaw',
      phone: '09999888777',
      address: 'No. 123, Pyay Road, Hlaing Township, Yangon',
      package: '50 Mbps Fiber',
      monthlyCost: '35000',
      expireTime: '2026-01-31',
      status: 0,
      balance: '50000.00',
      bandwidth: '50 Mbps',
      serviceType: 'Fiber',
      subCompany: 'T-Link Main Branch',
    );

    final testBindUserDetailed = BindUser(
      id: 1,
      accountId: 1,
      userName: 'tlink_user_001',
      realName: 'Aung Kyaw',
      phone: '09999888777',
      address: 'No. 123, Pyay Road, Hlaing Township, Yangon',
      package: '50 Mbps Fiber',
      monthlyCost: '35000',
      expireTime: '2026-01-31',
      status: 0,
      balance: '50000.00',
      bandwidth: '50 Mbps',
      serviceType: 'Fiber',
      subCompany: 'T-Link Main Branch',
    );

    setUp(() {
      mockRepository = MockProfileRepository();
      profileBloc = ProfileBloc(profileRepository: mockRepository);
    });

    tearDown(() {
      profileBloc.close();
    });

    test('initial state is ProfileInitial', () {
      expect(profileBloc.state, isA<ProfileInitial>());
    });

    blocTest<ProfileBloc, ProfileState>(
      'emits [ProfileLoading, ProfileLoaded] when profile loads successfully',
      build: () {
        when(() => mockRepository.getBindUsers())
            .thenAnswer((_) async => [testBindUser]);
        when(() => mockRepository.getBindUserDetails(any()))
            .thenAnswer((_) async => testBindUserDetailed);
        return profileBloc;
      },
      act: (bloc) => bloc.add(ProfileLoadRequested()),
      expect: () => [
        isA<ProfileLoading>(),
        isA<ProfileLoaded>(),
      ],
      verify: (_) {
        verify(() => mockRepository.getBindUsers()).called(1);
        verify(() => mockRepository.getBindUserDetails(1)).called(1);
      },
    );

    blocTest<ProfileBloc, ProfileState>(
      'ProfileLoaded state contains correct bind user with address',
      build: () {
        when(() => mockRepository.getBindUsers())
            .thenAnswer((_) async => [testBindUser]);
        when(() => mockRepository.getBindUserDetails(any()))
            .thenAnswer((_) async => testBindUserDetailed);
        return profileBloc;
      },
      act: (bloc) => bloc.add(ProfileLoadRequested()),
      verify: (bloc) {
        final state = bloc.state;
        expect(state, isA<ProfileLoaded>());
        final loadedState = state as ProfileLoaded;
        expect(loadedState.primaryBindUser, isNotNull);
        expect(loadedState.primaryBindUser!.address, 
            'No. 123, Pyay Road, Hlaing Township, Yangon');
        expect(loadedState.primaryBindUser!.subCompany, 'T-Link Main Branch');
        expect(loadedState.primaryBindUser!.bandwidth, '50 Mbps');
      },
    );

    blocTest<ProfileBloc, ProfileState>(
      'emits ProfileLoaded with empty list when no bind users',
      build: () {
        when(() => mockRepository.getBindUsers())
            .thenAnswer((_) async => []);
        return profileBloc;
      },
      act: (bloc) => bloc.add(ProfileLoadRequested()),
      expect: () => [
        isA<ProfileLoading>(),
        isA<ProfileLoaded>(),
      ],
      verify: (bloc) {
        final state = bloc.state as ProfileLoaded;
        expect(state.bindUsers, isEmpty);
        expect(state.primaryBindUser, isNull);
      },
    );

    blocTest<ProfileBloc, ProfileState>(
      'emits [ProfileLoading, ProfileError] when API throws exception',
      build: () {
        when(() => mockRepository.getBindUsers())
            .thenThrow(ApiException(message: 'Network error', statusCode: 500));
        return profileBloc;
      },
      act: (bloc) => bloc.add(ProfileLoadRequested()),
      expect: () => [
        isA<ProfileLoading>(),
        isA<ProfileError>(),
      ],
      verify: (bloc) {
        final state = bloc.state as ProfileError;
        expect(state.message, 'Network error');
      },
    );

    blocTest<ProfileBloc, ProfileState>(
      'refresh reloads profile data',
      build: () {
        when(() => mockRepository.getBindUsers())
            .thenAnswer((_) async => [testBindUser]);
        when(() => mockRepository.getBindUserDetails(any()))
            .thenAnswer((_) async => testBindUserDetailed);
        return profileBloc;
      },
      seed: () => ProfileLoaded(
        bindUsers: [testBindUser],
        primaryBindUser: testBindUser,
      ),
      act: (bloc) => bloc.add(ProfileRefreshRequested()),
      expect: () => [isA<ProfileLoaded>()],
      verify: (_) {
        verify(() => mockRepository.getBindUsers()).called(1);
      },
    );
  });
}
