<?php
// Script pour ajouter des logs PHP ultra-détaillés

$file_path = '/var/www/mdgeek.top/inscription.php';
$content = file_get_contents($file_path);

echo "=== AJOUT LOGS PHP ULTRA-DÉTAILLÉS ===\n";

// 1. Ajouter debug au tout début du fichier (après session_start)
$old_session_start = "session_start();";

$new_session_start = "session_start();

// === DEBUG PHP ULTRA-DÉTAILLÉ ===
error_log('=== INSCRIPTION PHP: DÉBUT EXÉCUTION SCRIPT ===');
error_log('INSCRIPTION PHP: Timestamp: ' . date('Y-m-d H:i:s'));
error_log('INSCRIPTION PHP: Request URI: ' . (\$_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log('INSCRIPTION PHP: Request Method: ' . (\$_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log('INSCRIPTION PHP: HTTP Host: ' . (\$_SERVER['HTTP_HOST'] ?? 'N/A'));
error_log('INSCRIPTION PHP: User Agent: ' . (\$_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));
error_log('INSCRIPTION PHP: Remote Addr: ' . (\$_SERVER['REMOTE_ADDR'] ?? 'N/A'));
error_log('INSCRIPTION PHP: Content Type: ' . (\$_SERVER['CONTENT_TYPE'] ?? 'N/A'));
error_log('INSCRIPTION PHP: Content Length: ' . (\$_SERVER['CONTENT_LENGTH'] ?? 'N/A'));
error_log('INSCRIPTION PHP: All Headers: ' . json_encode(getallheaders()));";

if (strpos($content, $old_session_start) !== false) {
    $content = str_replace($old_session_start, $new_session_start, $content);
    echo "✅ Debug ajouté au début du script\n";
} else {
    echo "⚠️ session_start non trouvé\n";
}

// 2. Ajouter debug avant la vérification POST
$old_post_check = "// Traitement du formulaire (AJAX ou normal)
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {";

$new_post_check = "// Traitement du formulaire (AJAX ou normal)
error_log('INSCRIPTION PHP: Vérification méthode de requête...');
error_log('INSCRIPTION PHP: REQUEST_METHOD = ' . (\$_SERVER['REQUEST_METHOD'] ?? 'UNDEFINED'));
error_log('INSCRIPTION PHP: POST data size: ' . strlen(file_get_contents('php://input')));
error_log('INSCRIPTION PHP: POST array: ' . json_encode(\$_POST));
error_log('INSCRIPTION PHP: GET array: ' . json_encode(\$_GET));

if (\$_SERVER['REQUEST_METHOD'] === 'POST') {";

if (strpos($content, $old_post_check) !== false) {
    $content = str_replace($old_post_check, $new_post_check, $content);
    echo "✅ Debug ajouté avant vérification POST\n";
} else {
    echo "⚠️ Vérification POST non trouvée\n";
}

// 3. Ajouter debug pour chaque étape de validation
$old_validation_start = "    error_log(\"INSCRIPTION DEBUG: Début validation des données\");
    \$errors = [];";

$new_validation_start = "    error_log(\"INSCRIPTION DEBUG: Début validation des données\");
    error_log('INSCRIPTION PHP: Validation - subdomain: ' . \$subdomain);
    error_log('INSCRIPTION PHP: Validation - email: ' . \$email);
    error_log('INSCRIPTION PHP: Validation - nom: ' . \$nom);
    error_log('INSCRIPTION PHP: Validation - prenom: ' . \$prenom);
    \$errors = [];";

if (strpos($content, $old_validation_start) !== false) {
    $content = str_replace($old_validation_start, $new_validation_start, $content);
    echo "✅ Debug ajouté à la validation détaillée\n";
} else {
    echo "⚠️ Début validation non trouvé\n";
}

// 4. Ajouter debug avant chaque étape de création de boutique
$old_shop_creation_start = "        error_log(\"INSCRIPTION DEBUG: Validation réussie, début création boutique\");
        error_log(\"INSCRIPTION DEBUG: Subdomain final: \$subdomain\");
        
        // Créer la boutique
        require_once(__DIR__ . '/classes/ShopManager.php');";

$new_shop_creation_start = "        error_log(\"INSCRIPTION DEBUG: Validation réussie, début création boutique\");
        error_log(\"INSCRIPTION DEBUG: Subdomain final: \$subdomain\");
        error_log('INSCRIPTION PHP: Tentative inclusion ShopManager...');
        
        // Créer la boutique
        require_once(__DIR__ . '/classes/ShopManager.php');
        error_log('INSCRIPTION PHP: ShopManager inclus avec succès');";

if (strpos($content, $old_shop_creation_start) !== false) {
    $content = str_replace($old_shop_creation_start, $new_shop_creation_start, $content);
    echo "✅ Debug ajouté avant création boutique\n";
} else {
    echo "⚠️ Début création boutique non trouvé\n";
}

// 5. Ajouter debug pour l'appel ShopManager
$old_shop_manager_call = "        \$shopManager = new ShopManager(\$pdo);
        \$shop_data = \$shopManager->createShop([";

$new_shop_manager_call = "        error_log('INSCRIPTION PHP: Instanciation ShopManager...');
        \$shopManager = new ShopManager(\$pdo);
        error_log('INSCRIPTION PHP: ShopManager créé, appel createShop...');
        
        \$shop_creation_data = [
            'nom' => \$nom,
            'prenom' => \$prenom,
            'nom_commercial' => \$nom_commercial,
            'subdomain' => \$subdomain,
            'email' => \$email,
            'telephone' => \$telephone,
            'adresse' => \$adresse,
            'code_postal' => \$code_postal,
            'ville' => \$ville
        ];
        error_log('INSCRIPTION PHP: Données pour createShop: ' . json_encode(\$shop_creation_data));
        
        \$shop_data = \$shopManager->createShop(\$shop_creation_data);";

// Chercher le pattern existant
if (preg_match('/\$shopManager = new ShopManager\(\$pdo\);\s*\$shop_data = \$shopManager->createShop\(\[/', $content)) {
    $content = preg_replace(
        '/(\$shopManager = new ShopManager\(\$pdo\);\s*)\$shop_data = \$shopManager->createShop\(\[([^]]+)\]/',
        $new_shop_manager_call,
        $content
    );
    echo "✅ Debug ajouté à l'appel ShopManager\n";
} else {
    echo "⚠️ Appel ShopManager non trouvé\n";
}

// 6. Ajouter debug après création réussie
$old_shop_success = "        \$shop_id = \$shop_data['shop_id'];
        \$admin_username = \$shop_data['admin_username'];";

$new_shop_success = "        error_log('INSCRIPTION PHP: createShop terminé avec succès');
        error_log('INSCRIPTION PHP: Résultat createShop: ' . json_encode(\$shop_data));
        
        \$shop_id = \$shop_data['shop_id'];
        \$admin_username = \$shop_data['admin_username'];
        
        error_log('INSCRIPTION PHP: Shop ID: ' . \$shop_id);
        error_log('INSCRIPTION PHP: Admin username: ' . \$admin_username);";

if (strpos($content, $old_shop_success) !== false) {
    $content = str_replace($old_shop_success, $new_shop_success, $content);
    echo "✅ Debug ajouté après succès création\n";
} else {
    echo "⚠️ Succès création non trouvé\n";
}

// 7. Améliorer le debug SSL existant
$old_ssl_debug = "        error_log(\"=== INSCRIPTION DEBUG: APPEL updateSSLCertificate ===\");
        error_log(\"INSCRIPTION DEBUG: Sous-domaine à traiter: \$subdomain\");
        error_log(\"INSCRIPTION DEBUG: Shop ID: \$shop_id\");
        error_log(\"INSCRIPTION DEBUG: Heure: \" . date('Y-m-d H:i:s'));
        
        \$ssl_updated = updateSSLCertificate(\$subdomain);";

$new_ssl_debug = "        error_log(\"=== INSCRIPTION DEBUG: APPEL updateSSLCertificate ===\");
        error_log(\"INSCRIPTION DEBUG: Sous-domaine à traiter: \$subdomain\");
        error_log(\"INSCRIPTION DEBUG: Shop ID: \$shop_id\");
        error_log(\"INSCRIPTION DEBUG: Heure: \" . date('Y-m-d H:i:s'));
        error_log('INSCRIPTION PHP: Début processus SSL automatique...');
        
        \$ssl_start_time = microtime(true);
        \$ssl_updated = updateSSLCertificate(\$subdomain);
        \$ssl_duration = microtime(true) - \$ssl_start_time;
        
        error_log('INSCRIPTION PHP: Processus SSL terminé en ' . round(\$ssl_duration, 2) . ' secondes');";

if (strpos($content, $old_ssl_debug) !== false) {
    $content = str_replace($old_ssl_debug, $new_ssl_debug, $content);
    echo "✅ Debug SSL amélioré\n";
} else {
    echo "⚠️ Debug SSL existant non trouvé\n";
}

// 8. Ajouter debug final avant réponse JSON
$old_final_response = "    error_log(\"INSCRIPTION DEBUG: Envoi réponse finale: \" . json_encode(\$response));
    header('Content-Type: application/json');
    echo json_encode(\$response);
    exit;";

$new_final_response = "    error_log(\"INSCRIPTION DEBUG: Envoi réponse finale: \" . json_encode(\$response));
    error_log('INSCRIPTION PHP: Taille réponse JSON: ' . strlen(json_encode(\$response)) . ' caractères');
    error_log('INSCRIPTION PHP: Headers envoyés: ' . json_encode(headers_list()));
    
    header('Content-Type: application/json');
    echo json_encode(\$response);
    
    error_log('INSCRIPTION PHP: Réponse JSON envoyée avec succès');
    error_log('=== INSCRIPTION PHP: FIN EXÉCUTION SCRIPT ===');
    exit;";

if (strpos($content, $old_final_response) !== false) {
    $content = str_replace($old_final_response, $new_final_response, $content);
    echo "✅ Debug ajouté à la réponse finale\n";
} else {
    echo "⚠️ Réponse finale non trouvée\n";
}

// Sauvegarder le fichier
if (file_put_contents($file_path, $content)) {
    echo "✅ Logs PHP ultra-détaillés ajoutés\n";
    
    // Vérifier la syntaxe
    $syntax_check = shell_exec("php -l $file_path 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "✅ Syntaxe PHP valide\n";
    } else {
        echo "❌ Erreur de syntaxe: $syntax_check\n";
        exit(1);
    }
} else {
    echo "❌ Impossible de sauvegarder\n";
    exit(1);
}

echo "\n=== LOGS PHP ULTRA-DÉTAILLÉS AJOUTÉS ===\n";
echo "Le script tracera maintenant CHAQUE étape du processus côté serveur.\n";
?>
