import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../constants/api_config.dart';

/// API Response wrapper
class ApiResponse<T> {
  final bool success;
  final int status;
  final String message;
  final T? data;
  final Map<String, dynamic>? errors;

  ApiResponse({
    required this.success,
    required this.status,
    required this.message,
    this.data,
    this.errors,
  });

  factory ApiResponse.fromJson(Map<String, dynamic> json, T? Function(dynamic)? fromJson) {
    return ApiResponse(
      success: json['success'] ?? false,
      status: json['status'] ?? 0,
      message: json['message'] ?? '',
      data: json['data'] != null && fromJson != null ? fromJson(json['data']) : json['data'],
      errors: json['errors'] != null ? Map<String, dynamic>.from(json['errors']) : null,
    );
  }
}

/// Custom API Exception
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  @override
  String toString() => message;
}

/// API Service for making HTTP requests
class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String? _token;
  
  void setToken(String? token) {
    _token = token;
  }

  String? get token => _token;

  Map<String, String> get _headers {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (_token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    return headers;
  }

  String _buildUrl(String endpoint) {
    return '${ApiConfig.baseUrl}$endpoint';
  }

  Future<ApiResponse<T>> get<T>(
    String endpoint, {
    Map<String, String>? queryParams,
    T? Function(dynamic)? fromJson,
  }) async {
    try {
      var uri = Uri.parse(_buildUrl(endpoint));
      if (queryParams != null) {
        uri = uri.replace(queryParameters: queryParams);
      }

      final response = await http
          .get(uri, headers: _headers)
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse<T>(response, fromJson);
    } on SocketException {
      throw ApiException(message: 'No internet connection');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException(message: 'An unexpected error occurred');
    }
  }

  Future<ApiResponse<T>> post<T>(
    String endpoint, {
    Map<String, dynamic>? body,
    T? Function(dynamic)? fromJson,
  }) async {
    try {
      final response = await http
          .post(
            Uri.parse(_buildUrl(endpoint)),
            headers: _headers,
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse<T>(response, fromJson);
    } on SocketException {
      throw ApiException(message: 'No internet connection');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException(message: 'An unexpected error occurred');
    }
  }

  Future<ApiResponse<T>> put<T>(
    String endpoint, {
    Map<String, dynamic>? body,
    T? Function(dynamic)? fromJson,
  }) async {
    try {
      final response = await http
          .put(
            Uri.parse(_buildUrl(endpoint)),
            headers: _headers,
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse<T>(response, fromJson);
    } on SocketException {
      throw ApiException(message: 'No internet connection');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException(message: 'An unexpected error occurred');
    }
  }

  Future<ApiResponse<T>> delete<T>(
    String endpoint, {
    T? Function(dynamic)? fromJson,
  }) async {
    try {
      final response = await http
          .delete(Uri.parse(_buildUrl(endpoint)), headers: _headers)
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse<T>(response, fromJson);
    } on SocketException {
      throw ApiException(message: 'No internet connection');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException(message: 'An unexpected error occurred');
    }
  }

  ApiResponse<T> _handleResponse<T>(
    http.Response response,
    T? Function(dynamic)? fromJson,
  ) {
    final json = jsonDecode(response.body);
    final apiResponse = ApiResponse<T>.fromJson(json, fromJson);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return apiResponse;
    } else if (response.statusCode == 401) {
      throw ApiException(
        message: apiResponse.message,
        statusCode: 401,
      );
    } else if (response.statusCode == 422) {
      throw ApiException(
        message: apiResponse.message,
        statusCode: 422,
        errors: apiResponse.errors,
      );
    } else {
      throw ApiException(
        message: apiResponse.message,
        statusCode: response.statusCode,
      );
    }
  }
}
