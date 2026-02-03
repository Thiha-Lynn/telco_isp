import '../../../core/api/api.dart';
import '../../../core/constants/api_config.dart';
import 'models/notification_model.dart';

/// Repository for notification operations
class NotificationRepository {
  final ApiService _apiService;

  NotificationRepository({ApiService? apiService})
      : _apiService = apiService ?? ApiService();

  /// Get all notifications with pagination
  Future<NotificationsResponse> getNotifications({
    int page = 1,
    int perPage = 20,
    bool unreadOnly = false,
  }) async {
    final queryParams = {
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (unreadOnly) 'unread_only': '1',
    };

    final response = await _apiService.get<Map<String, dynamic>>(
      ApiConfig.notifications,
      queryParams: queryParams,
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      return NotificationsResponse.fromJson(response.data!);
    }

    throw ApiException(message: response.message);
  }

  /// Get unread notification count
  Future<int> getUnreadCount() async {
    final response = await _apiService.get<Map<String, dynamic>>(
      ApiConfig.unreadCount,
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      return response.data!['unread_count'] ?? 0;
    }

    throw ApiException(message: response.message);
  }

  /// Get single notification details
  Future<NotificationItem> getNotification(int id) async {
    final response = await _apiService.get<Map<String, dynamic>>(
      '${ApiConfig.notifications}/$id',
      fromJson: (data) => data as Map<String, dynamic>,
    );

    if (response.success && response.data != null) {
      return NotificationItem.fromJson(response.data!['notification']);
    }

    throw ApiException(message: response.message);
  }

  /// Mark notification as read
  Future<void> markAsRead(int id) async {
    final response = await _apiService.put<Map<String, dynamic>>(
      '${ApiConfig.notifications}/$id/read',
    );

    if (!response.success) {
      throw ApiException(message: response.message);
    }
  }

  /// Mark all notifications as read
  Future<void> markAllAsRead() async {
    final response = await _apiService.put<Map<String, dynamic>>(
      '${ApiConfig.notifications}/read-all',
    );

    if (!response.success) {
      throw ApiException(message: response.message);
    }
  }
}
