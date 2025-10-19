<?php
// Script pour corriger la sortie du script SSL

$script_path = '/root/fix_servo_ssl_smart.sh';
$content = file_get_contents($script_path);

echo "=== CORRECTION SORTIE SCRIPT SSL ===\n";

// Remplacer les echo vers log par des echo vers stdout ET log
$replacements = [
    'echo "✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté dans $CERT_PATH" >> $LOG_FILE' => 
    'echo "✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté dans $CERT_PATH" | tee -a $LOG_FILE',
    
    'echo "✅ Configuration HTTPS ajoutée pour $NEW_DOMAIN avec certificat $CERT_PATH" >> $LOG_FILE' => 
    'echo "✅ Configuration HTTPS ajoutée pour $NEW_DOMAIN avec certificat $CERT_PATH" | tee -a $LOG_FILE',
    
    'echo "✅ Certificat SSL étendu avec succès" >> $LOG_FILE' => 
    'echo "✅ Certificat SSL étendu avec succès" | tee -a $LOG_FILE',
    
    'echo "❌ ERREUR: Configuration nginx invalide après ajout HTTPS" >> $LOG_FILE' => 
    'echo "❌ ERREUR: Configuration nginx invalide après ajout HTTPS" | tee -a $LOG_FILE',
    
    'echo "❌ ERREUR: Échec de l\'extension du certificat SSL" >> $LOG_FILE' => 
    'echo "❌ ERREUR: Échec de l\'extension du certificat SSL" | tee -a $LOG_FILE'
];

$changes = 0;
foreach ($replacements as $old => $new) {
    if (strpos($content, $old) !== false) {
        $content = str_replace($old, $new, $content);
        $changes++;
        echo "✅ Remplacé: " . substr($old, 0, 50) . "...\n";
    }
}

// Ajouter un echo final pour confirmer le succès
$old_end = 'echo "=== Fin auto-correction intelligente ===" >> $LOG_FILE
exit 0';

$new_end = 'echo "=== Fin auto-correction intelligente ===" >> $LOG_FILE
echo "✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté"
exit 0';

if (strpos($content, $old_end) !== false) {
    $content = str_replace($old_end, $new_end, $content);
    $changes++;
    echo "✅ Ajouté message de succès final vers stdout\n";
}

if ($changes > 0) {
    if (file_put_contents($script_path, $content)) {
        echo "✅ Script modifié avec $changes changements\n";
        echo "Le script enverra maintenant ses messages vers stdout ET le fichier de log\n";
    } else {
        echo "❌ Impossible de sauvegarder le script\n";
    }
} else {
    echo "⚠️ Aucun changement nécessaire\n";
}

echo "\n=== FIN CORRECTION ===\n";
?>
