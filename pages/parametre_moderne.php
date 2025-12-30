<?php
// ⭐ TRAITEMENT DES REDIRECTIONS STRIPE - DOIT ÊTRE EN PREMIER (avant tout output HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vider tout output buffer existant pour permettre la redirection
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if ($_POST['action'] === 'checkout' && isset($_SESSION['shop_id'])) {
        require_once __DIR__ . '/../classes/StripeManager.php';
        $stripeManager = new StripeManager();
        
        $plan_id = $_POST['plan_id'] ?? 0;
        
        if ($plan_id) {
            $session = $stripeManager->createCheckoutSession($plan_id, $_SESSION['shop_id'], $_SESSION['user_email'] ?? null);
            
            if ($session && isset($session->url)) {
                header("Location: " . $session->url);
                exit;
            }
        }
    }
    
    if ($_POST['action'] === 'portal' && isset($_SESSION['shop_id'])) {
        require_once __DIR__ . '/../classes/StripeManager.php';
        $stripeManager = new StripeManager();
        
        $return_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        $session = $stripeManager->createPortalSession($_SESSION['shop_id'], $return_url);
        
        if ($session && isset($session->url)) {
            header("Location: " . $session->url);
            exit;
        }
    }
    // Si échec des redirections, relancer le buffer pour continuer normalement
    ob_start();
}

include_once 'includes/night-mode-system.php';

// Définir la page actuelle pour le menu
$current_page = 'parametre_moderne';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo '<meta http-equiv="refresh" content="0;url=index.php">';
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
    exit;
}

// Charger le gestionnaire de layouts pour les étiquettes
require_once __DIR__ . '/../includes/label_manager.php';

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];

try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations utilisateur: " . $e->getMessage(), "danger");
}

// Variable pour stocker si un formulaire a été soumis avec succès
$form_submitted = false;

// Traitement du formulaire de mise à jour des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Mise à jour du profil
        $nom = cleanInput($_POST['nom']);
        $prenom = cleanInput($_POST['prenom']);
        $email = cleanInput($_POST['email']);
        $telephone = cleanInput($_POST['telephone']);
        
        try {
            $stmt = $shop_pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?");
            $result = $stmt->execute([$nom, $prenom, $email, $telephone, $user_id]);
            
            if ($result) {
                set_message("Votre profil a été mis à jour avec succès.", "success");
                $form_submitted = true;
            } else {
                set_message("Erreur lors de la mise à jour du profil.", "danger");
            }
        } catch (PDOException $e) {
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_password'])) {
        // Mise à jour du mot de passe
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            set_message("Les nouveaux mots de passe ne correspondent pas.", "danger");
        } else {
            try {
                $stmt = $shop_pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user_data['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $shop_pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $result = $stmt->execute([$hashed_password, $user_id]);
                    
                    if ($result) {
                        set_message("Votre mot de passe a été mis à jour avec succès.", "success");
                        $form_submitted = true;
                    } else {
                        set_message("Erreur lors de la mise à jour du mot de passe.", "danger");
                    }
                } else {
                    set_message("Le mot de passe actuel est incorrect.", "danger");
                }
            } catch (PDOException $e) {
                set_message("Erreur de base de données: " . $e->getMessage(), "danger");
            }
        }
    } elseif (isset($_POST['update_preferences'])) {
        // Mise à jour des préférences
        $theme = cleanInput($_POST['theme']);
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        $elements_per_page = (int)$_POST['elements_per_page'];
        $timezone_offset = (int)$_POST['timezone_offset'];
        
        try {
            // Vérifier si la table preferences existe
            $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'preferences'");
            $stmt->execute();
            $table_exists = $stmt->rowCount() > 0;
            
            if (!$table_exists) {
                set_message("La table des préférences n'existe pas. Veuillez contacter l'administrateur pour créer les tables manquantes.", "warning");
            } else {
                $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM preferences WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    $stmt = $shop_pdo->prepare("UPDATE preferences SET theme = ?, notifications = ?, elements_per_page = ?, timezone_offset = ? WHERE user_id = ?");
                    $result = $stmt->execute([$theme, $notifications, $elements_per_page, $timezone_offset, $user_id]);
                } else {
                    $stmt = $shop_pdo->prepare("INSERT INTO preferences (user_id, theme, notifications, elements_per_page, timezone_offset) VALUES (?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$user_id, $theme, $notifications, $elements_per_page, $timezone_offset]);
                }
                
                if ($result) {
                    $_SESSION['user_preferences'] = [
                        'theme' => $theme,
                        'notifications' => $notifications,
                        'elements_per_page' => $elements_per_page,
                        'timezone_offset' => $timezone_offset
                    ];
                    
                    set_message("Vos préférences ont été mises à jour avec succès.", "success");
                    $form_submitted = true;
                } else {
                    set_message("Erreur lors de la mise à jour des préférences.", "danger");
                }
            }
        } catch (PDOException $e) {
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_relance_devis'])) {
        // Mise à jour de la configuration des relances automatiques
        $est_active = isset($_POST['relance_active']) ? 1 : 0;
        $relances_horaires = [];
        
        // Récupérer les horaires depuis le formulaire
        if (isset($_POST['relance_horaires']) && is_array($_POST['relance_horaires'])) {
            foreach ($_POST['relance_horaires'] as $horaire) {
                $horaire = cleanInput($horaire);
                if (!empty($horaire) && preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $horaire)) {
                    $relances_horaires[] = $horaire;
                }
            }
        }
        
        // S'assurer qu'il y a au moins un horaire par défaut
        if (empty($relances_horaires)) {
            $relances_horaires = ['09:00'];
        }
        
        try {
            // Vérifier si la table relance_automatique_config existe
            $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'relance_automatique_config'");
            $stmt->execute();
            $table_exists = $stmt->rowCount() > 0;
            
            if (!$table_exists) {
                set_message("La table de configuration des relances n'existe pas. Veuillez contacter l'administrateur pour créer les tables manquantes.", "warning");
            } else {
                // Vérifier si la configuration existe déjà
                $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM relance_automatique_config WHERE shop_id = ?");
                $stmt->execute([$_SESSION['shop_id']]);
                $exists = $stmt->fetchColumn();
                
                $relances_json = json_encode($relances_horaires);
                
                if ($exists) {
                    $stmt = $shop_pdo->prepare("UPDATE relance_automatique_config SET est_active = ?, relances_horaires = ? WHERE shop_id = ?");
                    $result = $stmt->execute([$est_active, $relances_json, $_SESSION['shop_id']]);
                } else {
                    $stmt = $shop_pdo->prepare("INSERT INTO relance_automatique_config (shop_id, est_active, relances_horaires) VALUES (?, ?, ?)");
                    $result = $stmt->execute([$_SESSION['shop_id'], $est_active, $relances_json]);
                }
                
                if ($result) {
                    set_message("Configuration des relances automatiques mise à jour avec succès.", "success");
                    $form_submitted = true;
                } else {
                    set_message("Erreur lors de la mise à jour de la configuration des relances.", "danger");
                }
            }
        } catch (PDOException $e) {
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_company_settings'])) {
        // Mise à jour des paramètres d'entreprise dans la table 'parametres' (même table que SMS)
        $company_name = cleanInput($_POST['company_name']);
        $company_phone = cleanInput($_POST['company_phone']);
        $company_number = cleanInput($_POST['company_number'] ?? '');
        $company_email = cleanInput($_POST['company_email']);
        $company_address = cleanInput($_POST['company_address']);
        $company_hours = cleanInput($_POST['company_hours'] ?? '');
        
        $company_params = [
            'company_name' => $company_name,
            'company_phone' => $company_phone,
            'company_number' => $company_number,
            'company_email' => $company_email,
            'company_address' => $company_address,
            'company_hours' => $company_hours
        ];
        
        try {
            $shop_pdo->beginTransaction();
            
            foreach ($company_params as $cle => $valeur) {
                // Vérifier si le paramètre existe déjà
                $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM parametres WHERE cle = ?");
                $stmt->execute([$cle]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    $stmt = $shop_pdo->prepare("UPDATE parametres SET valeur = ? WHERE cle = ?");
                    $stmt->execute([$valeur, $cle]);
                } else {
                    $descriptions = [
                        'company_name' => 'Nom de l\'entreprise',
                        'company_phone' => 'Numéro de téléphone de l\'entreprise',
                        'company_number' => 'Numéro SIRET de l\'entreprise',
                        'company_email' => 'Adresse email de l\'entreprise',
                        'company_address' => 'Adresse de l\'entreprise',
                        'company_hours' => 'Horaires d\'ouverture'
                    ];
                    
                    $stmt = $shop_pdo->prepare("INSERT INTO parametres (cle, valeur, description) VALUES (?, ?, ?)");
                    $stmt->execute([$cle, $valeur, $descriptions[$cle] ?? '']);
                }
            }
            
            $shop_pdo->commit();
            set_message("Paramètres d'entreprise mis à jour avec succès.", "success");
            $form_submitted = true;
        } catch (PDOException $e) {
            $shop_pdo->rollback();
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_label_layout'])) {
        // Mise à jour du layout d'étiquette par défaut
        $layout_id = cleanInput($_POST['label_layout']);
        
        try {
            $success = LabelManager::setSelectedLayout($shop_pdo, $layout_id);
            
            if ($success) {
                $availableLayouts = LabelManager::getAvailableLayouts();
                $layoutName = isset($availableLayouts[$layout_id]) ? $availableLayouts[$layout_id]['name'] : $layout_id;
                set_message("Layout d'étiquette '{$layoutName}' défini par défaut avec succès.", "success");
                $form_submitted = true;
            } else {
                error_log("Erreur lors de la sauvegarde du layout d'étiquette.");
            }
        } catch (Exception $e) {
            set_message("Erreur: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_sms_settings'])) {
        // Mise à jour des paramètres SMS
        try {
            require_once __DIR__ . '/../includes/sms_billing_functions.php';
            
            $mainPdo = getMainDBConnection();
            $shopId = $_SESSION['shop_id'];
            
            $hard_cap_enabled = isset($_POST['hard_cap_enabled']) ? 1 : 0;
            $hard_cap_amount = floatval($_POST['hard_cap_amount'] ?? 20);
            $alerts_enabled = isset($_POST['alerts_enabled']) ? 1 : 0;
            $alert_email = cleanInput($_POST['alert_email'] ?? '');
            
            $stmt = $mainPdo->prepare("
                INSERT INTO sms_shop_settings (shop_id, hard_cap_enabled, hard_cap_amount, alerts_enabled, alert_email)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    hard_cap_enabled = VALUES(hard_cap_enabled),
                    hard_cap_amount = VALUES(hard_cap_amount),
                    alerts_enabled = VALUES(alerts_enabled),
                    alert_email = VALUES(alert_email)
            ");
            $stmt->execute([$shopId, $hard_cap_enabled, $hard_cap_amount, $alerts_enabled, $alert_email]);
            
            set_message("Paramètres SMS mis à jour avec succès.", "success");
            $form_submitted = true;
        } catch (Exception $e) {
            set_message("Erreur lors de la mise à jour des paramètres SMS: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'checkout') {
        // Gestion du checkout Stripe (depuis modal Gérer mon plan)
        require_once __DIR__ . '/../classes/StripeManager.php';
        $stripeManager = new StripeManager();
        
        $plan_id = $_POST['plan_id'] ?? 0;
        
        if ($plan_id) {
            $session = $stripeManager->createCheckoutSession($plan_id, $_SESSION['shop_id'], $user['email'] ?? null);
            
            if ($session && isset($session->url)) {
                header("Location: " . $session->url);
                exit;
            } else {
                set_message("Erreur lors de l'initialisation du paiement.", "danger");
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'portal') {
        // Gestion du portail de facturation Stripe (depuis modal Portail facturation)
        require_once __DIR__ . '/../classes/StripeManager.php';
        $stripeManager = new StripeManager();
        
        $return_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        $session = $stripeManager->createPortalSession($_SESSION['shop_id'], $return_url);
        
        if ($session && isset($session->url)) {
            header("Location: " . $session->url);
            exit;
        } else {
            set_message("Impossible d'accéder au portail de facturation. Avez-vous déjà un abonnement actif ?", "danger");
        }
    }
}

// Récupérer les préférences utilisateur
try {
    // Vérifier si la table preferences existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'preferences'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $shop_pdo->prepare("SELECT * FROM preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $preferences = false;
    }
    
    if (!$preferences) {
        $preferences = [
            'theme' => 'light',
            'notifications' => 1,
            'elements_per_page' => 20,
            'timezone_offset' => 0
        ];
    }
} catch (PDOException $e) {
    $preferences = [
        'theme' => 'light',
        'notifications' => 1,
        'elements_per_page' => 20,
        'timezone_offset' => 0
    ];
}

// Récupérer les layouts d'étiquettes disponibles et le layout actuel
try {
    $availableLayouts = LabelManager::getAvailableLayouts();
    $currentLayout = LabelManager::getSelectedLayout($shop_pdo);
} catch (Exception $e) {
    $availableLayouts = [];
    $currentLayout = '4x6_moderne';
    error_log("Erreur lors de la récupération des layouts: " . $e->getMessage());
}

// Récupérer une réparation existante pour l'aperçu des étiquettes
$previewRepairId = null;
try {
    $stmt = $shop_pdo->query("SELECT id FROM reparations ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $previewRepairId = $row['id'];
    }
} catch (Exception $e) {
    error_log("Erreur récupération réparation aperçu: " . $e->getMessage());
}

// Récupérer la configuration des relances automatiques
try {
    // Vérifier si la table relance_automatique_config existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'relance_automatique_config'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $shop_pdo->prepare("SELECT * FROM relance_automatique_config WHERE shop_id = ?");
        $stmt->execute([$_SESSION['shop_id']]);
        $relance_config = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $relance_config = false;
    }
    
    if (!$relance_config) {
        $relance_config = [
            'est_active' => 0,
            'relances_horaires' => json_encode(['09:00'])
        ];
    }
} catch (PDOException $e) {
    $relance_config = [
        'est_active' => 0,
        'relances_horaires' => json_encode(['09:00'])
    ];
}

// Récupérer les paramètres d'entreprise depuis la table 'parametres' (même table que SMS)
$company_settings = [
    'company_name' => '',
    'company_phone' => '',
    'company_number' => '',
    'company_email' => '',
    'company_address' => '',
    'company_hours' => '',
    'company_logo' => ''
];

try {
    $stmt = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone', 'company_number', 'company_email', 'company_address', 'company_hours', 'company_logo')");
    $stmt->execute();
    $params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($company_settings as $key => $default_value) {
        if (isset($params[$key])) {
            $company_settings[$key] = $params[$key];
        }
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des paramètres d'entreprise: " . $e->getMessage());
}

// Vérifier si l'utilisateur est admin
$is_admin_check = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>

<style>
/* FIX NAVBAR - Obligatoire pour affichage correct */
/* Masquer dock mobile sur desktop */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer navbar desktop visible */
    #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 60px !important;
        width: 100% !important;
    }
    /* Surcharger navbar-servo-fix.css */
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Éléments navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Container navbar avec centrage vertical parfait */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.75rem 1rem !important;
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important;
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important;
        margin: 0.125rem 0.25rem !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
    }
}

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
    }
}

/* Variables CSS pour les thèmes */
:root {
    /* Mode jour pro */
    --day-bg-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --day-bg-secondary: rgba(255, 255, 255, 0.95);
    --day-bg-card: rgba(255, 255, 255, 0.9);
    --day-text: #2d3748;
    --day-text-light: #718096;
    --day-border: rgba(226, 232, 240, 0.8);
    --day-shadow: rgba(0, 0, 0, 0.1);
    --day-accent: #667eea;
    --day-success: #48bb78;
    --day-warning: #ed8936;
    --day-danger: #f56565;
    --day-info: #4299e1;
}

[data-theme="dark"] {
    /* Mode nuit futuriste */
    --day-bg-primary: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
    --day-bg-secondary: rgba(15, 15, 35, 0.95);
    --day-bg-card: rgba(26, 26, 46, 0.95);
    --day-text: #e2e8f0;
    --day-text-light: #94a3b8;
    --day-border: rgba(148, 163, 184, 0.2);
    --day-shadow: rgba(0, 0, 0, 0.5);
    --day-accent: #00d4ff;
    --day-success: #00ff88;
    --day-warning: #ffb347;
    --day-danger: #ff6b6b;
    --day-info: #00d4ff;
}

/* Styles de base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--day-bg-primary);
    color: var(--day-text);
    min-height: 100vh;
    overflow-x: hidden;
}

/* Particules d'arrière-plan */
.particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
    opacity: 0.6;
}

.particle {
    position: absolute;
    background: var(--day-accent);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

[data-theme="dark"] .particle {
    background: var(--day-accent);
    box-shadow: 0 0 10px var(--day-accent);
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

/* Dashboard moderne */
.modern-dashboard {
    position: relative;
    z-index: 2;
    padding: 2rem;
    min-height: 100vh;
}

/* En-tête moderne */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-bg-card);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
}

.modern-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--day-text);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.modern-title i {
    color: var(--day-accent);
    font-size: 1.8rem;
}

/* Navigation latérale moderne */
.settings-nav {
    background: var(--day-bg-card);
    border-radius: 20px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    margin-bottom: 2rem;
}

.nav-list {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.nav-item {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--day-text-light);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid transparent;
    font-weight: 500;
}

.nav-item:hover {
    background: var(--day-accent);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.nav-item.active {
    background: var(--day-accent);
    color: white;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.nav-item i {
    font-size: 1.1rem;
}

/* Contenu des sections */
.content-section {
    display: none;
    background: var(--day-bg-card);
    border-radius: 20px;
    padding: 2rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    margin-bottom: 2rem;
}

.content-section.active {
    display: block;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    color: var(--day-accent);
}

/* Formulaires modernes */
.modern-form {
    display: grid;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.9rem;
}

.form-input {
    padding: 1rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-input:focus {
    outline: none;
    border-color: var(--day-accent);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: rgba(255, 255, 255, 0.15);
}

[data-theme="dark"] .form-input {
    background: rgba(255, 255, 255, 0.05);
    border-color: var(--day-border);
    color: var(--day-text);
}

[data-theme="dark"] .form-input:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--day-accent);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
}

.form-select {
    padding: 1rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-select:focus {
    outline: none;
    border-color: var(--day-accent);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

[data-theme="dark"] .form-select {
    background: rgba(255, 255, 255, 0.05);
    border-color: var(--day-border);
    color: var(--day-text);
}

[data-theme="dark"] .form-select:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--day-accent);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
}

[data-theme="dark"] .form-select option {
    background: #1a1a2e;
    color: var(--day-text);
}

/* Boutons modernes */
.modern-btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    background: var(--day-accent);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.modern-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.modern-btn--success {
    background: var(--day-success);
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
}

.modern-btn--success:hover {
    box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
}

.modern-btn--warning {
    background: var(--day-warning);
    box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
}

.modern-btn--warning:hover {
    box-shadow: 0 8px 25px rgba(237, 137, 54, 0.4);
}

.modern-btn--danger {
    background: var(--day-danger);
    box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
}

.modern-btn--danger:hover {
    box-shadow: 0 8px 25px rgba(245, 101, 101, 0.4);
}

/* Cartes d'information */
.info-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    margin-top: 2rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(10px);
}

.info-card h3 {
    color: var(--day-text);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.info-card p {
    color: var(--day-text-light);
    line-height: 1.6;
    margin-bottom: 0.5rem;
}

/* Le thème suit automatiquement les préférences système */

/* Animations */
.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .modern-dashboard {
        padding: 1rem;
    }
    
    .modern-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .modern-title {
        font-size: 1.5rem;
    }
    
    .nav-list {
        flex-direction: column;
    }
}

/* Styles pour les horaires de relance */
.relance-horaires {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.horaire-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.horaire-item input {
    flex: 1;
}

.remove-horaire {
    background: var(--day-danger);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.remove-horaire:hover {
    transform: scale(1.05);
}

.add-horaire {
    background: var(--day-success);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.add-horaire:hover {
    transform: translateY(-2px);
}

/* Checkbox personnalisé */
.custom-checkbox {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.custom-checkbox input[type="checkbox"] {
    appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid var(--day-border);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.custom-checkbox input[type="checkbox"]:checked {
    background: var(--day-accent);
    border-color: var(--day-accent);
}

.custom-checkbox input[type="checkbox"]:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

/* Messages d'alerte */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    backdrop-filter: blur(10px);
}

.alert-success {
    background: rgba(72, 187, 120, 0.1);
    border-color: var(--day-success);
    color: var(--day-success);
}

.alert-danger {
    background: rgba(245, 101, 101, 0.1);
    border-color: var(--day-danger);
    color: var(--day-danger);
}

.alert-warning {
    background: rgba(237, 137, 54, 0.1);
    border-color: var(--day-warning);
    color: var(--day-warning);
}

.alert-info {
    background: rgba(66, 153, 225, 0.1);
    border-color: var(--day-info);
    color: var(--day-info);
}

/* Styles pour la section étiquettes */
.layout-selector-settings {
    margin-bottom: 2rem;
}

.layout-type-section {
    margin-bottom: 2rem;
}

.layout-type-title {
    color: var(--primary);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.layout-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.layout-card-setting {
    border: 2px solid var(--day-border);
    border-radius: var(--radius);
    background: var(--day-bg-card);
    transition: var(--transition);
    overflow: hidden;
    position: relative;
}

.layout-card-setting:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.layout-card-setting.selected {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(118, 9, 183, 0.05) 100%);
}

.layout-card-setting.selected::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary);
}

.layout-card-header {
    padding: 1rem;
}

.layout-card-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    margin: 0;
}

.layout-card-setting input[type="radio"] {
    width: 20px;
    height: 20px;
    accent-color: var(--primary);
    cursor: pointer;
}

.layout-info {
    flex: 1;
}

.layout-name {
    font-weight: 600;
    color: #000;
    margin-bottom: 0.25rem;
}

/* Mode nuit */
[data-theme="dark"] .layout-name {
    color: var(--day-text);
}

[data-theme="dark"] .layout-card-setting {
    background: var(--day-bg-card);
    border-color: var(--day-border);
}

.layout-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
}

.layout-format {
    color: #666;
    font-weight: 500;
}

[data-theme="dark"] .layout-format {
    color: #aaa;
}

.layout-type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid transparent;
}

.layout-type-badge.thermique {
    background: #e3f2fd;
    color: #1976d2;
    border-color: #1976d2;
}

.layout-type-badge.couleur {
    background: #f3e5f5;
    color: #7b1fa2;
    border-color: #7b1fa2;
}

/* Mode nuit pour les badges */
[data-theme="dark"] .layout-type-badge.thermique {
    background: rgba(25, 118, 210, 0.2);
    color: #64b5f6;
    border-color: #64b5f6;
}

[data-theme="dark"] .layout-type-badge.couleur {
    background: rgba(156, 39, 176, 0.2);
    color: #ce93d8;
    border-color: #ce93d8;
}

.layout-description {
    padding: 0 1rem 1rem;
    font-size: 0.875rem;
    color: #666;
    line-height: 1.4;
}

[data-theme="dark"] .layout-description {
    color: #aaa;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.preview-section {
    position: relative;
}

.label-preview-container {
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    max-width: 90vw;
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    z-index: 1000;
    margin-top: 0.5rem;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.preview-header h5 {
    margin: 0;
    font-size: 1rem;
    color: var(--dark);
}

.close-preview {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: var(--transition);
}

.close-preview:hover {
    background: var(--border);
    color: var(--dark);
}

.preview-content {
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.preview-loading {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.preview-error {
    text-align: center;
    padding: 2rem;
    color: var(--danger);
}

@media (max-width: 768px) {
    .layout-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .label-preview-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90vw;
        max-height: 80vh;
    }
}

/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1031 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 70px !important;
    }
}

/* ========================================
   FOND ANIMÉ JOUR/NUIT - OVERRIDE GLOBAL
======================================== */
@keyframes gradientFlowParam {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Jour - Fond animé */
body:not(.night-mode) {
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 400% 400% !important;
    animation: gradientFlowParam 15s ease infinite !important;
    padding-top: 70px !important;
}

/* Mode Nuit - Fond animé */
body.night-mode {
    background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483) !important;
    background-size: 400% 400% !important;
    animation: gradientFlowParam 15s ease infinite !important;
    padding-top: 70px !important;
}

/* ========================================
   MASQUER NAVBAR DESKTOP SUR MOBILE
======================================== */
@media (max-width: 767px) {
    #desktop-navbar,
    nav#desktop-navbar,
    .navbar,
    nav.navbar {
        display: none !important;
        visibility: hidden !important;
    }
    
    body, body:not(.night-mode), body.night-mode {
        padding-top: 0 !important;
    }
    
    .modern-dashboard {
        padding-bottom: 100px !important;
    }
}

/* ========================================
   FIX NAVBAR DESKTOP
======================================== */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
    }
    
    #desktop-navbar, nav#desktop-navbar, .navbar {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 70px !important;
        min-height: 70px !important;
        width: 100% !important;
        overflow: visible !important;
        align-items: center !important;
    }
    
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        overflow: visible !important;
    }
    
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
    }
}

/* ========================================
   MODE NUIT - STYLES EXPLICITES
======================================== */
body.night-mode .modern-dashboard {
    background: transparent !important;
}

body.night-mode .modern-header,
body.night-mode .settings-nav,
body.night-mode .content-section {
    background: rgba(15, 15, 25, 0.95) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode .modern-title,
body.night-mode .section-title,
body.night-mode .form-label,
body.night-mode .nav-item {
    color: #ffffff !important;
}

body.night-mode .form-input,
body.night-mode .form-select {
    background: rgba(15, 23, 42, 0.8) !important;
    border-color: rgba(0, 212, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode .nav-item:not(.active) {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid rgba(0, 212, 255, 0.2) !important;
    color: #a0aec0 !important;
}

body.night-mode .nav-item.active,
body.night-mode .nav-item:hover {
    background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
    color: white !important;
}

/* ========================================
   NAVBAR MODE NUIT
======================================== */
body.night-mode #desktop-navbar,
body.night-mode nav#desktop-navbar,
body.night-mode .navbar {
    background: rgba(15, 15, 25, 0.95) !important;
    border-bottom: 1px solid rgba(0, 212, 255, 0.3) !important;
}
</style>

<!-- Le thème suit automatiquement le système -->

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-cogs"></i>
            Paramètres
        </h1>
    </div>

    <!-- Messages d'alerte -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type'] ?? 'info'); ?> fade-in">
            <?php 
            if (is_array($_SESSION['message'])) {
                echo implode('<br>', array_map('htmlspecialchars', $_SESSION['message']));
            } else {
                echo htmlspecialchars($_SESSION['message']);
            }
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Navigation latérale -->
    <div class="settings-nav fade-in">
        <ul class="nav-list">
            <li class="nav-item active" data-tab="profile">
                <i class="fas fa-user"></i>
                Mon profil
            </li>
            <li class="nav-item" data-tab="security">
                <i class="fas fa-lock"></i>
                Sécurité
            </li>
            <li class="nav-item" data-tab="preferences">
                <i class="fas fa-sliders-h"></i>
                Préférences
            </li>
            
            <!-- Lien vers page notifications -->
            <a href="index.php?page=notification_preferences" style="text-decoration: none; color: inherit;">
                <li class="nav-item">
                    <i class="fas fa-bell"></i>
                    Préférences notification
                </li>
            </a>

            <li class="nav-item" data-tab="relance_devis">
                <i class="fas fa-clock"></i>
                Relance devis
            </li>
            <?php if ($is_admin_check): ?>
            <li class="nav-item" data-tab="system">
                <i class="fas fa-server"></i>
                Système
            </li>
            <?php endif; ?>
            <li class="nav-item" data-tab="etiquettes">
                <i class="fas fa-tags"></i>
                Étiquettes
            </li>
            <li class="nav-item" data-tab="extension">
                <i class="fas fa-puzzle-piece"></i>
                Extension Fournisseur
            </li>

            <li class="nav-item" data-tab="sms_usage">
                <i class="fas fa-sms"></i>
                Consommation SMS
            </li>
            <li class="nav-item" data-tab="facturation">
                <i class="fas fa-credit-card"></i>
                Facturation
            </li>
        </ul>
    </div>

    <!-- Section Mon profil -->
    <div class="content-section active fade-in" id="profile">
        <h2 class="section-title">
            <i class="fas fa-user"></i>
            Mon profil
        </h2>
        
        <form method="POST" class="modern-form">
            <div class="form-group">
                <label class="form-label" for="nom">Nom</label>
                <input type="text" id="nom" name="nom" class="form-input" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" class="form-input" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" class="form-input" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
            </div>
            
            <button type="submit" name="update_profile" class="modern-btn modern-btn--success">
                <i class="fas fa-save"></i>
                Mettre à jour le profil
            </button>
        </form>
    </div>

    <!-- Section Sécurité -->
    <div class="content-section fade-in" id="security">
        <h2 class="section-title">
            <i class="fas fa-lock"></i>
            Sécurité
        </h2>
        
        <form method="POST" class="modern-form">
            <div class="form-group">
                <label class="form-label" for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirmer le nouveau mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
            </div>
            
            <button type="submit" name="update_password" class="modern-btn modern-btn--warning">
                <i class="fas fa-key"></i>
                Changer le mot de passe
            </button>
        </form>
    </div>

    <!-- Section Préférences -->
    <div class="content-section fade-in" id="preferences">
        <h2 class="section-title">
            <i class="fas fa-sliders-h"></i>
            Préférences
        </h2>
        
        <div class="info-card" style="margin-bottom: 1.5rem;">
            <h3><i class="fas fa-info-circle"></i> Thème automatique</h3>
            <p>Le thème de l'interface suit automatiquement les préférences de votre système d'exploitation.</p>
            <p><strong>Mode actuel :</strong> <span id="current-theme-display"></span></p>
        </div>
        
        <form method="POST" class="modern-form">
            <div class="form-group" style="display: none;">
                <label class="form-label" for="theme">Thème (détecté automatiquement)</label>
                <select id="theme" name="theme" class="form-select" disabled>
                    <option value="light" <?php echo ($preferences['theme'] === 'light') ? 'selected' : ''; ?>>Clair</option>
                    <option value="dark" <?php echo ($preferences['theme'] === 'dark') ? 'selected' : ''; ?>>Sombre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="custom-checkbox">
                    <input type="checkbox" name="notifications" <?php echo $preferences['notifications'] ? 'checked' : ''; ?>>
                    <span>Activer les notifications</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="elements_per_page">Éléments par page</label>
                <select id="elements_per_page" name="elements_per_page" class="form-select">
                    <option value="10" <?php echo ($preferences['elements_per_page'] == 10) ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo ($preferences['elements_per_page'] == 20) ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo ($preferences['elements_per_page'] == 50) ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo ($preferences['elements_per_page'] == 100) ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="timezone_offset">Fuseau horaire (heures par rapport à UTC)</label>
                <select id="timezone_offset" name="timezone_offset" class="form-select">
                    <option value="-12" <?php echo ($preferences['timezone_offset'] == -12) ? 'selected' : ''; ?>>UTC-12</option>
                    <option value="-11" <?php echo ($preferences['timezone_offset'] == -11) ? 'selected' : ''; ?>>UTC-11</option>
                    <option value="-10" <?php echo ($preferences['timezone_offset'] == -10) ? 'selected' : ''; ?>>UTC-10</option>
                    <option value="-9" <?php echo ($preferences['timezone_offset'] == -9) ? 'selected' : ''; ?>>UTC-9</option>
                    <option value="-8" <?php echo ($preferences['timezone_offset'] == -8) ? 'selected' : ''; ?>>UTC-8</option>
                    <option value="-7" <?php echo ($preferences['timezone_offset'] == -7) ? 'selected' : ''; ?>>UTC-7</option>
                    <option value="-6" <?php echo ($preferences['timezone_offset'] == -6) ? 'selected' : ''; ?>>UTC-6</option>
                    <option value="-5" <?php echo ($preferences['timezone_offset'] == -5) ? 'selected' : ''; ?>>UTC-5</option>
                    <option value="-4" <?php echo ($preferences['timezone_offset'] == -4) ? 'selected' : ''; ?>>UTC-4</option>
                    <option value="-3" <?php echo ($preferences['timezone_offset'] == -3) ? 'selected' : ''; ?>>UTC-3</option>
                    <option value="-2" <?php echo ($preferences['timezone_offset'] == -2) ? 'selected' : ''; ?>>UTC-2</option>
                    <option value="-1" <?php echo ($preferences['timezone_offset'] == -1) ? 'selected' : ''; ?>>UTC-1</option>
                    <option value="0" <?php echo ($preferences['timezone_offset'] == 0) ? 'selected' : ''; ?>>UTC+0</option>
                    <option value="1" <?php echo ($preferences['timezone_offset'] == 1) ? 'selected' : ''; ?>>UTC+1 (France)</option>
                    <option value="2" <?php echo ($preferences['timezone_offset'] == 2) ? 'selected' : ''; ?>>UTC+2</option>
                    <option value="3" <?php echo ($preferences['timezone_offset'] == 3) ? 'selected' : ''; ?>>UTC+3</option>
                    <option value="4" <?php echo ($preferences['timezone_offset'] == 4) ? 'selected' : ''; ?>>UTC+4</option>
                    <option value="5" <?php echo ($preferences['timezone_offset'] == 5) ? 'selected' : ''; ?>>UTC+5</option>
                    <option value="6" <?php echo ($preferences['timezone_offset'] == 6) ? 'selected' : ''; ?>>UTC+6</option>
                    <option value="7" <?php echo ($preferences['timezone_offset'] == 7) ? 'selected' : ''; ?>>UTC+7</option>
                    <option value="8" <?php echo ($preferences['timezone_offset'] == 8) ? 'selected' : ''; ?>>UTC+8</option>
                    <option value="9" <?php echo ($preferences['timezone_offset'] == 9) ? 'selected' : ''; ?>>UTC+9</option>
                    <option value="10" <?php echo ($preferences['timezone_offset'] == 10) ? 'selected' : ''; ?>>UTC+10</option>
                    <option value="11" <?php echo ($preferences['timezone_offset'] == 11) ? 'selected' : ''; ?>>UTC+11</option>
                    <option value="12" <?php echo ($preferences['timezone_offset'] == 12) ? 'selected' : ''; ?>>UTC+12</option>
                </select>
            </div>
            
            <button type="submit" name="update_preferences" class="modern-btn">
                <i class="fas fa-save"></i>
                Sauvegarder les préférences
            </button>
        </form>
    </div>


    <!-- Section Relance devis -->
    <div class="content-section fade-in" id="relance_devis">
        <h2 class="section-title">
            <i class="fas fa-clock"></i>
            Relance automatique des devis
        </h2>
        
        <form method="POST" class="modern-form">
            <div class="form-group">
                <label class="custom-checkbox">
                    <input type="checkbox" name="relance_active" <?php echo $relance_config['est_active'] ? 'checked' : ''; ?>>
                    <span>Activer les relances automatiques</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label">Horaires de relance</label>
                <div class="relance-horaires" id="relanceHoraires">
                    <?php 
                    $horaires = json_decode($relance_config['relances_horaires'], true);
                    if (empty($horaires)) {
                        $horaires = ['09:00'];
                    }
                    foreach ($horaires as $index => $horaire): 
                    ?>
                    <div class="horaire-item">
                        <input type="time" name="relance_horaires[]" class="form-input" value="<?php echo htmlspecialchars($horaire); ?>" required>
                        <?php if ($index > 0): ?>
                        <button type="button" class="remove-horaire" onclick="removeHoraire(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-horaire" onclick="addHoraire()">
                    <i class="fas fa-plus"></i>
                    Ajouter un horaire
                </button>
            </div>
            
            <button type="submit" name="update_relance_devis" class="modern-btn modern-btn--success">
                <i class="fas fa-save"></i>
                Sauvegarder la configuration
            </button>
        </form>
    </div>

    <!-- Section Système (Admin seulement) -->
    <?php if ($is_admin_check): ?>
    <div class="content-section fade-in" id="system">
        <h2 class="section-title">
            <i class="fas fa-server"></i>
            Paramètres d'entreprise
        </h2>
        
        <form method="POST" class="modern-form">
            <div class="form-group">
                <label class="form-label" for="company_name">Nom de l'entreprise</label>
                <input type="text" id="company_name" name="company_name" class="form-input" value="<?php echo htmlspecialchars($company_settings['company_name']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_phone">Téléphone de l'entreprise</label>
                <input type="tel" id="company_phone" name="company_phone" class="form-input" value="<?php echo htmlspecialchars($company_settings['company_phone']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_number">Numéro SIRET/SIREN</label>
                <input type="text" id="company_number" name="company_number" class="form-input" value="<?php echo htmlspecialchars($company_settings['company_number']); ?>" placeholder="Ex: 12345678901234">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_email">Email de l'entreprise</label>
                <input type="email" id="company_email" name="company_email" class="form-input" value="<?php echo htmlspecialchars($company_settings['company_email']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_address">Adresse de l'entreprise</label>
                <textarea id="company_address" name="company_address" class="form-input" rows="3"><?php echo htmlspecialchars($company_settings['company_address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_hours">Horaires d'ouverture</label>
                <textarea id="company_hours" name="company_hours" class="form-input" rows="4" placeholder="Ex: Lun-Ven: 9h-18h&#10;Sam: 9h-12h&#10;Dim: Fermé"><?php echo htmlspecialchars($company_settings['company_hours']); ?></textarea>
            </div>
            
            <button type="submit" name="update_company_settings" class="modern-btn modern-btn--success">
                <i class="fas fa-save"></i>
                Enregistrer les paramètres d'entreprise
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Section Étiquettes -->
    <div class="content-section fade-in" id="etiquettes">
        <h2 class="section-title">
            <i class="fas fa-tags"></i>
            Modèles d'étiquettes
        </h2>
        
        <p class="section-description">
            Choisissez le modèle d'étiquette par défaut qui sera utilisé lors de l'impression des étiquettes de réparation.
        </p>
        
        <form method="POST" class="modern-form">
            <div class="layout-selector-settings">
                <?php 
                $layoutsByType = [];
                foreach ($availableLayouts as $layoutId => $layoutInfo) {
                    $type = $layoutInfo['type'];
                    if (!isset($layoutsByType[$type])) {
                        $layoutsByType[$type] = [];
                    }
                    $layoutsByType[$type][$layoutId] = $layoutInfo;
                }
                
                foreach ($layoutsByType as $typeName => $layouts): ?>
                <div class="layout-type-section">
                    <h4 class="layout-type-title">
                        <i class="fas fa-<?php echo $typeName === 'Thermique' ? 'print' : 'palette'; ?>"></i>
                        Format <?php echo htmlspecialchars($typeName); ?>
                    </h4>
                    
                    <div class="layout-cards-grid">
                        <?php foreach ($layouts as $layoutId => $layoutInfo): ?>
                        <div class="layout-card-setting <?php echo $currentLayout === $layoutId ? 'selected' : ''; ?>" 
                             data-layout-id="<?php echo htmlspecialchars($layoutId); ?>">
                            <div class="layout-card-header">
                                <input type="radio" 
                                       name="label_layout" 
                                       value="<?php echo htmlspecialchars($layoutId); ?>" 
                                       id="layout_<?php echo htmlspecialchars($layoutId); ?>"
                                       <?php echo $currentLayout === $layoutId ? 'checked' : ''; ?>>
                                <label for="layout_<?php echo htmlspecialchars($layoutId); ?>" class="layout-card-label">
                                    <div class="layout-info">
                                        <div class="layout-name"><?php echo htmlspecialchars($layoutInfo['name']); ?></div>
                                        <div class="layout-meta">
                                            <span class="layout-format"><?php echo htmlspecialchars($layoutInfo['format']); ?></span>
                                            <span class="layout-type-badge <?php echo strtolower($layoutInfo['type']); ?>">
                                                <?php echo htmlspecialchars($layoutInfo['type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="layout-description">
                                <?php echo htmlspecialchars($layoutInfo['description']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_label_layout" class="modern-btn modern-btn--primary">
                    <i class="fas fa-save"></i>
                    Sauvegarder le modèle par défaut
                </button>
                
                <button type="button" id="previewLabelBtn" class="modern-btn modern-btn--outline" data-bs-toggle="modal" data-bs-target="#labelPreviewModal">
                    <i class="fas fa-eye"></i>
                    Aperçu
                </button>
            </div>
        </form>
    </div>

<!-- Modal Aperçu Étiquette -->
<div class="modal fade" id="labelPreviewModal" tabindex="-1" aria-labelledby="labelPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--day-bg-card); border-radius: 20px; max-height: 90vh; overflow: hidden;">
            <div class="modal-header" style="border-bottom: 1px solid var(--day-border); padding: 1.25rem 1.5rem;">
                <h5 class="modal-title" id="labelPreviewModalLabel" style="font-weight: 700; color: var(--day-text);">
                    <i class="fas fa-eye me-2" style="color: var(--day-accent);"></i>
                    Aperçu du modèle sélectionné
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 140px); display: flex; justify-content: center; background: #f0f0f0;">
                <div id="labelPreviewContent" style="display: flex; justify-content: center; align-items: flex-start;">
                    <!-- L'aperçu sera chargé ici -->
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--day-border); padding: 1rem 1.5rem;">
                <button type="button" class="modern-btn modern-btn--outline" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Section Extension Fournisseur -->
    <div class="content-section fade-in" id="extension">
        <h2 class="section-title">
            <i class="fas fa-puzzle-piece"></i>
            Extension Fournisseur
        </h2>
        
        <div class="info-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(118, 9, 183, 0.1) 100%); border-left: 4px solid var(--primary);">
            <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                <div style="background: white; padding: 1rem; border-radius: 12px; box-shadow: var(--shadow-sm);">
                    <img src="<?php echo isset($assets_path) ? $assets_path : 'assets/'; ?>images/logo.png" alt="SERVO Extension" style="width: 48px; height: 48px; object-fit: contain;">
                </div>
                <div>
                    <h3 style="margin-top: 0; color: var(--primary);">SERVO - Assistant d'Achat Réparation</h3>
                    <p style="color: var(--day-text);">
                        Simplifiez vos commandes ! Importez vos pièces détachées depuis <strong>Utopya</strong> et <strong>Mobilax</strong> directement dans SERVO.
                        Ajoutez des produits et créez des clients sans changer d'onglet.
                    </p>
                    <a href="<?php echo isset($assets_path) ? $assets_path : 'assets/'; ?>downloads/download_servo.php" class="modern-btn modern-btn--primary">
                        <i class="fas fa-download"></i> Télécharger l'extension (v1.0.0)
                    </a>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Mode Développeur requis</strong><br>
            En attendant la validation sur le Chrome Web Store, vous devez installer l'extension manuellement.
        </div>

        <div style="background: var(--day-bg-card); border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; border: 1px solid var(--day-border);">
            <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem; font-weight: 600;">
                <i class="fas fa-tools me-2" style="color: var(--day-text-light);"></i> Guide d'installation
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; gap: 1rem;">
                    <div style="width: 30px; height: 30px; background: rgba(67, 97, 238, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">1</div>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">Télécharger le fichier</strong>
                        <span style="color: var(--day-text-light);">Cliquez sur le bouton ci-dessus. <strong>Renommez le fichier téléchargé en <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; color: var(--day-danger);">servo.zip</code></strong></span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <div style="width: 30px; height: 30px; background: rgba(67, 97, 238, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">2</div>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">Extraire le fichier</strong>
                        <span style="color: var(--day-text-light);">Double-cliquez sur <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;">servo.zip</code> pour l'extraire. Vous obtiendrez un dossier <strong>servo-extension</strong>.</span>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="width: 30px; height: 30px; background: rgba(67, 97, 238, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">3</div>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">Ouvrir Chrome en mode développeur</strong>
                        <span style="color: var(--day-text-light);">Allez a l'url suivante : <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; color: var(--day-danger);">chrome://extensions</code> et activez le <strong>"Mode développeur"</strong> (en haut à droite).</span>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="width: 30px; height: 30px; background: rgba(67, 97, 238, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">4</div>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">Sélectionner le dossier : servo-extension</strong>
                        <span style="color: var(--day-text-light);">Cliquez sur <strong>"Charger l'extension non empaquetée"</strong> puis sélectionnez le dossier <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;">servo-extension</code>.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Consommation SMS -->
    <div class="content-section fade-in" id="sms_usage">
        <h2 class="section-title">
            <i class="fas fa-sms"></i>
            Consommation SMS
        </h2>
        
        <?php
        // Charger les fonctions de facturation SMS
        require_once __DIR__ . '/../includes/sms_billing_functions.php';
        
        $shopId = $_SESSION['shop_id'] ?? null;
        $smsUsage = null;
        $smsSettings = null;
        $subscription = null;
        $smsStats = [];
        
        if ($shopId) {
            $smsUsage = getCurrentBillingPeriod($shopId);
            $smsSettings = getShopSMSSettings($shopId);
            $subscription = getShopSubscriptionInfo($shopId);
            $smsStats = getSMSStats12Months($shopId);
        }
        
        $quotaTotal = ($smsUsage['sms_included_quota'] ?? 0) + ($smsSettings['bonus_sms'] ?? 0);
        $quotaUsed = $smsUsage['sms_from_quota'] ?? 0;
        $percentUsed = $quotaTotal > 0 ? min(100, round(($quotaUsed / $quotaTotal) * 100)) : 0;
        $isUnlimited = ($subscription['sms_credits'] ?? 0) == -1;
        $isTrial = ($subscription['status'] ?? '') === 'trial';
        
        // Couleur de la barre de progression
        $progressColor = '#10b981'; // vert
        if ($percentUsed >= 90) $progressColor = '#ef4444'; // rouge
        elseif ($percentUsed >= 80) $progressColor = '#f59e0b'; // orange
        ?>
        
        <div class="info-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0;"><i class="fas fa-chart-pie"></i> Utilisation ce mois</h3>
                <?php if ($isTrial): ?>
                    <span style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">
                        <i class="fas fa-infinity"></i> Essai - SMS illimités
                    </span>
                <?php elseif ($isUnlimited): ?>
                    <span style="background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">
                        <i class="fas fa-infinity"></i> Illimité
                    </span>
                <?php endif; ?>
            </div>
            
            <p style="margin-bottom: 1rem;">
                <strong>Plan:</strong> <?= htmlspecialchars($subscription['plan_name'] ?? 'Aucun plan') ?>
                <?php if (!$isUnlimited): ?>
                    - <?= $quotaTotal ?> SMS inclus/mois
                    <?php if ($smsSettings['bonus_sms'] > 0): ?>
                        <span style="color: #10b981;">(dont +<?= $smsSettings['bonus_sms'] ?> bonus)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
            
            <?php if (!$isUnlimited && !$isTrial): ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; color: #1e293b;">
                    <span><?= $quotaUsed ?> / <?= $quotaTotal ?> SMS utilisés</span>
                    <strong><?= $percentUsed ?>%</strong>
                </div>
                <div style="height: 14px; background: rgba(0,0,0,0.1); border-radius: 7px; overflow: hidden;">
                    <div style="height: 100%; width: <?= $percentUsed ?>%; background: <?= $progressColor ?>; border-radius: 7px; transition: width 0.5s;"></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center; margin-top: 1rem;">
                <div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #4361ee;"><?= $smsUsage['sms_sent_total'] ?? 0 ?></div>
                    <div style="font-size: 0.8rem; color: #1e293b;">SMS envoyés</div>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #f59e0b;"><?= $smsUsage['sms_extra_billed'] ?? 0 ?></div>
                    <div style="font-size: 0.8rem; color: #1e293b;">SMS extra</div>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #10b981;"><?= number_format($smsUsage['extra_cost'] ?? 0, 2) ?>€</div>
                    <div style="font-size: 0.8rem; color: #1e293b;">Coût extra</div>
                </div>
            </div>
            
            <p style="margin-top: 1rem; font-size: 0.85rem; color: #1e293b;">
                <i class="fas fa-calendar"></i>
                Période: <?= date('d/m/Y', strtotime($smsUsage['period_start'] ?? 'now')) ?> - <?= date('d/m/Y', strtotime($smsUsage['period_end'] ?? 'now')) ?>
            </p>
        </div>
        
        <!-- Contrôle des coûts -->
        <form method="POST" class="modern-form">
            <input type="hidden" name="update_sms_settings" value="1">
            
            <div class="form-group">
                <label class="custom-checkbox">
                    <input type="checkbox" name="hard_cap_enabled" <?= ($smsSettings['hard_cap_enabled'] ?? 0) ? 'checked' : '' ?> onchange="document.getElementById('hard_cap_wrapper').style.display = this.checked ? 'block' : 'none'">
                    <span><strong>Plafond de sécurité</strong> - Limiter les frais SMS supplémentaires</span>
                </label>
            </div>
            
            <div id="hard_cap_wrapper" style="<?= ($smsSettings['hard_cap_enabled'] ?? 0) ? '' : 'display:none' ?>">
                <div class="form-group">
                    <label class="form-label" for="hard_cap_amount">Montant maximum (€)</label>
                    <input type="number" id="hard_cap_amount" name="hard_cap_amount" class="form-input" 
                           value="<?= $smsSettings['hard_cap_amount'] ?? 20 ?>" step="1" min="1" max="1000">
                    <small style="color: #1e293b;">Les SMS seront bloqués au-delà de ce montant</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="custom-checkbox">
                    <input type="checkbox" name="alerts_enabled" <?= ($smsSettings['alerts_enabled'] ?? 1) ? 'checked' : '' ?>>
                    <span><strong>Alertes par email</strong> - Recevoir des alertes à 80%, 90% et 100% du quota</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="alert_email">Email pour les alertes</label>
                <input type="email" id="alert_email" name="alert_email" class="form-input" 
                       value="<?= htmlspecialchars($smsSettings['alert_email'] ?? '') ?>" 
                       placeholder="votre@email.com">
            </div>
            
            <button type="submit" class="modern-btn modern-btn--success">
                <i class="fas fa-save"></i>
                Enregistrer les paramètres SMS
            </button>
        </form>
        
        <!-- Graphique historique -->
        <div class="info-card" style="margin-top: 1.5rem;">
            <h3><i class="fas fa-chart-bar"></i> Historique des 12 derniers mois</h3>
            <div style="display: flex; align-items: flex-end; gap: 6px; height: 150px; margin-top: 1rem;">
                <?php 
                // Générer les 12 derniers mois
                $months = [];
                for ($i = 11; $i >= 0; $i--) {
                    $monthKey = date('Y-m', strtotime("-$i months"));
                    $monthLabel = date('M', strtotime("-$i months"));
                    $months[$monthKey] = ['label' => $monthLabel, 'count' => 0];
                }
                
                // Remplir avec les données réelles
                foreach ($smsStats as $stat) {
                    if (isset($months[$stat['month_year']])) {
                        $months[$stat['month_year']]['count'] = $stat['total_sms'] ?? 0;
                    }
                }
                
                $maxCount = max(1, max(array_column($months, 'count')));
                
                foreach ($months as $monthData): 
                    $height = ($monthData['count'] / $maxCount) * 120;
                ?>
                <div style="flex: 1; text-align: center;">
                    <div style="height: 120px; display: flex; flex-direction: column; justify-content: flex-end;">
                        <div style="background: linear-gradient(180deg, #4361ee, #00d4ff); 
                                    height: <?= max(4, $height) ?>px; 
                                    border-radius: 4px 4px 0 0;"
                             title="<?= $monthData['count'] ?> SMS"></div>
                    </div>
                    <div style="font-size: 0.65rem; color: #1e293b; margin-top: 4px;"><?= $monthData['label'] ?></div>
                    <div style="font-size: 0.7rem; font-weight: bold;"><?= $monthData['count'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Section Facturation -->
    <div class="content-section fade-in" id="facturation">
        <h2 class="section-title">
            <i class="fas fa-credit-card"></i>
            Gestion de l'abonnement et facturation
        </h2>
        
        <?php
        // Charger le dashboard d'abonnement
        require_once __DIR__ . '/../classes/SubscriptionManager.php';
        
        $manager = new SubscriptionManager($_SESSION['shop_id']);
        $subInfo = $manager->getSubscriptionInfo();
        $usageStats = $manager->getUsageStats($_SESSION['shop_id']);
        
        // Valeurs par défaut si pas d'info
        if (!$subInfo) {
            echo "<div class='alert alert-danger'>Impossible de charger les informations d'abonnement. Veuillez contacter le support.</div>";
        } else {
            $status_labels = [
                'trial' => 'Période d\'essai',
                'active' => 'Actif',
                'past_due' => 'Paiement en attente',
                'cancelled' => 'Annulé',
                'expired' => 'Expiré'
            ];
            
            $status_colors = [
                'trial' => 'warning',
                'active' => 'success',
                'past_due' => 'danger',
                'cancelled' => 'secondary',
                'expired' => 'danger'
            ];
            
            $current_status = $subInfo['subscription_status'] ?? 'unknown';
            $status_label = $status_labels[$current_status] ?? ucfirst($current_status);
            $badge_class = 'badge bg-' . ($status_colors[$current_status] ?? 'secondary');
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; margin-top: 2rem;">
            <!-- Carte Résumé Abonnement -->
            <div style="background: var(--day-bg-card); border-radius: 20px; padding: 2rem; border: 1px solid var(--day-border); box-shadow: 0 8px 32px var(--day-shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: var(--day-text);">Vue d'overview</h3>
                    <span class="<?= $badge_class ?>" style="padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><?= htmlspecialchars($status_label) ?></span>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="color: var(--day-text-light); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">Plan Actuel</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--day-text); margin-bottom: 0.25rem;">
                        <?= htmlspecialchars($subInfo['plan_name'] ?? 'Aucun plan') ?>
                    </div>
                    <div style="color: var(--day-text-light); font-size: 1.1rem;">
                        <?= number_format($subInfo['plan_price'] ?? 0, 2) ?> € / <?= ($subInfo['billing_period'] ?? 'monthly') == 'yearly' ? 'an' : 'mois' ?>
                    </div>
                </div>

                <?php if ($current_status === 'trial'): ?>
                    <?php 
                        $days_left = $subInfo['days_remaining'] ?? 0;
                        $progress = $manager->getTrialProgress($subInfo);
                    ?>
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(255, 193, 7, 0.1); border-radius: 12px; border: 1px solid rgba(255, 193, 7, 0.3);">
                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--day-text);">
                            <span>Période d'essai</span>
                            <span style="font-weight: 700;"><?= max(0, $days_left) ?> jours restants</span>
                        </div>
                        <div style="height: 8px; background: rgba(255, 193, 7, 0.2); border-radius: 4px; overflow: hidden;">
                            <div style="width: <?= $progress ?>%; height: 100%; background: linear-gradient(90deg, #f59e0b, #d97706); transition: width 0.3s ease;"></div>
                        </div>
                        <div style="color: var(--day-text-light); font-size: 0.75rem; margin-top: 0.5rem;">
                            Fin le <?= !empty($subInfo['trial_ends_at']) ? date('d/m/Y', strtotime($subInfo['trial_ends_at'])) : 'Non définie' ?>
                        </div>
                    </div>
                <?php elseif ($current_status === 'active'): ?>
                    <div style="padding: 1rem; background: rgba(72, 187, 120, 0.1); border-radius: 12px; border: 1px solid rgba(72, 187, 120, 0.3); margin-bottom: 1.5rem; color: var(--day-text); font-size: 0.875rem;">
                        Prochain renouvellement le : 
                        <strong><?= date('d/m/Y', strtotime($subInfo['current_period_end'] ?? 'now')) ?></strong>
                    </div>
                <?php endif; ?>

                <div style="display: grid; gap: 0.75rem;">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#managePlanModal" class="modern-btn" style="justify-content: center; text-decoration: none; width: 100%;">
                        <i class="fas fa-cog"></i>
                        Gérer mon plan
                    </button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#billingModal" class="modern-btn" style="justify-content: center; text-decoration: none; width: 100%; background: rgba(67, 97, 238, 0.1); color: var(--day-accent); box-shadow: none;">
                        <i class="fas fa-file-invoice"></i>
                        Portail facturation
                    </button>
                </div>
            </div>

            <!-- Carte Utilisation -->
            <div style="background: var(--day-bg-card); border-radius: 20px; padding: 2rem; border: 1px solid var(--day-border); box-shadow: 0 8px 32px var(--day-shadow);">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600; color: var(--day-text);">Utilisation</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(118, 75, 162, 0.1)); padding: 1.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(67, 97, 238, 0.2);">
                        <div style="font-size: 2.5rem; font-weight: 700; color: var(--day-accent); margin-bottom: 0.25rem;">
                            <?= number_format($usageStats['sms_count'] ?? 0) ?>
                        </div>
                        <div style="color: var(--day-text-light); font-size: 0.875rem; font-weight: 500;">SMS Envoyés</div>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(56, 178, 172, 0.1)); padding: 1.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(72, 187, 120, 0.2);">
                        <div style="font-size: 2.5rem; font-weight: 700; color: var(--day-success); margin-bottom: 0.25rem;">
                            <?= number_format($usageStats['client_count'] ?? 0) ?>
                        </div>
                        <div style="color: var(--day-text-light); font-size: 0.875rem; font-weight: 500;">Clients</div>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background: linear-gradient(135deg, rgba(66, 153, 225, 0.1), rgba(49, 130, 206, 0.1)); border-radius: 16px; border: 1px solid rgba(66, 153, 225, 0.2);">
                    <div style="display: flex; align-items: start; gap: 1rem;">
                        <i class="fas fa-info-circle" style="color: var(--day-info); font-size: 1.5rem; margin-top: 0.25rem;"></i>
                        <div>
                            <strong style="color: var(--day-text); display: block; margin-bottom: 0.5rem;">Besoin d'aide ?</strong>
                            <p style="color: var(--day-text-light); font-size: 0.875rem; margin: 0 0 1rem 0; line-height: 1.5;">
                                Notre équipe support est disponible pour vous aider avec votre abonnement.
                            </p>
                            <a href="mailto:support@servo.tools" class="modern-btn modern-btn--success" style="text-decoration: none; font-size: 0.875rem; padding: 0.75rem 1.25rem;">
                                <i class="fas fa-envelope"></i>
                                Contacter le support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php } ?>
    </div>

    <!-- Modal Gérer mon plan -->
    <div class="modal fade" id="managePlanModal" tabindex="-1" aria-labelledby="managePlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background: var(--day-bg-card); border: 1px solid var(--day-border); border-radius: 20px;">
                <div class="modal-header" style="border-bottom: 1px solid var(--day-border); padding: 1.5rem 2rem;">
                    <h3 class="modal-title" id="managePlanModalLabel" style="color: var(--day-text); font-weight: 700;">
                        <i class="fas fa-cog me-2" style="color: var(--day-accent);"></i>
                        Gérer mon abonnement
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <?php
                    // Récupérer les plans disponibles
                    $plans = $manager->getAvailablePlans();
                    $current_plan_id = $subInfo['plan_id'] ?? 0;
                    ?>
                    
                    <!-- Abonnement actuel -->
                    <div style="background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(118, 75, 162, 0.1)); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; border: 1px solid rgba(67, 97, 238, 0.2);">
                        <h4 style="color: var(--day-text); margin-bottom: 1rem; font-size: 1.25rem; font-weight: 600;">
                            Votre abonnement actuel
                        </h4>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--day-text); margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($subInfo['plan_name'] ?? 'Inconnu') ?>
                                </div>
                                <div style="color: var(--day-text-light); font-size: 0.875rem;">
                                    Statut : <span class="badge bg-<?= $subInfo['subscription_status'] == 'active' ? 'success' : 'warning' ?>" style="padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.75rem;"><?= ucfirst($subInfo['subscription_status']) ?></span>
                                </div>
                                <?php if ($subInfo['subscription_status'] == 'trial' && !empty($subInfo['trial_ends_at'])): ?>
                                    <div style="color: var(--day-warning); font-size: 0.875rem; margin-top: 0.5rem;">
                                        Fin de l'essai : <?= date('d/m/Y', strtotime($subInfo['trial_ends_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--day-text);"><?= number_format($subInfo['plan_price'], 2) ?> €</div>
                                <div style="color: var(--day-text-light); font-size: 0.875rem;">/ <?= ($subInfo['billing_period'] ?? 'monthly') == 'yearly' ? 'an' : 'mois' ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <h4 style="color: var(--day-text); margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">
                        Changer de plan
                    </h4>
                    
                    <!-- Grille des plans -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($plans as $plan): ?>
                            <?php 
                                $is_current = ($plan['id'] == $current_plan_id);
                                $features = json_decode($plan['features'] ?? '[]', true) ?? [];
                            ?>
                            <div style="background: var(--day-bg-card); border: 2px solid <?= $is_current ? 'var(--day-accent)' : 'var(--day-border)' ?>; border-radius: 16px; padding: 1.5rem; display: flex; flex-direction: column;">
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                                        <h5 style="font-weight: 700; font-size: 1.125rem; color: var(--day-text); margin: 0;"><?= htmlspecialchars($plan['name']) ?></h5>
                                        <?php if ($is_current): ?>
                                            <span class="badge bg-success" style="padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">Actuel</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: var(--day-text-light); font-size: 0.875rem; min-height: 40px; margin-bottom: 1rem;"><?= htmlspecialchars($plan['description']) ?></div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--day-text);">
                                        <?= number_format($plan['price'], 2) ?> €
                                        <span style="font-size: 0.875rem; color: var(--day-text-light); font-weight: 400;">/ <?= $plan['billing_period'] == 'yearly' ? 'an' : 'mois' ?></span>
                                    </div>
                                </div>

                                <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem 0; flex-grow: 1;">
                                    <?php foreach ($features as $feature): ?>
                                        <li style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; color: var(--day-text); font-size: 0.875rem;">
                                            <i class="fa-solid fa-check" style="color: var(--day-success);"></i>
                                            <?= htmlspecialchars($feature) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div style="margin-top: auto;">
                                    <?php if ($is_current): ?>
                                        <button class="modern-btn" disabled style="opacity: 0.7; cursor: default; width:100%; justify-content: center;">Votre plan actuel</button>
                                    <?php else: ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="checkout">
                                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                            <button type="submit" class="modern-btn" style="width:100%; justify-content: center;">
                                                Choisir ce plan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Portail Facturation -->
    <div class="modal fade" id="billingModal" tabindex="-1" aria-labelledby="billingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="background: var(--day-bg-card); border: 1px solid var(--day-border); border-radius: 20px;">
                <div class="modal-header" style="border-bottom: 1px solid var(--day-border); padding: 1.5rem 2rem;">
                    <h3 class="modal-title" id="billingModalLabel" style="color: var(--day-text); font-weight: 700;">
                        <i class="fas fa-file-invoice-dollar me-2" style="color: var(--day-accent);"></i>
                        Portail de Facturation
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 3rem 2rem; text-align: center;">
                    <div style="font-size: 4rem; color: var(--day-accent); margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    
                    <h4 style="color: var(--day-text); font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                        Gestion sécurisée de vos paiements
                    </h4>
                    
                    <p style="color: var(--day-text-light); font-size: 1rem; line-height: 1.6; max-width: 500px; margin: 0 auto 2rem auto;">
                        Pour garantir la sécurité de vos données financières, la gestion de vos factures et de votre historique de paiement est centralisée sur notre portail sécurisé Stripe.
                    </p>

                    <div style="background: linear-gradient(135deg, rgba(66, 153, 225, 0.1), rgba(49, 130, 206, 0.1)); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(66, 153, 225, 0.2);">
                        <p style="color: var(--day-text); font-size: 0.875rem; margin: 0; text-align: left;">
                            <strong><i class="fas fa-check-circle me-2" style="color: var(--day-success);"></i>Ce que vous pouvez faire :</strong><br>
                            • Télécharger vos factures<br>
                            • Changer votre carte bancaire<br>
                            • Modifier vos informations de facturation<br>
                            • Annuler votre abonnement
                        </p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="portal">
                        <button type="submit" class="modern-btn modern-btn--success" style="padding: 1rem 2rem; font-size: 1.1rem; justify-content: center;">
                            <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>
                            Accéder au Portail
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Gestion des particules
function createParticles() {
    const container = document.getElementById('particles');
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        const size = Math.random() * 4 + 2;
        const x = Math.random() * window.innerWidth;
        const y = Math.random() * window.innerHeight;
        const delay = Math.random() * 6;
        
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        particle.style.left = x + 'px';
        particle.style.top = y + 'px';
        particle.style.animationDelay = delay + 's';
        
        container.appendChild(particle);
    }
}

// Détection automatique du thème système
function detectSystemTheme() {
    // Vérifier si le navigateur supporte prefers-color-scheme
    if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        return darkModeQuery.matches ? 'dark' : 'light';
    }
    return 'light';
}

// Appliquer le thème automatiquement
function applySystemTheme() {
    const systemTheme = detectSystemTheme();
    
    if (systemTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
    } else {
        document.body.removeAttribute('data-theme');
    }
    
    // Synchroniser le select du thème
    const themeSelect = document.getElementById('theme');
    if (themeSelect) {
        themeSelect.value = systemTheme;
    }
    
    // Mettre à jour l'affichage du thème actuel
    const themeDisplay = document.getElementById('current-theme-display');
    if (themeDisplay) {
        if (systemTheme === 'dark') {
            themeDisplay.innerHTML = '<i class="fas fa-moon"></i> Mode nuit (sombre)';
            themeDisplay.style.color = '#00d4ff';
        } else {
            themeDisplay.innerHTML = '<i class="fas fa-sun"></i> Mode jour (clair)';
            themeDisplay.style.color = '#667eea';
        }
    }
    
    return systemTheme;
}

// Écouter les changements de thème système
function watchSystemTheme() {
    if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Écouter les changements
        darkModeQuery.addEventListener('change', (e) => {
            applySystemTheme();
        });
    }
}

// Gestion de la navigation
function initializeNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const contentSections = document.querySelectorAll('.content-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            // Retirer la classe active de tous les éléments
            navItems.forEach(nav => nav.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Ajouter la classe active à l'élément cliqué
            item.classList.add('active');
            
            // Afficher la section correspondante
            const tabId = item.getAttribute('data-tab');
            const targetSection = document.getElementById(tabId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
    
    // Vérifier s'il y a une ancre dans l'URL
    const hash = window.location.hash.substring(1);
    if (hash) {
        const targetNav = document.querySelector(`[data-tab="${hash}"]`);
        const targetSection = document.getElementById(hash);
        
        if (targetNav && targetSection) {
            navItems.forEach(nav => nav.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            targetNav.classList.add('active');
            targetSection.classList.add('active');
        }
    }
}

// Gestion des horaires de relance
function addHoraire() {
    const container = document.getElementById('relanceHoraires');
    const horaireItem = document.createElement('div');
    horaireItem.className = 'horaire-item';
    
    horaireItem.innerHTML = `
        <input type="time" name="relance_horaires[]" class="form-input" value="09:00" required>
        <button type="button" class="remove-horaire" onclick="removeHoraire(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(horaireItem);
}

function removeHoraire(button) {
    button.parentElement.remove();
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Appliquer le thème système automatiquement
    applySystemTheme();
    // Écouter les changements de thème système
    watchSystemTheme();
    
    createParticles();
    initializeNavigation();
    
    // Animation d'entrée
    setTimeout(() => {
        document.querySelectorAll('.fade-in').forEach((element, index) => {
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 100);
});

// Redimensionner les particules lors du resize
window.addEventListener('resize', () => {
    const container = document.getElementById('particles');
    container.innerHTML = '';
    createParticles();
});

// Gestion des étiquettes
document.addEventListener('DOMContentLoaded', function() {
    const layoutCards = document.querySelectorAll('.layout-card-setting');
    const layoutRadios = document.querySelectorAll('input[name="label_layout"]');
    const previewBtn = document.getElementById('previewLabelBtn');
    const previewContent = document.getElementById('labelPreviewContent');
    
    // Gestionnaire pour les cartes de layout
    layoutCards.forEach(card => {
        card.addEventListener('click', function() {
            const layoutId = this.dataset.layoutId;
            const radio = this.querySelector('input[type="radio"]');
            
            // Désélectionner toutes les cartes
            layoutCards.forEach(c => c.classList.remove('selected'));
            layoutRadios.forEach(r => r.checked = false);
            
            // Sélectionner la carte cliquée
            this.classList.add('selected');
            radio.checked = true;
        });
    });
    
    // Gestionnaire pour l'aperçu
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            const selectedLayout = document.querySelector('input[name="label_layout"]:checked');
            
            if (!selectedLayout) {
                alert('Veuillez sélectionner un modèle d\'étiquette');
                return;
            }
            
            // Afficher le loading dans le modal
            previewContent.innerHTML = `
                <div class="preview-loading" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--day-accent);"></i>
                    <p style="margin-top: 1rem; color: var(--day-text);">Chargement de l'aperçu...</p>
                </div>
            `;
            
            // Charger l'aperçu (utiliser une réparation existante)
            const previewRepairId = <?php echo $previewRepairId ? (int)$previewRepairId : 'null'; ?>;
            
            if (!previewRepairId) {
                previewContent.innerHTML = `
                    <div class="preview-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Aucune réparation disponible pour l'aperçu</p>
                        <small>Créez d'abord une réparation pour voir l'aperçu</small>
                    </div>
                `;
                return;
            }
            
            fetch(`ajax/preview_label.php?id=${previewRepairId}&layout=${selectedLayout.value}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    previewContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de l\'aperçu:', error);
                    previewContent.innerHTML = `
                        <div class="preview-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Erreur lors du chargement de l'aperçu</p>
                            <small>${error.message}</small>
                        </div>
                    `;
                });
        });
    }
});
</script>
