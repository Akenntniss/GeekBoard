<?php
/**
 * Diagnostic complet du système de pointage
 */

echo "🔍 DIAGNOSTIC COMPLET SYSTÈME DE POINTAGE\n";
echo "=========================================\n\n";

$base_path = '/var/www/mdgeek.top';

// 1. Vérifier les fichiers principaux
echo "📁 1. VÉRIFICATION FICHIERS PRINCIPAUX\n";
echo "-------------------------------------\n";

$files_to_check = [
    "$base_path/time_tracking_api.php" => "API Pointage",
    "$base_path/assets/js/time_tracking.js" => "JavaScript Pointage", 
    "$base_path/pages/admin_timetracking.php" => "Interface Admin",
    "$base_path/includes/navbar.php" => "Navbar modifiée",
    "$base_path/includes/modals.php" => "Menu latéral",
    "$base_path/pages/presence_gestion.php" => "Page présence"
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "✅ $description: EXISTS ($size bytes, modifié: $modified)\n";
    } else {
        echo "❌ $description: MISSING\n";
    }
}

echo "\n📋 2. VÉRIFICATION CONTENU NAVBAR\n";
echo "--------------------------------\n";

$navbar_path = "$base_path/includes/navbar.php";
if (file_exists($navbar_path)) {
    $navbar_content = file_get_contents($navbar_path);
    
    $checks = [
        'Clock-In' => strpos($navbar_content, 'Clock-In') !== false,
        'Clock-Out' => strpos($navbar_content, 'Clock-Out') !== false,
        'time_tracking.js' => strpos($navbar_content, 'time_tracking.js') !== false,
        'time-status-display' => strpos($navbar_content, 'time-status-display') !== false,
        'clock-button' => strpos($navbar_content, 'clock-button') !== false
    ];
    
    foreach ($checks as $feature => $found) {
        echo ($found ? "✅" : "❌") . " $feature dans navbar\n";
    }
} else {
    echo "❌ Navbar file not found\n";
}

echo "\n📋 3. VÉRIFICATION CONTENU MODALS\n";
echo "--------------------------------\n";

$modals_path = "$base_path/includes/modals.php";
if (file_exists($modals_path)) {
    $modals_content = file_get_contents($modals_path);
    
    $checks = [
        'Gestion Pointage' => strpos($modals_content, 'Gestion Pointage') !== false,
        'timetracking-card' => strpos($modals_content, 'timetracking-card') !== false,
        'admin_timetracking.php' => strpos($modals_content, 'admin_timetracking.php') !== false,
        'bg-gradient-timetracking' => strpos($modals_content, 'bg-gradient-timetracking') !== false
    ];
    
    foreach ($checks as $feature => $found) {
        echo ($found ? "✅" : "❌") . " $feature dans modals\n";
    }
} else {
    echo "❌ Modals file not found\n";
}

echo "\n📋 4. VÉRIFICATION PRESENCE_GESTION\n";
echo "----------------------------------\n";

$presence_path = "$base_path/pages/presence_gestion.php";
if (file_exists($presence_path)) {
    $presence_content = file_get_contents($presence_path);
    
    $checks = [
        'Mon Système de Pointage' => strpos($presence_content, 'Mon Système de Pointage') !== false,
        'time_tracking' => strpos($presence_content, 'time_tracking') !== false,
        'clock_in' => strpos($presence_content, 'clock_in') !== false,
        'timeTracking' => strpos($presence_content, 'timeTracking') !== false
    ];
    
    foreach ($checks as $feature => $found) {
        echo ($found ? "✅" : "❌") . " $feature dans presence_gestion\n";
    }
    
    // Taille du fichier
    $size = filesize($presence_path);
    echo "📏 Taille presence_gestion.php: $size bytes\n";
} else {
    echo "❌ Presence_gestion file not found\n";
}

echo "\n📋 5. VÉRIFICATION BASE DE DONNÉES\n";
echo "---------------------------------\n";

try {
    require_once "$base_path/config/database.php";
    
    if (isset($shop_pdo) && $shop_pdo !== null) {
        echo "✅ Connexion base de données OK\n";
        
        // Vérifier les tables
        $tables = ['time_tracking', 'time_tracking_settings', 'users'];
        foreach ($tables as $table) {
            $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table $table existe\n";
                
                if ($table === 'time_tracking') {
                    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM $table");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "   📊 Entrées dans $table: {$result['count']}\n";
                }
            } else {
                echo "❌ Table $table manquante\n";
            }
        }
    } else {
        echo "❌ Pas de connexion base de données\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "\n";
}

echo "\n📋 6. TEST API\n";
echo "-------------\n";

$api_path = "$base_path/time_tracking_api.php";
if (file_exists($api_path)) {
    echo "✅ Fichier API existe\n";
    
    // Test syntaxe
    $output = shell_exec("php -l $api_path 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntaxe API correcte\n";
    } else {
        echo "❌ Erreur syntaxe API: $output\n";
    }
} else {
    echo "❌ Fichier API manquant\n";
}

echo "\n📋 7. PERMISSIONS FICHIERS\n";
echo "-------------------------\n";

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $owner = posix_getpwuid(fileowner($file))['name'] ?? 'unknown';
        $group = posix_getgrgid(filegroup($file))['name'] ?? 'unknown';
        echo "📝 $description: $perms ($owner:$group)\n";
    }
}

echo "\n🏁 DIAGNOSTIC TERMINÉ\n";
echo "====================\n";
?>
