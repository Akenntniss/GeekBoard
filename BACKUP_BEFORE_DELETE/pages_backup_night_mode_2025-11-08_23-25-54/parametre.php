<?php
// Définir la page actuelle pour le menu
$current_page = 'parametre';

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
        } catch (PDOException $e) {
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_company_settings'])) {
        // Mise à jour des paramètres d'entreprise
        $company_name = cleanInput($_POST['company_name']);
        $company_phone = cleanInput($_POST['company_phone']);
        $company_email = cleanInput($_POST['company_email']);
        $company_address = cleanInput($_POST['company_address']);
        
        // Gestion de l'upload du logo
        $logo_path = '';
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '/var/www/mdgeek.top/assets/uploads/logos/';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['company_logo']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            
            if (in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                // Nom unique pour éviter les conflits
                $filename = 'logo_shop_' . $_SESSION['shop_id'] . '_' . time() . '.' . $file_info['extension'];
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $destination)) {
                    $logo_path = 'assets/uploads/logos/' . $filename;
                    
                    // Supprimer l'ancien logo s'il existe
                    try {
                        $stmt = $shop_pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'company_logo'");
                        $stmt->execute();
                        $old_logo = $stmt->fetchColumn();
                        
                        if (!empty($old_logo) && file_exists('/var/www/mdgeek.top/' . $old_logo)) {
                            unlink('/var/www/mdgeek.top/' . $old_logo);
                        }
                    } catch (Exception $e) {
                        error_log("Erreur lors de la suppression de l'ancien logo: " . $e->getMessage());
                    }
                } else {
                    set_message("Erreur lors de l'upload du logo.", "danger");
                }
            } else {
                set_message("Format de fichier non autorisé pour le logo. Utilisez JPG, PNG, GIF ou SVG.", "danger");
            }
        }
        
        $company_params = [
            'company_name' => $company_name,
            'company_phone' => $company_phone,
            'company_email' => $company_email,
            'company_address' => $company_address
        ];
        
        // Ajouter le logo seulement si un nouveau fichier a été uploadé
        if (!empty($logo_path)) {
            $company_params['company_logo'] = $logo_path;
        }
        
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
                        'company_email' => 'Adresse email de l\'entreprise',
                        'company_address' => 'Adresse de l\'entreprise',
                        'company_logo' => 'Chemin vers le logo de l\'entreprise'
                    ];
                    
                    $stmt = $shop_pdo->prepare("INSERT INTO parametres (cle, valeur, description) VALUES (?, ?, ?)");
                    $stmt->execute([$cle, $valeur, $descriptions[$cle]]);
                }
            }
            
            $shop_pdo->commit();
            set_message("Paramètres d'entreprise mis à jour avec succès." . (!empty($logo_path) ? " Logo uploadé." : ""), "success");
            $form_submitted = true;
        } catch (PDOException $e) {
            $shop_pdo->rollback();
            set_message("Erreur lors de la mise à jour des paramètres d'entreprise: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['delete_company_logo'])) {
        // Suppression du logo d'entreprise
        try {
            $shop_pdo->beginTransaction();
            
            // Récupérer le chemin de l'ancien logo
            $stmt = $shop_pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'company_logo'");
            $stmt->execute();
            $old_logo = $stmt->fetchColumn();
            
            // Supprimer le fichier physique s'il existe
            if (!empty($old_logo) && file_exists('/var/www/mdgeek.top/' . $old_logo)) {
                unlink('/var/www/mdgeek.top/' . $old_logo);
            }
            
            // Mettre à jour la base de données (vider la valeur)
            $stmt = $shop_pdo->prepare("UPDATE parametres SET valeur = '' WHERE cle = 'company_logo'");
            $stmt->execute();
            
            $shop_pdo->commit();
            set_message("Logo supprimé avec succès.", "success");
            $form_submitted = true;
            
        } catch (PDOException $e) {
            $shop_pdo->rollback();
            set_message("Erreur lors de la suppression du logo: " . $e->getMessage(), "danger");
        }
    }
    
    if ($form_submitted) {
        echo '<meta http-equiv="refresh" content="0;url=index.php?page=parametre">';
        exit();
    }
}

// Récupérer les préférences utilisateur
$preferences = [
    'theme' => 'light',
    'notifications' => 1,
    'elements_per_page' => 10,
    'timezone_offset' => 0
];

try {
    $stmt = $shop_pdo->prepare("SELECT * FROM preferences WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_preferences) {
        $preferences = [
            'theme' => $user_preferences['theme'],
            'notifications' => $user_preferences['notifications'],
            'elements_per_page' => $user_preferences['elements_per_page'],
            'timezone_offset' => isset($user_preferences['timezone_offset']) ? $user_preferences['timezone_offset'] : 0
        ];
    }
} catch (PDOException $e) {
    // Utiliser les préférences par défaut si erreur
}

// Récupérer la configuration des relances automatiques
$relance_config = [
    'est_active' => false,
    'relances_horaires' => ['09:00', '14:00', '17:00']
];

try {
    $stmt = $shop_pdo->prepare("SELECT * FROM relance_automatique_config WHERE shop_id = ?");
    $stmt->execute([$_SESSION['shop_id']]);
    $config_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config_row) {
        $relance_config['est_active'] = (bool)$config_row['est_active'];
        $horaires_json = $config_row['relances_horaires'];
        $horaires_decoded = json_decode($horaires_json, true);
        if (is_array($horaires_decoded) && !empty($horaires_decoded)) {
            $relance_config['relances_horaires'] = $horaires_decoded;
        }
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de la config relance: " . $e->getMessage());
}

// Récupérer les paramètres d'entreprise
$company_settings = [
    'company_name' => '',
    'company_phone' => '',
    'company_email' => '',
    'company_address' => '',
    'company_logo' => ''
];

try {
    $stmt = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone', 'company_email', 'company_address', 'company_logo')");
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
?>

<?php 
// ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
displayTrialWarning(); 
?>

<style>
:root {
    /* Mode jour */
    --primary: #4361ee;
    --primary-light: #6282ff;
    --primary-dark: #3a56d4;
    --secondary: #64748b;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    
    --bg-main: #f8fafc;
    --bg-card: #ffffff;
    --bg-input: #ffffff;
    --bg-hover: #f1f5f9;
    --text-primary: #1e293b;
    --text-secondary: #475569;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --border-input: #cbd5e1;
    
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.03);
    --shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.06), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    
    --radius: 0.75rem;
    --radius-md: 1rem;
    --radius-lg: 1.25rem;
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Layout principal */
.settings-container {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--bg-main) 0%, #e2e8f0 100%);
    padding: 80px 20px 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.settings-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
}

/* Header */
.settings-header {
    grid-column: 1 / -1;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: 32px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    margin-bottom: 24px;
}

.settings-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
}

.settings-title i {
    color: var(--primary);
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation latérale */
.settings-nav {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: 24px;
    height: fit-content;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    position: sticky;
    top: 100px;
}

.nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
    color: var(--text-secondary);
    text-decoration: none;
    font-weight: 500;
    border: 1px solid transparent;
}

.nav-item:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
    transform: translateX(4px);
}

.nav-item.active {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    box-shadow: var(--shadow-md);
}

.nav-item i {
    width: 20px;
    margin-right: 12px;
    text-align: center;
}

/* Info card */
.info-card {
    background: linear-gradient(135deg, var(--info), #0ea5e9);
    color: white;
    border-radius: var(--radius-md);
    padding: 20px;
    margin-top: 24px;
    box-shadow: var(--shadow-md);
}

.info-card h5 {
    margin: 0 0 8px 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-card p {
    margin: 0 0 16px 0;
    opacity: 0.9;
    font-size: 0.875rem;
    line-height: 1.5;
}

.info-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: var(--radius);
    font-size: 0.875rem;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-block;
}

.info-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

/* Contenu principal */
.settings-content {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow: hidden;
}

.content-section {
    display: none;
    animation: fadeIn 0.3s ease-in-out;
}

.content-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    padding: 24px 32px;
    border-bottom: 1px solid var(--border);
}

.section-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-body {
    padding: 32px;
}

/* Formulaires */
.form-grid {
    display: grid;
    gap: 24px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.form-input {
    padding: 12px 16px;
    border: 2px solid var(--border-input);
    border-radius: var(--radius);
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 1rem;
    transition: var(--transition);
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 16px;
    color: var(--text-muted);
    width: 16px;
    text-align: center;
    z-index: 1;
}

.input-with-icon .form-input {
    padding-left: 48px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 8px;
    border-radius: var(--radius);
    transition: var(--transition);
}

.password-toggle:hover {
    color: var(--primary);
    background: var(--bg-hover);
}

/* Select personnalisé */
.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 48px;
}

/* Checkbox et switch */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 0;
}

.custom-checkbox {
    position: relative;
    display: inline-block;
    width: 20px;
    height: 20px;
}

.custom-checkbox input {
    opacity: 0;
    position: absolute;
    width: 100%;
    height: 100%;
    margin: 0;
    cursor: pointer;
}

.checkmark {
    position: absolute;
    top: 0;
    left: 0;
    width: 20px;
    height: 20px;
    background: var(--bg-input);
    border: 2px solid var(--border-input);
    border-radius: 4px;
    transition: var(--transition);
}

.custom-checkbox input:checked ~ .checkmark {
    background: var(--primary);
    border-color: var(--primary);
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.custom-checkbox input:checked ~ .checkmark:after {
    display: block;
}

/* Boutons */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    box-shadow: var(--shadow);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger), #f87171);
    color: white;
    box-shadow: var(--shadow);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Session info */
.session-info {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--bg-hover);
    border-radius: var(--radius);
    margin: 16px 0;
}

.session-badge {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--success), #34d399);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.session-details h4 {
    margin: 0 0 4px 0;
    color: var(--text-primary);
    font-size: 1rem;
}

.session-details p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.divider {
    height: 1px;
    background: var(--border);
    margin: 24px 0;
}

.form-text {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .settings-wrapper {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .settings-nav {
        position: static;
        order: 2;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .settings-container {
        padding: 80px 16px 16px;
    }
    
    .section-body {
        padding: 24px;
    }
}
</style>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="settings-container" id="mainContent" style="display: none;">
    <div class="settings-wrapper">
        <div class="settings-header">
            <h1 class="settings-title">
                <i class="fas fa-cogs"></i>
                Paramètres
            </h1>
        </div>

        <!-- Navigation latérale -->
        <aside class="settings-nav">
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
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li class="nav-item" data-tab="system">
                    <i class="fas fa-server"></i>
                    Système
                </li>
                <?php endif; ?>
                <li class="nav-item" data-tab="warranty">
                    <i class="fas fa-shield-alt"></i>
                    Garantie
                </li>
            </ul>
            
            <div class="info-card">
                <h5><i class="fas fa-info-circle"></i>Aide</h5>
                <p>Configurez votre profil et vos préférences pour personnaliser votre expérience utilisateur.</p>
                <a href="#" class="info-btn">Documentation</a>
            </div>
        </aside>
        
        <!-- Contenu principal -->
        <main class="settings-content">
                <!-- Profil -->
            <section class="content-section active" id="profile">
                <div class="section-header">
                    <h3><i class="fas fa-id-card"></i>Informations personnelles</h3>
                        </div>
                <div class="section-body">
                    <form method="post" action="" class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                        <label for="nom" class="form-label">Nom</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" class="form-input" id="nom" name="nom" 
                                           value="<?php echo isset($user['nom']) ? htmlspecialchars($user['nom']) : ''; ?>" required>
                                        </div>
                                    </div>
                            <div class="form-group">
                                        <label for="prenom" class="form-label">Prénom</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" class="form-input" id="prenom" name="prenom" 
                                           value="<?php echo isset($user['prenom']) ? htmlspecialchars($user['prenom']) : ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                        <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="form-input" id="email" name="email" 
                                       value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                        <div class="form-group">
                                    <label for="telephone" class="form-label">Téléphone</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" class="form-input" id="telephone" name="telephone" 
                                       value="<?php echo isset($user['telephone']) ? htmlspecialchars($user['telephone']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer les modifications
                                </button>
                            </form>
                </div>
            </section>
                
                <!-- Sécurité -->
            <section class="content-section" id="security">
                <div class="section-header">
                    <h3><i class="fas fa-key"></i>Sécurité</h3>
                        </div>
                <div class="section-body">
                    <form method="post" action="" class="form-grid">
                        <div class="form-group">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-input" id="current_password" name="current_password" required>
                                <button type="button" class="password-toggle" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                        <div class="form-group">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-input" id="new_password" name="new_password" required minlength="8">
                                <button type="button" class="password-toggle" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                                </div>
                                
                        <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-input" id="confirm_password" name="confirm_password" required minlength="8">
                                <button type="button" class="password-toggle" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_password" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Mettre à jour le mot de passe
                                </button>
                            </form>

                    <div class="divider"></div>

                    <h4 style="margin-bottom: 16px; color: var(--text-primary);">Sessions actives</h4>
                    <div class="session-info">
                        <div class="session-badge">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="session-details">
                            <h4>Session actuelle</h4>
                            <p>Connecté depuis <?php echo isset($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'inconnu'; ?></p>
                                </div>
                            </div>
                            
                            <button class="btn btn-danger" onclick="if(confirm('Êtes-vous sûr de vouloir déconnecter toutes les autres sessions ?')) window.location.href='index.php?page=deconnexion&all=1';">
                        <i class="fas fa-sign-out-alt"></i>
                        Déconnecter toutes les autres sessions
                            </button>
                </div>
            </section>
                
                <!-- Préférences -->
            <section class="content-section" id="preferences">
                <div class="section-header">
                    <h3><i class="fas fa-sliders-h"></i>Préférences d'affichage</h3>
                        </div>
                <div class="section-body">
                    <form method="post" action="" class="form-grid">
                        <div class="form-group">
                                    <label for="theme" class="form-label">Thème</label>
                            <select class="form-input form-select" id="theme" name="theme">
                                        <option value="light" <?php echo $preferences['theme'] === 'light' ? 'selected' : ''; ?>>Clair</option>
                                        <option value="dark" <?php echo $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>Sombre</option>
                                        <option value="system" <?php echo $preferences['theme'] === 'system' ? 'selected' : ''; ?>>Utiliser les préférences système</option>
                                    </select>
                                </div>
                                
                        <div class="form-group">
                                    <label for="elements_per_page" class="form-label">Nombre d'éléments par page</label>
                            <select class="form-input form-select" id="elements_per_page" name="elements_per_page">
                                        <option value="10" <?php echo $preferences['elements_per_page'] == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="25" <?php echo $preferences['elements_per_page'] == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $preferences['elements_per_page'] == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $preferences['elements_per_page'] == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                                
                        <div class="form-group">
                                    <label for="timezone_offset" class="form-label">Fuseau horaire GMT</label>
                            <select class="form-input form-select" id="timezone_offset" name="timezone_offset">
                                        <?php for ($i = -12; $i <= 14; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $preferences['timezone_offset'] == $i ? 'selected' : ''; ?>>
                                                GMT<?php echo $i > 0 ? '+' . $i : $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="form-text">Définissez votre fuseau horaire par rapport à GMT (Greenwich Mean Time).</div>
                                </div>
                                
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notifications" name="notifications" <?php echo $preferences['notifications'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                </div>
                            <label for="notifications" class="form-label">Activer les notifications du navigateur</label>
                        </div>
                        
                        <button type="submit" name="update_preferences" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer les préférences
                        </button>
                    </form>
                </div>
            </section>
                
                <!-- Notifications -->
            <section class="content-section" id="notifications">
                <div class="section-header">
                    <h3><i class="fas fa-bell"></i>Paramètres de notification</h3>
                </div>
                <div class="section-body">
                    <form method="post" action="" class="form-grid">
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notification_email" checked>
                                <span class="checkmark"></span>
                        </div>
                            <label for="notification_email" class="form-label">Recevoir des notifications par email</label>
                                </div>
                                
                        <h4 style="margin: 24px 0 16px 0; color: var(--text-primary);">Types de notifications</h4>
                        
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notify_new_repair" checked>
                                <span class="checkmark"></span>
                            </div>
                            <label for="notify_new_repair" class="form-label">Nouvelles réparations</label>
                                    </div>
                                    
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notify_repair_status" checked>
                                <span class="checkmark"></span>
                            </div>
                            <label for="notify_repair_status" class="form-label">Changements de statut des réparations</label>
                                    </div>
                                    
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notify_new_task" checked>
                                <span class="checkmark"></span>
                                    </div>
                            <label for="notify_new_task" class="form-label">Nouvelles tâches</label>
                                    </div>
                                    
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notify_inventory" checked>
                                <span class="checkmark"></span>
                                    </div>
                            <label for="notify_inventory" class="form-label">Alertes d'inventaire</label>
                                </div>
                                
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="notify_system">
                                <span class="checkmark"></span>
                            </div>
                            <label for="notify_system" class="form-label">Notifications système</label>
                        </div>
                        
                        <button type="button" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer les paramètres
                        </button>
                    </form>
                </div>
            </section>

            <!-- Relance devis -->
            <section class="content-section" id="relance_devis">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i>Configuration des relances automatiques</h3>
                </div>
                <div class="section-body">
                    <form method="post" action="" class="form-grid">
                        <input type="hidden" name="update_relance_devis" value="1">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Fonctionnement :</strong> Les relances automatiques envoient des SMS aux clients 
                            pour les devis <strong>en attente</strong> et les devis <strong>expirés depuis moins de 15 jours</strong> 
                            aux heures que vous définissez. Maximum 10 relances par jour.
                        </div>
                        
                        <div class="form-group full-width">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="relance_active" name="relance_active" 
                                       <?php echo $relance_config['est_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="relance_active">
                                    Activer les relances automatiques
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Activer ou désactiver l'envoi automatique de SMS de relance pour les devis
                            </small>
                        </div>
                        
                        <div id="relance_horaires_section" style="<?php echo $relance_config['est_active'] ? '' : 'display: none;'; ?>">
                            <div class="form-group full-width">
                                <label class="form-label">
                                    <i class="fas fa-clock me-2"></i>
                                    Horaires des relances
                                </label>
                                
                                <div id="relance_horaires_container">
                                    <?php foreach ($relance_config['relances_horaires'] as $index => $horaire): ?>
                                    <div class="relance-horaire-item mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text">Relance <?php echo $index + 1; ?></span>
                                            <input type="time" class="form-control" name="relance_horaires[]" 
                                                   value="<?php echo htmlspecialchars($horaire); ?>" required>
                                            <?php if (count($relance_config['relances_horaires']) > 1): ?>
                                            <button type="button" class="btn btn-outline-danger btn-remove-horaire" 
                                                    onclick="removeHoraire(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-success btn-sm" 
                                            onclick="addHoraire()" id="btn_add_horaire">
                                        <i class="fas fa-plus me-2"></i>
                                        Ajouter une relance
                                    </button>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Les relances sont envoyées pour :
                                        <br>• Les devis avec le statut "En Attente" (non expirés)
                                        <br>• Les devis expirés depuis moins de 15 jours
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Enregistrer les paramètres
                            </button>
                        </div>
                    </form>
                </div>
            </section>
                
                <!-- Système (Admin uniquement) -->
                <?php 
                // Vérifier le rôle dans la base de données pour s'assurer qu'il est à jour
                $is_admin_check = false;
                if (isset($_SESSION['user_id'])) {
                    try {
                        $stmt = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $db_role = $stmt->fetchColumn();
                        if ($db_role === 'admin') {
                            $_SESSION['user_role'] = 'admin'; // Mettre à jour la session si nécessaire
                            $is_admin_check = true;
                        }
                    } catch (Exception $e) {
                        // En cas d'erreur, utiliser la session
                        $is_admin_check = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
                    }
                } else {
                    $is_admin_check = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
                }
                ?>
                <?php if ($is_admin_check): ?>
            <section class="content-section" id="system">
                <div class="section-header">
                    <h3><i class="fas fa-server"></i>Paramètres système</h3>
                        </div>
                <div class="section-body">
                    <form method="post" action="" enctype="multipart/form-data" class="form-grid">
                        <input type="hidden" name="update_company_settings" value="1">
                        
                        <div class="form-group">
                            <label for="company_name" class="form-label">Nom de l'entreprise</label>
                            <div class="input-with-icon">
                                <i class="fas fa-building input-icon"></i>
                                <input type="text" class="form-input" id="company_name" name="company_name" value="<?= htmlspecialchars($company_settings['company_name']) ?>">
                            </div>
                        </div>
                                
                        <div class="form-group">
                            <label for="company_address" class="form-label">Adresse</label>
                            <div class="input-with-icon">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <textarea class="form-input" id="company_address" name="company_address" rows="3" style="resize: vertical; padding-left: 40px;"><?= htmlspecialchars($company_settings['company_address']) ?></textarea>
                            </div>
                        </div>
                                
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_phone" class="form-label">Téléphone</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="text" class="form-input" id="company_phone" name="company_phone" value="<?= htmlspecialchars($company_settings['company_phone']) ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="company_email" class="form-label">Email</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-input" id="company_email" name="company_email" value="<?= htmlspecialchars($company_settings['company_email']) ?>">
                                </div>
                            </div>
                        </div>
                                
                        <div class="form-group">
                            <label for="company_logo" class="form-label">Logo</label>
                            <?php if (!empty($company_settings['company_logo'])): ?>
                                <div class="current-logo" style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                                    <p style="margin-bottom: 10px;"><small><strong>Logo actuel :</strong></small></p>
                                    <img src="<?= htmlspecialchars($company_settings['company_logo']) ?>" alt="Logo actuel" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; display: block; margin-bottom: 10px;">
                                    <form method="post" action="" style="display: inline-block;">
                                        <input type="hidden" name="delete_company_logo" value="1">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer le logo ? Cette action est irréversible.')">
                                            <i class="fas fa-trash"></i> Supprimer le logo
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-input" id="company_logo" name="company_logo" accept="image/*">
                            <small class="form-text">Formats acceptés : JPG, PNG, GIF, SVG. Taille recommandée : 200x100px</small>
                        </div>
                                
                        <div class="checkbox-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                <span class="checkmark"></span>
                            </div>
                            <label for="maintenance_mode" class="form-label">Mode maintenance</label>
                                </div>
                                
                        <div class="form-group full-width">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Enregistrer les paramètres d'entreprise
                            </button>
                        </div>
                    </form>
                </div>
            </section>
                <?php else: ?>
            <!-- Message pour les utilisateurs non-administrateurs -->
            <section class="content-section" id="system">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i>Paramètres système</h3>
                </div>
                <div class="section-body">
                    <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; border: 1px solid #ffeaa7;">
                        <h4><i class="fas fa-lock"></i> Accès restreint</h4>
                        <p>Les paramètres d'entreprise sont réservés aux administrateurs.</p>
                        <p><strong>Votre rôle actuel:</strong> <?= htmlspecialchars($_SESSION['user_role'] ?? 'Non défini') ?></p>
                        <p>Pour accéder à ces paramètres, contactez un administrateur pour obtenir les droits nécessaires.</p>
                        <hr>
                        <p><small><strong>Debug info:</strong> user_id=<?= $_SESSION['user_id'] ?? 'Non défini' ?>, shop_id=<?= $_SESSION['shop_id'] ?? 'Non défini' ?></small></p>
                        <a href="fix_parametre_button.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-tools"></i> Diagnostiquer le problème
                        </a>
                    </div>
                </div>
            </section>
                <?php endif; ?>
                
            <!-- Garantie (Accessible à tous) -->
            <section class="content-section" id="warranty">
                <div class="section-header">
                    <h3><i class="fas fa-shield-alt"></i>Paramètres de garantie</h3>
                </div>
                <div class="section-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Système de garantie automatique</strong><br>
                        Les garanties sont automatiquement créées lorsqu'une réparation passe au statut "Réparation Effectuée".
                    </div>
                    
                    <form id="warranty-settings-form" class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="garantie_active" class="form-checkbox me-2">
                                Activer le système de garantie
                            </label>
                            <small class="form-help">Permet l'activation/désactivation complète du système de garantie</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="garantie_duree_defaut" class="form-label">Durée par défaut (en jours)</label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-alt input-icon"></i>
                                <input type="number" class="form-input" id="garantie_duree_defaut" min="1" max="3650" value="90">
                            </div>
                            <small class="form-help">Durée de garantie appliquée automatiquement (1 à 3650 jours)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="garantie_description_defaut" class="form-label">Description par défaut</label>
                            <div class="input-with-icon">
                                <i class="fas fa-file-alt input-icon"></i>
                                <textarea class="form-input" id="garantie_description_defaut" rows="3" placeholder="Garantie pièces et main d'œuvre">Garantie pièces et main d'œuvre</textarea>
                            </div>
                            <small class="form-help">Description qui apparaîtra sur les garanties créées automatiquement</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="garantie_auto_creation" class="form-checkbox me-2" checked>
                                Création automatique des garanties
                            </label>
                            <small class="form-help">Créer automatiquement une garantie quand une réparation est marquée "Effectuée"</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="garantie_notification_expiration" class="form-label">Notification d'expiration</label>
                            <div class="input-with-icon">
                                <i class="fas fa-bell input-icon"></i>
                                <input type="number" class="form-input" id="garantie_notification_expiration" min="0" max="365" value="7">
                            </div>
                            <small class="form-help">Nombre de jours avant expiration pour notifier (0 = pas de notification)</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" onclick="saveWarrantySettings()">
                                <i class="fas fa-save"></i>
                                Enregistrer les paramètres
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="loadWarrantySettings()">
                                <i class="fas fa-undo"></i>
                                Annuler
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h5><i class="fas fa-chart-line me-2"></i>Statistiques des garanties</h5>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number text-success" id="warranties-active">-</div>
                                    <div class="stat-label">Garanties actives</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number text-warning" id="warranties-expiring">-</div>
                                    <div class="stat-label">Expirent bientôt</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number text-danger" id="warranties-expired">-</div>
                                    <div class="stat-label">Expirées</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-number text-info" id="warranty-claims">-</div>
                                    <div class="stat-label">Réclamations</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php?page=garanties" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list"></i>
                                Voir toutes les garanties
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
<!-- Code JavaScript orphelin supprimé -->


</div> <!-- Fermeture de mainContent -->

<style>
/* Styles pour les garanties */
.stat-card {
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.form-checkbox {
    width: 18px;
    height: 18px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid transparent;
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #b8daff;
    color: #004085;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.settings-container,
.settings-container * {
  background: transparent !important;
}

.section,
.settings-card,
.modal-content {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .section,
.dark-mode .settings-card,
.dark-mode .modal-content {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Fonction pour initialiser les onglets et la navigation
    function initializeNavigation() {
        console.log('🔧 Initialisation de la navigation des paramètres');
        
        // Debug pour le bouton des paramètres d'entreprise
        console.log('🔍 Debug paramètres - Page chargée');
        console.log('User role:', '<?= $_SESSION['user_role'] ?? 'undefined' ?>');
        console.log('Is admin check:', <?= $is_admin_check ? 'true' : 'false' ?>);
        
        // Vérifier si le bouton existe
        const saveButton = document.querySelector('button[type="submit"]:contains("Enregistrer les paramètres d\'entreprise")');
        if (saveButton) {
            console.log('✅ Bouton "Enregistrer les paramètres d\'entreprise" trouvé');
        } else {
            console.log('❌ Bouton "Enregistrer les paramètres d\'entreprise" non trouvé');
            
            // Chercher tous les boutons de soumission
            const allSubmitButtons = document.querySelectorAll('button[type="submit"]');
            console.log('Boutons de soumission trouvés:', allSubmitButtons.length);
            allSubmitButtons.forEach((btn, index) => {
                console.log(`Bouton ${index}:`, btn.textContent.trim());
            });
        }
        
        // Gestion des onglets
        const navItems = document.querySelectorAll('.nav-item');
        const contentSections = document.querySelectorAll('.content-section');
        
        console.log('📋 Navigation items trouvés:', navItems.length);
        console.log('📄 Content sections trouvées:', contentSections.length);
        
        // Vérifier s'il y a une ancre dans l'URL pour ouvrir un onglet spécifique
        let activeTab = 'profile';
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash)) {
            activeTab = hash;
        } else {
            // Afficher l'onglet sauvegardé ou le premier par défaut
            activeTab = localStorage.getItem('active_settings_tab') || 'profile';
        }
        showTab(activeTab);
        
        // Gérer les clics sur les onglets
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                console.log('🖱️ Clic sur onglet:', tabId);
                showTab(tabId);
                localStorage.setItem('active_settings_tab', tabId);
            });
        });
        
        function showTab(tabId) {
            console.log('👁️ Affichage de l\'onglet:', tabId);
            
            // Retirer la classe active de tous les onglets
            navItems.forEach(item => item.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Ajouter la classe active à l'onglet et section correspondants
            const activeNavItem = document.querySelector(`[data-tab="${tabId}"]`);
            const activeSection = document.getElementById(tabId);
            
            if (activeNavItem && activeSection) {
                activeNavItem.classList.add('active');
                activeSection.classList.add('active');
                console.log('✅ Onglet activé:', tabId);
            } else {
                console.log('❌ Onglet non trouvé:', tabId);
            }
        }
            
        // Gestion des boutons de visibilité des mots de passe
        const passwordToggles = document.querySelectorAll('.password-toggle');
        passwordToggles.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                    
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Animation smooth lors du focus sur les inputs
        const formInputs = document.querySelectorAll('.form-input');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Validation en temps réel des mots de passe
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (newPasswordInput && confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value && newPasswordInput.value) {
                    if (this.value === newPasswordInput.value) {
                        this.style.borderColor = 'var(--success)';
                        this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
                    } else {
                        this.style.borderColor = 'var(--danger)';
                        this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                    }
                } else {
                    this.style.borderColor = 'var(--border-input)';
                    this.style.boxShadow = 'none';
                }
            });
        }
        
        // Animation au survol des boutons
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Animation des formulaires lors de la soumission
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                    submitBtn.disabled = true;
                    
                    // Remettre le texte original après 3 secondes (en cas d'erreur)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
        });
    }
    
    // Gérer le loader et initialiser la navigation après
    setTimeout(function() {
        loader.classList.add('fade-out');
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
            
            // Initialiser la navigation après que le contenu soit visible
            setTimeout(function() {
                initializeNavigation();
                
                // Initialiser les garanties si la section existe
                if (document.getElementById('warranty-settings-form')) {
                    initializeWarrantySettings();
                }
            }, 100);
        }, 500);
    }, 300);
    
    // Fonctions de gestion des garanties
    function initializeWarrantySettings() {
        // Charger les paramètres de garantie au démarrage
        loadWarrantySettings();
        loadWarrantyStats();
        
        // Gérer la soumission du formulaire
        const warrantyForm = document.getElementById('warranty-settings-form');
        if (warrantyForm) {
            warrantyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveWarrantySettings();
            });
        }
    }

    // Charger les paramètres de garantie
    function loadWarrantySettings() {
        fetch('../ajax/update_warranty_settings.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remplir le formulaire avec les valeurs
                document.getElementById('garantie_active').checked = data.data.garantie_active.value === '1';
                document.getElementById('garantie_duree_defaut').value = data.data.garantie_duree_defaut.value;
                document.getElementById('garantie_description_defaut').value = data.data.garantie_description_defaut.value;
                document.getElementById('garantie_auto_creation').checked = data.data.garantie_auto_creation.value === '1';
                document.getElementById('garantie_notification_expiration').value = data.data.garantie_notification_expiration.value;
            } else {
                console.error('Erreur lors du chargement des paramètres:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
        });
    }

    // Sauvegarder les paramètres de garantie
    function saveWarrantySettings() {
        const formData = {
            garantie_active: document.getElementById('garantie_active').checked,
            garantie_duree_defaut: parseInt(document.getElementById('garantie_duree_defaut').value),
            garantie_description_defaut: document.getElementById('garantie_description_defaut').value,
            garantie_auto_creation: document.getElementById('garantie_auto_creation').checked,
            garantie_notification_expiration: parseInt(document.getElementById('garantie_notification_expiration').value)
        };
        
        const submitBtn = document.querySelector('#warranty-settings-form .btn-primary');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        submitBtn.disabled = true;
        
        fetch('../ajax/update_warranty_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                showWarrantyNotification('Paramètres de garantie sauvegardés avec succès', 'success');
                loadWarrantyStats(); // Recharger les statistiques
            } else {
                showWarrantyNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
            showWarrantyNotification('Erreur de communication avec le serveur', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    // Charger les statistiques des garanties
    function loadWarrantyStats() {
        fetch('../ajax/warranty_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('warranties-active').textContent = data.data.active || 0;
                document.getElementById('warranties-expiring').textContent = data.data.expiring || 0;
                document.getElementById('warranties-expired').textContent = data.data.expired || 0;
                document.getElementById('warranty-claims').textContent = data.data.claims || 0;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des statistiques:', error);
        });
    }

    // Fonction d'affichage des notifications pour les garanties
    function showWarrantyNotification(message, type) {
        // Créer une notification toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px; padding: 12px 16px; border-radius: 8px;';
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
        `;
        
        document.body.appendChild(toast);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});
</script>

<!-- Script de correctif pour la navigation des onglets -->
<script src="../assets/js/parametre_navigation_fix.js"></script>
<script src="../assets/js/fix_warranty_tab.js"></script> 