import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:animate_do/animate_do.dart';
import 'package:permission_handler/permission_handler.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_text_styles.dart';
import '../../data/models/network_device_models.dart';
import '../../data/network_devices_repository.dart';
import '../../data/services/network_scanner_service.dart';

class NetworkDevicesScreen extends StatefulWidget {
  const NetworkDevicesScreen({super.key});

  @override
  State<NetworkDevicesScreen> createState() => _NetworkDevicesScreenState();
}

class _NetworkDevicesScreenState extends State<NetworkDevicesScreen>
    with SingleTickerProviderStateMixin {
  final NetworkDevicesRepository _repository = NetworkDevicesRepository();
  
  // API Data
  NetworkDevicesData? _apiData;
  bool _isLoadingApi = true;
  
  // Local Network Scan Data
  WifiInfo? _wifiInfo;
  List<ScannedDevice> _scannedDevices = [];
  bool _isScanning = false;
  double _scanProgress = 0.0;
  bool _hasLocationPermission = false;
  
  // Tab Controller
  late TabController _tabController;
  
  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _checkPermissions();
    _loadData();
  }
  
  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
  
  Future<void> _checkPermissions() async {
    if (Platform.isAndroid || Platform.isIOS) {
      final status = await Permission.locationWhenInUse.status;
      setState(() {
        _hasLocationPermission = status.isGranted;
      });
    } else {
      // On other platforms, assume permission is granted
      setState(() {
        _hasLocationPermission = true;
      });
    }
  }
  
  Future<bool> _requestLocationPermission() async {
    if (Platform.isAndroid || Platform.isIOS) {
      final status = await Permission.locationWhenInUse.request();
      setState(() {
        _hasLocationPermission = status.isGranted;
      });
      return status.isGranted;
    }
    return true;
  }

  Future<void> _loadData() async {
    await Future.wait([
      _loadApiData(),
      _loadWifiInfo(),
    ]);
  }
  
  Future<void> _loadApiData() async {
    setState(() {
      _isLoadingApi = true;
    });

    try {
      final data = await _repository.getNetworkDevices();
      setState(() {
        _apiData = data;
        _isLoadingApi = false;
      });
    } catch (e) {
      setState(() {
        _isLoadingApi = false;
      });
    }
  }
  
  Future<void> _loadWifiInfo() async {
    if (!_hasLocationPermission) {
      final granted = await _requestLocationPermission();
      if (!granted) return;
    }
    
    try {
      final wifiInfo = await _repository.getWifiInfo();
      setState(() {
        _wifiInfo = wifiInfo;
      });
    } catch (e) {
      // Ignore errors
    }
  }
  
  Future<void> _startNetworkScan() async {
    if (_isScanning) return;
    
    // Network scan doesn't require location permission
    // Only WiFi info (SSID, BSSID) requires it on Android
    
    setState(() {
      _isScanning = true;
      _scanProgress = 0.0;
      _scannedDevices = [];
    });
    
    try {
      final devices = await _repository.scanLocalNetwork(
        onProgress: (progress, devicesFound) {
          setState(() {
            _scanProgress = progress;
          });
        },
      );
      
      setState(() {
        _scannedDevices = devices;
        _isScanning = false;
        _scanProgress = 1.0;
      });
    } catch (e) {
      setState(() {
        _isScanning = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Scan failed: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Tab Bar
        Container(
          margin: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.grey.shade200,
            borderRadius: BorderRadius.circular(12),
          ),
          child: TabBar(
            controller: _tabController,
            indicator: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(10),
            ),
            labelColor: Colors.white,
            unselectedLabelColor: AppColors.textSecondary,
            dividerColor: Colors.transparent,
            indicatorSize: TabBarIndicatorSize.tab,
            tabs: const [
              Tab(text: 'Local Network'),
              Tab(text: 'Router Info'),
            ],
          ),
        ),
        
        // Tab Content
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              _buildLocalNetworkTab(),
              _buildRouterInfoTab(),
            ],
          ),
        ),
      ],
    );
  }
  
  // ===== LOCAL NETWORK TAB =====
  Widget _buildLocalNetworkTab() {
    return RefreshIndicator(
      onRefresh: () async {
        await _loadWifiInfo();
        if (_scannedDevices.isNotEmpty) {
          await _startNetworkScan();
        }
      },
      color: AppColors.primary,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // WiFi Info Card
            if (_wifiInfo != null)
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                child: _buildWifiInfoCard(_wifiInfo!),
              ),
            
            const SizedBox(height: 20),
            
            // Scan Button
            FadeInUp(
              duration: const Duration(milliseconds: 400),
              delay: const Duration(milliseconds: 100),
              child: _buildScanButton(),
            ),
            
            // Scan Progress
            if (_isScanning) ...[
              const SizedBox(height: 16),
              FadeIn(
                child: _buildScanProgressCard(),
              ),
            ],
            
            // Scanned Devices
            if (_scannedDevices.isNotEmpty) ...[
              const SizedBox(height: 24),
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 200),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Discovered Devices',
                      style: AppTextStyles.h5.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.success.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '${_scannedDevices.length} found',
                        style: AppTextStyles.labelMedium.copyWith(
                          color: AppColors.success,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              ..._scannedDevices.asMap().entries.map((entry) {
                final index = entry.key;
                final device = entry.value;
                return FadeInUp(
                  duration: const Duration(milliseconds: 400),
                  delay: Duration(milliseconds: 250 + (index * 50)),
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _buildScannedDeviceCard(device),
                  ),
                );
              }),
            ],
            
            if (!_isScanning && _scannedDevices.isEmpty)
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 200),
                child: _buildScanPromptCard(),
              ),
            
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
  
  Widget _buildWifiInfoCard(WifiInfo wifiInfo) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppColors.primary,
            AppColors.primary.withValues(alpha: 0.8),
          ],
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.3),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  wifiInfo.isConnected ? Icons.wifi_rounded : Icons.wifi_off_rounded,
                  color: Colors.white,
                  size: 28,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Connected to WiFi',
                      style: AppTextStyles.bodySmall.copyWith(
                        color: Colors.white.withValues(alpha: 0.8),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      wifiInfo.ssid,
                      style: AppTextStyles.h4.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  wifiInfo.isConnected ? 'Connected' : 'Disconnected',
                  style: AppTextStyles.labelMedium.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                _buildWifiInfoRow(Icons.my_location_rounded, 'Your IP', wifiInfo.localIP),
                const SizedBox(height: 12),
                _buildWifiInfoRow(Icons.router_rounded, 'Gateway', wifiInfo.gatewayIP),
                if (wifiInfo.bssid.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _buildWifiInfoRow(Icons.qr_code_rounded, 'BSSID', wifiInfo.bssid),
                ],
                if (wifiInfo.submask.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _buildWifiInfoRow(Icons.grid_view_rounded, 'Subnet', wifiInfo.submask),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildWifiInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, color: Colors.white.withValues(alpha: 0.7), size: 18),
        const SizedBox(width: 8),
        Text(
          label,
          style: AppTextStyles.bodySmall.copyWith(
            color: Colors.white.withValues(alpha: 0.7),
          ),
        ),
        const Spacer(),
        GestureDetector(
          onTap: () => _copyToClipboard(value),
          child: Row(
            children: [
              Text(
                value.isEmpty ? '-' : value,
                style: AppTextStyles.bodyMedium.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(width: 8),
              Icon(
                Icons.copy_rounded,
                color: Colors.white.withValues(alpha: 0.7),
                size: 16,
              ),
            ],
          ),
        ),
      ],
    );
  }
  
  Widget _buildScanButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: _isScanning ? null : _startNetworkScan,
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF4CAF50),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          disabledBackgroundColor: Colors.grey.shade300,
        ),
        icon: Icon(
          _isScanning ? Icons.hourglass_top_rounded : Icons.radar_rounded,
          size: 24,
        ),
        label: Text(
          _isScanning ? 'Scanning...' : 'Scan Local Network',
          style: AppTextStyles.button.copyWith(
            color: Colors.white,
          ),
        ),
      ),
    );
  }
  
  Widget _buildScanProgressCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  strokeWidth: 3,
                  valueColor: AlwaysStoppedAnimation<Color>(AppColors.primary),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Scanning Network...',
                      style: AppTextStyles.bodyLarge.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Found ${_scannedDevices.length} devices',
                      style: AppTextStyles.bodySmall.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              Text(
                '${(_scanProgress * 100).toInt()}%',
                style: AppTextStyles.h5.copyWith(
                  fontWeight: FontWeight.bold,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: _scanProgress,
              minHeight: 8,
              backgroundColor: Colors.grey.shade200,
              valueColor: AlwaysStoppedAnimation<Color>(const Color(0xFF4CAF50)),
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildScannedDeviceCard(ScannedDevice device) {
    final deviceIcon = _getDeviceIcon(device.estimatedDeviceType);
    
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.success.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              deviceIcon,
              color: AppColors.success,
              size: 24,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  device.displayName,
                  style: AppTextStyles.bodyLarge.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(
                      Icons.lan_rounded,
                      size: 14,
                      color: AppColors.textSecondary,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      device.ipAddress,
                      style: AppTextStyles.bodySmall.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: () => _copyToClipboard(device.ipAddress),
            icon: Icon(
              Icons.copy_rounded,
              color: AppColors.textLight,
              size: 20,
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildScanPromptCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(32),
      margin: const EdgeInsets.only(top: 20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(
            Icons.radar_rounded,
            size: 64,
            color: AppColors.primary.withValues(alpha: 0.3),
          ),
          const SizedBox(height: 16),
          Text(
            'Discover Devices',
            style: AppTextStyles.h5.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Tap the scan button above to discover\ndevices connected to your network',
            style: AppTextStyles.bodySmall.copyWith(
              color: AppColors.textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
  
  // ===== ROUTER INFO TAB =====
  Widget _buildRouterInfoTab() {
    if (_isLoadingApi) {
      return const Center(child: CircularProgressIndicator());
    }
    
    if (_apiData == null) {
      return _buildErrorState();
    }
    
    return _buildRouterContent();
  }
  
  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.error_outline_rounded,
            size: 64,
            color: AppColors.error.withValues(alpha: 0.5),
          ),
          const SizedBox(height: 16),
          Text(
            'Failed to load data',
            style: AppTextStyles.bodyLarge.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _loadApiData,
            icon: const Icon(Icons.refresh),
            label: const Text('Retry'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRouterContent() {
    final data = _apiData!;

    return RefreshIndicator(
      onRefresh: _loadApiData,
      color: AppColors.primary,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Router Information Card
            FadeInUp(
              duration: const Duration(milliseconds: 400),
              child: _buildRouterInfoCard(data.routerInfo),
            ),
            const SizedBox(height: 16),

            // Today's Traffic Card
            FadeInUp(
              duration: const Duration(milliseconds: 400),
              delay: const Duration(milliseconds: 100),
              child: _buildTrafficCard(data.trafficInfo),
            ),
            const SizedBox(height: 24),

            // Active Devices Section
            FadeInUp(
              duration: const Duration(milliseconds: 400),
              delay: const Duration(milliseconds: 200),
              child: Text(
                'Connected Devices',
                style: AppTextStyles.h5.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(height: 12),

            // Device List
            ...data.activeDevices.asMap().entries.map((entry) {
              final index = entry.key;
              final device = entry.value;
              return FadeInUp(
                duration: const Duration(milliseconds: 400),
                delay: Duration(milliseconds: 250 + (index * 50)),
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _buildDeviceCard(device),
                ),
              );
            }),

            if (data.activeDevices.isEmpty)
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 300),
                child: _buildEmptyDevicesState(),
              ),

            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildRouterInfoCard(RouterInfo router) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.router_rounded,
                  color: AppColors.primary,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Router Information',
                  style: AppTextStyles.h5.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          if (router.routerType != null && router.routerType!.isNotEmpty) ...[
            _buildInfoRow('Router Type', router.routerType!),
            const Divider(height: 24),
          ],
          if (router.deviceSn != null && router.deviceSn!.isNotEmpty) ...[
            _buildInfoRowWithCopy('LOID / Device SN', router.deviceSn!),
            const Divider(height: 24),
          ],
          if (router.tr069Profile != null && router.tr069Profile!.isNotEmpty) ...[
            _buildInfoRow('ODB Box', router.tr069Profile!),
            const Divider(height: 24),
          ],
          if (router.pon != null && router.pon!.isNotEmpty) ...[
            _buildInfoRow('PON', router.pon!),
            const Divider(height: 24),
          ],
          if (router.bandwidth != null && router.bandwidth!.isNotEmpty) ...[
            _buildInfoRow('Bandwidth', router.bandwidth!),
            const Divider(height: 24),
          ],
          if (router.serviceType != null && router.serviceType!.isNotEmpty) ...[
            _buildInfoRow('Service Type', router.serviceType!),
            const Divider(height: 24),
          ],
          if (router.fiberLength != null && router.fiberLength!.isNotEmpty) ...[
            _buildInfoRow('Fiber Length', router.fiberLength!),
            const Divider(height: 24),
          ],
          if (router.opticalPower != null && router.opticalPower!.isNotEmpty) ...[
            _buildInfoRow('Optical Power', router.opticalPower!),
            const Divider(height: 24),
          ],
          if (router.odbRxPower != null && router.odbRxPower!.isNotEmpty) ...[
            _buildInfoRow('ODB RX Power', router.odbRxPower!),
            const Divider(height: 24),
          ],
          if (router.lan != null && router.lan!.isNotEmpty) ...[
            _buildInfoRow('LAN', router.lan!),
            const Divider(height: 24),
          ],
          if (router.authorizedDate != null && router.authorizedDate!.isNotEmpty) ...[
            _buildInfoRow('Installation Date', router.authorizedDate!),
            const Divider(height: 24),
          ],
          // Only show device count if we have data
          if (router.totalDevices > 0)
            _buildInfoRow(
              'Total Devices',
              '${router.totalDevices} (${router.activeDevices} Active)',
              valueColor: AppColors.success,
            ),
          // Show a message if no data available
          if (router.routerType == null && router.deviceSn == null && router.bandwidth == null)
            Center(
              child: Column(
                children: [
                  Icon(
                    Icons.info_outline_rounded,
                    size: 48,
                    color: AppColors.textLight.withValues(alpha: 0.5),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No router information available',
                    style: AppTextStyles.bodyMedium.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Please bind your account first',
                    style: AppTextStyles.bodySmall.copyWith(
                      color: AppColors.textLight,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildTrafficCard(TrafficInfo traffic) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.sync_rounded,
                  color: Colors.blue,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                "Today's Traffic",
                style: AppTextStyles.h5.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: _buildTrafficStat(
                  'Usage',
                  traffic.formattedUsage,
                  Icons.data_usage_rounded,
                  AppColors.primary,
                ),
              ),
              Container(
                width: 1,
                height: 50,
                color: AppColors.divider,
              ),
              Expanded(
                child: _buildTrafficStat(
                  'Download',
                  '${traffic.downloadGb.toStringAsFixed(2)} GB',
                  Icons.download_rounded,
                  Colors.green,
                ),
              ),
              Container(
                width: 1,
                height: 50,
                color: AppColors.divider,
              ),
              Expanded(
                child: _buildTrafficStat(
                  'Upload',
                  '${traffic.uploadGb.toStringAsFixed(2)} GB',
                  Icons.upload_rounded,
                  Colors.orange,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTrafficStat(
    String label,
    String value,
    IconData icon,
    Color color,
  ) {
    return Column(
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: 8),
        Text(
          value,
          style: AppTextStyles.h5.copyWith(
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: AppTextStyles.bodySmall.copyWith(
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }

  Widget _buildDeviceCard(ActiveDevice device) {
    final deviceIcon = _getDeviceIcon(device.deviceType);
    
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Row
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.success.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  deviceIcon,
                  color: AppColors.success,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  device.name,
                  style: AppTextStyles.bodyLarge.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: device.isActive
                      ? AppColors.success
                      : AppColors.textLight,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  device.isActive ? 'Active' : 'Inactive',
                  style: AppTextStyles.labelMedium.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          
          // Device Details
          _buildDeviceDetailRow('MAC:', device.macAddress),
          const SizedBox(height: 8),
          _buildDeviceDetailRow('IP:', device.ipAddress),
          if (device.port != null && device.port!.isNotEmpty) ...[
            const SizedBox(height: 8),
            _buildDeviceDetailRow('Port:', device.port!, wrap: true),
          ],
        ],
      ),
    );
  }

  Widget _buildDeviceDetailRow(String label, String value, {bool wrap = false}) {
    return Row(
      crossAxisAlignment: wrap ? CrossAxisAlignment.start : CrossAxisAlignment.center,
      children: [
        SizedBox(
          width: 50,
          child: Text(
            label,
            style: AppTextStyles.bodySmall.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: AppTextStyles.bodyMedium.copyWith(
              fontWeight: FontWeight.w500,
            ),
            maxLines: wrap ? 2 : 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
        GestureDetector(
          onTap: () => _copyToClipboard(value),
          child: Icon(
            Icons.copy_rounded,
            size: 18,
            color: AppColors.textLight,
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRow(String label, String value, {Color? valueColor}) {
    return Row(
      children: [
        Expanded(
          flex: 2,
          child: Text(
            label,
            style: AppTextStyles.bodyMedium.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ),
        Expanded(
          flex: 3,
          child: Text(
            value,
            style: AppTextStyles.bodyMedium.copyWith(
              fontWeight: FontWeight.w600,
              color: valueColor ?? AppColors.textPrimary,
            ),
            textAlign: TextAlign.end,
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRowWithCopy(String label, String value) {
    return Row(
      children: [
        Expanded(
          flex: 2,
          child: Text(
            label,
            style: AppTextStyles.bodyMedium.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ),
        Expanded(
          flex: 3,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Flexible(
                child: Text(
                  value,
                  style: AppTextStyles.bodyMedium.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                  overflow: TextOverflow.ellipsis,
                  maxLines: 1,
                ),
              ),
              const SizedBox(width: 8),
              GestureDetector(
                onTap: () => _copyToClipboard(value),
                child: Icon(
                  Icons.copy_rounded,
                  size: 18,
                  color: AppColors.textLight,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyDevicesState() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(
            Icons.devices_other_rounded,
            size: 64,
            color: AppColors.textLight.withValues(alpha: 0.5),
          ),
          const SizedBox(height: 16),
          Text(
            'No Active Devices',
            style: AppTextStyles.bodyLarge.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'No devices are currently connected to your network',
            style: AppTextStyles.bodySmall.copyWith(
              color: AppColors.textLight,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  IconData _getDeviceIcon(String? deviceType) {
    switch (deviceType?.toLowerCase()) {
      case 'phone':
      case 'mobile':
        return Icons.phone_android_rounded;
      case 'tablet':
        return Icons.tablet_android_rounded;
      case 'computer':
      case 'desktop':
      case 'laptop':
        return Icons.computer_rounded;
      case 'tv':
      case 'smart_tv':
        return Icons.tv_rounded;
      case 'iot':
      case 'smart_home':
        return Icons.home_rounded;
      case 'game':
      case 'gaming':
        return Icons.sports_esports_rounded;
      case 'router':
        return Icons.router_rounded;
      case 'printer':
        return Icons.print_rounded;
      default:
        return Icons.wifi_rounded;
    }
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.check_circle, color: Colors.white, size: 20),
            const SizedBox(width: 8),
            Text('Copied: $text'),
          ],
        ),
        backgroundColor: AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
        duration: const Duration(seconds: 2),
      ),
    );
  }
}
