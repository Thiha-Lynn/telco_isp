import 'package:flutter/material.dart';
import '../../../../core/constants/app_text_styles.dart';

class SpeedInfoCard extends StatefulWidget {
  final String title;
  final double speed;
  final String unit;
  final IconData icon;
  final Color color;
  final bool isActive;
  final double maxSpeed;

  const SpeedInfoCard({
    super.key,
    required this.title,
    required this.speed,
    required this.unit,
    required this.icon,
    required this.color,
    this.isActive = false,
    this.maxSpeed = 100,
  });

  @override
  State<SpeedInfoCard> createState() => _SpeedInfoCardState();
}

class _SpeedInfoCardState extends State<SpeedInfoCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _barAnimationController;

  @override
  void initState() {
    super.initState();
    _barAnimationController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );
    if (widget.isActive) {
      _barAnimationController.repeat(reverse: true);
    }
  }

  @override
  void didUpdateWidget(SpeedInfoCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.isActive && !_barAnimationController.isAnimating) {
      _barAnimationController.repeat(reverse: true);
    } else if (!widget.isActive && _barAnimationController.isAnimating) {
      _barAnimationController.stop();
      _barAnimationController.value = 0;
    }
  }

  @override
  void dispose() {
    _barAnimationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: widget.isActive ? 0.12 : 0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: widget.isActive ? widget.color.withValues(alpha: 0.5) : Colors.white.withValues(alpha: 0.1),
          width: widget.isActive ? 2 : 1,
        ),
        boxShadow: widget.isActive
            ? [
                BoxShadow(
                  color: widget.color.withValues(alpha: 0.2),
                  blurRadius: 20,
                  offset: const Offset(0, 4),
                ),
              ]
            : [],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: widget.color.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  widget.icon,
                  color: widget.color,
                  size: 20,
                ),
              ),
              const SizedBox(width: 10),
              Text(
                widget.title,
                style: AppTextStyles.labelMedium.copyWith(
                  color: Colors.white.withValues(alpha: 0.6),
                  letterSpacing: 1.2,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const Spacer(),
              if (widget.isActive)
                _buildPulsingDot(widget.color),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              TweenAnimationBuilder<double>(
                tween: Tween(begin: 0, end: widget.speed),
                duration: const Duration(milliseconds: 300),
                builder: (context, value, child) {
                  return Text(
                    value.toStringAsFixed(1),
                    style: AppTextStyles.h3.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  );
                },
              ),
              const SizedBox(width: 4),
              Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Text(
                  widget.unit,
                  style: AppTextStyles.bodySmall.copyWith(
                    color: Colors.white.withValues(alpha: 0.6),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Animated speed bar
          _buildAnimatedSpeedBar(),
        ],
      ),
    );
  }

  Widget _buildPulsingDot(Color color) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.5, end: 1.0),
      duration: const Duration(milliseconds: 800),
      builder: (context, value, child) {
        return Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color.withValues(alpha: value),
            boxShadow: [
              BoxShadow(
                color: color.withValues(alpha: value * 0.5),
                blurRadius: 6,
                spreadRadius: 2,
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildAnimatedSpeedBar() {
    // Calculate percentage based on actual speed vs max speed
    final percentage = (widget.speed / widget.maxSpeed).clamp(0.0, 1.0);
    
    // Determine bar color based on speed quality
    Color barColor;
    if (widget.speed == 0) {
      barColor = Colors.white.withValues(alpha: 0.3);
    } else if (widget.speed < 10) {
      barColor = const Color(0xFFFF6B6B); // Red - slow
    } else if (widget.speed < 25) {
      barColor = const Color(0xFFFFA726); // Orange - moderate
    } else if (widget.speed < 50) {
      barColor = const Color(0xFF8BC34A); // Light green - good
    } else {
      barColor = const Color(0xFF4CAF50); // Green - excellent
    }
    
    return LayoutBuilder(
      builder: (context, constraints) {
        final maxWidth = constraints.maxWidth;
        final barWidth = maxWidth * percentage;
        
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Speed bar container
            Container(
              height: 8,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Stack(
                children: [
                  // Animated fill bar
                  AnimatedBuilder(
                    animation: _barAnimationController,
                    builder: (context, child) {
                      return AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        width: barWidth,
                        height: 8,
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [
                              barColor.withValues(alpha: 0.6),
                              barColor,
                              barColor.withValues(alpha: widget.isActive ? 0.6 + (_barAnimationController.value * 0.4) : 1.0),
                            ],
                          ),
                          borderRadius: BorderRadius.circular(4),
                          boxShadow: widget.isActive
                              ? [
                                  BoxShadow(
                                    color: barColor.withValues(alpha: 0.5),
                                    blurRadius: 8,
                                    spreadRadius: 1,
                                  ),
                                ]
                              : [],
                        ),
                      );
                    },
                  ),
                  // Shimmer effect when active
                  if (widget.isActive && barWidth > 0)
                    AnimatedBuilder(
                      animation: _barAnimationController,
                      builder: (context, child) {
                        return Positioned(
                          left: barWidth * _barAnimationController.value - 20,
                          child: Container(
                            width: 40,
                            height: 8,
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [
                                  Colors.transparent,
                                  Colors.white.withValues(alpha: 0.4),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                ],
              ),
            ),
            const SizedBox(height: 6),
            // Speed label
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  _getSpeedQuality(),
                  style: AppTextStyles.labelMedium.copyWith(
                    color: barColor,
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  '${widget.speed.toStringAsFixed(1)} / ${widget.maxSpeed.toInt()} ${widget.unit}',
                  style: AppTextStyles.labelMedium.copyWith(
                    color: Colors.white.withValues(alpha: 0.5),
                    fontSize: 10,
                  ),
                ),
              ],
            ),
          ],
        );
      },
    );
  }

  String _getSpeedQuality() {
    if (widget.speed == 0) return '';
    if (widget.speed < 10) return 'Slow';
    if (widget.speed < 25) return 'Moderate';
    if (widget.speed < 50) return 'Good';
    if (widget.speed < 100) return 'Fast';
    return 'Excellent';
  }
}
