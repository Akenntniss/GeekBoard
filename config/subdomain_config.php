<?php
/**
 * Configuration des sous-domaines pour GeekBoard
 * Ce fichier gère la détection automatique du magasin basé sur le sous-domaine
 */

// Fonction pour détecter le magasin basé sur le sous-domaine
function detectShopFromSubdomain() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    try {
        // SYSTÈME DYNAMIQUE - Lecture depuis la base de données
        require_once __DIR__ . '/database.php';
        $pdo_general = getMainDBConnection();
        
        // Récupérer tous les magasins actifs depuis la base
        $stmt = $pdo_general->query("SELECT id, subdomain FROM shops WHERE active = 1");
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Construire le mapping dynamiquement pour les deux domaines
        $subdomain_mapping = [];
        foreach ($shops as $shop) {
            // NE JAMAIS mapper mdgeek.top ou servo.tools vers un magasin - c'est réservé aux landing pages
            if (!empty($shop['subdomain']) && $shop['subdomain'] !== '') {
                // Support pour les deux domaines principaux
                $mdgeek_domain = $shop['subdomain'] . '.mdgeek.top';
                $servo_domain = $shop['subdomain'] . '.servo.tools';
                
                $subdomain_mapping[$mdgeek_domain] = (int)$shop['id'];
                $subdomain_mapping[$servo_domain] = (int)$shop['id'];
                error_log("SUBDOMAIN_DYNAMIC: Mapping {$mdgeek_domain} => shop_id={$shop['id']}"); // Désactivé pour performance
                error_log("SUBDOMAIN_DYNAMIC: Mapping {$servo_domain} => shop_id={$shop['id']}"); // Désactivé pour performance
            } else {
                // Si un magasin a un subdomain vide, on ignore (domaines principaux réservés aux landing pages)
                error_log("SUBDOMAIN_DYNAMIC: Magasin avec subdomain vide ignoré (ID: {$shop['id']}) - domaines principaux réservés aux landing pages"); // Désactivé pour performance
            }
        }
        
        // Ajouter les alias spéciaux
        $subdomain_mapping['cannes.mdgeek.top'] = 4; // Alias pour cannesphones
        
        // Vérifier si l'hôte correspond à un sous-domaine
        if (isset($subdomain_mapping[$host])) {
            error_log("SUBDOMAIN_DYNAMIC: {$host} => shop_id={$subdomain_mapping[$host]}"); // Désactivé pour performance
            return $subdomain_mapping[$host];
        }
        
        error_log("SUBDOMAIN_DYNAMIC: Aucun magasin trouvé pour {$host}"); // Désactivé pour performance
        error_log("SUBDOMAIN_DYNAMIC: Mappings disponibles: " . print_r(array_keys($subdomain_mapping), true)); // Désactivé pour performance
        return null;
        
    } catch (Exception $e) {
        error_log("SUBDOMAIN_DYNAMIC: Erreur - " . $e->getMessage()); // Désactivé pour performance
        return null;
    }
}

// Détecter automatiquement le magasin si pas encore défini en session
// mdgeek.top n'est jamais mappé vers un magasin (réservé à la landing page)
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    
    if ($detected_shop_id) {
        // Vérifier que le magasin existe et est actif
        try {
            require_once __DIR__ . '/database.php';
            $pdo_general = getMainDBConnection();
            $stmt = $pdo_general->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
            $stmt->execute([$detected_shop_id]);
            $shop = $stmt->fetch();
            
            if ($shop) {
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
                error_log("Shop ID automatiquement détecté depuis sous-domaine: " . $shop['id'] . " (" . $shop['name'] . ")"); // Désactivé pour performance
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la détection automatique du magasin: " . $e->getMessage()); // Désactivé pour performance
        }
    }
}
?> 