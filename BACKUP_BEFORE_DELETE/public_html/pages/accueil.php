<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
    exit;
}

// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Récupérer les statistiques pour le tableau de bord (avec cache APCu léger)
$cache_key = 'dashboard_quick_' . ($_SESSION['shop_id'] ?? 'default');
$use_cache = function_exists('apcu_exists') && function_exists('apcu_fetch') && function_exists('apcu_store');

// Essayer le cache d'abord (1 minute seulement)
if ($use_cache && apcu_exists($cache_key)) {
    $cached_data = apcu_fetch($cache_key);
    if ($cached_data && is_array($cached_data)) {
        extract($cached_data);
    } else {
        $use_cache = false; // Cache corrompu, désactiver
    }
}

// Si pas de cache ou cache expiré, récupérer normalement
if (!$use_cache || !isset($reparations_stats_categorie)) {
    $reparations_stats_categorie = get_reparations_count_by_status_categorie();
    $reparations_en_attente = $reparations_stats_categorie['en_attente'];
    $reparations_en_cours = $reparations_stats_categorie['en_cours'];
    $reparations_nouvelles = $reparations_stats_categorie['nouvelles'];
    $reparations_actives = count_active_reparations();

    $total_clients = get_total_clients();
    $taches_recentes_count = get_taches_recentes_count();
    $reparations_recentes = get_recent_reparations(5);
    $reparations_recentes_count = count_recent_reparations();
    $taches = get_taches_en_cours(5);
    
    // Mettre en cache pour 1 minute seulement
    if ($use_cache) {
        try {
            apcu_store($cache_key, compact(
                'reparations_stats_categorie', 'reparations_en_attente', 'reparations_en_cours', 
                'reparations_nouvelles', 'reparations_actives', 'total_clients', 'taches_recentes_count',
                'reparations_recentes', 'reparations_recentes_count', 'taches'
            ), 60);
        } catch (Exception $e) {
            // Ignorer les erreurs de cache
        }
    }
}

// Récupérer les commandes récentes et leur compteur
$commandes_recentes = [];
$commandes_en_attente_count = 0;
try {
    $shop_pdo = getShopDBConnection();
    
    // Compter les commandes en attente
    $stmt_count = $shop_pdo->query("
        SELECT COUNT(*) as count 
        FROM commandes_pieces 
        WHERE statut IN ('en_attente', 'urgent')
    ");
    $commandes_en_attente_count = $stmt_count->fetch()['count'];
    
    // Récupérer les commandes récentes
    $stmt = $shop_pdo->query("
        SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom 
        FROM commandes_pieces c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
        WHERE c.statut IN ('en_attente', 'urgent')
        ORDER BY c.date_creation DESC 
        LIMIT 5
    ");
    $commandes_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
    error_log("Erreur lors de la récupération des commandes récentes: " . $e->getMessage());
    $commandes_en_attente_count = 0;
}

// Récupérer les statistiques journalières
function get_daily_stats($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    try {
        $shop_pdo = getShopDBConnection();
        
        // Nouvelles réparations du jour (toutes les réparations créées aujourd'hui, peu importe leur statut actuel)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ?
        ");
        $stmt->execute([$date]);
        $nouvelles_reparations = $stmt->fetchColumn();
        
        // Réparations effectuées du jour (réparations qui ont changé vers le statut "effectué" aujourd'hui)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? 
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_effectuees_modifiees = $stmt->fetchColumn();
        
        // Ajouter les réparations créées ET terminées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ? 
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
        ");
        $stmt->execute([$date]);
        $reparations_effectuees_nouvelles = $stmt->fetchColumn();
        
        $reparations_effectuees = $reparations_effectuees_modifiees + $reparations_effectuees_nouvelles;
        
        // Réparations restituées du jour (réparations qui ont changé vers le statut "restitué" aujourd'hui)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? 
            AND statut = 'restitue'
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_restituees_modifiees = $stmt->fetchColumn();
        
        // Ajouter les réparations créées ET restituées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ? 
            AND statut = 'restitue'
        ");
        $stmt->execute([$date]);
        $reparations_restituees_nouvelles = $stmt->fetchColumn();
        
        $reparations_restituees = $reparations_restituees_modifiees + $reparations_restituees_nouvelles;
        
        // Devis envoyés du jour
        $devis_envoyes = 0;
        try {
            $stmt = $shop_pdo->prepare("
                SELECT COUNT(*) as count 
                FROM devis 
                WHERE DATE(date_envoi) = ? AND statut = 'envoye'
            ");
            $stmt->execute([$date]);
            $devis_envoyes = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Table devis n'existe peut-être pas encore
            $devis_envoyes = 0;
        }
        
        return [
            'nouvelles_reparations' => $nouvelles_reparations ?: 0,
            'reparations_effectuees' => $reparations_effectuees ?: 0,
            'reparations_restituees' => $reparations_restituees ?: 0,
            'devis_envoyes' => $devis_envoyes ?: 0,
            'date' => $date
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques journalières: " . $e->getMessage());
        return [
            'nouvelles_reparations' => 0,
            'reparations_effectuees' => 0,
            'reparations_restituees' => 0,
            'devis_envoyes' => 0,
            'date' => $date
        ];
    }
}

$stats_journalieres = get_daily_stats();
?>

<?php 
// ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
displayTrialWarning(); 
?>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
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
    
    <!-- Loader Mode Clair -->
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

<div id="mainContent" style="display: none;">

<!-- Police Orbitron pour l'aspect futuriste -->
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<link href="assets/css/dashboard-minimal.css" rel="stylesheet">
<!-- Styles spécifiques pour le tableau de bord -->

<!-- Styles futuristes ultra-avancés -->

<!-- Améliorations complémentaires du dashboard -->

<!-- Design ultra-moderne révolutionnaire -->

<!-- Tableaux et onglets avancés -->

<!-- Effets spéciaux et micro-interactions -->

<!-- Correction arrière-plan animé et z-index -->

<!-- Correction des débordements et rotations -->

<!-- Boutons d'action modernes -->

<!-- Améliorations du header existant (glassmorphism + nouveau bouton) -->

<!-- Animations simples et performantes -->

<!-- Design unifié pour tous les boutons et statistiques -->

<!-- Thème professionnel mode clair -->

<!-- Corrections typographie mode nuit -->

<!-- Styles pour le modal de commande -->

<!-- 🚨 CORRECTION CRITIQUE Z-INDEX - TOUJOURS EN DERNIER -->

<!-- 🔧 Correction backdrop modal commande -->

<!-- 🎨 Correction modal futuriste pour la saisie -->

<!-- 🧹 Modal futuriste CLEAN - CSS ULTRA-PROPRE -->

<!-- 🏢 Modal commande de pièces - THÈME CORPORATE MODE JOUR -->

<!-- 🖱️ Correction problème clics page d'accueil -->

<!-- 🎯 Correction cartes dashboard État des réparations -->

<!-- 🎨 Harmonisation couleurs de fond sections -->

<!-- 📊 Correction z-index modal statistiques -->

<!-- ☀️ Adaptation mode jour pour statistiques et navigation -->

<!-- 📱 Actions rapides mobile - 1x4 sur une ligne -->

<!-- 🚨 FORÇAGE ULTIMATE 1x4 - PRIORITÉ MAXIMALE -->
n<!-- 🌅 NOUVEAU DESIGN MODE JOUR - Redesign complet -->

<!-- 🌙 CORRECTION VISIBILITÉ TEXTE BOUTONS MODE NUIT MOBILE -->

<!-- 🛠️ CONSOLE DEBUG VISUELLE POUR iOS -->
<div id="ios-debug-console" style="
    position: fixed;
    top: 10px;
    left: 10px;
    right: 10px;
    background: rgba(0,0,0,0.9);
    color: #00ff00;
    font-family: monospace;
    font-size: 12px;
    padding: 10px;
    border-radius: 8px;
    z-index: 999999;
    max-height: 200px;
    overflow-y: auto;
    display: none;
">
    <div style="text-align: right; margin-bottom: 5px; display: flex; gap: 5px; justify-content: flex-end;">
        <button onclick="copyDebugInfo()" 
                style="background: #007AFF; color: white; border: none; padding: 2px 8px; border-radius: 3px; font-size: 11px;">📋 Copier</button>
        <button onclick="clearDebugLog()" 
                style="background: #FF9500; color: white; border: none; padding: 2px 8px; border-radius: 3px; font-size: 11px;">🗑️ Vider</button>
        <button onclick="document.getElementById('ios-debug-console').style.display='none'" 
                style="background: red; color: white; border: none; padding: 2px 6px; border-radius: 3px;">✕</button>
    </div>
    <div id="debug-content"></div>
</div>

<button id="debug-toggle" onclick="toggleDebug()" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #007AFF;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 25px;
    z-index: 999998;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,122,255,0.4);
">🔍 Debug</button>

<!-- 🔥 DÉSACTIVATION FORCÉE DU CACHE ET SERVICE WORKER -->
<script>
// Désactiver tous les Service Workers
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for(let registration of registrations) {
            registration.unregister().then(function(boolean) {
                console.log('Service Worker désactivé:', boolean);
            });
        }
    });
}

// Forcer le rechargement sans cache
if (window.location.search.indexOf('nocache') === -1) {
    const separator = window.location.search ? '&' : '?';
    window.location.href = window.location.href + separator + 'nocache=' + Date.now();
}

// Console debug visuelle pour iOS
let debugLogs = [];

function addDebugLog(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = `[${timestamp}] ${message}`;
    debugLogs.push(logEntry);
    
    // Limiter à 50 logs
    if (debugLogs.length > 50) {
        debugLogs = debugLogs.slice(-50);
    }
    
    updateDebugDisplay();
    console.log(message); // Aussi dans la vraie console
}

function updateDebugDisplay() {
    const content = document.getElementById('debug-content');
    if (content) {
        content.innerHTML = debugLogs.map(log => `<div>${log}</div>`).join('');
        content.scrollTop = content.scrollHeight;
    }
}

function toggleDebug() {
    const console = document.getElementById('ios-debug-console');
    const isVisible = console.style.display !== 'none';
    console.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible) {
        runDiagnostic();
    }
}

function copyDebugInfo() {
    const debugText = debugLogs.join('\n');
    
    // Ajouter des informations système supplémentaires
    const systemInfo = [
        '=== INFORMATIONS DEBUG iOS ===',
        `Date: ${new Date().toLocaleString()}`,
        `URL: ${window.location.href}`,
        `User Agent: ${navigator.userAgent}`,
        `Écran: ${window.innerWidth}x${window.innerHeight}`,
        `Pixel Ratio: ${window.devicePixelRatio}`,
        `Orientation: ${window.orientation || 'N/A'}`,
        '=== LOGS DEBUG ===',
        debugText,
        '=== FIN DEBUG ==='
    ].join('\n');
    
    // Méthode moderne pour copier
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(systemInfo).then(function() {
            addDebugLog('✅ Debug copié dans le presse-papiers !');
            showCopyFeedback();
        }).catch(function(err) {
            addDebugLog('❌ Erreur copie: ' + err.message);
            fallbackCopy(systemInfo);
        });
    } else {
        // Fallback pour iOS plus anciens
        fallbackCopy(systemInfo);
    }
}

function fallbackCopy(text) {
    // Créer un textarea temporaire
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    
    // Sélectionner et copier
    textarea.focus();
    textarea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            addDebugLog('✅ Debug copié (fallback) !');
            showCopyFeedback();
        } else {
            addDebugLog('❌ Impossible de copier automatiquement');
            showManualCopy(text);
        }
    } catch (err) {
        addDebugLog('❌ Erreur copie fallback: ' + err.message);
        showManualCopy(text);
    }
    
    document.body.removeChild(textarea);
}

function showCopyFeedback() {
    const feedback = document.createElement('div');
    feedback.innerHTML = '✅ Copié !';
    feedback.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #007AFF;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        z-index: 9999999;
        font-weight: bold;
    `;
    document.body.appendChild(feedback);
    
    setTimeout(() => {
        document.body.removeChild(feedback);
    }, 2000);
}

function showManualCopy(text) {
    // Créer une modal pour copie manuelle
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        z-index: 9999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 12px;
        max-width: 90%;
        max-height: 80%;
        overflow: auto;
    `;
    
    content.innerHTML = `
        <h3 style="margin-top: 0;">📋 Copie Manuelle</h3>
        <p>Sélectionnez tout le texte ci-dessous et copiez-le :</p>
        <textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;">${text}</textarea>
        <br><br>
        <button onclick="document.body.removeChild(this.closest('div').parentElement)" 
                style="background: #007AFF; color: white; border: none; padding: 10px 20px; border-radius: 6px;">Fermer</button>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
}

function clearDebugLog() {
    debugLogs = [];
    updateDebugDisplay();
    addDebugLog('🗑️ Log vidé - Prêt pour nouveau diagnostic');
}

function runDiagnostic() {
    addDebugLog('🔍 DIAGNOSTIC iOS - Layout Mobile 1x4');
    addDebugLog(`📱 Écran: ${window.innerWidth}x${window.innerHeight}`);
    addDebugLog(`📱 User Agent: ${navigator.userAgent.includes('iPhone') ? 'iPhone' : navigator.userAgent.includes('iPad') ? 'iPad' : 'Autre'}`);
    
    // Vérifier la grille
    const grid = document.querySelector('.quick-actions-grid, .futuristic-action-grid');
    if (grid) {
        const computed = window.getComputedStyle(grid);
        addDebugLog('✅ Grille trouvée');
        addDebugLog(`📐 Colonnes: ${computed.gridTemplateColumns}`);
        addDebugLog(`📏 Gap: ${computed.gap}`);
        addDebugLog(`📦 Display: ${computed.display}`);
        addDebugLog(`📏 Width: ${computed.width}`);
        
        const cards = grid.querySelectorAll('.action-card, .futuristic-action-btn');
        addDebugLog(`🎴 Cartes trouvées: ${cards.length}`);
        
        cards.forEach((card, index) => {
            const cardStyle = window.getComputedStyle(card);
            const rect = card.getBoundingClientRect();
            addDebugLog(`Carte ${index + 1}: ${Math.round(rect.width)}px x ${Math.round(rect.height)}px`);
        });
        
        // Vérifier les CSS chargés
        const links = document.querySelectorAll('link[href*="mobile"], link[href*="1x4"]');
        addDebugLog(`📄 CSS Mobile chargés: ${links.length}`);
        links.forEach(link => {
            addDebugLog(`  - ${link.href.split('/').pop()}`);
        });
        
    } else {
        addDebugLog('❌ ERREUR: Grille non trouvée !');
    }
}

// Initialisation
addDebugLog('🔍 Debug iOS activé - Vérification du layout mobile 1x4');
addDebugLog(`Largeur écran: ${window.innerWidth}`);
addDebugLog(`Hauteur écran: ${window.innerHeight}`);

// FORCER LE LAYOUT 1x4 APRÈS CHARGEMENT - MOBILE UNIQUEMENT
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si on est sur mobile
    if (window.innerWidth <= 768) {
        const grid = document.querySelector('.quick-actions-grid, .futuristic-action-grid');
        if (grid) {
            // SYSTÈME DE SWIPE HORIZONTAL POUR LES BOUTONS
            const screenWidth = window.innerWidth;
            
            // Créer un conteneur scrollable horizontal OPTIMISÉ TACTILE
            const scrollContainer = document.createElement('div');
            scrollContainer.className = 'mobile-actions-scroll-container';
            scrollContainer.style.cssText = `
                width: 100% !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                padding: 8px 0 !important;
                margin: 0 !important;
                -webkit-overflow-scrolling: touch !important;
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
                touch-action: pan-x !important;
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                cursor: grab !important;
            `;
            
            // Masquer la scrollbar et ajouter animations
            const style = document.createElement('style');
            style.textContent = `
                .mobile-actions-scroll-container::-webkit-scrollbar {
                    display: none !important;
                }
                @keyframes pulse {
                    0% { transform: translateY(-50%) scale(1); opacity: 0.8; }
                    50% { transform: translateY(-50%) scale(1.1); opacity: 1; }
                    100% { transform: translateY(-50%) scale(1); opacity: 0.8; }
                }
                .mobile-actions-scroll-container {
                    scroll-behavior: smooth !important;
                }
                .mobile-actions-scroll-container::before {
                    content: '' !important;
                    position: absolute !important;
                    top: -10px !important;
                    bottom: -10px !important;
                    left: 0 !important;
                    right: 0 !important;
                    z-index: -1 !important;
                }
            `;
            document.head.appendChild(style);
            
            // Configurer la grille pour le scroll horizontal
            grid.style.setProperty('display', 'flex', 'important');
            grid.style.setProperty('flex-direction', 'row', 'important');
            grid.style.setProperty('gap', '8px', 'important');
            grid.style.setProperty('padding', '0 8px', 'important');
            grid.style.setProperty('margin', '0', 'important');
            grid.style.setProperty('border', 'none', 'important');
            grid.style.setProperty('box-sizing', 'border-box', 'important');
            grid.style.setProperty('min-width', 'max-content', 'important');
            
            // Insérer le conteneur scrollable
            const parent = grid.parentNode;
            parent.insertBefore(scrollContainer, grid);
            scrollContainer.appendChild(grid);
            
            // AMÉLIORER LA COMPATIBILITÉ TACTILE AVEC MOMENTUM
            let isScrolling = false;
            let startX = 0;
            let scrollLeft = 0;
            let startTime = 0;
            let endTime = 0;
            let distance = 0;
            
            // Événements touch pour iOS/Android AVEC MOMENTUM
            scrollContainer.addEventListener('touchstart', function(e) {
                isScrolling = true;
                startX = e.touches[0].pageX - scrollContainer.offsetLeft;
                scrollLeft = scrollContainer.scrollLeft;
                startTime = Date.now();
                scrollContainer.style.cursor = 'grabbing';
                addDebugLog('👆 Touch start détecté - Position: ' + Math.round(startX));
            }, { passive: true });
            
            scrollContainer.addEventListener('touchmove', function(e) {
                if (!isScrolling) return;
                e.preventDefault();
                const x = e.touches[0].pageX - scrollContainer.offsetLeft;
                const walk = (x - startX) * 2; // Multiplier pour plus de sensibilité
                scrollContainer.scrollLeft = scrollLeft - walk;
                addDebugLog('👆 Touch move - scroll actif');
            }, { passive: false });
            
            scrollContainer.addEventListener('touchend', function(e) {
                if (isScrolling) {
                    endTime = Date.now();
                    const timeDiff = endTime - startTime;
                    const currentX = e.changedTouches[0].pageX - scrollContainer.offsetLeft;
                    distance = Math.abs(currentX - startX);
                    
                    // Si le swipe est rapide et long, ajouter du momentum
                    if (timeDiff < 300 && distance > 30) {
                        const velocity = distance / timeDiff;
                        const momentum = velocity * 100; // Ajuster la force
                        const direction = currentX < startX ? 1 : -1;
                        
                        scrollContainer.scrollBy({
                            left: momentum * direction,
                            behavior: 'smooth'
                        });
                        
                        addDebugLog(`🚀 Momentum appliqué: ${Math.round(momentum)}px`);
                    }
                }
                
                isScrolling = false;
                scrollContainer.style.cursor = 'grab';
                addDebugLog('👆 Touch end - Distance: ' + Math.round(distance) + 'px');
            }, { passive: true });
            
            // Événements mouse pour debug sur desktop
            scrollContainer.addEventListener('mousedown', function(e) {
                isScrolling = true;
                startX = e.pageX - scrollContainer.offsetLeft;
                scrollLeft = scrollContainer.scrollLeft;
                scrollContainer.style.cursor = 'grabbing';
            });
            
            scrollContainer.addEventListener('mousemove', function(e) {
                if (!isScrolling) return;
                e.preventDefault();
                const x = e.pageX - scrollContainer.offsetLeft;
                const walk = (x - startX) * 2;
                scrollContainer.scrollLeft = scrollLeft - walk;
            });
            
            scrollContainer.addEventListener('mouseup', function() {
                isScrolling = false;
                scrollContainer.style.cursor = 'grab';
            });
            
            scrollContainer.addEventListener('mouseleave', function() {
                isScrolling = false;
                scrollContainer.style.cursor = 'grab';
            });
            
            // CONFIGURER LES CARTES POUR LE SCROLL HORIZONTAL
            const cards = grid.querySelectorAll('.action-card, .futuristic-action-btn');
            const cardWidth = 90; // Largeur fixe optimale pour le scroll
            
            cards.forEach((card, index) => {
                // Supprimer toutes les bordures et marges des cartes
                card.style.setProperty('border', 'none', 'important');
                card.style.setProperty('margin', '0', 'important');
                card.style.setProperty('box-shadow', 'none', 'important');
                
                // Dimensions fixes pour le scroll horizontal
                card.style.setProperty('width', `${cardWidth}px`, 'important');
                card.style.setProperty('min-width', `${cardWidth}px`, 'important');
                card.style.setProperty('max-width', `${cardWidth}px`, 'important');
                card.style.setProperty('height', '70px', 'important');
                card.style.setProperty('min-height', '70px', 'important');
                card.style.setProperty('max-height', '70px', 'important');
                card.style.setProperty('flex', '0 0 auto', 'important');
                card.style.setProperty('padding', '6px 4px', 'important');
                card.style.setProperty('font-size', '0.65rem', 'important');
                card.style.setProperty('box-sizing', 'border-box', 'important');
                card.style.setProperty('overflow', 'hidden', 'important');
                card.style.setProperty('text-align', 'center', 'important');
                
                // OPTIMISATIONS TACTILES POUR LES CARTES
                card.style.setProperty('touch-action', 'manipulation', 'important');
                card.style.setProperty('-webkit-user-select', 'none', 'important');
                card.style.setProperty('-webkit-touch-callout', 'none', 'important');
                card.style.setProperty('pointer-events', 'auto', 'important');
                
                // STYLES SPÉCIFIQUES MODE JOUR - GARDER FOND BLANC
                const isDarkMode = document.body.classList.contains('dark-mode');
                if (!isDarkMode) {
                    // MODE JOUR UNIQUEMENT - Garder le fond blanc original mais améliorer les bordures
                    card.style.setProperty('border', '2px solid rgba(0, 123, 255, 0.6)', 'important');
                    card.style.setProperty('box-shadow', '0 4px 12px rgba(0, 0, 0, 0.15)', 'important');
                    // NE PAS changer le background - garder le fond blanc original
                }
                
                // Empêcher le scroll sur les cartes elles-mêmes
                card.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                    addDebugLog(`🎯 Carte ${index + 1} touchée`);
                }, { passive: true });
                
                card.addEventListener('touchmove', function(e) {
                    // Permettre le scroll du conteneur parent
                    // Ne pas empêcher la propagation ici
                }, { passive: true });
                
                // Forcer la visibilité du texte et des icônes - MODE JOUR/NUIT
                const icon = card.querySelector('.action-icon');
                const text = card.querySelector('.action-text');
                if (icon) {
                    icon.style.setProperty('font-size', '1.1rem', 'important');
                    icon.style.setProperty('margin-bottom', '3px', 'important');
                    icon.style.setProperty('display', 'block', 'important');
                    icon.style.setProperty('visibility', 'visible', 'important');
                    icon.style.setProperty('line-height', '1', 'important');
                    
                    // COULEUR ICÔNE SELON LE MODE
                    if (!isDarkMode) {
                        // MODE JOUR - Icônes sombres sur fond blanc (CORRIGÉ)
                        icon.style.setProperty('color', '#1a1a1a', 'important');
                        icon.style.setProperty('opacity', '1', 'important');
                        icon.style.setProperty('text-shadow', '0 1px 2px rgba(255, 255, 255, 0.8)', 'important');
                        icon.style.setProperty('filter', 'drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1))', 'important');
                    }
                }
                if (text) {
                    text.style.setProperty('font-size', '0.55rem', 'important');
                    text.style.setProperty('display', 'block', 'important');
                    text.style.setProperty('visibility', 'visible', 'important');
                    text.style.setProperty('white-space', 'nowrap', 'important');
                    text.style.setProperty('overflow', 'hidden', 'important');
                    text.style.setProperty('text-overflow', 'ellipsis', 'important');
                    text.style.setProperty('line-height', '1', 'important');
                    text.style.setProperty('margin', '0', 'important');
                    text.style.setProperty('padding', '0', 'important');
                    text.style.setProperty('font-weight', '600', 'important');
                    
                    // COULEUR TEXTE SELON LE MODE
                    if (!isDarkMode) {
                        // MODE JOUR - Texte sombre sur fond blanc (CORRIGÉ)
                        text.style.setProperty('color', '#1a1a1a', 'important');
                        text.style.setProperty('opacity', '1', 'important');
                        text.style.setProperty('text-shadow', '0 1px 2px rgba(255, 255, 255, 0.8)', 'important');
                        text.style.setProperty('filter', 'drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1))', 'important');
                    }
                }
            });
            
            // AJOUTER DES INDICATEURS DE SWIPE
            const totalCardsWidth = (cardWidth + 8) * cards.length - 8; // largeur totale des cartes + gaps
            const needsScroll = totalCardsWidth > screenWidth;
            
            if (needsScroll) {
                // Ajouter un indicateur de swipe à droite
                const swipeIndicator = document.createElement('div');
                swipeIndicator.innerHTML = '→';
                swipeIndicator.style.cssText = `
                    position: absolute !important;
                    right: 5px !important;
                    top: 50% !important;
                    transform: translateY(-50%) !important;
                    background: rgba(0, 255, 255, 0.8) !important;
                    color: white !important;
                    border-radius: 50% !important;
                    width: 25px !important;
                    height: 25px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    font-size: 14px !important;
                    font-weight: bold !important;
                    z-index: 1000 !important;
                    pointer-events: none !important;
                    animation: pulse 2s infinite !important;
                `;
                
                scrollContainer.style.position = 'relative';
                scrollContainer.appendChild(swipeIndicator);
                
                // Masquer l'indicateur après scroll
                scrollContainer.addEventListener('scroll', function() {
                    if (this.scrollLeft > 10) {
                        swipeIndicator.style.opacity = '0';
                    } else {
                        swipeIndicator.style.opacity = '1';
                    }
                });
            }
            
            addDebugLog(`🔧 SCROLL HORIZONTAL ACTIVÉ - Écran: ${screenWidth}px`);
            addDebugLog(`📐 Largeur carte fixe: ${cardWidth}px`);
            addDebugLog(`📏 Largeur totale cartes: ${totalCardsWidth}px`);
            addDebugLog(`🎯 Scroll nécessaire: ${needsScroll ? 'OUI' : 'NON'}`);
            addDebugLog(`⚡ Swipe de gauche à droite activé`);
            
            // Vérifier le mode et les styles appliqués
            const currentMode = document.body.classList.contains('dark-mode');
            addDebugLog(`🎨 Mode détecté: ${currentMode ? 'NUIT (dark-mode)' : 'JOUR (light-mode)'}`);
            addDebugLog(`🎨 Styles mode jour: ${currentMode ? 'NON appliqués' : 'OUI appliqués (fond BLANC + texte SOMBRE)'}`);
            addDebugLog(`🎯 Cartes stylées: ${cards.length} avec fond blanc préservé`);
            
            // Vérifier le résultat
            setTimeout(() => {
                const computed = window.getComputedStyle(grid);
                addDebugLog('🎯 Grille après forçage:');
                addDebugLog(`📐 Colonnes: ${computed.gridTemplateColumns}`);
                addDebugLog(`📏 Gap: ${computed.gap}`);
                addDebugLog(`📦 Display: ${computed.display}`);
                addDebugLog(`📏 Width: ${computed.width}`);
                
                let totalWidth = 0;
                cards.forEach((card, index) => {
                    const rect = card.getBoundingClientRect();
                    totalWidth += rect.width;
                    addDebugLog(`Carte ${index + 1}: ${Math.round(rect.width)}px x ${Math.round(rect.height)}px`);
                });
                
                addDebugLog(`📊 Largeur totale cartes: ${Math.round(totalWidth)}px`);
                addDebugLog(`📊 Débordement: ${Math.round(totalWidth + 15) > screenWidth ? 'OUI' : 'NON'}`);
            }, 100);
            
        } else {
            addDebugLog('❌ Grille non trouvée !');
        }
    } else {
        addDebugLog('💻 Mode desktop détecté - pas de forçage 1x4');
    }
});
</script>

<!-- 📱 FORÇAGE ULTRA-SPÉCIFIQUE 1x4 MOBILE -->



<!-- Correction pour tableaux côte à côte -->


<div class="modern-dashboard futuristic-dashboard-container futuristic-enabled">
    <!-- Éléments futuristes de base (générés par JS) -->
    
    <!-- Actions rapides -->
    <?php include 'components/quick-actions.php'; ?>

    <!-- État des réparations -->
    <div class="statistics-container futuristic-card">
        <h3 class="section-title holographic-text">État des réparations</h3>
        <div class="statistics-grid futuristic-stats-grid">
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="stat-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $reparations_actives; ?></div>
                    <div class="stat-label stat-label-futuristic">Réparation</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=taches" class="stat-card progress-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $taches_recentes_count; ?></div>
                    <div class="stat-label stat-label-futuristic">Tâche</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=commandes_pieces" class="stat-card waiting-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $commandes_en_attente_count; ?></div>
                    <div class="stat-label stat-label-futuristic">Commande</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=reparations&urgence=1" class="stat-card clients-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $reparations_en_cours; ?></div>
                    <div class="stat-label stat-label-futuristic">Urgence</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Tableaux côte à côte -->
    <div class="dashboard-tables-container futuristic-tables-container">
        
        <!-- Tableau 1: Tâches en cours -->
        <div class="simple-table-section">
            <h4 class="table-title">
                <i class="fas fa-tasks"></i>
                <a href="index.php?page=taches" style="text-decoration: none; color: inherit;">
                    Tâches en cours
                    <span class="badge bg-primary ms-2"><?php echo $taches_recentes_count; ?></span>
                </a>
            </h4>
            <div class="modern-tabs" style="margin-bottom: 1rem;">
                <button class="modern-tab-button active" data-tab="toutes-taches">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-taches">Mes tâches</button>
            </div>
            <!-- 🎯 TABLEAU TÂCHES PARFAITEMENT ALIGNÉ -->
            <div class="table-container">
                <div class="tab-content active" id="toutes-taches">
                    <div class="modern-table">
                        <div class="modern-table-columns">
                            <span style="flex: 1;">Titre</span>
                            <span style="width: 30%; text-align: center;">Priorité</span>
                        </div>
                        <?php
                        $toutes_taches = get_toutes_taches_en_cours(10);
                        if (!empty($toutes_taches)) :
                            foreach ($toutes_taches as $index => $tache) :
                                $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="modern-table-indicator taches"></div>
                                <div class="modern-table-cell primary">
                                    <span class="modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></span>
                                </div>
                                <div class="modern-table-cell" style="width: 30%; text-align: center;">
                                    <span class="modern-badge <?php echo $urgence_class; ?>"><?php echo htmlspecialchars($tache['urgence']); ?></span>
                                </div>
                            </div>
                        <?php
                            endforeach;
                        else :
                        ?>
                            <div class="modern-table-empty">
                                <i class="fas fa-tasks"></i>
                                <div class="title">Aucune tâche en cours</div>
                                <p class="subtitle">Toutes les tâches ont été complétées</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tab-content" id="mes-taches">
                    <div class="modern-table">
                            <div class="modern-table-columns">
                                <span style="flex: 1;">Titre</span>
                                <span style="width: 30%; text-align: center;">Priorité</span>
                            </div>
                            <?php
                            $mes_taches = get_taches_en_cours(10);
                            if (!empty($mes_taches)) :
                                foreach ($mes_taches as $index => $tache) :
                                    $urgence_class = get_urgence_class($tache['urgence']);
                            ?>
                                <div class="modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                    <div class="modern-table-indicator taches"></div>
                                    <div class="modern-table-cell primary">
                                        <span class="modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></span>
                                    </div>
                                    <div class="modern-table-cell" style="width: 30%; text-align: center;">
                                        <span class="modern-badge <?php echo $urgence_class; ?>"><?php echo htmlspecialchars($tache['urgence']); ?></span>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <div class="modern-table-empty">
                                    <i class="fas fa-tasks"></i>
                                    <div class="title">Aucune tâche en cours</div>
                                    <p class="subtitle">Toutes les tâches ont été complétées</p>
                                </div>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau 2: Réparations récentes -->
        <div class="simple-table-section">
            <h4 class="table-title">
                <i class="fas fa-wrench"></i>
                <a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">
                    Réparations récentes
                    <span class="badge bg-primary ms-2"><?php echo $reparations_recentes_count; ?></span>
                </a>
            </h4>
            <div class="modern-table">
                        <div class="modern-table-columns">
                            <span style="flex: 1;">Client</span>
                            <span style="width: 35%;">Modèle</span>
                            <span style="width: 25%; text-align: center;">Date</span>
                        </div>
                        <?php if (count($reparations_recentes) > 0): ?>
                            <?php foreach ($reparations_recentes as $index => $reparation): ?>
                                <div class="modern-table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                    <div class="modern-table-indicator reparations"></div>
                                    <div class="modern-table-cell primary">
                                        <div class="modern-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span class="modern-table-text"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="modern-table-cell secondary">
                                        <span class="modern-table-subtext"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></span>
                                    </div>
                                    <div class="modern-table-cell tertiary">
                                        <div class="modern-date-badge">
                                            <span><?php echo format_date($reparation['date_reception'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="modern-table-empty">
                                <i class="fas fa-wrench"></i>
                                <div class="title">Aucune réparation récente</div>
                                <p class="subtitle">Aucune réparation en cours actuellement</p>
                            </div>
                        <?php endif; ?>
                    </div>
        </div>

        <!-- Tableau 3: Commandes à traiter -->
        <div class="simple-table-section">
            <h4 class="table-title">
                <i class="fas fa-shopping-cart"></i>
                <a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">
                    Commandes à traiter
                </a>
            </h4>
            <div class="modern-table">
                        <div class="modern-table-columns">
                            <span style="flex: 1;">Pièce</span>
                            <span style="width: 30%; text-align: center;">Statut</span>
                            <span style="width: 25%; text-align: center;">Date</span>
                        </div>
                        <?php if (count($commandes_recentes) > 0): ?>
                            <?php foreach ($commandes_recentes as $index => $commande): ?>
                                <?php 
                                $status_class = '';
                                $status_text = '';
                                switch($commande['statut']) {
                                    case 'en_attente':
                                        $status_class = 'warning';
                                        $status_text = 'En attente';
                                        break;
                                    case 'commande':
                                        $status_class = 'info';
                                        $status_text = 'Commandé';
                                        break;
                                    case 'recue':
                                        $status_class = 'info';
                                        $status_text = 'Reçu';
                                        break;
                                    case 'urgent':
                                        $status_class = 'danger';
                                        $status_text = 'URGENT';
                                        break;
                                }
                                ?>
                                <div class="modern-table-row" data-commande-id="<?php echo $commande['id']; ?>" onclick="afficherDetailsCommande(event, <?php echo $commande['id']; ?>)">
                                    <div class="modern-table-indicator commandes"></div>
                                    <div class="modern-table-cell primary" title="<?php echo htmlspecialchars($commande['nom_piece']); ?>">
                                        <div class="modern-avatar" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                            <i class="fas fa-cog" style="color: #666;"></i>
                                        </div>
                                        <span class="modern-table-text">
                                            <?php echo mb_strimwidth(htmlspecialchars($commande['nom_piece']), 0, 30, "..."); ?>
                                        </span>
                                    </div>
                                    <div class="modern-table-cell" style="width: 30%; text-align: center;">
                                        <span class="modern-badge <?php echo $status_class; ?> status-clickable" 
                                              onclick="ouvrirModalStatut(event, <?php echo $commande['id']; ?>, '<?php echo $commande['statut']; ?>', '<?php echo htmlspecialchars($commande['reference']); ?>', '<?php echo htmlspecialchars($commande['nom_piece']); ?>')" 
                                              data-commande-id="<?php echo $commande['id']; ?>" 
                                              data-statut="<?php echo $commande['statut']; ?>"
                                              title="Cliquer pour changer le statut">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    <div class="modern-table-cell tertiary">
                                        <div class="modern-date-badge">
                                            <span><?php echo format_date($commande['date_creation']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="modern-table-empty">
                                <i class="fas fa-shopping-cart"></i>
                                <div class="title">Aucune commande récente</div>
                                <p class="subtitle">Aucune commande en attente de traitement</p>
                            </div>
                        <?php endif; ?>
                    </div>
        </div>
    </div>

    <!-- Statistiques journalières -->
    <div class="statistics-container mt-4">
        <h3 class="section-title">Statistiques du jour</h3>
        <div class="statistics-grid">
            <div class="stat-card daily-stats-card" onclick="openStatsModal('nouvelles_reparations')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['nouvelles_reparations']; ?></div>
                    <div class="stat-label">Nouvelles réparations</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
            
            <div class="stat-card daily-stats-card" onclick="openStatsModal('reparations_effectuees')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['reparations_effectuees']; ?></div>
                    <div class="stat-label">Réparations effectuées</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            
            <div class="stat-card daily-stats-card" onclick="openStatsModal('reparations_restituees')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['reparations_restituees']; ?></div>
                    <div class="stat-label">Réparations restituées</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-area"></i>
                </div>
            </div>
            
            <div class="stat-card daily-stats-card" onclick="openStatsModal('devis_envoyes')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['devis_envoyes']; ?></div>
                    <div class="stat-label">Devis envoyés</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-pie"></i>
                </div>
                    </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques pour le modal de recherche client -->


<!-- Modal de recherche client -->
<div class="modal fade" id="searchClientModal" tabindex="-1" aria-labelledby="searchClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchClientModalLabel">Rechercher un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="clientSearchInput" placeholder="Nom, téléphone ou email">
                        </div>
                    <div id="searchResults" class="search-results">
                        <!-- Résultats de recherche apparaîtront ici -->
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Indicateurs principaux -->
</div>

<!-- Inclure les scripts pour le dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/modal-commande.js"></script>
<script src="assets/js/commandes-details.js"></script>
<script src="assets/js/commande-statut.js"></script>
<script src="assets/js/dashboard-commands.js"></script>
<script src="assets/js/client-historique.js"></script>
<script src="assets/js/taches.js"></script>
<script src="assets/js/dashboard-stats.js"></script>

<!-- Scripts pour interface futuriste -->
<script src="assets/js/dashboard-futuristic.js"></script>
<script src="assets/js/network-background.js"></script>

<!-- 🔧 Correction backdrop modal commande -->
<script src="assets/js/modal-commande-backdrop-fix.js"></script>

<!-- 🧹 Modal futuriste CLEAN - VERSION SANS INTERFÉRENCES -->
<script src="assets/js/modal-futuriste-clean.js?v=<?php echo time(); ?>_CLEAN_VERSION"></script>

<!-- 🔍 Correction recherche client modal commande -->
<script src="assets/js/modal-commande-search-fix.js"></script>

<!-- 🖱️ Correction problème clics page d'accueil -->
<script src="assets/js/homepage-click-fix.js"></script>

<!-- 🎯 Correction cartes dashboard État des réparations - VERSION SIMPLE -->
<script src="assets/js/dashboard-cards-simple-fix.js"></script>


<!-- Script supprimé - optimisations manuelles appliquées -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button, .modern-tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<!-- Modal futuriste GeekBoard pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- En-tête futuriste compact avec badges -->
            <div class="modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="taskDetailsModalLabel" style="margin:0;">Détails de la tâche</h5>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="modern-priority-badge" id="task-priority"></span>
                            <span class="modern-status-badge" id="task-status">En attente</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal en deux colonnes -->
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row g-3">
                        <!-- Colonne gauche: titre + description + pièces jointes -->
                        <div class="col-12 col-lg-8">
                            <div class="futuristic-card" style="margin-bottom:1rem;">
                                <h3 class="section-title holographic-text" style="margin-bottom:1rem;">Titre</h3>
                                <h4 id="task-title" class="modern-task-title" style="margin:0;"></h4>
                            </div>

                            <div class="futuristic-card" style="margin-bottom:1rem;">
                                <h3 class="section-title holographic-text" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-file-alt"></i>
                                    Description
                                </h3>
                                <div class="description-content">
                                    <div id="task-description-loader" class="description-loader">
                                        <div class="loader-spinner"></div>
                                        <span>Chargement des détails...</span>
                                    </div>
                                    <p id="task-description" class="modern-description" style="display:none;"></p>
                                </div>
                            </div>

                            <div id="task-attachments" class="futuristic-card" style="display:none;">
                                <h3 class="section-title holographic-text" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-paperclip"></i>
                                    Pièces jointes
                                </h3>
                                <div class="attachments-content">
                                    <div id="task-attachments-list" class="attachments-list"></div>
                                </div>
                            </div>

                            <div id="task-error-container" class="modern-error-container" style="display:none;margin-top:1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span class="error-message"></span>
                            </div>
                        </div>

                        <!-- Colonne droite: méta-informations -->
                        <div class="col-12 col-lg-4">
                            <div class="futuristic-card">
                                <h3 class="section-title holographic-text" style="margin-bottom:1rem;">Informations</h3>
                                <div class="statistics-grid" style="display:grid;grid-template-columns:1fr;gap:12px;">
                                    <div class="stat-card futuristic-stat-card" style="padding:1rem;">
                                        <div class="stat-icon stat-icon-futuristic" style="margin-bottom:0.75rem;">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-label stat-label-futuristic">Date de création</div>
                                            <div id="task-created-date" class="stat-value stat-value-futuristic">-</div>
                                        </div>
                                    </div>
                                    <div class="stat-card futuristic-stat-card" style="padding:1rem;">
                                        <div class="stat-icon stat-icon-futuristic" style="margin-bottom:0.75rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-label stat-label-futuristic">Assigné à</div>
                                            <div id="task-assignee" class="stat-value stat-value-futuristic">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied du modal avec actions (boutons standards stylés) -->
            <div class="modal-footer">
                <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                    <i class="fas fa-play me-2"></i> Démarrer
                </button>
                <button id="complete-task-btn" class="btn btn-secondary" data-task-id="" data-status="termine">
                    <i class="fas fa-check me-2"></i> Terminer
                </button>
                <a href="index.php?page=taches" id="voir-toutes-taches" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt me-2"></i> Voir toutes les tâches
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne pour afficher les détails d'une commande -->
<div class="modal fade" id="commandeDetailsModal" tabindex="-1" aria-labelledby="commandeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-task-modal">
            <!-- En-tête moderne avec dégradé -->
            <div class="modern-task-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="commandeDetailsModalLabel">Détails de la commande</h5>
                        <p class="modal-subtitle">Informations complètes</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-task-modal-body">
                <div class="commande-detail-container">
                    <!-- Section titre et statut -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="commande-reference" class="modern-task-title"></h4>
                            <p id="commande-piece-nom" class="task-subtitle"></p>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Statut</span>
                                    <span id="commande-statut" class="modern-priority-badge"></span>
                                </div>
                                <div class="task-status-container">
                                    <span class="status-label">Urgence</span>
                                    <span id="commande-urgence" class="modern-status-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loader pendant le chargement -->
                    <div id="commande-description-loader" class="description-loader">
                        <div class="loader-spinner"></div>
                        <span>Chargement des détails...</span>
                    </div>
                    
                    <!-- Contenu des détails de la commande -->
                    <div id="commande-details-content" style="display: none;">

                        <!-- Section Client -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-user section-icon"></i>
                                <h6 class="section-title">Informations Client</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-client" class="modern-description"></p>
                                <p id="commande-client-tel" class="task-subtitle"></p>
                            </div>
                        </div>

                        <!-- Section Fournisseur -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-truck section-icon"></i>
                                <h6 class="section-title">Fournisseur</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-fournisseur" class="modern-description"></p>
                            </div>
                        </div>

                        <!-- Section Détails de la pièce -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-cog section-icon"></i>
                                <h6 class="section-title">Détails de la pièce</h6>
                            </div>
                            <div class="description-content">
                                <div class="row mb-2">
                                    <div class="col-12">
                                        <div class="detail-item">
                                            <span class="detail-label">Nom de la pièce:</span>
                                            <span id="commande-piece-nom-detail" class="detail-value piece-name-value"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="detail-item">
                                            <span class="detail-label">Quantité:</span>
                                            <span id="commande-quantite" class="detail-value"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="detail-item">
                                            <span class="detail-label">Prix estimé:</span>
                                            <span id="commande-prix" class="detail-value price-value"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Code-barres et Date de création -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-barcode section-icon"></i>
                                <h6 class="section-title">Informations techniques</h6>
                            </div>
                            <div class="description-content">
                                <div class="row">
                                    <div id="commande-code-barre-section" class="col-md-6" style="display: none;">
                                        <div class="detail-item">
                                            <span class="detail-label">Code-barres:</span>
                                            <span id="commande-code-barre" class="detail-value font-monospace"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <span class="detail-label">Date de création:</span>
                                            <span id="commande-date-creation" class="detail-value"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Description -->
                        <div id="commande-description-section" class="task-description-section" style="display: none;">
                            <div class="section-header">
                                <i class="fas fa-file-alt section-icon"></i>
                                <h6 class="section-title">Description</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-description" class="modern-description"></p>
                            </div>
                        </div>

                        <!-- Section Dates importantes -->
                        <div class="task-description-section" id="commande-dates-section">
                            <div class="section-header">
                                <i class="fas fa-calendar section-icon"></i>
                                <h6 class="section-title">Dates importantes</h6>
                            </div>
                            <div class="description-content">
                                <div class="row">
                                    <div class="col-md-6" id="commande-date-commande-section" style="display: none;">
                                        <div class="detail-item">
                                            <span class="detail-label">Date de commande:</span>
                                            <span id="commande-date-commande" class="detail-value"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="commande-date-reception-section" style="display: none;">
                                        <div class="detail-item">
                                            <span class="detail-label">Date de réception:</span>
                                            <span id="commande-date-reception" class="detail-value"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Notes -->
                        <div id="commande-notes-section" class="task-description-section" style="display: none;">
                            <div class="section-header">
                                <i class="fas fa-sticky-note section-icon"></i>
                                <h6 class="section-title">Notes</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-notes" class="modern-description"></p>
                            </div>
                        </div>

                        <!-- Section Commentaire interne -->
                        <div id="commande-commentaire-section" class="task-description-section" style="display: none;">
                            <div class="section-header">
                                <i class="fas fa-comment section-icon"></i>
                                <h6 class="section-title">Commentaire interne</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-commentaire" class="modern-description"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section d'erreur -->
                    <div id="commande-error-container" class="task-description-section" style="display:none;">
                        <div class="section-header">
                            <i class="fas fa-exclamation-triangle section-icon text-danger"></i>
                            <h6 class="section-title text-danger">Erreur</h6>
                        </div>
                        <div class="description-content">
                            <p class="error-message modern-description text-danger">Une erreur est survenue lors du chargement des détails de la commande.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pied de page moderne -->
            <div class="modern-task-modal-footer">
                <div class="footer-actions">
                    <div class="primary-actions">
                        <a href="index.php?page=commandes_pieces" class="modern-action-btn view-all-btn">
                            <div class="btn-icon">
                                <i class="fas fa-list-ul"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Voir toutes</span>
                                <span class="btn-subtext">Toutes les commandes</span>
                            </div>
                        </a>
                    </div>
                    <div class="secondary-actions">
                        <button type="button" class="modern-action-btn close-btn" data-bs-dismiss="modal">
                            <div class="btn-icon">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Fermer</span>
                                <span class="btn-subtext">Fermer le modal</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne pour changer le statut d'une commande -->
<div class="modal fade" id="commandeStatutModal" tabindex="-1" aria-labelledby="commandeStatutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-task-modal">
            <!-- En-tête moderne avec dégradé -->
            <div class="modern-task-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="commandeStatutModalLabel">Changer le statut</h5>
                        <p class="modal-subtitle">Mettre à jour le statut de la commande</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-task-modal-body">
                <div class="statut-update-container">
                    <!-- Section titre et statut actuel -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="statut-commande-reference" class="modern-task-title"></h4>
                            <p id="statut-piece-nom" class="task-subtitle"></p>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Statut actuel</span>
                                    <span id="statut-actuel" class="modern-priority-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section choix du nouveau statut -->
                    <div class="task-description-section">
                        <div class="section-header">
                            <i class="fas fa-list-alt section-icon"></i>
                            <h6 class="section-title">Choisir le nouveau statut</h6>
                        </div>
                        <div class="description-content">
                            <div class="status-options-grid">
                                <div class="status-option" data-status="en_attente">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-warning">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">En attente</h6>
                                            <p class="status-description">Pas encore commandé</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="commande">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-primary">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Commandé</h6>
                                            <p class="status-description">Commande en cours</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="recue">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-success">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Reçu</h6>
                                            <p class="status-description">Pièce réceptionnée</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="utilise">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-info">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Utilisé</h6>
                                            <p class="status-description">Pièce installée</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="urgent">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">URGENT</h6>
                                            <p class="status-description">Priorité maximale</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="a_retourner">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-secondary">
                                            <i class="fas fa-undo"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">À retourner</h6>
                                            <p class="status-description">Retour fournisseur</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="annulee">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-dark">
                                            <i class="fas fa-times"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Annulé</h6>
                                            <p class="status-description">Commande annulée</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="termine">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-success">
                                            <i class="fas fa-flag-checkered"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Terminé</h6>
                                            <p class="status-description">Processus terminé</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section d'erreur -->
                    <div id="statut-error-container" class="task-description-section" style="display:none;">
                        <div class="section-header">
                            <i class="fas fa-exclamation-triangle section-icon text-danger"></i>
                            <h6 class="section-title text-danger">Erreur</h6>
                        </div>
                        <div class="description-content">
                            <p class="error-message modern-description text-danger">Une erreur est survenue lors de la mise à jour du statut.</p>
                        </div>
                    </div>
                    
                    <!-- Loader -->
                    <div id="statut-update-loader" class="description-loader" style="display: none;">
                        <div class="loader-spinner"></div>
                        <span>Mise à jour en cours...</span>
                    </div>
                </div>
            </div>
            
            <!-- Pied de page moderne (sans bouton fermer) -->
            <div class="modern-task-modal-footer" style="display: none;">
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne des statistiques -->
<div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modern-stats-modal">
            <!-- En-tête du modal avec dégradé -->
            <div class="modern-stats-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon-stats">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="statsModalLabel">Statistiques détaillées</h5>
                        <p class="modal-subtitle" id="statsModalSubtitle">Analyse des données</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-stats-modal-body">
                <!-- Filtres -->
                <div class="stats-filters-section">
                    <div class="filters-container">
                        <div class="filter-group">
                            <label class="filter-label">Période</label>
                            <div class="filter-buttons">
                                <button class="filter-btn active" data-period="day" onclick="changePeriod('day')">
                                    <i class="fas fa-calendar-day"></i>
                                    Jour
                                </button>
                                <button class="filter-btn" data-period="week" onclick="changePeriod('week')">
                                    <i class="fas fa-calendar-week"></i>
                                    Semaine
                                </button>
                                <button class="filter-btn" data-period="month" onclick="changePeriod('month')">
                                    <i class="fas fa-calendar-alt"></i>
                                    Mois
                                </button>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Date spécifique</label>
                            <div class="date-picker-container">
                                <input type="date" id="specificDate" class="form-control modern-date-input" 
                                       value="<?php echo date('Y-m-d'); ?>" onchange="changeSpecificDate()">
                                <button class="btn btn-outline-primary btn-sm" onclick="resetToToday()">
                                    <i class="fas fa-calendar-check"></i>
                                    Aujourd'hui
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section graphique -->
                <div class="stats-chart-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h6 class="chart-title" id="chartTitle">Évolution des nouvelles réparations</h6>
                            <div class="chart-controls">
                                <div class="chart-legend" id="chartLegend">
                                    <!-- Légende dynamique -->
                                </div>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="statsChart" width="800" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Section tableau de données -->
                <div class="stats-table-section">
                    <div class="table-header">
                        <h6 class="table-title">Données détaillées</h6>
                        <div class="table-actions">
                            <button class="modern-export-btn" onclick="exportStatsData()">
                                <i class="fas fa-download"></i>
                                <span>Exporter</span>
                            </button>
                        </div>
                    </div>
                    <div class="modern-table-container" id="tableContainer">
                        <!-- Indicateurs de défilement -->
                        <div class="scroll-indicator-top" id="scrollIndicatorTop"></div>
                        <div class="scroll-indicator-bottom" id="scrollIndicatorBottom"></div>
                        
                        <!-- Hint de défilement -->
                        <div class="scroll-hint" id="scrollHint">
                            Faites défiler pour voir plus
                            <i class="fas fa-arrows-alt-v"></i>
                        </div>
                        
                        <div class="modern-data-table" id="statsTable">
                            <div class="modern-table-header" id="statsTableHeader">
                                <!-- En-têtes dynamiques -->
                            </div>
                            <div class="modern-table-scrollable" id="tableScrollable">
                                <div class="modern-table-body" id="statsTableBody">
                                    <!-- Données dynamiques -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loader -->
                <div id="statsLoader" class="stats-loader" style="display: none;">
                    <div class="loader-spinner"></div>
                    <span>Chargement des statistiques...</span>
                </div>
                
                <!-- Message d'erreur -->
                <div id="statsError" class="stats-error-container" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="error-message"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le modal des statistiques -->



</div>
</div>
</div>

</div> <!-- Fermeture de mainContent -->



<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});
</script>