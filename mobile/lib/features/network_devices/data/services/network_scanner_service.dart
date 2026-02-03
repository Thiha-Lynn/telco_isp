import 'dart:async';
import 'dart:io';
import 'package:network_info_plus/network_info_plus.dart';

/// Service for scanning local network and discovering devices
class NetworkScannerService {
  final NetworkInfo _networkInfo = NetworkInfo();

  /// Callback for scan progress updates
  Function(double progress, int devicesFound)? onScanProgress;
  
  /// Get current WiFi information
  Future<WifiInfo> getWifiInfo() async {
    try {
      final wifiName = await _networkInfo.getWifiName();
      final wifiBSSID = await _networkInfo.getWifiBSSID();
      final wifiIP = await _networkInfo.getWifiIP();
      final wifiGatewayIP = await _networkInfo.getWifiGatewayIP();
      final wifiSubmask = await _networkInfo.getWifiSubmask();
      final wifiBroadcast = await _networkInfo.getWifiBroadcast();
      final wifiIPv6 = await _networkInfo.getWifiIPv6();

      return WifiInfo(
        ssid: wifiName?.replaceAll('"', '') ?? 'Unknown',
        bssid: wifiBSSID ?? '',
        localIP: wifiIP ?? '',
        gatewayIP: wifiGatewayIP ?? '',
        submask: wifiSubmask ?? '',
        broadcast: wifiBroadcast ?? '',
        ipv6: wifiIPv6 ?? '',
        isConnected: wifiIP != null && wifiIP.isNotEmpty,
      );
    } catch (e) {
      return WifiInfo(
        ssid: 'Unknown',
        bssid: '',
        localIP: '',
        gatewayIP: '',
        submask: '',
        broadcast: '',
        ipv6: '',
        isConnected: false,
      );
    }
  }

  /// Scan local network for connected devices using socket connections
  Future<List<ScannedDevice>> scanNetwork({
    String? subnet,
    int timeout = 500,
  }) async {
    final devices = <ScannedDevice>[];
    
    try {
      // Get subnet from WiFi info if not provided
      String subnetToScan = subnet ?? '';
      if (subnetToScan.isEmpty) {
        final wifiInfo = await getWifiInfo();
        if (wifiInfo.localIP.isEmpty) {
          return devices;
        }
        // Extract subnet from IP (e.g., 192.168.1.5 -> 192.168.1)
        final parts = wifiInfo.localIP.split('.');
        if (parts.length == 4) {
          subnetToScan = '${parts[0]}.${parts[1]}.${parts[2]}';
        }
      }

      if (subnetToScan.isEmpty) return devices;

      final futures = <Future<ScannedDevice?>>[];
      
      // Scan 1-254 for each subnet
      for (int i = 1; i <= 254; i++) {
        final host = '$subnetToScan.$i';
        futures.add(_pingHost(host, timeout));
      }
      
      int completed = 0;
      final results = await Future.wait(futures.map((f) async {
        final result = await f;
        completed++;
        onScanProgress?.call(completed / 254, devices.length);
        return result;
      }));
      
      for (final device in results) {
        if (device != null) {
          devices.add(device);
        }
      }
    } catch (e) {
      // Network scan error - silently handle
    }

    return devices;
  }
  
  /// Ping a host to check if it's reachable
  Future<ScannedDevice?> _pingHost(String host, int timeout) async {
    try {
      // Try to connect to common ports (80, 443, 22, etc.)
      final socket = await Socket.connect(
        host,
        80,
        timeout: Duration(milliseconds: timeout),
      ).catchError((_) => Socket.connect(
        host,
        443,
        timeout: Duration(milliseconds: timeout),
      )).catchError((_) => Socket.connect(
        host,
        22,
        timeout: Duration(milliseconds: timeout),
      )).catchError((_) async {
        // If socket connections fail, try DNS lookup as last resort
        try {
          await InternetAddress(host).reverse();
        } catch (_) {
          throw Exception('Host not reachable');
        }
        throw Exception('Host not reachable');
      });
      
      await socket.close();
      
      // Try to get hostname
      String? hostname;
      try {
        final addr = InternetAddress(host);
        final reversed = await addr.reverse();
        hostname = reversed.host;
      } catch (_) {}
      
      return ScannedDevice(
        ipAddress: host,
        isReachable: true,
        hostname: hostname,
        discoveredAt: DateTime.now(),
      );
    } catch (e) {
      return null;
    }
  }

  /// Get local device's network interface information
  Future<List<NetworkInterfaceInfo>> getNetworkInterfaces() async {
    final interfaces = <NetworkInterfaceInfo>[];
    
    try {
      final networkInterfaces = await NetworkInterface.list();
      for (final interface in networkInterfaces) {
        for (final addr in interface.addresses) {
          interfaces.add(NetworkInterfaceInfo(
            name: interface.name,
            address: addr.address,
            isIPv4: addr.type == InternetAddressType.IPv4,
            isIPv6: addr.type == InternetAddressType.IPv6,
          ));
        }
      }
    } catch (e) {
      // Error getting network interfaces - silently handle
    }
    
    return interfaces;
  }

  /// Ping a specific host to check if it's reachable
  Future<PingResult> pingHost(String host, {int timeout = 2000}) async {
    final stopwatch = Stopwatch()..start();
    
    try {
      final socket = await Socket.connect(
        host,
        80,
        timeout: Duration(milliseconds: timeout),
      );
      stopwatch.stop();
      await socket.close();
      
      return PingResult(
        host: host,
        isReachable: true,
        latencyMs: stopwatch.elapsedMilliseconds,
      );
    } catch (e) {
      // Try ICMP-style ping via InternetAddress
      try {
        final addr = InternetAddress(host);
        stopwatch.reset();
        stopwatch.start();
        final result = await addr.reverse();
        stopwatch.stop();
        
        return PingResult(
          host: host,
          isReachable: result.host.isNotEmpty,
          latencyMs: stopwatch.elapsedMilliseconds,
        );
      } catch (e) {
        return PingResult(
          host: host,
          isReachable: false,
          latencyMs: null,
        );
      }
    }
  }
}

/// WiFi connection information
class WifiInfo {
  final String ssid;
  final String bssid;
  final String localIP;
  final String gatewayIP;
  final String submask;
  final String broadcast;
  final String ipv6;
  final bool isConnected;

  WifiInfo({
    required this.ssid,
    required this.bssid,
    required this.localIP,
    required this.gatewayIP,
    required this.submask,
    required this.broadcast,
    required this.ipv6,
    required this.isConnected,
  });
}

/// Discovered device on the network
class ScannedDevice {
  final String ipAddress;
  final bool isReachable;
  final String? hostname;
  final String? macAddress;
  final DateTime discoveredAt;

  ScannedDevice({
    required this.ipAddress,
    required this.isReachable,
    this.hostname,
    this.macAddress,
    required this.discoveredAt,
  });
  
  /// Get a display name for the device
  String get displayName {
    if (hostname != null && hostname!.isNotEmpty) {
      return hostname!;
    }
    return ipAddress;
  }
  
  /// Try to determine device type from hostname
  String get estimatedDeviceType {
    final name = (hostname ?? '').toLowerCase();
    if (name.contains('iphone') || name.contains('android') || name.contains('phone')) {
      return 'phone';
    } else if (name.contains('ipad') || name.contains('tablet')) {
      return 'tablet';
    } else if (name.contains('mac') || name.contains('windows') || name.contains('pc') || name.contains('laptop')) {
      return 'computer';
    } else if (name.contains('tv') || name.contains('roku') || name.contains('chromecast') || name.contains('fire')) {
      return 'tv';
    } else if (name.contains('printer')) {
      return 'printer';
    } else if (name.contains('router') || name.contains('gateway')) {
      return 'router';
    }
    return 'unknown';
  }
}

/// Network interface information
class NetworkInterfaceInfo {
  final String name;
  final String address;
  final bool isIPv4;
  final bool isIPv6;

  NetworkInterfaceInfo({
    required this.name,
    required this.address,
    required this.isIPv4,
    required this.isIPv6,
  });
}

/// Ping result
class PingResult {
  final String host;
  final bool isReachable;
  final int? latencyMs;

  PingResult({
    required this.host,
    required this.isReachable,
    this.latencyMs,
  });
}
