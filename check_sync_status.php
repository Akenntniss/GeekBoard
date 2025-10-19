<?php
/**
 * Script pour vérifier le statut de la synchronisation automatique des mappings
 */

define('LOG_FILE', '/var/log/geekboard_sync_mappings.log');

echo "=== Statut de la synchronisation automatique des mappings ===\n\n";

// Vérifier si le fichier de log existe
if (!file_exists(LOG_FILE)) {
    echo "❌ Fichier de log introuvable: " . LOG_FILE . "\n";
    exit(1);
}

// Lire les dernières lignes du log
$log_lines = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$recent_lines = array_slice($log_lines, -20); // 20 dernières lignes

echo "📊 Dernières activités de synchronisation:\n";
echo str_repeat("-", 60) . "\n";

foreach ($recent_lines as $line) {
    // Colorer les différents types de messages
    if (strpos($line, 'ERROR') !== false) {
        echo "🔴 $line\n";
    } elseif (strpos($line, 'SUCCESS') !== false) {
        echo "✅ $line\n";
    } elseif (strpos($line, 'NOUVEAU') !== false) {
        echo "🆕 $line\n";
    } elseif (strpos($line, 'SYNC') !== false) {
        echo "🔄 $line\n";
    } elseif (strpos($line, 'OK:') !== false) {
        echo "✅ $line\n";
    } else {
        echo "ℹ️  $line\n";
    }
}

echo str_repeat("-", 60) . "\n";

// Statistiques
$total_lines = count($log_lines);
$error_count = count(array_filter($log_lines, function($line) {
    return strpos($line, 'ERROR') !== false;
}));
$success_count = count(array_filter($log_lines, function($line) {
    return strpos($line, 'SUCCESS') !== false;
}));
$sync_count = count(array_filter($log_lines, function($line) {
    return strpos($line, 'SYNC:') !== false;
}));

echo "\n📈 Statistiques globales:\n";
echo "   Total d'entrées: $total_lines\n";
echo "   Synchronisations effectuées: $sync_count\n";
echo "   Succès: $success_count\n";
echo "   Erreurs: $error_count\n";

// Vérifier la tâche cron
echo "\n🕐 Configuration cron:\n";
$cron_output = shell_exec('crontab -l 2>/dev/null | grep sync_mappings');
if ($cron_output) {
    echo "   ✅ Tâche cron active: " . trim($cron_output) . "\n";
    echo "   ⏰ Exécution: Toutes les 2 minutes\n";
} else {
    echo "   ❌ Aucune tâche cron trouvée\n";
}

// Vérifier la dernière exécution
$last_line = end($log_lines);
if ($last_line && preg_match('/\[([^\]]+)\]/', $last_line, $matches)) {
    $last_execution = $matches[1];
    $last_time = strtotime($last_execution);
    $minutes_ago = round((time() - $last_time) / 60);
    
    echo "\n🕒 Dernière exécution:\n";
    echo "   📅 Date: $last_execution\n";
    echo "   ⏱️  Il y a: $minutes_ago minute(s)\n";
    
    if ($minutes_ago > 5) {
        echo "   ⚠️  Attention: Dernière exécution il y a plus de 5 minutes\n";
    } else {
        echo "   ✅ Synchronisation récente\n";
    }
}

echo "\n=== Fin du rapport ===\n";
?>
