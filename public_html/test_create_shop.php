<?php
// Script pour simuler la création d'une boutique et capturer les logs de debug

echo "=== TEST CRÉATION BOUTIQUE AVEC DEBUG ===\n\n";

// Simuler l'appel à updateSSLCertificate comme dans inscription.php
function simulateShopCreation($subdomain) {
    echo "Simulation de la création de boutique pour : $subdomain.servo.tools\n";
    echo "Logs de debug à surveiller dans /var/log/nginx/error.log\n\n";
    
    // Inclure le fichier inscription.php pour avoir accès à la fonction
    // On ne peut pas l'inclure directement car il contient du HTML, donc on reproduit la fonction
    
    function updateSSLCertificate($subdomain) {
        try {
            $new_domain = $subdomain . '.servo.tools';
            error_log("=== SERVO SSL DEBUG: DÉBUT FONCTION updateSSLCertificate ===");
            error_log("SERVO SSL DEBUG: Sous-domaine reçu: $subdomain");
            error_log("SERVO SSL DEBUG: Domaine complet: $new_domain");
            error_log("SERVO SSL DEBUG: Utilisateur actuel: " . get_current_user());
            error_log("SERVO SSL DEBUG: Working directory: " . getcwd());
            error_log("SERVO SSL: Début correction automatique pour $new_domain");
            
            // Utiliser le script FINAL qui force le certificat principal servo.tools
            error_log("SERVO SSL DEBUG: Recherche du script SSL...");
            $fix_script = '/root/fix_servo_ssl_smart.sh';
            error_log("SERVO SSL DEBUG: Test script principal: $fix_script");
            error_log("SERVO SSL DEBUG: Script principal existe: " . (file_exists($fix_script) ? 'OUI' : 'NON'));
            if (!file_exists($fix_script)) {
                error_log("SERVO SSL: Script final introuvable, fallback vers amélioré");
                $fix_script = '/root/fix_servo_ssl_improved.sh';
                error_log("SERVO SSL DEBUG: Test script amélioré: $fix_script");
                error_log("SERVO SSL DEBUG: Script amélioré existe: " . (file_exists($fix_script) ? 'OUI' : 'NON'));
                if (!file_exists($fix_script)) {
                    error_log("SERVO SSL: Script amélioré introuvable, utilisation de l'ancien");
                    $fix_script = '/root/fix_servo_ssl.sh';
                    error_log("SERVO SSL DEBUG: Test script ancien: $fix_script");
                    error_log("SERVO SSL DEBUG: Script ancien existe: " . (file_exists($fix_script) ? 'OUI' : 'NON'));
                }
            }
            error_log("SERVO SSL DEBUG: Script final sélectionné: $fix_script");
            
            if (!file_exists($fix_script)) {
                error_log("SERVO SSL: Script de correction introuvable : $fix_script");
                return false;
            }
            
            // Passer le sous-domaine en paramètre au script amélioré
            $cmd = "sudo bash " . escapeshellarg($fix_script) . " " . escapeshellarg($subdomain) . " 2>&1";
            error_log("SERVO SSL DEBUG: Commande construite: $cmd");
            error_log("SERVO SSL DEBUG: Permissions du script: " . substr(sprintf('%o', fileperms($fix_script)), -4));
            error_log("SERVO SSL DEBUG: Script exécutable: " . (is_executable($fix_script) ? 'OUI' : 'NON'));
            error_log("SERVO SSL: Exécution commande améliorée : $cmd");
            
            $start_time = microtime(true);
            $output = shell_exec($cmd);
            $execution_time = microtime(true) - $start_time;
            
            error_log("SERVO SSL DEBUG: Temps d'exécution: " . round($execution_time, 2) . " secondes");
            error_log("SERVO SSL DEBUG: Taille de la sortie: " . strlen($output) . " caractères");
            
            error_log("SERVO SSL: Sortie script amélioré : " . substr($output, 0, 500));
            error_log("SERVO SSL DEBUG: Sortie complète du script:");
            error_log($output);
            
            // Vérifier le succès
            error_log("SERVO SSL DEBUG: Analyse des conditions de succès...");
            $success_patterns = [
                "✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté",
                "✅ SSL_SUCCESS: Configuration complète",
                "✅ Certificat SSL étendu avec succès",
                "Successfully received certificate",
                "Certificate not yet due for renewal",
                "SSL_SUCCESS:"
            ];
            
            $success_found = false;
            $matched_pattern = '';
            foreach ($success_patterns as $pattern) {
                if (strpos($output, $pattern) !== false) {
                    $success_found = true;
                    $matched_pattern = $pattern;
                    break;
                }
            }
            
            error_log("SERVO SSL DEBUG: Succès détecté: " . ($success_found ? 'OUI' : 'NON'));
            if ($success_found) {
                error_log("SERVO SSL DEBUG: Pattern correspondant: $matched_pattern");
            }
            
            if ($success_found) {
                error_log("SERVO SSL: Configuration FINALE (HTTP + HTTPS + SSL) avec certificat principal servo.tools créée pour : " . $new_domain . " - Output: " . substr($output, 0, 200));
                return true;
            } else {
                error_log("SERVO SSL: Erreur lors de la configuration FINALE via script principal : " . $output);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SERVO SSL DEBUG: EXCEPTION CAPTURÉE");
            error_log("SERVO SSL DEBUG: Message d'exception: " . $e->getMessage());
            error_log("SERVO SSL DEBUG: Stack trace: " . $e->getTraceAsString());
            error_log("SERVO SSL: Exception lors de la configuration SSL améliorée : " . $e->getMessage());
            return false;
        }
        error_log("=== SERVO SSL DEBUG: FIN FONCTION updateSSLCertificate ===");
    }
    
    // Simuler l'appel comme dans inscription.php
    error_log("=== INSCRIPTION DEBUG: APPEL updateSSLCertificate ===");
    error_log("INSCRIPTION DEBUG: Sous-domaine à traiter: $subdomain");
    error_log("INSCRIPTION DEBUG: Shop ID: SIMULATION");
    error_log("INSCRIPTION DEBUG: Heure: " . date('Y-m-d H:i:s'));
    
    $ssl_updated = updateSSLCertificate($subdomain);
    
    error_log("INSCRIPTION DEBUG: Résultat updateSSLCertificate: " . ($ssl_updated ? 'SUCCÈS' : 'ÉCHEC'));
    if (!$ssl_updated) {
        error_log("INSCRIPTION DEBUG: ÉCHEC SSL - La boutique serait créée sans certificat SSL");
    }
    
    return $ssl_updated;
}

// Tester avec testdebug
echo "Test avec testdebug.servo.tools...\n";
$result = simulateShopCreation('testdebug');

echo "\n=== RÉSULTAT ===\n";
echo $result ? "✅ SUCCÈS: Le processus SSL a fonctionné\n" : "❌ ÉCHEC: Le processus SSL a échoué\n";

echo "\n=== INSTRUCTIONS ===\n";
echo "1. Vérifiez les logs détaillés avec : tail -f /var/log/nginx/error.log | grep 'SERVO SSL DEBUG\\|INSCRIPTION DEBUG'\n";
echo "2. Créez maintenant une vraie boutique via https://servo.tools/inscription.php\n";
echo "3. Les mêmes logs de debug apparaîtront pour identifier le problème exact\n";
?> 