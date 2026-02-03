/// User model
class User {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String? photo;
  final int userStatus;
  final int? bindUserId;
  final DateTime? createdAt;

  User({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    this.photo,
    required this.userStatus,
    this.bindUserId,
    this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      phone: json['phone'] ?? '',
      email: json['email'],
      photo: json['photo'],
      userStatus: json['user_status'] ?? 0,
      bindUserId: json['bind_user_id'],
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at']) 
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'email': email,
      'photo': photo,
      'user_status': userStatus,
      'bind_user_id': bindUserId,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  bool get isActive => userStatus == 0;
}

/// Auth response containing user and token
class AuthResponse {
  final User user;
  final String token;
  final String tokenType;
  final DateTime expiresAt;

  AuthResponse({
    required this.user,
    required this.token,
    required this.tokenType,
    required this.expiresAt,
  });

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      user: User.fromJson(json['user']),
      token: json['token'] ?? '',
      tokenType: json['token_type'] ?? 'Bearer',
      expiresAt: DateTime.tryParse(json['expires_at'] ?? '') ?? DateTime.now().add(const Duration(days: 30)),
    );
  }
}
