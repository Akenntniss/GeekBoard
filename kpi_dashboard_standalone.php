<?php
/**
 * Dashboard KPI Standalone - GeekBoard
 * Version autonome avec mode jour/nuit automatique
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Vérification authentification  
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur';

if (!$user_id) {
    header('Location: /pages/login.php');
    exit();
}

$is_admin = ($user_role === 'admin');

// Récupérer les utilisateurs (pour filtres)
$users = [];
if ($is_admin) {
    try {
        $pdo = getShopDBConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'technicien') ORDER BY full_name");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération users: " . $e->getMessage());
    }
}

$page_title = "Dashboard KPI";
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0078e8">
    <title><?php echo $page_title; ?> - GeekBoard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --primary: #0078e8;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        /* Mode Jour (défaut) */
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        /* Mode Nuit */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1d3a 100%);
            color: #e0e6ed;
        }
        
        /* Navbar */
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .navbar-custom {
            background: linear-gradient(135deg, #1e2139 0%, #2a2d4a 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary);
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        [data-theme="dark"] .navbar-brand {
            color: #60a5fa;
        }
        
        /* Alignement icônes navbar */
        .navbar-custom .container-fluid {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .navbar-brand {
            display: inline-flex;
            align-items: center;
            margin: 0;
            padding: 0.5rem 0;
            line-height: 1;
            text-decoration: none;
        }
        
        .navbar-brand:hover {
            text-decoration: none;
        }
        
        .navbar-brand i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .navbar-custom .d-flex {
            display: flex;
            align-items: center;
        }
        
        .navbar-custom .text-muted {
            display: inline-flex;
            align-items: center;
        }
        
        .navbar-custom .text-muted i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Toggle Theme Button */
        .theme-toggle {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 50px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .theme-toggle:hover {
            background: var(--primary);
            color: white;
            transform: rotate(180deg);
        }
        
        [data-theme="dark"] .theme-toggle {
            border-color: #60a5fa;
            color: #60a5fa;
        }
        
        [data-theme="dark"] .theme-toggle:hover {
            background: #60a5fa;
            color: #0a0e27;
        }
        
        /* Container */
        .container-fluid {
            padding: 2rem;
            max-width: 1400px;
        }
        
        /* Onglets Navigation */
        .nav-tabs-custom {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .nav-tabs-custom {
            background: linear-gradient(135deg, #1e2139 0%, #2a2d4a 100%);
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            padding: 12px 24px;
            border-radius: 10px;
            margin-right: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-tabs-custom .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .nav-tabs-custom .nav-link:hover {
            background: rgba(0, 120, 232, 0.1);
            color: var(--primary);
        }
        
        .nav-tabs-custom .nav-link:hover::before {
            width: 100%;
        }
        
        .nav-tabs-custom .nav-link.active {
            background: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 120, 232, 0.3);
        }
        
        [data-theme="dark"] .nav-tabs-custom .nav-link {
            color: #a0aec0;
        }
        
        [data-theme="dark"] .nav-tabs-custom .nav-link:hover {
            background: rgba(96, 165, 250, 0.15);
            color: #60a5fa;
        }
        
        [data-theme="dark"] .nav-tabs-custom .nav-link.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
        }
        
        /* Cards KPI */
        .kpi-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        [data-theme="dark"] .kpi-card {
            background: linear-gradient(135deg, #1e2139 0%, #2a2d4a 100%);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border-color: rgba(96, 165, 250, 0.2);
        }
        
        [data-theme="dark"] .kpi-card:hover {
            box-shadow: 0 10px 35px rgba(59, 130, 246, 0.2);
        }
        
        .kpi-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        [data-theme="dark"] .kpi-label {
            color: #94a3b8;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            background: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        [data-theme="dark"] .kpi-value {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }
        
        /* Tables */
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        [data-theme="dark"] .table {
            background: #1e2139;
            color: #e0e6ed;
        }
        
        [data-theme="dark"] .table thead {
            background: #2a2d4a;
        }
        
        [data-theme="dark"] .table tbody tr:hover {
            background: rgba(96, 165, 250, 0.1);
        }
        
        /* Loading Overlay */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        
        #loadingOverlay.show {
            display: flex;
        }
        
        [data-theme="dark"] #loadingOverlay {
            background: rgba(10, 14, 39, 0.95);
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
        }
        
        /* Accordéon */
        .accordion-item {
            background: white;
            border: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 0.5rem;
            border-radius: 10px !important;
            overflow: hidden;
        }
        
        [data-theme="dark"] .accordion-item {
            background: #1e2139;
            border-color: rgba(96, 165, 250, 0.2);
        }
        
        .accordion-button {
            background: white;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
            color: white;
        }
        
        [data-theme="dark"] .accordion-button {
            background: #2a2d4a;
            color: #e0e6ed;
        }
        
        [data-theme="dark"] .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 120, 232, 0.4);
        }
        
        [data-theme="dark"] .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 120, 232, 0.15);
        }
        
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background: #2a2d4a;
            border-color: rgba(96, 165, 250, 0.2);
            color: #e0e6ed;
        }
        
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background: #1e2139;
            border-color: #60a5fa;
            box-shadow: 0 0 0 0.25rem rgba(96, 165, 250, 0.25);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-pane {
            animation: fadeIn 0.5s ease;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .kpi-value {
                font-size: 1.5rem;
            }
            
            .nav-tabs-custom .nav-link {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }
        
        /* AI Profile Cards - Futuristic Design */
        .ai-profile-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .ai-profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,120,232,0.1) 0%, rgba(0,212,255,0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 20px;
        }
        
        .ai-profile-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 120, 232, 0.3);
            border-color: rgba(0, 212, 255, 0.5);
        }
        
        .ai-profile-card:hover::before {
            opacity: 1;
        }
        
        [data-theme="dark"] .ai-profile-card {
            background: rgba(30, 33, 57, 0.8);
            border-color: rgba(96, 165, 250, 0.3);
        }
        
        .ai-profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .ai-avatar-container {
            width: 100px;
            height: 100px;
            position: relative;
        }
        
        .ai-avatar-container svg {
            filter: drop-shadow(0 0 20px currentColor);
        }
        
        .ai-profile-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .ai-profile-status.active {
            background: linear-gradient(135deg, #28a745 0%, #00ff88 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        
        .ai-profile-status.inactive {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        .ai-profile-body {
            position: relative;
            z-index: 1;
            margin: 1.5rem 0;
        }
        
        .ai-profile-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            background: linear-gradient(135deg, #0078e8 0%, #00d4ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        [data-theme="dark"] .ai-profile-name {
            background: linear-gradient(135deg, #60a5fa 0%, #00e5ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .ai-profile-description {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 0;
        }
        
        [data-theme="dark"] .ai-profile-description {
            color: #94a3b8;
        }
        
        .ai-profile-footer {
            position: relative;
            z-index: 1;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] .ai-profile-footer {
            border-top-color: rgba(96, 165, 250, 0.2);
        }
        
        .btn-ai-action {
            width: 100%;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-ai-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-ai-action:hover::before {
            left: 100%;
        }
        
        .btn-ai-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 120, 232, 0.4);
        }
        
        [data-theme="dark"] .btn-ai-action {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        [data-theme="dark"] .btn-ai-action:hover {
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-custom fixed-top">
        <div class="container-fluid">
            <a href="/index.php" class="navbar-brand">
                <i class="fas fa-chart-line me-2"></i>
                SERVO KPI TRACKER
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($user_name); ?>
                </span>
                <button class="theme-toggle" id="themeToggle" title="Changer de thème">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-3">Chargement des données...</div>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="container-fluid" style="margin-top: 80px;">
        <?php
        // Indiquer au fichier inclus qu'il est en mode standalone (pour masquer sa navbar)
        $is_standalone_mode = true;
        
        // Inclure le contenu du dashboard
        include __DIR__ . '/pages/kpi_dashboard.php';
        ?>
    </div>
    
    <!-- Theme Toggle Script -->
    <script>
        // Système de thème automatique avec localStorage
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        // Fonction pour appliquer le thème
        function applyTheme(theme) {
            html.setAttribute('data-theme', theme);
            localStorage.setItem('kpi-theme', theme);
            
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
            
            console.log(`🎨 Thème appliqué: ${theme}`);
        }
        
        // Charger le thème sauvegardé ou détecter la préférence système
        function loadTheme() {
            const savedTheme = localStorage.getItem('kpi-theme');
            
            if (savedTheme) {
                applyTheme(savedTheme);
            } else {
                // Détecter la préférence système
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                applyTheme(prefersDark ? 'dark' : 'light');
            }
        }
        
        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
        
        // Écouter les changements de préférence système
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Ne changer automatiquement que si pas de préférence sauvegardée
            if (!localStorage.getItem('kpi-theme')) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
        
        // Initialiser au chargement
        loadTheme();
    </script>

    <!-- Modal Prévisualisation Prompt IA -->
    <div class="modal fade" id="promptPreviewModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Prévisualisation du Prompt IA
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous pouvez modifier le prompt ci-dessous ou cliquer sur une note pour l'insérer.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sticky-note me-2"></i>Notes Employé & Magasin
                            <small class="text-muted">(Cliquez pour insérer dans le prompt)</small>
                        </label>
                        <div id="notesGrid" class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background: #f8f9fa;">
                            <div class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin"></i> Chargement des notes...
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="promptEditor" class="form-label fw-bold">
                            <i class="fas fa-edit me-2"></i>Prompt Complet
                            <small class="text-muted">(éditable)</small>
                        </label>
                        <textarea 
                            id="promptEditor" 
                            class="form-control font-monospace" 
                            rows="20" 
                            style="font-size: 0.9rem; white-space: pre-wrap;"
                        ></textarea>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            Astuce : Modifiez le texte ou ajoutez des notes pour affiner l'analyse
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="confirmAndSendPrompt()">
                        <i class="fas fa-paper-plane me-2"></i>Lancer l'Analyse
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
