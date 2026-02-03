/// API Configuration
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = 'https://isp.mlbbshop.app/api/v1';
  
  // Auth endpoints
  static const String login = '/login';
  static const String register = '/register';
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';
  static const String logout = '/logout';
  static const String refreshToken = '/refresh-token';
  static const String changePassword = '/change-password';
  
  // Profile endpoints
  static const String profile = '/profile';
  
  // Package endpoints
  static const String packages = '/packages';
  static const String myPackages = '/my-packages';
  
  // Payment endpoints
  static const String payments = '/payments';
  static const String paymentMethods = '/payments/methods';
  static const String initiatePayment = '/payments/initiate';
  
  // Notification endpoints
  static const String notifications = '/notifications';
  static const String unreadCount = '/notifications/unread-count';
  
  // Fault report endpoints
  static const String faultReports = '/fault-reports';
  
  // System endpoints
  static const String banners = '/banners';
  static const String maintenanceStatus = '/maintenance-status';
  static const String appVersion = '/app-version';
  static const String settings = '/settings';
  
  // Bind user endpoints
  static const String bindUsers = '/bind-users';
  
  // Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
}
