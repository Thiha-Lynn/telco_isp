/// Bind User model - represents a bound broadband account
class BindUser {
  final int id;
  final int? accountId;
  final String? userName;
  final String? realName;
  final String? phone;
  final String? address;
  final String? package;
  final String? monthlyCost;
  final String? expireTime;
  final int status;
  final String? balance;
  final String? bandwidth;
  final String? serviceType;
  final String? subCompany;

  BindUser({
    required this.id,
    this.accountId,
    this.userName,
    this.realName,
    this.phone,
    this.address,
    this.package,
    this.monthlyCost,
    this.expireTime,
    required this.status,
    this.balance,
    this.bandwidth,
    this.serviceType,
    this.subCompany,
  });

  factory BindUser.fromJson(Map<String, dynamic> json) {
    return BindUser(
      id: json['id'] ?? 0,
      accountId: json['account_id'],
      userName: json['user_name'],
      realName: json['real_name'],
      phone: json['phone'],
      address: json['address'],
      package: json['package'],
      monthlyCost: json['monthly_cost'],
      expireTime: json['expire_time'],
      status: json['status'] ?? 0,
      balance: json['balance'],
      bandwidth: json['bandwidth'],
      serviceType: json['service_type'],
      subCompany: json['sub_company'],
    );
  }

  bool get isActive => status == 0;
  
  String get statusText {
    switch (status) {
      case 0:
        return 'Active';
      case 1:
        return 'Suspended';
      case 2:
        return 'Expired';
      default:
        return 'Unknown';
    }
  }
}
