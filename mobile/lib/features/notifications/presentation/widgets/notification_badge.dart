import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/api/api_service.dart';
import '../../../../core/constants/api_config.dart';

/// A notification bell icon with animated badge showing unread count
class NotificationBadge extends StatefulWidget {
  final VoidCallback onTap;
  final Color iconColor;
  final double iconSize;

  const NotificationBadge({
    super.key,
    required this.onTap,
    this.iconColor = Colors.white,
    this.iconSize = 28,
  });

  @override
  State<NotificationBadge> createState() => NotificationBadgeState();
}

class NotificationBadgeState extends State<NotificationBadge> 
    with SingleTickerProviderStateMixin {
  int _unreadCount = 0;
  late AnimationController _animationController;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
    _scaleAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: Curves.elasticOut,
      ),
    );
    _fetchUnreadCount();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _fetchUnreadCount() async {
    try {
      final apiService = ApiService();
      final response = await apiService.get<Map<String, dynamic>>(
        ApiConfig.unreadCount,
        fromJson: (data) => data as Map<String, dynamic>,
      );
      
      if (response.success && response.data != null) {
        final count = response.data!['unread_count'] ?? 0;
        if (mounted) {
          setState(() => _unreadCount = count);
          if (count > 0) {
            _animationController.forward();
          }
        }
      }
    } catch (e) {
      // Silently fail
    }
  }

  /// Call this method to refresh the unread count
  void refresh() {
    _fetchUnreadCount();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: widget.onTap,
      child: Container(
        padding: const EdgeInsets.all(8),
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            Icon(
              Icons.notifications_outlined,
              color: widget.iconColor,
              size: widget.iconSize,
            ),
            if (_unreadCount > 0)
              Positioned(
                right: -2,
                top: -2,
                child: ScaleTransition(
                  scale: _scaleAnimation,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    constraints: const BoxConstraints(
                      minWidth: 18,
                      minHeight: 18,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.error,
                      shape: _unreadCount > 9 ? BoxShape.rectangle : BoxShape.circle,
                      borderRadius: _unreadCount > 9 
                          ? BorderRadius.circular(9) 
                          : null,
                      border: Border.all(
                        color: Colors.white,
                        width: 2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.error.withValues(alpha: 0.4),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Text(
                        _unreadCount > 99 ? '99+' : _unreadCount.toString(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
