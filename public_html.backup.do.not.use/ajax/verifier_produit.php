<?php
// Désactiver l'affichage des erreurs dans la sortie
error_reporting(0);
ini_set('display_errors', 0);

// Inclure les configurations obligatoires GeekBoard
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier le paramètre code
if (!isset($_GET['code']) || empty($_GET['code'])) {
    echo json_encode(['error' => 'Code-barres manquant']);
    exit;
}

try {
    // Log pour diagnostic
    error_log("verifier_produit.php - HOST: " . ($_SERVER['HTTP_HOST'] ?? 'non défini'));
    error_log("verifier_produit.php - Code recherché: " . ($_GET['code'] ?? 'non défini'));
    
    // Détecter le sous-domaine et se connecter directement à la bonne base
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = '';
    
    if (strpos($host, '.mdgeek.top') !== false) {
        $subdomain = str_replace('.mdgeek.top', '', $host);
    } elseif (strpos($host, '.servo.tools') !== false) {
        $subdomain = str_replace('.servo.tools', '', $host);
    } else {
        throw new Exception('Sous-domaine non valide: ' . $host);
    }
    
    $dbname = 'geekboard_' . $subdomain;
    
    error_log("verifier_produit.php - Host: " . $host);
    error_log("verifier_produit.php - Sous-domaine détecté: " . $subdomain);
    error_log("verifier_produit.php - Base de données: " . $dbname);
    
    // Connexion directe à la base du magasin
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", 'root', 'Mamanmaman01#');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    error_log("verifier_produit.php - Connexion réussie à: " . $dbname);
    
    // Nettoyer le code
    $code = trim($_GET['code']);
    error_log("verifier_produit.php - Code nettoyé: " . $code);
    
    // Préparer et exécuter la requête
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE reference = ?");
    $stmt->execute([$code]);
    $produit = $stmt->fetch();
    
    error_log("verifier_produit.php - Produit trouvé: " . ($produit ? 'OUI (ID: ' . $produit['id'] . ')' : 'NON'));

    // Préparer la réponse
    if ($produit) {
        $response = [
            'existe' => true,
            'id' => $produit['id'],
            'nom' => $produit['nom'],
            'reference' => $produit['reference'],
            'quantite' => $produit['quantite']
        ];
        error_log("verifier_produit.php - Réponse: " . json_encode($response));
        echo json_encode($response);
    } else {
        $response = ['existe' => false];
        error_log("verifier_produit.php - Réponse: " . json_encode($response));
        echo json_encode($response);
    }

} catch (Exception $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur dans verifier_produit.php: " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    echo json_encode([
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?> 