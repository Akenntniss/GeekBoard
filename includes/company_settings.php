<?php
/**
 * Fonctions pour gérer les paramètres d'entreprise
 */

/**
 * Récupérer les paramètres d'entreprise pour un magasin
 * @param int $shop_id ID du magasin
 * @return array Paramètres d'entreprise
 */
function getCompanySettings($shop_id = null) {
    try {
        // Utiliser le shop_id de la session si non fourni
        if ($shop_id === null) {
            $shop_id = $_SESSION['shop_id'] ?? null;
        }
        
        if (!$shop_id) {
            throw new Exception("Shop ID non défini");
        }
        
        // Récupérer la connexion à la base de données du magasin
        $pdo = getShopDBConnectionById($shop_id);
        
        if (!$pdo) {
            throw new Exception("Impossible de se connecter à la base de données du magasin");
        }
        
        // Initialiser avec les valeurs par défaut
        $company_settings = getDefaultCompanySettings();
        
        // Récupérer les paramètres depuis la table 'parametres' (structure clé/valeur)
        try {
            $stmt = $pdo->query("SELECT cle, valeur FROM parametres");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir en tableau associatif et mettre à jour les valeurs
            foreach ($rows as $row) {
                $key = $row['cle'];
                $value = $row['valeur'];
                
                // Mapper les clés de la base vers les clés attendues
                if (!empty($value)) {
                    $company_settings[$key] = $value;
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la lecture de la table parametres: " . $e->getMessage());
        }
        
        return $company_settings;
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des paramètres d'entreprise: " . $e->getMessage());
        return getDefaultCompanySettings();
    }
}

/**
 * Récupérer les paramètres par défaut d'entreprise
 * @return array Paramètres par défaut
 */
function getDefaultCompanySettings() {
    return [
        'company_name' => 'MKMKMK',
        'company_phone' => '04 93 68 66 30',
        'company_number' => '',
        'company_email' => 'contact@mkmkmk.servo.tools',
        'company_address' => '',
        'company_hours' => 'Lun-Ven: 9h-18h\nSam: 9h-12h\nDim: Fermé',
        'company_logo' => ''
    ];
}

/**
 * Récupérer les variables SMS avec les vraies valeurs de l'entreprise
 * @param int $shop_id ID du magasin
 * @param array $repair_data Données de la réparation (optionnel)
 * @return array Variables SMS avec leurs valeurs
 */
function getSmsVariables($shop_id = null, $repair_data = []) {
    $company_settings = getCompanySettings($shop_id);
    
    // Générer les URLs dynamiques
    $host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
    $repair_id = $repair_data['id'] ?? '';
    
    $variables = [
        // Variables d'entreprise
        '[COMPANY_NAME]' => $company_settings['company_name'],
        '[COMPANY_PHONE]' => $company_settings['company_phone'],
        '[COMPANY_NUMBER]' => $company_settings['company_number'],
        '[COMPANY_EMAIL]' => $company_settings['company_email'],
        '[COMPANY_ADDRESS]' => $company_settings['company_address'],
        '[COMPANY_HOURS]' => $company_settings['company_hours'],
        
        // Variables de réparation
        '[CLIENT_NOM]' => $repair_data['client_nom'] ?? '',
        '[CLIENT_PRENOM]' => $repair_data['client_prenom'] ?? '',
        '[CLIENT_TELEPHONE]' => $repair_data['client_telephone'] ?? '',
        '[REPARATION_ID]' => $repair_id,
        '[APPAREIL_TYPE]' => $repair_data['type_appareil'] ?? '',
        '[APPAREIL_MARQUE]' => $repair_data['marque'] ?? '',
        '[APPAREIL_MODELE]' => $repair_data['modele'] ?? '',
        '[PRIX]' => !empty($repair_data['prix_reparation']) ? number_format($repair_data['prix_reparation'], 2, ',', ' ') . ' €' : '',
        
        // Variables de dates
        '[DATE_RECEPTION]' => !empty($repair_data['date_reception']) ? date('d/m/Y', strtotime($repair_data['date_reception'])) : '',
        '[DATE_FIN_PREVUE]' => !empty($repair_data['date_fin_prevue']) ? date('d/m/Y', strtotime($repair_data['date_fin_prevue'])) : '',
        
        // Variables d'URLs
        '[URL_SUIVI]' => 'https://' . $host . '/suivi.php?id=' . $repair_id,
        '[URL_DEVIS]' => 'https://' . $host . '/devis.php?id=' . $repair_id,
        '[LIEN_SUIVI]' => 'https://' . $host . '/suivi.php?id=' . $repair_id,
        '[LIEN]' => 'https://' . $host . '/suivi.php?id=' . $repair_id,
    ];
    
    return $variables;
}

/**
 * Remplacer les variables dans un template SMS
 * @param string $template Template SMS
 * @param int $shop_id ID du magasin
 * @param array $repair_data Données de la réparation
 * @return string Template avec variables remplacées
 */
function replaceSmsVariables($template, $shop_id = null, $repair_data = []) {
    $variables = getSmsVariables($shop_id, $repair_data);
    
    return str_replace(array_keys($variables), array_values($variables), $template);
}
?>

