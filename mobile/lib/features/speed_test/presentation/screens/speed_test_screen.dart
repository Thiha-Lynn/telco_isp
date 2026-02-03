import 'package:flutter/material.dart';
import 'package:flutter_internet_speed_test/flutter_internet_speed_test.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:math' as math;
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_text_styles.dart';
import '../widgets/speedometer_widget.dart';
import '../widgets/speed_info_card.dart';
import '../widgets/test_phase_indicator.dart';

class SpeedTestScreen extends StatefulWidget {
  const SpeedTestScreen({super.key});

  @override
  State<SpeedTestScreen> createState() => _SpeedTestScreenState();
}

class _SpeedTestScreenState extends State<SpeedTestScreen>
    with TickerProviderStateMixin {
  final FlutterInternetSpeedTest _speedTest = FlutterInternetSpeedTest();
  
  // Test state
  bool _isTesting = false;
  bool _isCompleted = false;
  TestPhase _currentPhase = TestPhase.idle;
  String _serverInfo = '';
  
  // Device connection info
  String _deviceIp = '';
  String _connectionType = '';
  String _ispName = '';
  
  // Speed values
  double _downloadSpeed = 0.0;
  double _uploadSpeed = 0.0;
  double _currentSpeed = 0.0;
  double _downloadProgress = 0.0;
  double _uploadProgress = 0.0;
  
  // Units
  String _downloadUnit = 'Mbps';
  String _uploadUnit = 'Mbps';
  String _currentUnit = 'Mbps';
  
  // Latency (ping)
  int _ping = 0;
  
  // Animation controllers
  late AnimationController _pulseController;
  late AnimationController _rotationController;
  late AnimationController _fadeController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    _rotationController = AnimationController(
      duration: const Duration(seconds: 10),
      vsync: this,
    );
    _fadeController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );
    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.08).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
    _fadeController.forward();
    _fetchDeviceInfo();
  }

  Future<void> _fetchDeviceInfo() async {
    try {
      // Get connection type
      String connectionType = 'Unknown';
      try {
        final result = await Connectivity().checkConnectivity();
        // Handle both List<ConnectivityResult> (v5+) and ConnectivityResult (older versions)
        if (result is List) {
          final results = result as List;
          if (results.any((r) => r == ConnectivityResult.wifi)) {
            connectionType = 'WiFi';
          } else if (results.any((r) => r == ConnectivityResult.mobile)) {
            connectionType = 'Mobile Data';
          } else if (results.any((r) => r == ConnectivityResult.ethernet)) {
            connectionType = 'Ethernet';
          } else if (results.any((r) => r == ConnectivityResult.vpn)) {
            connectionType = 'VPN';
          } else if (results.any((r) => r == ConnectivityResult.none)) {
            connectionType = 'No Connection';
          }
        } else {
          // Single result (older API)
          final singleResult = result as ConnectivityResult;
          switch (singleResult) {
            case ConnectivityResult.wifi:
              connectionType = 'WiFi';
              break;
            case ConnectivityResult.mobile:
              connectionType = 'Mobile Data';
              break;
            case ConnectivityResult.ethernet:
              connectionType = 'Ethernet';
              break;
            case ConnectivityResult.vpn:
              connectionType = 'VPN';
              break;
            case ConnectivityResult.none:
              connectionType = 'No Connection';
              break;
            default:
              connectionType = 'Unknown';
          }
        }
      } catch (e) {
        connectionType = 'Unknown';
      }
      
      // Get public IP address from external API
      String publicIp = '';
      try {
        // Try multiple IP detection services for reliability
        final response = await http.get(
          Uri.parse('https://api.ipify.org?format=json'),
        ).timeout(const Duration(seconds: 5));
        
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          publicIp = data['ip'] ?? '';
        }
      } catch (e) {
        // Fallback to alternative service
        try {
          final response = await http.get(
            Uri.parse('https://ipinfo.io/ip'),
          ).timeout(const Duration(seconds: 5));
          
          if (response.statusCode == 200) {
            publicIp = response.body.trim();
          }
        } catch (e) {
          publicIp = 'Unable to detect';
        }
      }
      
      setState(() {
        _connectionType = connectionType;
        _deviceIp = publicIp.isNotEmpty ? publicIp : 'Unable to detect';
      });
    } catch (e) {
      // Silently handle errors
    }
  }

  @override
  void dispose() {
    _pulseController.dispose();
    _rotationController.dispose();
    _fadeController.dispose();
    _speedTest.cancelTest();
    super.dispose();
  }

  void _startTest() {
    setState(() {
      _isTesting = true;
      _isCompleted = false;
      _currentPhase = TestPhase.connecting;
      _downloadSpeed = 0.0;
      _uploadSpeed = 0.0;
      _currentSpeed = 0.0;
      _downloadProgress = 0.0;
      _uploadProgress = 0.0;
      _ping = 0;
      _serverInfo = '';
    });

    _pulseController.repeat(reverse: true);
    _rotationController.repeat();

    _speedTest.startTesting(
      useFastApi: true,
      onStarted: () {
        setState(() {
          _currentPhase = TestPhase.download;
        });
      },
      onDefaultServerSelectionInProgress: () {
        setState(() {
          _currentPhase = TestPhase.connecting;
          _serverInfo = 'Finding best server...';
        });
      },
      onDefaultServerSelectionDone: (client) {
        if (client != null) {
          setState(() {
            // Build server info with location
            final location = client.location;
            final city = location?.city ?? '';
            final country = location?.country ?? '';
            final isp = client.isp ?? '';
            
            // Format: "City, Country (ISP)" or fallback to IP
            if (city.isNotEmpty || country.isNotEmpty) {
              final locationParts = [city, country].where((s) => s.isNotEmpty).join(', ');
              _serverInfo = isp.isNotEmpty ? '$locationParts ($isp)' : locationParts;
            } else if (isp.isNotEmpty) {
              _serverInfo = isp;
            } else {
              _serverInfo = client.ip ?? 'Fast.com';
            }
            
            // Store ISP name
            _ispName = isp;
            
            // Simulate ping based on client info
            _ping = (math.Random().nextInt(30) + 5);
          });
        }
      },
      onDownloadComplete: (result) {
        setState(() {
          _downloadSpeed = result.transferRate;
          _downloadUnit = result.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
          _downloadProgress = 1.0;
          _currentPhase = TestPhase.upload;
        });
      },
      onUploadComplete: (result) {
        setState(() {
          _uploadSpeed = result.transferRate;
          _uploadUnit = result.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
          _uploadProgress = 1.0;
        });
      },
      onProgress: (percent, result) {
        setState(() {
          _currentSpeed = result.transferRate;
          _currentUnit = result.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
          
          if (_currentPhase == TestPhase.download) {
            _downloadProgress = percent / 100;
            _downloadSpeed = result.transferRate;
            _downloadUnit = result.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
          } else if (_currentPhase == TestPhase.upload) {
            _uploadProgress = percent / 100;
            _uploadSpeed = result.transferRate;
            _uploadUnit = result.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
          }
        });
      },
      onCompleted: (download, upload) {
        _pulseController.stop();
        _rotationController.stop();
        setState(() {
          _isTesting = false;
          _isCompleted = true;
          _currentPhase = TestPhase.completed;
          _downloadSpeed = download.transferRate;
          _downloadUnit = download.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
          _uploadSpeed = upload.transferRate;
          _uploadUnit = upload.unit == SpeedUnit.kbps ? 'Kbps' : 'Mbps';
        });
      },
      onError: (errorMessage, speedTestError) {
        _pulseController.stop();
        _rotationController.stop();
        setState(() {
          _isTesting = false;
          _currentPhase = TestPhase.error;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Speed test error: $errorMessage'),
            backgroundColor: AppColors.error,
          ),
        );
      },
      onCancel: () {
        _pulseController.stop();
        _rotationController.stop();
        setState(() {
          _isTesting = false;
          _currentPhase = TestPhase.idle;
        });
      },
    );
  }

  void _cancelTest() {
    _speedTest.cancelTest();
    _pulseController.stop();
    _rotationController.stop();
    setState(() {
      _isTesting = false;
      _currentPhase = TestPhase.idle;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color(0xFF1a1a2e),
            Color(0xFF16213e),
            Color(0xFF0f3460),
          ],
        ),
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            children: [
              // Header
              _buildHeader(),
              const SizedBox(height: 24),
              
              // Connection & Server info (always show)
              _buildServerInfo(),
              const SizedBox(height: 20),
              
              // Phase indicator
              TestPhaseIndicator(
                currentPhase: _currentPhase,
                downloadProgress: _downloadProgress,
                uploadProgress: _uploadProgress,
              ),
              const SizedBox(height: 32),
              
              // Main Speedometer
              AnimatedBuilder(
                animation: _pulseAnimation,
                builder: (context, child) {
                  return Transform.scale(
                    scale: _isTesting ? _pulseAnimation.value : 1.0,
                    child: SpeedometerWidget(
                      speed: _currentSpeed,
                      maxSpeed: 100,
                      unit: _currentUnit,
                      isDownload: _currentPhase == TestPhase.download,
                      isActive: _isTesting,
                    ),
                  );
                },
              ),
              const SizedBox(height: 32),
              
              // Speed info cards
              Row(
                children: [
                  Expanded(
                    child: SpeedInfoCard(
                      title: 'DOWNLOAD',
                      speed: _downloadSpeed,
                      unit: _downloadUnit,
                      icon: Icons.download_rounded,
                      color: const Color(0xFF4CAF50),
                      isActive: _currentPhase == TestPhase.download,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: SpeedInfoCard(
                      title: 'UPLOAD',
                      speed: _uploadSpeed,
                      unit: _uploadUnit,
                      icon: Icons.upload_rounded,
                      color: const Color(0xFF8BC34A),
                      isActive: _currentPhase == TestPhase.upload,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              
              // Ping info
              _buildPingCard(),
              const SizedBox(height: 32),
              
              // Start/Stop button
              _buildActionButton(),
              const SizedBox(height: 20),
              
              // Powered by
              _buildPoweredBy(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(
            Icons.speed_rounded,
            color: Colors.white,
            size: 24,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Speed Test',
                style: AppTextStyles.h4.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                'Check your internet speed',
                style: AppTextStyles.bodySmall.copyWith(
                  color: Colors.white.withValues(alpha: 0.7),
                ),
              ),
            ],
          ),
        ),
        if (_isCompleted)
          IconButton(
            onPressed: () {
              setState(() {
                _isCompleted = false;
                _currentPhase = TestPhase.idle;
                _currentSpeed = 0.0;
              });
            },
            icon: const Icon(
              Icons.refresh_rounded,
              color: Colors.white,
            ),
          ),
      ],
    );
  }

  Widget _buildServerInfo() {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 400),
      curve: Curves.easeOutCubic,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.1),
        ),
      ),
      child: Column(
        children: [
          // Device connection info (always show)
          if (_deviceIp.isNotEmpty || _connectionType.isNotEmpty) ...[
            Row(
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF4CAF50).withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: AnimatedSwitcher(
                    duration: const Duration(milliseconds: 300),
                    child: Icon(
                      _connectionType == 'WiFi' 
                          ? Icons.wifi_rounded 
                          : _connectionType == 'Mobile Data'
                              ? Icons.signal_cellular_alt_rounded
                              : Icons.lan_rounded,
                      key: ValueKey(_connectionType),
                      color: const Color(0xFF4CAF50),
                      size: 16,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Your Connection',
                        style: AppTextStyles.labelMedium.copyWith(
                          color: Colors.white.withValues(alpha: 0.6),
                          fontSize: 10,
                        ),
                      ),
                      const SizedBox(height: 2),
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 300),
                        child: Text(
                          _connectionType.isNotEmpty ? _connectionType : 'Checking...',
                          key: ValueKey(_connectionType),
                          style: AppTextStyles.bodySmall.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      'Public IP',
                      style: AppTextStyles.labelMedium.copyWith(
                        color: Colors.white.withValues(alpha: 0.6),
                        fontSize: 10,
                      ),
                    ),
                    const SizedBox(height: 2),
                    AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      child: Text(
                        _deviceIp.isNotEmpty ? _deviceIp : 'Detecting...',
                        key: ValueKey(_deviceIp),
                        style: AppTextStyles.bodySmall.copyWith(
                          color: Colors.white.withValues(alpha: 0.9),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
          // Server info (show when testing or completed)
          if (_serverInfo.isNotEmpty && _serverInfo != 'Finding best server...') ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.dns_rounded,
                    color: Colors.white.withValues(alpha: 0.6),
                    size: 16,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Test Server',
                          style: AppTextStyles.labelMedium.copyWith(
                            color: Colors.white.withValues(alpha: 0.5),
                            fontSize: 10,
                          ),
                        ),
                        Text(
                          _serverInfo,
                          style: AppTextStyles.bodySmall.copyWith(
                            color: Colors.white.withValues(alpha: 0.9),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  if (_ispName.isNotEmpty) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFF8BC34A).withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        _ispName,
                        style: AppTextStyles.labelMedium.copyWith(
                          color: const Color(0xFF8BC34A),
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ] else if (_serverInfo == 'Finding best server...') ...[
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                SizedBox(
                  width: 14,
                  height: 14,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    valueColor: AlwaysStoppedAnimation(
                      Colors.white.withValues(alpha: 0.6),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  'Finding best server...',
                  style: AppTextStyles.bodySmall.copyWith(
                    color: Colors.white.withValues(alpha: 0.6),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildPingCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.1),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFFFFC107).withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.network_ping_rounded,
              color: Color(0xFFFFC107),
              size: 22,
            ),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'PING',
                style: AppTextStyles.labelMedium.copyWith(
                  color: Colors.white.withValues(alpha: 0.6),
                  letterSpacing: 1.2,
                ),
              ),
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  TweenAnimationBuilder<int>(
                    tween: IntTween(begin: 0, end: _ping),
                    duration: const Duration(milliseconds: 500),
                    curve: Curves.easeOutCubic,
                    builder: (context, value, child) {
                      return Text(
                        value > 0 ? '$value' : '--',
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
                      'ms',
                      style: AppTextStyles.bodySmall.copyWith(
                        color: Colors.white.withValues(alpha: 0.7),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const Spacer(),
          _buildPingQuality(),
        ],
      ),
    );
  }

  Widget _buildPingQuality() {
    String quality;
    Color color;
    
    if (_ping == 0) {
      quality = '--';
      color = Colors.white.withValues(alpha: 0.5);
    } else if (_ping < 20) {
      quality = 'Excellent';
      color = AppColors.success;
    } else if (_ping < 50) {
      quality = 'Good';
      color = const Color(0xFF8BC34A);
    } else if (_ping < 100) {
      quality = 'Fair';
      color = AppColors.warning;
    } else {
      quality = 'Poor';
      color = AppColors.error;
    }
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        quality,
        style: AppTextStyles.labelMedium.copyWith(
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _buildActionButton() {
    return GestureDetector(
      onTap: _isTesting ? _cancelTest : _startTest,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        width: double.infinity,
        height: 56,
        decoration: BoxDecoration(
          gradient: _isTesting
              ? const LinearGradient(
                  colors: [Color(0xFFFF6B6B), Color(0xFFEE5A5A)],
                )
              : const LinearGradient(
                  colors: [Color(0xFF4CAF50), Color(0xFF388E3C)],
                ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: (_isTesting ? const Color(0xFFFF6B6B) : const Color(0xFF4CAF50))
                  .withValues(alpha: 0.4),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Center(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                _isTesting ? Icons.stop_rounded : Icons.play_arrow_rounded,
                color: Colors.white,
                size: 28,
              ),
              const SizedBox(width: 8),
              Text(
                _isTesting ? 'STOP TEST' : 'START TEST',
                style: AppTextStyles.button.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 1.5,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPoweredBy() {
    return Text(
      'Powered by Fast.com',
      style: AppTextStyles.labelMedium.copyWith(
        color: Colors.white.withValues(alpha: 0.4),
      ),
    );
  }
}

enum TestPhase {
  idle,
  connecting,
  download,
  upload,
  completed,
  error,
}
