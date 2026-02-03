/// Router Information Model
class RouterInfo {
  final int? onuId;
  final String? deviceSn;
  final String? tr069Profile;
  final String? authorizedDate;
  final int totalDevices;
  final int activeDevices;
  // Extended router info from MBT
  final String? routerType;
  final String? pon;
  final String? fiberLength;
  final String? opticalPower;
  final String? odbRxPower;
  final String? lan;
  final String? bandwidth;
  final String? serviceType;

  RouterInfo({
    this.onuId,
    this.deviceSn,
    this.tr069Profile,
    this.authorizedDate,
    this.totalDevices = 0,
    this.activeDevices = 0,
    this.routerType,
    this.pon,
    this.fiberLength,
    this.opticalPower,
    this.odbRxPower,
    this.lan,
    this.bandwidth,
    this.serviceType,
  });

  factory RouterInfo.fromJson(Map<String, dynamic> json) {
    return RouterInfo(
      onuId: json['onu_id'],
      deviceSn: json['device_sn'] ?? json['loid'],
      tr069Profile: json['tr069_profile'] ?? json['odb_box'],
      authorizedDate: json['authorized_date'] ?? json['installation_date'],
      totalDevices: json['total_devices'] ?? 0,
      activeDevices: json['active_devices'] ?? 0,
      routerType: json['router_type'],
      pon: json['pon'],
      fiberLength: json['fiber_length'],
      opticalPower: json['optical_power'],
      odbRxPower: json['odb_rx_power'],
      lan: json['lan'],
      bandwidth: json['bandwidth'],
      serviceType: json['service_type'],
    );
  }
}

/// Bind User Network Info - detailed info from API
class BindUserNetworkInfo {
  final int id;
  final String? userName;
  final String? realName;
  final String? routerType;
  final String? odbBox;
  final String? pon;
  final String? loid;
  final String? fiberLength;
  final String? opticalPower;
  final String? odbRxPower;
  final String? odbGps;
  final String? lan;
  final String? speedTest;
  final String? bandwidth;
  final String? serviceType;
  final String? installationDate;
  final String? lastOnline;
  final String? lastOffline;

  BindUserNetworkInfo({
    required this.id,
    this.userName,
    this.realName,
    this.routerType,
    this.odbBox,
    this.pon,
    this.loid,
    this.fiberLength,
    this.opticalPower,
    this.odbRxPower,
    this.odbGps,
    this.lan,
    this.speedTest,
    this.bandwidth,
    this.serviceType,
    this.installationDate,
    this.lastOnline,
    this.lastOffline,
  });

  factory BindUserNetworkInfo.fromJson(Map<String, dynamic> json) {
    return BindUserNetworkInfo(
      id: json['id'] ?? 0,
      userName: json['user_name'],
      realName: json['real_name'],
      routerType: json['router_type'],
      odbBox: json['odb_box'],
      pon: json['pon'],
      loid: json['loid'],
      fiberLength: json['fiber_length'],
      opticalPower: json['optical_power'],
      odbRxPower: json['odb_rx_power'],
      odbGps: json['odb_gps'],
      lan: json['lan'],
      speedTest: json['speed_test'],
      bandwidth: json['bandwidth'],
      serviceType: json['service_type'],
      installationDate: json['installation_date'],
      lastOnline: json['last_online'],
      lastOffline: json['last_offline'],
    );
  }
}

/// Today's Traffic Model
class TrafficInfo {
  final double usageGb;
  final double downloadGb;
  final double uploadGb;
  final String? lastUpdated;

  TrafficInfo({
    this.usageGb = 0.0,
    this.downloadGb = 0.0,
    this.uploadGb = 0.0,
    this.lastUpdated,
  });

  factory TrafficInfo.fromJson(Map<String, dynamic> json) {
    return TrafficInfo(
      usageGb: (json['usage_gb'] ?? 0).toDouble(),
      downloadGb: (json['download_gb'] ?? 0).toDouble(),
      uploadGb: (json['upload_gb'] ?? 0).toDouble(),
      lastUpdated: json['last_updated'],
    );
  }

  String get formattedUsage {
    if (usageGb >= 1) {
      return '${usageGb.toStringAsFixed(2)} GB';
    } else {
      return '${(usageGb * 1024).toStringAsFixed(0)} MB';
    }
  }
}

/// Active Device Model
class ActiveDevice {
  final String name;
  final String macAddress;
  final String ipAddress;
  final String? port;
  final bool isActive;
  final String? deviceType;
  final String? connectionTime;

  ActiveDevice({
    required this.name,
    required this.macAddress,
    required this.ipAddress,
    this.port,
    this.isActive = true,
    this.deviceType,
    this.connectionTime,
  });

  factory ActiveDevice.fromJson(Map<String, dynamic> json) {
    return ActiveDevice(
      name: json['name'] ?? json['hostname'] ?? 'Unknown Device',
      macAddress: json['mac_address'] ?? json['mac'] ?? '',
      ipAddress: json['ip_address'] ?? json['ip'] ?? '',
      port: json['port'],
      isActive: json['is_active'] ?? json['active'] ?? true,
      deviceType: json['device_type'] ?? json['type'],
      connectionTime: json['connection_time'],
    );
  }
}

/// Complete Network Devices Data
class NetworkDevicesData {
  final RouterInfo routerInfo;
  final TrafficInfo trafficInfo;
  final List<ActiveDevice> activeDevices;

  NetworkDevicesData({
    required this.routerInfo,
    required this.trafficInfo,
    required this.activeDevices,
  });

  factory NetworkDevicesData.fromJson(Map<String, dynamic> json) {
    return NetworkDevicesData(
      routerInfo: RouterInfo.fromJson(json['router_info'] ?? {}),
      trafficInfo: TrafficInfo.fromJson(json['traffic_info'] ?? {}),
      activeDevices: (json['active_devices'] as List? ?? [])
          .map((e) => ActiveDevice.fromJson(e))
          .toList(),
    );
  }

  // For demo/mock data
  factory NetworkDevicesData.mock() {
    return NetworkDevicesData(
      routerInfo: RouterInfo(
        onuId: 10418,
        deviceSn: 'HWTC4FA45607',
        tr069Profile: 'SmartOLT',
        authorizedDate: '2023-06-07 21:09:34',
        totalDevices: 7,
        activeDevices: 4,
      ),
      trafficInfo: TrafficInfo(
        usageGb: 1.13,
        downloadGb: 0.98,
        uploadGb: 0.15,
        lastUpdated: DateTime.now().toIso8601String(),
      ),
      activeDevices: [
        ActiveDevice(
          name: 'Redmi-K50',
          macAddress: '9a:2c:93:e3:ef:c2',
          ipAddress: '192.168.100.4',
          port: 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1',
          isActive: true,
          deviceType: 'phone',
        ),
        ActiveDevice(
          name: 'raspberrypi',
          macAddress: 'd8:3a:dd:f1:b3:a7',
          ipAddress: '192.168.100.162',
          port: 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1',
          isActive: true,
          deviceType: 'computer',
        ),
        ActiveDevice(
          name: 'Yin-s-A70',
          macAddress: 'a4:5e:60:b2:c8:f1',
          ipAddress: '192.168.100.78',
          port: 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1',
          isActive: true,
          deviceType: 'phone',
        ),
        ActiveDevice(
          name: 'Desktop-PC',
          macAddress: '00:1a:2b:3c:4d:5e',
          ipAddress: '192.168.100.10',
          port: 'InternetGatewayDevice.LANDevice.1.LANEthernetInterfaceConfig.1',
          isActive: true,
          deviceType: 'computer',
        ),
      ],
    );
  }
}
