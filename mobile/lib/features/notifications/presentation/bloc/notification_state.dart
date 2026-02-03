import 'package:equatable/equatable.dart';
import '../../data/models/notification_model.dart';

/// Notification States
abstract class NotificationState extends Equatable {
  const NotificationState();

  @override
  List<Object?> get props => [];
}

class NotificationInitial extends NotificationState {
  const NotificationInitial();
}

class NotificationLoading extends NotificationState {
  const NotificationLoading();
}

class NotificationLoaded extends NotificationState {
  final List<NotificationItem> notifications;
  final int unreadCount;
  final PaginationInfo pagination;
  final bool isLoadingMore;
  final NotificationItem? selectedNotification;

  const NotificationLoaded({
    required this.notifications,
    required this.unreadCount,
    required this.pagination,
    this.isLoadingMore = false,
    this.selectedNotification,
  });

  @override
  List<Object?> get props => [
        notifications,
        unreadCount,
        pagination,
        isLoadingMore,
        selectedNotification,
      ];

  NotificationLoaded copyWith({
    List<NotificationItem>? notifications,
    int? unreadCount,
    PaginationInfo? pagination,
    bool? isLoadingMore,
    NotificationItem? selectedNotification,
    bool clearSelected = false,
  }) {
    return NotificationLoaded(
      notifications: notifications ?? this.notifications,
      unreadCount: unreadCount ?? this.unreadCount,
      pagination: pagination ?? this.pagination,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      selectedNotification: clearSelected ? null : (selectedNotification ?? this.selectedNotification),
    );
  }
}

class NotificationError extends NotificationState {
  final String message;

  const NotificationError(this.message);

  @override
  List<Object?> get props => [message];
}
