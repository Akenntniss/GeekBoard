/// Configuration de l'API GeekBoard
/// URL du serveur et paramètres de connexion

class ApiConfig {
  // URL de base de l'API
  // En production, utiliser servo.tools
  // En développement local, utiliser localhost
  static const String baseUrl = 'https://servo.tools/api/v2';
  
  // Alternative pour le développement local
  // static const String baseUrl = 'http://localhost/GeekBoard/api/v2';
  
  // Timeout des requêtes (en secondes)
  static const int timeout = 30;
  
  // Endpoints
  static const String loginEndpoint = '/auth/login.php';
  static const String verifyEndpoint = '/auth/verify.php';
  static const String reparationsListEndpoint = '/reparations/list.php';
  static const String reparationsGetEndpoint = '/reparations/get.php';
  static const String clientsListEndpoint = '/clients/list.php';
  static const String clientsGetEndpoint = '/clients/get.php';
  static const String dashboardStatsEndpoint = '/dashboard/stats.php';
}
