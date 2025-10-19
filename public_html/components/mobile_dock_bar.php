<?php
// Barre de navigation mobile moderne - Version propre mobile_dock_bar
// Détection du type d'appareil
$isMobile = preg_match('/(iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini)/i', $_SERVER['HTTP_USER_AGENT']);
$isIPad = preg_match('/(iPad)/i', $_SERVER['HTTP_USER_AGENT']) || 
          (preg_match('/(Macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']));

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Compter les tâches actives
$tasks_count = 0;
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM taches WHERE statut IN ('en_cours', 'nouveau')");
            $result = $stmt->fetch();
            $tasks_count = $result['count'] ?? 0;
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs de comptage
}

// Inclure les styles et scripts
$assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>

<!-- Styles CSS pour mobile_dock_bar -->
<link rel="stylesheet" href="<?php echo $assets_path; ?>css/mobile_dock_bar.css">
<script src="<?php echo $assets_path; ?>js/mobile_dock_bar.js" defer></script>

<!-- NOUVELLE BARRE MOBILE DOCK BAR avec effet glassmorphism -->
<div id="mobile_dock_bar" class="d-block d-lg-none">
    <div class="dock-bar-container">
        <!-- Accueil -->
        <a href="index.php?page=accueil" class="dock-bar-item <?php echo $currentPage == 'accueil' ? 'active' : ''; ?>" aria-label="Accueil">
            <div class="dock-bar-icon">
                <i class="fas fa-home"></i>
            </div>
            <span class="dock-bar-label">Accueil</span>
        </a>

        <!-- Réparations -->
        <a href="index.php?page=reparations" class="dock-bar-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" aria-label="Réparations">
            <div class="dock-bar-icon">
                <i class="fas fa-tools"></i>
            </div>
            <span class="dock-bar-label">Réparations</span>
        </a>

        <!-- Bouton + central -->
        <button class="dock-bar-item dock-bar-plus" type="button" id="nouvelle-action-trigger-dock" aria-label="Nouvelle action">
            <div class="dock-bar-icon">
                <i class="fas fa-plus"></i>
            </div>
            <span class="dock-bar-label">Nouvelle</span>
        </button>

        <!-- Tâches -->
        <a href="index.php?page=taches" class="dock-bar-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" aria-label="Tâches">
            <div class="dock-bar-icon">
                <i class="fas fa-tasks"></i>
                <?php if ($tasks_count > 0): ?>
                    <span class="dock-bar-badge"><?php echo $tasks_count; ?></span>
                <?php endif; ?>
            </div>
            <span class="dock-bar-label">Tâches</span>
        </a>

        <!-- Menu -->
        <a href="#" class="dock-bar-item" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-label="Menu principal">
            <div class="dock-bar-icon">
                <i class="fas fa-bars"></i>
            </div>
            <span class="dock-bar-label">Menu</span>
        </a>
    </div>
</div>

<!-- Animation de Confirmation de Pointage -->
<div id="pointage-confirmation-overlay" class="pointage-confirmation-overlay">
    <div class="pointage-confirmation-card">
        <div class="pointage-icon-container">
            <div class="pointage-icon-circle">
                <svg class="pointage-icon" viewBox="0 0 48 48">
                    <circle cx="24" cy="24" r="20" fill="currentColor"/>
                    <circle cx="24" cy="24" r="18" fill="rgba(30, 41, 59, 0.1)" stroke="rgba(30, 41, 59, 0.3)" stroke-width="2"/>
                    <line x1="24" y1="8" x2="24" y2="12" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                    <line x1="24" y1="36" x2="24" y2="40" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                    <line x1="8" y1="24" x2="12" y2="24" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                    <line x1="36" y1="24" x2="40" y2="24" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                    <line x1="24" y1="24" x2="24" y2="14" stroke="rgba(30, 41, 59, 0.9)" stroke-width="3" stroke-linecap="round"/>
                    <line x1="24" y1="24" x2="30" y2="24" stroke="rgba(30, 41, 59, 0.9)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="24" cy="24" r="2" fill="rgba(30, 41, 59, 0.9)"/>
                </svg>
            </div>
            <div class="pointage-ripple"></div>
            <div class="pointage-ripple-2"></div>
        </div>
        <div class="pointage-text-container">
            <h3 class="pointage-title" id="pointage-title">Pointage Entrée</h3>
            <p class="pointage-subtitle" id="pointage-subtitle">Confirmé avec succès</p>
            <div class="pointage-time" id="pointage-time"></div>
        </div>
        <div class="pointage-success-check">
            <svg class="check-icon" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
            </svg>
        </div>
    </div>
</div>

<!-- Animation Menu Circulaire pour Actions Nouvelles -->
<div id="nouvelles-actions-overlay" class="circular-menu-overlay nouvelles-actions-overlay">
    <div class="links">
        <ul class="links__list" style="--item-total:6">
            <li class="links__item" style="--item-count:1">
                <a class="links__link" href="index.php?page=ajouter_reparation">
                    <div class="links__content">
                        <svg class="links__icon" viewBox="0 0 48 48">
                            <!-- Roue crantée pour réparation -->
                            <circle cx="24" cy="24" r="16" fill="currentColor"/>
                            <circle cx="24" cy="24" r="6" fill="rgba(30, 41, 59, 0.8)"/>
                            <!-- Crans de la roue -->
                            <rect x="22" y="4" width="4" height="8" fill="currentColor"/>
                            <rect x="22" y="36" width="4" height="8" fill="currentColor"/>
                            <rect x="4" y="22" width="8" height="4" fill="currentColor"/>
                            <rect x="36" y="22" width="8" height="4" fill="currentColor"/>
                            <!-- Crans diagonaux -->
                            <rect x="35" y="9" width="6" height="4" fill="currentColor" transform="rotate(45 38 11)"/>
                            <rect x="7" y="9" width="6" height="4" fill="currentColor" transform="rotate(-45 10 11)"/>
                            <rect x="35" y="35" width="6" height="4" fill="currentColor" transform="rotate(-45 38 37)"/>
                            <rect x="7" y="35" width="6" height="4" fill="currentColor" transform="rotate(45 10 37)"/>
                            <!-- Trou central -->
                            <circle cx="24" cy="24" r="3" fill="rgba(30, 41, 59, 0.9)"/>
                        </svg>
                        <span class="links__text">Réparation</span>
                    </div>
                </a>
            </li>
            <li class="links__item" style="--item-count:2">
                <a class="links__link" href="#" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
                    <div class="links__content">
                        <svg class="links__icon" viewBox="0 0 48 48">
                            <!-- Caddie de course amélioré -->
                            <path d="M8 6h4l3 15h20l3-12H15l-1-5L12 6H8z" fill="currentColor" stroke="currentColor" stroke-width="2"/>
                            <!-- Roues -->
                            <circle cx="18" cy="38" r="4" fill="currentColor"/>
                            <circle cx="36" cy="38" r="4" fill="currentColor"/>
                            <!-- Panier détaillé -->
                            <rect x="15" y="12" width="24" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <!-- Articles dans le panier -->
                            <rect x="18" y="15" width="3" height="3" fill="rgba(30, 41, 59, 0.8)"/>
                            <rect x="24" y="15" width="3" height="3" fill="rgba(30, 41, 59, 0.8)"/>
                            <rect x="30" y="15" width="3" height="3" fill="rgba(30, 41, 59, 0.8)"/>
                            <rect x="21" y="19" width="3" height="3" fill="rgba(30, 41, 59, 0.8)"/>
                            <rect x="27" y="19" width="3" height="3" fill="rgba(30, 41, 59, 0.8)"/>
                            <!-- Poignée -->
                            <path d="M12 6v-2c0-1 1-2 2-2h2c1 0 2 1 2 2v2" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span class="links__text">Commande</span>
                    </div>
                </a>
            </li>
            <li class="links__item" style="--item-count:3">
                <a class="links__link" href="#" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal">
                    <div class="links__content">
                        <svg class="links__icon" viewBox="0 0 48 48">
                            <!-- Clipboard avec tâches -->
                            <rect x="8" y="4" width="32" height="40" rx="4" fill="currentColor"/>
                            <rect x="12" y="8" width="24" height="32" rx="2" fill="rgba(30, 41, 59, 0.9)"/>
                            <!-- Clip du clipboard -->
                            <rect x="18" y="2" width="12" height="6" rx="3" fill="currentColor"/>
                            <rect x="20" y="4" width="8" height="2" fill="rgba(30, 41, 59, 0.9)"/>
                            <!-- Lignes de tâches -->
                            <line x1="16" y1="16" x2="32" y2="16" stroke="currentColor" stroke-width="2"/>
                            <line x1="16" y1="22" x2="32" y2="22" stroke="currentColor" stroke-width="2"/>
                            <line x1="16" y1="28" x2="32" y2="28" stroke="currentColor" stroke-width="2"/>
                            <line x1="16" y1="34" x2="32" y2="34" stroke="currentColor" stroke-width="2"/>
                            <!-- Checkboxes -->
                            <rect x="16" y="14" width="4" height="4" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/>
                            <rect x="16" y="20" width="4" height="4" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/>
                            <rect x="16" y="26" width="4" height="4" rx="1" fill="#4ade80" stroke="#4ade80" stroke-width="1.5"/>
                            <rect x="16" y="32" width="4" height="4" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/>
                            <!-- Checkmark -->
                            <path d="M17 28l1.5 1.5L21 27" stroke="rgba(30, 41, 59, 0.9)" stroke-width="2" fill="none"/>
                        </svg>
                        <span class="links__text">Tâche</span>
                    </div>
                </a>
            </li>
            <li class="links__item" style="--item-count:4">
                <a class="links__link" href="#" id="dynamic-timetracking-button-circular">
                    <div class="links__content">
                        <svg class="links__icon timetracking-icon" viewBox="0 0 48 48">
                            <!-- Horloge moderne -->
                            <circle cx="24" cy="24" r="20" fill="currentColor"/>
                            <circle cx="24" cy="24" r="18" fill="rgba(30, 41, 59, 0.1)" stroke="rgba(30, 41, 59, 0.3)" stroke-width="2"/>
                            <!-- Marqueurs d'heures -->
                            <line x1="24" y1="8" x2="24" y2="12" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                            <line x1="24" y1="36" x2="24" y2="40" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                            <line x1="8" y1="24" x2="12" y2="24" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                            <line x1="36" y1="24" x2="40" y2="24" stroke="rgba(30, 41, 59, 0.8)" stroke-width="3"/>
                            <!-- Marqueurs secondaires -->
                            <line x1="35.5" y1="12.5" x2="33.5" y2="14.5" stroke="rgba(30, 41, 59, 0.5)" stroke-width="2"/>
                            <line x1="35.5" y1="35.5" x2="33.5" y2="33.5" stroke="rgba(30, 41, 59, 0.5)" stroke-width="2"/>
                            <line x1="12.5" y1="12.5" x2="14.5" y2="14.5" stroke="rgba(30, 41, 59, 0.5)" stroke-width="2"/>
                            <line x1="12.5" y1="35.5" x2="14.5" y2="33.5" stroke="rgba(30, 41, 59, 0.5)" stroke-width="2"/>
                            <!-- Aiguilles -->
                            <line x1="24" y1="24" x2="24" y2="14" stroke="rgba(30, 41, 59, 0.9)" stroke-width="3" stroke-linecap="round"/>
                            <line x1="24" y1="24" x2="30" y2="24" stroke="rgba(30, 41, 59, 0.9)" stroke-width="2" stroke-linecap="round"/>
                            <!-- Centre -->
                            <circle cx="24" cy="24" r="2" fill="rgba(30, 41, 59, 0.9)"/>
                            <!-- Texte IN/OUT -->
                            <text x="24" y="32" text-anchor="middle" class="clock-text" font-size="8" font-weight="bold" fill="rgba(30, 41, 59, 0.9)">IN</text>
                        </svg>
                        <span class="links__text">Pointage</span>
                    </div>
                </a>
            </li>
            <li class="links__item" style="--item-count:5">
                <a class="links__link" href="#" data-bs-toggle="modal" data-bs-target="#universal_scanner_modal">
                    <div class="links__content">
                        <svg class="links__icon" viewBox="0 0 48 48">
                            <!-- Scanner QR/Code-barres -->
                            <rect x="6" y="8" width="30" height="20" rx="3" fill="currentColor"/>
                            <rect x="8" y="10" width="26" height="16" rx="2" fill="rgba(30, 41, 59, 0.9)"/>
                            <!-- Code-barres -->
                            <line x1="10" y1="12" x2="10" y2="24" stroke="currentColor" stroke-width="1"/>
                            <line x1="12" y1="12" x2="12" y2="24" stroke="currentColor" stroke-width="2"/>
                            <line x1="15" y1="12" x2="15" y2="24" stroke="currentColor" stroke-width="1"/>
                            <line x1="17" y1="12" x2="17" y2="24" stroke="currentColor" stroke-width="3"/>
                            <line x1="21" y1="12" x2="21" y2="24" stroke="currentColor" stroke-width="1"/>
                            <line x1="23" y1="12" x2="23" y2="24" stroke="currentColor" stroke-width="2"/>
                            <line x1="26" y1="12" x2="26" y2="24" stroke="currentColor" stroke-width="1"/>
                            <line x1="28" y1="12" x2="28" y2="24" stroke="currentColor" stroke-width="2"/>
                            <line x1="31" y1="12" x2="31" y2="24" stroke="currentColor" stroke-width="1"/>
                            <!-- Loupe -->
                            <circle cx="32" cy="32" r="8" fill="none" stroke="currentColor" stroke-width="3"/>
                            <circle cx="32" cy="32" r="6" fill="rgba(30, 41, 59, 0.1)"/>
                            <line x1="37.5" y1="37.5" x2="42" y2="42" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <!-- Reflet sur la loupe -->
                            <path d="M28 28c2-2 6-2 8 0" stroke="rgba(30, 41, 59, 0.6)" stroke-width="2" fill="none"/>
                            <!-- Rayon laser -->
                            <line x1="20" y1="30" x2="20" y2="34" stroke="#ff4444" stroke-width="2" opacity="0.8"/>
                        </svg>
                        <span class="links__text">Scanner</span>
                    </div>
                </a>
            </li>
            <li class="links__item" style="--item-count:6">
                <a class="links__link" href="#" id="nouvelles-actions-close">
                    <div class="links__content">
                        <svg class="links__icon" viewBox="0 0 48 48">
                            <!-- Bouton fermer moderne -->
                            <circle cx="24" cy="24" r="20" fill="currentColor"/>
                            <circle cx="24" cy="24" r="18" fill="rgba(30, 41, 59, 0.1)" stroke="rgba(30, 41, 59, 0.3)" stroke-width="2"/>
                            <!-- X stylisé -->
                            <line x1="16" y1="16" x2="32" y2="32" stroke="rgba(30, 41, 59, 0.9)" stroke-width="4" stroke-linecap="round"/>
                            <line x1="32" y1="16" x2="16" y2="32" stroke="rgba(30, 41, 59, 0.9)" stroke-width="4" stroke-linecap="round"/>
                            <!-- Cercle intérieur pour plus de style -->
                            <circle cx="24" cy="24" r="12" fill="none" stroke="rgba(30, 41, 59, 0.2)" stroke-width="1"/>
                        </svg>
                        <span class="links__text">Fermer</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* ===== STYLES POUR LE MENU CIRCULAIRE MOBILE ===== */
.circular-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(-170deg, #064997 20%, #105ba7);
    background-size: 112px 112px, 112px 112px, 56px 56px, 56px 56px, 28px 28px, 28px 28px;
    background-image: 
        linear-gradient(270deg, #2b67ac 3px, transparent 0),
        linear-gradient(#2b67ac 3px, transparent 0),
        linear-gradient(270deg, rgba(43,103,172,.4) 1px, transparent 0),
        linear-gradient(#2b67ac 1px, transparent 0),
        linear-gradient(270deg, rgba(43,103,172,.4) 1px, transparent 0),
        linear-gradient(#2b67ac 1px, transparent 0);
    z-index: 9999;
    display: none;
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
}

.circular-menu-overlay.active {
    display: flex;
    opacity: 1;
}

.links {
    --base-grid: 8px;
    --colour-white: #fff;
    --colour-black: #1a1a1a;
    --link-size: calc(var(--base-grid) * 20);
    color: var(--colour-black);
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
}

.links__list {
    position: relative;
    list-style: none;
    margin: 0;
    padding: 0;
}

.links__item {
    width: 110px;
    height: 110px;
    position: absolute;
    top: 0;
    left: 0;
    margin-top: -55px;
    margin-left: -55px;
    --angle: calc(360deg / var(--item-total));
    --rotation: calc(90deg + var(--angle) * var(--item-count));
    transform: rotate(var(--rotation)) translate(140px) rotate(calc(var(--rotation) * -1));
    transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.links__item:hover {
    transform: rotate(var(--rotation)) translate(145px) rotate(calc(var(--rotation) * -1)) scale(1.08);
}

.links__link {
    opacity: 0;
    animation: on-load 0.3s ease-in-out forwards;
    animation-delay: calc(var(--item-count) * 150ms);
    width: 100%;
    height: 100%;
    border-radius: 50%;
    position: relative;
    background-color: var(--colour-white);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.links__icon {
    width: calc(var(--base-grid) * 8);
    height: calc(var(--base-grid) * 8);
    transition: all 0.3s ease-in-out;
    fill: var(--colour-black);
}

.links__text {
    position: relative;
    width: 100%;
    text-align: center;
    font-size: 10px;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    animation: none;
    font-weight: 600;
    color: var(--colour-black);
    margin-top: 6px;
    z-index: 10;
}

.links__link:after {
    content: "";
    background-color: transparent;
    width: var(--link-size);
    height: var(--link-size);
    border: 2px dashed var(--colour-white);
    display: block;
    border-radius: 50%;
    position: absolute;
    top: 0;
    left: 0;
    transition: all 0.3s cubic-bezier(0.53, -0.67, 0.73, 0.74);
    transform: none;
    opacity: 0;
}

.links__link:hover .links__icon {
    transition: all 0.3s ease-in-out;
    transform: translateY(calc(var(--base-grid) * -1));
}

.links__link:hover .links__text {
    display: block;
}

.links__link:hover:after {
    transition: all 0.3s cubic-bezier(0.37, 0.74, 0.15, 1.65);
    transform: scale(1.1);
    opacity: 1;
}

@keyframes on-load {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    70% {
        opacity: 0.7;
        transform: scale(1.1);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes text {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(0);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(calc(var(--base-grid) * 5));
    }
}

/* Responsive pour tablettes et mobiles */
@media (max-width: 1366px) {
    .circular-menu-overlay {
        display: none;
    }
    
    .circular-menu-overlay.active {
        display: flex;
    }
    
    .links {
        --link-size: calc(var(--base-grid) * 18);
    }
}

@media (max-width: 768px) {
    .links {
        --link-size: calc(var(--base-grid) * 16);
    }
    
    .links__text {
        font-size: calc(var(--base-grid) * 1.8);
    }
}

/* ===== STYLES SPÉCIFIQUES POUR LE MENU NOUVELLES ACTIONS ===== */
.nouvelles-actions-overlay {
    background: linear-gradient(-170deg, rgba(15, 23, 42, 0.95) 20%, rgba(30, 41, 59, 0.95));
    background-image: 
        linear-gradient(270deg, rgba(51, 65, 85, 0.3) 3px, transparent 0),
        linear-gradient(rgba(51, 65, 85, 0.3) 3px, transparent 0),
        linear-gradient(270deg, rgba(71, 85, 105, 0.2) 1px, transparent 0),
        linear-gradient(rgba(51, 65, 85, 0.3) 1px, transparent 0),
        linear-gradient(270deg, rgba(71, 85, 105, 0.2) 1px, transparent 0),
        linear-gradient(rgba(51, 65, 85, 0.3) 1px, transparent 0);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
}

/* Mode nuit (par défaut) - couleurs douces */
.nouvelles-actions-overlay .links__link {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(37, 99, 235, 0.18) 100%);
    border: 1px solid rgba(59, 130, 246, 0.3);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15), 0 2px 10px rgba(37, 99, 235, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 20px;
    border-radius: 25px;
    width: 110px;
    height: 110px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.nouvelles-actions-overlay .links__link:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.28) 100%);
    border-color: rgba(59, 130, 246, 0.5);
    transform: scale(1.08) translateY(-3px);
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.25), 0 6px 20px rgba(37, 99, 235, 0.15);
}

/* Mode jour - boutons plus foncés */
body:not(.dark-mode) .nouvelles-actions-overlay .links__link {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(29, 78, 216, 0.35) 100%);
    border: 1px solid rgba(37, 99, 235, 0.5);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25), 0 2px 10px rgba(29, 78, 216, 0.15);
}

body:not(.dark-mode) .nouvelles-actions-overlay .links__link:hover {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.35) 0%, rgba(29, 78, 216, 0.45) 100%);
    border-color: rgba(37, 99, 235, 0.7);
    box-shadow: 0 15px 40px rgba(37, 99, 235, 0.35), 0 6px 20px rgba(29, 78, 216, 0.25);
}

.nouvelles-actions-overlay .links__link:after {
    border-color: rgba(59, 130, 246, 0.3);
    border-radius: 25px;
}

.nouvelles-actions-overlay .links__content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 100%;
}

/* Mode nuit - icônes plus claires et contrastées */
.nouvelles-actions-overlay .links__icon {
    fill: rgba(219, 234, 254, 0.95);
    stroke: rgba(219, 234, 254, 0.95);
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    transition: all 0.3s ease;
    filter: brightness(1.1) contrast(1.1);
}

.nouvelles-actions-overlay .links__link:hover .links__icon {
    fill: rgba(255, 255, 255, 1);
    stroke: rgba(255, 255, 255, 1);
    filter: brightness(1.2) contrast(1.2);
}

/* Mode jour - icônes plus contrastées */
body:not(.dark-mode) .nouvelles-actions-overlay .links__icon {
    fill: rgba(30, 64, 175, 0.9);
    stroke: rgba(30, 64, 175, 0.9);
    filter: brightness(1) contrast(1.1);
}

body:not(.dark-mode) .nouvelles-actions-overlay .links__link:hover .links__icon {
    fill: rgba(30, 64, 175, 1);
    stroke: rgba(30, 64, 175, 1);
    filter: brightness(0.9) contrast(1.2);
}

/* Mode nuit - texte doux */
.nouvelles-actions-overlay .links__text {
    color: rgba(203, 213, 225, 0.95) !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    text-align: center;
    line-height: 1.2;
    margin: 0;
    letter-spacing: 0.3px;
    opacity: 1 !important;
    display: block !important;
    visibility: visible !important;
    white-space: nowrap;
    text-shadow: 0 1px 3px rgba(0,0,0,0.4);
    z-index: 20;
    position: relative;
    transition: all 0.3s ease;
}

/* Mode jour - texte plus contrasté */
body:not(.dark-mode) .nouvelles-actions-overlay .links__text {
    color: rgba(30, 58, 138, 0.95) !important;
    font-weight: 700 !important;
    text-shadow: 0 1px 2px rgba(255,255,255,0.5);
}

/* Style spécial pour l'horloge avec texte IN/OUT */
/* Mode nuit - texte horloge plus visible */
.nouvelles-actions-overlay .timetracking-icon .clock-text {
    fill: rgba(219, 234, 254, 0.95);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    transition: fill 0.3s ease;
    filter: brightness(1.1) contrast(1.1);
}

.nouvelles-actions-overlay .links__link:hover .timetracking-icon .clock-text {
    fill: rgba(255, 255, 255, 1);
    filter: brightness(1.2) contrast(1.2);
}

/* Mode jour - texte horloge contrasté */
body:not(.dark-mode) .nouvelles-actions-overlay .timetracking-icon .clock-text {
    fill: rgba(30, 64, 175, 0.9);
    filter: brightness(1) contrast(1.1);
}

body:not(.dark-mode) .nouvelles-actions-overlay .links__link:hover .timetracking-icon .clock-text {
    fill: rgba(30, 64, 175, 1);
    filter: brightness(0.9) contrast(1.2);
}

/* ===== ANIMATION DE CONFIRMATION DE POINTAGE ===== */
.pointage-confirmation-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 10000;
    display: none;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.pointage-confirmation-overlay.active {
    display: flex;
    opacity: 1;
}

.pointage-confirmation-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95) 0%, rgba(37, 99, 235, 0.95) 100%);
    border-radius: 24px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(59, 130, 246, 0.4), 0 8px 25px rgba(37, 99, 235, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 320px;
    width: 90%;
    transform: scale(0.8) translateY(50px);
    animation: pointage-card-enter 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    position: relative;
    overflow: hidden;
}

/* Mode jour - carte plus foncée */
body:not(.dark-mode) .pointage-confirmation-card {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.95) 0%, rgba(29, 78, 216, 0.95) 100%);
    box-shadow: 0 20px 60px rgba(37, 99, 235, 0.5), 0 8px 25px rgba(29, 78, 216, 0.4);
}

@keyframes pointage-card-enter {
    0% {
        transform: scale(0.8) translateY(50px);
        opacity: 0;
    }
    60% {
        transform: scale(1.05) translateY(-5px);
        opacity: 1;
    }
    100% {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

.pointage-icon-container {
    position: relative;
    margin: 0 auto 24px;
    width: 80px;
    height: 80px;
}

.pointage-icon-circle {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
    animation: pointage-icon-pulse 0.8s ease-out;
}

.pointage-icon {
    width: 48px;
    height: 48px;
    fill: rgba(255, 255, 255, 0.95);
    stroke: rgba(255, 255, 255, 0.95);
}

@keyframes pointage-icon-pulse {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.pointage-ripple,
.pointage-ripple-2 {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 80px;
    height: 80px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    animation: pointage-ripple 1.5s ease-out infinite;
}

.pointage-ripple-2 {
    animation-delay: 0.5s;
    border-color: rgba(255, 255, 255, 0.2);
}

@keyframes pointage-ripple {
    0% {
        transform: translate(-50%, -50%) scale(0);
        opacity: 1;
    }
    70% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0.3;
    }
    100% {
        transform: translate(-50%, -50%) scale(2);
        opacity: 0;
    }
}

.pointage-text-container {
    margin-bottom: 20px;
}

.pointage-title {
    color: rgba(255, 255, 255, 0.95);
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px 0;
    animation: pointage-text-slide 0.8s ease-out 0.2s both;
}

.pointage-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 12px 0;
    animation: pointage-text-slide 0.8s ease-out 0.3s both;
}

.pointage-time {
    color: rgba(255, 255, 255, 0.9);
    font-size: 18px;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    animation: pointage-text-slide 0.8s ease-out 0.4s both;
}

@keyframes pointage-text-slide {
    0% {
        transform: translateY(20px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

.pointage-success-check {
    width: 32px;
    height: 32px;
    background: rgba(34, 197, 94, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    animation: pointage-check-bounce 0.6s ease-out 0.5s both;
}

.check-icon {
    width: 20px;
    height: 20px;
    fill: white;
}

@keyframes pointage-check-bounce {
    0% {
        transform: scale(0) rotate(-180deg);
        opacity: 0;
    }
    50% {
        transform: scale(1.2) rotate(-90deg);
        opacity: 0.8;
    }
    100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .pointage-confirmation-card {
        padding: 28px 20px;
        max-width: 280px;
    }
    
    .pointage-title {
        font-size: 20px;
    }
    
    .pointage-subtitle {
        font-size: 14px;
    }
    
    .pointage-time {
        font-size: 16px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments pour le menu Nouvelles Actions
    const nouvelleActionTriggerDock = document.getElementById('nouvelle-action-trigger-dock');
    const nouvellesActionsOverlay = document.getElementById('nouvelles-actions-overlay');
    const nouvellesActionsClose = document.getElementById('nouvelles-actions-close');
    
    // Fonction pour ouvrir le menu des nouvelles actions
    function openNouvellesActionsMenu(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Vérifier si on est sur mobile ou tablette
        if (window.innerWidth <= 1366) {
            nouvellesActionsOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêcher le scroll
            
            // Ajouter l'effet de flou seulement aux éléments principaux, pas à l'overlay
            const elementsToBlur = document.querySelectorAll('body > *:not(#nouvelles-actions-overlay):not(script):not(style)');
            elementsToBlur.forEach(element => {
                element.style.filter = 'blur(3px)';
                element.style.transition = 'filter 0.3s ease';
            });
        }
    }
    
    // Fonction pour fermer le menu des nouvelles actions
    function closeNouvellesActionsMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        nouvellesActionsOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restaurer le scroll
        
        // Retirer l'effet de flou de tous les éléments
        const elementsToUnblur = document.querySelectorAll('body > *:not(#nouvelles-actions-overlay):not(script):not(style)');
        elementsToUnblur.forEach(element => {
            element.style.filter = 'none';
        });
    }
    
    
    // Event listeners pour le menu nouvelles actions
    if (nouvelleActionTriggerDock) {
        nouvelleActionTriggerDock.addEventListener('click', openNouvellesActionsMenu);
        nouvelleActionTriggerDock.addEventListener('touchstart', openNouvellesActionsMenu);
    }
    
    // Event listeners pour le bouton fermer
    if (nouvellesActionsClose) {
        nouvellesActionsClose.addEventListener('click', closeNouvellesActionsMenu);
        nouvellesActionsClose.addEventListener('touchstart', closeNouvellesActionsMenu);
    }
    
    // Fermer le menu en cliquant sur l'overlay (arrière-plan)
    if (nouvellesActionsOverlay) {
        nouvellesActionsOverlay.addEventListener('click', function(e) {
            if (e.target === nouvellesActionsOverlay) {
                closeNouvellesActionsMenu();
            }
        });
    }
    
    // Event listeners pour les modals (fermer le menu circulaire après ouverture)
    const modalTriggers = nouvellesActionsOverlay.querySelectorAll('[data-bs-toggle="modal"]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            setTimeout(() => {
                closeNouvellesActionsMenu();
            }, 300); // Délai pour permettre l'ouverture du modal
        });
    });
    
    // Gestion du bouton de pointage dynamique
    const dynamicTimetrackingBtn = document.getElementById('dynamic-timetracking-button-circular');
    if (dynamicTimetrackingBtn) {
    // Mettre à jour le bouton de pointage au chargement
    updateTimeTrackingButtonCircular();
    
    // Vérification périodique pour maintenir la synchronisation
    setInterval(() => {
        updateTimeTrackingButtonCircular();
    }, 30000); // Toutes les 30 secondes
        
        dynamicTimetrackingBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Fermer le menu circulaire
            closeNouvellesActionsMenu();
            
            // Vérifier si on est sur mobile ou tablette
            if (window.innerWidth <= 1366) {
                // Déterminer le type de pointage
                const isCurrentlyClockedIn = dynamicTimetrackingBtn.dataset.clockedIn === 'true';
                const pointageType = isCurrentlyClockedIn ? 'sortie' : 'entree';
                
                // Exécuter l'action de pointage avec gestion des données d'approbation
                executePointageWithAnimation(pointageType, isCurrentlyClockedIn);
            } else {
                // Sur desktop, exécuter directement
                if (typeof timeTracking !== 'undefined') {
                    const isCurrentlyClockedIn = dynamicTimetrackingBtn.dataset.clockedIn === 'true';
                    if (isCurrentlyClockedIn) {
                        const result = timeTracking.clockOut();
                        if (result && typeof result.then === 'function') {
                            result.then(() => {
                                setTimeout(() => {
                                    updateTimeTrackingButtonCircular();
                                }, 1000);
                            });
                        } else {
                            setTimeout(() => {
                                updateTimeTrackingButtonCircular();
                            }, 2000);
                        }
                    } else {
                        const result = timeTracking.clockIn();
                        if (result && typeof result.then === 'function') {
                            result.then(() => {
                                setTimeout(() => {
                                    updateTimeTrackingButtonCircular();
                                }, 1000);
                            });
                        } else {
                            setTimeout(() => {
                                updateTimeTrackingButtonCircular();
                            }, 2000);
                        }
                    }
                }
            }
        });
    }
    
    // Fermer le menu en cliquant sur l'overlay (en dehors des liens)
    if (nouvellesActionsOverlay) {
        nouvellesActionsOverlay.addEventListener('click', function(e) {
            if (e.target === nouvellesActionsOverlay || e.target.classList.contains('links')) {
                closeNouvellesActionsMenu();
            }
        });
    }
    
    // Fermer le menu avec la touche Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (nouvellesActionsOverlay.classList.contains('active')) {
                closeNouvellesActionsMenu();
            }
        }
    });
    
    // Gérer le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1366) {
            if (nouvellesActionsOverlay.classList.contains('active')) {
                closeNouvellesActionsMenu();
            }
        }
    });
});

// Fonction pour mettre à jour le bouton de pointage circulaire
function updateTimeTrackingButtonCircular() {
    const dynamicBtn = document.getElementById('dynamic-timetracking-button-circular');
    if (!dynamicBtn) return;
    
    // Vérifier l'état du pointage via une requête AJAX
    fetch('ajax/get_timetracking_status.php')
        .then(response => response.json())
        .then(data => {
            const textElement = dynamicBtn.querySelector('.links__text');
            const clockTextElement = dynamicBtn.querySelector('.clock-text');
            
            if (data.success) {
                if (data.is_clocked_in) {
                    // Utilisateur est pointé IN - proposer OUT
                    textElement.textContent = 'Pointer OUT';
                    if (clockTextElement) clockTextElement.textContent = 'OUT';
                    dynamicBtn.dataset.clockedIn = 'true';
                } else {
                    // Utilisateur n'est pas pointé - proposer IN
                    textElement.textContent = 'Pointer IN';
                    if (clockTextElement) clockTextElement.textContent = 'IN';
                    dynamicBtn.dataset.clockedIn = 'false';
                }
            } else {
                textElement.textContent = 'Pointage';
                if (clockTextElement) clockTextElement.textContent = 'IN';
                dynamicBtn.dataset.clockedIn = 'false';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification du statut de pointage:', error);
            const textElement = dynamicBtn.querySelector('.links__text');
            const clockTextElement = dynamicBtn.querySelector('.clock-text');
            textElement.textContent = 'Pointage';
            if (clockTextElement) clockTextElement.textContent = 'IN';
            dynamicBtn.dataset.clockedIn = 'false';
        });
}

// Fonction pour afficher l'animation de confirmation de pointage
function showPointageConfirmation(type, approvalData = null) {
    const overlay = document.getElementById('pointage-confirmation-overlay');
    const title = document.getElementById('pointage-title');
    const subtitle = document.getElementById('pointage-subtitle');
    const timeElement = document.getElementById('pointage-time');
    
    if (!overlay) return;
    
    // Mettre à jour le contenu selon le type
    if (type === 'entree') {
        title.textContent = 'Pointage Entrée';
        if (approvalData && approvalData.auto_approved) {
            subtitle.textContent = '🟢 Arrivée approuvée automatiquement';
        } else if (approvalData && !approvalData.auto_approved) {
            subtitle.textContent = '🟡 Arrivée en attente d\'approbation';
        } else {
            subtitle.textContent = 'Arrivée confirmée avec succès';
        }
    } else {
        title.textContent = 'Pointage Sortie';
        if (approvalData && approvalData.auto_approved) {
            subtitle.textContent = '🟢 Départ approuvé automatiquement';
        } else if (approvalData && !approvalData.auto_approved) {
            subtitle.textContent = '🟡 Départ en attente d\'approbation';
        } else {
            subtitle.textContent = 'Départ confirmé avec succès';
        }
    }
    
    // Afficher l'heure actuelle
    const now = new Date();
    const timeString = now.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    timeElement.textContent = timeString;
    
    // Afficher l'overlay avec animation
    overlay.classList.add('active');
    overlay.style.display = 'flex';
    
    // Masquer automatiquement après 4 secondes (plus long pour lire le message d'approbation)
    setTimeout(() => {
        hidePointageConfirmation();
    }, 4000);
}

// Fonction pour masquer l'animation de confirmation
function hidePointageConfirmation() {
    const overlay = document.getElementById('pointage-confirmation-overlay');
    if (!overlay) return;
    
    overlay.classList.remove('active');
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 400);
}

// Fonction pour exécuter le pointage avec animation et gestion d'approbation
async function executePointageWithAnimation(pointageType, isCurrentlyClockedIn) {
    try {
        // Afficher l'animation de confirmation immédiatement
        showPointageConfirmation(pointageType);
        
        // Préparer la requête API (même que le modal PC)
        const action = isCurrentlyClockedIn ? 'clock_out' : 'clock_in';
        
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${action}`
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Mettre à jour l'animation avec les données d'approbation
            setTimeout(() => {
                showPointageConfirmation(pointageType, data.data);
            }, 500);
            
            // Mettre à jour le bouton après le pointage
            setTimeout(() => {
                updateTimeTrackingButtonCircular();
            }, 1500);
            
            console.log('✅ Pointage mobile réussi:', data);
            
        } else {
            // En cas d'erreur, masquer l'animation et afficher l'erreur
            hidePointageConfirmation();
            console.error('❌ Erreur pointage mobile:', data.message);
            
            // Fallback vers timeTracking si disponible
            if (typeof timeTracking !== 'undefined') {
                if (isCurrentlyClockedIn) {
                    const result = timeTracking.clockOut();
                    if (result && typeof result.then === 'function') {
                        result.then(() => {
                            setTimeout(() => {
                                updateTimeTrackingButtonCircular();
                            }, 1000);
                        });
                    } else {
                        setTimeout(() => {
                            updateTimeTrackingButtonCircular();
                        }, 2000);
                    }
                } else {
                    const result = timeTracking.clockIn();
                    if (result && typeof result.then === 'function') {
                        result.then(() => {
                            setTimeout(() => {
                                updateTimeTrackingButtonCircular();
                            }, 1000);
                        });
                    } else {
                        setTimeout(() => {
                            updateTimeTrackingButtonCircular();
                        }, 2000);
                    }
                }
            }
        }
        
    } catch (error) {
        console.error('❌ Erreur lors du pointage mobile:', error);
        
        // Masquer l'animation en cas d'erreur
        hidePointageConfirmation();
        
        // Fallback vers timeTracking si disponible
        if (typeof timeTracking !== 'undefined') {
            if (isCurrentlyClockedIn) {
                const result = timeTracking.clockOut();
                if (result && typeof result.then === 'function') {
                    result.then(() => {
                        setTimeout(() => {
                            updateTimeTrackingButtonCircular();
                        }, 1000);
                    });
                } else {
                    setTimeout(() => {
                        updateTimeTrackingButtonCircular();
                    }, 2000);
                }
            } else {
                const result = timeTracking.clockIn();
                if (result && typeof result.then === 'function') {
                    result.then(() => {
                        setTimeout(() => {
                            updateTimeTrackingButtonCircular();
                        }, 1000);
                    });
                } else {
                    setTimeout(() => {
                        updateTimeTrackingButtonCircular();
                    }, 2000);
                }
            }
        }
    }
}

// Fermer l'animation en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const pointageOverlay = document.getElementById('pointage-confirmation-overlay');
    if (pointageOverlay) {
        pointageOverlay.addEventListener('click', function(e) {
            if (e.target === pointageOverlay) {
                hidePointageConfirmation();
            }
        });
    }
});
</script>

