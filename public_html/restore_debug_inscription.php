<?php
// Restaurer le debug dans inscription.php maintenant que le script SSL est corrigé

$file_path = '/var/www/mdgeek.top/inscription.php';
$content = file_get_contents($file_path);

echo "=== RESTAURATION DEBUG INSCRIPTION.PHP ===\n";

// 1. Ajouter debug à l'appel updateSSLCertificate
$old_ssl_call = "        // ÉTAPE 2 : Étendre le certificat SSL principal avec le nouveau sous-domaine (méthode mdgeek.top)
        \$ssl_updated = updateSSLCertificate(\$subdomain);";

$new_ssl_call = "        // ÉTAPE 2 : Étendre le certificat SSL principal avec le nouveau sous-domaine (méthode mdgeek.top)
        error_log(\"=== INSCRIPTION DEBUG: APPEL updateSSLCertificate ===\");
        error_log(\"INSCRIPTION DEBUG: Sous-domaine à traiter: \$subdomain\");
        error_log(\"INSCRIPTION DEBUG: Shop ID: \$shop_id\");
        error_log(\"INSCRIPTION DEBUG: Heure: \" . date('Y-m-d H:i:s'));
        
        \$ssl_updated = updateSSLCertificate(\$subdomain);
        
        error_log(\"INSCRIPTION DEBUG: Résultat updateSSLCertificate: \" . (\$ssl_updated ? 'SUCCÈS' : 'ÉCHEC'));
        if (!\$ssl_updated) {
            error_log(\"INSCRIPTION DEBUG: ÉCHEC SSL - La boutique sera créée sans certificat SSL\");
        }";

if (strpos($content, $old_ssl_call) !== false) {
    $content = str_replace($old_ssl_call, $new_ssl_call, $content);
    echo "✅ Debug ajouté à l'appel SSL\n";
} else {
    echo "⚠️ Appel SSL non trouvé, recherche d'une variante...\n";
    // Chercher une variante
    if (preg_match('/(\$ssl_updated = updateSSLCertificate\(\$subdomain\);)/', $content, $matches)) {
        $simple_call = $matches[1];
        $debug_call = "error_log(\"=== INSCRIPTION DEBUG: APPEL updateSSLCertificate ===\");
        error_log(\"INSCRIPTION DEBUG: Sous-domaine à traiter: \$subdomain\");
        error_log(\"INSCRIPTION DEBUG: Shop ID: \$shop_id\");
        error_log(\"INSCRIPTION DEBUG: Heure: \" . date('Y-m-d H:i:s'));
        
        $simple_call
        
        error_log(\"INSCRIPTION DEBUG: Résultat updateSSLCertificate: \" . (\$ssl_updated ? 'SUCCÈS' : 'ÉCHEC'));
        if (!\$ssl_updated) {
            error_log(\"INSCRIPTION DEBUG: ÉCHEC SSL - La boutique sera créée sans certificat SSL\");
        }";
        
        $content = str_replace($simple_call, $debug_call, $content);
        echo "✅ Debug ajouté à l'appel SSL (variante)\n";
    }
}

// 2. Ajouter debug au début de updateSSLCertificate
$old_function_start = "function updateSSLCertificate(\$subdomain) {
    try {
        \$new_domain = \$subdomain . '.servo.tools';
        error_log(\"SERVO SSL: Début correction automatique pour \$new_domain\");";

$new_function_start = "function updateSSLCertificate(\$subdomain) {
    try {
        \$new_domain = \$subdomain . '.servo.tools';
        error_log(\"=== SERVO SSL DEBUG: DÉBUT FONCTION updateSSLCertificate ===\");
        error_log(\"SERVO SSL DEBUG: Sous-domaine reçu: \$subdomain\");
        error_log(\"SERVO SSL DEBUG: Domaine complet: \$new_domain\");
        error_log(\"SERVO SSL DEBUG: Utilisateur actuel: \" . get_current_user());
        error_log(\"SERVO SSL: Début correction automatique pour \$new_domain\");";

if (strpos($content, $old_function_start) !== false) {
    $content = str_replace($old_function_start, $new_function_start, $content);
    echo "✅ Debug ajouté au début de updateSSLCertificate\n";
} else {
    echo "⚠️ Début de fonction updateSSLCertificate non trouvé\n";
}

// 3. Ajouter debug à la fin de la fonction updateSSLCertificate
$old_function_end = "    } catch (Exception \$e) {
        error_log(\"SERVO SSL: Exception lors de la configuration SSL améliorée : \" . \$e->getMessage());
        return false;
    }
}";

$new_function_end = "    } catch (Exception \$e) {
        error_log(\"SERVO SSL DEBUG: EXCEPTION CAPTURÉE\");
        error_log(\"SERVO SSL DEBUG: Message d'exception: \" . \$e->getMessage());
        error_log(\"SERVO SSL: Exception lors de la configuration SSL améliorée : \" . \$e->getMessage());
        return false;
    }
    error_log(\"=== SERVO SSL DEBUG: FIN FONCTION updateSSLCertificate ===\");
}";

if (strpos($content, $old_function_end) !== false) {
    $content = str_replace($old_function_end, $new_function_end, $content);
    echo "✅ Debug ajouté à la fin de updateSSLCertificate\n";
} else {
    echo "⚠️ Fin de fonction updateSSLCertificate non trouvée\n";
}

// Sauvegarder le fichier
if (file_put_contents($file_path, $content)) {
    echo "✅ Debug restauré dans inscription.php\n";
    
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

echo "\n=== DEBUG RESTAURÉ ===\n";
echo "Le processus SSL automatique d'inscription.php est maintenant tracé.\n";
echo "Testez avec une vraie création de boutique pour voir les logs.\n";
?>
