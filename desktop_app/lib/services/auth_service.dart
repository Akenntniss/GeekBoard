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
  
  // Subscription status
  String? _subscriptionStatus;
  String? _subscriptionRedirectUrl;
  String? _subscriptionMessage;
  int? _subscriptionDaysRemaining;
  
  String? get subscriptionStatus => _subscriptionStatus;
  String? get subscriptionRedirectUrl => _subscriptionRedirectUrl;
  String? get subscriptionMessage => _subscriptionMessage;
  int? get subscriptionDaysRemaining => _subscriptionDaysRemaining;
  bool get isSubscriptionBlocked => _subscriptionStatus == 'expired' || _subscriptionStatus == 'inactive';
  
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
          
          // Vérifier le statut d'abonnement
          final subscriptionOk = await checkSubscription();
          if (!subscriptionOk) {
            // Abonnement expiré/inactif - ne pas autoriser l'accès
            notifyListeners();
            return false;
          }
          
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
  
  /// Vérifier le statut d'abonnement
  Future<bool> checkSubscription() async {
    if (_token == null) return false;
    
    try {
      final apiService = ApiService(token: _token);
      final response = await apiService.get('/auth/subscription_check.php');
      
      _subscriptionStatus = response['status'];
      _subscriptionMessage = response['message'];
      _subscriptionRedirectUrl = response['redirect_url'];
      _subscriptionDaysRemaining = response['days_remaining'];
      
      // Return true if subscription is valid
      if (_subscriptionStatus == 'ok' || _subscriptionStatus == 'trial_active') {
        return true;
      }
      
      // Blocked statuses
      return false;
    } catch (e) {
      print('Subscription check error: $e');
      // Fail-open: allow access on error to avoid locking out users
      _subscriptionStatus = 'error';
      return true;
    }
  }
  
  /// Récupérer le dernier sous-domaine utilisé
  Future<String?> getLastSubdomain() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_subdomainKey);
  }
  
  /// Login
  Future<bool> login(String subdomain, String email, String password, {bool rememberMe = false}) async {
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
        
        // Gérer la persistance selon le choix de l'utilisateur
        if (rememberMe) {
          await _secureStorage.write(key: _tokenKey, value: _token);
        } else {
          // Si l'utilisateur ne veut pas rester connecté, on ne stocke pas (ou on nettoie)
          await _secureStorage.delete(key: _tokenKey);
        }

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_subdomainKey, subdomain);
        _cachedSubdomain = subdomain; // Cache for immediate use
        
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
    _cachedSubdomain = null;
    
    await _secureStorage.delete(key: _tokenKey);
    
    notifyListeners();
  }
  
  /// Obtenir un ApiService configuré
  ApiService getApiService() {
    return ApiService(token: _token);
  }

  ApiService get apiService => getApiService();

  // Cached subdomain for quick access
  String? _cachedSubdomain;
  
  /// Get subdomain reliably - first from shop, then from cache
  /// The subdomain is always set during login from user input
  String getSubdomain() {
    // Try from currentShop first
    if (_currentShop != null && _currentShop!.subdomain.isNotEmpty) {
      _cachedSubdomain = _currentShop!.subdomain;
      return _currentShop!.subdomain;
    }
    // Return cached (set during login) - should never be null after login
    if (_cachedSubdomain != null && _cachedSubdomain!.isNotEmpty) {
      return _cachedSubdomain!;
    }
    // Last resort: try to get from currentUser's shopName
    if (_currentUser != null && _currentUser!.shopName.isNotEmpty) {
      _cachedSubdomain = _currentUser!.shopName;
      return _currentUser!.shopName;
    }
    // This should never happen - user must be logged in
    print('WARNING: No subdomain available - user should be logged in');
    return '';
  }

  /// Set subdomain (called during login)
  void setSubdomain(String subdomain) {
    _cachedSubdomain = subdomain;
  }
}
