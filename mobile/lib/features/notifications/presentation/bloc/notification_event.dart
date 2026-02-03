import 'package:equatable/equatable.dart';
import '../../data/models/notification_model.dart';

/// Notification Events
abstract class NotificationEvent extends Equatable {
  const NotificationEvent();

  @override
  List<Object?> get props => [];
}

class LoadNotifications extends NotificationEvent {
  final bool refresh;
  
  const LoadNotifications({this.refresh = false});

  @override
  List<Object?> get props => [refresh];
}

class LoadMoreNotifications extends NotificationEvent {
  const LoadMoreNotifications();
}

class RefreshUnreadCount extends NotificationEvent {
  const RefreshUnreadCount();
}

class MarkNotificationAsRead extends NotificationEvent {
  final int notificationId;

  const MarkNotificationAsRead(this.notificationId);

  @override
  List<Object?> get props => [notificationId];
}

class MarkAllNotificationsAsRead extends NotificationEvent {
  const MarkAllNotificationsAsRead();
}

class SelectNotification extends NotificationEvent {
  final NotificationItem notification;

  const SelectNotification(this.notification);

  @override
  List<Object?> get props => [notification];
}

class ClearSelectedNotification extends NotificationEvent {
  const ClearSelectedNotification();
}
