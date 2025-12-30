<?php
/**
 * API de prévisualisation des étiquettes
 * Génère un aperçu de l'étiquette avec le layout sélectionné
 */

// Configuration des en-têtes pour AJAX
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/label_manager.php';

// Initialiser la session magasin si nécessaire
initializeShopSession();

try {
    // Vérifier les paramètres requis
    if (!isset($_GET['id']) || !isset($_GET['layout'])) {
        throw new Exception('Paramètres manquants');
    }
    
    $reparation_id = (int)$_GET['id'];
    $layout_id = cleanInput($_GET['layout']);
    
    // Récupérer la connexion à la base de données
    $pdo = getShopDBConnection();
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Récupérer les informations de la réparation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }
    
    // Vérifier que le layout existe
    $availableLayouts = LabelManager::getAvailableLayouts();
    if (!isset($availableLayouts[$layout_id])) {
        throw new Exception('Layout non trouvé');
    }
    
    // Formatage de la date
    $date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
    
    // Générer l'aperçu avec le layout sélectionné
    echo '<div class="preview-wrapper" style="transform: scale(0.7); transform-origin: center; border: 2px dashed #ddd; border-radius: 8px; background: white; display: inline-block;">';
    
    // Charger le layout
    try {
        echo LabelManager::loadLayout($layout_id, $reparation);
    } catch (Exception $e) {
        // Fallback sur le layout par défaut en cas d'erreur
        echo LabelManager::loadLayout('4x6_moderne', $reparation);
    }
    
    echo '</div>';
    
    // Ajouter un script pour initialiser les QR codes si nécessaire
    echo '<script>
    // Attendre que le DOM soit prêt
    setTimeout(function() {
        // Réinitialiser les QR codes dans l\'aperçu
        const qrElements = document.querySelectorAll("#labelPreview [id*=\'qrcode\']");
        qrElements.forEach(function(element) {
            if (element.innerHTML === "" || element.innerHTML.indexOf("img") === -1) {
                // Générer le QR code
                try {
                    new QRCode(element, {
                        text: window.location.origin + "/index.php?page=statut_rapide&id=' . $reparation_id . '",
                        width: element.id.includes("moderne") ? 90 : 85,
                        height: element.id.includes("moderne") ? 90 : 85,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch (error) {
                    console.warn("Erreur génération QR code:", error);
                    element.innerHTML = "<div style=\"width: 85px; height: 85px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 10px; text-align: center;\">QR Code</div>";
                }
            }
        });
    }, 100);
    </script>';
    
} catch (Exception $e) {
    // Retourner une erreur formatée
    echo '<div class="preview-error text-center p-4">';
    echo '<i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>';
    echo '<p class="text-muted">Erreur lors de la génération de l\'aperçu</p>';
    echo '<small class="text-danger">' . htmlspecialchars($e->getMessage()) . '</small>';
    echo '</div>';
}
?>