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
    if (response.body.isEmpty) {
      print('DEBUG: Empty response body for ${response.request?.url}');
      throw const FormatException('Empty response body');
    }

    try {
      print('DEBUG: Raw response body for ${response.request?.url}:');
      print(response.body); // Imprime le corps brut
      
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        return data;
      } else if (response.statusCode == 401) {
        throw ApiException('Session expirée, veuillez vous reconnecter');
      } else {
        throw ApiException(data['error'] ?? 'Erreur serveur');
      }
    } catch (e) {
      print('DEBUG: JSON Decode Error: $e');
      print('DEBUG: Response causing error: ${response.body}');
      rethrow;
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
    return response;
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

  /// Créer une réparation (avec support photo)
  Future<Map<String, dynamic>> createReparation(Map<String, dynamic> data, {String? imagePath, List<int>? imageBytes}) async {
    if (imagePath == null && imageBytes == null) {
      return post(ApiConfig.reparationsCreateEndpoint, data);
    }

    try {
      final url = _buildUrl(ApiConfig.reparationsCreateEndpoint);
      final request = http.MultipartRequest('POST', Uri.parse(url));
      
      request.headers.addAll(_headers);
      request.headers.remove('Content-Type'); // Let MultipartRequest set it

      // Add fields
      data.forEach((key, value) {
        if (value != null) {
          if (value is Map || value is List) {
            request.fields[key] = jsonEncode(value);
          } else {
             request.fields[key] = value.toString();
          }
        }
      });

      // Add file
      if (imagePath != null) {
        request.files.add(await http.MultipartFile.fromPath('photo', imagePath));
      } else if (imageBytes != null) {
         request.files.add(http.MultipartFile.fromBytes('photo', imageBytes, filename: 'photo.jpg'));
      }

      final streamedResponse = await request.send().timeout(Duration(seconds: ApiConfig.timeout));
      final response = await http.Response.fromStream(streamedResponse);
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur upload: $e');
    }
  }

  /// Mettre à jour le statut d'une réparation
  Future<Map<String, dynamic>> updateReparationStatus(int id, String newStatus) async {
    return post(ApiConfig.reparationsUpdateEndpoint, {
      'id': id,
      'statut': newStatus
    });
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

  /// Créer une tâche
  Future<Map<String, dynamic>> createTask(Map<String, dynamic> data) async {
    return post(ApiConfig.tasksCreateEndpoint, data);
  }

  /// Créer une commande
  Future<Map<String, dynamic>> createCommand(Map<String, dynamic> data) async {
    return post(ApiConfig.commandesCreateEndpoint, data);
  }

  /// Créer un client
  Future<Map<String, dynamic>> createClient(Map<String, dynamic> data) async {
    return post(ApiConfig.clientsCreateEndpoint, data);
  }

  /// Récupérer la liste des employés
  Future<List<dynamic>> getEmployees() async {
    final response = await get(ApiConfig.employeesListEndpoint);
    return response['employees'] ?? response['employes'] ?? [];
  }

  /// Récupérer la liste des fournisseurs
  Future<List<dynamic>> getSuppliers() async {
    final response = await get(ApiConfig.suppliersListEndpoint);
    return response['fournisseurs'] ?? [];
  }
  
  /// Envoyer un template SMS
  Future<Map<String, dynamic>> sendSmsTemplate(int reparationId, int templateId) async {
    return post(ApiConfig.smsSendTemplateEndpoint, {
      'reparation_id': reparationId,
      'template_id': templateId,
    });
  }

  /// Send Batch Devis (or preview)
  Future<Map<String, dynamic>> batchSendDevis({
     String action = 'send', 
     List<int>? includeIds,
     int? shopId,
  }) async {
    final body = {
      'action': action,
      if (includeIds != null) 'include_ids': includeIds,
      if (shopId != null) 'shop_id': shopId,
    };
    return post(ApiConfig.devisBatchSendEndpoint, body);
  }
}

/// Exception personnalisée pour l'API
class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  
  @override
  String toString() => message;
}
