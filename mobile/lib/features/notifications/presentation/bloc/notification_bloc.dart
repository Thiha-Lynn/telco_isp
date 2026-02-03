import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/notification_repository.dart';
import 'notification_event.dart';
import 'notification_state.dart';

class NotificationBloc extends Bloc<NotificationEvent, NotificationState> {
  final NotificationRepository _repository;
  int _currentPage = 1;

  NotificationBloc({NotificationRepository? repository})
      : _repository = repository ?? NotificationRepository(),
        super(const NotificationInitial()) {
    on<LoadNotifications>(_onLoadNotifications);
    on<LoadMoreNotifications>(_onLoadMoreNotifications);
    on<RefreshUnreadCount>(_onRefreshUnreadCount);
    on<MarkNotificationAsRead>(_onMarkNotificationAsRead);
    on<MarkAllNotificationsAsRead>(_onMarkAllNotificationsAsRead);
    on<SelectNotification>(_onSelectNotification);
    on<ClearSelectedNotification>(_onClearSelectedNotification);
  }

  Future<void> _onLoadNotifications(
    LoadNotifications event,
    Emitter<NotificationState> emit,
  ) async {
    try {
      if (event.refresh) {
        _currentPage = 1;
      }
      
      if (!event.refresh) {
        emit(const NotificationLoading());
      }

      final response = await _repository.getNotifications(page: 1);
      _currentPage = 1;

      emit(NotificationLoaded(
        notifications: response.notifications,
        unreadCount: response.unreadCount,
        pagination: response.pagination,
      ));
    } catch (e) {
      emit(NotificationError(e.toString()));
    }
  }

  Future<void> _onLoadMoreNotifications(
    LoadMoreNotifications event,
    Emitter<NotificationState> emit,
  ) async {
    final currentState = state;
    if (currentState is! NotificationLoaded) return;
    if (currentState.isLoadingMore) return;
    if (!currentState.pagination.hasMore) return;

    try {
      emit(currentState.copyWith(isLoadingMore: true));

      final nextPage = _currentPage + 1;
      final response = await _repository.getNotifications(page: nextPage);
      _currentPage = nextPage;

      final allNotifications = [
        ...currentState.notifications,
        ...response.notifications,
      ];

      emit(NotificationLoaded(
        notifications: allNotifications,
        unreadCount: response.unreadCount,
        pagination: response.pagination,
        isLoadingMore: false,
      ));
    } catch (e) {
      emit(currentState.copyWith(isLoadingMore: false));
    }
  }

  Future<void> _onRefreshUnreadCount(
    RefreshUnreadCount event,
    Emitter<NotificationState> emit,
  ) async {
    try {
      final unreadCount = await _repository.getUnreadCount();
      
      final currentState = state;
      if (currentState is NotificationLoaded) {
        emit(currentState.copyWith(unreadCount: unreadCount));
      }
    } catch (e) {
      // Silently fail - unread count is not critical
    }
  }

  Future<void> _onMarkNotificationAsRead(
    MarkNotificationAsRead event,
    Emitter<NotificationState> emit,
  ) async {
    final currentState = state;
    if (currentState is! NotificationLoaded) return;

    try {
      await _repository.markAsRead(event.notificationId);

      // Update local state
      final updatedNotifications = currentState.notifications.map((notification) {
        if (notification.id == event.notificationId) {
          return notification.copyWith(isRead: true);
        }
        return notification;
      }).toList();

      final newUnreadCount = currentState.unreadCount > 0 
          ? currentState.unreadCount - 1 
          : 0;

      emit(currentState.copyWith(
        notifications: updatedNotifications,
        unreadCount: newUnreadCount,
      ));
    } catch (e) {
      // Silently fail
    }
  }

  Future<void> _onMarkAllNotificationsAsRead(
    MarkAllNotificationsAsRead event,
    Emitter<NotificationState> emit,
  ) async {
    final currentState = state;
    if (currentState is! NotificationLoaded) return;

    try {
      await _repository.markAllAsRead();

      // Update local state
      final updatedNotifications = currentState.notifications
          .map((notification) => notification.copyWith(isRead: true))
          .toList();

      emit(currentState.copyWith(
        notifications: updatedNotifications,
        unreadCount: 0,
      ));
    } catch (e) {
      // Silently fail
    }
  }

  void _onSelectNotification(
    SelectNotification event,
    Emitter<NotificationState> emit,
  ) {
    final currentState = state;
    if (currentState is NotificationLoaded) {
      emit(currentState.copyWith(selectedNotification: event.notification));
      
      // Mark as read if not already
      if (!event.notification.isRead) {
        add(MarkNotificationAsRead(event.notification.id));
      }
    }
  }

  void _onClearSelectedNotification(
    ClearSelectedNotification event,
    Emitter<NotificationState> emit,
  ) {
    final currentState = state;
    if (currentState is NotificationLoaded) {
      emit(currentState.copyWith(clearSelected: true));
    }
  }
}
