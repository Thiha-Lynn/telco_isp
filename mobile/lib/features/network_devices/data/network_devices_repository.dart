import '../../../core/api/api_service.dart';
import '../../../core/constants/api_config.dart';
import 'models/network_device_models.dart';
import 'services/network_scanner_service.dart';

/// Repository for Network Devices operations
class NetworkDevicesRepository {
  final ApiService _apiService;
  final NetworkScannerService _scannerService;

  NetworkDevicesRepository({
    ApiService? apiService,
    NetworkScannerService? scannerService,
  })  : _apiService = apiService ?? ApiService(),
        _scannerService = scannerService ?? NetworkScannerService();

  /// Get WiFi information from local device
  Future<WifiInfo> getWifiInfo() async {
    return await _scannerService.getWifiInfo();
  }

  /// Scan local network for devices
  Future<List<ScannedDevice>> scanLocalNetwork({
    Function(double progress, int devicesFound)? onProgress,
  }) async {
    _scannerService.onScanProgress = onProgress;
    return await _scannerService.scanNetwork();
  }

  /// Get bind user details including router/network info
  Future<BindUserNetworkInfo?> getBindUserNetworkInfo() async {
    try {
      // First get the list of bind users
      final listResponse = await _apiService.get<Map<String, dynamic>>(
        ApiConfig.bindUsers,
        fromJson: (data) => data as Map<String, dynamic>,
      );

      if (listResponse.success && listResponse.data != null) {
        final bindUsers = listResponse.data!['bind_users'] as List? ?? [];
        if (bindUsers.isNotEmpty) {
          // Get the first bind user's ID
          final firstBindUserId = bindUsers[0]['id'];
          
          // Get detailed info for this bind user
          final detailResponse = await _apiService.get<Map<String, dynamic>>(
            '${ApiConfig.bindUsers}/$firstBindUserId',
            fromJson: (data) => data as Map<String, dynamic>,
          );

          if (detailResponse.success && detailResponse.data != null) {
            final bindUser = detailResponse.data!['bind_user'];
            if (bindUser != null) {
              return BindUserNetworkInfo.fromJson(bindUser);
            }
          }
        }
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  /// Get network devices data including router info, traffic, and active devices
  Future<NetworkDevicesData> getNetworkDevices() async {
    try {
      // Try to get real data from bind user API
      final bindUserInfo = await getBindUserNetworkInfo();
      
      if (bindUserInfo != null) {
        return NetworkDevicesData(
          routerInfo: RouterInfo(
            onuId: null, // Not available in current API
            deviceSn: bindUserInfo.loid,
            tr069Profile: bindUserInfo.odbBox,
            authorizedDate: bindUserInfo.installationDate,
            totalDevices: 0,
            activeDevices: 0,
            // Extended router info
            routerType: bindUserInfo.routerType,
            pon: bindUserInfo.pon,
            fiberLength: bindUserInfo.fiberLength,
            opticalPower: bindUserInfo.opticalPower,
            odbRxPower: bindUserInfo.odbRxPower,
            lan: bindUserInfo.lan,
            bandwidth: bindUserInfo.bandwidth,
            serviceType: bindUserInfo.serviceType,
          ),
          trafficInfo: TrafficInfo(
            usageGb: 0.0,
            downloadGb: 0.0,
            uploadGb: 0.0,
            lastUpdated: null,
          ),
          activeDevices: [],
        );
      }
      
      // Return mock data if no bind user data
      return NetworkDevicesData.mock();
    } catch (e) {
      // Return mock data on error for demo purposes
      return NetworkDevicesData.mock();
    }
  }

  /// Get router information
  Future<RouterInfo> getRouterInfo() async {
    try {
      final response = await _apiService.get<Map<String, dynamic>>(
        '${ApiConfig.bindUsers}/router-info',
        fromJson: (data) => data as Map<String, dynamic>,
      );

      if (response.success && response.data != null) {
        return RouterInfo.fromJson(response.data!);
      }
      
      return RouterInfo();
    } catch (e) {
      return RouterInfo();
    }
  }

  /// Get today's traffic usage
  Future<TrafficInfo> getTrafficInfo() async {
    try {
      final response = await _apiService.get<Map<String, dynamic>>(
        '${ApiConfig.bindUsers}/traffic',
        fromJson: (data) => data as Map<String, dynamic>,
      );

      if (response.success && response.data != null) {
        return TrafficInfo.fromJson(response.data!);
      }
      
      return TrafficInfo();
    } catch (e) {
      return TrafficInfo();
    }
  }

  /// Get active devices connected to the network
  Future<List<ActiveDevice>> getActiveDevices() async {
    try {
      final response = await _apiService.get<Map<String, dynamic>>(
        '${ApiConfig.bindUsers}/active-devices',
        fromJson: (data) => data as Map<String, dynamic>,
      );

      if (response.success && response.data != null) {
        final devices = response.data!['devices'] as List? ?? [];
        return devices.map((e) => ActiveDevice.fromJson(e)).toList();
      }
      
      return [];
    } catch (e) {
      return [];
    }
  }
}
