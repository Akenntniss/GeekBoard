<?php
/**
 * Gestionnaire de Layouts d'Étiquettes
 * Gère la sélection, le chargement et la prévisualisation des layouts
 */

class LabelManager {
    
    /**
     * Liste complète des layouts disponibles
     * @return array
     */
    public static function getAvailableLayouts() {
        return [
            '4x6_moderne' => [
                'name' => 'Moderne',
                'format' => '4x6"',
                'type' => 'Thermique',
                'description' => 'Design minimaliste et moderne (Noir & Blanc)',
                'file' => 'pages/labels/layouts/4x6_moderne.php',
                'thumbnail' => 'assets/images/layouts/4x6_moderne.png'
            ],
            '4x6_business' => [
                'name' => 'Business',
                'format' => '4x6"',
                'type' => 'Thermique',
                'description' => 'Design professionnel et structuré (Noir & Blanc)',
                'file' => 'pages/labels/layouts/4x6_business.php',
                'thumbnail' => 'assets/images/layouts/4x6_business.png'
            ],
            '4x6_startup' => [
                'name' => 'Startup',
                'format' => '4x6"',
                'type' => 'Thermique',
                'description' => 'Design dynamique et créatif (Noir & Blanc)',
                'file' => 'pages/labels/layouts/4x6_startup.php',
                'thumbnail' => 'assets/images/layouts/4x6_startup.png'
            ],
            '4x6_professional' => [
                'name' => 'Professional',
                'format' => '4x6"',
                'type' => 'Thermique',
                'description' => 'Design classique et élégant (Noir & Blanc)',
                'file' => 'pages/labels/layouts/4x6_professional.php',
                'thumbnail' => 'assets/images/layouts/4x6_professional.png'
            ],
            'a4_moderne' => [
                'name' => 'Moderne',
                'format' => 'A4',
                'type' => 'Couleur',
                'description' => 'Design minimaliste avec couleurs vives',
                'file' => 'pages/labels/layouts/a4_moderne.php',
                'thumbnail' => 'assets/images/layouts/a4_moderne.png'
            ],
            'a4_business' => [
                'name' => 'Business',
                'format' => 'A4',
                'type' => 'Couleur',
                'description' => 'Design professionnel avec touches de couleur',
                'file' => 'pages/labels/layouts/a4_business.php',
                'thumbnail' => 'assets/images/layouts/a4_business.png'
            ],
            'a4_startup' => [
                'name' => 'Startup',
                'format' => 'A4',
                'type' => 'Couleur',
                'description' => 'Design dynamique et coloré',
                'file' => 'pages/labels/layouts/a4_startup.php',
                'thumbnail' => 'assets/images/layouts/a4_startup.png'
            ],
            'a4_professional' => [
                'name' => 'Professional',
                'format' => 'A4',
                'type' => 'Couleur',
                'description' => 'Design classique avec élégance colorée',
                'file' => 'pages/labels/layouts/a4_professional.php',
                'thumbnail' => 'assets/images/layouts/a4_professional.png'
            ],
            'a4_split' => [
                'name' => 'Split (Client/Atelier)',
                'format' => 'A4',
                'type' => 'Couleur',
                'description' => 'Document à découper : 75% Client + 25% Atelier',
                'file' => 'pages/labels/layouts/a4_split.php',
                'thumbnail' => 'assets/images/layouts/a4_split.png'
            ],
            'mini_qr_only' => [
                'name' => 'Mini QR',
                'format' => '2x2"',
                'type' => 'Thermique',
                'description' => 'QR code uniquement (Noir & Blanc)',
                'file' => 'pages/labels/layouts/mini_qr_only.php',
                'thumbnail' => 'assets/images/layouts/mini_qr_only.png'
            ],
            'mini_qr_number' => [
                'name' => 'Mini QR + N°',
                'format' => '2x3"',
                'type' => 'Thermique',
                'description' => 'QR code + Numéro de réparation (Noir & Blanc)',
                'file' => 'pages/labels/layouts/mini_qr_number.php',
                'thumbnail' => 'assets/images/layouts/mini_qr_number.png'
            ]
        ];
    }
    
    /**
     * Récupère le layout sélectionné depuis les paramètres
     * @param PDO $pdo
     * @return string
     */
    public static function getSelectedLayout($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'label_layout_default' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetchColumn();
            
            // Layout par défaut si aucun n'est défini
            return $result ? $result : '4x6_moderne';
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du layout: " . $e->getMessage());
            return '4x6_moderne';
        }
    }
    
    /**
     * Définit le layout par défaut
     * @param PDO $pdo
     * @param string $layoutId
     * @return bool
     */
    public static function setSelectedLayout($pdo, $layoutId) {
        try {
            // Vérifier que le layout existe
            $layouts = self::getAvailableLayouts();
            if (!isset($layouts[$layoutId])) {
                return false;
            }
            
            // Vérifier si le paramètre existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM parametres WHERE cle = 'label_layout_default'");
            $stmt->execute();
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE parametres SET valeur = ? WHERE cle = 'label_layout_default'");
                $stmt->execute([$layoutId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO parametres (cle, valeur, description) VALUES ('label_layout_default', ?, 'Layout d''étiquette par défaut')");
                $stmt->execute([$layoutId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la sauvegarde du layout: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charge un layout spécifique
     * @param string $layoutId
     * @param array $reparation Données de la réparation
     * @return string HTML du layout
     */
    public static function loadLayout($layoutId, $reparation) {
        $layouts = self::getAvailableLayouts();
        
        if (!isset($layouts[$layoutId])) {
            throw new Exception("Layout non trouvé: " . $layoutId);
        }
        
        $layoutFile = $layouts[$layoutId]['file'];
        $fullPath = dirname(__DIR__) . '/' . $layoutFile;
        
        if (!file_exists($fullPath)) {
            throw new Exception("Fichier de layout introuvable: " . $fullPath);
        }
        
        // Charger le layout avec les données
        ob_start();
        include $fullPath;
        return ob_get_clean();
    }
    
    /**
     * Récupère les informations d'un layout
     * @param string $layoutId
     * @return array|null
     */
    public static function getLayoutInfo($layoutId) {
        $layouts = self::getAvailableLayouts();
        return isset($layouts[$layoutId]) ? $layouts[$layoutId] : null;
    }
    
    /**
     * Groupe les layouts par type
     * @return array
     */
    public static function getLayoutsByType() {
        $layouts = self::getAvailableLayouts();
        $grouped = [
            'Thermique 4x6"' => [],
            'A4 Couleur' => [],
            'Mini Étiquettes' => []
        ];
        
        foreach ($layouts as $id => $layout) {
            if (strpos($id, '4x6_') === 0) {
                $grouped['Thermique 4x6"'][$id] = $layout;
            } elseif (strpos($id, 'a4_') === 0) {
                $grouped['A4 Couleur'][$id] = $layout;
            } elseif (strpos($id, 'mini_') === 0) {
                $grouped['Mini Étiquettes'][$id] = $layout;
            }
        }
        
        return $grouped;
    }
}

