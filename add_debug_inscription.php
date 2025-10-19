<?php
// Script pour ajouter du debug détaillé à inscription.php

$file_path = '/var/www/mdgeek.top/inscription.php';
$content = file_get_contents($file_path);

echo "=== AJOUT DEBUG DÉTAILLÉ À INSCRIPTION.PHP ===\n";

// 1. Ajouter du debug au début de la fonction updateSSLCertificate
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
        error_log(\"SERVO SSL DEBUG: Working directory: \" . getcwd());
        error_log(\"SERVO SSL: Début correction automatique pour \$new_domain\");";

if (strpos($content, $old_function_start) !== false) {
    $content = str_replace($old_function_start, $new_function_start, $content);
    echo "✅ Debug ajouté au début de updateSSLCertificate\n";
} else {
    echo "⚠️ Début de fonction updateSSLCertificate non trouvé\n";
}

// 2. Ajouter du debug pour la détection des scripts
$old_script_detection = "        // Utiliser le script FINAL qui force le certificat principal servo.tools
        \$fix_script = '/root/fix_servo_ssl_smart.sh';
        if (!file_exists(\$fix_script)) {
            error_log(\"SERVO SSL: Script final introuvable, fallback vers amélioré\");
            \$fix_script = '/root/fix_servo_ssl_improved.sh';
            if (!file_exists(\$fix_script)) {
                error_log(\"SERVO SSL: Script amélioré introuvable, utilisation de l'ancien\");
                \$fix_script = '/root/fix_servo_ssl.sh';
            }
        }";

$new_script_detection = "        // Utiliser le script FINAL qui force le certificat principal servo.tools
        error_log(\"SERVO SSL DEBUG: Recherche du script SSL...\");
        \$fix_script = '/root/fix_servo_ssl_smart.sh';
        error_log(\"SERVO SSL DEBUG: Test script principal: \$fix_script\");
        error_log(\"SERVO SSL DEBUG: Script principal existe: \" . (file_exists(\$fix_script) ? 'OUI' : 'NON'));
        if (!file_exists(\$fix_script)) {
            error_log(\"SERVO SSL: Script final introuvable, fallback vers amélioré\");
            \$fix_script = '/root/fix_servo_ssl_improved.sh';
            error_log(\"SERVO SSL DEBUG: Test script amélioré: \$fix_script\");
            error_log(\"SERVO SSL DEBUG: Script amélioré existe: \" . (file_exists(\$fix_script) ? 'OUI' : 'NON'));
            if (!file_exists(\$fix_script)) {
                error_log(\"SERVO SSL: Script amélioré introuvable, utilisation de l'ancien\");
                \$fix_script = '/root/fix_servo_ssl.sh';
                error_log(\"SERVO SSL DEBUG: Test script ancien: \$fix_script\");
                error_log(\"SERVO SSL DEBUG: Script ancien existe: \" . (file_exists(\$fix_script) ? 'OUI' : 'NON'));
            }
        }
        error_log(\"SERVO SSL DEBUG: Script final sélectionné: \$fix_script\");";

if (strpos($content, $old_script_detection) !== false) {
    $content = str_replace($old_script_detection, $new_script_detection, $content);
    echo "✅ Debug ajouté à la détection des scripts\n";
} else {
    echo "⚠️ Section de détection des scripts non trouvée\n";
}

// 3. Ajouter du debug pour l'exécution de la commande
$old_command_execution = "        // Passer le sous-domaine en paramètre au script amélioré
        \$cmd = \"sudo bash \" . escapeshellarg(\$fix_script) . \" \" . escapeshellarg(\$subdomain) . \" 2>&1\";
        error_log(\"SERVO SSL: Exécution commande améliorée : \$cmd\");
        \$output = shell_exec(\$cmd);";

$new_command_execution = "        // Passer le sous-domaine en paramètre au script amélioré
        \$cmd = \"sudo bash \" . escapeshellarg(\$fix_script) . \" \" . escapeshellarg(\$subdomain) . \" 2>&1\";
        error_log(\"SERVO SSL DEBUG: Commande construite: \$cmd\");
        error_log(\"SERVO SSL DEBUG: Permissions du script: \" . substr(sprintf('%o', fileperms(\$fix_script)), -4));
        error_log(\"SERVO SSL DEBUG: Script exécutable: \" . (is_executable(\$fix_script) ? 'OUI' : 'NON'));
        error_log(\"SERVO SSL: Exécution commande améliorée : \$cmd\");
        
        \$start_time = microtime(true);
        \$output = shell_exec(\$cmd);
        \$execution_time = microtime(true) - \$start_time;
        
        error_log(\"SERVO SSL DEBUG: Temps d'exécution: \" . round(\$execution_time, 2) . \" secondes\");
        error_log(\"SERVO SSL DEBUG: Taille de la sortie: \" . strlen(\$output) . \" caractères\");";

if (strpos($content, $old_command_execution) !== false) {
    $content = str_replace($old_command_execution, $new_command_execution, $content);
    echo "✅ Debug ajouté à l'exécution de commande\n";
} else {
    echo "⚠️ Section d'exécution de commande non trouvée\n";
}

// 4. Ajouter du debug pour la vérification des résultats
$old_result_check = "        error_log(\"SERVO SSL: Sortie script amélioré : \" . substr(\$output, 0, 500));
        
        // Vérifier le succès";

$new_result_check = "        error_log(\"SERVO SSL: Sortie script amélioré : \" . substr(\$output, 0, 500));
        error_log(\"SERVO SSL DEBUG: Sortie complète du script:\");
        error_log(\$output);
        
        // Vérifier le succès
        error_log(\"SERVO SSL DEBUG: Analyse des conditions de succès...\");";

if (strpos($content, $old_result_check) !== false) {
    $content = str_replace($old_result_check, $new_result_check, $content);
    echo "✅ Debug ajouté à la vérification des résultats\n";
} else {
    echo "⚠️ Section de vérification des résultats non trouvée\n";
}

// 5. Ajouter du debug pour les conditions de succès
$old_success_conditions = "        if (strpos(\$output, \"✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté\") !== false ||
            strpos(\$output, \"✅ SSL_SUCCESS: Configuration complète\") !== false || 
            strpos(\$output, \"✅ Certificat SSL étendu avec succès\") !== false || 
            strpos(\$output, \"Successfully received certificate\") !== false ||
            strpos(\$output, \"Certificate not yet due for renewal\") !== false ||
            strpos(\$output, \"SSL_SUCCESS:\") !== false) {";

$new_success_conditions = "        \$success_patterns = [
            \"✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté\",
            \"✅ SSL_SUCCESS: Configuration complète\",
            \"✅ Certificat SSL étendu avec succès\",
            \"Successfully received certificate\",
            \"Certificate not yet due for renewal\",
            \"SSL_SUCCESS:\"
        ];
        
        \$success_found = false;
        \$matched_pattern = '';
        foreach (\$success_patterns as \$pattern) {
            if (strpos(\$output, \$pattern) !== false) {
                \$success_found = true;
                \$matched_pattern = \$pattern;
                break;
            }
        }
        
        error_log(\"SERVO SSL DEBUG: Succès détecté: \" . (\$success_found ? 'OUI' : 'NON'));
        if (\$success_found) {
            error_log(\"SERVO SSL DEBUG: Pattern correspondant: \$matched_pattern\");
        }
        
        if (\$success_found) {";

if (strpos($content, $old_success_conditions) !== false) {
    $content = str_replace($old_success_conditions, $new_success_conditions, $content);
    echo "✅ Debug ajouté aux conditions de succès\n";
} else {
    echo "⚠️ Section des conditions de succès non trouvée\n";
}

// 6. Ajouter du debug à l'appel de updateSSLCertificate dans le processus principal
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
    echo "✅ Debug ajouté à l'appel SSL dans le processus principal\n";
} else {
    echo "⚠️ Appel SSL dans le processus principal non trouvé\n";
}

// 7. Ajouter du debug à la fin de la fonction updateSSLCertificate
$old_function_end = "    } catch (Exception \$e) {
        error_log(\"SERVO SSL: Exception lors de la configuration SSL améliorée : \" . \$e->getMessage());
        return false;
    }
}";

$new_function_end = "    } catch (Exception \$e) {
        error_log(\"SERVO SSL DEBUG: EXCEPTION CAPTURÉE\");
        error_log(\"SERVO SSL DEBUG: Message d'exception: \" . \$e->getMessage());
        error_log(\"SERVO SSL DEBUG: Stack trace: \" . \$e->getTraceAsString());
        error_log(\"SERVO SSL: Exception lors de la configuration SSL améliorée : \" . \$e->getMessage());
        return false;
    }
    error_log(\"=== SERVO SSL DEBUG: FIN FONCTION updateSSLCertificate ===\");
}";

if (strpos($content, $old_function_end) !== false) {
    $content = str_replace($old_function_end, $new_function_end, $content);
    echo "✅ Debug ajouté à la fin de la fonction\n";
} else {
    echo "⚠️ Fin de fonction updateSSLCertificate non trouvée\n";
}

// Sauvegarder le fichier modifié
if (file_put_contents($file_path, $content)) {
    echo "✅ Fichier inscription.php mis à jour avec debug détaillé\n";
    
    // Vérifier la syntaxe PHP
    $syntax_check = shell_exec("php -l $file_path 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "✅ Syntaxe PHP valide après ajout du debug\n";
        echo "\n=== DEBUG AJOUTÉ AVEC SUCCÈS ===\n";
        echo "Maintenant, lors de la prochaine création de boutique :\n";
        echo "1. Tous les détails seront loggés dans /var/log/nginx/error.log\n";
        echo "2. Cherchez les messages 'SERVO SSL DEBUG' et 'INSCRIPTION DEBUG'\n";
        echo "3. Cela permettra d'identifier exactement où le processus échoue\n";
    } else {
        echo "❌ Erreur de syntaxe PHP après ajout du debug :\n$syntax_check\n";
        exit(1);
    }
} else {
    echo "❌ Impossible de sauvegarder le fichier\n";
    exit(1);
}
?>
