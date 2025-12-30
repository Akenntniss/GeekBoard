/// Service d'authentification - Gère le login et le stockage du token
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import '../models/shop.dart';
import 'api_service.dart';

class AuthService extends ChangeNotifier {
  static const String _tokenKey = 'auth_token';
  static const String _subdomainKey = 'last_subdomain';
  
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();
  
  User? _currentUser;
  Shop? _currentShop;
  String? _token;
  bool _isLoading = false;
  String? _error;
  
  User? get currentUser => _currentUser;
  Shop? get currentShop => _currentShop;
  String? get token => _token;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _token != null && _currentUser != null;
  String? get error => _error;
  
  /// Initialiser le service (charger le token sauvegardé)
  Future<bool> init() async {
    try {
      _token = await _secureStorage.read(key: _tokenKey);
      
      if (_token != null) {
        // Vérifier si le token est encore valide
        final apiService = ApiService(token: _token);
        final response = await apiService.verifyToken();
        
        if (response['success'] == true) {
          _currentUser = User.fromJson(response['user']);
          _currentShop = Shop.fromJson(response['shop']);
          notifyListeners();
          return true;
        } else {
          // Token invalide, le supprimer
          await logout();
        }
      }
    } catch (e) {
      // Erreur lors de la vérification, supprimer le token
      await logout();
    }
    
    return false;
  }
  
  /// Récupérer le dernier sous-domaine utilisé
  Future<String?> getLastSubdomain() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_subdomainKey);
  }
  
  /// Login
  Future<bool> login(String subdomain, String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      final apiService = ApiService();
      final response = await apiService.login(subdomain, email, password);
      
      if (response['success'] == true) {
        _token = response['token'];
        _currentUser = User.fromJson(response['user']);
        _currentShop = Shop.fromJson(response['shop']);
        
        // Sauvegarder le token et le sous-domaine
        await _secureStorage.write(key: _tokenKey, value: _token);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_subdomainKey, subdomain);
        
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['error'] ?? 'Erreur de connexion';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  /// Logout
  Future<void> logout() async {
    _token = null;
    _currentUser = null;
    _currentShop = null;
    _error = null;
    
    await _secureStorage.delete(key: _tokenKey);
    
    notifyListeners();
  }
  
  /// Obtenir un ApiService configuré
  ApiService getApiService() {
    return ApiService(token: _token);
  }
}
