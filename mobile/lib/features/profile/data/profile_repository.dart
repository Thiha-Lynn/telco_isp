import '../../../core/api/api_service.dart';
import '../../../core/constants/api_config.dart';
import 'models/bind_user_model.dart';

/// Profile Repository for handling profile-related API calls
class ProfileRepository {
  final ApiService _apiService;

  ProfileRepository({ApiService? apiService})
      : _apiService = apiService ?? ApiService();

  /// Get bound broadband users/accounts
  Future<List<BindUser>> getBindUsers() async {
    final response = await _apiService.get<Map<String, dynamic>>(
      ApiConfig.bindUsers,
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      final bindUsersJson = response.data!['bind_users'] as List? ?? [];
      return bindUsersJson
          .map((json) => BindUser.fromJson(json as Map<String, dynamic>))
          .toList();
    }

    return [];
  }

  /// Get bind user details by ID
  Future<BindUser?> getBindUserDetails(int id) async {
    final response = await _apiService.get<Map<String, dynamic>>(
      '${ApiConfig.bindUsers}/$id',
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      final bindUserJson = response.data!['bind_user'] as Map<String, dynamic>?;
      if (bindUserJson != null) {
        return BindUser.fromJson(bindUserJson);
      }
    }

    return null;
  }
}
