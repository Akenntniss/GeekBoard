<?php
// Détection du mode sombre/clair
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';
?>

<!-- NOUVEAU MENU CARTE STYLE -->
<div class="menu-card-overlay" id="menuCardOverlay" style="display: none;">
    <div class="menu-card" id="menuCard">
        
        <!-- Section Gestion Principale -->
        <ul class="list">
            <li class="element" onclick="window.location.href='index.php'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="label">Accueil</span>
            </li>
            <li class="element" onclick="window.location.href='index.php?page=reparations'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <span class="label">Réparations</span>
            </li>
            <li class="element" onclick="window.location.href='index.php?page=ajouter_reparation'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="label">Nouvelle Réparation</span>
            </li>
            <li class="element" onclick="window.location.href='index.php?page=clients'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                <span class="label">Clients</span>
            </li>
            <li class="element" onclick="window.location.href='index.php?page=taches'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="label">Tâches</span>
                <?php if ($tasks_count > 0): ?>
                    <span class="task-badge"><?php echo $tasks_count; ?></span>
                <?php endif; ?>
            </li>
            <li class="element" onclick="window.location.href='index.php?page=commandes_pieces'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span class="label">Commandes</span>
            </li>
        </ul>

        <div class="separator"></div>

        <!-- Section Administration -->
        <ul class="list">
            <li class="element" onclick="window.location.href='index.php?page=employes'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="label">Employés</span>
            </li>
            
            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <li class="element" onclick="window.location.href='index.php?page=presence_gestion'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="label">Présences</span>
            </li>
            
            <li class="element" onclick="window.location.href='index.php?page=kpi_dashboard'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="label">KPI Dashboard</span>
            </li>
            
            <li class="element" onclick="window.location.href='index.php?page=parametre'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="label">Paramètres</span>
            </li>
            <?php endif; ?>
        </ul>

        <div class="separator"></div>

        <!-- Section Missions et Communication -->
        <ul class="list">
            <li class="element" onclick="window.location.href='index.php?page=mes_missions'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="label">Mes Missions</span>
            </li>
            
            <li class="element" onclick="window.location.href='index.php?page=base_connaissances'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span class="label">Base Connaissances</span>
            </li>
            
            <li class="element" onclick="window.location.href='index.php?page=rachat_appareils'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span class="label">Rachat Appareils</span>
            </li>
        </ul>

        <div class="separator"></div>

        <!-- Section Actions -->
        <ul class="list">
            <?php if (isset($_SESSION['shop_id'])): ?>
            <li class="element" onclick="window.location.href='/pages/change_shop.php'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="label">Changer Magasin</span>
            </li>
            <?php endif; ?>
            
            <li class="element delete" onclick="window.location.href='index.php?action=logout'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="label">Déconnexion</span>
            </li>
        </ul>
    </div>
</div>

<style>
/* ===== OVERLAY ET POSITIONNEMENT ===== */
.menu-card-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: transparent;
    z-index: 9999;
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 70px 20px 20px 20px;
}

.menu-card {
    width: 240px;
    border-radius: 10px;
    padding: 15px 0px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
    animation: slideInFromTop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* ===== STYLES MODE CLAIR ===== */
[data-bs-theme="light"] .menu-card {
    background-color: rgb(255, 255, 255);
    background-image: linear-gradient(
        139deg,
        rgb(255, 255, 255) 0%,
        rgb(255, 255, 255) 50%,
        rgb(255, 255, 255) 100%
    );
}

[data-bs-theme="light"] .menu-card .separator {
    border-top: 1.5px solid #e2e8f0;
}

[data-bs-theme="light"] .menu-card .list {
    list-style-type: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0px 10px;
    margin: 0;
}

[data-bs-theme="light"] .menu-card .list .element {
    display: flex;
    align-items: center;
    color: #141414;
    gap: 10px;
    transition: all 0.3s ease-out;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
}

[data-bs-theme="light"] .menu-card .list .element svg {
    width: 19px;
    height: 19px;
    transition: all 0.3s ease-out;
    stroke: #141414;
}

[data-bs-theme="light"] .menu-card .list .element .label {
    font-weight: 600;
    font-size: 14px;
}

[data-bs-theme="light"] .menu-card .list .element:hover {
    background-color: #5353ff;
    color: #fff;
    transform: translate(1px, -1px);
}

[data-bs-theme="light"] .menu-card .list .delete:hover {
    background-color: #8e2a2a;
}

[data-bs-theme="light"] .menu-card .list .element:active {
    transform: scale(0.99);
}

[data-bs-theme="light"] .menu-card .list:not(:last-child) .element:hover svg {
    stroke: #fff;
}

[data-bs-theme="light"] .menu-card .list:last-child svg {
    stroke: #bd89ff;
}

[data-bs-theme="light"] .menu-card .list:last-child .element {
    color: #bd89ff;
}

[data-bs-theme="light"] .menu-card .list:last-child .element:hover {
    background-color: rgba(0, 0, 0, 0.85);
}

/* ===== STYLES MODE NUIT ===== */
[data-bs-theme="dark"] .menu-card {
    background-color: rgba(36, 40, 50, 1);
    background-image: linear-gradient(
        139deg,
        rgba(36, 40, 50, 1) 0%,
        rgba(36, 40, 50, 1) 0%,
        rgba(37, 28, 40, 1) 100%
    );
}

[data-bs-theme="dark"] .menu-card .separator {
    border-top: 1.5px solid #42434a;
}

[data-bs-theme="dark"] .menu-card .list {
    list-style-type: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0px 10px;
    margin: 0;
}

[data-bs-theme="dark"] .menu-card .list .element {
    display: flex;
    align-items: center;
    color: #7e8590;
    gap: 10px;
    transition: all 0.3s ease-out;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
}

[data-bs-theme="dark"] .menu-card .list .element svg {
    width: 19px;
    height: 19px;
    transition: all 0.3s ease-out;
    stroke: #7e8590;
}

[data-bs-theme="dark"] .menu-card .list .element .label {
    font-weight: 600;
    font-size: 14px;
}

[data-bs-theme="dark"] .menu-card .list .element:hover {
    background-color: #5353ff;
    color: #ffffff;
    transform: translate(1px, -1px);
}

[data-bs-theme="dark"] .menu-card .list .delete:hover {
    background-color: #8e2a2a;
}

[data-bs-theme="dark"] .menu-card .list .element:active {
    transform: scale(0.99);
}

[data-bs-theme="dark"] .menu-card .list:not(:last-child) .element:hover svg {
    stroke: #ffffff;
}

[data-bs-theme="dark"] .menu-card .list:last-child svg {
    stroke: #bd89ff;
}

[data-bs-theme="dark"] .menu-card .list:last-child .element {
    color: #bd89ff;
}

[data-bs-theme="dark"] .menu-card .list:last-child .element:hover {
    background-color: rgba(56, 45, 71, 0.836);
}

/* ===== BADGE POUR LES TÂCHES ===== */
.task-badge {
    background: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
    min-width: 18px;
    text-align: center;
}

/* ===== ANIMATIONS ===== */
@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes slideOutToTop {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
}

.menu-card.closing {
    animation: slideOutToTop 0.2s ease-in-out;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .menu-card-overlay {
        padding: 60px 15px 15px 15px;
        justify-content: center;
    }
    
    .menu-card {
        width: 280px;
        max-width: calc(100vw - 30px);
    }
}

@media (max-width: 480px) {
    .menu-card {
        width: 100%;
        max-width: calc(100vw - 20px);
    }
    
    .menu-card-overlay {
        padding: 60px 10px 10px 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('menuCardOverlay');
    const menuCard = document.getElementById('menuCard');
    
    // Fonction pour ouvrir le menu
    window.openCardMenu = function(buttonElement) {
        overlay.style.display = 'flex';
        
        // Position le menu près du bouton si on a la référence
        if (buttonElement) {
            const rect = buttonElement.getBoundingClientRect();
            const menuCardElement = overlay.querySelector('.menu-card');
            
            // Desktop: position à droite du bouton
            if (window.innerWidth > 768) {
                overlay.style.justifyContent = 'flex-end';
                overlay.style.alignItems = 'flex-start';
                overlay.style.paddingTop = (rect.bottom + 10) + 'px';
                overlay.style.paddingRight = '20px';
            }
        }
        
        // Animation d'entrée
        menuCard.classList.remove('closing');
        
        // Focus trap
        menuCard.focus();
    };
    
    // Fonction pour fermer le menu
    window.closeCardMenu = function() {
        menuCard.classList.add('closing');
        setTimeout(() => {
            overlay.style.display = 'none';
            menuCard.classList.remove('closing');
        }, 200);
    };
    
    // Fermer en cliquant sur l'overlay
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeCardMenu();
        }
    });
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.style.display === 'flex') {
            closeCardMenu();
        }
    });
    
    // Gestion du mobile dock
    const mobileTrigger = document.getElementById('mobile-menu-trigger');
    if (mobileTrigger) {
        mobileTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            openCardMenu(this);
        });
    }
});
</script>
