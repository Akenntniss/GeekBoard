<?php
/**
 * Middleware de vérification de la période d'essai
 * À inclure dans les pages de connexion des boutiques
 */

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

function checkTrialStatus($shop_id) {
    try {
        // Connexion à la base principale
        $pdo = getMainDBConnection();
        if (!$pdo) {
            throw new Exception('Impossible de se connecter à la base principale');
        }
        
        // Vérifier le statut du shop
        $stmt = $pdo->prepare("
            SELECT 
                s.id,
                s.active,
                s.subscription_status,
                s.trial_ends_at,
                s.subdomain,
                DATEDIFF(s.trial_ends_at, NOW()) as days_remaining
            FROM shops s
            WHERE s.id = ?
        ");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shop) {
            return ['status' => 'error', 'message' => 'Boutique introuvable'];
        }
        
        // Si le shop n'est pas actif
        if ($shop['active'] != 1) {
            return ['status' => 'inactive', 'shop' => $shop];
        }
        
        // Si en période d'essai
        if ($shop['subscription_status'] == 'trial') {
            if ($shop['days_remaining'] < 0) {
                // Essai expiré - désactiver le shop
                $stmt = $pdo->prepare("
                    UPDATE shops 
                    SET active = 0, subscription_status = 'expired'
                    WHERE id = ?
                ");
                $stmt->execute([$shop_id]);
                
                return ['status' => 'expired', 'shop' => $shop];
            } else {
                // Essai encore valide
                return ['status' => 'trial_active', 'shop' => $shop, 'days_remaining' => $shop['days_remaining']];
            }
        }
        
        // Si abonnement actif
        if ($shop['subscription_status'] == 'active') {
            return ['status' => 'subscribed', 'shop' => $shop];
        }
        
        // Statut non reconnu
        return ['status' => 'unknown', 'shop' => $shop];
        
    } catch (Exception $e) {
        error_log("Erreur vérification essai : " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Rediriger vers la page d'abonnement si nécessaire
 */
function handleTrialExpired($shop_id, $subdomain = null) {
    if ($subdomain) {
        // Redirection vers la page d'abonnement avec le shop_id
        $redirect_url = "https://mdgeek.top/subscription_required.php?shop_id=" . $shop_id;
        header("Location: $redirect_url");
        exit;
    } else {
        // Affichage d'un message d'erreur
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Essai expiré - SERVO</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🕒 Essai gratuit expiré</h1>
        <p>Votre période d'essai de 30 jours est terminée.</p>
        <p>Pour continuer à utiliser SERVO, veuillez choisir un abonnement.</p>
        <a href='https://mdgeek.top/subscription_required.php?shop_id=$shop_id' class='btn'>
            Choisir un abonnement
        </a>
    </div>
</body>
</html>";
        exit;
    }
}

/**
 * Afficher un avertissement si l'essai expire bientôt
 */
function showTrialWarning($days_remaining) {
    if ($days_remaining <= 7 && $days_remaining >= 0) {
        $message = "";
        $class = "";
        
        if ($days_remaining <= 1) {
            $message = $days_remaining == 0 ? 
                "⚠️ Votre essai se termine aujourd'hui !" : 
                "⚠️ Votre essai se termine demain !";
            $class = "alert-danger";
        } elseif ($days_remaining <= 3) {
            $message = "⚠️ Votre essai se termine dans $days_remaining jours";
            $class = "alert-warning";
        } else {
            $message = "ℹ️ Votre essai se termine dans $days_remaining jours";
            $class = "alert-info";
        }
        
        echo "<div class='alert $class' style='margin: 10px; padding: 15px; border-radius: 5px; text-align: center;'>
                $message
                <a href='https://mdgeek.top/subscription_required.php' style='margin-left: 15px; color: #007bff; text-decoration: underline;'>
                    Choisir un abonnement
                </a>
              </div>";
    }
}

// Usage dans les pages de boutique :
/*
// En début de page (après session_start())
$shop_id = $_SESSION['shop_id'] ?? null;
if ($shop_id) {
    $trial_status = checkTrialStatus($shop_id);
    
    switch ($trial_status['status']) {
        case 'expired':
        case 'inactive':
            handleTrialExpired($shop_id, $trial_status['shop']['subdomain'] ?? null);
            break;
            
        case 'trial_active':
            // Afficher un avertissement si proche de l'expiration
            showTrialWarning($trial_status['days_remaining']);
            break;
            
        case 'subscribed':
            // Tout va bien, continuer normalement
            break;
            
        case 'error':
        case 'unknown':
            // Logger l'erreur mais permettre l'accès (fail-open)
            error_log("Erreur vérification essai shop $shop_id: " . $trial_status['message']);
            break;
    }
}
*/
?>
