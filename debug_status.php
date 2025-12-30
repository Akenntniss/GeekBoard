<?php
// Debug du statut des employés

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

// La fonction à tester (copie exacte de accueil-modern.php)
function get_employee_status() {
    try {
        $shop_pdo = getShopDBConnection();
        
        // Récupérer tous les utilisateurs EN LIGNE avec leurs réparations en cours
        $stmt = $shop_pdo->query("
            SELECT 
                u.id as user_id,
                u.full_name as user_name,
                u.role,
                u.is_online,
                r.id as reparation_id,
                r.appareil as model,
                r.probleme_description as probleme,
                r.date_reception,
                r.statut,
                c.nom as client_nom,
                c.prenom as client_prenom
            FROM users u
            LEFT JOIN reparations r ON u.id = r.employe_id 
                AND r.statut IN ('en_cours', 'diagnostic', 'attente_piece', 'reparation_en_cours')
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE u.is_online = 1
            ORDER BY u.full_name, r.date_reception DESC
        ");
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Nombre d'utilisateurs trouvés: " . count($users) . "\n";
        print_r($users);
        
        // Organiser les données par utilisateur
        $employee_status = [];
        foreach ($users as $row) {
            $user_id = $row['user_id'];
            
            if (!isset($employee_status[$user_id])) {
                $employee_status[$user_id] = [
                    'nom' => $row['user_name'],
                    'poste' => ucfirst($row['role']), // Admin ou Technicien
                    'statut' => 'disponible',
                    'reparations' => []
                ];
            }
            
            if ($row['reparation_id']) {
                $employee_status[$user_id]['statut'] = 'en cours d\'intervention';
                
                // Calculer le temps passé sur la réparation
                $date_reception = new DateTime($row['date_reception']);
                $now = new DateTime();
                $interval = $date_reception->diff($now);
                
                $temps_passe = '';
                if ($interval->days > 0) {
                    $temps_passe = $interval->days . 'j ';
                }
                $temps_passe .= $interval->h . 'h ' . $interval->i . 'm';
                
                $employee_status[$user_id]['reparations'][] = [
                    'id' => $row['reparation_id'],
                    'model' => $row['model'] ?: 'N/A',
                    'probleme' => $row['probleme'] ?: 'N/A',
                    'temps_passe' => $temps_passe,
                    'client' => $row['client_nom'] . ' ' . $row['client_prenom']
                ];
            }
        }
        
        return $employee_status;
        
    } catch (PDOException $e) {
        echo "Erreur lors de la récupération du statut des employés: " . $e->getMessage() . "\n";
        return [];
    }
}

echo "Test de get_employee_status()...\n";
$status = get_employee_status();
echo "\nRésultat final:\n";
print_r($status);
?>
