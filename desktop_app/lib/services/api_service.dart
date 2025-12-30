/// Service API - Gère toutes les communications avec l'API REST
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/reparation.dart';

class ApiService {
  final String? token;
  
  ApiService({this.token});
  
  /// Headers par défaut
  Map<String, String> get _headers {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }
  
  /// Construire l'URL complète
  String _buildUrl(String endpoint, [Map<String, String>? queryParams]) {
    var url = '${ApiConfig.baseUrl}$endpoint';
    
    if (queryParams != null && queryParams.isNotEmpty) {
      final query = queryParams.entries
          .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
          .join('&');
      url += '?$query';
    }
    
    return url;
  }
  
  /// Effectuer une requête GET
  Future<Map<String, dynamic>> get(String endpoint, [Map<String, String>? queryParams]) async {
    try {
      final url = _buildUrl(endpoint, queryParams);
      final response = await http.get(
        Uri.parse(url),
        headers: _headers,
      ).timeout(Duration(seconds: ApiConfig.timeout));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur de connexion: $e');
    }
  }
  
  /// Effectuer une requête POST
  Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> body) async {
    try {
      final url = _buildUrl(endpoint);
      final response = await http.post(
        Uri.parse(url),
        headers: _headers,
        body: jsonEncode(body),
      ).timeout(Duration(seconds: ApiConfig.timeout));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur de connexion: $e');
    }
  }
  
  /// Gérer la réponse HTTP
  Map<String, dynamic> _handleResponse(http.Response response) {
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    } else if (response.statusCode == 401) {
      throw ApiException('Session expirée, veuillez vous reconnecter');
    } else {
      throw ApiException(data['error'] ?? 'Erreur serveur');
    }
  }
  
  /// Login
  Future<Map<String, dynamic>> login(String subdomain, String email, String password) async {
    return post(ApiConfig.loginEndpoint, {
      'subdomain': subdomain,
      'email': email,
      'password': password,
    });
  }
  
  /// Vérifier le token
  Future<Map<String, dynamic>> verifyToken() async {
    return get(ApiConfig.verifyEndpoint);
  }
  
  /// Récupérer les statistiques du dashboard
  Future<Map<String, dynamic>> getDashboardStats() async {
    final response = await get(ApiConfig.dashboardStatsEndpoint);
    return response['data'] ?? {};
  }
  
  /// Récupérer la liste des réparations
  Future<Map<String, dynamic>> getReparations({
    int page = 1,
    int limit = 50,
    String? status,
    String? search,
  }) async {
    final params = {
      'page': page.toString(),
      'limit': limit.toString(),
    };
    
    if (status != null && status.isNotEmpty) {
      params['status'] = status;
    }
    
    if (search != null && search.isNotEmpty) {
      params['search'] = search;
    }
    
    return get(ApiConfig.reparationsListEndpoint, params);
  }
  
  /// Récupérer une réparation par ID
  Future<Reparation> getReparation(int id) async {
    final response = await get(ApiConfig.reparationsGetEndpoint, {'id': id.toString()});
    return Reparation.fromJson(response['data']);
  }
  
  /// Récupérer la liste des clients
  Future<Map<String, dynamic>> getClients({
    int page = 1,
    int limit = 50,
    String? search,
  }) async {
    final params = {
      'page': page.toString(),
      'limit': limit.toString(),
    };
    
    if (search != null && search.isNotEmpty) {
      params['search'] = search;
    }
    
    return get(ApiConfig.clientsListEndpoint, params);
  }
  
  /// Récupérer un client par ID
  Future<Map<String, dynamic>> getClient(int id) async {
    final response = await get(ApiConfig.clientsGetEndpoint, {'id': id.toString()});
    return response;
  }
}

/// Exception personnalisée pour l'API
class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  
  @override
  String toString() => message;
}
