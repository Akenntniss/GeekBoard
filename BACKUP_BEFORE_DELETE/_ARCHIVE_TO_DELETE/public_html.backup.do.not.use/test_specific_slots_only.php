<?php
/**
 * Test du système avec créneaux spécifiques uniquement
 * 
 * Ce script teste :
 * 1. Pointage dans les créneaux spécifiques = auto-approbation
 * 2. Pointage hors créneaux spécifiques = demande d'approbation
 * 3. Pointage sans créneaux définis = demande d'approbation
 */

echo "<h1>🧪 Test du système créneaux spécifiques uniquement</h1>";

// Données de test
$tests = [
    [
        'name' => 'Pointage user 7 dans créneau matin (10:30)',
        'user_id' => 7,
        'clock_time' => '2025-01-10 10:30:00',
        'expected' => 'auto_approve = true'
    ],
    [
        'name' => 'Pointage user 7 hors créneau matin (07:30)',
        'user_id' => 7,
        'clock_time' => '2025-01-10 07:30:00',
        'expected' => 'auto_approve = false'
    ],
    [
        'name' => 'Pointage user 7 dans créneau après-midi (15:00)',
        'user_id' => 7,
        'clock_time' => '2025-01-10 15:00:00',
        'expected' => 'auto_approve = true'
    ],
    [
        'name' => 'Pointage user 7 hors créneau après-midi (20:00)',
        'user_id' => 7,
        'clock_time' => '2025-01-10 20:00:00',
        'expected' => 'auto_approve = false'
    ],
    [
        'name' => 'Pointage user 6 (sans créneaux définis)',
        'user_id' => 6,
        'clock_time' => '2025-01-10 10:00:00',
        'expected' => 'auto_approve = false'
    ]
];

// Fonction simulée depuis l'API QR
function simulateCheckTimeSlotApproval($user_id, $clock_time) {
    // Configuration BDD (simulée)
    $time_slots = [
        7 => [
            'morning' => ['start_time' => '10:00:00', 'end_time' => '12:30:00'],
            'afternoon' => ['start_time' => '14:00:00', 'end_time' => '19:00:00']
        ]
        // User 6 n'a pas de créneaux
    ];
    
    $time_only = date('H:i:s', strtotime($clock_time));
    $day_period = (date('H', strtotime($clock_time)) < 13) ? 'morning' : 'afternoon';
    
    // Vérifier uniquement les créneaux spécifiques à l'utilisateur
    if (isset($time_slots[$user_id]) && isset($time_slots[$user_id][$day_period])) {
        $user_slot = $time_slots[$user_id][$day_period];
        
        if ($time_only >= $user_slot['start_time'] && $time_only <= $user_slot['end_time']) {
            return [
                'auto_approve' => true,
                'reason' => "Pointage dans créneau autorisé ({$user_slot['start_time']}-{$user_slot['end_time']})"
            ];
        } else {
            return [
                'auto_approve' => false,
                'reason' => "Pointage hors créneau autorisé ({$user_slot['start_time']}-{$user_slot['end_time']})"
            ];
        }
    }
    
    // Aucun créneau spécifique défini = demande d'approbation systématique
    return [
        'auto_approve' => false,
        'reason' => 'Aucun créneau horaire défini pour cet utilisateur'
    ];
}

echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Test</th><th>Utilisateur</th><th>Heure</th><th>Résultat</th><th>Raison</th><th>Status</th>";
echo "</tr>";

foreach ($tests as $test) {
    $result = simulateCheckTimeSlotApproval($test['user_id'], $test['clock_time']);
    
    $status = ($result['auto_approve'] === true && strpos($test['expected'], 'true') !== false) ||
              ($result['auto_approve'] === false && strpos($test['expected'], 'false') !== false);
    
    $statusText = $status ? '✅ PASS' : '❌ FAIL';
    $statusColor = $status ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background: $statusColor;'>";
    echo "<td>{$test['name']}</td>";
    echo "<td>User {$test['user_id']}</td>";
    echo "<td>" . date('H:i:s', strtotime($test['clock_time'])) . "</td>";
    echo "<td>" . ($result['auto_approve'] ? 'AUTO-APPROUVÉ' : 'DEMANDE APPROBATION') . "</td>";
    echo "<td>{$result['reason']}</td>";
    echo "<td><strong>$statusText</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>📋 Résumé du système</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h3>✅ Système créneaux spécifiques uniquement</h3>";
echo "<ul>";
echo "<li><strong>✅ Approbation automatique :</strong> Pointages dans les créneaux spécifiques de l'employé</li>";
echo "<li><strong>⏳ Demande d'approbation :</strong> Pointages hors créneaux spécifiques</li>";
echo "<li><strong>⏳ Demande d'approbation :</strong> Employés sans créneaux définis</li>";
echo "<li><strong>❌ Plus de créneaux globaux :</strong> Chaque employé doit avoir ses propres créneaux</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Liens utiles</h2>";
echo "<ul>";
echo "<li><a href='https://mkmkmk.mdgeek.top/index.php?page=admin_timetracking'>📊 Page Admin Timetracking</a></li>";
echo "<li><a href='https://mkmkmk.mdgeek.top/pointage_qr.php'>📱 Page Pointage QR</a></li>";
echo "</ul>";
?>

<script>
console.log('🧪 Test créneaux spécifiques uniquement - Page chargée');
</script>
