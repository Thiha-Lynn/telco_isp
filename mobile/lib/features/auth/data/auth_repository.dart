import 'dart:convert';
import '../../../core/api/api.dart';
import '../../../core/constants/api_config.dart';
import 'models/user_model.dart';

/// Auth Repository for handling authentication operations
class AuthRepository {
  final ApiService _apiService;
  final StorageService _storageService;

  AuthRepository({
    ApiService? apiService,
    StorageService? storageService,
  })  : _apiService = apiService ?? ApiService(),
        _storageService = storageService ?? StorageService();

  /// Login with phone and password
  Future<AuthResponse> login({
    required String phone,
    required String password,
  }) async {
    final response = await _apiService.post<Map<String, dynamic>>(
      ApiConfig.login,
      body: {
        'phone': phone,
        'password': password,
      },
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      final authResponse = AuthResponse.fromJson(response.data!);
      await _saveAuthData(authResponse);
      return authResponse;
    }

    throw ApiException(message: response.message);
  }

  /// Register a new user
  Future<AuthResponse> register({
    required String phone,
    required String name,
    required String password,
    required String confirmPassword,
  }) async {
    final response = await _apiService.post<Map<String, dynamic>>(
      ApiConfig.register,
      body: {
        'phone': phone,
        'name': name,
        'password': password,
        'confirm_password': confirmPassword,
      },
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      final authResponse = AuthResponse.fromJson(response.data!);
      await _saveAuthData(authResponse);
      return authResponse;
    }

    throw ApiException(message: response.message);
  }

  /// Forgot password
  Future<String> forgotPassword({required String phone}) async {
    final response = await _apiService.post<Map<String, dynamic>>(
      ApiConfig.forgotPassword,
      body: {'phone': phone},
      fromJson: (data) => data as Map<String, dynamic>,
    );

    return response.message;
  }

  /// Logout
  Future<void> logout() async {
    try {
      await _apiService.post(ApiConfig.logout);
    } finally {
      await _clearAuthData();
    }
  }

  /// Get current user profile
  Future<User> getProfile() async {
    final response = await _apiService.get<Map<String, dynamic>>(
      ApiConfig.profile,
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      return User.fromJson(response.data!['user']);
    }

    throw ApiException(message: response.message);
  }

  /// Check if user is logged in
  Future<bool> isLoggedIn() async {
    return await _storageService.isLoggedIn();
  }

  /// Get saved user
  Future<User?> getSavedUser() async {
    final userData = await _storageService.getUserData();
    if (userData != null) {
      return User.fromJson(jsonDecode(userData));
    }
    return null;
  }

  /// Initialize auth - restore token
  Future<void> initialize() async {
    final token = await _storageService.getToken();
    if (token != null) {
      _apiService.setToken(token);
    }
  }

  /// Save auth data
  Future<void> _saveAuthData(AuthResponse authResponse) async {
    await _storageService.saveToken(authResponse.token);
    await _storageService.saveTokenExpiry(authResponse.expiresAt.toIso8601String());
    await _storageService.saveUserData(jsonEncode(authResponse.user.toJson()));
    _apiService.setToken(authResponse.token);
  }

  /// Clear auth data
  Future<void> _clearAuthData() async {
    await _storageService.clearAll();
    _apiService.setToken(null);
  }
}
