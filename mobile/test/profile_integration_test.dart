import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tlink_mobile/features/profile/data/profile_repository.dart';
import 'package:tlink_mobile/features/profile/data/models/bind_user_model.dart';
import 'package:tlink_mobile/features/auth/data/auth_repository.dart';
import 'package:tlink_mobile/core/api/api_service.dart';

/// Integration test for Profile API
/// This test requires the API to be running
/// 
/// Run with: flutter test test/profile_integration_test.dart
void main() {
  // Initialize Flutter bindings for HTTP calls
  TestWidgetsFlutterBinding.ensureInitialized();
  
  group('Profile API Integration Tests', () {
    late ProfileRepository profileRepository;
    late AuthRepository authRepository;
    late ApiService apiService;

    setUpAll(() async {
      apiService = ApiService();
      authRepository = AuthRepository();
      profileRepository = ProfileRepository();
      
      // Login to get a valid token
      try {
        final authResponse = await authRepository.login(
          phone: '09999888777',
          password: 'Test@123',
        );
        print('Login successful: ${authResponse.token}');
      } catch (e) {
        print('Login failed: $e');
        // Test will fail if login fails
      }
    });

    test('getBindUsers returns list of bind users', () async {
      final bindUsers = await profileRepository.getBindUsers();
      
      print('Retrieved ${bindUsers.length} bind users');
      
      expect(bindUsers, isA<List<BindUser>>());
      
      if (bindUsers.isNotEmpty) {
        final firstUser = bindUsers.first;
        print('First bind user:');
        print('  ID: ${firstUser.id}');
        print('  User Name: ${firstUser.userName}');
        print('  Real Name: ${firstUser.realName}');
        print('  Phone: ${firstUser.phone}');
        print('  Address: ${firstUser.address}');
        print('  Status: ${firstUser.status}');
        
        expect(firstUser.id, isPositive);
        expect(firstUser.userName, isNotNull);
      }
    });

    test('getBindUserDetails returns detailed bind user info', () async {
      final bindUsers = await profileRepository.getBindUsers();
      
      if (bindUsers.isEmpty) {
        print('No bind users found, skipping detail test');
        return;
      }
      
      final bindUser = await profileRepository.getBindUserDetails(bindUsers.first.id);
      
      expect(bindUser, isNotNull);
      print('Bind user details:');
      print('  ID: ${bindUser?.id}');
      print('  User Name: ${bindUser?.userName}');
      print('  Real Name: ${bindUser?.realName}');
      print('  Phone: ${bindUser?.phone}');
      print('  Address: ${bindUser?.address}');
      print('  Bandwidth: ${bindUser?.bandwidth}');
      print('  Service Type: ${bindUser?.serviceType}');
      print('  Sub Company: ${bindUser?.subCompany}');
      print('  Monthly Cost: ${bindUser?.monthlyCost}');
      print('  Expire Time: ${bindUser?.expireTime}');
      print('  Balance: ${bindUser?.balance}');
      
      // Verify address is not null (we updated test data)
      expect(bindUser?.address, isNotNull, 
          reason: 'Address should not be null - check test data');
      expect(bindUser?.subCompany, isNotNull,
          reason: 'Sub company should not be null - check test data');
    });

    test('BindUser model parses API response correctly', () async {
      final bindUsers = await profileRepository.getBindUsers();
      
      if (bindUsers.isEmpty) {
        print('No bind users found, skipping model test');
        return;
      }
      
      final bindUser = await profileRepository.getBindUserDetails(bindUsers.first.id);
      
      expect(bindUser, isNotNull);
      
      // Test model properties
      expect(bindUser!.id, isA<int>());
      expect(bindUser.userName, isA<String>());
      expect(bindUser.isActive, isA<bool>());
      expect(bindUser.statusText, isIn(['Active', 'Suspended', 'Expired', 'Unknown']));
    });
  });
}
