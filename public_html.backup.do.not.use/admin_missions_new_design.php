<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Missions - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Mode Jour */
            --bg-primary: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --bg-secondary: #ffffff;
            --bg-glass: rgba(255, 255, 255, 0.1);
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --accent-primary: #3b82f6;
            --accent-secondary: #8b5cf6;
            --border-color: rgba(203, 213, 225, 0.3);
            --shadow-color: rgba(0, 0, 0, 0.1);
            --glow-color: rgba(59, 130, 246, 0.3);
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            
            /* Transitions */
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        [data-theme="dark"] {
            /* Mode Nuit - Glass Morphism Modern */
            --bg-primary: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            --bg-secondary: rgba(30, 41, 59, 0.8);
            --bg-glass: rgba(255, 255, 255, 0.05);
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --accent-primary: #00d4ff;
            --accent-secondary: #8b5cf6;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
            --glow-color: rgba(0, 212, 255, 0.3);
            --success-color: #00ff88;
            --warning-color: #fbbf24;
            --danger-color: #ff6b6b;
            --info-color: #00d4ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            transition: var(--transition-smooth);
            position: relative;
            overflow-x: hidden;
        }

        /* Effet de fond animé pour mode nuit */
        [data-theme="dark"] body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(6, 182, 212, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 0.8; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }

        /* Header moderne */
        .modern-header {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 8px 32px var(--shadow-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
            transition: var(--transition-smooth);
        }

        .modern-header h1 {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            text-align: center;
            position: relative;
        }

        [data-theme="dark"] .modern-header h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
            border-radius: 2px;
            box-shadow: 0 0 20px var(--glow-color);
        }

        /* Toggle Button Ultra Moderne */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border: none;
            border-radius: 50%;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition-bounce);
            z-index: 1001;
            box-shadow: 
                0 8px 32px var(--shadow-color),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
        }

        .theme-toggle:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 
                0 12px 40px var(--shadow-color),
                0 0 30px var(--glow-color);
        }

        [data-theme="dark"] .theme-toggle {
            background: rgba(0, 212, 255, 0.1);
            border-color: var(--accent-primary);
        }

        /* Cartes Glass Morphism */
        .glass-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            margin: 1rem 0;
            box-shadow: 
                0 8px 32px var(--shadow-color),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-primary), transparent);
            opacity: 0.5;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 20px 50px var(--shadow-color),
                0 0 30px var(--glow-color);
        }

        [data-theme="dark"] .glass-card {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .glass-card:hover {
            background: rgba(30, 41, 59, 0.5);
            border-color: var(--accent-primary);
        }

        /* Boutons modernes */
        .btn-modern {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: var(--transition-bounce);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition-smooth);
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-modern:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 40px var(--glow-color);
        }

        /* Tableaux modernes */
        .table-modern {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px var(--shadow-color);
            border: 1px solid var(--border-color);
        }

        .table-modern th {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
            position: relative;
        }

        .table-modern td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            transition: var(--transition-smooth);
        }

        .table-modern tr:hover td {
            background: var(--bg-glass);
            transform: scale(1.01);
        }

        [data-theme="dark"] .table-modern tr:hover td {
            background: rgba(0, 212, 255, 0.1);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
        }

        /* Badges modernes */
        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
            transition: var(--transition-smooth);
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success-color), #00ff88);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning-color), #fbbf24);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--danger-color), #ff6b6b);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .badge-info {
            background: linear-gradient(135deg, var(--info-color), #00d4ff);
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }

        /* Modales modernes */
        .modal-content {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: 0 25px 50px var(--shadow-color);
        }

        [data-theme="dark"] .modal-content {
            background: rgba(15, 23, 42, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            border-bottom: none;
            border-radius: 20px 20px 0 0;
        }

        /* Formulaires modernes */
        .form-control {
            background: var(--bg-glass);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1rem;
            color: var(--text-primary);
            transition: var(--transition-smooth);
        }

        .form-control:focus {
            background: var(--bg-glass);
            border-color: var(--accent-primary);
            box-shadow: 0 0 20px var(--glow-color);
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-control {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        [data-theme="dark"] .form-control:focus {
            background: rgba(30, 41, 59, 0.5);
            border-color: var(--accent-primary);
        }

        /* Statistiques modernes */
        .stat-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px var(--shadow-color);
        }

        [data-theme="dark"] .stat-card {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .stat-card:hover {
            background: rgba(30, 41, 59, 0.5);
            box-shadow: 0 20px 50px rgba(0, 212, 255, 0.2);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        /* Animations d'entrée */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideInUp 0.6s ease-out;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .theme-toggle {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .glass-card {
                padding: 1rem;
                margin: 0.5rem 0;
            }
            
            .modern-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Décalage PC */
        @media (min-width: 992px) {
            .modern-dashboard {
                padding-top: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Toggle Button -->
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-sun" id="themeIcon"></i>
    </button>

    <!-- Header -->
    <header class="modern-header">
        <div class="container">
            <h1>Administration des Missions</h1>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container modern-dashboard">
        <div class="row">
            <!-- Statistiques -->
            <div class="col-md-3 mb-4">
                <div class="stat-card animate-slide-in">
                    <div class="stat-number">24</div>
                    <div class="stat-label">Missions Actives</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card animate-slide-in">
                    <div class="stat-number">12</div>
                    <div class="stat-label">En Attente</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card animate-slide-in">
                    <div class="stat-number">156</div>
                    <div class="stat-label">Complétées</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card animate-slide-in">
                    <div class="stat-number">8</div>
                    <div class="stat-label">Validations</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="glass-card animate-slide-in">
                    <h3>Actions Rapides</h3>
                    <div class="d-flex gap-3 flex-wrap">
                        <button class="btn-modern">
                            <i class="fas fa-plus me-2"></i>Nouvelle Mission
                        </button>
                        <button class="btn-modern">
                            <i class="fas fa-check me-2"></i>Valider Missions
                        </button>
                        <button class="btn-modern">
                            <i class="fas fa-chart-bar me-2"></i>Statistiques
                        </button>
                        <button class="btn-modern">
                            <i class="fas fa-users me-2"></i>Gérer Utilisateurs
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des missions -->
        <div class="row">
            <div class="col-12">
                <div class="glass-card animate-slide-in">
                    <h3>Missions Récentes</h3>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Créateur</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>001</td>
                                    <td>Configuration serveur</td>
                                    <td>Admin</td>
                                    <td><span class="badge-modern badge-success">Terminé</span></td>
                                    <td>2024-01-15</td>
                                    <td>
                                        <button class="btn btn-sm btn-modern">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>002</td>
                                    <td>Mise à jour base de données</td>
                                    <td>Dev Team</td>
                                    <td><span class="badge-modern badge-warning">En cours</span></td>
                                    <td>2024-01-14</td>
                                    <td>
                                        <button class="btn btn-sm btn-modern">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>003</td>
                                    <td>Déploiement frontend</td>
                                    <td>Frontend</td>
                                    <td><span class="badge-modern badge-info">En attente</span></td>
                                    <td>2024-01-13</td>
                                    <td>
                                        <button class="btn btn-sm btn-modern">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Système de thème ultra moderne
        class ThemeManager {
            constructor() {
                this.currentTheme = localStorage.getItem('theme') || 'light';
                this.init();
            }

            init() {
                this.setTheme(this.currentTheme);
                this.setupEventListeners();
                this.setupSystemDetection();
            }

            setTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                this.currentTheme = theme;
                localStorage.setItem('theme', theme);
                this.updateIcon();
                this.animateToggle();
            }

            updateIcon() {
                const icon = document.getElementById('themeIcon');
                if (this.currentTheme === 'dark') {
                    icon.className = 'fas fa-moon';
                } else {
                    icon.className = 'fas fa-sun';
                }
            }

            animateToggle() {
                const toggle = document.getElementById('themeToggle');
                toggle.style.transform = 'scale(0.8) rotate(180deg)';
                setTimeout(() => {
                    toggle.style.transform = 'scale(1) rotate(0deg)';
                }, 200);
            }

            setupEventListeners() {
                document.getElementById('themeToggle').addEventListener('click', () => {
                    const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                    this.setTheme(newTheme);
                });
            }

            setupSystemDetection() {
                // Détection automatique du thème système
                if (!localStorage.getItem('theme')) {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
                    this.setTheme(prefersDark.matches ? 'dark' : 'light');
                    
                    // Écoute des changements système
                    prefersDark.addEventListener('change', (e) => {
                        if (!localStorage.getItem('theme')) {
                            this.setTheme(e.matches ? 'dark' : 'light');
                        }
                    });
                }
            }
        }

        // Animations d'entrée
        function animateCards() {
            const cards = document.querySelectorAll('.glass-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            new ThemeManager();
            animateCards();
        });
    </script>
</body>
</html> 