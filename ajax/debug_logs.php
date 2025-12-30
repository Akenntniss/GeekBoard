<?php
$logFile = '../logs/planning_error.log';
if (file_exists($logFile)) {
    echo "<pre>LOG FILE: $logFile\n\n";
    $content = file_get_contents($logFile);
    // Afficher les 2000 derniers caractères pour ne pas surcharger
    if (strlen($content) > 2000) {
        echo htmlspecialchars(substr($content, -2000));
    } else {
        echo htmlspecialchars($content);
    }
    echo "</pre>";
} else {
    echo "Log file $logFile not found.";
}
?>
