<?php

// Connexion à la base de données du magasin
$pdo = getShopDBConnection();

if (!$pdo) {
    echo "<div style='text-align: center; padding: 50px; color: #e74c3c;'>
            <h2>Erreur de connexion à la base de données</h2>
            <p>Impossible de se connecter à la base de données du magasin.</p>
          </div>";
    exit;
}

// Récupération des employés avec leurs statistiques
try {
    // Vérifier d'abord quelles tables existent
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $has_reparation_attributions = in_array('reparation_attributions', $tables);
    $has_time_tracking = in_array('time_tracking', $tables);
    $has_reparation_logs = in_array('reparation_logs', $tables);
    
    // Base query pour les utilisateurs
    $base_query = "
        SELECT u.*, 
               0 as total_reparations,
               0 as reparations_30j,
               0 as heures_travaillees,
               0 as total_pointages,
               0 as heures_mois,
               0 as pointages_mois,
               0 as en_cours_travail,
               NULL as derniere_connexion
        FROM users u 
        WHERE u.role IN ('admin', 'technicien')
    ";
    
    // Logique de récupération des stats (inchangée)
    if ($has_reparation_logs && $has_time_tracking) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%') 
                       THEN rl.reparation_id 
                   END), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%')
                       AND rl.date_action >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       THEN rl.reparation_id 
                   END), 0) as reparations_30j,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' THEN tt.work_duration ELSE 0 END), 0) as heures_travaillees,
                   COALESCE(COUNT(DISTINCT tt.id), 0) as total_pointages,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' AND DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.work_duration ELSE 0 END), 0) as heures_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.id END), 0) as pointages_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE(tt.clock_in) = CURDATE() AND tt.clock_out IS NULL THEN tt.id END), 0) as en_cours_travail,
                   MAX(tt.clock_in) as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_logs rl ON u.id = rl.employe_id 
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_reparation_logs) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%') 
                       THEN rl.reparation_id 
                   END), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%')
                       AND rl.date_action >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       THEN rl.reparation_id 
                   END), 0) as reparations_30j,
                   0 as heures_travaillees,
                   0 as total_pointages,
                   0 as heures_mois,
                   0 as pointages_mois,
                   0 as en_cours_travail,
                   NULL as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_logs rl ON u.id = rl.employe_id 
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_reparation_attributions && $has_time_tracking) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT ra.reparation_id), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE WHEN ra.date_debut >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ra.reparation_id END), 0) as reparations_30j,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' THEN tt.work_duration ELSE 0 END), 0) as heures_travaillees,
                   COALESCE(COUNT(DISTINCT tt.id), 0) as total_pointages,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' AND DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.work_duration ELSE 0 END), 0) as heures_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.id END), 0) as pointages_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE(tt.clock_in) = CURDATE() AND tt.clock_out IS NULL THEN tt.id END), 0) as en_cours_travail,
                   MAX(tt.clock_in) as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_attributions ra ON u.id = ra.employe_id 
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_reparation_attributions) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT ra.reparation_id), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE WHEN ra.date_debut >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ra.reparation_id END), 0) as reparations_30j,
                   0 as heures_travaillees,
                   0 as total_pointages,
                   0 as heures_mois,
                   0 as pointages_mois,
                   0 as en_cours_travail,
                   NULL as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_attributions ra ON u.id = ra.employe_id 
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_time_tracking) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   0 as total_reparations,
                   0 as reparations_30j,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' THEN tt.work_duration ELSE 0 END), 0) as heures_travaillees,
                   COALESCE(COUNT(DISTINCT tt.id), 0) as total_pointages,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' AND DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.work_duration ELSE 0 END), 0) as heures_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.id END), 0) as pointages_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE(tt.clock_in) = CURDATE() AND tt.clock_out IS NULL THEN tt.id END), 0) as en_cours_travail,
                   MAX(tt.clock_in) as derniere_connexion
            FROM users u 
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else {
        $stmt = $pdo->query($base_query . " ORDER BY u.full_name ASC");
    }
    
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div style='color: #e74c3c; text-align: center; padding: 20px;'>
            Erreur lors de la récupération des employés : " . htmlspecialchars($e->getMessage()) . "
          </div>";
    $employees = [];
}

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparation_attributions WHERE employe_id = ?");
        $stmt->execute([$id]);
        $has_repairs = $stmt->fetchColumn() > 0;
        
        if ($has_repairs) {
            echo "<script>
                    alert('Impossible de supprimer cet employé car il a des réparations associées.');
                    window.location.href = 'index.php?page=employes';
                  </script>";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo "<script>
                    window.location.href = 'index.php?page=employes';
                  </script>";
        }
    } catch (PDOException $e) {
        echo "<script>
                alert('Erreur lors de la suppression : " . addslashes($e->getMessage()) . "');
                window.location.href = 'index.php?page=employes';
              </script>";
    }
}

// Traitement de l'ajout d'employé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $nom = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (!empty($nom) && !empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetchColumn() > 0) {
                echo "<script>alert('Ce nom d\\'utilisateur existe déjà!');</script>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $hashed_password, $nom, $role]);
                echo "<script>window.location.href = 'index.php?page=employes';</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Erreur lors de l\\'ajout : " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Filtres
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
if ($q !== '' || ($role_filter === 'admin' || $role_filter === 'technicien')) {
    $employees = array_values(array_filter($employees, function($emp) use ($q, $role_filter) {
        if ($q !== '' && stripos(($emp['full_name'] ?? '') . ' ' . ($emp['username'] ?? ''), $q) === false) { return false; }
        if ($role_filter !== '' && ($emp['role'] ?? '') !== $role_filter) { return false; }
        return true;
    }));
}
?>

<style>
/* ========================================
   DESIGN SYSTEM - VARIABLES
======================================== */
:root {
    /* MODE JOUR (Défaut) - Style Pro/Clean */
    --bg-body: #f0f2f5;
    --bg-card: #ffffff;
    --bg-input: #f8f9fa;
    --text-main: #1a1a1a;
    --text-secondary: #65676b;
    --border-color: #e4e6eb;
    --primary: #0078e8;
    --primary-hover: #0065c2;
    --danger: #dc3545;
    --success: #28a745;
    --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --radius: 12px;
    --gradient-header: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
}

/* MODE NUIT - Style Futuriste/Cyberpunk */
body.night-mode {
    --bg-body: #0f172a;
    --bg-card: #1e293b;
    --bg-input: #0f172a;
    --text-main: #f1f5f9;
    --text-secondary: #94a3b8;
    --border-color: #334155;
    --primary: #3b82f6;
    --primary-hover: #2563eb;
    --danger: #ef4444;
    --success: #10b981;
    --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.4);
    --gradient-header: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

/* ========================================
   STYLES GLOBAUX
======================================== */
body {
    background-color: var(--bg-body) !important;
    color: var(--text-main) !important;
    font-family: 'Inter', sans-serif;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Layout */
.page-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Header de page */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-title i {
    color: var(--primary);
}

/* Cartes */
.modern-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
    overflow: hidden;
}

.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Filtres */
.filters-bar {
    background: var(--bg-card);
    padding: 1.5rem;
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.form-control, .form-select {
    background-color: var(--bg-input) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-main) !important;
    border-radius: 8px;
    padding: 0.75rem 1rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}

/* Boutons */
.btn-primary {
    background: var(--primary) !important;
    border: none !important;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--primary-hover) !important;
    transform: translateY(-1px);
}

/* ========================================
   NOUVEAU DESIGN CARTES EMPLOYÉS - GLASSMORPHISM
======================================== */
.modern-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%) !important;
    backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(255,255,255,0.3) !important;
    border-radius: 24px !important;
    box-shadow: 
        0 8px 32px rgba(31, 38, 135, 0.15),
        inset 0 0 0 1px rgba(255,255,255,0.1) !important;
    overflow: hidden;
    position: relative;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
    height: auto !important; /* Pas de hauteur fixe - s'adapte au contenu */
}

.modern-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
    background-size: 300% 100%;
    animation: gradientShift 4s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.modern-card:hover {
    transform: translateY(-8px) scale(1.02) !important;
    box-shadow: 
        0 20px 60px rgba(102, 126, 234, 0.25),
        0 0 40px rgba(139, 92, 246, 0.15),
        inset 0 0 0 1px rgba(255,255,255,0.2) !important;
}

/* Mode Nuit - Cartes */
body.night-mode .modern-card {
    background: linear-gradient(135deg, rgba(30,41,59,0.9) 0%, rgba(15,23,42,0.8) 100%) !important;
    border: 1px solid rgba(139,92,246,0.2) !important;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.4),
        0 0 60px rgba(139, 92, 246, 0.1),
        inset 0 0 0 1px rgba(139,92,246,0.1) !important;
}

body.night-mode .modern-card:hover {
    box-shadow: 
        0 20px 60px rgba(139, 92, 246, 0.3),
        0 0 80px rgba(236, 72, 153, 0.15),
        inset 0 0 0 1px rgba(139,92,246,0.3) !important;
}

/* Employee Header - Nouveau Style */
.employee-header {
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    position: relative;
    border: none !important;
}

.employee-avatar {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -1px;
    box-shadow: 
        0 10px 30px rgba(102, 126, 234, 0.4),
        0 0 0 4px rgba(255,255,255,0.5);
    position: relative;
    overflow: hidden;
}

.employee-avatar::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
    animation: avatarShine 3s ease-in-out infinite;
}

@keyframes avatarShine {
    0%, 100% { transform: translateX(-100%) rotate(45deg); }
    50% { transform: translateX(100%) rotate(45deg); }
}

.employee-info {
    flex: 1;
}

.employee-info h5 {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 700;
    background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .employee-info h5 {
    background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.employee-role {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
    color: #667eea;
    border: 1px solid rgba(102,126,234,0.2);
}

body.night-mode .employee-role {
    background: linear-gradient(135deg, rgba(139,92,246,0.2) 0%, rgba(236,72,153,0.1) 100%);
    color: #a78bfa;
    border-color: rgba(139,92,246,0.3);
}

/* Stats Section - Nouveau Design Horizontal */
.employee-stats {
    padding: 0 2rem 1.5rem;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.stat-item {
    flex: 1;
    min-width: 80px;
    background: linear-gradient(135deg, rgba(102,126,234,0.08) 0%, rgba(118,75,162,0.05) 100%);
    padding: 1rem 0.75rem;
    border-radius: 16px;
    text-align: center;
    border: 1px solid rgba(102,126,234,0.1);
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: linear-gradient(135deg, rgba(102,126,234,0.15) 0%, rgba(118,75,162,0.1) 100%);
    transform: translateY(-2px);
}

body.night-mode .stat-item {
    background: linear-gradient(135deg, rgba(139,92,246,0.15) 0%, rgba(236,72,153,0.08) 100%);
    border-color: rgba(139,92,246,0.2);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
    line-height: 1.2;
}

body.night-mode .stat-value {
    background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 0.65rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 0.35rem;
    display: block;
    font-weight: 600;
}

body.night-mode .stat-label {
    color: #94a3b8;
}

/* Actions Section - Nouveau Style */
.employee-actions {
    padding: 1.25rem 2rem;
    background: linear-gradient(180deg, rgba(248,250,252,0.5) 0%, rgba(241,245,249,0.8) 100%);
    border-top: 1px solid rgba(226,232,240,0.5);
    display: flex;
    gap: 0.75rem;
}

body.night-mode .employee-actions {
    background: linear-gradient(180deg, rgba(30,41,59,0.5) 0%, rgba(15,23,42,0.8) 100%);
    border-top-color: rgba(51,65,85,0.5);
}

.employee-actions .btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.employee-actions .btn-outline-primary {
    background: transparent !important;
    border: 2px solid #667eea !important;
    color: #667eea !important;
}

.employee-actions .btn-outline-primary:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-color: transparent !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.4);
}

.employee-actions .btn-outline-danger {
    background: transparent !important;
    border: 2px solid #f87171 !important;
    color: #f87171 !important;
    padding: 0.75rem !important;
    flex: 0 !important;
}

.employee-actions .btn-outline-danger:hover {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    border-color: transparent !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239,68,68,0.4);
}

/* Badge Admin/Tech - Nouveau Style */
.badge.bg-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    padding: 0.4rem 0.85rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}

.badge.bg-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
    padding: 0.4rem 0.85rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(6,182,212,0.3);
}

/* Modals */
.modal-content {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-main) !important;
}

.modal-header {
    border-bottom: 1px solid var(--border-color) !important;
}

.modal-footer {
    border-top: 1px solid var(--border-color) !important;
}

.close {
    color: var(--text-main) !important;
}

/* ========================================
   MODE NUIT - MODALS EMPLOYES
======================================== */
body.night-mode .modal-content,
body.night-mode #editEmployeeModal .modal-content,
body.night-mode #addEmployeeModal .modal-content {
    background: linear-gradient(135deg, rgba(30,41,59,0.98) 0%, rgba(15,23,42,0.98) 100%) !important;
    border: 1px solid rgba(139,92,246,0.3) !important;
    color: #f1f5f9 !important;
    backdrop-filter: blur(20px) !important;
}

body.night-mode .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: 1px solid rgba(139,92,246,0.3) !important;
    color: white !important;
}

body.night-mode .modal-header .modal-title {
    color: white !important;
}

body.night-mode .modal-header .btn-close {
    filter: brightness(0) invert(1) !important;
}

body.night-mode .modal-body {
    background: transparent !important;
    color: #f1f5f9 !important;
}

body.night-mode .modal-body .form-label {
    color: #e2e8f0 !important;
}

body.night-mode .modal-body .form-control,
body.night-mode .modal-body .form-select {
    background: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid rgba(139,92,246,0.3) !important;
    color: #f1f5f9 !important;
}

body.night-mode .modal-body .form-control:focus,
body.night-mode .modal-body .form-select:focus {
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 0 3px rgba(139,92,246,0.2) !important;
}

body.night-mode .modal-body .form-control::placeholder {
    color: #94a3b8 !important;
}

body.night-mode .modal-footer {
    background: rgba(15, 23, 42, 0.5) !important;
    border-top: 1px solid rgba(139,92,246,0.3) !important;
}

/* Loader */
.loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--bg-body);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.5s ease;
}

.loader-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid var(--border-color);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ====================================================================
   ANIMATED BACKGROUND SYSTEM (harmonisé avec taches_moderne.php)
==================================================================== */
/* Mode Jour - Fond animé bleu/violet */
html body {
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 300% 300% !important;
    animation: gradientFlowDay 20s ease infinite !important;
}

@keyframes gradientFlowDay {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Nuit - Transparent pour voir #animated-bg */
html body.night-mode,
html body.dark-mode {
    background: transparent !important;
    animation: none !important;
}

/* Conteneurs transparents */
.page-container {
    background: transparent !important;
}

/* Cartes avec fond blanc semi-opaque en mode jour */
html body .modern-card,
html body .filters-bar,
html body .modal-content {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px) !important;
}

/* Mode Nuit - Cartes avec fond sombre */
html body.night-mode .modern-card,
html body.night-mode .filters-bar,
html body.night-mode .modal-content,
html body.dark-mode .modern-card,
html body.dark-mode .filters-bar,
html body.dark-mode .modal-content {
    background: rgba(30, 41, 59, 0.95) !important;
}

/* #animated-bg pour le mode nuit */
#animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.5s ease;
    background-color: #0f172a;
}

body.night-mode #animated-bg,
body.dark-mode #animated-bg {
    opacity: 1;
}

#animated-bg::before,
#animated-bg::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

#animated-bg::before {
    background: radial-gradient(circle at 20% 30%, rgba(76, 29, 149, 0.4), transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.3), transparent 50%);
    animation: moveBackground1 25s ease-in-out infinite alternate;
}

#animated-bg::after {
    background: radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.3), transparent 45%),
                radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.25), transparent 45%);
    animation: moveBackground2 30s ease-in-out infinite alternate-reverse;
}

@keyframes moveBackground1 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.1) translate(30px, -20px); }
    100% { transform: scale(1) translate(-20px, 20px); }
}

@keyframes moveBackground2 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.15) translate(-30px, 25px); }
    100% { transform: scale(1) translate(20px, -20px); }
}

/* ====================================================================
   MOBILE - MASQUER NAVBAR DESKTOP
==================================================================== */
@media (max-width: 991px) {
    #desktop-navbar,
    nav#desktop-navbar {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    
    .page-container {
        padding: 1rem !important;
        padding-bottom: 100px !important; /* Espace pour le dock mobile */
    }
}

/* Boutons avec fond solide */
html body .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
}

html body .btn-outline-primary {
    background: transparent !important;
    border: 2px solid #667eea !important;
    color: #667eea !important;
}

html body .btn-outline-danger {
    background: transparent !important;
    border: 2px solid #ef4444 !important;
    color: #ef4444 !important;
}

/* ====================================================================
   FIX NAVBAR SERVO - DESKTOP
==================================================================== */
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
        height: 60px !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 60px !important;
        padding: 0.5rem 1.5rem !important;
        min-height: 60px !important;
        position: relative !important;
    }
    
    /* Logo SERVO - CENTRÉ */
    .servo-logo-container {
        position: fixed !important;
        left: 50% !important;
        top: 30px !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1031 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 40px !important;
    }
    
    /* S'assurer que le loader SERVO et tous les SVG sont visibles */
    .servo-logo-container .loader,
    .servo-logo-container svg,
    .servo-logo-container path {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center !important;
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
    
    /* Padding pour le body - réduit pour moins d'espace */
    body {
        padding-top: 60px !important;
    }
}
</style>

<!-- Animated Background for Night Mode -->
<div id="animated-bg"></div>

<!-- Loader -->
<div id="pageLoader" class="loader-overlay">
    <div class="loader-spinner"></div>
</div>

<div class="page-container" id="mainContent" style="opacity: 0; transition: opacity 0.5s ease;">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-users-cog"></i>
            Gestion des Utilisateurs
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-plus me-2"></i>Nouvel Utilisateur
        </button>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <form method="get" action="index.php" class="row g-3 align-items-center">
            <input type="hidden" name="page" value="employes">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--border-color); color: var(--text-secondary);">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0" 
                           placeholder="Rechercher un employé..." value="<?php echo htmlspecialchars($q); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="role">
                    <option value="">Tous les rôles</option>
                    <option value="technicien" <?php echo $role_filter==='technicien'?'selected':''; ?>>Technicien</option>
                    <option value="admin" <?php echo $role_filter==='admin'?'selected':''; ?>>Administrateur</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Liste Employés -->
    <?php if (empty($employees)): ?>
        <div class="text-center py-5">
            <div style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;">
                <i class="fas fa-user-slash"></i>
            </div>
            <h3 style="color: var(--text-main);">Aucun utilisateur trouvé</h3>
            <p style="color: var(--text-secondary);">Essayez de modifier vos filtres ou ajoutez un nouvel utilisateur.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($employees as $employee): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="modern-card">
                        <div class="employee-header">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
                            </div>
                            <div class="employee-info">
                                <h5><?php echo htmlspecialchars($employee['full_name']); ?></h5>
                                <span class="employee-role">
                                    <i class="fas <?php echo $employee['role']==='admin' ? 'fa-user-shield' : 'fa-tools'; ?> me-1"></i>
                                    <?php echo ucfirst($employee['role']); ?>
                                </span>
                            </div>
                            <?php if ($employee['role'] === 'admin'): ?>
                                <span class="badge bg-danger ms-auto">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info ms-auto">Tech</span>
                            <?php endif; ?>
                        </div>

                        <div class="employee-stats">
                            <div class="stat-item stat-clickable" data-employe-id="<?php echo $employee['id']; ?>" data-type="all" data-employe-name="<?php echo htmlspecialchars($employee['full_name']); ?>" style="cursor: pointer;" title="Cliquez pour voir les réparations">
                                <span class="stat-value"><?php echo (int)$employee['total_reparations']; ?></span>
                                <span class="stat-label">Total Réparations</span>
                            </div>
                            <div class="stat-item stat-clickable" data-employe-id="<?php echo $employee['id']; ?>" data-type="30days" data-employe-name="<?php echo htmlspecialchars($employee['full_name']); ?>" style="cursor: pointer;" title="Cliquez pour voir les réparations des 30 derniers jours">
                                <span class="stat-value"><?php echo (int)$employee['reparations_30j']; ?></span>
                                <span class="stat-label">30 Derniers Jours</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo round($employee['heures_mois'] ?? 0, 1); ?>h</span>
                                <span class="stat-label">Heures (Mois)</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo (int)($employee['pointages_mois'] ?? 0); ?></span>
                                <span class="stat-label">Pointages</span>
                            </div>
                        </div>

                        <div class="employee-actions">
                            <button type="button" data-user-id="<?php echo $employee['id']; ?>" class="btn btn-outline-primary flex-grow-1 edit-user-btn">
                                <i class="fas fa-edit me-2"></i>Modifier
                            </button>
                            <?php if ($employee['username'] !== 'admin'): ?>
                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $employee['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Template Edit (Hidden) -->
                        <div id="tmpl-edit-user-<?php echo $employee['id']; ?>" class="d-none">
                            <form id="editEmployeeForm">
                                <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nom d'utilisateur</label>
                                    <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($employee['username']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mot de passe (laisser vide si inchangé)</label>
                                    <input type="password" class="form-control" name="password">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rôle</label>
                                    <select class="form-select" name="role" required>
                                        <option value="technicien" <?php echo $employee['role']==='technicien'?'selected':''; ?>>Technicien</option>
                                        <option value="admin" <?php echo $employee['role']==='admin'?'selected':''; ?>>Administrateur</option>
                                    </select>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Ajout -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvel Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="addEmployeeErrors" class="alert alert-danger d-none"></div>
                <form id="addEmployeeForm" method="POST">
                    <input type="hidden" name="add_employee" value="1">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle *</label>
                        <select class="form-select" name="role" required>
                            <option value="technicien">Technicien</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edition -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editEmployeeModalBody">
                <!-- Content loaded via JS -->
            </div>
        </div>
    </div>
</div>

<script>
// Loader
window.addEventListener('load', function() {
    setTimeout(function() {
        document.getElementById('pageLoader').style.opacity = '0';
        setTimeout(function() {
            document.getElementById('pageLoader').style.display = 'none';
            document.getElementById('mainContent').style.opacity = '1';
        }, 500);
    }, 500);
});

// Delete Confirmation
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        window.location.href = 'index.php?page=employes&delete=' + id;
    }
}

// Edit Modal Logic
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.edit-user-btn');
    if (!btn) return;
    
    const userId = btn.getAttribute('data-user-id');
    const modalEl = document.getElementById('editEmployeeModal');
    const modalBody = document.getElementById('editEmployeeModalBody');
    
    // Show modal
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
    
    // Load content
    const tpl = document.getElementById('tmpl-edit-user-' + userId);
    if (tpl) {
        modalBody.innerHTML = tpl.innerHTML;
    } else {
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    }
});

// Form Submissions (AJAX)
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.id === 'editEmployeeForm') {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('ajax/update_employe.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Erreur');
                }
            });
    }
});

// Click on stat-items to show repairs
document.addEventListener('click', function(e) {
    const statItem = e.target.closest('.stat-clickable');
    if (!statItem) return;
    
    const employeId = statItem.getAttribute('data-employe-id');
    const type = statItem.getAttribute('data-type');
    const employeName = statItem.getAttribute('data-employe-name');
    
    // Stocker l'ID employé pour l'interface IA
    window.currentEmployeeIdForAI = employeId;
    
    // Update modal title
    const titleEl = document.getElementById('repairsModalTitle');
    if (type === '30days') {
        titleEl.innerHTML = '<i class="fas fa-tools me-2"></i>Réparations de ' + employeName + ' (30 derniers jours)';
    } else {
        titleEl.innerHTML = '<i class="fas fa-tools me-2"></i>Toutes les réparations de ' + employeName;
    }
    
    // Show modal with loading
    const modalEl = document.getElementById('repairsListModal');
    const modalBody = document.getElementById('repairsListBody');
    modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div><p class="mt-3" style="color: var(--text-secondary);">Chargement des réparations...</p></div>';
    
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
    
    // Fetch repairs via AJAX
    const url = 'ajax/get_employe_reparations.php?employe_id=' + employeId + '&type=' + type;
    console.log('DEBUG: Fetching URL:', url);
    
    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.reparations.length > 0) {
                // Store data for pagination
                window.repairsData = res.reparations;
                window.repairsCurrentPage = 1;
                window.repairsPerPage = 10;
                renderRepairsTable();
            } else if (res.success && res.reparations.length === 0) {
                modalBody.innerHTML = '<div style="text-align: center; padding: 3rem;"><i class="fas fa-inbox" style="font-size: 3rem; color: #94a3b8; margin-bottom: 1rem; display: block;"></i><p style="color: #94a3b8;">Aucune réparation trouvée</p></div>';
            } else {
                modalBody.innerHTML = '<div style="text-align: center; padding: 3rem; color: #ef4444;"><i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i><p>' + (res.message || 'Erreur lors du chargement') + '</p></div>';
            }
        })
        .catch(err => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 3rem; color: #ef4444;"><i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i><p>Erreur: ' + err.message + '</p></div>';
        });
});

// Render repairs table with pagination
function renderRepairsTable() {
    const data = window.repairsData;
    const page = window.repairsCurrentPage;
    const perPage = window.repairsPerPage;
    const totalPages = Math.ceil(data.length / perPage);
    const start = (page - 1) * perPage;
    const end = start + perPage;
    const pageData = data.slice(start, end);
    
    let html = '<table class="custom-repairs-table">';
    html += '<thead><tr><th>#</th><th>Date</th><th>Modèle</th><th>Problème</th><th>Statut</th></tr></thead>';
    html += '<tbody>';
    
    pageData.forEach(rep => {
        html += '<tr>';
        html += '<td><span class="repair-id-badge">' + rep.id + '</span></td>';
        html += '<td>' + rep.date + '</td>';
        html += '<td class="repair-modele-cell">' + rep.modele + '</td>';
        html += '<td class="repair-problem-cell">' + rep.probleme + '</td>';
        html += '<td><span class="status-pill" style="background-color: ' + rep.statut_couleur + '">' + rep.statut + '</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    
    // Pagination
    html += '<div class="repairs-pagination">';
    html += '<button onclick="changeRepairsPage(-1)" ' + (page <= 1 ? 'disabled' : '') + '><i class="fas fa-chevron-left"></i> Précédent</button>';
    html += '<span class="page-info">Page ' + page + ' / ' + totalPages + ' (' + data.length + ' réparations)</span>';
    html += '<button onclick="changeRepairsPage(1)" ' + (page >= totalPages ? 'disabled' : '') + '>Suivant <i class="fas fa-chevron-right"></i></button>';
    html += '</div>';
    
    document.getElementById('repairsListBody').innerHTML = html;
}

// Change page for repairs
function changeRepairsPage(direction) {
    const totalPages = Math.ceil(window.repairsData.length / window.repairsPerPage);
    window.repairsCurrentPage += direction;
    if (window.repairsCurrentPage < 1) window.repairsCurrentPage = 1;
    if (window.repairsCurrentPage > totalPages) window.repairsCurrentPage = totalPages;
    renderRepairsTable();
}
</script>

<!-- Modal Liste des Réparations -->
<div class="modal fade" id="repairsListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="repairsModalTitle">
                    <i class="fas fa-tools me-2"></i>Réparations
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-ai-analysis" onclick="openAIModal()" title="Analyse IA de l'employé">
                        <i class="fas fa-robot me-1"></i>
                        <span class="d-none d-md-inline">Analyse IA</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body" id="repairsListBody">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Analyse IA (modal séparé plein écran) -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered">
        <div class="modal-content ai-modal-content">
            <!-- Header IA -->
            <div class="modal-header ai-modal-header">
                <div class="ai-modal-title-wrapper">
                    <div class="ai-modal-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="ai-modal-title-text">
                        <h5 class="modal-title">Assistant IA</h5>
                        <span class="ai-modal-subtitle" id="aiModalEmployeeName">Analyse Employé</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="closeAIModal()" aria-label="Close"></button>
            </div>
            
            <!-- Corps de la conversation -->
            <div class="modal-body ai-modal-body">
                <div id="aiConversation" class="ai-conversation-full">
                    <div class="ai-welcome-centered">
                        <div class="ai-welcome-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h4>Assistant d'Analyse IA</h4>
                        <p>Posez une question sur cet employé ou lancez une analyse complète pour obtenir des insights détaillés sur ses performances.</p>
                        
                        <div class="ai-quick-actions-centered">
                            <button type="button" class="ai-action-card" onclick="sendAIMessage('Analyse complète')">
                                <i class="fas fa-chart-line"></i>
                                <span>Analyse complète</span>
                            </button>
                            <button type="button" class="ai-action-card" onclick="sendAIMessage('Quels sont les points forts de cet employé ?')">
                                <i class="fas fa-star"></i>
                                <span>Points forts</span>
                            </button>
                            <button type="button" class="ai-action-card" onclick="sendAIMessage('Quels objectifs suggères-tu pour cet employé ?')">
                                <i class="fas fa-bullseye"></i>
                                <span>Objectifs</span>
                            </button>
                            <button type="button" class="ai-action-card" onclick="sendAIMessage('Analyse la productivité de cet employé')">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Productivité</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Zone de saisie -->
            <div class="modal-footer ai-modal-footer">
                <div class="ai-input-container">
                    <input type="text" id="aiUserInput" class="ai-input-full" placeholder="Posez une question sur cet employé..." 
                           onkeypress="if(event.key==='Enter') sendAIMessage()">
                    <button type="button" class="ai-send-btn-full" onclick="sendAIMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ====================================================================
   MODAL RÉPARATIONS - DESIGN CUSTOM SANS BOOTSTRAP
==================================================================== */
/* Stat clickable */
.stat-clickable:hover {
    background: linear-gradient(135deg, rgba(102,126,234,0.2) 0%, rgba(118,75,162,0.15) 100%) !important;
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 20px rgba(102,126,234,0.2);
}

/* Modal position fix - éviter d'être coupé par la navbar */
#repairsListModal .modal-dialog {
    margin-top: 90px !important;
    max-height: calc(100vh - 100px);
}

#repairsListModal .modal-content {
    max-height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
}

#repairsListModal .modal-body {
    overflow-y: auto;
    flex: 1;
}

/* Tableau custom sans Bootstrap */
.custom-repairs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    border-radius: 12px;
    overflow: hidden;
}

.custom-repairs-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.custom-repairs-table th {
    color: white;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 1rem 0.75rem;
    text-align: left;
    border: none;
}

.custom-repairs-table tbody tr {
    background: #fff;
    transition: background 0.2s ease;
}

.custom-repairs-table tbody tr:nth-child(even) {
    background: #f8fafc;
}

.custom-repairs-table tbody tr:hover {
    background: rgba(102,126,234,0.08);
}

.custom-repairs-table td {
    padding: 0.85rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
    vertical-align: middle;
}

.repair-id-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    display: inline-block;
}

.repair-modele-cell {
    font-weight: 600;
    color: #475569;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.repair-problem-cell {
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #64748b;
}

.status-pill {
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

/* Pagination */
.repairs-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.repairs-pagination button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.repairs-pagination button:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
}

.repairs-pagination button:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}

.repairs-pagination .page-info {
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 500;
    margin: 0 1rem;
}

/* ====================================================================
   MODE NUIT - MODAL RÉPARATIONS
==================================================================== */
body.night-mode #repairsListModal .modal-content {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    border: 1px solid rgba(139,92,246,0.3) !important;
    color: #f1f5f9 !important;
}

body.night-mode #repairsListModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: 1px solid rgba(139,92,246,0.3) !important;
    color: white !important;
}

body.night-mode #repairsListModal .modal-header .btn-close {
    filter: brightness(0) invert(1) !important;
}

body.night-mode #repairsListModal .modal-body {
    background: transparent !important;
    color: #f1f5f9 !important;
}

body.night-mode .custom-repairs-table tbody tr {
    background: #1e293b;
}

body.night-mode .custom-repairs-table tbody tr:nth-child(even) {
    background: #0f172a;
}

body.night-mode .custom-repairs-table tbody tr:hover {
    background: rgba(139,92,246,0.15);
}

body.night-mode .custom-repairs-table td {
    color: #e2e8f0;
    border-bottom-color: #334155;
}

body.night-mode .repair-modele-cell {
    color: #a5b4fc;
}

body.night-mode .repair-problem-cell {
    color: #94a3b8;
}

body.night-mode .repairs-pagination {
    border-top-color: #334155;
}

body.night-mode .repairs-pagination .page-info {
    color: #94a3b8;
}

body.night-mode .repairs-pagination button:disabled {
    background: #334155;
    color: #64748b;
}

/* ====================================================================
   MODAL IA SÉPARÉ - DESIGN MODERNE
==================================================================== */

/* Bouton Analyse IA */
.btn-ai-analysis {
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-ai-analysis:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    color: white;
}

.btn-ai-analysis i {
    font-size: 1rem;
}

/* Modal IA Content */
.ai-modal-content {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    overflow: hidden;
}

/* Header Modal IA */
.ai-modal-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 50%, #4f46e5 100%);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 1rem 1.5rem;
}

.ai-modal-title-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ai-modal-icon {
    width: 45px;
    height: 45px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.ai-modal-title-text h5 {
    margin: 0;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
}

.ai-modal-subtitle {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.7);
}

/* Body Modal IA */
.ai-modal-body {
    background: linear-gradient(180deg, #0f0a29 0%, #1a1640 100%);
    min-height: 400px;
    max-height: 60vh;
    overflow-y: auto;
    padding: 2rem;
}

/* Zone de conversation plein écran */
.ai-conversation-full {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Écran de bienvenue centré */
.ai-welcome-centered {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem;
}

.ai-welcome-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    margin-bottom: 1.5rem;
    box-shadow: 0 0 40px rgba(139, 92, 246, 0.4);
}

.ai-welcome-centered h4 {
    color: white;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.ai-welcome-centered p {
    color: rgba(255,255,255,0.6);
    max-width: 500px;
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Actions rapides en grille */
.ai-quick-actions-centered {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    max-width: 500px;
}

@media (max-width: 576px) {
    .ai-quick-actions-centered {
        grid-template-columns: 1fr;
    }
}

.ai-action-card {
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
}

.ai-action-card:hover {
    background: rgba(139, 92, 246, 0.25);
    border-color: rgba(139, 92, 246, 0.6);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
}

.ai-action-card i {
    font-size: 1.5rem;
    color: #a78bfa;
}

.ai-action-card span {
    font-weight: 500;
    font-size: 0.9rem;
}

/* Footer Modal IA */
.ai-modal-footer {
    background: rgba(15, 10, 41, 0.9);
    border-top: 1px solid rgba(139, 92, 246, 0.2);
    padding: 1rem 1.5rem;
}

.ai-input-container {
    display: flex;
    gap: 0.75rem;
    width: 100%;
}

.ai-input-full {
    flex: 1;
    padding: 1rem 1.25rem;
    background: rgba(139, 92, 246, 0.1);
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 12px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.ai-input-full:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
    background: rgba(139, 92, 246, 0.15);
}

.ai-input-full::placeholder {
    color: rgba(255,255,255,0.4);
}

.ai-send-btn-full {
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    border: none;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.ai-send-btn-full:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
}

/* Messages IA dans le modal */
.ai-message {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-message.user {
    flex-direction: row-reverse;
}

.ai-message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.ai-message.assistant .ai-message-avatar {
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    color: white;
}

.ai-message.user .ai-message-avatar {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.ai-message-content {
    background: rgba(30, 27, 75, 0.8);
    border: 1px solid rgba(139, 92, 246, 0.2);
    padding: 1rem 1.25rem;
    border-radius: 16px;
    max-width: 80%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    color: #e2e8f0;
}

.ai-message.user .ai-message-content {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
}

.ai-message-content p {
    margin: 0;
    line-height: 1.7;
    white-space: pre-wrap;
}

/* Indicateur de chargement */
.ai-loading {
    display: flex;
    gap: 0.4rem;
    padding: 0.5rem;
}

.ai-loading span {
    width: 10px;
    height: 10px;
    background: #8b5cf6;
    border-radius: 50%;
    animation: aiTyping 1.4s infinite ease-in-out both;
}

.ai-loading span:nth-child(1) { animation-delay: -0.32s; }
.ai-loading span:nth-child(2) { animation-delay: -0.16s; }

@keyframes aiTyping {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Mode Jour pour le modal IA (optionnel) */
body:not(.night-mode) .ai-modal-content {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 50%, #f5f3ff 100%);
}

body:not(.night-mode) .ai-modal-body {
    background: linear-gradient(180deg, #faf5ff 0%, #f0f9ff 100%);
}

body:not(.night-mode) .ai-welcome-centered h4 {
    color: #1e1b4b;
}

body:not(.night-mode) .ai-welcome-centered p {
    color: #64748b;
}

body:not(.night-mode) .ai-action-card {
    background: white;
    border-color: rgba(139, 92, 246, 0.2);
    color: #1e1b4b;
}

body:not(.night-mode) .ai-action-card:hover {
    background: rgba(139, 92, 246, 0.1);
}

body:not(.night-mode) .ai-modal-footer {
    background: white;
}

body:not(.night-mode) .ai-input-full {
    background: white;
    border-color: #e2e8f0;
    color: #1e1b4b;
}

body:not(.night-mode) .ai-input-full::placeholder {
    color: #94a3b8;
}

body:not(.night-mode) .ai-message-content {
    background: white;
    border-color: #e2e8f0;
    color: #1e1b4b;
}
</style>

<script>
// ====================================================================
// INTERFACE IA - ANALYSE EMPLOYÉ
// ====================================================================

// Variables globales pour l'IA
let aiCurrentEmployeeId = null;
let aiConversationHistory = [];
let aiIsLoading = false;

/**
 * Ouvre le modal IA séparé
 */
function openAIModal() {
    // Récupérer l'ID employé
    aiCurrentEmployeeId = window.currentEmployeeIdForAI || null;
    
    console.log('=== OPEN AI MODAL ===');
    console.log('Employee ID:', aiCurrentEmployeeId);
    
    if (!aiCurrentEmployeeId) {
        alert("Impossible de trouver l'ID de l'employé");
        return;
    }
    
    // Mettre à jour le nom de l'employé dans le header
    const titleEl = document.getElementById('repairsModalTitle');
    const employeeName = titleEl ? titleEl.textContent.replace(/.*de\s+/i, '').replace(/\s*\(.*\).*/, '').trim() : 'Employé';
    document.getElementById('aiModalEmployeeName').textContent = 'Analyse de ' + employeeName;
    
    // Réinitialiser la conversation
    resetAIConversation();
    
    // Ouvrir le modal IA
    const aiModal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    aiModal.show();
}

/**
 * Ferme le modal IA
 */
function closeAIModal() {
    const aiModalEl = document.getElementById('aiAnalysisModal');
    const aiModal = bootstrap.Modal.getInstance(aiModalEl);
    if (aiModal) {
        aiModal.hide();
    }
}

/**
 * Envoie un message à l'IA
 */
async function sendAIMessage(customPrompt = null) {
    if (aiIsLoading) return;
    
    const input = document.getElementById('aiUserInput');
    const prompt = customPrompt || input.value.trim();
    
    if (!prompt) return;
    
    // DEBUG
    console.log('=== AI MESSAGE DEBUG ===');
    console.log('Employee ID:', aiCurrentEmployeeId);
    console.log('Prompt:', prompt);
    console.log('window.currentEmployeeIdForAI:', window.currentEmployeeIdForAI);
    
    // Vérifier l'ID employé
    if (!aiCurrentEmployeeId) {
        // Essayer de récupérer depuis window
        aiCurrentEmployeeId = window.currentEmployeeIdForAI;
        console.log('Récupéré depuis window:', aiCurrentEmployeeId);
    }
    
    if (!aiCurrentEmployeeId) {
        console.error('ERREUR: ID employé non trouvé!');
        showAIError("Impossible de trouver l'ID de l'employé");
        return;
    }
    
    // Vider l'input si c'est une saisie manuelle
    if (!customPrompt) {
        input.value = '';
    }
    
    aiIsLoading = true;
    
    // Afficher le message utilisateur
    addAIMessage('user', prompt);
    
    // Afficher l'indicateur de chargement
    showAILoading();
    
    try {
        const formData = new FormData();
        formData.append('employee_id', aiCurrentEmployeeId);
        formData.append('prompt', prompt);
        formData.append('conversation_history', JSON.stringify(aiConversationHistory));
        
        console.log('Envoi requête à /ajax/employee_ai_analysis.php...');
        
        const response = await fetch('/ajax/employee_ai_analysis.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Important pour les cookies de session
        });
        
        console.log('Response status:', response.status);
        
        const data = await response.json();
        
        console.log('Response data:', data);
        
        // Si debug présent, l'afficher
        if (data.debug) {
            console.log('DEBUG info:', data.debug);
        }
        
        // Retirer l'indicateur de chargement
        hideAILoading();
        
        if (data.success) {
            // Mettre à jour l'historique
            aiConversationHistory = data.conversation_history || [];
            
            // Afficher la réponse
            addAIMessage('assistant', data.analysis);
        } else {
            console.error('Erreur API:', data.error);
            showAIError(data.error || "Erreur lors de l'analyse");
        }
        
    } catch (error) {
        console.error('Erreur IA catch:', error);
        hideAILoading();
        showAIError("Erreur de connexion au serveur IA");
    }
    
    aiIsLoading = false;
}

/**
 * Ajoute un message à la conversation
 */
function addAIMessage(role, content) {
    const conv = document.getElementById('aiConversation');
    
    // Cacher le message de bienvenue s'il existe
    const welcome = conv.querySelector('.ai-welcome');
    if (welcome) {
        welcome.style.display = 'none';
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `ai-message ${role}`;
    
    const avatar = role === 'assistant' 
        ? '<i class="fas fa-robot"></i>' 
        : '<i class="fas fa-user"></i>';
    
    // Formater le contenu (convertir les emojis et retours à la ligne)
    const formattedContent = formatAIContent(content);
    
    messageDiv.innerHTML = `
        <div class="ai-message-avatar">${avatar}</div>
        <div class="ai-message-content">
            <p>${formattedContent}</p>
        </div>
    `;
    
    conv.appendChild(messageDiv);
    
    // Scroller vers le bas
    conv.scrollTop = conv.scrollHeight;
}

/**
 * Formate le contenu de l'IA (markdown basique)
 */
function formatAIContent(content) {
    // Échapper le HTML
    content = content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    
    // Convertir les retours à la ligne
    content = content.replace(/\n/g, '<br>');
    
    // Gras **texte**
    content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Italique *texte*
    content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
    
    return content;
}

/**
 * Affiche l'indicateur de chargement
 */
function showAILoading() {
    const conv = document.getElementById('aiConversation');
    
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'aiLoadingIndicator';
    loadingDiv.className = 'ai-message assistant';
    loadingDiv.innerHTML = `
        <div class="ai-message-avatar"><i class="fas fa-robot"></i></div>
        <div class="ai-message-content">
            <div class="ai-loading">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    `;
    
    conv.appendChild(loadingDiv);
    conv.scrollTop = conv.scrollHeight;
}

/**
 * Cache l'indicateur de chargement
 */
function hideAILoading() {
    const loading = document.getElementById('aiLoadingIndicator');
    if (loading) {
        loading.remove();
    }
}

/**
 * Affiche une erreur
 */
function showAIError(message) {
    const conv = document.getElementById('aiConversation');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'ai-message assistant';
    errorDiv.innerHTML = `
        <div class="ai-message-avatar" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="ai-message-content" style="border-left: 3px solid #ef4444;">
            <p>⚠️ ${message}</p>
        </div>
    `;
    
    conv.appendChild(errorDiv);
    conv.scrollTop = conv.scrollHeight;
}

/**
 * Réinitialise la conversation IA
 */
function resetAIConversation() {
    aiConversationHistory = [];
    const conv = document.getElementById('aiConversation');
    conv.innerHTML = `
        <div class="ai-welcome-centered">
            <div class="ai-welcome-icon">
                <i class="fas fa-brain"></i>
            </div>
            <h4>Assistant d'Analyse IA</h4>
            <p>Posez une question sur cet employé ou lancez une analyse complète pour obtenir des insights détaillés sur ses performances.</p>
            
            <div class="ai-quick-actions-centered">
                <button type="button" class="ai-action-card" onclick="sendAIMessage('Analyse complète')">
                    <i class="fas fa-chart-line"></i>
                    <span>Analyse complète</span>
                </button>
                <button type="button" class="ai-action-card" onclick="sendAIMessage('Quels sont les points forts de cet employé ?')">
                    <i class="fas fa-star"></i>
                    <span>Points forts</span>
                </button>
                <button type="button" class="ai-action-card" onclick="sendAIMessage('Quels objectifs suggères-tu pour cet employé ?')">
                    <i class="fas fa-bullseye"></i>
                    <span>Objectifs</span>
                </button>
                <button type="button" class="ai-action-card" onclick="sendAIMessage('Analyse la productivité de cet employé')">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Productivité</span>
                </button>
            </div>
        </div>
    `;
}

// Réinitialiser la conversation quand le modal IA est fermé
document.addEventListener('DOMContentLoaded', function() {
    const aiModalEl = document.getElementById('aiAnalysisModal');
    if (aiModalEl) {
        aiModalEl.addEventListener('hidden.bs.modal', function() {
            resetAIConversation();
        });
    }
});
</script>