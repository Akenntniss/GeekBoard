<?php
// Script pour ajouter un debug complet à inscription.php

$file_path = '/var/www/mdgeek.top/inscription.php';
$content = file_get_contents($file_path);

echo "=== AJOUT DEBUG COMPLET À INSCRIPTION.PHP ===\n";

// 1. Ajouter debug au début du traitement POST
$old_post_start = "if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    \$nom = trim(\$_POST['nom'] ?? '');
    \$prenom = trim(\$_POST['prenom'] ?? '');
    \$nom_commercial = trim(\$_POST['nom_commercial'] ?? '');
    \$subdomain = trim(\$_POST['subdomain'] ?? '');
    \$email = trim(\$_POST['email'] ?? '');
    \$telephone = trim(\$_POST['telephone'] ?? '');";

$new_post_start = "if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log(\"=== INSCRIPTION DEBUG: DÉBUT TRAITEMENT POST ===\");
    error_log(\"INSCRIPTION DEBUG: Méthode: \" . \$_SERVER['REQUEST_METHOD']);
    error_log(\"INSCRIPTION DEBUG: Données POST reçues: \" . json_encode(\$_POST));
    error_log(\"INSCRIPTION DEBUG: User Agent: \" . (\$_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));
    error_log(\"INSCRIPTION DEBUG: IP Client: \" . (\$_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    
    \$nom = trim(\$_POST['nom'] ?? '');
    \$prenom = trim(\$_POST['prenom'] ?? '');
    \$nom_commercial = trim(\$_POST['nom_commercial'] ?? '');
    \$subdomain = trim(\$_POST['subdomain'] ?? '');
    \$email = trim(\$_POST['email'] ?? '');
    \$telephone = trim(\$_POST['telephone'] ?? '');
    
    error_log(\"INSCRIPTION DEBUG: Données extraites - nom: \$nom, prenom: \$prenom, subdomain: \$subdomain, email: \$email\");";

if (strpos($content, $old_post_start) !== false) {
    $content = str_replace($old_post_start, $new_post_start, $content);
    echo "✅ Debug ajouté au début du traitement POST\n";
} else {
    echo "⚠️ Début traitement POST non trouvé\n";
}

// 2. Ajouter debug pour la validation
$old_validation = "    // Validation des données
    \$errors = [];";

$new_validation = "    // Validation des données
    error_log(\"INSCRIPTION DEBUG: Début validation des données\");
    \$errors = [];";

if (strpos($content, $old_validation) !== false) {
    $content = str_replace($old_validation, $new_validation, $content);
    echo "✅ Debug ajouté à la validation\n";
} else {
    echo "⚠️ Section validation non trouvée\n";
}

// 3. Ajouter debug pour les erreurs de validation
$old_error_check = "    if (!empty(\$errors)) {
        \$response = [
            'success' => false,
            'errors' => \$errors
        ];";

$new_error_check = "    if (!empty(\$errors)) {
        error_log(\"INSCRIPTION DEBUG: Erreurs de validation détectées: \" . json_encode(\$errors));
        \$response = [
            'success' => false,
            'errors' => \$errors
        ];";

if (strpos($content, $old_error_check) !== false) {
    $content = str_replace($old_error_check, $new_error_check, $content);
    echo "✅ Debug ajouté aux erreurs de validation\n";
} else {
    echo "⚠️ Section erreurs validation non trouvée\n";
}

// 4. Ajouter debug avant la création de boutique
$old_shop_creation = "    try {
        // Créer la boutique
        require_once(__DIR__ . '/classes/ShopManager.php');";

$new_shop_creation = "    try {
        error_log(\"INSCRIPTION DEBUG: Validation réussie, début création boutique\");
        error_log(\"INSCRIPTION DEBUG: Subdomain final: \$subdomain\");
        
        // Créer la boutique
        require_once(__DIR__ . '/classes/ShopManager.php');";

if (strpos($content, $old_shop_creation) !== false) {
    $content = str_replace($old_shop_creation, $new_shop_creation, $content);
    echo "✅ Debug ajouté avant création boutique\n";
} else {
    echo "⚠️ Section création boutique non trouvée\n";
}

// 5. Ajouter debug après création réussie
$old_success_response = "        \$response = [
            'success' => true,
            'message' => 'Boutique créée avec succès !',
            'data' => [
                'url' => \"https://\$subdomain.servo.tools\",
                'admin_username' => \$admin_username
            ]
        ];";

$new_success_response = "        error_log(\"INSCRIPTION DEBUG: Boutique créée avec succès - ID: \$shop_id, URL: https://\$subdomain.servo.tools\");
        
        \$response = [
            'success' => true,
            'message' => 'Boutique créée avec succès !',
            'data' => [
                'url' => \"https://\$subdomain.servo.tools\",
                'admin_username' => \$admin_username
            ]
        ];";

if (strpos($content, $old_success_response) !== false) {
    $content = str_replace($old_success_response, $new_success_response, $content);
    echo "✅ Debug ajouté au succès\n";
} else {
    echo "⚠️ Section succès non trouvée\n";
}

// 6. Ajouter debug pour les exceptions
$old_exception = "    } catch (Exception \$e) {
        error_log(\"Erreur lors de la création de boutique: \" . \$e->getMessage());
        \$response = [
            'success' => false,
            'errors' => ['Une erreur technique s\\'est produite. Veuillez réessayer.']
        ];
    }";

$new_exception = "    } catch (Exception \$e) {
        error_log(\"INSCRIPTION DEBUG: EXCEPTION CAPTURÉE\");
        error_log(\"INSCRIPTION DEBUG: Message: \" . \$e->getMessage());
        error_log(\"INSCRIPTION DEBUG: Stack trace: \" . \$e->getTraceAsString());
        error_log(\"Erreur lors de la création de boutique: \" . \$e->getMessage());
        \$response = [
            'success' => false,
            'errors' => ['Une erreur technique s\\'est produite. Veuillez réessayer.']
        ];
    }";

if (strpos($content, $old_exception) !== false) {
    $content = str_replace($old_exception, $new_exception, $content);
    echo "✅ Debug ajouté aux exceptions\n";
} else {
    echo "⚠️ Section exception non trouvée\n";
}

// 7. Ajouter debug pour la réponse finale
$old_response_output = "    // Réponse JSON pour AJAX
    header('Content-Type: application/json');
    echo json_encode(\$response);
    exit;";

$new_response_output = "    // Réponse JSON pour AJAX
    error_log(\"INSCRIPTION DEBUG: Envoi réponse finale: \" . json_encode(\$response));
    header('Content-Type: application/json');
    echo json_encode(\$response);
    exit;";

if (strpos($content, $old_response_output) !== false) {
    $content = str_replace($old_response_output, $new_response_output, $content);
    echo "✅ Debug ajouté à la réponse finale\n";
} else {
    echo "⚠️ Section réponse finale non trouvée\n";
}

// Sauvegarder le fichier
if (file_put_contents($file_path, $content)) {
    echo "✅ Debug complet ajouté à inscription.php\n";
    
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

echo "\n=== DEBUG COMPLET AJOUTÉ ===\n";
echo "Maintenant, toute soumission de formulaire sera tracée complètement.\n";
echo "Surveillez les logs: tail -f /var/log/nginx/error.log | grep 'INSCRIPTION DEBUG'\n";
?>
