/// Notification model
class NotificationItem {
  final int id;
  final String title;
  final String message;
  final bool isRead;
  final bool isBroadcast;
  final String? publishInfo;
  final DateTime? createdAt;

  NotificationItem({
    required this.id,
    required this.title,
    required this.message,
    required this.isRead,
    required this.isBroadcast,
    this.publishInfo,
    this.createdAt,
  });

  factory NotificationItem.fromJson(Map<String, dynamic> json) {
    return NotificationItem(
      id: _parseIntLocal(json['id']),
      title: json['title']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      isRead: _parseBool(json['is_read']),
      isBroadcast: _parseBool(json['is_broadcast']),
      publishInfo: json['publish_info']?.toString(),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  /// Helper to parse int from dynamic
  static int _parseIntLocal(dynamic value, [int defaultValue = 0]) {
    if (value == null) return defaultValue;
    if (value is int) return value;
    if (value is String) return int.tryParse(value) ?? defaultValue;
    return defaultValue;
  }

  /// Helper to parse bool from dynamic
  static bool _parseBool(dynamic value, [bool defaultValue = false]) {
    if (value == null) return defaultValue;
    if (value is bool) return value;
    if (value is int) return value == 1;
    if (value is String) return value == '1' || value.toLowerCase() == 'true';
    return defaultValue;
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'message': message,
      'is_read': isRead,
      'is_broadcast': isBroadcast,
      'publish_info': publishInfo,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  NotificationItem copyWith({
    int? id,
    String? title,
    String? message,
    bool? isRead,
    bool? isBroadcast,
    String? publishInfo,
    DateTime? createdAt,
  }) {
    return NotificationItem(
      id: id ?? this.id,
      title: title ?? this.title,
      message: message ?? this.message,
      isRead: isRead ?? this.isRead,
      isBroadcast: isBroadcast ?? this.isBroadcast,
      publishInfo: publishInfo ?? this.publishInfo,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  /// Get notification type based on title keywords
  NotificationType get type {
    final lowerTitle = title.toLowerCase();
    if (lowerTitle.contains('payment') || lowerTitle.contains('bill')) {
      return NotificationType.payment;
    } else if (lowerTitle.contains('maintenance') || lowerTitle.contains('update')) {
      return NotificationType.system;
    } else if (lowerTitle.contains('promotion') || lowerTitle.contains('offer') || lowerTitle.contains('discount')) {
      return NotificationType.promotion;
    } else if (lowerTitle.contains('speed') || lowerTitle.contains('upgrade')) {
      return NotificationType.upgrade;
    } else if (lowerTitle.contains('welcome')) {
      return NotificationType.welcome;
    }
    return NotificationType.general;
  }

  /// Get time ago string
  String get timeAgo {
    if (createdAt == null) return '';
    
    final now = DateTime.now();
    final difference = now.difference(createdAt!);
    
    if (difference.inDays > 30) {
      final months = (difference.inDays / 30).floor();
      return '$months ${months == 1 ? 'month' : 'months'} ago';
    } else if (difference.inDays > 0) {
      return '${difference.inDays} ${difference.inDays == 1 ? 'day' : 'days'} ago';
    } else if (difference.inHours > 0) {
      return '${difference.inHours} ${difference.inHours == 1 ? 'hour' : 'hours'} ago';
    } else if (difference.inMinutes > 0) {
      return '${difference.inMinutes} ${difference.inMinutes == 1 ? 'minute' : 'minutes'} ago';
    } else {
      return 'Just now';
    }
  }
}

enum NotificationType {
  payment,
  system,
  promotion,
  upgrade,
  welcome,
  general,
}

/// Notifications response model
class NotificationsResponse {
  final List<NotificationItem> notifications;
  final int unreadCount;
  final PaginationInfo pagination;

  NotificationsResponse({
    required this.notifications,
    required this.unreadCount,
    required this.pagination,
  });

  factory NotificationsResponse.fromJson(Map<String, dynamic> json) {
    return NotificationsResponse(
      notifications: (json['notifications'] as List<dynamic>?)
              ?.map((e) => NotificationItem.fromJson(e))
              .toList() ??
          [],
      unreadCount: _parseInt(json['unread_count']),
      pagination: PaginationInfo.fromJson(json['pagination'] ?? {}),
    );
  }
}

/// Helper function to parse int from dynamic (handles String or int)
int _parseInt(dynamic value, [int defaultValue = 0]) {
  if (value == null) return defaultValue;
  if (value is int) return value;
  if (value is String) return int.tryParse(value) ?? defaultValue;
  return defaultValue;
}

class PaginationInfo {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  PaginationInfo({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  factory PaginationInfo.fromJson(Map<String, dynamic> json) {
    return PaginationInfo(
      currentPage: _parseInt(json['current_page'], 1),
      lastPage: _parseInt(json['last_page'], 1),
      perPage: _parseInt(json['per_page'], 20),
      total: _parseInt(json['total']),
    );
  }

  bool get hasMore => currentPage < lastPage;
}
