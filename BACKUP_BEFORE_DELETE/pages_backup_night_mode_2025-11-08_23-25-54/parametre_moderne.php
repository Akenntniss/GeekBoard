<?php
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
        // Mise à jour des paramètres d'entreprise
        $company_name = cleanInput($_POST['company_name']);
        $company_phone = cleanInput($_POST['company_phone']);
        $company_email = cleanInput($_POST['company_email']);
        $company_address = cleanInput($_POST['company_address']);
        
        try {
            // Vérifier si la table company_settings existe
            $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'company_settings'");
            $stmt->execute();
            $table_exists = $stmt->rowCount() > 0;
            
            if (!$table_exists) {
                set_message("La table des paramètres d'entreprise n'existe pas. Veuillez contacter l'administrateur pour créer les tables manquantes.", "warning");
            } else {
                // Vérifier si les paramètres d'entreprise existent déjà
                $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM company_settings WHERE shop_id = ?");
                $stmt->execute([$_SESSION['shop_id']]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    $stmt = $shop_pdo->prepare("UPDATE company_settings SET company_name = ?, company_phone = ?, company_email = ?, company_address = ? WHERE shop_id = ?");
                    $result = $stmt->execute([$company_name, $company_phone, $company_email, $company_address, $_SESSION['shop_id']]);
                } else {
                    $stmt = $shop_pdo->prepare("INSERT INTO company_settings (shop_id, company_name, company_phone, company_email, company_address) VALUES (?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$_SESSION['shop_id'], $company_name, $company_phone, $company_email, $company_address]);
                }
                
                if ($result) {
                    set_message("Paramètres d'entreprise mis à jour avec succès.", "success");
                    $form_submitted = true;
                } else {
                    set_message("Erreur lors de la mise à jour des paramètres d'entreprise.", "danger");
                }
            }
        } catch (PDOException $e) {
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
                set_message("Erreur lors de la sauvegarde du layout d'étiquette.", "danger");
            }
        } catch (Exception $e) {
            set_message("Erreur: " . $e->getMessage(), "danger");
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

// Récupérer les paramètres d'entreprise
try {
    // Vérifier si la table company_settings existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'company_settings'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $shop_pdo->prepare("SELECT * FROM company_settings WHERE shop_id = ?");
        $stmt->execute([$_SESSION['shop_id']]);
        $company_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $company_settings = false;
    }
    
    if (!$company_settings) {
        $company_settings = [
            'company_name' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_address' => '',
            'company_logo' => ''
        ];
    }
} catch (PDOException $e) {
    $company_settings = [
        'company_name' => '',
        'company_phone' => '',
        'company_email' => '',
        'company_address' => '',
        'company_logo' => ''
    ];
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
    border: 2px solid var(--border);
    border-radius: var(--radius);
    background: white;
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
    color: var(--dark);
    margin-bottom: 0.25rem;
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

.layout-type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.layout-type-badge.thermique {
    background: #e3f2fd;
    color: #1976d2;
}

.layout-type-badge.couleur {
    background: #f3e5f5;
    color: #7b1fa2;
}

.layout-description {
    padding: 0 1rem 1rem;
    font-size: 0.875rem;
    color: #666;
    line-height: 1.4;
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
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> fade-in">
            <?php 
            if (is_array($_SESSION['message'])) {
                echo implode('<br>', $_SESSION['message']);
            } else {
                echo $_SESSION['message'];
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
            <li class="nav-item" data-tab="notifications">
                <i class="fas fa-bell"></i>
                Notifications
            </li>
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
            <li class="nav-item" data-tab="warranty">
                <i class="fas fa-shield-alt"></i>
                Garantie
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

    <!-- Section Notifications -->
    <div class="content-section fade-in" id="notifications">
        <h2 class="section-title">
            <i class="fas fa-bell"></i>
            Notifications
        </h2>
        
        <div class="info-card">
            <h3>Configuration des notifications</h3>
            <p>Les notifications vous permettent de rester informé des événements importants de votre système.</p>
            <p>Vous pouvez activer ou désactiver les notifications dans la section Préférences.</p>
        </div>
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
                <label class="form-label" for="company_email">Email de l'entreprise</label>
                <input type="email" id="company_email" name="company_email" class="form-input" value="<?php echo htmlspecialchars($company_settings['company_email']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_address">Adresse de l'entreprise</label>
                <textarea id="company_address" name="company_address" class="form-input" rows="3"><?php echo htmlspecialchars($company_settings['company_address']); ?></textarea>
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
                
                <div class="preview-section">
                    <button type="button" id="previewLabelBtn" class="modern-btn modern-btn--outline">
                        <i class="fas fa-eye"></i>
                        Aperçu
                    </button>
                    <div id="labelPreviewContainer" class="label-preview-container" style="display: none;">
                        <div class="preview-header">
                            <h5>Aperçu du modèle sélectionné</h5>
                            <button type="button" id="closeLabelPreview" class="close-preview">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="labelPreviewContent" class="preview-content">
                            <!-- L'aperçu sera chargé ici -->
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Section Garantie -->
    <div class="content-section fade-in" id="warranty">
        <h2 class="section-title">
            <i class="fas fa-shield-alt"></i>
            Informations de garantie
        </h2>
        
        <div class="info-card">
            <h3>Garantie du système</h3>
            <p><strong>Version:</strong> GeekBoard v2.0</p>
            <p><strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y'); ?></p>
            <p><strong>Support:</strong> Contactez notre équipe pour toute assistance technique.</p>
            <p><strong>Licence:</strong> Logiciel sous licence propriétaire.</p>
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
    const previewContainer = document.getElementById('labelPreviewContainer');
    const previewContent = document.getElementById('labelPreviewContent');
    const closePreviewBtn = document.getElementById('closeLabelPreview');
    
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
            
            // Afficher le conteneur d'aperçu
            previewContainer.style.display = 'block';
            
            // Afficher le loading
            previewContent.innerHTML = `
                <div class="preview-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Chargement de l'aperçu...</p>
                </div>
            `;
            
            // Charger l'aperçu (utiliser une réparation d'exemple)
            fetch(`ajax/preview_label.php?id=1&layout=${selectedLayout.value}`)
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
    
    // Gestionnaire pour fermer l'aperçu
    if (closePreviewBtn) {
        closePreviewBtn.addEventListener('click', function() {
            previewContainer.style.display = 'none';
        });
    }
    
    // Fermer l'aperçu en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        if (previewContainer && previewContainer.style.display === 'block') {
            if (!previewContainer.contains(e.target) && !previewBtn.contains(e.target)) {
                previewContainer.style.display = 'none';
            }
        }
    });
});
</script>
