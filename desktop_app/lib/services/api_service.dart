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
    var baseUrl = ApiConfig.baseUrl;
    if (!baseUrl.endsWith('/') && !endpoint.startsWith('/')) {
      baseUrl = '$baseUrl/';
    }
    var url = '$baseUrl$endpoint';
    
    // Injecter le token en fallback (pour éviter le header stripping)
    final Map<String, String> finalParams = Map.from(queryParams ?? {});
    if (token != null) {
      finalParams['token'] = token!;
    }
    
    if (finalParams.isNotEmpty) {
      final query = finalParams.entries
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

  /// Effectuer une requête PUT
  Future<Map<String, dynamic>> put(String endpoint, Map<String, dynamic> body) async {
    try {
      final url = _buildUrl(endpoint);
      final response = await http.put(
        Uri.parse(url),
        headers: _headers,
        body: jsonEncode(body),
      ).timeout(Duration(seconds: ApiConfig.timeout));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur de connexion: $e');
    }
  }

  /// Effectuer une requête DELETE
  Future<Map<String, dynamic>> delete(String endpoint) async {
    try {
      final url = _buildUrl(endpoint);
      final response = await http.delete(
        Uri.parse(url),
        headers: _headers,
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

  /// Créer un rachat (avec support photos multiples)
  Future<Map<String, dynamic>> createRachat(Map<String, dynamic> data, Map<String, dynamic> files) async {
    try {
      final url = _buildUrl(ApiConfig.rachatCreateEndpoint);
      final request = http.MultipartRequest('POST', Uri.parse(url));
      
      request.headers.addAll(_headers);
      request.headers.remove('Content-Type');

      // Add fields
      data.forEach((key, value) {
        if (value != null) {
           request.fields[key] = value.toString();
        }
      });

      // Add files
      for (var entry in files.entries) {
        final key = entry.key;
        final value = entry.value; // Path or Bytes
        
        if (value is String) { // Path
           request.files.add(await http.MultipartFile.fromPath(key, value));
        } else if (value is List<int>) { // Bytes
           request.files.add(http.MultipartFile.fromBytes(key, value, filename: '$key.jpg'));
        }
      }

      final streamedResponse = await request.send().timeout(Duration(seconds: ApiConfig.timeout));
      final response = await http.Response.fromStream(streamedResponse);
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur upload rachat: $e');
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

  Future<List<dynamic>> getSuppliers() async {
    final response = await get(ApiConfig.suppliersListEndpoint);
    return response['fournisseurs'] ?? [];
  }

  Future<Map<String, dynamic>> getSupplierAuth() async {
    return get(ApiConfig.suppliersAuthEndpoint);
  }

  Future<Map<String, dynamic>> updateSupplierAuth(int supplierId, bool status) async {
    return post(ApiConfig.suppliersAuthEndpoint, {
      'supplier_id': supplierId,
      'status': status
    });
  }
  
  /// Envoyer un template SMS
  Future<Map<String, dynamic>> sendSmsTemplate(int? reparationId, int templateId, {int? commandId}) async {
    final body = {
      'template_id': templateId,
    };
    if (reparationId != null) body['reparation_id'] = reparationId;
    if (commandId != null) body['command_id'] = commandId;
    
    return post(ApiConfig.smsSendTemplateEndpoint, body);
  }

  /// Perform mission action (join, submit)
  Future<Map<String, dynamic>> performMissionAction(String action, Map<String, dynamic> data) async {
    return post(ApiConfig.missionsActionEndpoint, {
      'action': action,
      ...data,
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
  // --- SCREENS ---
  
  Future<List<Map<String, dynamic>>> getScreens() async {
    final response = await get(ApiConfig.screensListEndpoint);
    return List<Map<String, dynamic>>.from(response['screens']);
  }

  Future<Map<String, dynamic>> getScreenDetails(int id) async {
    final response = await get('${ApiConfig.screensGetEndpoint}?id=$id');
    return Map<String, dynamic>.from(response['screen'] ?? response);
  }

  Future<Map<String, dynamic>> createScreen(String name) async {
    return post(ApiConfig.screensCreateEndpoint, {'name': name});
  }

  Future<void> updateScreen(int id, {String? deviceType, String? orientation, bool? slideshowEnabled, int? slideDuration, int? selectedSlideId}) async {
    final Map<String, dynamic> data = {'id': id};
    if (deviceType != null) data['device_type'] = deviceType;
    if (orientation != null) data['orientation'] = orientation;
    if (slideshowEnabled != null) data['slideshow_enabled'] = slideshowEnabled ? 1 : 0;
    if (slideDuration != null) data['slide_duration'] = slideDuration;
    if (selectedSlideId != null) data['selected_slide_id'] = selectedSlideId;
    await post(ApiConfig.screensUpdateEndpoint, data);
  }

  Future<void> deleteScreen(int id) async {
    await get('${ApiConfig.screensDeleteEndpoint}?id=$id');
  }

  Future<void> addSlide(int screenId, String type, {String? text, String? color, String? filePath, int duration = 10, int order = 0}) async {
    final url = _buildUrl(ApiConfig.screensAddSlideEndpoint);
    final request = http.MultipartRequest('POST', Uri.parse(url));
    request.headers.addAll(_headers);
    request.headers.remove('Content-Type');

    request.fields['screen_id'] = screenId.toString();
    request.fields['type'] = type;
    request.fields['duration'] = duration.toString();
    request.fields['display_order'] = order.toString();

    if (type == 'TEXT') {
      request.fields['text_content'] = text ?? '';
      request.fields['text_color'] = color ?? '#FFFFFF';
      request.fields['bg_color'] = '#000000';
    } else if (type == 'IMAGE' && filePath != null) {
      request.files.add(await http.MultipartFile.fromPath('file', filePath));
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    _handleResponse(response);
  }

  Future<void> deleteSlide(int id) async {
    await get('${ApiConfig.screensDeleteSlideEndpoint}?id=$id');
  }

  Future<void> setScreenState(String token, String status, {Map<String, dynamic>? data}) async {
    await post(ApiConfig.screensStateEndpoint, {
      'token': token,
      'status': status,
      'data': data
    });
  }

  Future<List<Map<String, dynamic>>> getShopUsers() async {
    final response = await get(ApiConfig.screensGetUsersEndpoint);
    return List<Map<String, dynamic>>.from(response['users']);
  }

  Future<void> assignUsersToScreen(int screenId, List<int> userIds) async {
    await post(ApiConfig.screensAssignUsersEndpoint, {
      'screen_id': screenId,
      'user_ids': userIds
    });
  }
  Future<Map<String, dynamic>> getPartnerTransactions(int partnerId) async {
    return get(ApiConfig.partnersTransactionsEndpoint, {'partner_id': partnerId.toString()});
  }

  Future<Map<String, dynamic>> validatePartnerTransaction(int pendingId, String action, String reason) async {
    return post(ApiConfig.partnersValidateTransactionEndpoint, {
      'pending_id': pendingId,
      'action': action,
      'reason': reason,
    });
  }

  /// GET request to an external full URL (for legacy endpoints)
  Future<Map<String, dynamic>> getExternal(String fullUrl) async {
    try {
      final response = await http.get(
        Uri.parse(fullUrl),
        headers: _headers,
      ).timeout(Duration(seconds: ApiConfig.timeout));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur de connexion externe: $e');
    }
  }

  /// POST request to an external full URL (for legacy endpoints)
  Future<Map<String, dynamic>> postExternal(String fullUrl, Map<String, dynamic> body) async {
    try {
      final response = await http.post(
        Uri.parse(fullUrl),
        headers: _headers,
        body: jsonEncode(body),
      ).timeout(Duration(seconds: ApiConfig.timeout));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Erreur de connexion externe: $e');
    }
  }
}

/// Exception personnalisée pour l'API
class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  
  @override
  String toString() => message;
}
