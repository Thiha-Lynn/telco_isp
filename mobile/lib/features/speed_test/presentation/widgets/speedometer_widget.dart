import 'package:flutter/material.dart';
import 'dart:math' as math;
import '../../../../core/constants/app_text_styles.dart';

class SpeedometerWidget extends StatefulWidget {
  final double speed;
  final double maxSpeed;
  final String unit;
  final bool isDownload;
  final bool isActive;

  const SpeedometerWidget({
    super.key,
    required this.speed,
    required this.maxSpeed,
    required this.unit,
    this.isDownload = true,
    this.isActive = false,
  });

  @override
  State<SpeedometerWidget> createState() => _SpeedometerWidgetState();
}

class _SpeedometerWidgetState extends State<SpeedometerWidget>
    with TickerProviderStateMixin {
  late AnimationController _glowController;
  late AnimationController _speedController;
  late Animation<double> _glowAnimation;
  late Animation<double> _speedAnimation;
  double _previousSpeed = 0;

  @override
  void initState() {
    super.initState();
    _glowController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    _speedController = AnimationController(
      duration: const Duration(milliseconds: 400),
      vsync: this,
    );
    _glowAnimation = Tween<double>(begin: 0.3, end: 0.8).animate(
      CurvedAnimation(parent: _glowController, curve: Curves.easeInOut),
    );
    _speedAnimation = Tween<double>(begin: 0, end: widget.speed).animate(
      CurvedAnimation(parent: _speedController, curve: Curves.easeOutCubic),
    );
    if (widget.isActive) {
      _glowController.repeat(reverse: true);
    }
    _speedController.forward();
  }

  @override
  void didUpdateWidget(SpeedometerWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.isActive && !_glowController.isAnimating) {
      _glowController.repeat(reverse: true);
    } else if (!widget.isActive && _glowController.isAnimating) {
      _glowController.stop();
    }
    
    // Animate speed changes smoothly
    if (widget.speed != oldWidget.speed) {
      _previousSpeed = oldWidget.speed;
      _speedAnimation = Tween<double>(
        begin: _previousSpeed,
        end: widget.speed,
      ).animate(
        CurvedAnimation(parent: _speedController, curve: Curves.easeOutCubic),
      );
      _speedController.reset();
      _speedController.forward();
    }
  }

  @override
  void dispose() {
    _glowController.dispose();
    _speedController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: Listenable.merge([_glowAnimation, _speedAnimation]),
      builder: (context, child) {
        final animatedSpeed = _speedAnimation.value;
        return Container(
          width: 280,
          height: 280,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            boxShadow: widget.isActive
                ? [
                    BoxShadow(
                      color: (widget.isDownload
                              ? const Color(0xFF4CAF50)
                              : const Color(0xFF8BC34A))
                          .withValues(alpha: _glowAnimation.value * 0.5),
                      blurRadius: 40,
                      spreadRadius: 10,
                    ),
                  ]
                : [],
          ),
          child: CustomPaint(
            painter: SpeedometerPainter(
              speed: animatedSpeed,
              maxSpeed: widget.maxSpeed,
              isDownload: widget.isDownload,
              isActive: widget.isActive,
              glowIntensity: widget.isActive ? _glowAnimation.value : 0.3,
            ),
            child: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    animatedSpeed.toStringAsFixed(1),
                    style: const TextStyle(
                      fontSize: 56,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      height: 1,
                    ),
                  ),
                  const SizedBox(height: 4),
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      widget.unit,
                      style: AppTextStyles.bodyMedium.copyWith(
                        color: Colors.white.withValues(alpha: 0.8),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  AnimatedSwitcher(
                    duration: const Duration(milliseconds: 300),
                    child: widget.isActive
                        ? Text(
                            widget.isDownload ? 'Downloading...' : 'Uploading...',
                            key: ValueKey(widget.isDownload),
                            style: AppTextStyles.bodySmall.copyWith(
                              color: widget.isDownload
                                  ? const Color(0xFF4CAF50)
                                  : const Color(0xFF8BC34A),
                              fontWeight: FontWeight.w500,
                            ),
                          )
                        : const SizedBox.shrink(),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class SpeedometerPainter extends CustomPainter {
  final double speed;
  final double maxSpeed;
  final bool isDownload;
  final bool isActive;
  final double glowIntensity;

  SpeedometerPainter({
    required this.speed,
    required this.maxSpeed,
    required this.isDownload,
    required this.isActive,
    required this.glowIntensity,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;
    
    // Background circle
    final bgPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.05)
      ..style = PaintingStyle.fill;
    canvas.drawCircle(center, radius, bgPaint);
    
    // Outer ring
    final outerRingPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.1)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    canvas.drawCircle(center, radius - 10, outerRingPaint);
    
    // Arc background
    final arcRect = Rect.fromCircle(center: center, radius: radius - 30);
    final arcBgPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.1)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 16
      ..strokeCap = StrokeCap.round;
    
    // Draw background arc (270 degrees, starting from bottom-left)
    canvas.drawArc(
      arcRect,
      math.pi * 0.75, // Start from bottom-left (135 degrees)
      math.pi * 1.5,   // Sweep 270 degrees
      false,
      arcBgPaint,
    );
    
    // Speed arc with gradient
    final speedPercent = (speed / maxSpeed).clamp(0.0, 1.0);
    final sweepAngle = math.pi * 1.5 * speedPercent;
    
    if (speedPercent > 0) {
      final gradient = SweepGradient(
        startAngle: math.pi * 0.75,
        endAngle: math.pi * 0.75 + sweepAngle,
        colors: isDownload
            ? [
                const Color(0xFF4CAF50),
                const Color(0xFF66BB6A),
                const Color(0xFF81C784),
              ]
            : [
                const Color(0xFF8BC34A),
                const Color(0xFF9CCC65),
                const Color(0xFFAED581),
              ],
        tileMode: TileMode.clamp,
      );
      
      final arcPaint = Paint()
        ..shader = gradient.createShader(arcRect)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 16
        ..strokeCap = StrokeCap.round;
      
      canvas.drawArc(
        arcRect,
        math.pi * 0.75,
        sweepAngle,
        false,
        arcPaint,
      );
      
      // Glowing dot at the end
      if (isActive) {
        final dotAngle = math.pi * 0.75 + sweepAngle;
        final dotX = center.dx + (radius - 30) * math.cos(dotAngle);
        final dotY = center.dy + (radius - 30) * math.sin(dotAngle);
        
        final dotGlowPaint = Paint()
          ..color = (isDownload ? const Color(0xFF4CAF50) : const Color(0xFF8BC34A))
              .withValues(alpha: glowIntensity)
          ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 8);
        canvas.drawCircle(Offset(dotX, dotY), 12, dotGlowPaint);
        
        final dotPaint = Paint()
          ..color = Colors.white
          ..style = PaintingStyle.fill;
        canvas.drawCircle(Offset(dotX, dotY), 6, dotPaint);
      }
    }
    
    // Draw scale markers
    _drawScaleMarkers(canvas, center, radius - 30);
  }

  void _drawScaleMarkers(Canvas canvas, Offset center, double radius) {
    final markerPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.4)
      ..strokeWidth = 2
      ..strokeCap = StrokeCap.round;
    
    final textPainter = TextPainter(
      textDirection: TextDirection.ltr,
      textAlign: TextAlign.center,
    );
    
    // Draw markers every 25 units (0, 25, 50, 75, 100)
    final labels = ['0', '25', '50', '75', '100'];
    for (int i = 0; i < 5; i++) {
      final angle = math.pi * 0.75 + (math.pi * 1.5 * i / 4);
      
      // Outer marker position
      final outerX = center.dx + (radius + 8) * math.cos(angle);
      final outerY = center.dy + (radius + 8) * math.sin(angle);
      
      // Inner marker position
      final innerX = center.dx + (radius - 8) * math.cos(angle);
      final innerY = center.dy + (radius - 8) * math.sin(angle);
      
      canvas.drawLine(
        Offset(innerX, innerY),
        Offset(outerX, outerY),
        markerPaint,
      );
      
      // Draw label
      final labelX = center.dx + (radius + 24) * math.cos(angle);
      final labelY = center.dy + (radius + 24) * math.sin(angle);
      
      textPainter.text = TextSpan(
        text: labels[i],
        style: TextStyle(
          color: Colors.white.withValues(alpha: 0.5),
          fontSize: 11,
          fontWeight: FontWeight.w500,
        ),
      );
      textPainter.layout();
      textPainter.paint(
        canvas,
        Offset(labelX - textPainter.width / 2, labelY - textPainter.height / 2),
      );
    }
    
    // Draw small tick marks
    for (int i = 0; i < 20; i++) {
      if (i % 5 == 0) continue; // Skip major markers
      
      final angle = math.pi * 0.75 + (math.pi * 1.5 * i / 20);
      
      final outerX = center.dx + (radius + 4) * math.cos(angle);
      final outerY = center.dy + (radius + 4) * math.sin(angle);
      
      final innerX = center.dx + (radius - 4) * math.cos(angle);
      final innerY = center.dy + (radius - 4) * math.sin(angle);
      
      final tickPaint = Paint()
        ..color = Colors.white.withValues(alpha: 0.2)
        ..strokeWidth = 1
        ..strokeCap = StrokeCap.round;
      
      canvas.drawLine(
        Offset(innerX, innerY),
        Offset(outerX, outerY),
        tickPaint,
      );
    }
  }

  @override
  bool shouldRepaint(SpeedometerPainter oldDelegate) {
    return speed != oldDelegate.speed ||
        isActive != oldDelegate.isActive ||
        glowIntensity != oldDelegate.glowIntensity;
  }
}
