import 'package:flutter/material.dart';
import '../../../../core/constants/app_text_styles.dart';
import '../screens/speed_test_screen.dart';

class TestPhaseIndicator extends StatelessWidget {
  final TestPhase currentPhase;
  final double downloadProgress;
  final double uploadProgress;

  const TestPhaseIndicator({
    super.key,
    required this.currentPhase,
    required this.downloadProgress,
    required this.uploadProgress,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: _buildPhaseStep(
              icon: Icons.dns_rounded,
              label: 'Server',
              isActive: currentPhase == TestPhase.connecting,
              isCompleted: currentPhase.index > TestPhase.connecting.index,
              color: const Color(0xFF8E8EFF),
            ),
          ),
          _buildConnector(
            isActive: currentPhase.index >= TestPhase.download.index,
          ),
          Expanded(
            child: _buildPhaseStep(
              icon: Icons.download_rounded,
              label: 'Download',
              isActive: currentPhase == TestPhase.download,
              isCompleted: currentPhase.index > TestPhase.download.index,
              progress: downloadProgress,
              color: const Color(0xFF4CAF50),
            ),
          ),
          _buildConnector(
            isActive: currentPhase.index >= TestPhase.upload.index,
          ),
          Expanded(
            child: _buildPhaseStep(
              icon: Icons.upload_rounded,
              label: 'Upload',
              isActive: currentPhase == TestPhase.upload,
              isCompleted: currentPhase.index > TestPhase.upload.index,
              progress: uploadProgress,
              color: const Color(0xFF8BC34A),
            ),
          ),
          _buildConnector(
            isActive: currentPhase == TestPhase.completed,
          ),
          Expanded(
            child: _buildPhaseStep(
              icon: Icons.check_circle_rounded,
              label: 'Done',
              isActive: currentPhase == TestPhase.completed,
              isCompleted: currentPhase == TestPhase.completed,
              color: const Color(0xFF4CAF50),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPhaseStep({
    required IconData icon,
    required String label,
    required bool isActive,
    required bool isCompleted,
    required Color color,
    double? progress,
  }) {
    final displayColor = isActive || isCompleted
        ? color
        : Colors.white.withValues(alpha: 0.3);
    
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Stack(
          alignment: Alignment.center,
          children: [
            // Progress ring for active phase
            if (isActive && progress != null)
              SizedBox(
                width: 36,
                height: 36,
                child: CircularProgressIndicator(
                  value: progress,
                  strokeWidth: 3,
                  backgroundColor: Colors.white.withValues(alpha: 0.1),
                  valueColor: AlwaysStoppedAnimation(color),
                ),
              ),
            // Icon container
            AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              width: isActive ? 32 : 28,
              height: isActive ? 32 : 28,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isActive || isCompleted
                    ? color.withValues(alpha: 0.2)
                    : Colors.white.withValues(alpha: 0.05),
                border: Border.all(
                  color: displayColor,
                  width: isActive ? 2 : 1,
                ),
              ),
              child: Icon(
                isCompleted ? Icons.check_rounded : icon,
                color: displayColor,
                size: isActive ? 16 : 14,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: AppTextStyles.labelMedium.copyWith(
            color: displayColor,
            fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
            fontSize: 10,
          ),
        ),
      ],
    );
  }

  Widget _buildConnector({required bool isActive}) {
    return Expanded(
      child: Container(
        height: 2,
        margin: const EdgeInsets.only(bottom: 20),
        decoration: BoxDecoration(
          gradient: isActive
              ? const LinearGradient(
                  colors: [
                    Color(0xFF4CAF50),
                    Color(0xFF8BC34A),
                  ],
                )
              : null,
          color: isActive ? null : Colors.white.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(1),
        ),
      ),
    );
  }
}
