<?php
// Test direct de la fonction updateSSLCertificate avec le script corrigé

echo "=== TEST DIRECT FONCTION SSL ===\n";

// Inclure les fonctions d'inscription.php
require_once('/var/www/mdgeek.top/inscription.php');

// Tester avec un sous-domaine fictif
$test_subdomain = 'testdirectssl';

echo "Test de updateSSLCertificate avec: $test_subdomain\n";
echo "Logs à surveiller dans /var/log/nginx/error.log\n\n";

$start_time = microtime(true);
$result = updateSSLCertificate($test_subdomain);
$execution_time = microtime(true) - $start_time;

echo "=== RÉSULTAT ===\n";
echo "Résultat: " . ($result ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n";
echo "Temps d'exécution: " . round($execution_time, 2) . " secondes\n";

if ($result) {
    echo "\n=== VÉRIFICATION ===\n";
    echo "Vérification du certificat SSL...\n";
    $ssl_check = shell_exec("openssl s_client -connect $test_subdomain.servo.tools:443 -servername $test_subdomain.servo.tools < /dev/null 2>/dev/null | grep 'subject='");
    if ($ssl_check) {
        echo "✅ Certificat SSL: " . trim($ssl_check) . "\n";
    } else {
        echo "❌ Impossible de vérifier le certificat SSL\n";
    }
    
    echo "\nTest HTTPS...\n";
    $https_check = shell_exec("curl -sI https://$test_subdomain.servo.tools/ 2>/dev/null | head -1");
    if ($https_check) {
        echo "✅ HTTPS: " . trim($https_check) . "\n";
    } else {
        echo "❌ HTTPS non accessible\n";
    }
} else {
    echo "\n❌ La fonction SSL a échoué - vérifiez les logs pour plus de détails\n";
}

echo "\n=== COMMANDES UTILES ===\n";
echo "Logs SSL: tail -20 /var/log/nginx/error.log | grep 'SERVO SSL'\n";
echo "Vérifier nginx: grep '$test_subdomain' /etc/nginx/sites-available/servo.tools.conf\n";
echo "Test manuel: curl -I https://$test_subdomain.servo.tools/\n";
?>
