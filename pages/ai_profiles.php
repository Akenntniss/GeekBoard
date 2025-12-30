<?php
/**
 * Gestion des Profils IA - GeekBoard
 */

// Debug temporaire
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification authentification  
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur';

if (!$user_id) {
    header('Location: /index.php');
    exit();
}


$is_admin = ($user_role === 'admin');

// Récupérer la BDD
$shop_pdo = getShopDBConnection();

// Récupérer l'ID du profil à éditer si spécifié
$profile_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$profile_data = null;

if ($profile_id) {
    // Charger les données du profil
    $stmt = $shop_pdo->prepare("SELECT * FROM kpi_ai_profiles WHERE id = ?");
    $stmt->execute([$profile_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile_data) {
        $_SESSION['message'] = "Profil IA non trouvé";
        $_SESSION['message_type'] = "danger";
        header("Location: ai_profiles.php");
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['profile_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $system_prompt = trim($_POST['system_prompt'] ?? $description); // Utiliser description comme system_prompt si pas fourni
    $active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        if ($profile_id) {
            // Modifier
            $stmt = $shop_pdo->prepare("
                UPDATE kpi_ai_profiles 
                SET name = ?, description = ?, system_prompt = ?, active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $system_prompt, $active, $profile_id]);
            $_SESSION['message'] = "Profil IA modifié avec succès";
            $_SESSION['message_type'] = "success";
        } else {
            // Créer
            $stmt = $shop_pdo->prepare("
                INSERT INTO kpi_ai_profiles (name, description, system_prompt, active) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $system_prompt, $active]);
            $_SESSION['message'] = "Profil IA créé avec succès";
            $_SESSION['message_type'] = "success";
        }
        
        header("Location: ai_profiles.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

// Si pas en mode édition, récupérer tous les profils
$profiles = [];
if (!$profile_id) {
    $stmt = $shop_pdo->query("SELECT * FROM kpi_ai_profiles ORDER BY created_at DESC");
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $profile_id ? 'Modifier' : 'Créer' ?> Profil IA - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/unified-night-mode.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-color: #2d3748;
            --border-color: rgba(226, 232, 240, 0.8);
        }

        [data-theme="dark"] {
            --card-bg: rgba(26, 26, 46, 0.95);
            --text-color: #e2e8f0;
            --border-color: rgba(148, 163, 184, 0.2);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            padding-top: 80px;
        }

        .ai-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .ai-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .ai-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .ai-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .ai-title i {
            color: #667eea;
        }

        .form-modern {
            display: grid;
            gap: 1.5rem;
        }

        .form-modern label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-modern input, 
        .form-modern textarea, 
        .form-modern select {
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-modern input:focus, 
        .form-modern textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-modern {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .profile-item {
            padding: 1.5rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .profile-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }

        .profile-name {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--text-color);
        }

        .profile-desc {
            color: #718096;
            margin-top: 0.5rem;
        }

        .badge-active {
            background: #48bb78;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .badge-inactive {
            background: #cbd5e0;
            color: #2d3748;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- Navbar non incluse pour éviter les erreurs -->

    <div class="ai-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>
        
        <?php if ($profile_id): ?>
            <!-- Mode édition -->
            <div class="ai-card">
                <div class="ai-header">
                    <h1 class="ai-title">
                        <i class="fas fa-edit"></i>
                        Modifier le Profil IA
                    </h1>
                    <a href="ai_profiles.php" class="btn btn-secondary btn-modern">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>

                <form method="POST" class="form-modern">
                    <div>
                        <label for="profile_name">Nom du Profil *</label>
                        <input type="text" id="profile_name" name="profile_name" 
                               class="form-control" required 
                               value="<?= htmlspecialchars($profile_data['name']) ?>">
                    </div>

                    <div>
                        <label for="expertise">Domaine d'Expertise</label>
                        <input type="text" id="expertise" name="expertise" 
                               class="form-control" 
                               value="<?= htmlspecialchars($profile_data['expertise'] ?? '') ?>"
                               placeholder="Ex: Analyse RH, Finances, Performance...">
                    </div>

                    <div>
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" 
                                  class="form-control" rows="6" required><?= htmlspecialchars($profile_data['description']) ?></textarea>
                        <small class="text-muted">Cette description sera envoyée à l'IA pour contextualiser l'analyse</small>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                               <?= $profile_data['active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Profil actif
                        </label>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                        <a href="ai_profiles.php" class="btn btn-secondary btn-modern">Annuler</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Mode liste -->
            <div class="ai-card">
                <div class="ai-header">
                    <h1 class="ai-title">
                        <i class="fas fa-robot"></i>
                        Profils IA
                    </h1>
                    <a href="?edit=new" class="btn btn-primary-modern">
                        <i class="fas fa-plus me-2"></i>Nouveau Profil
                    </a>
                </div>

                <?php if (empty($profiles)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun profil IA trouvé. Créez votre premier profil d'expert IA !
                    </div>
                <?php else: ?>
                    <?php foreach ($profiles as $profile): ?>
                        <div class="profile-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="profile-name"><?= htmlspecialchars($profile['name']) ?></span>
                                        <?php if ($profile['active']): ?>
                                            <span class="badge-active">Actif</span>
                                        <?php else: ?>
                                            <span class="badge-inactive">Inactif</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="profile-desc">
                                        <?= nl2br(htmlspecialchars(substr($profile['description'], 0, 200))) ?>
                                        <?= strlen($profile['description']) > 200 ? '...' : '' ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Créé le <?= date('d/m/Y', strtotime($profile['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="?edit=<?= $profile['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/unified-night-mode.js"></script>
</body>
</html>
