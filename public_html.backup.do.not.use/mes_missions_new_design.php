<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Missions - GeekBoard</title>
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

        /* Statistiques spéciales pour missions utilisateur */
        .mission-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .mission-stat-card {
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

        .mission-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
        }

        .mission-stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px var(--shadow-color);
        }

        [data-theme="dark"] .mission-stat-card {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .mission-stat-card:hover {
            background: rgba(30, 41, 59, 0.5);
            box-shadow: 0 20px 50px rgba(0, 212, 255, 0.2);
        }

        .mission-stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .mission-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .mission-stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        /* Cartes de missions */
        .mission-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--accent-primary), var(--accent-secondary));
        }

        .mission-card:hover {
            transform: translateX(5px);
            box-shadow: 0 15px 40px var(--shadow-color);
        }

        [data-theme="dark"] .mission-card {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .mission-card:hover {
            background: rgba(30, 41, 59, 0.5);
            border-color: var(--accent-primary);
        }

        .mission-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .mission-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .mission-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .mission-reward {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--success-color);
        }

        .mission-deadline {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
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

        .btn-secondary {
            background: var(--bg-glass);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--bg-glass);
            border-color: var(--accent-primary);
            color: var(--text-primary);
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
            
            .mission-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Décalage PC */
        @media (min-width: 992px) {
            .modern-dashboard {
                padding-top: 5px;
            }
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

        /* Filtres */
        .filter-bar {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        [data-theme="dark"] .filter-bar {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-secondary);
            border-radius: 25px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .filter-tab.active {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            border-color: transparent;
        }

        .filter-tab:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
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
            <h1>Mes Missions</h1>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container modern-dashboard">
        <!-- Statistiques -->
        <div class="mission-stat-grid">
            <div class="mission-stat-card animate-slide-in">
                <div class="mission-stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="mission-stat-number">12</div>
                <div class="mission-stat-label">Missions Actives</div>
            </div>
            <div class="mission-stat-card animate-slide-in">
                <div class="mission-stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="mission-stat-number">5</div>
                <div class="mission-stat-label">En Cours</div>
            </div>
            <div class="mission-stat-card animate-slide-in">
                <div class="mission-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="mission-stat-number">28</div>
                <div class="mission-stat-label">Complétées ce mois</div>
            </div>
            <div class="mission-stat-card animate-slide-in">
                <div class="mission-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="mission-stat-number">3</div>
                <div class="mission-stat-label">Validations en attente</div>
            </div>
            <div class="mission-stat-card animate-slide-in">
                <div class="mission-stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="mission-stat-number">1,250</div>
                <div class="mission-stat-label">Cagnotte & XP</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-bar animate-slide-in">
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">Toutes</button>
                <button class="filter-tab" data-filter="active">Actives</button>
                <button class="filter-tab" data-filter="completed">Terminées</button>
                <button class="filter-tab" data-filter="pending">En attente</button>
            </div>
        </div>

        <!-- Liste des missions -->
        <div class="row">
            <div class="col-12">
                <div class="glass-card animate-slide-in">
                    <h3>Mes Missions</h3>
                    
                    <!-- Mission Card Example -->
                    <div class="mission-card">
                        <div class="mission-title">Configuration serveur de production</div>
                        <div class="mission-description">
                            Configurer et déployer les services backend sur le serveur de production avec SSL et monitoring.
                        </div>
                        <div class="mission-meta">
                            <div class="mission-reward">
                                <i class="fas fa-coins"></i>
                                <span>+150 XP</span>
                            </div>
                            <div class="mission-deadline">
                                <i class="fas fa-calendar"></i>
                                <span>Échéance: 2 jours</span>
                            </div>
                            <div>
                                <span class="badge-modern badge-warning">En cours</span>
                            </div>
                            <div>
                                <button class="btn-modern btn-sm">Voir détails</button>
                            </div>
                        </div>
                    </div>

                    <div class="mission-card">
                        <div class="mission-title">Intégration API de paiement</div>
                        <div class="mission-description">
                            Intégrer Stripe pour les paiements en ligne avec webhook et gestion d'erreurs.
                        </div>
                        <div class="mission-meta">
                            <div class="mission-reward">
                                <i class="fas fa-coins"></i>
                                <span>+200 XP</span>
                            </div>
                            <div class="mission-deadline">
                                <i class="fas fa-calendar"></i>
                                <span>Échéance: 5 jours</span>
                            </div>
                            <div>
                                <span class="badge-modern badge-info">Nouvelle</span>
                            </div>
                            <div>
                                <button class="btn-modern btn-sm">Commencer</button>
                            </div>
                        </div>
                    </div>

                    <div class="mission-card">
                        <div class="mission-title">Optimisation base de données</div>
                        <div class="mission-description">
                            Optimiser les requêtes lentes et créer des index pour améliorer les performances.
                        </div>
                        <div class="mission-meta">
                            <div class="mission-reward">
                                <i class="fas fa-coins"></i>
                                <span>+100 XP</span>
                            </div>
                            <div class="mission-deadline">
                                <i class="fas fa-calendar"></i>
                                <span>Terminé</span>
                            </div>
                            <div>
                                <span class="badge-modern badge-success">Validé</span>
                            </div>
                            <div>
                                <button class="btn-secondary btn-sm">Voir rapport</button>
                            </div>
                        </div>
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

        // Gestion des filtres
        class FilterManager {
            constructor() {
                this.currentFilter = 'all';
                this.init();
            }

            init() {
                this.setupEventListeners();
            }

            setupEventListeners() {
                document.querySelectorAll('.filter-tab').forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        const filter = e.target.dataset.filter;
                        this.setFilter(filter);
                    });
                });
            }

            setFilter(filter) {
                // Mise à jour des tabs
                document.querySelectorAll('.filter-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

                // Logique de filtrage (à adapter selon vos besoins)
                this.currentFilter = filter;
                this.filterMissions();
            }

            filterMissions() {
                // Exemple de filtrage - à adapter selon vos données
                const missions = document.querySelectorAll('.mission-card');
                missions.forEach(mission => {
                    const isVisible = this.shouldShowMission(mission);
                    mission.style.display = isVisible ? 'block' : 'none';
                });
            }

            shouldShowMission(mission) {
                if (this.currentFilter === 'all') return true;
                
                // Logique de filtrage basée sur les badges
                const badge = mission.querySelector('.badge-modern');
                if (!badge) return false;
                
                const status = badge.textContent.toLowerCase();
                
                switch (this.currentFilter) {
                    case 'active':
                        return status.includes('cours') || status.includes('nouvelle');
                    case 'completed':
                        return status.includes('validé') || status.includes('terminé');
                    case 'pending':
                        return status.includes('attente');
                    default:
                        return true;
                }
            }
        }

        // Animations d'entrée
        function animateCards() {
            const cards = document.querySelectorAll('.glass-card, .mission-stat-card, .mission-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            new ThemeManager();
            new FilterManager();
            animateCards();
        });
    </script>
</body>
</html> 