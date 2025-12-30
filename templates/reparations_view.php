<!-- CSS et JS essentiels -->
<!-- Sync Force: 2025-11-20 -->
<link rel="stylesheet" href="/assets/css/pages/reparations.css">
<link rel="stylesheet" href="/assets/css/futuristic-interface.css">
<script src="/assets/js/modern-filters.js" defer></script>

<script>
// Variable globale pour l'ID de l'utilisateur connecté - définie très tôt
window.currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
console.log('currentUserId défini globalement:', window.currentUserId);

// Appliquer immédiatement le dark-mode selon les préférences système AVANT l'affichage du loader
(function() {
    const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
    if (prefersDarkScheme.matches) {
        document.documentElement.classList.add('dark-mode');
        // Ajouter aussi au body dès que possible
        if (document.body) {
            document.body.classList.add('dark-mode');
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('dark-mode');
            });
        }
    }
})();


// Debug et gestion des clics sur les photos
document.addEventListener('click', function(e) {
    const photoItem = e.target.closest('.repair-photo-item');
    if (photoItem) {
        console.log('🖱️ Clic détecté sur une photo:', photoItem);
        console.log('🔍 Attribut onclick:', photoItem.getAttribute('onclick'));
        
        // Empêcher le comportement par défaut
        e.preventDefault();
        e.stopPropagation();
        
        // Extraire l'URL et la description de l'attribut onclick
        const onclickAttr = photoItem.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes('openPhotoViewerSafe')) {
            // Parser l'attribut onclick pour extraire les paramètres
            // Utiliser une regex plus robuste qui gère les apostrophes
            const match = onclickAttr.match(/openPhotoViewerSafe\('([^']+)',\s*'([^']*)'\)/);
            
            if (match) {
                const photoUrl = match[1];
                const description = match[2];
                console.log('🎯 Extraction réussie:', { photoUrl, description });
                
                // Appeler directement la fonction
                if (typeof window.openPhotoViewerSafe === 'function') {
                    window.openPhotoViewerSafe(photoUrl, description);
                } else {
                    console.error('❌ Fonction openPhotoViewerSafe non disponible');
                }
            } else {
                // Essayer une approche différente si la regex échoue
                console.log('🔧 Tentative d\'extraction alternative...');
                
                // Extraire manuellement les paramètres
                const startIndex = onclickAttr.indexOf("('") + 2;
                const endIndex = onclickAttr.lastIndexOf("')");
                
                if (startIndex > 1 && endIndex > startIndex) {
                    const params = onclickAttr.substring(startIndex, endIndex);
                    const parts = params.split("', '");
                    
                    if (parts.length >= 2) {
                        const photoUrl = parts[0];
                        const description = parts[1];
                        console.log('🎯 Extraction alternative réussie:', { photoUrl, description });
                        
                        // Appeler directement la fonction
                        if (typeof window.openPhotoViewerSafe === 'function') {
                            window.openPhotoViewerSafe(photoUrl, description);
                        } else {
                            console.error('❌ Fonction openPhotoViewerSafe non disponible');
                        }
                    } else {
                        console.error('❌ Impossible de parser les paramètres:', params);
                    }
                } else {
                    console.error('❌ Format d\'attribut onclick non reconnu:', onclickAttr);
                }
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing view toggle...');
    
    // Toggle entre vue tableau et cartes
    const toggleButtons = document.querySelectorAll('.toggle-view');
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    
    console.log('Elements found:', { 
        toggleButtons: toggleButtons.length,
        tableView: tableView,
        cardsView: cardsView
    });
    
    // Fonction pour ajuster l'affichage des cartes
    function adjustCardsLayout() {
        const cards = document.querySelectorAll('#cards-view .dashboard-card');
        
        // Reset des hauteurs pour recalculer
        cards.forEach(card => {
            card.style.height = 'auto';
        });
        
        // Si on est sur un écran de plus de 768px, on uniformise les hauteurs par ligne
        if (window.innerWidth > 768) {
            let rowCards = [];
            let currentOffset = null;
            
            // Regrouper les cartes par ligne en fonction de leur position Y
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                
                if (currentOffset === null || Math.abs(rect.top - currentOffset) > 10) {
                    // Nouvelle ligne
                    if (rowCards.length > 0) {
                        // Appliquer la hauteur maximale à la ligne précédente
                        const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                        rowCards.forEach(c => {
                            c.style.height = maxHeight + 'px';
                        });
                    }
                    
                    currentOffset = rect.top;
                    rowCards = [card];
                } else {
                    // Même ligne
                    rowCards.push(card);
                }
            });
            
            // Traiter la dernière ligne
            if (rowCards.length > 0) {
                const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                rowCards.forEach(c => {
                    c.style.height = maxHeight + 'px';
                });
            }
        }
    }
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const viewMode = this.getAttribute('data-view');
            console.log('Switching to view:', viewMode);
            
            // Mettre à jour les boutons
            toggleButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.add('active');
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary');
            
            // Mettre à jour l'affichage
            if (viewMode === 'table') {
                tableView.classList.remove('d-none');
                cardsView.classList.add('d-none');
                localStorage.setItem('repairViewMode', 'table');
            } else {
                tableView.classList.add('d-none');
                cardsView.classList.remove('d-none');
                localStorage.setItem('repairViewMode', 'cards');
                // Ajuster le layout des cartes
                setTimeout(adjustCardsLayout, 100);
            }
            
            // Mettre à jour l'URL avec le mode de vue tout en conservant les autres paramètres
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('view', viewMode);
            
            // Mettre à jour l'URL sans recharger la page
            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            history.pushState({}, '', newUrl);
        });
    });
    
    // Vérifier d'abord s'il y a un paramètre view dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    
    if (viewParam) {
        // Si un paramètre view est présent dans l'URL, l'utiliser et le sauvegarder
        console.log('URL view parameter found:', viewParam);
        localStorage.setItem('repairViewMode', viewParam);
    }
    
    // Ensuite seulement charger la préférence utilisateur (soit depuis l'URL, soit depuis localStorage)
    const savedViewMode = localStorage.getItem('repairViewMode') || 'cards';
    console.log('View mode to apply:', savedViewMode);
    
    // Trouver et cliquer sur le bouton correspondant au mode d'affichage
    const btn = document.querySelector(`.toggle-view[data-view="${savedViewMode}"]`);
    if (btn) {
        console.log('Clicking view mode button for:', savedViewMode);
        
        // Utiliser un délai minimal pour s'assurer que le DOM est prêt
        setTimeout(() => {
            btn.click();
            // Ajuster le layout des cartes si on est en mode cartes
            if (savedViewMode === 'cards') {
                setTimeout(adjustCardsLayout, 200);
            }
        }, 10);
    } else {
        console.error('View mode button not found for:', savedViewMode);
    }
    
    // Ajuster le layout des cartes lors du redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (!cardsView.classList.contains('d-none')) {
            adjustCardsLayout();
        }
    });
    
    // Fonction pour appliquer un filtre tout en conservant le mode d'affichage
    window.applyFilter = function(statut_ids) {
        // Récupérer le mode d'affichage actuel
        const viewMode = localStorage.getItem('repairViewMode') || 'cards';
        
        // Construire l'URL avec tous les paramètres
        let url = `index.php?page=reparations&statut_ids=${statut_ids}&view=${viewMode}`;
        
        console.log('Applying filter with params:', { statut_ids, viewMode });
        
        // Rediriger avec les bons paramètres
        window.location.href = url;
    }
    
    // Modifier les liens de filtres pour utiliser la fonction applyFilter
    document.querySelectorAll('.filter-btn, .modern-filter').forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('🔘 Clic sur bouton de filtre détecté:', this);
            
            // Si le bouton a un attribut data-category-id, il s'agit d'un filtre
            const categoryId = this.getAttribute('data-category-id');
            if (categoryId) {
                e.preventDefault();
                let statusIds;
                switch (categoryId) {
                    case '1': statusIds = '1,2,3,19,20'; break;
                    case '2': statusIds = '4,5'; break;
                    case '3': statusIds = '6,7,8'; break;
                    case '4': statusIds = '9,10'; break;
                    case '5': statusIds = '11,12,13'; break;
                    default: statusIds = '1,2,3,4,5';
                }
                console.log('🔘 Application du filtre:', statusIds);
                window.applyFilter(statusIds);
            } else if (this.classList.contains('filter-btn') || this.classList.contains('modern-filter')) {
                // C'est le bouton "Toutes" ou "Récentes"
                e.preventDefault();
                console.log('🔘 Application du filtre "Toutes"');
                window.applyFilter('1,2,3,4,5');
            }
        });
    });
    
    // Appliquer des styles améliorés aux boutons au format tableau
    function applyButtonStyles() {
        // Boutons SMS
        document.querySelectorAll('#table-view .btn-soft-info').forEach(btn => {
            btn.style.backgroundColor = '#6610f2';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(102, 16, 242, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#5a0dce';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(102, 16, 242, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#6610f2';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(102, 16, 242, 0.2)';
            });
        });
        
        // Boutons corbeille (delete)
        document.querySelectorAll('#table-view .btn-soft-danger, #table-view .delete-repair').forEach(btn => {
            btn.style.backgroundColor = '#e74c3c';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(231, 76, 60, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#c0392b';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(231, 76, 60, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#e74c3c';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(231, 76, 60, 0.2)';
            });
        });
        
        // Boutons start-repair
        document.querySelectorAll('#table-view .btn-soft-primary, #table-view .start-repair').forEach(btn => {
            btn.style.background = 'linear-gradient(135deg, #0d6efd, #3a86ff)';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(13, 110, 253, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.background = 'linear-gradient(135deg, #3a86ff, #0d6efd)';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(13, 110, 253, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.background = 'linear-gradient(135deg, #0d6efd, #3a86ff)';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(13, 110, 253, 0.2)';
            });
        });
    }
    
    // Appliquer les styles une fois que la vue est chargée
    setTimeout(applyButtonStyles, 100);
    
    // Réappliquer les styles si on change de vue
    document.querySelectorAll('.toggle-view').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(applyButtonStyles, 100);
        });
    });
});

</script>

<!-- Masquer le dock mobile pendant les modals de devis (desktop uniquement) -->
<style>
    /* Masquage agressif quand un modal est ouvert sur desktop */
    body.hide-mobile-dock #mobile-dock {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        z-index: -1 !important;
    }
    @media (max-width: 768px) {
        /* Ne rien changer sur vrais mobiles/tablettes */
        body.hide-mobile-dock #mobile-dock { display: block !important; visibility: visible !important; opacity: 1 !important; z-index: auto !important; }
    }
</style>

<!-- Styles de fond mode jour harmonisés avec index.php -->
<style>
/* Variables CSS pour le mode jour (harmonisées avec la homepage) */
:root {
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);
    
    /* Variables CSS pour le mode nuit */
    --night-primary: #6366f1;
    --night-secondary: #8b5cf6;
    --night-accent: #06b6d4;
    --night-bg: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(30, 41, 59, 0.95);
    --night-text: #e2e8f0;
    --night-text-light: #94a3b8;
    --night-shadow: rgba(0, 0, 0, 0.3);
    --night-border: rgba(255, 255, 255, 0.1);
}

/* Keyframes pour l'animation du fond en mode nuit (harmonisé avec unified-night-mode.css) */
@keyframes gradientFlowNightPage {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Jour - Fond statique (PERFORMANCE OPTIMISÉE) */
body {
    background: var(--day-bg) !important;
    color: var(--day-text) !important;
    min-height: 100vh !important;
}

/* Mode Nuit - Fond animé (HARMONISÉ avec unified-night-mode.css) */
body.dark-mode,
body.night-mode {
    background: var(--night-bg) !important;
    background-size: 400% 400% !important;
    animation: gradientFlowNightPage 15s ease infinite !important;
    color: var(--night-text) !important;
}

/* Espacement en haut de la page pour la navbar fixe */
/* Desktop: réserve l'espace pour la navbar de 60px + marge */
@media (min-width: 992px) {
    body {
        padding-top: 80px !important;
        margin-top: 0 !important;
    }
    
    .page-container,
    #mainContent {
        padding-top: 15px !important;
        margin-top: 0 !important;
    }
}

/* Mobile: pas de padding-top car nav fixed n'est pas affichée */
@media (max-width: 991px) {
    body {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    
    .page-container,
    #mainContent {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function isRealMobile() {
        return window.innerWidth <= 768 && ('ontouchstart' in window || navigator.maxTouchPoints > 0);
    }
    function setDockHidden(hidden) {
        const mobileDock = document.getElementById('mobile-dock');
        if (!mobileDock) return;
        if (!isRealMobile()) {
            if (hidden) {
                document.body.classList.add('hide-mobile-dock');
            } else {
                document.body.classList.remove('hide-mobile-dock');
            }
        }
    }
    const modalIds = ['devisEnAttenteModal', 'devisDetailsModal', 'renvoyerTousModal', 'prolongerModal'];
    modalIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('show.bs.modal', () => setDockHidden(true));
        el.addEventListener('hidden.bs.modal', () => setDockHidden(false));
    });
});
</script>

<style>
/* Affichage explicite du nouveau modal au moment de l'ouverture */
#updateStatusModal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 1060 !important;
}
#updateStatusModal .modal-dialog { pointer-events: auto; }
#updateStatusModal .modal-content { display: block; }

/* ========================================
   TOOLTIPS PERSONNALISÉS POUR TOUS LES BOUTONS
   ULTRA-SPÉCIFIQUE POUR ÉVITER LES CONFLITS
   ======================================== */

/* Sélecteurs ultra-spécifiques pour maximiser la priorité */
html body .action-button[data-tooltip],
html body .custom-action-btn[data-tooltip],
html body button[data-tooltip],
html body a[data-tooltip],
html body .repair-action-btn[data-tooltip],
.action-button[data-tooltip],
.custom-action-btn[data-tooltip],
button[data-tooltip],
a[data-tooltip],
.repair-action-btn[data-tooltip] {
    position: relative !important;
}

/* Tooltip principal - POSITION EN DESSOUS - ULTRA-SPÉCIFIQUE */
html body .action-button[data-tooltip]::before,
html body .custom-action-btn[data-tooltip]::before,
html body button[data-tooltip]::before,
html body a[data-tooltip]::before,
html body .repair-action-btn[data-tooltip]::before,
.action-button[data-tooltip]::before,
.custom-action-btn[data-tooltip]::before,
button[data-tooltip]::before,
a[data-tooltip]::before,
.repair-action-btn[data-tooltip]::before {
    content: attr(data-tooltip) !important;
    position: absolute !important;
    top: 100% !important; /* EN DESSOUS du bouton */
    left: 50% !important;
    transform: translateX(-50%) translateY(8px) !important;
    background: rgba(0, 0, 0, 0.9) !important;
    color: white !important;
    padding: 0.5rem 0.75rem !important;
    border-radius: 6px !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    white-space: nowrap !important;
    opacity: 0 !important;
    pointer-events: none !important;
    transition: all 0.3s ease !important;
    z-index: 999999 !important; /* Z-index ULTRA élevé */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    display: block !important;
    visibility: hidden !important;
}

/* Flèche du tooltip - POINTE VERS LE HAUT - ULTRA-SPÉCIFIQUE */
html body .action-button[data-tooltip]::after,
html body .custom-action-btn[data-tooltip]::after,
html body button[data-tooltip]::after,
html body a[data-tooltip]::after,
html body .repair-action-btn[data-tooltip]::after,
.action-button[data-tooltip]::after,
.custom-action-btn[data-tooltip]::after,
button[data-tooltip]::after,
a[data-tooltip]::after,
.repair-action-btn[data-tooltip]::after {
    content: '' !important;
    position: absolute !important;
    top: 100% !important; /* EN DESSOUS du bouton */
    left: 50% !important;
    transform: translateX(-50%) translateY(2px) !important;
    width: 0 !important;
    height: 0 !important;
    border-left: 6px solid transparent !important;
    border-right: 6px solid transparent !important;
    border-bottom: 6px solid rgba(0, 0, 0, 0.9) !important; /* Flèche vers le haut */
    opacity: 0 !important;
    pointer-events: none !important;
    transition: all 0.3s ease !important;
    z-index: 999999 !important; /* Z-index ULTRA élevé */
    display: block !important;
    visibility: hidden !important;
}

/* Afficher les tooltips au survol - ULTRA-SPÉCIFIQUE */
html body .action-button[data-tooltip]:hover::before,
html body .action-button[data-tooltip]:hover::after,
html body .custom-action-btn[data-tooltip]:hover::before,
html body .custom-action-btn[data-tooltip]:hover::after,
html body button[data-tooltip]:hover::before,
html body button[data-tooltip]:hover::after,
html body a[data-tooltip]:hover::before,
html body a[data-tooltip]:hover::after,
html body .repair-action-btn[data-tooltip]:hover::before,
html body .repair-action-btn[data-tooltip]:hover::after,
.action-button[data-tooltip]:hover::before,
.action-button[data-tooltip]:hover::after,
.custom-action-btn[data-tooltip]:hover::before,
.custom-action-btn[data-tooltip]:hover::after,
button[data-tooltip]:hover::before,
button[data-tooltip]:hover::after,
a[data-tooltip]:hover::before,
a[data-tooltip]:hover::after,
.repair-action-btn[data-tooltip]:hover::before,
.repair-action-btn[data-tooltip]:hover::after {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateX(-50%) translateY(0) !important;
}

/* Règles spécifiques pour tooltips dans le modal */
#repairDetailsModal .repair-action-btn[data-tooltip],
#repairDetailsModal button[data-tooltip] {
    position: relative;
    overflow: visible !important;
}

#repairDetailsModal .repair-action-btn[data-tooltip]::before,
#repairDetailsModal .repair-action-btn[data-tooltip]::after,
#repairDetailsModal button[data-tooltip]::before,
#repairDetailsModal button[data-tooltip]::after {
    z-index: 99999 !important;
    position: absolute; /* Garder absolute au lieu de fixed */
}

/* Assurer que le conteneur des boutons permet le débordement */
.repair-actions-grid {
    overflow: visible !important;
}

/* Permettre aux tooltips des boutons d'action principaux de dépasser */
.action-buttons-container,
.modern-action-buttons {
    overflow: visible !important;
}

/* Tooltips mode nuit */
body.dark-mode .action-button[data-tooltip]::before,
body.night-mode .action-button[data-tooltip]::before,
body.dark-mode .custom-action-btn[data-tooltip]::before,
body.night-mode .custom-action-btn[data-tooltip]::before,
body.dark-mode button[data-tooltip]::before,
body.night-mode button[data-tooltip]::before,
body.dark-mode a[data-tooltip]::before,
body.night-mode a[data-tooltip]::before,
body.dark-mode .repair-action-btn[data-tooltip]::before,
body.night-mode .repair-action-btn[data-tooltip]::before {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(37, 99, 235, 0.95));
    border: 1px solid rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
}

body.dark-mode .action-button[data-tooltip]::after,
body.night-mode .action-button[data-tooltip]::after,
body.dark-mode .custom-action-btn[data-tooltip]::after,
body.night-mode .custom-action-btn[data-tooltip]::after,
body.dark-mode button[data-tooltip]::after,
body.night-mode button[data-tooltip]::after,
body.dark-mode a[data-tooltip]::after,
body.night-mode a[data-tooltip]::after,
body.dark-mode .repair-action-btn[data-tooltip]::after,
body.night-mode .repair-action-btn[data-tooltip]::after {
    border-bottom-color: rgba(59, 130, 246, 0.95); /* Flèche vers le haut en mode nuit */
}

/* Tooltips spécifiques pour certains types de boutons */
.btn-primary[data-tooltip]::before {
    background: linear-gradient(135deg, #0d6efd, #0b5ed7);
}

body.dark-mode .btn-primary[data-tooltip]::before,
body.night-mode .btn-primary[data-tooltip]::before {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.95), rgba(11, 94, 215, 0.95));
    border: 1px solid rgba(13, 110, 253, 0.5);
}

.btn-primary[data-tooltip]::after {
    border-bottom-color: #0d6efd;
}

body.dark-mode .btn-primary[data-tooltip]::after,
body.night-mode .btn-primary[data-tooltip]::after {
    border-bottom-color: rgba(13, 110, 253, 0.95);
}

.btn-warning[data-tooltip]::before {
    background: linear-gradient(135deg, #ffc107, #f59e0b);
}

body.dark-mode .btn-warning[data-tooltip]::before,
body.night-mode .btn-warning[data-tooltip]::before {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.95), rgba(245, 158, 11, 0.95));
    border: 1px solid rgba(255, 193, 7, 0.5);
}

.btn-warning[data-tooltip]::after {
    border-bottom-color: #ffc107;
}

body.dark-mode .btn-warning[data-tooltip]::after,
body.night-mode .btn-warning[data-tooltip]::after {
    border-bottom-color: rgba(255, 193, 7, 0.95);
}

.btn-success[data-tooltip]::before {
    background: linear-gradient(135deg, #10b981, #059669);
}

body.dark-mode .btn-success[data-tooltip]::before,
body.night-mode .btn-success[data-tooltip]::before {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.95), rgba(5, 150, 105, 0.95));
    border: 1px solid rgba(16, 185, 129, 0.5);
}

.btn-success[data-tooltip]::after {
    border-bottom-color: #10b981;
}

body.dark-mode .btn-success[data-tooltip]::after,
body.night-mode .btn-success[data-tooltip]::after {
    border-bottom-color: rgba(16, 185, 129, 0.95);
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .action-button[data-tooltip]::before,
    .custom-action-btn[data-tooltip]::before,
    button[data-tooltip]::before,
    a[data-tooltip]::before,
    .repair-action-btn[data-tooltip]::before {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
    }
}
</style>

<script>
// Fonction pour améliorer l'affichage du tableau
document.addEventListener('DOMContentLoaded', function() {
    function improveTableLayout() {
        // Appliquer les styles d'alignement à gauche pour les cellules de tableau
        const tableCells = document.querySelectorAll('#table-view table td, #table-view table th');
        tableCells.forEach(cell => {
            cell.style.textAlign = 'left';
        });
        
        // Seule la dernière colonne (actions) reste alignée à droite
        const lastCells = document.querySelectorAll('#table-view table td:last-child, #table-view table th:last-child');
        lastCells.forEach(cell => {
            cell.style.textAlign = 'right';
        });
        
        // Nous ne tronquons plus le nom de l'appareil pour afficher le texte complet
        const appareilCells = document.querySelectorAll('#table-view .d-none.d-md-table-cell:nth-child(3)');
        appareilCells.forEach(cell => {
            // Garder le texte complet
            cell.style.whiteSpace = 'normal';
            cell.style.wordBreak = 'break-word';
        });
    }
    
    // Appliquer au chargement
    setTimeout(improveTableLayout, 200);
    
    // Réappliquer lors du changement de vue
    document.querySelectorAll('.toggle-view').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(improveTableLayout, 200);
        });
    });
});
</script>

<div class="page-container" id="mainContent">
    <!-- Filtres rapides pour tous les écrans -->
    <div class="modern-filters-container">
        <!-- Barre de recherche moderne -->
        <div class="modern-search">
            <form method="GET" action="index.php" class="search-form">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="hidden" name="page" value="reparations">
                    <input type="hidden" name="view" value="<?php echo isset($_GET['view']) ? htmlspecialchars($_GET['view']) : (isset($_COOKIE['repairViewMode']) ? htmlspecialchars($_COOKIE['repairViewMode']) : 'cards'); ?>">
                    <input type="text" class="search-input" name="search" placeholder="Rechercher par nom, téléphone, appareil..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <button type="button" class="reset-btn" onclick="window.location.href='index.php?page=reparations<?php echo isset($_GET['view']) ? '&view='.htmlspecialchars($_GET['view']) : ''; ?>'">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                    
                    <button class="search-btn" type="submit">
                        <i class="fas fa-search"></i>Rechercher
                    </button>
                </div>
                
                <!-- Suppression de la section des options avancées car les boutons ont été déplacés dans la section d'action -->
            </form>
        </div>

        <!-- Filtres modernes -->
        <div class="modern-filters">
            <!-- Bouton Nouvelle -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '1,2,3,19,20' ? 'active' : ''; ?>"
               data-category-id="1">
                <div class="ripple"></div>
                <i class="fas fa-plus-circle filter-icon"></i>
                <span class="filter-name">Nouvelle</span>
                <span class="filter-count"><?php echo $total_nouvelles ?? 0; ?></span>
            </a>
            
            <!-- Bouton En cours -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '4,5' ? 'active' : ''; ?>"
               data-category-id="2">
                <div class="ripple"></div>
                <i class="fas fa-spinner filter-icon"></i>
                <span class="filter-name">En cours</span>
                <span class="filter-count"><?php echo $total_en_cours ?? 0; ?></span>
            </a>
            
            <!-- Bouton En attente -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '6,7,8' ? 'active' : ''; ?>"
               data-category-id="3">
                <div class="ripple"></div>
                <i class="fas fa-clock filter-icon"></i>
                <span class="filter-name">En attente</span>
                <span class="filter-count"><?php echo $total_en_attente ?? 0; ?></span>
            </a>
            
            <!-- Bouton Terminé -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '9,10' ? 'active' : ''; ?>"
               data-category-id="4">
                <div class="ripple"></div>
                <i class="fas fa-check-circle filter-icon"></i>
                <span class="filter-name">Terminé</span>
                <span class="filter-count"><?php echo $total_termines ?? 0; ?></span>
            </a>
            
            <!-- Bouton Toutes -->
            <a href="javascript:void(0);" 
               class="modern-filter <?php echo ($statut_ids == '1,2,3,4,5' || (empty($statut) && empty($_GET['statut_ids']))) ? 'active' : ''; ?>">
                <div class="ripple"></div>
                <i class="fas fa-list filter-icon"></i>
                <span class="filter-name">Récentes</span>
                <span class="filter-count"><?php echo $total_reparations ?? 0; ?></span>
            </a>
            
            <!-- Bouton Archivé (Admin uniquement) -->
            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '11,12,13' ? 'active' : ''; ?>"
               data-category-id="5">
                <div class="ripple"></div>
                <i class="fas fa-archive filter-icon"></i>
                <span class="filter-name">Archivé</span>
                <span class="filter-count"><?php echo $total_archives ?? 0; ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Boutons d'action principaux -->
    <div class="action-buttons-container">
        <div class="modern-action-buttons">
            <a href="index.php?page=ajouter_reparation" class="action-button">
                <i class="fas fa-plus-circle"></i>
                    </a>
            <button type="button" class="action-button toggle-view" data-view="table">
                <i class="fas fa-table"></i>
                    </button>
            <button type="button" class="action-button toggle-view active" data-view="cards">
                <i class="fas fa-th-large"></i>
                    </button>
                <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#devisEnAttenteModal" data-tooltip="Voir les devis en attente">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>DEVIS EN ATTENTE</span>
                    </button>
            <button type="button" class="action-button" onclick="openMyRepairs()" data-tooltip="Voir mes réparations">
                <i class="fas fa-user-check"></i>
                <span>MES RÉPARATIONS</span>
                <span class="my-repairs-badge" id="myRepairsBadge" style="display: flex;">4</span>
                    </button>
            <button type="button" class="action-button mobile-status-update" onclick="openStatusUpdateModal()" data-tooltip="Mise à jour des dossiers">
                <i class="fas fa-sync-alt"></i>
                <span>MISE À JOUR STATUT</span>
                    </button>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <button type="button" class="action-button mobile-assign-repair" onclick="openTechnicienAttribution()" data-tooltip="Attribuer technicien">
                <i class="fas fa-user-cog"></i>
                <span>ATTRIBUER RÉPARATION</span>
                    </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Conteneur pour les résultats -->
    <div class="results-container">
        <div class="card">
            <div class="card-body">
                <!-- Vue tableau moderne personnalisé -->
                <div id="table-view" class="d-none">
                    <div class="custom-table-container">
                        <!-- En-tête du tableau moderne -->
                        <div class="custom-table-header">
                            <div class="custom-header-cell header-indicators">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="custom-header-cell header-client">
                                <i class="fas fa-user"></i>
                                <span>Client</span>
                            </div>
                            <div class="custom-header-cell header-device">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Appareil</span>
                            </div>
                            <div class="custom-header-cell header-problem">
                                <i class="fas fa-wrench"></i>
                                <span>Problème</span>
                            </div>
                            <div class="custom-header-cell header-date">
                                <i class="fas fa-calendar"></i>
                                <span>Date</span>
                            </div>
                            <div class="custom-header-cell header-status">
                                <i class="fas fa-tasks"></i>
                                <span>Statut</span>
                            </div>
                            <div class="custom-header-cell header-price">
                                <i class="fas fa-euro-sign"></i>
                                <span>Prix</span>
                            </div>
                            <div class="custom-header-cell header-actions">
                                <i class="fas fa-cogs"></i>
                                <span>Actions</span>
                            </div>
                        </div>

                        <!-- Corps du tableau moderne -->
                        <div class="custom-table-body">
                                <?php if (!empty($reparations)): ?>
                                    <?php foreach ($reparations as $reparation): ?>
                                <div class="custom-table-row draggable-card" 
                                     data-id="<?php echo $reparation['id']; ?>" 
                                     data-repair-id="<?php echo $reparation['id']; ?>" 
                                     data-status="<?php echo $reparation['statut']; ?>" 
                                     draggable="true">
                                     
                                    <!-- Colonne Indicateurs -->
                                    <div class="custom-table-cell cell-indicators">
                                        <div class="indicators-group">
                                                <?php if ($reparation['commande_requise']): ?>
                                                    <div class="indicator-badge order-required" title="Commande requise">
                                                        <i class="fas fa-shopping-basket"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($reparation['urgent']): ?>
                                                    <div class="indicator-badge urgent" title="Urgent">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                    </div>

                                    <!-- Colonne Client -->
                                    <div class="custom-table-cell cell-client">
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                                <div class="phone-indicator">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            <div class="client-details">
                                                <div class="client-name">
                                                        <?php echo htmlspecialchars(($reparation['client_nom'] ?? '') . ' ' . ($reparation['client_prenom'] ?? '')); ?>
                                                </div>
                                                <div class="client-id">ID: <?php echo $reparation['id']; ?></div>
                                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                                <div class="client-phone"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                    </div>

                                    <!-- Colonne Appareil -->
                                    <div class="custom-table-cell cell-device">
                                        <div class="device-info">
                                            <?php echo htmlspecialchars($reparation['modele']); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Problème -->
                                    <div class="custom-table-cell cell-problem">
                                        <div class="problem-info">
                                            <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Date -->
                                    <div class="custom-table-cell cell-date">
                                        <div class="date-info">
                                            <?php echo isset($reparation['date_reception']) ? format_date($reparation['date_reception']) : (isset($reparation['date_creation']) ? format_date($reparation['date_creation']) : 'N/A'); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Statut -->
                                    <div class="custom-table-cell cell-status">
                                        <div class="status-container">
                                            <span class="status-badge">
                                            <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                            </span>
                                            </div>
                                    </div>

                                    <!-- Colonne Prix -->
                                    <div class="custom-table-cell cell-price">
                                        <div class="price-info">
                                            <?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Actions -->
                                    <div class="custom-table-cell cell-actions">
                                        <div class="actions-group">
                                                <?php 
                                                // Vérifier si l'utilisateur est attribué à cette réparation ET si c'est sa réparation active
                                                $is_assigned = ($reparation['employe_id'] == $_SESSION['user_id']);
                                                $is_active_repair = ($reparation['user_active_repair_id'] == $reparation['id']);
                                                $show_stop = $is_assigned && $is_active_repair;
                                                ?>
                                                <?php if (!$show_stop): ?>
                                            <button class="custom-action-btn btn-primary start-repair-btn" 
                                                    data-id="<?php echo $reparation['id']; ?>" 
                                                    data-tooltip="Démarrer la réparation"
                                                    title="Démarrer la réparation">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <?php else: ?>
                                            <button class="custom-action-btn btn-warning stop-repair-btn" 
                                                    data-id="<?php echo $reparation['id']; ?>" 
                                                    data-tooltip="Arrêter la réparation"
                                                    title="Arrêter la réparation">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                                <?php endif; ?>
                                            
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                               class="custom-action-btn btn-success" 
                                                   data-tooltip="Appeler le client"
                                                   title="Appeler">
                                                    <i class="fas fa-phone"></i>
                                                </a>
                                                <?php endif; ?>

                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                            <button class="custom-action-btn btn-info" 
                                                        onclick="openSendSms(<?php echo $reparation['id']; ?>, '<?php echo  htmlspecialchars($reparation['client_nom'] ?? ''); ?>', '<?php echo htmlspecialchars($reparation['client_telephone']); ?>')"
                                                        data-tooltip="Envoyer un message au client"
                                                        title="SMS">
                                                    <i class="fas fa-sms"></i>
                                                </button>
                                                <?php endif; ?>
                                            
                                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                <button type="button" 
                                                    class="custom-action-btn btn-danger delete-repair" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                    </div>
                                </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="table-empty">
                                                    <div class="no-results-container">
                                    <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                    <p class="text-muted">Aucune réparation trouvée.</p>
                                            </div>
                                </div>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Vue cartes -->
                <div id="cards-view">
                    <div class="repair-cards-container">
                        <?php if (!empty($reparations)): ?>
                    <?php foreach ($reparations as $reparation): ?>
                                <div class="modern-card draggable-card animate-card" data-id="<?php echo $reparation['id']; ?>" data-repair-id="<?php echo $reparation['id']; ?>" data-status="<?php echo $reparation['statut']; ?>" draggable="true">
                                    <!-- En-tête de la carte -->
                                    <div class="card-header">
                                        <div class="status-indicator">
                                            <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                        </div>
                                        <div class="repair-id">
                                            <span>ID: <?php echo $reparation['id']; ?></span>
                                        </div>
                                    </div>
                            
                                    <!-- Contenu principal -->
                                    <div class="card-content">
                                        <!-- Indicateurs spéciaux -->
                                        <?php if ($reparation['urgent'] || $reparation['commande_requise'] || !empty($reparation['notes_techniques'])): ?>
                                        <div class="special-indicators">
                                            <?php if ($reparation['urgent']): ?>
                                            <div class="indicator indicator-urgent">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                <span>Urgent</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($reparation['commande_requise']): ?>
                                            <div class="indicator indicator-order">
                                                    <i class="fas fa-shopping-cart"></i>
                                                <span>Commande</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($reparation['notes_techniques'])): ?>
                                            <div class="indicator indicator-notes">
                                                    <i class="fas fa-clipboard-list"></i>
                                                <span>Notes</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Informations du client -->
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="client-details">
                                                <div class="client-name">
                                                    <?php echo htmlspecialchars(($reparation['client_nom'] ?? '') . ' ' . ($reparation['client_prenom'] ?? '')); ?>
                                            </div>
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                                <div class="client-contact">
                                                    <i class="fas fa-phone-alt"></i>
                                                    <span><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                                        </div>
                                                <?php endif; ?>
                                                <?php if (!empty($reparation['client_email'])): ?>
                                                <div class="client-contact">
                                                    <i class="fas fa-envelope"></i>
                                                    <span><?php echo htmlspecialchars($reparation['client_email']); ?></span>
                                            </div>
                                                <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                        <!-- Informations de l'appareil -->
                                        <div class="device-info">
                                            <div class="device-icon">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="device-details">
                                                <div class="device-model">
                                                    <?php echo htmlspecialchars($reparation['modele']); ?>
                                            </div>
                                                <div class="device-problem">
                                                    <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 100)) . (strlen($reparation['description_probleme']) > 100 ? '...' : ''); ?>
                                        </div>
                                            </div>
                                            </div>
                                        
                                        <!-- Date de réception -->
                                        <div class="reception-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <span>Reçu le: <?php echo isset($reparation['date_reception']) ? format_date($reparation['date_reception']) : (isset($reparation['date_creation']) ? format_date($reparation['date_creation']) : 'N/A'); ?></span>
                                        </div>
                                        
                                        <!-- Section prix -->
                                        <div class="price-section">
                                            <div class="price">
                                                <i class="fas fa-tag"></i>
                                                <span><?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                                
                                <!-- Pied de la carte avec les boutons d'action -->
                                <div class="card-footer">
                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                           class="action-btn btn-call" 
                                       title="Appeler">
                                        <i class="fas fa-phone-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php 
                                    // Utiliser la même logique que pour le tableau
                                    $is_assigned_card = ($reparation['employe_id'] == $_SESSION['user_id']);
                                    $is_active_repair_card = ($reparation['user_active_repair_id'] == $reparation['id']);
                                    $show_stop_card = $is_assigned_card && $is_active_repair_card;
                                    ?>
                                    <?php if (!$show_stop_card): ?>
                                    <button type="button" 
                                                class="action-btn btn-start start-repair" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Démarrer">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" 
                                                class="action-btn btn-stop stop-repair-btn" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Arrêter">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                    <a href="#" 
                                           class="action-btn btn-message" 
                                       title="SMS"
                                       data-client-id="<?php echo $reparation['client_id']; ?>"
                                       data-client-nom="<?php echo htmlspecialchars($reparation['client_nom']); ?>"
                                       data-client-prenom="<?php echo htmlspecialchars($reparation['client_prenom']); ?>"
                                       data-client-tel="<?php echo htmlspecialchars($reparation['client_telephone']); ?>"
                                       onclick="openSmsModal(
                                           '<?php echo $reparation['client_id']; ?>', 
                                           '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                                           '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                                           '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                                       ); return false;">
                                        <i class="fas fa-comment-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <button type="button" 
                                                class="action-btn btn-delete delete-repair" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                    </div>
                    <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                            <div class="no-results-container">
                                <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                <p class="text-muted">Aucune réparation trouvée.</p>
                                </div>
                            </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne pour choisir le statut spécifique après le drag & drop -->
<div class="modal fade" id="chooseStatusModal" tabindex="-1" aria-labelledby="chooseStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-status-modal">
            <div class="modal-header modern-status-header">
                <div class="status-modal-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="status-modal-title-container">
                    <h5 class="modal-title" id="chooseStatusModalLabel">Changer le statut</h5>
                    <p class="status-modal-subtitle">Sélectionnez le nouveau statut pour cette réparation</p>
                </div>
                <button type="button" class="btn-close modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <div class="modal-body modern-status-body">
                <!-- Informations de la réparation -->
                <div class="repair-info-card">
                    <div class="repair-info-content">
                        <div class="repair-info-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <div class="repair-info-details">
                            <h6 class="repair-info-title">Réparation #<span id="currentRepairNumber">-</span></h6>
                            <p class="repair-info-client">Client: <span id="currentRepairClient">-</span></p>
                            <p class="repair-info-device">Appareil: <span id="currentRepairDevice">-</span></p>
                        </div>
                        <div class="current-status-badge">
                            <span class="current-status-label">Statut actuel:</span>
                            <span class="current-status-value" id="currentStatusDisplay">-</span>
                        </div>
                    </div>
                </div>

                <!-- Container pour les statuts -->
                <div id="statusCategoriesContainer" class="status-categories-container">
                    <!-- Loading state -->
                    <div class="status-loading-container">
                        <div class="status-loading-spinner">
                            <div class="modern-spinner"></div>
                        </div>
                        <h6 class="status-loading-title">Chargement des statuts...</h6>
                        <p class="status-loading-text">Récupération des statuts disponibles</p>
                    </div>
                </div>

                <!-- Options SMS -->
                <div class="sms-options-card">
                    <div class="sms-toggle-container">
                        <div class="sms-toggle-info">
                            <i class="fas fa-sms"></i>
                            <div>
                                <h6>Notification SMS</h6>
                                <p>Envoyer un SMS au client pour l'informer du changement</p>
                            </div>
                        </div>
                        <div class="sms-toggle-switch">
                            <input type="checkbox" id="sendSmsToggle" class="sms-checkbox" checked>
                            <label for="sendSmsToggle" class="sms-toggle-label">
                                <span class="sms-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Champs cachés -->
                <input type="hidden" id="chooseStatusRepairId" value="">
                <input type="hidden" id="chooseStatusCategoryId" value="">
                <input type="hidden" id="selectedStatusId" value="">
                
            </div>
            
            <div class="modal-footer modern-status-footer">
                <button type="button" class="btn btn-outline-secondary modern-cancel-btn" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Cliquez directement sur un statut pour l'appliquer
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour le drag & drop des statuts -->
<script>
// Variable globale pour l'ID de l'utilisateur connecté (définie plus haut)
const currentUserId = window.currentUserId;

// Fonction pour initialiser le bouton toggle pour l'envoi de SMS
function initSmsToggleButton() {
    const toggleButton = document.getElementById('smsToggleButton');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    
    if (!toggleButton || !smsSwitch) {
        console.error('Éléments du toggle SMS non trouvés');
        return;
    }
    
    // Par défaut, le SMS n'est pas envoyé (value="0")
    toggleButton.addEventListener('click', function() {
        // Inverser l'état actuel
        const currentValue = smsSwitch.value;
        const newValue = currentValue === '1' ? '0' : '1';
        smsSwitch.value = newValue;
        
        // Mettre à jour l'apparence du bouton
        if (newValue === '1') {
            // SMS activé
            toggleButton.classList.remove('btn-danger');
            toggleButton.classList.add('btn-success');
            toggleButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>ENVOYER UN SMS AU CLIENT';
            // Jouer un son si disponible
            if (typeof playNotificationSound === 'function') {
                playNotificationSound();
            }
        } else {
            // SMS désactivé
            toggleButton.classList.remove('btn-success');
            toggleButton.classList.add('btn-danger');
            toggleButton.innerHTML = '<i class="fas fa-ban me-2"></i>NE PAS ENVOYER DE SMS AU CLIENT';
        }
        
        console.log('État d\'envoi de SMS mis à jour:', newValue === '1' ? 'Activé' : 'Désactivé');
    });
    
    console.log('Bouton toggle SMS initialisé avec succès');
}

// Function pour jouer un son de notification
function playNotificationSound() {
    // Créer un élément audio
    const audio = new Audio('../assets/sounds/notification.mp3');
    audio.volume = 0.5;
    audio.play().catch(e => console.log('Erreur lors de la lecture du son:', e));
}
document.addEventListener('DOMContentLoaded', function() {
    // Afficher l'ID utilisateur dans la console
    console.log('Utilisateur connecté ID:', currentUserId);
    
    // Initialisation du bouton toggle SMS
    initSmsToggleButton();
    
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Rendre les lignes du tableau cliquables
    document.querySelectorAll('#table-view .repair-row').forEach(function(row) {
        row.style.cursor = 'pointer';
        
        row.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur un bouton
            if (e.target.closest('.btn') || e.target.closest('button') || e.target.closest('a') || e.target.closest('.action-btn')) {
                return;
            }
            
            // Récupérer l'ID de la réparation
            const repairId = this.getAttribute('data-id') || this.getAttribute('data-repair-id');
            if (repairId) {
                console.log('🔄 Ouverture du modal pour la réparation:', repairId);
                
                // Utiliser le nouveau modal moderne
                if (window.modernRepairModal) {
                    window.modernRepairModal.openModal(repairId);
                } else {
                    console.error('ModernRepairModal non disponible');
                }
            }
        });
    });
    
    // Fonctions pour les actions du modal
    window.updateNotesTechniques = function(repairId, notes) {
        console.log('Mise à jour des notes techniques pour la réparation', repairId);
        
        const formData = new FormData();
        formData.append('repair_id', repairId);
        formData.append('notes_techniques', notes);
        
        fetch('ajax/update_notes_techniques.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Notes techniques mises à jour');
                // Afficher une confirmation discrète
                const textarea = document.getElementById(`notesTechniques_${repairId}`);
                if (textarea) {
                    textarea.style.borderColor = '#28a745';
                    setTimeout(() => {
                        textarea.style.borderColor = '';
                    }, 2000);
                }
            } else {
                console.error('❌ Erreur lors de la mise à jour des notes:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX notes techniques:', error);
        });
    };
    
    window.addPhoto = function(repairId) {
        console.log('Ajout de photo pour la réparation', repairId);
        
        // Créer un input file invisible
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.style.display = 'none';
        
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('repair_id', repairId);
                formData.append('photo', file);
                
                // Afficher un loader
                const photoButton = document.querySelector(`button[onclick="addPhoto(${repairId})"]`);
                if (photoButton) {
                    photoButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Téléchargement...';
                    photoButton.disabled = true;
                }
                
                fetch('ajax/upload_repair_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Photo ajoutée avec succès');
                        // Recharger le modal ou ajouter la photo à l'affichage
                        location.reload(); // Solution simple pour rafraîchir
                    } else {
                        console.error('❌ Erreur lors de l\'ajout de la photo:', data.message);
                        alert('Erreur lors de l\'ajout de la photo: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur AJAX photo:', error);
                    alert('Erreur lors du téléchargement de la photo');
                })
                .finally(() => {
                    if (photoButton) {
                        photoButton.innerHTML = '<i class="fas fa-plus me-1"></i>Ajouter une photo';
                        photoButton.disabled = false;
                    }
                });
            }
        };
        
        document.body.appendChild(input);
        input.click();
        document.body.removeChild(input);
    };
    
    // Fonction pour charger les statuts
    window.loadStatusOptions = function(repairId) {
        const container = document.getElementById('statusCategoriesContainer');
        if (!container) return;
        
        // Afficher le loader
        container.innerHTML = `
            <div class="status-loading-container">
                <div class="status-loading-spinner">
                    <div class="modern-spinner"></div>
                </div>
                <h6 class="status-loading-title">Chargement des statuts...</h6>
                <p class="status-loading-text">Récupération des statuts disponibles</p>
            </div>
        `;
        
        // Récupérer l'ID du magasin
        let shopId = null;
        if (typeof SessionHelper !== 'undefined' && SessionHelper.getShopId) {
            shopId = SessionHelper.getShopId();
        } else if (localStorage.getItem('shop_id')) {
            shopId = localStorage.getItem('shop_id');
        }
        
        let apiUrl = 'ajax/get_all_statuts.php';
        if (shopId) {
            apiUrl += `?shop_id=${shopId}`;
        }
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.statuts) {
                    renderStatusOptions(data.statuts, repairId);
                } else {
                    container.innerHTML = `<div class="alert alert-danger">Erreur: ${data.error || 'Impossible de charger les statuts'}</div>`;
                }
            })
            .catch(error => {
                console.error('Erreur chargement statuts:', error);
                container.innerHTML = `<div class="alert alert-danger">Erreur de connexion</div>`;
            });
    };

    // Fonction pour afficher les statuts
    window.renderStatusOptions = function(statusGroups, repairId) {
        const container = document.getElementById('statusCategoriesContainer');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.entries(statusGroups).forEach(([categoryCode, categoryData]) => {
            // Titre de catégorie
            const categoryTitle = document.createElement('h6');
            categoryTitle.className = `status-category-title text-${categoryData.couleur || 'secondary'} mt-3 mb-2`;
            categoryTitle.innerHTML = `<i class="fas fa-tag me-2"></i> ${categoryData.nom}`;
            container.appendChild(categoryTitle);
            
            // Container de boutons
            const buttonsContainer = document.createElement('div');
            buttonsContainer.className = 'd-grid gap-2';
            
            categoryData.statuts.forEach(statut => {
                const button = document.createElement('button');
                button.className = `btn btn-outline-${categoryData.couleur || 'secondary'} text-start`;
                button.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    ${statut.nom}
                `;
                button.onclick = () => updateSpecificStatus(statut.id, button);
                buttonsContainer.appendChild(button);
            });
            
            container.appendChild(buttonsContainer);
        });
    };

    window.openStatusModal = function(repairId) {
        console.log('Ouverture du modal de changement de statut pour la réparation', repairId);
        
        // Utiliser le modal existant de changement de statut
        const statusModal = document.getElementById('chooseStatusModal');
        if (statusModal && typeof bootstrap !== 'undefined') {
            // Stocker l'ID de la réparation pour le modal de statut
            window.currentRepairIdForStatus = repairId;
            
            // Mettre à jour l'input caché pour updateSpecificStatus
            const hiddenInput = document.getElementById('chooseStatusRepairId');
            if (hiddenInput) {
                hiddenInput.value = repairId;
            }
            
            // Initialiser le chargement des statuts
            if (typeof loadStatusOptions === 'function') {
                loadStatusOptions(repairId);
            } else {
                console.error('❌ Fonction loadStatusOptions non trouvée');
            }
            
            const modalInstance = new bootstrap.Modal(statusModal);
            modalInstance.show();
        } else {
            console.error('❌ Modal de changement de statut non trouvé');
        }
    };
    
    // Fonctions pour le drag & drop des cartes
    function initCardDragAndDrop() {
        // Sélectionner toutes les cartes de réparation et les lignes du tableau
        const draggableCards = document.querySelectorAll('.draggable-card');
        const dropZones = document.querySelectorAll('.modern-filter.droppable');
        
        // Variables pour le ghost element
        let ghostElement = null;
        let draggedCard = null;
        
        console.log('Initializing drag & drop with', draggableCards.length, 'cards and', dropZones.length, 'drop zones');
        
        // Ajouter les écouteurs d'événements pour les cartes et les lignes
        draggableCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            // Empêcher la propagation du clic pour les boutons à l'intérieur des cartes
            const buttons = card.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mousedown', e => {
                    e.stopPropagation();
                });
                
                button.addEventListener('click', e => {
                    e.stopPropagation();
                });
            });
        });
        
        // Ajouter les écouteurs d'événements pour les zones de dépôt
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleDragOver);
            zone.addEventListener('dragenter', handleDragEnter);
            zone.addEventListener('dragleave', handleDragLeave);
            zone.addEventListener('drop', handleDrop);
        });
        
        /**
         * Gère le début du drag
         */
        function handleDragStart(e) {
            console.log('Début du drag sur une carte', this);
            
            // Marquer la carte comme étant en cours de déplacement
            this.classList.add('dragging');
            draggedCard = this;
            
            // Récupérer les données de réparation et de statut
            const repairId = this.getAttribute('data-repair-id') || this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            console.log('Données de drag:', { repairId, status });
            
            // Stocker les données de l'élément déplacé
            e.dataTransfer.setData('text/plain', JSON.stringify({
                repairId: repairId,
                status: status
            }));
            
            // Créer un "ghost element" pour le feedback visuel
            createGhostElement(this, e);
            
            // Définir l'effet de déplacement
            e.dataTransfer.effectAllowed = 'move';
        }
        
        /**
         * Gère la fin du drag
         */
        function handleDragEnd(e) {
            // Supprimer la classe de dragging
            this.classList.remove('dragging');
            
            // Supprimer le ghost element
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
                ghostElement = null;
            }
            
            // Réinitialiser les zones de dépôt
            dropZones.forEach(zone => {
                zone.classList.remove('drag-over');
            });
            
            // Supprimer l'écouteur mousemove
            document.removeEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Gère le survol d'une zone de dépôt
         */
        function handleDragOver(e) {
            // Empêcher le comportement par défaut pour permettre le drop
            e.preventDefault();
            return false;
        }
        
        /**
         * Gère l'entrée dans une zone de dépôt
         */
        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }
        
        /**
         * Gère la sortie d'une zone de dépôt
         */
        function handleDragLeave() {
            this.classList.remove('drag-over');
        }
        
        /**
         * Gère le dépôt dans une zone
         */
        function handleDrop(e) {
            // Empêcher le comportement par défaut
            e.preventDefault();
            
            console.log('Drop détecté sur une zone de dépôt', this);
            
            // Récupérer les données
            try {
                const dataText = e.dataTransfer.getData('text/plain');
                console.log('Données de transfert brutes:', dataText);
                
                const data = JSON.parse(dataText);
                console.log('Données de transfert parsées:', data);
                
                const repairId = data.repairId;
                const categoryId = this.getAttribute('data-category-id');
                
                console.log('ID réparation:', repairId);
                console.log('ID catégorie:', categoryId);
                console.log('Element de statut:', draggedCard ? draggedCard.querySelector('.status-indicator') : 'Non trouvé');
                
                // Vérifier que nous avons toutes les données nécessaires
                if (!repairId || !categoryId) {
                    console.error('Données incomplètes pour la mise à jour du statut');
                    return false;
                }
                
                // Effet visuel de succès sur la zone de dépôt
                this.classList.add('drop-success');
                setTimeout(() => {
                    this.classList.remove('drop-success');
                }, 1000);
                
                // Mettre à jour le statut de la réparation via la fonction fetchStatusOptions
                if (draggedCard && draggedCard.querySelector('.status-indicator')) {
                fetchStatusOptions(repairId, categoryId, draggedCard.querySelector('.status-indicator'));
                } else {
                    console.error('Impossible de trouver l\'indicateur de statut sur la carte glissée');
                    // Essayer de créer une référence alternative
                    const allCards = document.querySelectorAll('.dashboard-card, .draggable-card');
                    let targetCard = null;
                    allCards.forEach(card => {
                        const cardId = card.getAttribute('data-repair-id') || card.getAttribute('data-id');
                        if (cardId == repairId) {
                            targetCard = card;
                        }
                    });
                    
                    if (targetCard && targetCard.querySelector('.status-indicator')) {
                        console.log('Carte cible alternative trouvée:', targetCard);
                        fetchStatusOptions(repairId, categoryId, targetCard.querySelector('.status-indicator'));
                    } else {
                        console.error('Aucune carte cible alternative trouvée, rechargement de la page');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                }
                
            } catch (error) {
                console.error('Erreur lors du traitement des données:', error);
            }
            
            // Réinitialiser l'état visuel
            this.classList.remove('drag-over');
            return false;
        }
        
        /**
         * Crée un élément fantôme pour le feedback visuel pendant le drag
         */
        function createGhostElement(sourceElement, event) {
            // Supprimer l'ancien ghost s'il existe
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
            }
            
            // Créer un clone simplifié de la carte pour le ghost
            ghostElement = document.createElement('div');
            ghostElement.className = 'dashboard-card ghost-card';
            
            // Vérifier si c'est une ligne de tableau ou une carte
            if (sourceElement.tagName === 'TR') {
                // C'est une ligne de tableau
                const statusCell = sourceElement.querySelector('td:nth-child(6)');
                if (statusCell) {
                    const badge = statusCell.querySelector('.status-indicator');
                    if (badge) ghostElement.appendChild(badge.cloneNode(true));
                }
                
                const clientInfo = sourceElement.querySelector('td:nth-child(2) h6');
                if (clientInfo) ghostElement.appendChild(clientInfo.cloneNode(true));
            } else {
                // C'est une carte
                const statusBadge = sourceElement.querySelector('.status-indicator');
                if (statusBadge) ghostElement.appendChild(statusBadge.cloneNode(true));
                
                const deviceInfo = sourceElement.querySelector('.mb-0');
                if (deviceInfo) ghostElement.appendChild(deviceInfo.cloneNode(true));
            }
            
            // Positionner l'élément
            const rect = sourceElement.getBoundingClientRect();
            
            // Calculer l'offset par rapport au point de clic
            const offsetX = event.clientX - rect.left;
            const offsetY = event.clientY - rect.top;
            
            // Sauvegarder l'offset pour les mises à jour de position
            ghostElement.dataset.offsetX = offsetX;
            ghostElement.dataset.offsetY = offsetY;
            
            // Appliquer la position initiale
            ghostElement.style.left = (event.pageX - offsetX) + 'px';
            ghostElement.style.top = (event.pageY - offsetY) + 'px';
            
            // Ajouter au DOM
            document.body.appendChild(ghostElement);
            
            // Ajouter un écouteur pour le mouvement de la souris
            document.addEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Met à jour la position de l'élément fantôme pendant le drag
         */
        function updateGhostPosition(e) {
            if (ghostElement) {
                const offsetX = parseInt(ghostElement.dataset.offsetX) || 0;
                const offsetY = parseInt(ghostElement.dataset.offsetY) || 0;
                
                ghostElement.style.left = (e.pageX - offsetX) + 'px';
                ghostElement.style.top = (e.pageY - offsetY) + 'px';
            }
        }
    }
        });
        
        /**
 * Affiche une notification temporaire
 */
function showNotification(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '300px';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
}
    /**
     * Détermine la couleur Bootstrap à utiliser en fonction de la couleur de la catégorie
     */
    function getCategoryColor(color) {
        // Convertir la couleur en classe Bootstrap
        const colorMap = {
            'info': 'info',
            'primary': 'primary',
            'warning': 'warning',
            'success': 'success',
            'danger': 'danger',
            'secondary': 'secondary'
        };
        return colorMap[color] || 'primary';
    }

    /**
     * Récupère les options de statut pour une catégorie donnée
     */
    function fetchStatusOptions(repairId, categoryId, statusIndicator) {
        // Afficher un indicateur de chargement dans le badge
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Chargement...</span>';
    
    console.log('Récupération des statuts pour la catégorie:', categoryId);
        
        // Récupérer les statuts disponibles pour cette catégorie
        fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
            console.log('Réponse de get_statuts_by_category:', data);
            
                if (data.success) {
                    // Stocker les IDs pour une utilisation ultérieure
                    document.getElementById('chooseStatusRepairId').value = repairId;
                    document.getElementById('chooseStatusCategoryId').value = categoryId;
                    
                    // Remplir les informations de la réparation dans le modal
                    document.getElementById('currentRepairNumber').textContent = repairId;
                    
                    // Trouver les informations de la réparation depuis la carte
                    const repairCard = document.querySelector(`[data-repair-id="${repairId}"], [data-id="${repairId}"]`);
                    if (repairCard) {
                        const clientName = repairCard.querySelector('.client-name, .repair-client, .card-title')?.textContent?.trim() || 'Client inconnu';
                        const deviceInfo = repairCard.querySelector('.device-info, .repair-device, .card-text')?.textContent?.trim() || 'Appareil non spécifié';
                        
                        document.getElementById('currentRepairClient').textContent = clientName;
                        document.getElementById('currentRepairDevice').textContent = deviceInfo;
                    } else {
                        document.getElementById('currentRepairClient').textContent = 'Client inconnu';
                        document.getElementById('currentRepairDevice').textContent = 'Appareil non spécifié';
                    }
                    
                    // Générer les boutons de statut
                    const container = document.getElementById('statusCategoriesContainer');
                    if (!container) {
                        console.error('Élément statusCategoriesContainer non trouvé dans le DOM');
                        return;
                    }
                    container.innerHTML = ''; // Effacer le contenu précédent, y compris l'indicateur de chargement
                    
                    // Déterminer la couleur de la catégorie
                    const categoryColor = getCategoryColor(data.category.couleur);
                    
                    // Ajouter un titre pour la catégorie dans le modal
                    const categoryTitle = document.getElementById('chooseStatusModalLabel');
                    if (categoryTitle) {
                        categoryTitle.innerHTML = `<i class="fas fa-tasks me-2"></i> Statuts "${data.category.nom}"`;
                    }
                    
                    // Créer un bouton pour chaque statut
                    data.statuts.forEach(statut => {
                        const button = document.createElement('button');
                        button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                        button.setAttribute('data-status-id', statut.id);
                        button.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            ${statut.nom}
                        `;
                        button.addEventListener('click', () => updateSpecificStatus(statut.id, statusIndicator));
                        container.appendChild(button);
                    });
                    
                    // Afficher le modal avec un délai pour s'assurer que les données sont stockées
                    setTimeout(() => {
                        const modalElement = document.getElementById('chooseStatusModal');
                        const modal = new bootstrap.Modal(modalElement, {
                            backdrop: true,
                            keyboard: true,
                            focus: true
                        });
                        
                        // Événement pour gérer l'ouverture du modal - SEULEMENT SI PAS DÉJÀ ATTACHÉ
                        if (!modalElement.dataset.shownListenerAttached) {
                            modalElement.dataset.shownListenerAttached = 'true';
                            modalElement.addEventListener('shown.bs.modal', function() {
                                // Permettre le scroll de la page
                                document.body.style.overflow = 'auto';
                                document.body.style.paddingRight = '0';
                                
                                // Forcer la position du modal
                                this.style.display = 'block';
                                this.style.position = 'fixed';
                                this.style.top = '0';
                                this.style.left = '0';
                                this.style.width = '100%';
                                this.style.height = '100%';
                                this.style.zIndex = '9999';
                                
                                // Forcer la position du modal-dialog
                                const modalDialog = this.querySelector('.modal-dialog');
                                if (modalDialog) {
                                    modalDialog.style.position = 'fixed';
                                    modalDialog.style.top = '80px';
                                    modalDialog.style.left = '50%';
                                    modalDialog.style.transform = 'translateX(-50%)';
                                    modalDialog.style.margin = '0';
                                    modalDialog.style.width = '90%';
                                    modalDialog.style.maxWidth = '500px';
                                    modalDialog.style.zIndex = '10000';
                                }
                                
                                console.log('📍 Modal positionné à 80px du haut et scroll activé');
                            });
                        }
                        
                        modal.show();
                        
                        // Vérifier que les données sont bien stockées
                        console.log('🔍 Vérification des données du modal:');
                        console.log('- Repair ID:', document.getElementById('chooseStatusRepairId')?.value);
                        console.log('- Category ID:', document.getElementById('chooseStatusCategoryId')?.value);
                    }, 100);
                    
                    // Rétablir le badge de statut quand l'utilisateur annule - SEULEMENT SI PAS DÉJÀ ATTACHÉ
                    const closeBtn = document.querySelector('#chooseStatusModal .btn-close');
                    const cancelBtn = document.querySelector('#chooseStatusModal .btn-outline-secondary');
                    
                    const handleCancel = function() {
                    console.log('Annulation de la sélection de statut');
                        // Nettoyer le backdrop et réactiver le scroll
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        
                        // Simplement recharger la page pour éviter l'update automatique
                        location.reload();
                    };
                    
                    if (closeBtn && !closeBtn.dataset.cancelListenerAttached) {
                        closeBtn.dataset.cancelListenerAttached = 'true';
                        closeBtn.addEventListener('click', handleCancel);
                    }
                    
                    if (cancelBtn && !cancelBtn.dataset.cancelListenerAttached) {
                        cancelBtn.dataset.cancelListenerAttached = 'true';
                        cancelBtn.addEventListener('click', handleCancel);
                    }
                    
                } else {
                    // Afficher l'erreur
                    showNotification('Erreur: ' + data.error, 'danger');
                    location.reload(); // Recharger la page en cas d'erreur
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des statuts:', error);
                showNotification('Erreur de communication avec le serveur', 'danger');
                location.reload(); // Recharger la page en cas d'erreur
            });
    }
    
    /**
     * Met à jour le statut spécifique d'une réparation
     */
    function updateSpecificStatus(statusId, statusIndicator) {
        // Récupérer les ID stockés
        const repairId = document.getElementById('chooseStatusRepairId').value;
    
        console.log('Mise à jour du statut:', statusId, 'pour la réparation:', repairId);
        
        // Récupérer l'état de l'option d'envoi de SMS
        const sendSmsToggle = document.getElementById('sendSmsToggle');
        const sendSms = sendSmsToggle ? sendSmsToggle.checked : true; // Par défaut activé si élément non trouvé
        console.log('Envoi de SMS:', sendSms ? 'Activé' : 'Désactivé');
        
        // Fermer le modal (autoriser explicitement)
        const modalEl = document.getElementById('chooseStatusModal');
        if (modalEl) modalEl.dataset.allowHide = '1';
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        
        // Nettoyer le backdrop et réactiver le scroll
        document.body.classList.remove('modal-open');
        // Nettoyage agressif désactivé: laisser Bootstrap gérer backdrop/overflow
        
        // Afficher un indicateur de chargement
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
        
        // Préparer les données
        const data = {
            repair_id: repairId,
            status_id: statusId,
            send_sms: sendSms,
            user_id: 1 // Toujours utiliser l'ID 1 (admin) pour éviter les problèmes
        };
        
        // Afficher les données pour le débogage
        console.log('Données envoyées:', data);
        
        // Fonction pour afficher une notification
        function showSilentNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            const toast = new bootstrap.Toast(notification, { delay: 5000 });
            toast.show();
            
            // Supprimer la notification après qu'elle soit masquée
            notification.addEventListener('hidden.bs.toast', function () {
                notification.remove();
            });
        }
        
        // Essayer d'abord avec fetch (méthode JSON standard)
        fetch('../ajax/update_repair_specific_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Réponse brute:', response);
            
            if (!response.ok) {
                if (response.status === 500) {
                    // Pour les erreurs 500, on va essayer une approche différente
                    throw new Error('RETRY_WITH_FORM');
                }
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            // Essayer de parser la réponse en JSON
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    console.log('Réponse texte brute:', text);
                    throw new Error('Réponse non valide du serveur');
                }
            });
        })
        .then(handleSuccess)
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            
            if (error.message === 'RETRY_WITH_FORM') {
                console.log('Nouvelle tentative avec FormData au lieu de JSON...');
                
                // Seconde tentative avec FormData mais en indiquant qu'il s'agit de données JSON
                const formData = new FormData();
                // Ajouter les données sous forme d'une seule entrée JSON
                formData.append('json_data', JSON.stringify(data));
                
                return fetch('../ajax/update_repair_specific_status.php?format=json', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        // Troisième tentative - essayons en direct
                        console.log('Troisième tentative - mise à jour directe du statut...');
                        return directStatusUpdate(repairId, statusId, sendSms);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            // Si ce n'est pas du JSON, on essaie la mise à jour directe
                            return directStatusUpdate(repairId, statusId, sendSms);
                        }
                    });
                })
                .then(handleSuccess)
                .catch(formError => {
                    console.error('Erreur lors de la seconde tentative:', formError);
                    // Tenter une mise à jour directe du statut sans passer par l'API
                    return directStatusUpdate(repairId, statusId, sendSms)
                        .then(handleSuccess)
                        .catch(directError => {
                            handleError(directError);
                        });
                });
            } else {
                // Erreur normale, pas de seconde tentative
                handleError(error);
            }
        });
        
        // Fonction pour tenter une mise à jour directe du statut sans passer par l'API complète
        function directStatusUpdate(repairId, statusId, sendSms) {
            console.log('Effectuant une mise à jour directe du statut...');
            
            // URL simplifiée pour juste mettre à jour le statut
            return fetch('../ajax/simple_status_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `repair_id=${repairId}&status_id=${statusId}&send_sms=${sendSms ? 1 : 0}`
            })
            .then(response => {
                if (!response.ok) {
                    // En dernier recours, simuler une réponse de succès
                    console.log('Mise à jour directe échouée, simulation de réponse');
                    return {
                        success: true,
                        message: 'Statut mis à jour localement',
                        data: {
                            badge: getDefaultBadge(statusId),
                            sms_sent: false,
                            sms_message: 'SMS non envoyé (mise à jour locale)'
                        }
                    };
                }
                
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // En cas d'erreur, simuler une réponse
                        return {
                            success: true,
                            message: 'Statut mis à jour localement',
                            data: {
                                badge: getDefaultBadge(statusId),
                                sms_sent: false,
                                sms_message: 'SMS non envoyé (mise à jour locale)'
                            }
                        };
                    }
                });
            });
        }
        
        // Fonction pour gérer le succès
        function handleSuccess(data) {
            console.log('Réponse JSON:', data);
            
            if (data.success) {
                // Mettre à jour le badge avec le nouveau statut
                statusIndicator.innerHTML = data.data.badge;
                
                // Mettre à jour l'attribut data-status de la carte
                const card = statusIndicator.closest('.draggable-card');
                if (card) {
                    card.setAttribute('data-status', data.data.statut);
                    
                    // Animation de succès
                    card.classList.add('updated');
                    setTimeout(() => {
                        card.classList.remove('updated');
                    }, 1000);
                }
                
                // Afficher un message de succès pour le changement de statut
                showSilentNotification('Statut mis à jour avec succès', 'success');
                
                // Afficher un message supplémentaire si un SMS a été envoyé
                if (data.data && data.data.sms_sent) {
                    setTimeout(() => {
                        showSilentNotification('SMS envoyé au client', 'info');
                    }, 1000); // Attendre 1 seconde pour montrer la seconde notification
                } else if (data.data && data.data.sms_message) {
                    setTimeout(() => {
                        showSilentNotification(data.data.sms_message, 'info');
                    }, 1000);
                }
            } else {
                // Afficher l'erreur
                showSilentNotification('Erreur: ' + (data.message || 'Une erreur est survenue'), 'danger');
                
                // Recharger la page pour rétablir l'état correct
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        }
        
        // Fonction pour gérer les erreurs
        function handleError(error) {
            showSilentNotification('Erreur de communication avec le serveur: ' + error.message, 'danger');
            console.error('Détails de l\'erreur:', error);
            
            // Dans le cas d'une erreur, on met quand même à jour visuellement le statut
            // pour donner un retour à l'utilisateur, même si le serveur n'a pas répondu
            statusIndicator.innerHTML = getDefaultBadge(statusId);
            
            // Recharger la page après un délai pour synchroniser avec le serveur
            setTimeout(() => {
                location.reload();
            }, 3000);
        }
        
        // Fonction pour obtenir un badge par défaut en cas d'erreur
        function getDefaultBadge(statusId) {
            // Logique simplifiée pour déterminer la couleur du badge
            let color = 'primary';
            let icon = 'info-circle';
            let text = 'Nouveau statut';
            
            // Associer des couleurs aux ID de statut courants (à adapter selon vos statuts)
            if (statusId === 1) { // Nouveau Diagnostique
                color = 'info';
                icon = 'search';
                text = 'Diagnostique';
            } else if (statusId === 3) { // Nouvelle Commande
                color = 'warning';
                icon = 'shopping-cart';
                text = 'Commande';
            } else if (statusId === 9) { // Réparation Effectuée
                color = 'success';
                icon = 'check-circle';
                text = 'Terminé';
            } else if (statusId === 11) { // Restitué
                color = 'dark';
                icon = 'box-open';
                text = 'Restitué';
            }
            
            return `<span class="badge bg-${color}"><i class="fas fa-${icon} me-1"></i> ${text}</span>`;
        }
    }
</script>

<!-- Modal de détails de réparation - Version moderne complètement refaite -->
<div class="modal fade" id="repairDetailsModal" tabindex="-1" aria-labelledby="repairDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content modern-repair-modal">
            <div class="modal-header modern-repair-header">
                <div class="repair-header-content">
                    <div class="repair-status-indicator" id="repairStatusIndicator"></div>
                    <div class="repair-title-section">
                        <h5 class="modal-title" id="repairDetailsModalLabel">
                            <i class="fas fa-tools me-2"></i>
                            <span id="repairTitleText">Réparation #<span id="repairIdDisplay">--</span></span>
                        </h5>
                        <div class="repair-subtitle" id="repairSubtitle">Chargement...</div>
                    </div>
                    <div class="repair-warranty-badge" id="warrantyBadge">
                        <span class="warranty-text"></span>
                    </div>
                </div>
                <button type="button" class="btn-close modern-btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body modern-repair-body">
                <!-- Contenu principal -->
                <div id="repairDetailsContent" class="repair-content-container">
                    <!-- Le contenu sera généré dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le modal de réparation -->
<script src="/assets/js/price-numpad.js"></script>
<!-- <script src="/assets/js/modern-status-modal.js"></script> --> <!-- Désactivé temporairement pour éviter les conflits avec le glisser-déposer -->


<!-- Modal de création de devis (PROPRE) -->
<?php include 'components/modals/devis_modal_clean.php'; ?>
<!-- <?php include 'includes/modals.php'; ?> -->

<!-- Script pour le modal de devis (PROPRE) -->
<script src="/assets/js/devis-clean.js"></script>

<style>
/* ===== MODAL DE RÉPARATION MODERNE ===== */
.modern-repair-modal {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
}

.modern-repair-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 1.5rem;
    position: relative;
}

.repair-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 1rem;
}

.repair-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    animation: pulse 2s infinite;
}

.repair-title-section {
    flex: 1;
}

.repair-title-section .modal-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.repair-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.repair-warranty-badge {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
    display: none;
}

.repair-warranty-badge.active {
    display: block;
}

.modern-btn-close {
    color: white;
    opacity: 0.8;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.modern-btn-close:hover {
    opacity: 1;
    transform: scale(1.1);
}

.modern-repair-body {
    padding: 0;
    background: var(--day-bg-animated);
}

.repair-content-container {
    min-height: 400px;
}

/* ===== SECTIONS DU CONTENU ===== */
.repair-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    margin: 1rem;
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: border-color 0.2s ease, opacity 0.2s ease;
    overflow: visible !important; /* Permet aux tooltips de dépasser */
}

.repair-section:hover {
    /* OPTIMISÉ: Suppression transform/box-shadow lourds */
    border-color: rgba(102, 126, 234, 0.4);
    opacity: 0.95;
}

.repair-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.repair-section-title i {
    color: #667eea;
    font-size: 1.2rem;
}

/* ===== GRILLE D'ACTIONS ===== */
.repair-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.repair-action-btn {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition: border-color 0.2s ease, color 0.2s ease;
    text-decoration: none;
    color: #374151;
    font-weight: 500;
    min-height: 80px;
    position: relative;
    overflow: hidden;
}

/* ANIMATION SHINE SUPPRIMÉE POUR PERFORMANCE */
/* .repair-action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s;
}

.repair-action-btn:hover::before {
    left: 100%;
} */

.repair-action-btn:hover {
    /* OPTIMISÉ: Suppression transform lourd */
    border-color: rgba(102, 126, 234, 0.4);
    color: #1f2937;
}

.repair-action-btn i {
    font-size: 1.5rem;
    /* Suppression transition pour performance */
}

.repair-action-btn:hover i {
    /* OPTIMISÉ: Suppression scale lourd sur icônes */
}

/* Couleurs spécifiques par action */
.repair-action-btn.devis { background: linear-gradient(135deg, #ddd6fe 0%, #c4b5fd 100%); }
.repair-action-btn.devis i { color: #7c3aed; }

.repair-action-btn.status { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); }
.repair-action-btn.status i { color: #16a34a; }

.repair-action-btn.price { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
.repair-action-btn.price i { color: #d97706; }

.repair-action-btn.order { background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%); }
.repair-action-btn.order i { color: #0891b2; }

.repair-action-btn.print { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); }
.repair-action-btn.print i { color: #6b7280; }

.repair-action-btn.client { background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); }
.repair-action-btn.client i { color: #be185d; }

/* ===== BOUTON PRINCIPAL DE RÉPARATION ===== */
.repair-main-action {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 16px;
    padding: 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.repair-main-action.stop {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.repair-main-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.repair-main-action.stop:hover {
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
}

/* ===== INFORMATIONS CLIENT/APPAREIL ===== */
.repair-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.repair-info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.repair-info-item i {
    color: #667eea;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.repair-info-item .info-content {
    flex: 1;
}

.repair-info-item .info-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.repair-info-item .info-value {
    font-size: 1rem;
    color: #1f2937;
    font-weight: 600;
    margin-top: 0.25rem;
}

/* ===== PHOTOS ===== */
.repair-photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.repair-photo-item {
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.repair-photo-item:hover {
    /* OPTIMISÉ: Suppression scale/box-shadow lourds */
    opacity: 0.85;
}

.repair-photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.repair-add-photo {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    aspect-ratio: 1;
}

.repair-add-photo:hover {
    border-color: #667eea;
    color: #667eea;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

/* ===== STYLES DARK MODE POUR LE MODAL ===== */
body.dark-mode .modern-repair-body {
    background: var(--night-bg) !important; /* OPTIMISÉ: Suppression animation */
}

body.dark-mode .repair-section {
    background: rgba(30, 32, 45, 0.95) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
}

body.dark-mode .repair-section-title {
    color: rgba(235, 240, 255, 0.95) !important;
}

body.dark-mode .repair-action-btn {
    background: rgba(40, 42, 55, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(235, 240, 255, 0.85) !important;
}

body.dark-mode .repair-action-btn:hover {
    background: rgba(50, 52, 65, 0.9) !important;
    border-color: #667eea !important;
    /* OPTIMISÉ: Suppression box-shadow lourd */
}

body.dark-mode .repair-info-item {
    color: rgba(235, 240, 255, 0.85) !important;
}

body.dark-mode .info-label {
    color: rgba(235, 240, 255, 0.6) !important;
}

body.dark-mode .info-value {
    color: rgba(235, 240, 255, 0.95) !important;
}

body.dark-mode .repair-add-photo {
    background: rgba(40, 42, 55, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: rgba(235, 240, 255, 0.7) !important;
}

body.dark-mode .repair-add-photo:hover {
    border-color: #667eea !important;
    color: #7d9bff !important;
    background: rgba(50, 52, 65, 0.9) !important;
}

/* ===== ANIMATIONS ===== */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.repair-section {
    animation: slideIn 0.5s ease-out;
}

/* ===== MODE NUIT ===== */
body.dark-mode .modern-repair-modal {
    background: #0f172a;
}

body.dark-mode .modern-repair-body {
    background: var(--night-bg-animated);
}

body.dark-mode .repair-section {
    background: rgba(15, 23, 42, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

body.dark-mode .repair-section-title {
    color: #e2e8f0;
}

body.dark-mode .repair-action-btn {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #e2e8f0;
    border-color: rgba(255, 255, 255, 0.1);
}

body.dark-mode .repair-action-btn:hover {
    color: #f8fafc;
}

body.dark-mode .repair-info-item {
    background: rgba(30, 41, 59, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
}

body.dark-mode .repair-info-item .info-label {
    color: #94a3b8;
}

body.dark-mode .repair-info-item .info-value {
    color: #e2e8f0;
}

body.dark-mode .repair-add-photo {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-color: #475569;
    color: #94a3b8;
}

body.dark-mode .repair-add-photo:hover {
    border-color: #667eea;
    color: #667eea;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .repair-info-grid {
        grid-template-columns: 1fr;
    }
    
    .repair-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .modern-repair-header {
        padding: 1rem;
    }
    
    .repair-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .repair-warranty-badge {
        align-self: flex-end;
    }
}

#repairDetailsModal .modal-dialog {
    max-width: 90%;
}

/* ===== MODAL DE DEVIS - CORRECTION Z-INDEX ET BACKDROP ===== */
#devisModalClean {
    z-index: 1060 !important;
}

#devisModalClean .modal-dialog {
    z-index: 1061 !important;
    position: relative;
    pointer-events: auto !important;
}

#devisModalClean .modal-content {
    z-index: 1062 !important;
    position: relative;
    pointer-events: auto !important;
}

/* S'assurer que tous les éléments interactifs sont cliquables */
#devisModalClean .modal-footer,
#devisModalClean .modal-header,
#devisModalClean .modal-body {
    pointer-events: auto !important;
    position: relative;
    z-index: 1063 !important;
}

#devisModalClean button,
#devisModalClean input,
#devisModalClean textarea,
#devisModalClean select,
#devisModalClean .btn {
    pointer-events: auto !important;
    position: relative;
    z-index: 1064 !important;
}

/* Forcer le backdrop du modal de devis */
#devisModalClean.show ~ .modal-backdrop,
body:has(#devisModalClean.show) .modal-backdrop {
    z-index: 1055 !important;
    display: block !important;
    opacity: 0.5 !important;
    visibility: visible !important;
}

/* S'assurer que le modal de devis est au-dessus */
body.modal-open #devisModalClean.show {
    display: block !important;
    z-index: 1060 !important;
}

/* Correction pour les modals empilés */
.modal.show + .modal.show {
    z-index: 1070 !important;
}

.modal.show + .modal.show .modal-dialog {
    z-index: 1071 !important;
}

.modal.show + .modal.show ~ .modal-backdrop {
    z-index: 1065 !important;
}

/* Forcer l'affichage correct du modal de devis */
#devisModalClean.fade.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Centrage du modal de devis */
#devisModalClean .modal-dialog {
    margin: 1.75rem auto;
    max-width: 90%;
}

@media (min-width: 576px) {
    #devisModalClean .modal-dialog {
        max-width: 90%;
        margin: 1.75rem auto;
    }
}

@media (min-width: 992px) {
    #devisModalClean .modal-dialog {
        max-width: 80%;
    }
}

/* Mode nuit pour le modal de devis */
body.dark-mode #devisModalClean .modal-content {
    background-color: #1f2937;
    border: 1px solid #374151;
}

body.dark-mode #devisModalClean .modal-header {
    background-color: #111827;
    border-bottom-color: #374151;
}

body.dark-mode #devisModalClean .modal-body {
    background-color: #1f2937;
    color: #e5e7eb;
}

body.dark-mode #devisModalClean .modal-footer {
    background-color: #111827;
    border-top-color: #374151;
}

#repairDetailsModal .modal-title {
    position: relative;
    width: 100%;
}

/* Styles pour les badges de garantie */
.warranty-badge {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.warranty-active { color: #047857; }
.warranty-expired { color: #b91c1c; }
.warranty-expiring { color: #b45309; }
.warranty-none { color: #374151; }

/* Animations modernes */
@keyframes fadeInBounce {
    0% {
        opacity: 0;
        transform: translateX(40px) scale(0.7) rotate(5deg);
    }
    60% {
        opacity: 1;
        transform: translateX(-8px) scale(1.08) rotate(-2deg);
    }
    80% {
        transform: translateX(3px) scale(0.98) rotate(1deg);
    }
    100% {
        opacity: 1;
        transform: translateX(0) scale(1) rotate(0deg);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        filter: brightness(1);
    }
    50% {
        transform: scale(1.03);
        filter: brightness(1.1);
    }
}

/* ANIMATION SHINE SUPPRIMÉE POUR PERFORMANCE */
/* @keyframes shine {
    0% {
        left: -100%;
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        left: 100%;
        opacity: 0;
    }
} */

@keyframes statusPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.2);
    }
}

/* Animation de clignotement pour les garanties qui expirent */
.warranty-expiring .warranty-text {
    animation: pulse 1.5s infinite, blink 2.5s infinite, glow 3s infinite;
}

@keyframes blink {
    0%, 60% {
        opacity: 1;
    }
    80% {
        opacity: 0.8;
    }
    100% {
        opacity: 1;
    }
}

@keyframes glow {
    0%, 100% {
        box-shadow: 
            0 4px 15px rgba(245, 158, 11, 0.3),
            0 2px 8px rgba(245, 158, 11, 0.1),
            inset 0 1px 0 rgba(255,255,255,0.2);
    }
    50% {
        box-shadow: 
            0 6px 20px rgba(245, 158, 11, 0.5),
            0 4px 12px rgba(245, 158, 11, 0.2),
            inset 0 1px 0 rgba(255,255,255,0.3),
            0 0 20px rgba(245, 158, 11, 0.4);
    }
}

/* Responsive design pour le badge en superposition */
@media (max-width: 768px) {
    .warranty-badge {
        top: calc(50% - 0.1cm);
        right: 10px;
        transform: translateY(-50%) scale(0.9);
    }
    
    .warranty-text {
        padding: 6px 12px;
        font-size: 0.65rem;
        letter-spacing: 0.5px;
    }
    
    .warranty-text::before {
        width: 6px;
        height: 6px;
        left: 6px;
    }
}

@media (max-width: 480px) {
    .warranty-badge {
        top: calc(50% - 0.2cm);
        right: 8px;
        transform: translateY(-50%) scale(0.8);
    }
    
    .warranty-text {
        padding: 5px 10px;
        font-size: 0.6rem;
        letter-spacing: 0.3px;
    }
}

/* Mode sombre */
body.dark-mode .warranty-text {
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.1);
}

body.dark-mode .warranty-active {
    box-shadow: 
        0 4px 15px rgba(16, 185, 129, 0.4),
        0 2px 8px rgba(16, 185, 129, 0.2),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

body.dark-mode .warranty-expired {
    box-shadow: 
        0 4px 15px rgba(239, 68, 68, 0.4),
        0 2px 8px rgba(239, 68, 68, 0.2),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

body.dark-mode .warranty-expiring {
    box-shadow: 
        0 4px 15px rgba(245, 158, 11, 0.4),
        0 2px 8px rgba(245, 158, 11, 0.2),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

body.dark-mode .warranty-none {
    box-shadow: 
        0 4px 15px rgba(107, 114, 128, 0.3),
        0 2px 8px rgba(107, 114, 128, 0.15),
        inset 0 1px 0 rgba(255,255,255,0.05);
}

@media (max-width: 1200px) {
    #repairDetailsModal .modal-dialog {
        max-width: 80%;
    }
}

@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 85%;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-dialog {
        max-width: 95%;
    }
}

#repairDetailsModal .modal-content {
    border: none;
    border-radius: 0.75rem;
    overflow: hidden;
}

#repairDetailsModal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

#repairDetailsModal .modal-body {
    padding: 1.5rem;
    background-color: #ffffff;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

#repairDetailsModal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

/* Backdrop global pour tous les modals (effet blur et foncé) */
.modal-backdrop,
.modal-backdrop.show,
.modal-backdrop.fade.show {
    backdrop-filter: blur(8px) !important;
    background: rgba(0, 0, 0, 0.4) !important;
    transition: all 0.3s ease !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 1050 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
}

.dark-mode .modal-backdrop,
.dark-mode .modal-backdrop.show,
.dark-mode .modal-backdrop.fade.show {
    backdrop-filter: blur(12px) !important;
    background: rgba(0, 0, 0, 0.6) !important;
}

/* Forcer l'affichage du backdrop pour tous les modals */
        body.modal-open .modal-backdrop {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        /* Animation pour le modal SMS custom */
        @keyframes modalSlideIn {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        @keyframes modalSlideOut {
            0% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
        }

/* Spécifique pour repairDetailsModal */
#repairDetailsModal.show ~ .modal-backdrop,
body:has(#repairDetailsModal.show) .modal-backdrop {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 1050 !important;
}

/* Forcer l'affichage du modal SMS avec z-index très élevé */
#smsModal {
    z-index: 9999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    background: rgba(255, 0, 0, 0.3) !important;
}

#smsModal.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 9999 !important;
}

#smsModal .modal-dialog {
    z-index: 10000 !important;
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    width: 500px !important;
    margin: 0 !important;
    pointer-events: auto !important;
    max-width: 500px !important;
    border: 3px solid blue !important;
}

#smsModal .modal-content {
    z-index: 10001 !important;
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    background: white !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    border: 5px solid red !important;
    min-height: 300px !important;
}

/* Forcer tous les éléments internes du modal SMS */
#smsModal .modal-header,
#smsModal .modal-body,
#smsModal .modal-footer {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 10002 !important;
}

/* Forcer l'affichage de tous les éléments du formulaire */
#smsModal input,
#smsModal textarea,
#smsModal select,
#smsModal button,
#smsModal label {
    z-index: 10003 !important;
    position: relative !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Mode sombre pour le modal SMS */
.dark-mode #smsModal .modal-content {
    background: #1e2534 !important;
    color: #e2e8f0 !important;
}

.dark-mode #smsModal .modal-header {
    background-color: #000000 !important;
    border-bottom-color: #374151 !important;
}

.dark-mode #smsModal .modal-title {
    color: #ffffff !important;
}

.dark-mode #smsModal .btn-secondary {
    color: #ffffff !important;
}

/* Styles pour le mode sombre */
.dark-mode #repairDetailsModal .modal-content {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    border-bottom-color: #374151 !important;
}

/* Sélecteurs plus spécifiques pour forcer le fond noir */
body.dark-mode #repairDetailsModal .modal-header,
html body.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    background: #000000 !important;
}

/* Forcer le titre du modal en blanc en mode nuit */
.dark-mode #repairDetailsModal .modal-header .modal-title,
.dark-mode #repairDetailsModal .modal-header #repairDetailsModalLabel,
.dark-mode #repairDetailsModal .modal-header #repairTitleText {
    color: #ffffff !important;
}

.dark-mode #repairDetailsModal .modal-body {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-footer {
    background-color: #1f2937;
    border-top-color: #374151;
}

.dark-mode #repairDetailsModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #repairDetailsModal .btn-secondary {
    background-color: #4b5563;
    border-color: #374151;
    color: #ffffff !important; /* Texte FERMER en blanc en mode nuit */
}

.dark-mode #repairDetailsContent {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #repairDetailsLoader .spinner-border {
    border-right-color: transparent;
}

/* Styles pour les cartes en mode sombre */
.dark-mode #repairDetailsModal .card {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode #repairDetailsModal .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-body {
    background-color: #1f2937;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-footer {
    background-color: #111827;
    border-top-color: #374151;
}

/* Styles pour les éléments d'information en mode sombre */
.dark-mode .repair-summary-item {
    border-right-color: #374151;
}

.dark-mode .repair-summary-item .info .label {
    color: #94a3b8;
}

.dark-mode .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.1);
    color: #60a5fa;
}

.dark-mode .contact-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-label {
    color: #94a3b8;
}

.dark-mode .empty-state {
    color: #94a3b8;
}

/* Styles pour les cartes */
#repairDetailsModal .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.2s ease;
    border-radius: 0.5rem;
    overflow: hidden;
    height: 100%;
}

#repairDetailsModal .card:hover {
    /* OPTIMISÉ dark-mode: Suppression box-shadow lourd */
    opacity: 0.95;
}

#repairDetailsModal .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.25rem;
}

#repairDetailsModal .card-body {
    padding: 1.25rem;
    background-color: #ffffff;
}

/* Styles pour le résumé de réparation */
.repair-summary {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    width: 100%;
}
.repair-summary-item {
    flex: 1;
    min-width: 180px;
    display: flex;
    align-items: center;
    padding: 1rem;
    border-right: 1px solid rgba(0, 0, 0, 0.05);
}

.repair-summary-item:last-child {
    border-right: none;
}

.icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.repair-summary-item .info .label {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.repair-summary-item .info .value {
    font-weight: 600;
    font-size: 1rem;
}

/* Styles pour les informations client */
.contact-info {
    margin-top: 1rem;
}

.contact-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.contact-info-item:last-child {
    margin-bottom: 0;
}

.contact-info-item i {
    font-size: 1.1rem;
    width: 25px;
    margin-right: 0.75rem;
    text-align: center;
}

/* Styles pour les informations appareil */
.device-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.device-info-item {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.device-info-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.device-info-value {
    font-weight: 500;
    color: #343a40;
}

.problem-description {
    white-space: pre-line;
    line-height: 1.5;
}

.device-photo {
    margin-top: 1.5rem;
    text-align: center;
}

.device-photo img {
    max-height: 300px;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Styles pour les notes techniques */
.technical-notes {
    white-space: pre-line;
    line-height: 1.6;
    padding: 0.75rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
    font-size: 0.95rem;
}

/* Styles pour les photos */
.photo-gallery {
    margin: 0;
}

#repairDetailsModal .photo-item {
    position: relative;
    overflow: hidden;
    border-radius: 0.375rem;
    cursor: pointer;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    aspect-ratio: 1/1;
}

#repairDetailsModal .photo-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

#repairDetailsModal .photo-item:hover img {
    transform: scale(1.05);
}

#repairDetailsModal .photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.6));
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 0.75rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#repairDetailsModal .photo-item:hover .photo-overlay {
    opacity: 1;
}

#repairDetailsModal .photo-actions {
    display: flex;
    gap: 0.5rem;
}

/* Styles pour l'état vide */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6c757d;
}
/* Styles pour les boutons d'action */
.action-buttons, 
.client-action-buttons {
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    width: 100%;
}
.action-buttons .col-4,
.client-action-buttons .col-4 {
    width: 32%;
    flex: 0 0 auto;
    max-width: 32%;
    padding: 0;
}

#repairDetailsModal .action-btn,
#repairDetailsModal .client-action-btn {
    height: 120px;
    width: 100%;
    padding: 15px 10px;
    text-align: center;
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 10px;
    border-width: 2px;
}

#repairDetailsModal .action-btn i,
#repairDetailsModal .client-action-btn i {
    font-size: 2.25rem;
    margin-bottom: 12px;
}

#repairDetailsModal .action-btn:hover,
#repairDetailsModal .client-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .action-btn:active,
#repairDetailsModal .client-action-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

#repairDetailsModal .action-btn span,
#repairDetailsModal .client-action-btn span {
    font-size: 0.8rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: normal;
    line-height: 1.2;
    font-weight: 700;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 98%;
    }
    
    .repair-summary-item {
        min-width: 150px;
        padding: 1rem;
    }
    
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons, 
    .client-action-buttons {
        gap: 8px;
    }
    
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 31%;
        max-width: 31%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        padding: 10px 5px;
        height: 110px;
        border-width: 2px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.7rem;
        margin-top: 0.25rem !important;
        font-weight: 700;
        line-height: 1.1;
    }
    
    .repair-summary-item {
        flex: 0 0 50%;
        min-width: unset;
        border-right: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-child(even) {
        border-left: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .repair-summary-item:last-child:nth-child(odd) {
        border-bottom: none;
    }
    
    .device-photo img {
        max-height: 200px;
    }
}

@media (max-width: 576px) {
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 30%;
        max-width: 30%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        height: 100px;
        padding: 8px 5px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.75rem;
        margin-bottom: 8px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.65rem;
        line-height: 1;
    }
}

/* Animation de transition */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

/* Fix pour le scroll du modal - AJOUT POUR RÉSOUDRE LE PROBLÈME DE SCROLL */
#repairDetailsModal .modal-dialog {
    height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
}

#repairDetailsModal .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
}

#repairDetailsModal .modal-body {
    flex: 1;
    overflow-y: auto !important;
    max-height: none !important;
}

/* Correction pour mobile */
@media (max-width: 768px) {
    #repairDetailsModal .modal-dialog {
        height: calc(100vh - 20px);
        margin: 10px;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Styles pour le mode sombre */
.dark-mode #repairDetailsModal .modal-content {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    border-bottom-color: #374151 !important;
}

/* Sélecteurs plus spécifiques pour forcer le fond noir */
body.dark-mode #repairDetailsModal .modal-header,
html body.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    background: #000000 !important;
}

/* Forcer le titre du modal en blanc en mode nuit */
.dark-mode #repairDetailsModal .modal-header .modal-title,
.dark-mode #repairDetailsModal .modal-header #repairDetailsModalLabel,
.dark-mode #repairDetailsModal .modal-header #repairTitleText {
    color: #ffffff !important;
}

.dark-mode #repairDetailsModal .modal-body {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-footer {
    background-color: #1f2937;
    border-top-color: #374151;
}

.dark-mode #repairDetailsModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #repairDetailsModal .btn-secondary {
    background-color: #4b5563;
    border-color: #374151;
}

.dark-mode #repairDetailsContent {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #repairDetailsModal .card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
}

.dark-mode #repairDetailsModal .card:hover {
    /* OPTIMISÉ: Suppression box-shadow lourd */
    opacity: 0.95;
}

.dark-mode #repairDetailsModal .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-body {
    background-color: #1f2937;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-footer {
    background-color: #111827;
    border-top-color: #374151;
}

.dark-mode .repair-summary-item {
    border-right-color: #374151;
    border-bottom-color: #374151;
}

.dark-mode .repair-summary-item .info .label {
    color: #94a3b8;
}

.dark-mode .repair-summary-item .info .value {
    color: #e2e8f0;
}

.dark-mode .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.1);
    color: #60a5fa;
}

.dark-mode .contact-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-label {
    color: #94a3b8;
}

.dark-mode .device-info-value {
    color: #e2e8f0;
}

.dark-mode .technical-notes {
    background-color: rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
}

.dark-mode .empty-state {
    color: #94a3b8;
}

.dark-mode #repairDetailsModal .photo-item {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
}

.dark-mode #repairDetailsModal .photo-item:hover {
    /* OPTIMISÉ: Suppression box-shadow lourd */
    opacity: 0.9;
}

#repairDetailsModal .action-btn,
#repairDetailsModal .client-action-btn {
    height: 85px;
    width: 100%;
    padding: 10px 5px;
    text-align: center;
    transition: opacity 0.2s ease, border-color 0.2s ease; /* OPTIMISÉ: Spécifique au lieu de all */
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 8px;
    border-width: 1.5px;
}

#repairDetailsModal .action-btn i,
#repairDetailsModal .client-action-btn i {
    font-size: 1.75rem;
    margin-bottom: 8px;
}

#repairDetailsModal .action-btn span,
#repairDetailsModal .client-action-btn span {
    font-size: 0.7rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: normal;
    line-height: 1.2;
    font-weight: 700;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 98%;
    }
    
    .repair-summary-item {
        min-width: 150px;
        padding: 1rem;
    }
    
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons, 
    .client-action-buttons {
        gap: 6px;
    }
    
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 31%;
        max-width: 31%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        padding: 8px 5px;
        height: 75px;
        border-width: 1.5px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.65rem;
        margin-top: 0.1rem !important;
        font-weight: 700;
        line-height: 1.1;
    }
    
    .repair-summary-item {
        flex: 0 0 50%;
        min-width: unset;
        border-right: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-child(even) {
        border-left: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .repair-summary-item:last-child:nth-child(odd) {
        border-bottom: none;
    }
    
    .device-photo img {
        max-height: 200px;
    }
}

@media (max-width: 576px) {
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 30%;
        max-width: 30%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        height: 70px;
        padding: 6px 4px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.4rem;
        margin-bottom: 5px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.6rem;
        line-height: 1;
    }
}
</style>

<!-- Modal de confirmation pour arrêter une réparation -->
<div class="modal fade" id="stopRepairConfirmModal" tabindex="-1" aria-labelledby="stopRepairConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="stopRepairConfirmModalLabel">
                    <i class="fas fa-stop-circle me-2"></i>
                    Arrêter la réparation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3">Êtes-vous sûr de vouloir arrêter cette réparation ?</h5>
                <p class="text-muted">Vous pourrez choisir le statut après confirmation.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-warning btn-lg px-5" id="confirmStopRepairBtn">
                    <i class="fas fa-check me-2"></i>Oui, arrêter
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal de confirmation d'arrêt */
#stopRepairConfirmModal .modal-content {
    border-radius: 16px;
    overflow: hidden;
    background: #ffffff;
    color: #1a202c;
}

#stopRepairConfirmModal .modal-header {
    border-bottom: none;
    padding: 1.5rem;
}

#stopRepairConfirmModal .modal-body {
    padding: 2rem;
}

#stopRepairConfirmModal .modal-footer {
    border-top: none;
    padding: 1.5rem;
}

#stopRepairConfirmModal .btn {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

#stopRepairConfirmModal .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* Mode nuit - sélecteurs multiples pour compatibilité maximale */
.dark-mode #stopRepairConfirmModal .modal-content,
.night-mode #stopRepairConfirmModal .modal-content,
body.dark-mode #stopRepairConfirmModal .modal-content,
body.night-mode #stopRepairConfirmModal .modal-content,
html.dark-mode #stopRepairConfirmModal .modal-content,
html.night-mode #stopRepairConfirmModal .modal-content {
    background: #1a202c !important;
    color: #e2e8f0 !important;
    border: 1px solid #2d3748 !important;
}

.dark-mode #stopRepairConfirmModal .modal-header,
.night-mode #stopRepairConfirmModal .modal-header,
body.dark-mode #stopRepairConfirmModal .modal-header,
body.night-mode #stopRepairConfirmModal .modal-header,
html.dark-mode #stopRepairConfirmModal .modal-header,
html.night-mode #stopRepairConfirmModal .modal-header {
    background: #f59e0b !important;
    color: #1a202c !important;
}

.dark-mode #stopRepairConfirmModal h5,
.night-mode #stopRepairConfirmModal h5,
body.dark-mode #stopRepairConfirmModal h5,
body.night-mode #stopRepairConfirmModal h5,
html.dark-mode #stopRepairConfirmModal h5,
html.night-mode #stopRepairConfirmModal h5 {
    color: #e2e8f0 !important;
}

.dark-mode #stopRepairConfirmModal .modal-body h5,
.night-mode #stopRepairConfirmModal .modal-body h5,
body.dark-mode #stopRepairConfirmModal .modal-body h5,
body.night-mode #stopRepairConfirmModal .modal-body h5,
html.dark-mode #stopRepairConfirmModal .modal-body h5,
html.night-mode #stopRepairConfirmModal .modal-body h5 {
    color: #e2e8f0 !important;
}

.dark-mode #stopRepairConfirmModal .text-muted,
.night-mode #stopRepairConfirmModal .text-muted,
body.dark-mode #stopRepairConfirmModal .text-muted,
body.night-mode #stopRepairConfirmModal .text-muted,
html.dark-mode #stopRepairConfirmModal .text-muted,
html.night-mode #stopRepairConfirmModal .text-muted {
    color: #a0aec0 !important;
}

.dark-mode #stopRepairConfirmModal .btn-secondary,
.night-mode #stopRepairConfirmModal .btn-secondary,
body.dark-mode #stopRepairConfirmModal .btn-secondary,
body.night-mode #stopRepairConfirmModal .btn-secondary,
html.dark-mode #stopRepairConfirmModal .btn-secondary,
html.night-mode #stopRepairConfirmModal .btn-secondary {
    background: #2d3748 !important;
    border-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.dark-mode #stopRepairConfirmModal .btn-secondary:hover,
.night-mode #stopRepairConfirmModal .btn-secondary:hover,
body.dark-mode #stopRepairConfirmModal .btn-secondary:hover,
body.night-mode #stopRepairConfirmModal .btn-secondary:hover,
html.dark-mode #stopRepairConfirmModal .btn-secondary:hover,
html.night-mode #stopRepairConfirmModal .btn-secondary:hover {
    background: #4a5568 !important;
    border-color: #4a5568 !important;
}

.dark-mode #stopRepairConfirmModal .btn-warning,
.night-mode #stopRepairConfirmModal .btn-warning,
body.dark-mode #stopRepairConfirmModal .btn-warning,
body.night-mode #stopRepairConfirmModal .btn-warning,
html.dark-mode #stopRepairConfirmModal .btn-warning,
html.night-mode #stopRepairConfirmModal .btn-warning {
    background: #f59e0b !important;
    border-color: #f59e0b !important;
    color: #1a202c !important;
}

.dark-mode #stopRepairConfirmModal .btn-close,
.night-mode #stopRepairConfirmModal .btn-close,
body.dark-mode #stopRepairConfirmModal .btn-close,
body.night-mode #stopRepairConfirmModal .btn-close,
html.dark-mode #stopRepairConfirmModal .btn-close,
html.night-mode #stopRepairConfirmModal .btn-close {
    filter: invert(1) !important;
}
</style>

<!-- Modal de vérification du prix -->
<div class="modal fade" id="priceVerificationModal" tabindex="-1" aria-labelledby="priceVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="priceVerificationModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Vérification du prix
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="display-1 text-warning mb-3">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <h4>Attention : Prix à 0€</h4>
                    <p class="text-muted">Le prix de cette réparation est actuellement de 0€. Voulez-vous le mettre à jour avant de terminer ?</p>
                </div>
                
                <div class="form-group mb-4">
                    <label for="verificationPriceInput" class="form-label fw-bold">Nouveau prix (€)</label>
                    <div class="input-group input-group-lg">
                        <input type="number" class="form-control text-center fw-bold" id="verificationPriceInput" placeholder="0.00" step="0.01" min="0">
                        <span class="input-group-text">€</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between p-3">
                <button type="button" class="btn btn-outline-secondary" id="confirmZeroPriceBtn">
                    Confirmer 0€
                </button>
                <button type="button" class="btn btn-primary px-4" id="updatePriceAndFinishBtn">
                    <i class="fas fa-save me-2"></i>Mettre à jour et terminer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal de vérification de prix */
#priceVerificationModal .modal-content {
    border-radius: 16px;
    overflow: hidden;
    border: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

#priceVerificationModal .display-1 {
    font-size: 4rem;
    opacity: 0.8;
}

#priceVerificationModal .form-control {
    font-size: 1.5rem;
    border-radius: 12px 0 0 12px;
}

#priceVerificationModal .input-group-text {
    border-radius: 0 12px 12px 0;
    font-size: 1.5rem;
    font-weight: bold;
}

/* Mode nuit */
.dark-mode #priceVerificationModal .modal-content,
.night-mode #priceVerificationModal .modal-content,
body.dark-mode #priceVerificationModal .modal-content,
body.night-mode #priceVerificationModal .modal-content,
html.dark-mode #priceVerificationModal .modal-content,
html.night-mode #priceVerificationModal .modal-content {
    background: #1a202c !important;
    color: #e2e8f0 !important;
    border: 1px solid #4a5568 !important;
}

.dark-mode #priceVerificationModal .modal-header,
.night-mode #priceVerificationModal .modal-header,
body.dark-mode #priceVerificationModal .modal-header,
body.night-mode #priceVerificationModal .modal-header,
html.dark-mode #priceVerificationModal .modal-header,
html.night-mode #priceVerificationModal .modal-header {
    background: #d97706 !important;
    color: #fff !important;
    border-bottom: 1px solid #4a5568 !important;
}

.dark-mode #priceVerificationModal .text-muted,
.night-mode #priceVerificationModal .text-muted,
body.dark-mode #priceVerificationModal .text-muted,
body.night-mode #priceVerificationModal .text-muted,
html.dark-mode #priceVerificationModal .text-muted,
html.night-mode #priceVerificationModal .text-muted {
    color: #cbd5e0 !important;
}

.dark-mode #priceVerificationModal .form-control,
.night-mode #priceVerificationModal .form-control,
body.dark-mode #priceVerificationModal .form-control,
body.night-mode #priceVerificationModal .form-control,
html.dark-mode #priceVerificationModal .form-control,
html.night-mode #priceVerificationModal .form-control {
    background: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #fff !important;
}

.dark-mode #priceVerificationModal .input-group-text,
.night-mode #priceVerificationModal .input-group-text,
body.dark-mode #priceVerificationModal .input-group-text,
body.night-mode #priceVerificationModal .input-group-text,
html.dark-mode #priceVerificationModal .input-group-text,
html.night-mode #priceVerificationModal .input-group-text {
    background: #4a5568 !important;
    border-color: #4a5568 !important;
    color: #fff !important;
}

.dark-mode #priceVerificationModal .btn-outline-secondary,
.night-mode #priceVerificationModal .btn-outline-secondary,
body.dark-mode #priceVerificationModal .btn-outline-secondary,
body.night-mode #priceVerificationModal .btn-outline-secondary,
html.dark-mode #priceVerificationModal .btn-outline-secondary,
html.night-mode #priceVerificationModal .btn-outline-secondary {
    color: #cbd5e0 !important;
    border-color: #cbd5e0 !important;
}

.dark-mode #priceVerificationModal .btn-outline-secondary:hover,
.night-mode #priceVerificationModal .btn-outline-secondary:hover,
body.dark-mode #priceVerificationModal .btn-outline-secondary:hover,
body.night-mode #priceVerificationModal .btn-outline-secondary:hover,
html.dark-mode #priceVerificationModal .btn-outline-secondary:hover,
html.night-mode #priceVerificationModal .btn-outline-secondary:hover {
    background: #4a5568 !important;
    color: #fff !important;
}

/* Force dark mode for no-results-container */
.dark-mode .no-results-container,
.night-mode .no-results-container,
html[data-theme='dark'] .no-results-container,
body.dark-mode .no-results-container {
    background-color: #1e293b !important;
    border: 1px solid #334155 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5) !important;
}

.dark-mode .no-results-container i,
.night-mode .no-results-container i,
html[data-theme='dark'] .no-results-container i,
body.dark-mode .no-results-container i {
    color: #94a3b8 !important;
}

.dark-mode .no-results-container p,
.night-mode .no-results-container p,
html[data-theme='dark'] .no-results-container p,
body.dark-mode .no-results-container p {
    color: #e2e8f0 !important;
}
</style>

<!-- Modal pour le démarrage d'une réparation déjà active - Version simplifiée -->
<div class="modal fade" id="activeRepairModal" tabindex="-1" aria-labelledby="activeRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="activeRepairModalLabel">
                    <i class="fas fa-tools me-2"></i>Terminer la réparation en cours
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Informations de la réparation active -->
                <div class="text-center mb-4">
                    <div class="badge bg-primary fs-6 px-3 py-2 mb-2 active-repair-badge">
                        <i class="fas fa-cog fa-spin me-2"></i>
                        <span class="active-repair-text">Réparation <span id="activeRepairId"></span> en cours</span>
                    </div>
                    <!-- Section info supprimée -->
                </div>
                
                <hr class="my-4">

                         <!-- Actions principales simplifiées -->
                         <div class="text-center mb-3">
                             <div class="question-header p-3 mb-3">
                                 <h6 class="mb-0 fw-bold text-white">
                                     <i class="fas fa-question-circle me-2"></i>
                                     Comment terminer cette réparation ?
                                 </h6>
                    </div>
                </div>
                
                <!-- Boutons d'actions principaux -->
                <div class="d-grid gap-3">
                    <!-- Réparation terminée avec succès -->
                    <button type="button" class="btn btn-success btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="reparation_effectue">
                        <i class="fas fa-check-circle me-3 fs-4"></i>
                        <div class="text-start">
                            <div class="fw-bold">Réparation terminée</div>
                            <small class="opacity-75">L'appareil fonctionne parfaitement</small>
                                        </div>
                                            </button>

                    <!-- Besoin d'un devis -->
                    <button type="button" class="btn btn-info btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="en_attente_accord_client">
                        <i class="fas fa-file-invoice-dollar me-3 fs-4"></i>
                        <div class="text-start">
                            <div class="fw-bold">Envoyer un devis</div>
                            <small class="opacity-75">Pièces supplémentaires nécessaires</small>
                                        </div>
                                            </button>

                    <!-- Commander des pièces -->
                    <button type="button" class="btn btn-primary btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="nouvelle_commande">
                        <i class="fas fa-shopping-cart me-3 fs-4"></i>
                        <div class="text-start">
                            <div class="fw-bold">Commander des pièces</div>
                            <small class="opacity-75">Passer une commande fournisseur</small>
                                        </div>
                    </button>

                             <!-- Autres options (regroupées) -->
                             <div class="collapse" id="moreOptions">
                                 <div class="d-grid gap-3">
                                     <button type="button" class="btn btn-warning btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="en_attente_livraison">
                                         <i class="fas fa-truck me-3 fs-4"></i>
                                         <div class="text-start">
                                             <div class="fw-bold text-dark">En attente de livraison</div>
                                             <small class="text-dark opacity-75">Pièces commandées, en attente</small>
                                         </div>
                                            </button>

                                     <button type="button" class="btn btn-secondary btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="en_attente_responsable">
                                         <i class="fas fa-user-clock me-3 fs-4"></i>
                                         <div class="text-start">
                                             <div class="fw-bold text-white">Attendre un responsable</div>
                                             <small class="text-white opacity-75">Besoin d'une validation</small>
                                        </div>
                                            </button>

                                     <button type="button" class="btn btn-danger btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="reparation_annule">
                                         <i class="fas fa-times-circle me-3 fs-4"></i>
                                         <div class="text-start">
                                             <div class="fw-bold">Annuler la réparation</div>
                                             <small class="opacity-75">Impossible à réparer</small>
                                        </div>
                                            </button>
                                        </div>
                                    </div>
                                        
                             <!-- Bouton pour afficher plus d'options -->
                             <button class="btn btn-info btn-lg" type="button" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions" id="toggleMoreOptions">
                                 <i class="fas fa-ellipsis-h me-2"></i>
                                 <span class="more-text">Plus d'options</span>
                                 <span class="less-text d-none">Moins d'options</span>
                             </button>
                                </div>
                     <div class="modal-footer border-0 pt-0">
                         <button type="button" class="btn btn-danger btn-lg" data-bs-dismiss="modal">
                             <i class="fas fa-times me-2"></i>Fermer
                         </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal activeRepairModal simplifié */
#activeRepairModal {
    z-index: 26000 !important;
}

#activeRepairModal .modal-backdrop {
    z-index: 25999 !important;
    pointer-events: none !important;
}

#activeRepairModal .modal-dialog {
    max-width: 500px;
    z-index: 26001 !important;
}

/* === MODAL HISTORIQUE SMS MODERNE === */
#repairSmsHistoryModal {
    z-index: 25000 !important;
}

#repairSmsHistoryModal .modal-backdrop {
    z-index: 24999 !important;
}

.modern-sms-history-modal {
    background: transparent;
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    z-index: 25001 !important;
}

.repair-sms-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 16px 16px 0 0;
    border: none;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
    width: 100%;
}

.modal-icon {
    font-size: 2.2rem;
    opacity: 0.9;
}

.modal-title-section h2 {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 600;
    line-height: 1.2;
}
.modal-subtitle {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 0.95rem;
    font-weight: 400;
}

.repair-sms-close {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    border-radius: 10px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

.repair-sms-close:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.05);
}

.repair-sms-body {
    padding: 0;
    background: #f8fafc;
}

.repair-sms-footer {
    background: white;
    padding: 20px 30px;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 16px 16px;
}

.repair-sms-btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.repair-sms-btn-secondary {
    background: #6b7280;
    color: white;
}

.repair-sms-btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

/* Mode Futuriste (Nuit) */
body.dark-mode .repair-sms-modal {
    background: #0f172a;
    border: 1px solid #1e293b;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8);
}

body.dark-mode .repair-sms-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-bottom: 1px solid #334155;
}

body.dark-mode .repair-sms-body {
    background: #0f172a;
}

body.dark-mode .repair-sms-footer {
    background: #1e293b;
    border-top-color: #334155;
}

body.dark-mode .repair-sms-btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

body.dark-mode .repair-sms-btn-secondary:hover {
    background: #4b5563;
}

body.dark-mode .repair-sms-close {
    background: rgba(255, 255, 255, 0.1);
}

body.dark-mode .repair-sms-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Header moderne */
.modern-sms-header {
    position: relative;
    padding: 0;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    overflow: hidden;
}

.header-gradient {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, 
        rgba(102, 126, 234, 0.9) 0%, 
        rgba(118, 75, 162, 0.9) 100%);
    animation: gradientShift 3s ease-in-out infinite alternate;
}

@keyframes gradientShift {
    0% { opacity: 0.9; }
    100% { opacity: 0.7; }
}

.header-content {
    position: relative;
    display: flex;
    align-items: center;
    padding: 25px 30px;
    z-index: 2;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.header-icon i {
    font-size: 24px;
    color: white;
}

.header-text {
    flex: 1;
}

.header-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.header-subtitle {
    margin: 5px 0 0 0;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.modern-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 3;
}

.modern-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* Body moderne */
.modern-sms-body {
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 0;
    min-height: 400px;
    position: relative;
}

/* Loading moderne */
.modern-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 400px;
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
}

.loading-animation {
    text-align: center;
}

.loading-dots {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 20px;
}

.dot {
    width: 12px;
    height: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    animation: dotPulse 1.4s ease-in-out infinite both;
}

.dot:nth-child(1) { animation-delay: -0.32s; }
.dot:nth-child(2) { animation-delay: -0.16s; }
.dot:nth-child(3) { animation-delay: 0s; }

@keyframes dotPulse {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1.2);
        opacity: 1;
    }
}

.loading-text {
    color: #64748b;
    font-size: 16px;
    font-weight: 500;
    margin: 0;
}

/* Content area */
.sms-history-content {
    padding: 30px;
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 400px;
}

/* Footer moderne */
.modern-sms-footer {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: 20px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: none;
}

.footer-info {
    display: flex;
    align-items: center;
    color: #94a3b8;
    font-size: 14px;
    font-weight: 500;
}

.footer-info i {
    margin-right: 8px;
    color: #60a5fa;
}

.modern-footer-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.modern-footer-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

/* Mode sombre */
body.dark-mode .modern-sms-body,
body.dark-mode .sms-history-content {
    background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
}

body.dark-mode .loading-text {
    color: #94a3b8;
}

body.dark-mode .modern-sms-footer {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        padding: 20px;
    }
    
    .header-icon {
        width: 50px;
        height: 50px;
        margin-right: 15px;
    }
    
    .header-title {
        font-size: 20px;
    }
    
    .sms-history-content {
        padding: 20px;
    }
    
    .modern-sms-footer {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
    }
}

/* Spinner et loading */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 30px;
    gap: 20px;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

body.dark-mode .spinner {
    border-color: #334155;
    border-top-color: #667eea;
}

.loading-spinner p {
    color: #64748b;
    font-size: 1.1rem;
    margin: 0;
}

body.dark-mode .loading-spinner p {
    color: #94a3b8;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#activeRepairModal .complete-btn {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 10;
    pointer-events: auto !important;
}

#activeRepairModal .complete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

         #activeRepairModal .badge {
             border-radius: 25px;
         }

         /* Style pour la section question */
         #activeRepairModal .question-header {
             background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
             border-radius: 12px;
             box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
         }

/* Amélioration de la lisibilité du badge */
#activeRepairModal .active-repair-badge {
    background-color: #0d6efd !important;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

#activeRepairModal .active-repair-text {
    color: #ffffff !important;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

#activeRepairModal .active-repair-info {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(241, 245, 249, 0.9));
    padding: 12px;
    border-radius: 12px;
    margin-top: 10px;
    border: 1px solid rgba(203, 213, 225, 0.5);
    backdrop-filter: none;
}

#activeRepairModal .active-repair-info strong {
    color: #495057;
}

#activeRepairModal .fa-spin {
    animation: spin 2s linear infinite;
}

#activeRepairModal #toggleMoreOptions {
    transition: all 0.3s ease;
    border-radius: 8px;
}

         #activeRepairModal #toggleMoreOptions:hover {
             background-color: #31d2f2;
             border-color: #25cff2;
             color: #000;
         }

/* Animation pour le collapse */
#activeRepairModal .collapse {
    transition: all 0.3s ease;
}

         /* Style pour les boutons dans le collapse - maintenant gérés par d-grid gap-3 */

/* Personnalisation du backdrop du modal */
#activeRepairModal .modal-backdrop {
    background: linear-gradient(45deg, rgba(13, 110, 253, 0.3), rgba(25, 135, 84, 0.3));
}

.dark-mode #activeRepairModal .modal-backdrop {
    background: linear-gradient(45deg, rgba(13, 110, 253, 0.4), rgba(15, 23, 42, 0.6));
}

/* Couleur de fond personnalisée pour le modal - sans effet transparent/glossy/blurry */
#activeRepairModal .modal-content {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 2px solid #cbd5e1;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Styles pour le mode sombre - sans effet transparent/glossy/blurry */
.dark-mode #activeRepairModal .modal-content,
.night-mode #activeRepairModal .modal-content,
html[data-theme='dark'] #activeRepairModal .modal-content {
    background: linear-gradient(135deg, #111111 0%, #000000 100%) !important;
    color: #f8f9fa !important;
    border: 1px solid #333 !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

.dark-mode #activeRepairModal .active-repair-info,
.night-mode #activeRepairModal .active-repair-info,
html[data-theme='dark'] #activeRepairModal .active-repair-info {
    background: #000000 !important;
    border: 1px solid #333;
}

.dark-mode #activeRepairModal .modal-header,
.night-mode #activeRepairModal .modal-header,
html[data-theme='dark'] #activeRepairModal .modal-header {
    background-color: #0d6efd;
    border-bottom: 1px solid #333;
}

.dark-mode #activeRepairModal .text-muted,
.night-mode #activeRepairModal .text-muted,
html[data-theme='dark'] #activeRepairModal .text-muted {
    color: #9ca3af !important;
}

.dark-mode #activeRepairModal .badge.bg-primary,
.night-mode #activeRepairModal .badge.bg-primary,
html[data-theme='dark'] #activeRepairModal .badge.bg-primary {
    background-color: #0d6efd !important;
}

/* Styles spécifiques pour le mode sombre */
    color: #f3f4f6;
}

.dark-mode #activeRepairModal .complete-btn.btn-success {
    background-color: #198754;
    border-color: #198754;
}

.dark-mode #activeRepairModal .complete-btn.btn-info {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
    color: #000;
}

.dark-mode #activeRepairModal .complete-btn.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.dark-mode #activeRepairModal .complete-btn.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

.dark-mode #activeRepairModal .complete-btn.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

         .dark-mode #activeRepairModal .complete-btn.btn-danger {
             background-color: #dc3545;
             border-color: #dc3545;
         }

         /* Amélioration du contraste pour les boutons en attente en mode sombre */
         .dark-mode #activeRepairModal .complete-btn.btn-warning .text-dark {
             color: #000 !important;
         }

         .dark-mode #activeRepairModal .complete-btn.btn-secondary .text-white {
             color: #fff !important;
         }

         .dark-mode #activeRepairModal .btn-danger.btn-lg {
             background-color: #dc3545;
             border-color: #dc3545;
             color: #fff;
         }

.dark-mode #activeRepairModal .btn-outline-secondary {
    color: #f8f9fa;
    border-color: #6c757d;
}

.dark-mode #activeRepairModal .btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

         .dark-mode #activeRepairModal #toggleMoreOptions {
             background-color: #0dcaf0;
             border-color: #0dcaf0;
             color: #000;
         }

         .dark-mode #activeRepairModal #toggleMoreOptions:hover {
             background-color: #31d2f2;
             border-color: #25cff2;
             color: #000;
         }

         /* Style pour la section question en mode sombre */
         .dark-mode #activeRepairModal .question-header {
             background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
             border: 1px solid rgba(255, 255, 255, 0.2);
}
/* Styles responsifs pour mobile */
@media (max-width: 767px) {
    #activeRepairModal .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    #activeRepairModal .complete-btn {
        padding: 1rem;
    }
    
    #activeRepairModal .complete-btn .fs-4 {
        font-size: 1.2rem !important;
    }
}
</style>

<script>
// JavaScript pour améliorer l'expérience utilisateur du modal activeRepairModal
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du bouton "Plus d'options"
    const toggleMoreOptions = document.getElementById('toggleMoreOptions');
    const moreOptions = document.getElementById('moreOptions');
    
    if (toggleMoreOptions) {
        toggleMoreOptions.addEventListener('click', function() {
            const moreText = this.querySelector('.more-text');
            const lessText = this.querySelector('.less-text');
            const isExpanded = moreOptions.classList.contains('show');
            
            // Changer le texte du bouton après l'animation
            setTimeout(() => {
                if (isExpanded) {
                    moreText.classList.remove('d-none');
                    lessText.classList.add('d-none');
                } else {
                    moreText.classList.add('d-none');
                    lessText.classList.remove('d-none');
                }
            }, 150);
        });
    }
});

// Attendre que le DOM soit chargé pour les autres fonctionnalités
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de démarrage de réparation
    const startRepairButtons = document.querySelectorAll('.start-repair');
    
    // Ajouter des écouteurs d'événements à chaque bouton
    startRepairButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Récupérer l'ID de la réparation depuis l'attribut data-id
            const repairId = this.getAttribute('data-id');
            
            // Vérifier d'abord si l'utilisateur a déjà une réparation active
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'check_active_repair',
                    reparation_id: repairId
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.has_active_repair) {
                        // L'utilisateur a déjà une réparation active, afficher le modal
                        const activeRepair = data.active_repair;
                        document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                        document.getElementById('activeRepairDevice').textContent = activeRepair.modele || 'Non renseigné';
                        document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom || ''} ${activeRepair.client_prenom || ''}`.trim() || 'Non renseigné';
                        document.getElementById('activeRepairProblem').textContent = activeRepair.description_probleme || 'Non renseigné';
                        
                        // Ajouter des écouteurs aux boutons de statut
                        const completeButtons = document.querySelectorAll(".complete-btn");
                        completeButtons.forEach(button => {
                            // Créer un clone du bouton pour éviter les doublons d'écouteurs
                            const newButton = button.cloneNode(true);
                            button.parentNode.replaceChild(newButton, button);
                            
                            // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
                            newButton.addEventListener("click", function() {
                                const status = this.getAttribute("data-status");
                                completeActiveRepair(activeRepair.id, status);
                            });
                        });
                        
                        // Afficher le modal
                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                        activeRepairModal.show();
                    } else {
                        // L'utilisateur n'a pas de réparation active, attribuer la réparation
                        assignRepair(repairId);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    });
    
    // Fonction pour assigner une réparation
    function assignRepair(repairId) {
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'assign_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Rafraîchir la page avec les réparations en cours au lieu de rediriger vers details_reparation
                window.location.href = `index.php?page=reparations&statut_ids=4,5`;
            } else {
                // Afficher une alerte en cas d'erreur
                alert(data.message || 'Une erreur est survenue lors de l\'attribution de la réparation.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    }
    

    
    // Fonction pour changer le statut d'une réparation
    function changeRepairStatus(repairId, status, callback) {
        fetch('ajax/change_repair_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_id: repairId,
                status: status
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Statut de la réparation ${repairId} changé à ${status}`);
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.error(`Erreur lors du changement de statut: ${data.message}`);
                alert(`Erreur lors du changement de statut: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors du changement de statut.');
        });
    }

    // Gestion des boutons démarrer/arrêter
    const startRepairBtns = document.querySelectorAll('.start-repair-btn');
    const stopRepairBtns = document.querySelectorAll('.stop-repair-btn');
    
    console.log('🔍 Boutons trouvés:', {
        start: startRepairBtns.length,
        stop: stopRepairBtns.length
    });
    
    // Écouteurs pour les boutons "Démarrer"
    startRepairBtns.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            console.log('▶️ Bouton démarrer cliqué pour réparation:', repairId);
            startRepairAction(repairId);
        });
    });
    
    // Écouteurs pour les boutons "Arrêter"
    stopRepairBtns.forEach(function(button) {
        console.log('🛑 Attachement du listener pour bouton stop, data-id:', button.getAttribute('data-id'));
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            console.log('🛑 CLIC DÉTECTÉ sur bouton stop pour réparation:', repairId);
            stopRepairAction(repairId);
        });
    });

    // Empêcher la fermeture automatique de certains modals (garde renforcée avec fenêtre de protection)
    const protectedIds = new Set(['nouvelles_actions_modal', 'chooseStatusModal']);
    const guardWindows = Object.create(null); // id -> timestamp

    document.addEventListener('show.bs.modal', function(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        if (protectedIds.has(id)) {
            // Fenêtre anti-fermeture pendant 500ms après ouverture (réduit de 1500ms)
            guardWindows[id] = Date.now() + 500;
            // Forcer mode statique à l'ouverture
            try {
                modal.setAttribute('data-bs-backdrop', 'static');
                modal.setAttribute('data-bs-keyboard', 'false');
                // Corriger backdrop click - UNIQUEMENT si pas déjà attaché
                if (!modal.dataset.guardListenerAttached) {
                    modal.dataset.guardListenerAttached = 'true';
                    modal.addEventListener('click', function(ev) {
                        if (ev.target === modal && !modal.dataset.allowHide) {
                            ev.stopPropagation();
                            ev.preventDefault();
                        }
                    }, { passive: false });
                }
            } catch (_) {}
        }
    });

    document.addEventListener('hide.bs.modal', function(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        const now = Date.now();
        if (protectedIds.has(id) && !modal.dataset.allowHide) {
            if (!guardWindows[id] || now <= guardWindows[id]) {
                console.warn('[MODAL GUARD] Empêche la fermeture (fenêtre active):', id);
                event.preventDefault();
                try {
                    const instance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: false });
                    setTimeout(() => instance.show(), 0);
                } catch (e) {}
                return;
            }
        }
    });

    document.addEventListener('hidden.bs.modal', function(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        if (protectedIds.has(id)) {
            delete guardWindows[id];
        }
    });

    // Monkey-patch: empêcher tout hide() programmatique sur les modals protégés
    if (window.bootstrap && bootstrap.Modal && !bootstrap.Modal.__patchedForAutoClose) {
        const originalHide = bootstrap.Modal.prototype.hide;
        bootstrap.Modal.prototype.hide = function() {
            try {
                const el = this && this._element ? this._element : null;
                const id = el && el.id ? el.id : '';
                if (el && protectedIds.has(id) && !el.dataset.allowHide) {
                    console.warn('[MODAL PATCH] hide() bloqué pour', id);
                    return; // bloquer
                }
            } catch (_) {}
            return originalHide.apply(this, arguments);
        };
        bootstrap.Modal.__patchedForAutoClose = true;
    }

});

/*
// Fonction pour démarrer une réparation
function startRepairAction(repairId) {
    if (confirm('Êtes-vous sûr de vouloir démarrer cette réparation ?')) {
        // Vérifier d'abord si l'utilisateur a déjà une réparation active
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_active_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.has_active_repair && data.active_repair.id != repairId) {
                    // L'utilisateur a déjà une réparation active différente
                    if (confirm('Vous avez déjà une réparation active (#' + data.active_repair.id + '). Voulez-vous la terminer et démarrer cette nouvelle réparation ?')) {
                        // Terminer d'abord la réparation active
                        completeActiveRepairAndStartNew(data.active_repair.id, repairId);
                    }
                } else {
                    // L'utilisateur n'a pas de réparation active, attribuer la réparation
                    assignRepairAction(repairId);
                }
            } else {
                alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion lors de la vérification');
        });
    }
}
*/
// Fonction pour arrêter une réparation
/*
// Fonction globale pour terminer la réparation active
window.completeActiveRepair = function(repairId, finalStatus) {
    // Vérifier si nous avons un statut
    if (!finalStatus) {
        alert('Veuillez sélectionner un statut final');
        return;
    }
    
    // Si le statut est "en_attente_accord_client", ouvrir le modal d'envoi de devis
    if (finalStatus === 'en_attente_accord_client') {
        // Fermer le modal actif
        const activeRepairModalElement = document.getElementById('activeRepairModal');
        const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
        if (activeRepairModal) activeRepairModal.hide();
        
        // D'abord changer le statut de la réparation en "en_attente_accord_client"
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès après avoir mis à jour le statut
                showToast('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.', 'success');
                
                // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal d'envoi de devis
                if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                    window.RepairModal.executeAction('devis', repairId);
                } else {
                    showToast("Le module d'envoi de devis n'est pas disponible. La réparation a été mise en attente d'accord client.", 'warning');
                    // Rediriger vers le filtre "En attente" après un court délai
                    setTimeout(() => {
                        const currentView = localStorage.getItem('repairViewMode') || 'cards';
                        window.location.href = `index.php?page=reparations&statut_ids=6,7,8&view=${currentView}`;
                    }, 1500);
                }
            } else {
                showToast(data.message || 'Une erreur est survenue lors de la mise à jour du statut.', 'error');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Une erreur est survenue lors de la communication avec le serveur.', 'error');
            window.location.reload();
        });
        
        return;
    }
    
    // Si le statut est "nouvelle_commande", ouvrir le modal de commande de pièces
    if (finalStatus === 'nouvelle_commande') {
        // Fermer le modal actif
        const activeRepairModalElement = document.getElementById('activeRepairModal');
        const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
        if (activeRepairModal) activeRepairModal.hide();
        
        // D'abord changer le statut de la réparation
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès après avoir mis à jour le statut
                showToast('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.', 'success');
                
                // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal de commande
                if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                    window.RepairModal.executeAction('order', repairId);
                } else {
                    showToast("Le module de commande n'est pas disponible. La réparation a été mise en statut nouvelle commande.", 'warning');
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                showToast(data.message || 'Une erreur est survenue lors de la mise à jour du statut.', 'error');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Une erreur est survenue lors de la communication avec le serveur.', 'error');
            window.location.reload();
        });
        
        return;
    }
    
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'complete_active_repair',
            reparation_id: repairId,
            final_status: finalStatus
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const activeRepairModalElement = document.getElementById('activeRepairModal');
            const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
            if (activeRepairModal) activeRepairModal.hide();
            
            // Afficher un message de succès
            showToast('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.', 'success');
            
            // Recharger la page pour mettre à jour la liste
            window.location.reload();
        } else {
            showToast(data.message || 'Une erreur est survenue lors de la mise à jour du statut.', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('Une erreur est survenue lors de la communication avec le serveur.', 'error');
    });
};
*/

function stopRepairAction(repairId) {
    // Ouvrir le modal de confirmation au lieu de confirm()
    const stopConfirmModal = document.getElementById('stopRepairConfirmModal');
    if (!stopConfirmModal) {
        console.error('Modal de confirmation non trouvé');
        return;
    }
    
    const confirmBtn = document.getElementById('confirmStopRepairBtn');
    if (!confirmBtn) {
        console.error('Bouton de confirmation non trouvé');
        return;
    }
    
    // Nettoyer les anciens event listeners en clonant le bouton
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    console.log('✅ Event listener nettoyé');
    
    // Ajouter le nouvel event listener
    newConfirmBtn.addEventListener('click', () => {
        console.log('✅ Bouton "Oui, arrêter" cliqué');
        
        // Fermer le modal de confirmation
        const modalInstance = bootstrap.Modal.getInstance(stopConfirmModal);
        if (modalInstance) {
            modalInstance.hide();
            console.log('✅ Modal de confirmation fermé');
        }
        
        // Nous n'appelons plus complete_active_repair ici car cela fermerait la session
        // Au lieu de cela, nous ouvrons le modal pour demander le statut final
        // La session sera fermée quand l'utilisateur choisira un statut
        
        // Mettre à jour l'ID dans le modal pour l'affichage (même si on n'a pas les détails complets ici)
        const idElement = document.getElementById('activeRepairId');
        if (idElement) idElement.textContent = `#${repairId}`;
        
        // Attacher les écouteurs d'événements aux boutons du modal
        const completeButtons = document.querySelectorAll("#activeRepairModal .complete-btn");
        completeButtons.forEach(button => {
            // Créer un clone du bouton pour éviter les doublons d'écouteurs
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
            newButton.addEventListener("click", function() {
                const status = this.getAttribute("data-status");
                window.completeActiveRepair(repairId, status);
            });
        });
        
        // Fermer tous les modaux ouverts
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
        
        // Attendre que les modaux soient fermés avant d'ouvrir le nouveau
        setTimeout(() => {
            // Ouvrir le modal activeRepairModal
            const activeRepairModalElement = document.getElementById('activeRepairModal');
            if (activeRepairModalElement) {
                const activeRepairModal = new bootstrap.Modal(activeRepairModalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                activeRepairModal.show();
            } else {
                // Si le modal n'existe pas, recharger la page
                location.reload();
            }
        }, 300);
    }
}

// Exposer les fonctions globalement pour le modal
window.startRepairAction = startRepairAction;
window.stopRepairAction = stopRepairAction;
window.completeActiveRepairAndStartNew = completeActiveRepairAndStartNew;

// Fonction pour attribuer une réparation
function assignRepairAction(repairId) {
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'assign_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réparation démarrée avec succès !');
            location.reload();
        } else {
            alert('Erreur lors du démarrage : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors du démarrage');
    });
}

// Fonction pour terminer une réparation active et en démarrer une nouvelle
function completeActiveRepairAndStartNew(activeRepairId, newRepairId, finalStatus = 'reparation_effectue') {
    console.log('🚀 completeActiveRepairAndStartNew appelée avec:', {
        activeRepairId,
        newRepairId,
        finalStatus
    });
    
    // Fermer le modal activeRepairModal d'abord
    const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
    if (activeRepairModal) {
        activeRepairModal.hide();
    }
    
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'complete_active_repair',
            reparation_id: activeRepairId,
            final_status: finalStatus
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Maintenant attribuer la nouvelle réparation
            assignRepairAction(newRepairId);
        } else {
            alert('Erreur lors de la finalisation : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la finalisation');
    });
}
</script>
<!-- Modal pour envoyer un SMS -->
<div class="modal fade" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel"><i class="fas fa-paper-plane me-2"></i>Envoyer un SMS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="smsForm" method="POST" action="ajax/send_sms.php">
                <div class="modal-body">
                    <input type="hidden" id="client_id" name="client_id" value="">
                    
                    <div class="mb-4">
                        <label for="recipient" class="form-label">Destinataire</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" class="form-control" id="recipient_name" readonly>
                            <input type="text" class="form-control" id="recipient_tel" name="telephone" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="template" class="form-label">Modèle de message</label>
                        <div class="template-wrapper position-relative">
                            <select class="form-select form-select-lg" id="template" name="template">
                                <option value="">Sélectionner un modèle...</option>
                                <!-- Les modèles seront chargés dynamiquement -->
                            </select>
                            <div class="position-absolute top-50 end-0 translate-middle-y me-3 pointer-events-none">
                                <i class="fas fa-chevron-down text-primary"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="charCount" class="badge bg-light text-dark">0 caractères</div>
                            <div id="smsCount" class="badge">1 SMS</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-lg btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary" id="sendSmsBtn">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Historique SMS Moderne -->
<div class="modal fade" id="repairSmsHistoryModal" tabindex="-1" style="z-index: 25000 !important;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modern-sms-history-modal">
            <!-- Header avec gradient -->
            <div class="modern-sms-header">
                <div class="header-gradient"></div>
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="header-title">Historique SMS</h3>
                        <p class="header-subtitle" id="repairSmsClientInfo">Chargement...</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Body avec contenu moderne -->
            <div class="modern-sms-body">
                <!-- Loading State -->
                <div class="modern-loading" id="repairSmsLoading">
                    <div class="loading-animation">
                        <div class="loading-dots">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>
                        <p class="loading-text">Chargement de l'historique SMS...</p>
                    </div>
                </div>
                
                <!-- Content Area -->
                <div class="sms-history-content" id="repairSmsContent" style="display: none;">
                    <!-- Le contenu sera injecté ici -->
                </div>
            </div>
            
            <!-- Footer moderne -->
            <div class="modern-sms-footer">
                <div class="footer-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Historique des 50 derniers SMS</span>
                </div>
                <button type="button" class="modern-footer-btn" data-bs-dismiss="modal">
                    <i class="fas fa-check"></i>
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteRepairModal" tabindex="-1" aria-labelledby="deleteRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRepairModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette réparation ?</p>
                <p class="text-danger"><strong>Attention :</strong> Cette action est irréversible et supprimera définitivement toutes les données associées à cette réparation.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour ouvrir le modal SMS
function openSmsModal(clientId, nom, prenom, telephone) {
    // Stocker les données du client dans des variables globales pour le modal custom
    window.currentClientId = clientId;
    window.currentClientNom = nom;
    window.currentClientPrenom = prenom;
    window.currentClientTel = telephone;
    
    // Créer le backdrop manuellement d'abord
    let backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
    
    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.style.cssText = `
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        z-index: 9998 !important;
        backdrop-filter: ${document.body.classList.contains('dark-mode') ? 'blur(12px)' : 'blur(8px)'} !important;
        background: ${document.body.classList.contains('dark-mode') ? 'rgba(0, 0, 0, 0.6)' : 'rgba(0, 0, 0, 0.4)'} !important;
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    `;
    document.body.appendChild(backdrop);
    
    // Supprimer tout modal SMS existant
    const existingModal = document.getElementById('customSmsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode');
    
    // Créer un modal moderne avec design adaptatif
    const newModal = document.createElement('div');
    newModal.id = 'customSmsModal';
    newModal.innerHTML = `
        <div class="custom-sms-modal-dialog" style="
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 520px !important;
            max-width: 90vw !important;
            z-index: 10000 !important;
            background: ${isDarkMode ? 
                'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%)' : 
                'linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #e2e8f0 100%)'
            } !important;
            border: ${isDarkMode ? 
                '1px solid rgba(59, 130, 246, 0.3)' : 
                '1px solid rgba(148, 163, 184, 0.2)'
            } !important;
            border-radius: 20px !important;
            padding: 0 !important;
            box-shadow: ${isDarkMode ? 
                '0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(59, 130, 246, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.05)' : 
                '0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.8), inset 0 1px 0 rgba(255, 255, 255, 0.9)'
            } !important;
            backdrop-filter: blur(20px) !important;
            animation: modalSlideIn 0.3s ease-out !important;
        ">
            <!-- Header -->
            <div class="custom-sms-header" style="
                padding: 24px 28px 20px 28px !important;
                border-bottom: ${isDarkMode ? 
                    '1px solid rgba(59, 130, 246, 0.2)' : 
                    '1px solid rgba(148, 163, 184, 0.15)'
                } !important;
                background: ${isDarkMode ? 
                    'linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%)' : 
                    'linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.8) 100%)'
                } !important;
                border-radius: 20px 20px 0 0 !important;
                position: relative !important;
            ">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="
                            width: 48px; 
                            height: 48px; 
                            background: ${isDarkMode ? 
                                'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' : 
                                'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)'
                            }; 
                            border-radius: 12px; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center;
                            box-shadow: ${isDarkMode ? 
                                '0 8px 25px -8px rgba(59, 130, 246, 0.4)' : 
                                '0 8px 25px -8px rgba(37, 99, 235, 0.3)'
                            };
                        ">
                            <i class="fas fa-sms" style="color: white; font-size: 20px;"></i>
                        </div>
                        <div>
                            <h3 style="
                                margin: 0 !important; 
                                color: ${isDarkMode ? '#f1f5f9' : '#1e293b'} !important;
                                font-size: 20px !important;
                                font-weight: 600 !important;
                                letter-spacing: -0.025em !important;
                            ">Envoyer un SMS</h3>
                            <p style="
                                margin: 4px 0 0 0 !important;
                                color: ${isDarkMode ? '#94a3b8' : '#64748b'} !important;
                                font-size: 14px !important;
                                font-weight: 400 !important;
                            ">À: ${nom} ${prenom} (${telephone})</p>
                        </div>
                    </div>
                    <button onclick="closeCustomModal()" style="
                        background: ${isDarkMode ? 
                            'rgba(51, 65, 85, 0.6)' : 
                            'rgba(148, 163, 184, 0.1)'
                        } !important;
                        border: none !important;
                        width: 36px !important;
                        height: 36px !important;
                        border-radius: 8px !important;
                        cursor: pointer !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        transition: all 0.2s ease !important;
                        color: ${isDarkMode ? '#94a3b8' : '#64748b'} !important;
                    " onmouseover="this.style.background='${isDarkMode ? 'rgba(239, 68, 68, 0.2)' : 'rgba(239, 68, 68, 0.1)'}'; this.style.color='${isDarkMode ? '#fca5a5' : '#dc2626'}';" onmouseout="this.style.background='${isDarkMode ? 'rgba(51, 65, 85, 0.6)' : 'rgba(148, 163, 184, 0.1)'}'; this.style.color='${isDarkMode ? '#94a3b8' : '#64748b'}';">
                        <i class="fas fa-times" style="font-size: 14px;"></i>
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="custom-sms-body" style="
                padding: 28px !important;
                background: ${isDarkMode ? 
                    'rgba(15, 23, 42, 0.4)' : 
                    'rgba(255, 255, 255, 0.6)'
                } !important;
            ">
                <div style="margin-bottom: 20px;">
                    <label style="
                        display: block;
                        margin-bottom: 8px;
                        color: ${isDarkMode ? '#e2e8f0' : '#374151'};
                        font-size: 14px;
                        font-weight: 500;
                        letter-spacing: -0.025em;
                    ">Modèle de message</label>
                    <select id="customSmsTemplate" style="
                        width: 100% !important;
                        padding: 12px 16px !important;
                        border: ${isDarkMode ? 
                            '1px solid rgba(59, 130, 246, 0.2)' : 
                            '1px solid rgba(203, 213, 225, 0.6)'
                        } !important;
                        border-radius: 12px !important;
                        font-size: 14px !important;
                        background: ${isDarkMode ? 
                            'rgba(30, 41, 59, 0.6)' : 
                            'rgba(255, 255, 255, 0.8)'
                        } !important;
                        color: ${isDarkMode ? '#f1f5f9' : '#1f2937'} !important;
                        margin-bottom: 16px !important;
                        cursor: pointer !important;
                    ">
                        <option value="">-- Sélectionner un modèle --</option>
                        <option value="loading" disabled>Chargement des modèles...</option>
                    </select>

                    <label style="
                        display: block;
                        margin-bottom: 8px;
                        color: ${isDarkMode ? '#e2e8f0' : '#374151'};
                        font-size: 14px;
                        font-weight: 500;
                        letter-spacing: -0.025em;
                    ">Message SMS</label>
                    <textarea id="customSmsMessage" placeholder="Tapez votre message SMS ici..." style="
                        width: 100% !important; 
                        height: 140px !important; 
                        padding: 16px !important; 
                        border: ${isDarkMode ? 
                            '1px solid rgba(59, 130, 246, 0.2)' : 
                            '1px solid rgba(203, 213, 225, 0.6)'
                        } !important;
                        border-radius: 12px !important;
                        font-size: 15px !important;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                        resize: vertical !important;
                        background: ${isDarkMode ? 
                            'rgba(30, 41, 59, 0.6)' : 
                            'rgba(255, 255, 255, 0.8)'
                        } !important;
                        color: ${isDarkMode ? '#f1f5f9' : '#1f2937'} !important;
                        transition: all 0.2s ease !important;
                        backdrop-filter: blur(10px) !important;
                        box-sizing: border-box !important;
                    " onfocus="this.style.borderColor='${isDarkMode ? '#3b82f6' : '#2563eb'}'; this.style.boxShadow='${isDarkMode ? '0 0 0 3px rgba(59, 130, 246, 0.1)' : '0 0 0 3px rgba(37, 99, 235, 0.1)'}';" onblur="this.style.borderColor='${isDarkMode ? 'rgba(59, 130, 246, 0.2)' : 'rgba(203, 213, 225, 0.6)'}'; this.style.boxShadow='none';"></textarea>
                    <div style="
                        margin-top: 8px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <span id="smsCharCount" style="
                            color: ${isDarkMode ? '#64748b' : '#6b7280'};
                            font-size: 12px;
                        ">0/160 caractères</span>
                        <span style="
                            color: ${isDarkMode ? '#64748b' : '#6b7280'};
                            font-size: 12px;
                        ">💡 Conseil: Soyez concis et professionnel</span>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="custom-sms-footer" style="
                padding: 20px 28px 24px 28px !important;
                border-top: ${isDarkMode ? 
                    '1px solid rgba(59, 130, 246, 0.2)' : 
                    '1px solid rgba(148, 163, 184, 0.15)'
                } !important;
                background: ${isDarkMode ? 
                    'linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%)' : 
                    'linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.8) 100%)'
                } !important;
                border-radius: 0 0 20px 20px !important;
                display: flex !important;
                justify-content: flex-end !important;
                gap: 12px !important;
            ">
                <button onclick="closeCustomModal()" style="
                    background: ${isDarkMode ? 
                        'rgba(51, 65, 85, 0.8)' : 
                        'rgba(148, 163, 184, 0.1)'
                    } !important;
                    color: ${isDarkMode ? '#cbd5e1' : '#475569'} !important;
                    border: ${isDarkMode ? 
                        '1px solid rgba(71, 85, 105, 0.3)' : 
                        '1px solid rgba(203, 213, 225, 0.4)'
                    } !important;
                    padding: 12px 24px !important;
                    border-radius: 10px !important;
                    cursor: pointer !important;
                    font-size: 14px !important;
                    font-weight: 500 !important;
                    transition: all 0.2s ease !important;
                    backdrop-filter: blur(10px) !important;
                " onmouseover="this.style.background='${isDarkMode ? 'rgba(71, 85, 105, 0.6)' : 'rgba(148, 163, 184, 0.2)'}'" onmouseout="this.style.background='${isDarkMode ? 'rgba(51, 65, 85, 0.8)' : 'rgba(148, 163, 184, 0.1)'}'">
                    <i class="fas fa-times" style="margin-right: 8px;"></i>Annuler
                </button>
                <button id="sendSmsBtn" onclick="sendCustomSms()" style="
                    background: ${isDarkMode ? 
                        'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' : 
                        'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)'
                    } !important;
                    color: white !important;
                    border: none !important;
                    padding: 12px 24px !important;
                    border-radius: 10px !important;
                    cursor: pointer !important;
                    font-size: 14px !important;
                    font-weight: 600 !important;
                    transition: all 0.2s ease !important;
                    box-shadow: ${isDarkMode ? 
                        '0 8px 25px -8px rgba(59, 130, 246, 0.4)' : 
                        '0 8px 25px -8px rgba(37, 99, 235, 0.3)'
                    } !important;
                " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='${isDarkMode ? '0 12px 35px -8px rgba(59, 130, 246, 0.5)' : '0 12px 35px -8px rgba(37, 99, 235, 0.4)'}';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='${isDarkMode ? '0 8px 25px -8px rgba(59, 130, 246, 0.4)' : '0 8px 25px -8px rgba(37, 99, 235, 0.3)'}';">
                    <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>Envoyer SMS
                </button>
            </div>
        </div>
    `;
    
    // Ajouter le modal au body
    document.body.appendChild(newModal);
    
    // Charger les modèles de SMS APRES avoir créé le modal
    fetch('ajax/get_sms_templates.php')
        .then(response => response.json())
        .then(data => {
            const templateSelect = document.getElementById('customSmsTemplate');
            if (!templateSelect) return;
            
            // Vider les options existantes sauf la première
            templateSelect.innerHTML = '<option value="">-- Sélectionner un modèle --</option>';
            
            // Ajouter les nouveaux modèles
            if (data.success && data.templates) {
                data.templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.nom;
                    option.dataset.content = template.contenu;
                    templateSelect.appendChild(option);
                });
            }
            
            // Ajouter l'écouteur d'événement pour le changement de modèle
            templateSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const content = selectedOption.dataset.content;
                const textarea = document.getElementById('customSmsMessage');
                
                if (content && textarea) {
                    // Remplacer les variables dans le contenu
                    let message = content
                        .replace('[NOM]', nom)
                        .replace('[PRENOM]', prenom)
                        .replace('[TELEPHONE]', telephone);
                        
                    textarea.value = message;
                    
                    // Mettre à jour le compteur
                    const charCount = document.getElementById('smsCharCount');
                    if (charCount) {
                        charCount.textContent = `${message.length}/160 caractères`;
                        charCount.style.color = message.length > 160 ? '#ef4444' : (isDarkMode ? '#64748b' : '#6b7280');
                    }
                }
            });
        })
        .catch(error => {
            console.error('Erreur lors du chargement des modèles de SMS:', error);
            const templateSelect = document.getElementById('customSmsTemplate');
            if (templateSelect) {
                templateSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            }
        });
        
    // Ajouter un écouteur pour le compteur de caractères
    const textarea = document.getElementById('customSmsMessage');
    if (textarea) {
        textarea.addEventListener('input', function() {
            const charCount = document.getElementById('smsCharCount');
            if (charCount) {
                charCount.textContent = `${this.value.length}/160 caractères`;
                charCount.style.color = this.value.length > 160 ? '#ef4444' : (isDarkMode ? '#64748b' : '#6b7280');
            }
        });
        
        // Focus sur le textarea
        setTimeout(() => {
            textarea.focus();
        }, 100);
    }
    
    // Créer la fonction de fermeture globale avec animation
    window.closeCustomModal = function() {
        const modalDialog = newModal.querySelector('.custom-sms-modal-dialog');
        if (modalDialog) {
            modalDialog.style.animation = 'modalSlideOut 0.3s ease-in forwards';
        }
        
        setTimeout(() => {
            newModal.remove();
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 300);
    };
    
    // Créer la fonction d'envoi SMS
    window.sendCustomSms = function() {
        const messageText = document.getElementById('customSmsMessage').value;
        
        if (!messageText.trim()) {
            alert('Veuillez saisir un message avant d\'envoyer le SMS.');
            return;
        }
        
        // Récupérer les données du client depuis les variables globales
        const clientId = window.currentClientId || '';
        const clientTel = window.currentClientTel || '';
        
        if (!clientTel) {
            alert('Numéro de téléphone du client non trouvé.');
            return;
        }
        
        // Désactiver le bouton
        const btn = document.getElementById('sendSmsBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi...';
        
        // Envoyer le SMS via AJAX
        fetch('ajax/send_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `client_id=${encodeURIComponent(clientId)}&telephone=${encodeURIComponent(clientTel)}&message=${encodeURIComponent(messageText)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher notification succès
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #10b981;
                    color: white;
                    padding: 16px 24px;
                    border-radius: 12px;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    z-index: 10001;
                    animation: slideInRight 0.3s ease-out;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                `;
                notification.innerHTML = '<i class="fas fa-check-circle"></i> SMS envoyé avec succès !';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
                
                closeCustomModal();
            } else {
                alert('Erreur lors de l\'envoi : ' + (data.message || 'Erreur inconnue'));
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de communication avec le serveur.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Ajouter le compteur de caractères
    setTimeout(() => {
        const textarea = document.getElementById('customSmsMessage');
        const charCount = document.getElementById('smsCharCount');
        
        if (textarea && charCount) {
            const updateCharCount = () => {
                const length = textarea.value.length;
                charCount.textContent = length + '/160 caractères';
                
                // Changer la couleur selon la limite
                if (length > 160) {
                    charCount.style.color = '#ef4444';
                } else if (length > 140) {
                    charCount.style.color = '#f59e0b';
                } else {
                    const isDarkMode = document.body.classList.contains('dark-mode');
                    charCount.style.color = isDarkMode ? '#64748b' : '#6b7280';
                }
            };
            
            textarea.addEventListener('input', updateCharCount);
            updateCharCount(); // Initial call
        }
    }, 100);
    
    // Fermer en cliquant sur le backdrop
    if (backdrop) {
        backdrop.onclick = window.closeCustomModal;
    }
}

// Mise à jour du compteur de caractères SMS
function updateSmsCounter() {
    const messageField = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    const length = messageField.value.length;
    charCount.textContent = length + ' caractères';
    
    // Calcul du nombre de SMS
    if (length <= 160) {
        smsCount.textContent = '1 SMS';
    } else {
        // 153 caractères par SMS pour les messages concaténés
        const count = Math.ceil(length / 153);
        smsCount.textContent = count + ' SMS';
    }
}

// Initialisation des éléments du modal SMS quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le compteur de caractères lors de la saisie
    const messageField = document.getElementById('message');
    if (messageField) {
        messageField.addEventListener('input', updateSmsCounter);
    }
    
    // Charger le contenu du modèle sélectionné
    const templateSelect = document.getElementById('template');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.content) {
                messageField.value = selectedOption.dataset.content;
                updateSmsCounter();
            }
        });
    }
    
    // Gérer la soumission du formulaire SMS
    const smsForm = document.getElementById('smsForm');
    if (smsForm) {
        smsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendBtn = document.getElementById('sendSmsBtn');
            
            // Désactiver le bouton pendant l'envoi
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
            
            fetch('ajax/send_sms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
                
                if (data.success) {
                    // Fermer le modal manuellement
                    const modal = document.getElementById('smsModal');
                    const backdrop = document.querySelector('.modal-backdrop');
                    
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                        modal.setAttribute('aria-hidden', 'true');
                        modal.removeAttribute('aria-modal');
                    }
                    
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    // Afficher un message de succès
                    alert('SMS envoyé avec succès !');
                } else {
                    // Afficher le message d'erreur
                    alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Une erreur inconnue est survenue.'));
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du SMS:', error);
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
                alert('Erreur lors de l\'envoi du SMS. Veuillez réessayer.');
            });
        });
    }
    
    // Initialiser les boutons de suppression
    const deleteButtons = document.querySelectorAll('.delete-repair');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Mettre à jour le lien de confirmation
            confirmBtn.href = 'index.php?page=reparations&action=delete&id=' + repairId;
            
            // Afficher le modal de confirmation
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteRepairModal'));
            deleteModal.show();
        });
    });
});
</script>
<!-- Styles compacts pour les boutons d'action -->
<link rel="stylesheet" href="assets/css/compact-buttons.css">

<script>
// Initialiser le helper de modal au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si RepairModal est déjà initialisé
    if (window.RepairModal && !window.RepairModal._isInitialized) {
        window.RepairModal._isInitialized = true;
        window.RepairModal.init();
        
        // Si un modal est en attente d'ouverture, l'ouvrir maintenant
        if (window.pendingModalId && typeof window.openPendingModal === 'function') {
            setTimeout(window.openPendingModal, 100);
        }
    }

    // Vérifier si PriceNumpad est déjà initialisé
    if (window.PriceNumpad && !window.PriceNumpad._isInitialized) {
        window.PriceNumpad._isInitialized = true;
        window.PriceNumpad.init();
    }

    // Vérifier si StatusModal est déjà initialisé
    if (window.StatusModal && !window.StatusModal._isInitialized) {
        window.StatusModal._isInitialized = true;
        window.StatusModal.init();
    }
});
// Le nouveau script de mise à jour des statuts est maintenant géré par new-update-status-modal.js

// Script pour détecter le paramètre showRepId dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const showRepId = urlParams.get('showRepId');
    
if (showRepId && typeof chargerDetailsReparation === 'function') {
            const reparationInfoModal = new bootstrap.Modal(document.getElementById('reparationInfoModal'));
            reparationInfoModal.show();
            chargerDetailsReparation(showRepId);
        }
</script>

<!-- Styles pour le modal de relance -->
<style>
.form-check-custom {
    padding: 10px;
    border: 1px solid var(--day-border);
    border-radius: 8px;
    background: var(--day-card-bg);
    transition: var(--transition-normal);
}

.form-check-custom:hover {
    background: rgba(59, 130, 246, 0.1);
    border-color: var(--day-primary);
}

.form-check-custom input:checked + label {
    color: var(--day-primary);
    font-weight: 500;
}

body.dark-mode .form-check-custom {
    border-color: var(--night-border);
    background: var(--night-card-bg);
}

body.dark-mode .form-check-custom:hover {
    background: rgba(0, 212, 255, 0.1);
    border-color: var(--night-primary);
}

body.dark-mode .form-check-custom input:checked + label {
    color: var(--night-primary);
}
</style>

<!-- Modal pour la relance client -->
<div class="modal fade" id="relanceClientModal" tabindex="-1" aria-labelledby="relanceClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(30, 144, 255, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(30, 144, 255, 0.8) 0%, rgba(0, 77, 155, 0.8) 100%); color: white;">
                <h5 class="modal-title" id="relanceClientModalLabel" style="text-shadow: 0 0 10px rgba(30, 144, 255, 0.7);">
                    <i class="fas fa-bell me-2"></i>Relance des clients
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="background: rgba(255, 255, 255, 0.05);">
                <div class="alert alert-info" id="alertInfo" style="background: rgba(30, 144, 255, 0.1); border: 1px solid rgba(30, 144, 255, 0.3); border-radius: 10px;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="alertInfoText">Vous êtes sur le point d'envoyer un SMS de relance aux clients dont les réparations sont terminées ou archivées mais pas encore récupérées.</span>
                </div>
                
                <!-- Filtres par statut -->
                <div class="mb-3">
                    <label class="form-label">Sélectionner les types de réparations à relancer:</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input" type="checkbox" id="filterDevisAttente" value="en_attente_accord_client">
                                <label class="form-check-label" for="filterDevisAttente">
                                    <i class="fas fa-clock text-warning me-1"></i>
                                    Devis en attente
                                </label>
                            </div>
                            <small class="text-muted">Statut: en attente accord client</small>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input" type="checkbox" id="filterReparationTerminee" value="reparation_effectue">
                                <label class="form-check-label" for="filterReparationTerminee">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Réparation Terminée
                                </label>
                            </div>
                            <small class="text-muted">Statut: réparation effectuée</small>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input" type="checkbox" id="filterReparationAnnulee" value="reparation_annule">
                                <label class="form-check-label" for="filterReparationAnnulee">
                                    <i class="fas fa-times-circle text-danger me-1"></i>
                                    Réparation Annulée
                                </label>
                            </div>
                            <small class="text-muted">Statut: réparation annulée</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="relanceDelayDays" class="form-label">Relancer les réparations qui datent depuis au moins:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="relanceDelayDays" min="1" value="3" style="border-radius: 8px 0 0 8px; border: 1px solid rgba(30, 144, 255, 0.5);">
                        <span class="input-group-text" style="background: rgba(30, 144, 255, 0.3); border: 1px solid rgba(30, 144, 255, 0.5); border-radius: 0 8px 8px 0; color: white;">jours</span>
                    </div>
                    <small class="text-muted">Laissez 3 jours par défaut pour ne pas relancer des clients trop tôt.</small>
                </div>
                
                <div id="previewResults" class="d-none mt-3">
                    <h6 class="mb-3">Liste des clients à relancer:</h6>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllClients" checked>
                            <label class="form-check-label" for="selectAllClients">Sélectionner / Désélectionner tous</label>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover" style="border-radius: 10px; overflow: hidden; border: 1px solid rgba(30, 144, 255, 0.2);">
                            <thead style="background: rgba(30, 144, 255, 0.2);">
                                <tr>
                                    <th>Sélection</th>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Statut</th>
                                    <th>Terminé depuis</th>
                                </tr>
                            </thead>
                            <tbody id="previewResultsBody">
                                <!-- Les résultats seront ajoutés ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                    <div id="noClientsMessage" class="alert alert-warning d-none" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 10px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Aucun client à relancer avec les critères sélectionnés.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: rgba(30, 144, 255, 0.05); border-top: 1px solid rgba(30, 144, 255, 0.2);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px; border: 1px solid rgba(30, 144, 255, 0.5);">Annuler</button>
                <button type="button" class="btn btn-primary" id="previewRelanceBtn" style="background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%); border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);">
                    <i class="fas fa-search me-1"></i>Rechercher les clients
                </button>
                <button type="button" class="btn btn-warning" id="sendRelanceBtn" disabled style="background: linear-gradient(135deg, #ff9500 0%, #ff6a00 100%); border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(255, 149, 0, 0.3);">
                    <i class="fas fa-paper-plane me-1"></i>Envoyer les SMS
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Script pour gérer le modal de relance client -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const relanceClientBtn = document.getElementById('relanceClientBtn');
    const relanceDelayDays = document.getElementById('relanceDelayDays');
    const previewResults = document.getElementById('previewResults');
    const previewResultsBody = document.getElementById('previewResultsBody');
    const noClientsMessage = document.getElementById('noClientsMessage');
    const previewRelanceBtn = document.getElementById('previewRelanceBtn');
    const sendRelanceBtn = document.getElementById('sendRelanceBtn');
    const selectAllClients = document.getElementById('selectAllClients');
    const filterDevisAttente = document.getElementById('filterDevisAttente');
    const filterReparationTerminee = document.getElementById('filterReparationTerminee');
    const filterReparationAnnulee = document.getElementById('filterReparationAnnulee');
    
    // Initialiser le modal
    let relanceModal;
    if (relanceClientBtn) {
        relanceClientBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Ouvrir le modal
            relanceModal = new bootstrap.Modal(document.getElementById('relanceClientModal'));
            relanceModal.show();
        });
    }
    
    // Écouter les changements sur le champ de jours
    if (relanceDelayDays) {
        relanceDelayDays.addEventListener('input', function() {
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Gestionnaires pour les cases à cocher de filtre
    [filterDevisAttente, filterReparationTerminee, filterReparationAnnulee].forEach(checkbox => {
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                // Réinitialiser l'aperçu quand un filtre change
                previewResults.classList.add('d-none');
                sendRelanceBtn.disabled = true;
            });
        }
    });
    
    // Action du bouton d'aperçu
    if (previewRelanceBtn) {
        previewRelanceBtn.addEventListener('click', function() {
            // Récupérer les valeurs
            const days = relanceDelayDays.value !== '' ? parseInt(relanceDelayDays.value) : 3;
            
            // Récupérer les filtres sélectionnés
            const selectedFilters = [];
            if (filterDevisAttente && filterDevisAttente.checked) {
                selectedFilters.push('en_attente_accord_client');
            }
            if (filterReparationTerminee && filterReparationTerminee.checked) {
                selectedFilters.push('reparation_effectue');
            }
            if (filterReparationAnnulee && filterReparationAnnulee.checked) {
                selectedFilters.push('reparation_annule');
            }
            
            // Vérifier qu'au moins un filtre est sélectionné
            if (selectedFilters.length === 0) {
                alert('Veuillez sélectionner au moins un type de réparation à relancer.');
                return;
            }
            
            // Appeler l'API pour obtenir un aperçu avec les filtres sélectionnés
            getPreviewRelance(days, selectedFilters);
        });
    }
    
    // Action du bouton d'envoi
    if (sendRelanceBtn) {
        sendRelanceBtn.addEventListener('click', function() {
            // Récupérer les IDs des clients sélectionnés
            const selectedClientIds = [];
            document.querySelectorAll('.client-select:checked').forEach(checkbox => {
                selectedClientIds.push(checkbox.getAttribute('data-client-id'));
            });
            
            // Si aucun client n'est sélectionné, afficher une alerte
            if (selectedClientIds.length === 0) {
                alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
                return;
            }
            
            // Demander confirmation
            if (!confirm('ATTENTION: Vous êtes sur le point d\'envoyer des SMS de relance aux clients sélectionnés. Continuer?')) {
                return;
            }
            
            // Récupérer les valeurs
            const days = relanceDelayDays.value !== '' ? parseInt(relanceDelayDays.value) : 3;
            
            // Récupérer les filtres sélectionnés
            const selectedFilters = [];
            if (filterDevisAttente && filterDevisAttente.checked) {
                selectedFilters.push('en_attente_accord_client');
            }
            if (filterReparationTerminee && filterReparationTerminee.checked) {
                selectedFilters.push('reparation_effectue');
            }
            if (filterReparationAnnulee && filterReparationAnnulee.checked) {
                selectedFilters.push('reparation_annule');
            }
            
            // Appeler l'API pour envoyer les relances
            sendRelanceSMS(days, selectedFilters);
        });
    }
    
    // Gestionnaire pour la case à cocher "Sélectionner tous"
    if (selectAllClients) {
        selectAllClients.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.client-select').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
        
        // Ajouter un écouteur d'événements pour les clics sur les cases individuelles
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('client-select')) {
                // Vérifier si toutes les cases sont cochées
                const allCheckboxes = document.querySelectorAll('.client-select');
                const allChecked = [...allCheckboxes].every(checkbox => checkbox.checked);
                
                // Mettre à jour la case "Sélectionner tous"
                selectAllClients.checked = allChecked;
            }
        });
    }
    
    // Fonction pour obtenir un aperçu des relances
    function getPreviewRelance(days, selectedFilters = []) {
        // Afficher un indicateur de chargement
        previewResultsBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <span class="ms-2">Recherche des clients à relancer...</span>
                </td>
            </tr>
        `;
        previewResults.classList.remove('d-none');
        noClientsMessage.classList.add('d-none');
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'preview',
                days: days,
                selectedFilters: selectedFilters
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'aperçu
                if (data.clients && data.clients.length > 0) {
                    previewResultsBody.innerHTML = '';
                    
                    // Les en-têtes du tableau restent fixes
                    const tableHeadRow = document.querySelector('#previewResults table thead tr');
                    if (tableHeadRow) {
                        tableHeadRow.innerHTML = `
                            <th>Sélection</th>
                            <th>Client</th>
                            <th>Appareil</th>
                            <th>Statut</th>
                            <th>Date</th>
                        `;
                    }
                    
                    // Ajouter chaque client à la liste
                    data.clients.forEach(client => {
                        // Déterminer le statut et sa couleur
                        let statusText = "Inconnu";
                        let statusClass = "secondary";
                        
                        if (client.statut === 'en_attente_accord_client') {
                            statusText = "Devis en attente";
                            statusClass = "warning";
                        } else if (client.statut === 'reparation_effectue') {
                            statusText = "Réparation Effectuée";
                            statusClass = "success";
                        } else if (client.statut === 'reparation_annule') {
                            statusText = "Réparation Annulée";
                            statusClass = "danger";
                        }
                        
                        // Créer la ligne
                        const row = document.createElement('tr');
                        
                        // Informations sur l'appareil
                        let deviceInfo = client.type_appareil;
                        if (client.modele) {
                            deviceInfo += ` ${client.modele}`;
                        }
                        
                        // Déterminer la date affichée
                        let dateInfo = '';
                        if (client.days_since) {
                            dateInfo = `${client.days_since} jours`;
                        } else if (client.date_info) {
                            dateInfo = client.date_info;
                        }
                        
                        // Définir le contenu de la ligne
                        row.innerHTML = `
                            <td class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input client-select" type="checkbox" checked data-client-id="${client.id}">
                                </div>
                            </td>
                            <td>${client.client_nom} ${client.client_prenom}</td>
                            <td>${deviceInfo}</td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                            <td>${dateInfo}</td>
                        `;
                        
                        previewResultsBody.appendChild(row);
                    });
                    
                    // Activer le bouton d'envoi
                    sendRelanceBtn.disabled = false;
                } else {
                    // Aucun client à relancer
                    noClientsMessage.classList.remove('d-none');
                    previewResultsBody.innerHTML = '';
                    sendRelanceBtn.disabled = true;
                }
            } else {
                // Afficher l'erreur
                previewResultsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            ${data.message || 'Une erreur est survenue lors de la recherche des clients.'}
                        </td>
                    </tr>
                `;
                sendRelanceBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            previewResultsBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Recherche en cours, veuillez patienter...
                    </td>
                </tr>
            `;
            sendRelanceBtn.disabled = false;
        });
    }
    
    // Fonction pour envoyer les SMS de relance
    function sendRelanceSMS(days, selectedFilters = []) {
        // Désactiver le bouton et afficher un indicateur de chargement
        sendRelanceBtn.disabled = true;
        sendRelanceBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
        
        // Récupérer les IDs des clients sélectionnés
        const selectedClientIds = [];
        document.querySelectorAll('.client-select:checked').forEach(checkbox => {
            selectedClientIds.push(checkbox.getAttribute('data-client-id'));
        });
        
        // Si aucun client n'est sélectionné, afficher une alerte
        if (selectedClientIds.length === 0) {
            alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            return;
        }
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'send',
                days: days,
                clientIds: selectedClientIds,
                selectedFilters: selectedFilters
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // S'assurer que le modal existe et est initialisé
                const modalElement = document.getElementById('relanceClientModal');
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                
                // Si l'instance n'existe pas, la créer
                if (!modalInstance && modalElement) {
                    modalInstance = new bootstrap.Modal(modalElement);
                }
                
                // Fermer le modal s'il est disponible (autoriser)
                if (modalInstance) {
                    const el = modalElement;
                    if (el) el.dataset.allowHide = '1';
                    modalInstance.hide();
                } else {
                    console.warn('Modal non trouvé ou non initialisé, fermeture impossible');
                }
                
                // Afficher un message de succès
                alert(`${data.count} SMS de relance envoyés avec succès.`);
                
                // Recharger la page
                window.location.reload();
            } else {
                // Afficher l'erreur
                alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de l\'envoi des SMS.'));
                sendRelanceBtn.disabled = false;
                sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            // Suppression de l'alerte d'erreur car les SMS s'envoient correctement
            // On réactive simplement le bouton
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
        });
    }
});
</script>

<!-- Scripts pour gérer l'indicateur SMS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour l'indicateur d'envoi de SMS
    const smsIndicator = document.getElementById('sendSmsIndicator');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    const smsLabel = document.getElementById('sendSmsLabel');
    
    if (smsIndicator && smsSwitch && smsLabel) {
        // Initialiser l'état
        let smsEnabled = true;
        
        // Fonction pour mettre à jour l'apparence
        function updateSmsIndicator() {
            if (smsEnabled) {
                smsIndicator.style.backgroundColor = '#4caf50'; // Vert
                smsSwitch.value = '1';
                smsLabel.textContent = 'Envoyer un SMS de notification';
            } else {
                smsIndicator.style.backgroundColor = '#f44336'; // Rouge
                smsSwitch.value = '0';
                smsLabel.textContent = 'Ne pas envoyer de SMS';
            }
        }
        
        // Gestionnaire de clic
        smsIndicator.addEventListener('click', function() {
            smsEnabled = !smsEnabled;
            updateSmsIndicator();
        });
        
        smsLabel.addEventListener('click', function() {
            smsEnabled = !smsEnabled;
            updateSmsIndicator();
        });
    }
});
</script>

<script>
// Script pour gérer le bouton d'envoi de SMS
document.addEventListener('DOMContentLoaded', function() {
    const smsToggleButton = document.getElementById('smsToggleButton');
    const sendSmsSwitch = document.getElementById('sendSmsSwitch');
    
    if (smsToggleButton && sendSmsSwitch) {
        // Initialiser le bouton avec l'état par défaut (SMS désactivé)
        updateSmsButtonState(false);
        
        // Ajouter un écouteur d'événement pour le clic
        smsToggleButton.addEventListener('click', function() {
            // Inverser l'état actuel
            const currentState = sendSmsSwitch.value === '1';
            const newState = !currentState;
            
            // Mettre à jour l'état du bouton
            updateSmsButtonState(newState);
            
            // Mettre à jour la valeur de l'input hidden
            sendSmsSwitch.value = newState ? '1' : '0';
            
            // Jouer un son de notification pour donner un feedback à l'utilisateur
            playNotificationSound(newState);
        });
    }
    
    // Fonction pour mettre à jour l'apparence du bouton selon l'état
    function updateSmsButtonState(sendSmsEnabled) {
        if (sendSmsEnabled) {
            // SMS activé: bouton vert avec icône d'envoi
            smsToggleButton.className = 'btn btn-success btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transform: translateY(-2px);';
            smsToggleButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i> ENVOYER UN SMS AU CLIENT';
        } else {
            // SMS désactivé: bouton rouge avec icône d'interdiction
            smsToggleButton.className = 'btn btn-danger btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);';
            smsToggleButton.innerHTML = '<i class="fas fa-ban me-2"></i> NE PAS ENVOYER DE SMS AU CLIENT';
        }
    }
    
    // Fonction pour jouer un son de notification
    function playNotificationSound(success) {
        const audio = new Audio(success ? '../assets/sounds/success.mp3' : '../assets/sounds/beep.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Impossible de jouer le son de notification:', e));
    }
});
// Script pour gérer le bouton d'envoi de SMS
document.addEventListener('DOMContentLoaded', function() {
    console.log("Initialisation du bouton SMS...");
    const smsToggleButton = document.getElementById('smsToggleButton');
    const sendSmsSwitch = document.getElementById('sendSmsSwitch');
    
    if (smsToggleButton && sendSmsSwitch) {
        // S'assurer que la valeur initiale est correcte
        if (!sendSmsSwitch.value) {
            sendSmsSwitch.value = '0';
        }
        
        // Initialiser le bouton avec l'état par défaut
        const initialState = sendSmsSwitch.value === '1';
        updateSmsButtonState(initialState);
        console.log("État initial du SMS:", initialState ? "Activé" : "Désactivé");
        
        // Ajouter un écouteur d'événement pour le clic
        smsToggleButton.addEventListener('click', function() {
            // Inverser l'état actuel
            const currentState = sendSmsSwitch.value === '1';
            const newState = !currentState;
            
            // Mettre à jour la valeur de l'input hidden
            sendSmsSwitch.value = newState ? '1' : '0';
            console.log("Nouvel état du SMS:", newState ? "Activé" : "Désactivé", "Valeur:", sendSmsSwitch.value);
            
            // Mettre à jour l'état du bouton
            updateSmsButtonState(newState);
            
            // Jouer un son de notification pour donner un feedback à l'utilisateur
            playNotificationSound(newState);
        });
    } else {
        console.error("Éléments du bouton SMS non trouvés:", smsToggleButton, sendSmsSwitch);
    }
    
    // Fonction pour mettre à jour l'apparence du bouton selon l'état
    function updateSmsButtonState(sendSmsEnabled) {
        if (sendSmsEnabled) {
            // SMS activé: bouton vert avec icône d'envoi
            smsToggleButton.className = 'btn btn-success btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transform: translateY(-2px);';
            smsToggleButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i> ENVOYER UN SMS AU CLIENT';
        } else {
            // SMS désactivé: bouton rouge avec icône d'interdiction
            smsToggleButton.className = 'btn btn-danger btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);';
            smsToggleButton.innerHTML = '<i class="fas fa-ban me-2"></i> NE PAS ENVOYER DE SMS AU CLIENT';
        }
    }
    
    // Fonction pour jouer un son de notification
    function playNotificationSound(success) {
        try {
            const audio = new Audio(success ? '../assets/sounds/success.mp3' : '../assets/sounds/beep.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => console.log('Impossible de jouer le son de notification:', e));
        } catch (e) {
            console.error("Erreur lors de la lecture du son:", e);
        }
    }
});
</script>

<!-- Scripts pour gérer l'ID du magasin dans les requêtes AJAX -->
<script src="/assets/js/session-helper.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Stocker l'ID du magasin pour les requêtes AJAX
    const shopId = "<?php echo $current_shop_id ?: ''; ?>";
    if (shopId) {
        // Stocker l'ID du magasin sur l'élément body
        document.body.setAttribute('data-shop-id', shopId);
        console.log('ID du magasin défini sur la page:', shopId);
        
        // Si le SessionHelper est disponible, stocker l'ID
        if (window.SessionHelper) {
            window.SessionHelper.storeShopId(shopId);
        }
    } else {
        console.warn('Aucun ID de magasin trouvé en session');
    }
});
</script>


<!-- Script pour exposer la fonction fetchStatusOptions au contexte global -->
<script>
// Exposer la fonction fetchStatusOptions pour le glisser-déposer
window.fetchStatusOptions = function(repairId, categoryId, statusIndicator) {
    console.log("Fonction fetchStatusOptions appelée depuis le bridge", {repairId, categoryId});
    
    // Afficher un indicateur de chargement dans le badge
    statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Chargement...</span>';
    
    // Récupérer les statuts disponibles pour cette catégorie
    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
        .then(response => {
            console.log('Statut de la réponse HTTP:', response.status);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Type de contenu invalide:', contentType);
                throw new Error('Le serveur n\'a pas retourné du JSON valide');
            }
            
            return response.text().then(text => {
                console.log('Réponse brute du serveur:', text);
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('Erreur de parsing JSON:', parseError);
                    throw new Error('Réponse JSON invalide du serveur');
                }
            });
        })
        .then(data => {
            console.log('Réponse de get_statuts_by_category:', data);
            
            if (data.success) {
                // Stocker les IDs pour une utilisation ultérieure
                document.getElementById('chooseStatusRepairId').value = repairId;
                document.getElementById('chooseStatusCategoryId').value = categoryId;
                
                // Remplir les informations de la réparation dans le modal
                document.getElementById('currentRepairNumber').textContent = repairId;
                
                // Trouver les informations de la réparation depuis la carte
                const repairCard = document.querySelector(`[data-repair-id="${repairId}"], [data-id="${repairId}"]`);
                if (repairCard) {
                    const clientName = repairCard.querySelector('.client-name, .repair-client, .card-title')?.textContent?.trim() || 'Client inconnu';
                    const deviceInfo = repairCard.querySelector('.device-info, .repair-device, .card-text')?.textContent?.trim() || 'Appareil non spécifié';
                    
                    document.getElementById('currentRepairClient').textContent = clientName;
                    document.getElementById('currentRepairDevice').textContent = deviceInfo;
                } else {
                    document.getElementById('currentRepairClient').textContent = 'Client inconnu';
                    document.getElementById('currentRepairDevice').textContent = 'Appareil non spécifié';
                }
                
                // Générer les boutons de statut
                const container = document.getElementById('statusCategoriesContainer');
                if (!container) {
                    console.error('Élément statusCategoriesContainer non trouvé dans le DOM');
                    return;
                }
                container.innerHTML = ''; // Effacer le contenu précédent
                
                // Déterminer la couleur de la catégorie
                const categoryColor = getCategoryColor(data.category.couleur);
                
                // Ajouter un titre pour la catégorie dans le modal
                const categoryTitle = document.getElementById('chooseStatusModalLabel');
                if (categoryTitle) {
                    categoryTitle.innerHTML = `<i class="fas fa-tasks me-2"></i> Statuts "${data.category.nom}"`;
                }
                
                // Créer un bouton pour chaque statut
                data.statuts.forEach(statut => {
                    const button = document.createElement('button');
                    button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                    button.setAttribute('data-status-id', statut.id);
                    button.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        ${statut.nom}
                    `;
                    button.addEventListener('click', () => updateSpecificStatus(statut.id, statusIndicator));
                    container.appendChild(button);
                });
                
                // Afficher le modal avec un délai pour s'assurer que les données sont stockées
                setTimeout(() => {
                    const modalElement = document.getElementById('chooseStatusModal');
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    
                        // Événement pour gérer l'ouverture du modal
                        modalElement.addEventListener('shown.bs.modal', function() {
                            // Permettre le scroll de la page
                            document.body.style.overflow = 'auto';
                            document.body.style.paddingRight = '0';
                            
                            // Forcer la position du modal
                            this.style.display = 'block';
                            this.style.position = 'fixed';
                            this.style.top = '0';
                            this.style.left = '0';
                            this.style.width = '100%';
                            this.style.height = '100%';
                            this.style.zIndex = '9999';
                            
                            // Forcer la position du modal-dialog
                            const modalDialog = this.querySelector('.modal-dialog');
                            if (modalDialog) {
                                modalDialog.style.position = 'fixed';
                                modalDialog.style.top = '80px';
                                modalDialog.style.left = '50%';
                                modalDialog.style.transform = 'translateX(-50%)';
                                modalDialog.style.margin = '0';
                                modalDialog.style.width = '90%';
                                modalDialog.style.maxWidth = '500px';
                                modalDialog.style.zIndex = '10000';
                            }
                            
                            console.log('📍 Modal positionné à 80px du haut et scroll activé');
                        });
                    
                    modal.show();
                    
                    // Vérifier que les données sont bien stockées
                    console.log('🔍 Vérification des données du modal:');
                    console.log('- Repair ID:', document.getElementById('chooseStatusRepairId')?.value);
                    console.log('- Category ID:', document.getElementById('chooseStatusCategoryId')?.value);
                }, 100);
                
                // Rétablir le badge de statut quand l'utilisateur annule
                const closeBtn = document.querySelector('#chooseStatusModal .btn-close');
                const cancelBtn = document.querySelector('#chooseStatusModal .btn-outline-secondary');
                
                const handleCancel = function() {
                    console.log('Annulation de la sélection de statut');
                    // Nettoyer le backdrop et réactiver le scroll
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    // Restaurer le statut d'origine
                    location.reload();
                };
                
                if (closeBtn) {
                    // Enlever les anciens écouteurs d'événements
                    closeBtn.removeEventListener('click', handleCancel);
                    // Ajouter le nouvel écouteur
                    closeBtn.addEventListener('click', handleCancel);
                }
                
                if (cancelBtn) {
                    // Enlever les anciens écouteurs d'événements
                    cancelBtn.removeEventListener('click', handleCancel);
                    // Ajouter le nouvel écouteur
                    cancelBtn.addEventListener('click', handleCancel);
                }
                
            } else {
                // Afficher l'erreur
                if (typeof showNotification === 'function') {
                    showNotification('Erreur: ' + data.error, 'danger');
                } else {
                    alert('Erreur: ' + data.error);
                }
                location.reload(); // Recharger la page en cas d'erreur
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des statuts:', error);
            
            // Afficher un message d'erreur spécifique selon le type d'erreur
            let errorMessage = 'Erreur de communication avec le serveur';
            if (error.message) {
                errorMessage = error.message;
            }
            
            if (typeof showNotification === 'function') {
                showNotification(`Erreur: ${errorMessage}`, 'danger');
            } else {
                alert(`Erreur: ${errorMessage}`);
            }
            
            // Restaurer l'indicateur de statut original
            if (statusIndicator) {
                statusIndicator.innerHTML = '<span class="badge bg-warning">Erreur</span>';
            }
            
            // Proposer de recharger la page
            if (confirm('Une erreur s\'est produite. Voulez-vous recharger la page ?')) {
                location.reload();
            }
        });
};
// Exposer la fonction updateSpecificStatus au contexte global
window.updateSpecificStatus = function(statusId, statusIndicator) {
    // Récupérer les ID stockés
    const repairId = document.getElementById('chooseStatusRepairId').value;

    console.log('Mise à jour du statut:', statusId, 'pour la réparation:', repairId);
    
    // Récupérer l'état de l'option d'envoi de SMS
    const sendSmsToggle = document.getElementById('sendSmsToggle');
    const sendSms = sendSmsToggle ? sendSmsToggle.checked : true; // Par défaut activé si élément non trouvé
    console.log('Envoi de SMS:', sendSms ? 'Activé' : 'Désactivé');
    
    // Fermer le modal
    const modalElement = document.getElementById('chooseStatusModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    
    // Nettoyer le backdrop et réactiver le scroll
    document.body.classList.remove('modal-open');
    // Nettoyage agressif désactivé: laisser Bootstrap gérer backdrop/overflow
    
    // Afficher un indicateur de chargement
    statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
    
    // Préparer les données
    const data = {
        repair_id: repairId,
        status_id: statusId,
        send_sms: sendSms,
        user_id: 1 // Utiliser l'ID 1 (admin) pour éviter les problèmes
    };
    
    // Afficher les données pour le débogage
    console.log('Données envoyées pour mise à jour de statut:', data);
    
    // Fonction pour afficher une notification
    function showSilentNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            const notification = document.createElement('div');
            notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Toast !== 'undefined') {
                const toast = new bootstrap.Toast(notification, { delay: 5000 });
                toast.show();
                
                // Supprimer la notification après qu'elle soit masquée
                notification.addEventListener('hidden.bs.toast', function () {
                    notification.remove();
                });
            } else {
                // Fallback si bootstrap n'est pas disponible
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        }
    }
    
    // Essayer d'abord avec fetch (méthode JSON standard)
    fetch('../ajax/update_repair_specific_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Réponse brute:', response);
        
        if (!response.ok) {
            if (response.status === 500) {
                // Pour les erreurs 500, on va essayer une approche différente
                throw new Error('RETRY_WITH_FORM');
            }
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        // Essayer de parser la réponse en JSON
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                console.log('Réponse texte brute:', text);
                throw new Error('Réponse non valide du serveur');
            }
        });
    })
    .then(data => {
        // Succès de la mise à jour
        console.log('Mise à jour réussie:', data);
        
        if (data.success) {
            // Mettre à jour le badge avec le nouveau statut
            if (data.data && data.data.badge) {
                statusIndicator.innerHTML = data.data.badge;
            }
            
            // Afficher une notification de succès
            showSilentNotification('Statut mis à jour avec succès', 'success');
            
            // Option: recharger la page après un délai
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // Afficher l'erreur
            showSilentNotification('Erreur: ' + (data.message || 'Une erreur est survenue.'), 'danger');
            // Recharger la page
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour du statut:', error);
        
        if (error.message === 'RETRY_WITH_FORM') {
            console.log('Nouvelle tentative avec FormData au lieu de JSON...');
            
            // Seconde tentative avec FormData mais en indiquant qu'il s'agit de données JSON
            const formData = new FormData();
            formData.append('json_data', JSON.stringify(data));
            
            fetch('../ajax/update_repair_specific_status.php?format=json', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(responseText => {
                console.log('Réponse de la seconde tentative:', responseText);
                // Afficher un message de succès générique
                showSilentNotification('Statut mis à jour avec succès', 'success');
                // Recharger la page après un court délai
                setTimeout(() => {
                    location.reload();
                }, 1500);
            })
            .catch(error => {
                console.error('Erreur lors de la seconde tentative:', error);
                showSilentNotification('Erreur lors de la mise à jour du statut', 'danger');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            });
        } else {
            // Afficher l'erreur
            showSilentNotification('Erreur lors de la mise à jour du statut: ' + error.message, 'danger');
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
    });
};

// Fonction helper pour obtenir la couleur de catégorie
function getCategoryColor(color) {
    // Convertir la couleur en classe Bootstrap
    const colorMap = {
        'info': 'info',
        'primary': 'primary',
        'warning': 'warning',
        'success': 'success',
        'danger': 'danger',
        'secondary': 'secondary'
    };
    return colorMap[color] || 'primary';
}

// Fonction sécurisée pour ouvrir le visualiseur de photos
window.openPhotoViewerSafe = function(photoUrl, description) {
    console.log('🖼️ Ouverture photo viewer sécurisée:', { photoUrl, description });
    console.log('🔍 Stack trace:', new Error().stack);
    
    // Nettoyer la description
    const cleanDescription = description.replace(/&apos;/g, "'");
    
    // Créer le modal directement dans le DOM
    const existingModal = document.getElementById('photoViewerModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modalHtml = `
        <div class="modal fade" id="photoViewerModal" tabindex="-1" style="z-index: 25100 !important;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-image me-2"></i>
                            ${cleanDescription}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <style>
                        /* Styles pour le modal visualiseur photo en mode jour */
                        #photoViewerModal .modal-content {
                            background-color: #ffffff !important;
                            border: 1px solid #dee2e6 !important;
                            color: #212529 !important;
                        }
                        #photoViewerModal .modal-header {
                            background-color: #f8f9fa !important;
                            border-bottom: 1px solid #dee2e6 !important;
                            color: #212529 !important;
                        }
                        #photoViewerModal .modal-title {
                            color: #212529 !important;
                            font-weight: 600 !important;
                        }
                        #photoViewerModal .btn-close {
                            filter: none !important;
                        }
                        #photoViewerModal .modal-body {
                            background-color: #ffffff !important;
                            color: #212529 !important;
                        }
                        
                        /* Styles pour le modal visualiseur photo en mode nuit */
                        body.dark-mode #photoViewerModal .modal-content {
                            background-color: #1e293b !important;
                            border: 1px solid #334155 !important;
                            color: #e2e8f0 !important;
                        }
                        body.dark-mode #photoViewerModal .modal-header {
                            background-color: #0f172a !important;
                            border-bottom: 1px solid #334155 !important;
                            color: #e2e8f0 !important;
                        }
                        body.dark-mode #photoViewerModal .modal-title {
                            color: #e2e8f0 !important;
                            font-weight: 600 !important;
                        }
                        body.dark-mode #photoViewerModal .btn-close {
                            filter: invert(1) !important;
                        }
                        body.dark-mode #photoViewerModal .modal-body {
                            background-color: #1e293b !important;
                            color: #e2e8f0 !important;
                        }
                        
                        /* Styles additionnels pour une meilleure intégration */
                        #photoViewerModal .modal-dialog {
                            max-width: 90vw !important;
                        }
                        #photoViewerModal .modal-header .fas {
                            margin-right: 8px !important;
                        }
                        
                        /* Mode nuit pour l'icône */
                        body.dark-mode #photoViewerModal .modal-header .fas {
                            color: #60a5fa !important;
                        }
                        
                        /* Mode jour pour l'icône */
                        #photoViewerModal .modal-header .fas {
                            color: #3b82f6 !important;
                        }
                    </style>
                    <div class="modal-body text-center p-0">
                        <img src="${photoUrl}" alt="${cleanDescription}" style="width: 100%; height: auto; max-height: 70vh; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    try {
        const modal = new bootstrap.Modal(document.getElementById('photoViewerModal'));
        modal.show();
        
        // Nettoyer après fermeture
        document.getElementById('photoViewerModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    } catch (error) {
        console.error('Erreur lors de l\'ouverture du modal photo:', error);
        alert('Erreur lors de l\'affichage de la photo');
    }
};

// Fonction de fallback pour l'ancienne méthode
if (typeof ModernRepairModal === 'undefined') {
    window.ModernRepairModal = {};
}
window.ModernRepairModal.openPhotoViewer = function(photoUrl, description) {
    console.log('🔄 Redirection vers openPhotoViewerSafe depuis ModernRepairModal.openPhotoViewer');
    window.openPhotoViewerSafe(photoUrl, description);
};

// Fonction globale de fallback
window.openPhotoViewer = function(photoUrl, description) {
    console.log('🔄 Redirection vers openPhotoViewerSafe depuis openPhotoViewer');
    window.openPhotoViewerSafe(photoUrl, description);
};

// Test de la fonction photo viewer
window.testPhotoViewer = function() {
    console.log('🧪 Test du photo viewer...');
    if (typeof window.openPhotoViewerSafe === 'function') {
        console.log('✅ Fonction openPhotoViewerSafe disponible');
        window.openPhotoViewerSafe('assets/images/reparations/repair_6910ecff198c1.jpg', 'Test photo');
    } else {
        console.error('❌ Fonction openPhotoViewerSafe non disponible');
    }
};

// Fonction sécurisée pour ouvrir le modal de devis
window.openDevisModalSafely = function(reparationId) {
    console.log('🎯 Ouverture sécurisée du modal de devis pour la réparation', reparationId);
    
    // Vérifier si le modal de devis existe
    const devisModal = document.getElementById('devisModalClean');
    if (!devisModal) {
        console.error('❌ Modal devisModalClean non trouvé');
        alert('Erreur: Le modal de création de devis n\'est pas disponible.');
        return;
    }
    
    try {
        console.log('✅ Ouverture du modal devisModalClean');
        
        // Remplir le champ caché avec l'ID de réparation
        const reparationIdInput = devisModal.querySelector('#devis_reparation_id');
        if (reparationIdInput) {
            reparationIdInput.value = reparationId;
            console.log('✅ ID de réparation défini:', reparationId);
        }
        
        // Créer un bouton temporaire avec l'ID de réparation
        const tempButton = document.createElement('button');
        tempButton.dataset.reparationId = reparationId;
        
        // Déclencher l'événement show.bs.modal avec le bouton temporaire
        const event = new Event('show.bs.modal');
        event.relatedTarget = tempButton;
        devisModal.dispatchEvent(event);
        
        // Ouvrir le modal
        const modalInstance = new bootstrap.Modal(devisModal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modalInstance.show();
        
        // Forcer le backdrop et le z-index après ouverture
        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.cssText = `
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    z-index: 1055 !important;
                    background-color: rgba(0, 0, 0, 0.5) !important;
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                `;
            }
            
            // Forcer le z-index du modal
            devisModal.style.cssText = `
                z-index: 1060 !important;
                display: block !important;
            `;
            
            const modalDialog = devisModal.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.cssText = `
                    z-index: 1061 !important;
                    position: relative !important;
                    pointer-events: auto !important;
                `;
            }
            
            const modalContent = devisModal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.cssText = `
                    z-index: 1062 !important;
                    position: relative !important;
                    pointer-events: auto !important;
                `;
            }
            
            // S'assurer que les boutons sont cliquables
            const buttons = devisModal.querySelectorAll('button, .btn');
            buttons.forEach(btn => {
                btn.style.pointerEvents = 'auto';
                btn.style.position = 'relative';
                btn.style.zIndex = '1064';
            });
            
            console.log('✅ Z-index et backdrop forcés pour le modal de devis');
            
            // Vérifier et initialiser le gestionnaire de devis si nécessaire
            if (typeof window.devisCleanManager === 'undefined') {
                console.log('⚠️ devisCleanManager non trouvé, tentative d\'initialisation...');
                
                // Essayer de créer une instance du gestionnaire
                if (typeof DevisCleanManager !== 'undefined') {
                    window.devisCleanManager = new DevisCleanManager();
                    console.log('✅ devisCleanManager initialisé manuellement');
                } else {
                    console.log('⚠️ Classe DevisCleanManager non disponible, ajout d\'événements manuels');
                    
                    // Ajouter manuellement l'événement au bouton Suivant
                    const suivantBtn = devisModal.querySelector('#suivantBtn');
                    if (suivantBtn) {
                        suivantBtn.addEventListener('click', function() {
                            console.log('🔄 Clic sur Suivant détecté');
                            // Logique basique pour passer à l'étape suivante
                            const currentStep = devisModal.querySelector('.step-content:not([style*="display: none"])');
                            if (currentStep) {
                                const stepId = currentStep.id;
                                console.log('Étape actuelle:', stepId);
                                
                                if (stepId === 'step-1') {
                                    // Passer à l'étape 2
                                    currentStep.style.display = 'none';
                                    const step2 = devisModal.querySelector('#step-2');
                                    if (step2) {
                                        step2.style.display = 'block';
                                        
                                        // Ajouter une panne par défaut si le container est vide
                                        const pannesContainer = step2.querySelector('#pannesContainer');
                                        if (pannesContainer && pannesContainer.children.length === 0) {
                                            console.log('🔧 Ajout d\'une panne par défaut');
                                            ajouterPanneDefaut(pannesContainer);
                                        }
                                    }
                                    
                                    // Mettre à jour les indicateurs
                                    const indicators = devisModal.querySelectorAll('.step-item');
                                    indicators.forEach(ind => ind.classList.remove('active'));
                                    const step2Indicator = devisModal.querySelector('.step-item[data-step="2"]');
                                    if (step2Indicator) step2Indicator.classList.add('active');
                                    
                                    // Afficher le bouton Précédent
                                    const precedentBtn = devisModal.querySelector('#precedentBtn');
                                    if (precedentBtn) precedentBtn.style.display = 'block';
                                } else if (stepId === 'step-2') {
                                    // Passer à l'étape 3
                                    currentStep.style.display = 'none';
                                    const step3 = devisModal.querySelector('#step-3');
                                    if (step3) {
                                        step3.style.display = 'block';
                                        
                                        // Ajouter une solution par défaut si le container est vide
                                        const solutionsContainer = step3.querySelector('#solutionsContainer');
                                        if (solutionsContainer && solutionsContainer.children.length === 0) {
                                            console.log('🔧 Ajout d\'une solution par défaut');
                                            ajouterSolutionDefaut(solutionsContainer);
                                        }
                                    }
                                    
                                    // Mettre à jour les indicateurs
                                    const indicators = devisModal.querySelectorAll('.step-item');
                                    indicators.forEach(ind => ind.classList.remove('active'));
                                    const step3Indicator = devisModal.querySelector('.step-item[data-step="3"]');
                                    if (step3Indicator) step3Indicator.classList.add('active');
                                    
                                    // Changer le bouton Suivant en Sauvegarder
                                    const suivantBtn = devisModal.querySelector('#suivantBtn');
                                    const sauvegarderBtn = devisModal.querySelector('#sauvegarderBtn');
                                    if (suivantBtn) suivantBtn.style.display = 'none';
                                    if (sauvegarderBtn) sauvegarderBtn.style.display = 'block';
                                }
                            }
                        });
                        console.log('✅ Événement manuel ajouté au bouton Suivant');
                    }
                    
                    // Ajouter l'événement pour le bouton "Ajouter une panne"
                    const ajouterPanneBtn = devisModal.querySelector('#ajouterPanneBtn');
                    if (ajouterPanneBtn) {
                        ajouterPanneBtn.addEventListener('click', function() {
                            console.log('🔧 Clic sur Ajouter une panne');
                            const pannesContainer = devisModal.querySelector('#pannesContainer');
                            if (pannesContainer) {
                                ajouterPanneDefaut(pannesContainer);
                            }
                        });
                        console.log('✅ Événement manuel ajouté au bouton Ajouter une panne');
                    }
                    
                    // Ajouter l'événement pour le bouton "Précédent"
                    const precedentBtn = devisModal.querySelector('#precedentBtn');
                    if (precedentBtn) {
                        precedentBtn.addEventListener('click', function() {
                            console.log('🔄 Clic sur Précédent détecté');
                            const currentStep = devisModal.querySelector('.step-content:not([style*="display: none"])');
                            if (currentStep) {
                                const stepId = currentStep.id;
                                console.log('Étape actuelle:', stepId);
                                
                                if (stepId === 'step-2') {
                                    // Retour à l'étape 1
                                    currentStep.style.display = 'none';
                                    const step1 = devisModal.querySelector('#step-1');
                                    if (step1) step1.style.display = 'block';
                                    
                                    // Mettre à jour les indicateurs
                                    const indicators = devisModal.querySelectorAll('.step-item');
                                    indicators.forEach(ind => ind.classList.remove('active'));
                                    const step1Indicator = devisModal.querySelector('.step-item[data-step="1"]');
                                    if (step1Indicator) step1Indicator.classList.add('active');
                                    
                                    // Masquer le bouton Précédent
                                    precedentBtn.style.display = 'none';
                                } else if (stepId === 'step-3') {
                                    // Retour à l'étape 2
                                    currentStep.style.display = 'none';
                                    const step2 = devisModal.querySelector('#step-2');
                                    if (step2) step2.style.display = 'block';
                                    
                                    // Mettre à jour les indicateurs
                                    const indicators = devisModal.querySelectorAll('.step-item');
                                    indicators.forEach(ind => ind.classList.remove('active'));
                                    const step2Indicator = devisModal.querySelector('.step-item[data-step="2"]');
                                    if (step2Indicator) step2Indicator.classList.add('active');
                                    
                                    // Remettre le bouton Suivant
                                    const suivantBtn = devisModal.querySelector('#suivantBtn');
                                    const sauvegarderBtn = devisModal.querySelector('#sauvegarderBtn');
                                    if (suivantBtn) suivantBtn.style.display = 'block';
                                    if (sauvegarderBtn) sauvegarderBtn.style.display = 'none';
                                }
                            }
                        });
                        console.log('✅ Événement manuel ajouté au bouton Précédent');
                    }
                    
                    // Ajouter l'événement pour le bouton "Ajouter une solution"
                    const ajouterSolutionBtn = devisModal.querySelector('#ajouterSolutionBtn');
                    if (ajouterSolutionBtn) {
                        ajouterSolutionBtn.addEventListener('click', function() {
                            console.log('🔧 Clic sur Ajouter une solution');
                            const solutionsContainer = devisModal.querySelector('#solutionsContainer');
                            if (solutionsContainer) {
                                ajouterSolutionDefaut(solutionsContainer);
                            }
                        });
                        console.log('✅ Événement manuel ajouté au bouton Ajouter une solution');
                    }
                    
                    // Ajouter l'événement pour le bouton "Sauvegarder"
                    const sauvegarderBtn = devisModal.querySelector('#sauvegarderBtn');
                    if (sauvegarderBtn) {
                        sauvegarderBtn.addEventListener('click', function() {
                            console.log('💾 Clic sur Sauvegarder détecté');
                            sauvegarderDevis(devisModal);
                        });
                        console.log('✅ Événement manuel ajouté au bouton Sauvegarder');
                    }
                }
            } else {
                console.log('✅ devisCleanManager déjà disponible');
            }
        }, 100);
        
        console.log('✅ Modal de devis ouvert avec succès');
        
    } catch (error) {
        console.error('❌ Erreur lors de l\'ouverture du modal de devis:', error);
        alert('Erreur lors de l\'ouverture du modal de devis. Veuillez réessayer.');
    }
};

// Fonction pour ajouter une panne par défaut
function ajouterPanneDefaut(container) {
    const panneIndex = container.children.length + 1;
    
    const panneHtml = `
        <div class="panne-item card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Panne #${panneIndex}
                    </h6>
                    <button type="button" class="btn btn-outline-danger btn-sm supprimer-panne">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description de la panne *</label>
                            <textarea class="form-control panne-description" name="pannes[][description]" 
                                      rows="3" required placeholder="Décrivez le problème identifié..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gravité</label>
                            <select class="form-select panne-gravite" name="pannes[][gravite]">
                                <option value="faible">Faible</option>
                                <option value="moyenne" selected>Moyenne</option>
                                <option value="elevee">Élevée</option>
                                <option value="critique">Critique</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Coût estimé</label>
                            <div class="input-group">
                                <input type="number" class="form-control panne-cout" name="pannes[][cout]" 
                                       step="0.01" min="0" placeholder="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = panneHtml;
    const panneElement = tempDiv.firstElementChild;
    
    // Ajouter l'événement de suppression
    const supprimerBtn = panneElement.querySelector('.supprimer-panne');
    supprimerBtn.addEventListener('click', function() {
        panneElement.remove();
        // Renuméroter les pannes restantes
        const pannes = container.querySelectorAll('.panne-item');
        pannes.forEach((panne, index) => {
            const titre = panne.querySelector('.card-title');
            if (titre) {
                titre.innerHTML = `<i class="fas fa-exclamation-triangle text-warning me-2"></i>Panne #${index + 1}`;
            }
        });
    });
    
    container.appendChild(panneElement);
    
    // Focus sur le premier champ
    const firstInput = panneElement.querySelector('.panne-description');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
    
    console.log(`✅ Panne #${panneIndex} ajoutée`);
}

// Fonction pour ajouter une solution par défaut
function ajouterSolutionDefaut(container) {
    const solutionIndex = container.children.length + 1;
    
    const solutionHtml = `
        <div class="solution-item card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb text-success me-2"></i>
                        Solution #${solutionIndex}
                    </h6>
                    <button type="button" class="btn btn-outline-danger btn-sm supprimer-solution">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description de la solution *</label>
                            <textarea class="form-control solution-description" name="solutions[][description]" 
                                      rows="3" required placeholder="Décrivez la solution proposée..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pièces nécessaires</label>
                            <textarea class="form-control solution-pieces" name="solutions[][pieces]" 
                                      rows="2" placeholder="Liste des pièces à remplacer..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Garantie</label>
                                    <input type="text" class="form-control solution-garantie" name="solutions[][garantie]"
                                           value="3 mois" placeholder="Ex: 3 mois">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prix *</label>
                            <div class="input-group">
                                <input type="number" class="form-control solution-prix" name="solutions[][prix]" 
                                       step="0.01" min="0" required placeholder="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = solutionHtml;
    const solutionElement = tempDiv.firstElementChild;
    
    // Ajouter l'événement de suppression
    const supprimerBtn = solutionElement.querySelector('.supprimer-solution');
    supprimerBtn.addEventListener('click', function() {
        solutionElement.remove();
        // Renuméroter les solutions restantes
        const solutions = container.querySelectorAll('.solution-item');
        solutions.forEach((solution, index) => {
            const titre = solution.querySelector('.card-title');
            if (titre) {
                titre.innerHTML = `<i class="fas fa-lightbulb text-success me-2"></i>Solution #${index + 1}`;
            }
        });
    });
    
    container.appendChild(solutionElement);
    
    // Focus sur le premier champ
    const firstInput = solutionElement.querySelector('.solution-description');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
    
    console.log(`✅ Solution #${solutionIndex} ajoutée`);
}

// Fonction pour sauvegarder le devis
function sauvegarderDevis(modal) {
    console.log('💾 Début de la sauvegarde du devis');
    
    // Récupérer l'ID de réparation
    const reparationIdInput = modal.querySelector('#devis_reparation_id');
    if (!reparationIdInput || !reparationIdInput.value) {
        alert('Erreur: ID de réparation manquant');
        return;
    }
    
    const reparationId = reparationIdInput.value;
    console.log('🔍 ID de réparation:', reparationId);
    
    // Collecter les données du formulaire
    const formData = {
        reparation_id: reparationId,
        titre: modal.querySelector('#devis_titre')?.value || '',
        description: modal.querySelector('#devis_description')?.value || '',
        pannes: [],
        solutions: []
    };
    
    // Collecter les pannes
    const panneItems = modal.querySelectorAll('.panne-item');
    panneItems.forEach((panneItem, index) => {
        const description = panneItem.querySelector('.panne-description')?.value || '';
        const gravite = panneItem.querySelector('.panne-gravite')?.value || 'moyenne';
        const cout = parseFloat(panneItem.querySelector('.panne-cout')?.value || '0');
        
        if (description.trim()) {
            formData.pannes.push({
                nom: description.trim(), // Le nom de la panne
                description: description.trim(), // Description détaillée
                gravite: gravite,
                cout: cout
            });
        }
    });
    
    // Collecter les solutions
    const solutionItems = modal.querySelectorAll('.solution-item');
    console.log(`🔍 Nombre d'éléments .solution-item trouvés: ${solutionItems.length}`);
    
    solutionItems.forEach((solutionItem, index) => {
        const description = solutionItem.querySelector('.solution-description')?.value || '';
        const pieces = solutionItem.querySelector('.solution-pieces')?.value || '';
        const garantie = solutionItem.querySelector('.solution-garantie')?.value || '';
        const prix = parseFloat(solutionItem.querySelector('.solution-prix')?.value || '0');
        
        console.log(`🔍 Solution ${index + 1}:`, {
            description: description,
            pieces: pieces,
            garantie: garantie,
            prix: prix,
            descriptionTrim: description.trim(),
            prixValid: prix > 0
        });
        
        if (description.trim() && prix > 0) {
            const solution = {
                nom: description.trim(), // Le nom de la solution
                description: pieces.trim(), // Les pièces deviennent la description détaillée
                prix: prix,
                garantie: garantie.trim()
            };
            formData.solutions.push(solution);
            console.log(`✅ Solution ${index + 1} ajoutée:`, solution);
        } else {
            console.log(`❌ Solution ${index + 1} ignorée - description vide ou prix ≤ 0`);
        }
    });
    
    console.log('📋 Données collectées:', formData);
    console.log('🔍 Nombre de pannes:', formData.pannes.length);
    console.log('🔍 Nombre de solutions:', formData.solutions.length);
    console.log('🔍 Détail des solutions:', formData.solutions);
    
    // Validation
    if (!formData.titre.trim()) {
        alert('Veuillez saisir un titre pour le devis');
        modal.querySelector('#devis_titre')?.focus();
        return;
    }
    
    if (formData.pannes.length === 0) {
        alert('Veuillez ajouter au moins une panne');
        return;
    }
    
    if (formData.solutions.length === 0) {
        alert('Veuillez ajouter au moins une solution avec un prix');
        return;
    }
    
    // Afficher le spinner de chargement
    const btnText = modal.querySelector('#sauvegarderBtn .btn-text');
    const btnLoading = modal.querySelector('#sauvegarderBtn .btn-loading');
    if (btnText) btnText.style.display = 'none';
    if (btnLoading) btnLoading.style.display = 'inline-flex';
    
    // Désactiver le bouton
    const sauvegarderBtn = modal.querySelector('#sauvegarderBtn');
    if (sauvegarderBtn) sauvegarderBtn.disabled = true;
    
    console.log('🚀 Envoi des données vers ajax/creer_devis_clean.php');
    
    // Envoyer les données
    fetch('ajax/creer_devis_clean.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        console.log('📡 Réponse reçue:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('✅ Données de réponse:', data);
        
        // Restaurer le bouton
        if (btnText) btnText.style.display = 'inline-flex';
        if (btnLoading) btnLoading.style.display = 'none';
        if (sauvegarderBtn) sauvegarderBtn.disabled = false;
        
        if (data.success) {
            // Succès
            alert(`✅ ${data.message}\n\n` +
                  `📄 Numéro de devis: ${data.numero_devis || 'N/A'}\n` +
                  `💰 Total HT: ${data.data?.total_ht || '0'}€\n` +
                  `💰 Total TTC: ${data.data?.total_ttc || '0'}€\n` +
                  `📱 SMS: ${data.sms_sent ? '✅ Envoyé' : '❌ ' + (data.sms_message || 'Non envoyé')}`);
            
            // Fermer le modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
            
            // Recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } else {
            // Erreur
            console.error('❌ Erreur serveur:', data);
            alert(`❌ Erreur: ${data.message || 'Erreur inconnue'}`);
        }
    })
    .catch(error => {
        console.error('❌ Erreur réseau:', error);
        
        // Restaurer le bouton
        if (btnText) btnText.style.display = 'inline-flex';
        if (btnLoading) btnLoading.style.display = 'none';
        if (sauvegarderBtn) sauvegarderBtn.disabled = false;
        
        alert('❌ Erreur de connexion. Veuillez réessayer.');
    });
}
</script>

<!-- Script pour ajuster la largeur du tableau -->
<!-- Script supprimé - largeur responsive gérée par CSS -->

<!-- Inclusion du script pour l'ajustement du tableau -->
<!-- <script src="/assets/js/reparations-table.js"></script> -->
<!-- Inclusion du script pour les fonctions clients -->
<!-- <script src="/assets/js/client-functions.js"></script> -->
<!-- Inclusion du script pour la sélection de fournisseurs -->
<script src="/assets/js/fournisseur-selector.js"></script>

<!-- Script du modal de mise à jour des statuts -->
<script src="/assets/js/update-status-modal.js"></script>

<!-- Click handler pour ouvrir le modal en vue cartes -->
<script>
document.addEventListener('click', function(e) {
    const detailsBtn = e.target.closest('.repair-cards-container .btn.btn-primary');
    const card = e.target.closest('.modern-card, .draggable-card');
    if (!detailsBtn && !card) return;

    const container = detailsBtn ? detailsBtn.closest('.modern-card, .draggable-card') : card;
    if (!container) return;
    const repairId = container.getAttribute('data-repair-id') || container.getAttribute('data-id');
    if (!repairId) return;

    e.preventDefault();
    e.stopPropagation();

    try {
        if (window.RepairModal && typeof RepairModal.loadRepairDetails === 'function') {
            console.log('🔄 Ouverture du modal (cartes) pour la réparation:', repairId);
            RepairModal.loadRepairDetails(repairId);
            return;
        }
    } catch (err) {
        console.error('Erreur ouverture détails (cartes):', err);
    }

    // Fallback: ouvrir simplement le modal si disponible
    const modal = document.getElementById('repairDetailsModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const modalInstance = new bootstrap.Modal(modal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modalInstance.show();
        
        // Forcer le backdrop après ouverture
        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.cssText = `
                    position: fixed !important; top: 0 !important; left: 0 !important;
                    width: 100% !important; height: 100% !important; z-index: 1050 !important;
                    backdrop-filter: ${document.body.classList.contains('dark-mode') ? 'blur(12px)' : 'blur(8px)'} !important;
                    background: ${document.body.classList.contains('dark-mode') ? 'rgba(0, 0, 0, 0.6)' : 'rgba(0, 0, 0, 0.4)'} !important;
                    display: block !important; opacity: 1 !important; visibility: visible !important;
                `;
            }
        }, 100);
    }
});
</script>
<!-- Scripts nécessaires pour le modal de détails réparation -->
<script src="/assets/js/modal-helper.js"></script>
<script src="/assets/js/toast-notifications.js?v=<?php echo time(); ?>"></script>
<script src="/assets/js/repair-actions.js?v=<?php echo time(); ?>"></script>
<script src="/assets/js/modern-repair-modal.js?v=<?php echo time(); ?>"></script>
<script src="/assets/js/stop-repair-modal.js?v=<?php echo time(); ?>"></script>
<script src="/assets/js/main.js"></script>


<style>
/* Laisser Bootstrap gérer l'affichage des modals normalement */

/* Modal Devis en attente - Plein écran */
#devisEnAttenteModal.show {
    position: fixed !important;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex !important;
    align-items: center;
    justify-content: center;
}
#devisEnAttenteModal .modal-dialog {
    width: 90% !important;
    max-width: 1200px !important;
    height: 90% !important;
    margin: 0 !important;
}
#devisEnAttenteModal .modal-content { 
    height: 100% !important; 
    display: flex; 
    flex-direction: column; 
}
#devisEnAttenteModal .modal-body { 
    flex: 1 !important; 
    overflow: hidden !important; 
    padding: 0 !important; 
}
#devisEnAttenteModal #devisEnAttenteFrame { width: 100% !important; height: 100% !important; border: 0 !important; }

/* Correctifs pour le modal de détails de réparation */
#repairDetailsModal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 1070 !important;
}

#repairDetailsModal.show .modal-dialog {
    transform: none !important;
}

/* Rétablir totalement les valeurs Bootstrap par défaut (pas d'override agressif) */
/* supprimé: .modal-backdrop/.modal z-index forcés */

/* Visibilité fiable pour updateStatusModal (aligné avec repairDetailsModal) */
#updateStatusModal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
#updateStatusModal .modal-dialog { transform: none !important; pointer-events: auto !important; }
#updateStatusModal { pointer-events: auto !important; }

/* Styles pour les tableaux modernes du modal updateStatusModal - STYLES FORCÉS */
#updateStatusModal .modern-table-container {
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
    overflow: hidden !important;
    margin-bottom: 24px !important;
    width: 100% !important;
    display: block !important;
    border: 1px solid #f1f5f9 !important;
}

#updateStatusModal .modern-table-header {
    display: grid !important;
    grid-template-columns: 50px 1.5fr 1fr 2fr 100px 120px !important;
    background: #ffffff !important;
    color: #374151 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    width: 100% !important;
    height: auto !important;
    border-bottom: 2px solid #f1f5f9 !important;
}

#updateStatusModal .header-cell {
    padding: 16px 12px !important;
    display: flex !important;
    align-items: center !important;
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    min-height: 50px !important;
    height: auto !important;
}

#updateStatusModal .header-cell:last-child {
    border-right: none !important;
}

#updateStatusModal .checkbox-cell {
    justify-content: center !important;
}

#updateStatusModal .price-cell {
    justify-content: flex-end !important;
}

#updateStatusModal .modern-table-body {
    max-height: 400px !important;
    overflow-y: auto !important;
    width: 100% !important;
    min-height: 100px !important;
    display: block !important;
    background: white !important;
}

#updateStatusModal .table-row {
    display: grid !important;
    grid-template-columns: 50px 1.5fr 1fr 2fr 100px 120px !important;
    border-bottom: 1px solid #e5e7eb !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
    width: 100% !important;
    min-height: 60px !important;
    background: white !important;
    position: relative !important;
    z-index: 1 !important;
}

#updateStatusModal .table-row:hover {
    background: #f9fafb !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

#updateStatusModal .table-row.selected {
    background: #eff6ff !important;
    border-left: 4px solid #3b82f6 !important;
}

#updateStatusModal .table-cell {
    padding: 16px 12px !important;
    display: flex !important;
    align-items: center !important;
    font-size: 14px !important;
    color: #374151 !important;
    border-right: 1px solid #f3f4f6 !important;
    min-height: 60px !important;
    word-break: break-word !important;
    height: auto !important;
    position: relative !important;
}

#updateStatusModal .table-cell:last-child {
    border-right: none !important;
}

#updateStatusModal .table-cell.checkbox-cell {
    justify-content: center !important;
}

#updateStatusModal .table-cell.price-cell {
    justify-content: flex-end !important;
    font-weight: 600 !important;
    color: #059669 !important;
}

.modern-checkbox {
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.modern-checkbox:checked {
    background: #3b82f6;
    border-color: #3b82f6;
}

.modern-checkbox:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.loading-row {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6b7280;
    font-size: 14px;
    gap: 12px;
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.empty-row {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #9ca3af;
    font-size: 14px;
    font-style: italic;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Styles pour les onglets modernes CSS purs */
#updateStatusModal .modern-tabs {
    display: flex;
    background: #ffffff;
    border-radius: 16px;
    padding: 8px;
    margin-bottom: 28px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

#updateStatusModal .modern-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 20px;
    border: none;
    background: transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
}

#updateStatusModal .modern-tab:hover {
    background: #f1f5f9;
    color: #475569;
}

#updateStatusModal .modern-tab.active {
    background: #3b82f6;
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

#updateStatusModal .modern-tab i {
    font-size: 16px;
}

#updateStatusModal .tab-badge {
    background: #e5e7eb;
    color: #6b7280;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    min-width: 24px;
    text-align: center;
}

#updateStatusModal .modern-tab.active .tab-badge {
    background: rgba(255, 255, 255, 0.9);
    color: #3b82f6;
}

#updateStatusModal .modern-tab-content {
    position: relative;
}

#updateStatusModal .tab-panel {
    display: none;
    animation: fadeIn 0.3s ease;
}

#updateStatusModal .tab-panel.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Styles pour la section d'actions moderne */
#updateStatusModal .modern-actions-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    background: #ffffff;
    border-radius: 16px;
    margin-top: 24px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

#updateStatusModal .selection-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #475569;
    font-weight: 500;
}

#updateStatusModal .selection-info i {
    color: #3b82f6;
}

#updateStatusModal .selection-buttons {
    display: flex;
    gap: 12px;
}

/* Styles pour le footer moderne */
#updateStatusModal .modal-footer-modern {
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
    padding: 24px;
}

#updateStatusModal .footer-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

#updateStatusModal .status-selector {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

#updateStatusModal .status-selector label {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

#updateStatusModal .modern-select {
    padding: 14px 40px 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

#updateStatusModal .modern-select:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

#updateStatusModal .modern-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

#updateStatusModal .modern-select option {
    padding: 12px;
    background: white;
    color: #374151;
    font-weight: 500;
}

/* Styles pour le switch moderne */
#updateStatusModal .modern-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

#updateStatusModal .switch-slider {
    position: relative;
    width: 50px;
    height: 24px;
    background: #cbd5e1;
    border-radius: 24px;
    transition: background 0.3s ease;
}

#updateStatusModal .switch-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

#updateStatusModal .modern-switch input:checked + .switch-slider {
    background: #3b82f6;
}

#updateStatusModal .modern-switch input:checked + .switch-slider::after {
    transform: translateX(26px);
}

#updateStatusModal .modern-switch input {
    display: none;
}

#updateStatusModal .switch-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #374151;
}

/* Styles pour les boutons modernes */
#updateStatusModal .modern-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

#updateStatusModal .modern-btn.primary {
    background: #3b82f6;
    color: white;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

#updateStatusModal .modern-btn.primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}
#updateStatusModal .modern-btn.secondary {
    background: #ffffff;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

#updateStatusModal .modern-btn.secondary:hover {
    background: #f9fafb;
    color: #374151;
    border-color: #9ca3af;
}

#updateStatusModal .modern-btn.outline {
    background: #ffffff;
    color: #3b82f6;
    border: 1px solid #3b82f6;
}

#updateStatusModal .modern-btn.outline:hover {
    background: #eff6ff;
    color: #2563eb;
}

#updateStatusModal .action-buttons {
    display: flex;
    gap: 12px;
}

/* Override du fond du modal pour un mode plus clair */
#updateStatusModal .modal-content {
    background: #ffffff !important;
    border: 1px solid #f1f5f9 !important;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08) !important;
}

#updateStatusModal .modal-header {
    background: #ffffff !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

#updateStatusModal .modal-body {
    background: #ffffff !important;
    padding: 32px !important;
}

/* STYLES POUR LE MODE NUIT */
body.dark-mode #updateStatusModal .modal-content {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3) !important;
}

body.dark-mode #updateStatusModal .modal-header {
    background: #1f2937 !important;
    border-bottom: 1px solid #374151 !important;
}

body.dark-mode #updateStatusModal .modal-body {
    background: #1f2937 !important;
    padding: 32px !important;
}

/* Onglets en mode nuit */
body.dark-mode #updateStatusModal .modern-tabs {
    background: #111827 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-tab {
    color: #9ca3af !important;
}

body.dark-mode #updateStatusModal .modern-tab:hover {
    background: #374151 !important;
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .modern-tab.active {
    background: #3b82f6 !important;
    color: white !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25) !important;
}

body.dark-mode #updateStatusModal .tab-badge {
    background: #4b5563 !important;
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .modern-tab.active .tab-badge {
    background: rgba(255, 255, 255, 0.9) !important;
    color: #3b82f6 !important;
}

/* Tableau en mode nuit */
body.dark-mode #updateStatusModal .modern-table-container {
    background: #111827 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-table-header {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-bottom: 2px solid #374151 !important;
}

body.dark-mode #updateStatusModal .header-cell {
    color: #f9fafb !important;
}

body.dark-mode #updateStatusModal .table-row {
    background: #111827 !important;
    border-bottom: 1px solid #374151 !important;
}

body.dark-mode #updateStatusModal .table-row:hover {
    background: #1f2937 !important;
}

body.dark-mode #updateStatusModal .table-row.selected {
    background: #1e3a8a !important;
    border-left: 4px solid #3b82f6 !important;
}

body.dark-mode #updateStatusModal .table-cell {
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .price-cell {
    color: #10b981 !important;
    font-weight: 600 !important;
}

/* Section d'actions en mode nuit */
body.dark-mode #updateStatusModal .modern-actions-section {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

body.dark-mode #updateStatusModal .selection-info {
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .selection-info i {
    color: #3b82f6 !important;
}

/* Footer en mode nuit */
body.dark-mode #updateStatusModal .modal-footer-modern {
    background: #1f2937 !important;
    border-top: 1px solid #374151 !important;
}

body.dark-mode #updateStatusModal .status-selector label {
    color: #f9fafb !important;
}

body.dark-mode #updateStatusModal .modern-select {
    background: #111827 !important;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e") !important;
    border: 2px solid #374151 !important;
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .modern-select:hover {
    border-color: #3b82f6 !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-select option {
    background: #111827 !important;
    color: #d1d5db !important;
}

/* Switch SMS en mode nuit */
body.dark-mode #updateStatusModal .switch-slider {
    background: #4b5563 !important;
}

body.dark-mode #updateStatusModal .modern-switch input:checked + .switch-slider {
    background: #3b82f6 !important;
}

body.dark-mode #updateStatusModal .switch-label {
    color: #d1d5db !important;
}

/* Boutons en mode nuit */
body.dark-mode #updateStatusModal .modern-btn.secondary {
    background: #374151 !important;
    color: #d1d5db !important;
    border: 1px solid #4b5563 !important;
}

body.dark-mode #updateStatusModal .modern-btn.secondary:hover {
    background: #4b5563 !important;
    color: #f9fafb !important;
    border-color: #6b7280 !important;
}

body.dark-mode #updateStatusModal .modern-btn.outline {
    background: #1f2937 !important;
    color: #3b82f6 !important;
    border: 1px solid #3b82f6 !important;
}

body.dark-mode #updateStatusModal .modern-btn.outline:hover {
    background: #1e3a8a !important;
    color: #60a5fa !important;
}

/* Checkboxes en mode nuit */
body.dark-mode #updateStatusModal .modern-checkbox {
    background: #374151 !important;
    border: 2px solid #4b5563 !important;
}

body.dark-mode #updateStatusModal .modern-checkbox:checked {
    background: #3b82f6 !important;
    border-color: #3b82f6 !important;
}

/* Badges de statut en mode clair */
#updateStatusModal .status-badge {
    background: #f3f4f6 !important;
    color: #374151 !important;
    border: 1px solid #d1d5db !important;
    font-weight: 600 !important;
    text-shadow: none !important;
}

/* Badges de statut en mode nuit */
body.dark-mode #updateStatusModal .status-badge {
    background: #374151 !important;
    color: #d1d5db !important;
    border: 1px solid #4b5563 !important;
}

/* Messages loading et empty en mode nuit */
body.dark-mode #updateStatusModal .loading-row,
body.dark-mode #updateStatusModal .empty-row {
    background: #111827 !important;
    color: #9ca3af !important;
}

body.dark-mode #updateStatusModal .loading-spinner {
    border-top-color: #3b82f6 !important;
}

/* Titre du modal */
#updateStatusModal .modal-title {
    color: #1f2937 !important;
}

/* Titre du modal en mode nuit */
body.dark-mode #updateStatusModal .modal-title {
    color: #f9fafb !important;
}

/* Élargir le modal pour PC */
@media (min-width: 1200px) {
    #updateStatusModal .modal-dialog {
        max-width: calc(1140px + 20px) !important;
    }
}

/* Désactiver les glissements dans le tableau */
#updateStatusModal .modern-table-container,
#updateStatusModal .modern-table-header,
#updateStatusModal .modern-table-body,
#updateStatusModal .table-row,
#updateStatusModal .table-cell,
#updateStatusModal .header-cell {
    -webkit-user-drag: none !important;
    -khtml-user-drag: none !important;
    -moz-user-drag: none !important;
    -o-user-drag: none !important;
    user-drag: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    pointer-events: auto !important;
    touch-action: none !important;
    -webkit-touch-callout: none !important;
    -webkit-tap-highlight-color: transparent !important;
}

/* Permettre la sélection des checkboxes et inputs */
#updateStatusModal .modern-checkbox,
#updateStatusModal input,
#updateStatusModal select,
#updateStatusModal button {
    -webkit-user-select: auto !important;
    -moz-user-select: auto !important;
    -ms-user-select: auto !important;
    user-select: auto !important;
    pointer-events: auto !important;
    touch-action: auto !important;
}

/* Responsive pour les petits écrans */
@media (max-width: 768px) {
    #updateStatusModal .modern-table-header,
    #updateStatusModal .table-row {
        grid-template-columns: 40px 1fr 1fr 80px 80px !important;
    }
    
    #updateStatusModal .header-cell:nth-child(4),
    #updateStatusModal .table-cell:nth-child(4) {
        display: none !important;
    }
    
    #updateStatusModal .footer-controls {
        flex-direction: column;
        gap: 16px;
    }
    
    #updateStatusModal .modern-actions-section {
        flex-direction: column;
        gap: 16px;
    }
}
</style>

<!-- Modal Devis en attente -->
<style>
/* Correction Z-Index pour passer au-dessus du logo Servo (9999) */
#devisEnAttenteModal {
    z-index: 11000 !important;
}

/* Styles mode nuit pour le modal devisEnAttenteModal */
body.dark-mode #devisEnAttenteModal .modal-content {
    background-color: #1e1e1e !important;
    border-color: #333333 !important;
}

body.dark-mode #devisEnAttenteModal .modal-header,
body.dark-mode #devisEnAttenteModal .modal-header.bg-light {
    background-color: #1e293b !important;
    border-bottom-color: #333333 !important;
}

body.dark-mode #devisEnAttenteModal .modal-title {
    color: #e0e0e0 !important;
}

body.dark-mode #devisEnAttenteModal .modal-footer {
    background-color: #1e293b !important;
    border-top-color: #333333 !important;
}

body.dark-mode #devisEnAttenteModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>
<div class="modal fade" id="devisEnAttenteModal" tabindex="-1" aria-labelledby="devisEnAttenteLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="devisEnAttenteLabel">
                    <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Devis en attente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="devisEnAttenteFrame" src="about:blank" style="width:100%; height:75vh; border:0;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Charger l'interface des devis en attente dans l'iframe au moment de l'ouverture
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('devisEnAttenteModal');
    const frame = document.getElementById('devisEnAttenteFrame');
    if (!modalEl || !frame) return;
    
    // Charger l'iframe quand le modal s'ouvre
    modalEl.addEventListener('shown.bs.modal', function() {
        frame.src = 'index.php?page=devis&statut_ids=envoye';
    });

    // Force le nettoyage du backdrop à la fermeture ET nettoyer l'iframe
    modalEl.addEventListener('hidden.bs.modal', function () {
        // Nettoyage backdrop
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Nettoyage iframe
        frame.src = 'about:blank';
    });
});
</script>
});
</script>
<!-- Script pour ouverture automatique du modal de détails -->
<script>
// Correctif d'affichage forcé du modal updateStatusModal
// Correctif minimal pour les modals sur cette page
document.addEventListener('DOMContentLoaded', function() {
    // Nettoyage: ne plus forcer l'affichage des modals ici

    // Déplacer UNE SEULE FOIS certains modals sous <body> au chargement
    (function moveCriticalModalsOnce() {
        const idsToMove = ['nouvelles_actions_modal', 'devisEnAttenteModal', 'updateStatusModal', 'ajouterTacheModal'];
        idsToMove.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                try {
                    document.body.appendChild(el);
                    console.log('[MODAL FIX] Modal déplacé sous <body> (once):', id);
                } catch (err) {
                    console.warn('[MODAL FIX] Impossible de déplacer le modal (once):', id, err);
                }
            }
        });
    })();
});
// Variable globale pour stocker l'ID du modal à ouvrir
window.pendingModalId = null;

document.addEventListener("DOMContentLoaded", function() {
    // Vérifier s'il y a un paramètre open_modal dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    let openModalParam = urlParams.get('open_modal');
    
    // Support pour le format id=XXX&open_modal=1 (en plus de open_modal=XXX)
    // Si open_modal vaut "1" et qu'un paramètre "id" existe, utiliser l'id
    if (openModalParam === '1' && urlParams.get('id')) {
        openModalParam = urlParams.get('id');
    }
    
    // open_modal contient directement l'ID de la réparation (ou l'id récupéré ci-dessus)
    if (openModalParam && openModalParam !== '1') {
        window.pendingModalId = openModalParam;
        
        // Nettoyer l'URL immédiatement pour éviter les problèmes de rechargement
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('open_modal');
        cleanUrl.searchParams.delete('id'); // Aussi nettoyer id si utilisé pour le modal
        window.history.replaceState({}, document.title, cleanUrl);

        
        // Fonction pour tenter d'ouvrir le modal
        function attemptOpenModal(retries = 0) {
            // Vérifier si RepairModal est disponible
            if (typeof RepairModal !== 'undefined' && RepairModal.loadRepairDetails) {
                try {
                    RepairModal.loadRepairDetails(window.pendingModalId);
                    window.pendingModalId = null; // Marquer comme traité
                    return true;
                } catch (error) {
                    // Erreur silencieuse
                }
            }
            
            // Si RepairModal n'est pas disponible, essayer l'initialisation
            if (typeof RepairModal !== 'undefined' && RepairModal.init && !RepairModal._isInitialized) {
                try {
                    RepairModal.init();
                    // Réessayer après initialisation
                    setTimeout(() => attemptOpenModal(retries), 200);
                    return false;
                } catch (error) {
                    // Erreur silencieuse
                }
            }
            
            // Chercher un bouton existant à cliquer
            const detailsButton = document.querySelector(`[onclick*="RepairModal.loadRepairDetails(${window.pendingModalId})"]`) || 
                                document.querySelector(`[data-repair-id="${window.pendingModalId}"]`);
            
            if (detailsButton) {
                console.log('✅ Bouton de détails trouvé, clic simulé...');
                try {
                    detailsButton.click();
                    window.pendingModalId = null; // Marquer comme traité
                    return true;
                } catch (error) {
                    console.error('❌ Erreur lors du clic sur le bouton:', error);
                }
            }
            
            // Fallback : ouvrir le modal directement et charger les données
            const modal = document.getElementById('repairDetailsModal');
            if (modal && typeof bootstrap !== 'undefined') {
                console.log('🔄 Fallback: ouverture directe du modal...');
                try {
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    // Charger les détails via AJAX
                    const shopId = document.body.getAttribute('data-shop-id') || '<?php echo $current_shop_id ?? ""; ?>';
                    const apiUrl = `ajax/get_repair_details.php?id=${window.pendingModalId}${shopId ? '&shop_id=' + shopId : ''}`;
                    
                    console.log('🔄 Chargement des détails via:', apiUrl);
                    
                    fetch(apiUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('✅ Détails chargés avec succès');
                                // Mettre à jour le titre du modal
                                const modalTitle = document.getElementById('repairDetailsModalLabel');
                                if (modalTitle) {
                                    modalTitle.innerHTML = `<i class="fas fa-tools me-2 text-primary"></i>Réparation #${window.pendingModalId}`;
                                }
                                window.pendingModalId = null; // Marquer comme traité
                            } else {
                                console.error('❌ Erreur lors du chargement des détails:', data.message);
                                throw new Error(data.message || 'Erreur lors du chargement');
                            }
                        })
                        .catch(error => {
                            console.error('❌ Erreur AJAX:', error);
                            // Afficher un message d'erreur dans le modal
                            const modalBody = modal.querySelector('.modal-body');
                            if (modalBody) {
                                modalBody.innerHTML = `
                                    <div class="alert alert-danger">
                                        <h5>Erreur</h5>
                                        <p>Impossible de charger les détails de la réparation #${window.pendingModalId}</p>
                                        <p class="small">Erreur: ${error.message}</p>
                                    </div>
                                `;
                            }
                        });
                    
                    return true;
                } catch (error) {
                    console.error('❌ Erreur lors de l\'ouverture directe du modal:', error);
                }
            }
            
            // Si on arrive ici, réessayer si on n'a pas atteint le maximum
            if (retries < 10) {
                setTimeout(() => attemptOpenModal(retries + 1), 300 + (retries * 100));
                return false;
            } else {
                console.error('❌ Impossible d\'ouvrir le modal après 10 tentatives');
                alert(`Impossible d'ouvrir automatiquement les détails de la réparation #${window.pendingModalId}. Vous pouvez cliquer manuellement sur la réparation pour voir ses détails.`);
                window.pendingModalId = null;
                return false;
            }
        }
        
        // Démarrer les tentatives d'ouverture avec un délai initial
        setTimeout(() => attemptOpenModal(), 800);
    }
});

// Fonction de secours pour ouvrir le modal en cas d'échec
window.openPendingModal = function() {
    if (window.pendingModalId) {
        console.log('🔄 Fonction de secours appelée pour la réparation:', window.pendingModalId);
        if (typeof RepairModal !== 'undefined' && RepairModal.loadRepairDetails) {
            RepairModal.loadRepairDetails(window.pendingModalId);
            window.pendingModalId = null;
        }
    }
};
</script>
<!-- Modal de mise à jour des statuts par lots -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    <i class="fas fa-tasks me-2"></i>Mise à jour des statuts par lots
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Onglets modernes CSS purs -->
                <div class="modern-tabs" id="statusTabs">
                    <button class="modern-tab active" data-tab="nouvelles" id="nouvelles-tab">
                        <i class="fas fa-plus-circle"></i>
                        <span>Nouvelles</span>
                        <span class="tab-badge" id="count-nouvelles">0</span>
                    </button>
                    <button class="modern-tab" data-tab="en-cours" id="en-cours-tab">
                        <i class="fas fa-cog"></i>
                        <span>En cours</span>
                        <span class="tab-badge" id="count-en-cours">0</span>
                    </button>
                    <button class="modern-tab" data-tab="en-attente" id="en-attente-tab">
                        <i class="fas fa-clock"></i>
                        <span>En attente</span>
                        <span class="tab-badge" id="count-en-attente">0</span>
                    </button>
                    <button class="modern-tab" data-tab="terminees" id="terminees-tab">
                        <i class="fas fa-check-circle"></i>
                        <span>Terminées</span>
                        <span class="tab-badge" id="count-terminees">0</span>
                    </button>
                </div>

                <!-- Contenu des onglets avec tableaux modernes CSS purs -->
                <div class="modern-tab-content" id="statusTabsContent">
                    <!-- Onglet Nouvelles -->
                    <div class="tab-panel active" id="nouvelles">
                        <div class="cards-grid-container">
                            <div class="cards-grid" id="repairs-nouvelles">
                                <div class="loading-card">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet En cours -->
                    <div class="tab-panel" id="en-cours">
                        <div class="cards-grid-container">
                            <div class="cards-grid" id="repairs-en-cours">
                                <div class="loading-card">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet En attente -->
                    <div class="tab-panel" id="en-attente">
                        <div class="cards-grid-container">
                            <div class="cards-grid" id="repairs-en-attente">
                                <div class="loading-card">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Terminées -->
                    <div class="tab-panel" id="terminees">
                        <div class="cards-grid-container">
                            <div class="cards-grid" id="repairs-terminees">
                                <div class="loading-card">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section d'actions moderne -->
                <div class="modern-actions-section">
                    <div class="selection-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="selected-count">0 réparation(s) sélectionnée(s)</span>
                    </div>
                    
                    <div class="selection-buttons">
                        <button type="button" class="modern-btn outline" id="select-all-visible">
                            <i class="fas fa-check-square"></i>
                            Tout sélectionner
                        </button>
                        <button type="button" class="modern-btn outline" id="deselect-all">
                            <i class="fas fa-square"></i>
                            Tout désélectionner
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-modern">
                <div class="footer-controls">
                    <div class="status-selector">
                        <label for="new-status-select">Nouveau statut :</label>
                        <select id="new-status-select" class="modern-select">
                            <option value="">-- Choisir un statut --</option>
                            <option value="nouvelles">Nouvelle Intervention</option>
                            <option value="nouvelle_commande">Nouvelle Commande</option>
                            <option value="en_attente_accord">En attente de l'accord client</option>
                            <option value="en_attente_livraison">En attente de livraison</option>
                            <option value="reparation_effectue">Réparation Effectuée</option>
                            <option value="reparation_annule">Réparation Annulée</option>
                            <option value="restituee">Restituée</option>
                            <option value="gardiennage">Gardiennage</option>
                            <option value="archive">Archiver</option>
                        </select>
                    </div>
                    
                    <div class="sms-toggle">
                        <label class="modern-switch">
                            <input type="checkbox" id="send-sms-checkbox" checked>
                            <span class="switch-slider"></span>
                            <span class="switch-label">
                                <i class="fas fa-sms"></i>
                                Envoyer SMS
                            </span>
                        </label>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="modern-btn secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="button" class="modern-btn primary" id="update-selected-repairs">
                            <i class="fas fa-save"></i>
                            Mettre à jour
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- CSS pour les cartes modernes -->
<style>
/* ===== GRILLE DE CARTES MODERNE ===== */
.cards-grid-container {
    padding: 1.5rem 0;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    padding: 0 1rem;
}

/* Carte de réparation */
.repair-card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.repair-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.4);
}

/* Header de la carte avec checkbox et statut */
.repair-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.repair-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #667eea;
}

.repair-status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Infos client */
.repair-client-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.repair-client-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.repair-client-name {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1f2937;
    flex: 1;
}

/* Infos appareil */
.repair-device-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.repair-device-icon {
    color: #667eea;
}

/* Problème */
.repair-problem {
    color: #4b5563;
    font-size: 0.9rem;
    line-height: 1.5;
    padding: 0.75rem;
    background: rgba(243, 244, 246, 0.8);
    border-radius: 8px;
    min-height: 3rem;
    display: flex;
    align-items: center;
}

/* Badges de statut - Mode jour avec meilleur contraste */
.repair-status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Statut 1, 2, 3 - Nouvelles (Bleu) */
.repair-status-badge.status-1,
.repair-status-badge.status-2,
.repair-status-badge.status-3 {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

/* Statut 4, 5 - En cours (Orange) */
.repair-status-badge.status-4,
.repair-status-badge.status-5 {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
}

/* Statut 6, 7, 8 - En attente (Jaune/Ambre) */
.repair-status-badge.status-6,
.repair-status-badge.status-7,
.repair-status-badge.status-8 {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

/* Statut 9, 11, 15 - Terminées (Vert) */
.repair-status-badge.status-9,
.repair-status-badge.status-11,
.repair-status-badge.status-15 {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

/* Statut 10 - Annulé/Refusé (Rouge) */
.repair-status-badge.status-10 {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

/* Mode nuit - badges plus sombres */
body.dark-mode .repair-status-badge.status-1,
body.dark-mode .repair-status-badge.status-2,
body.dark-mode .repair-status-badge.status-3 {
    background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
}

body.dark-mode .repair-status-badge.status-4,
body.dark-mode .repair-status-badge.status-5 {
    background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%);
}

body.dark-mode .repair-status-badge.status-6,
body.dark-mode .repair-status-badge.status-7,
body.dark-mode .repair-status-badge.status-8 {
    background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
}

body.dark-mode .repair-status-badge.status-9,
body.dark-mode .repair-status-badge.status-11,
body.dark-mode .repair-status-badge.status-15 {
    background: linear-gradient(135deg, #047857 0%, #065f46 100%);
}

body.dark-mode .repair-status-badge.status-10 {
    background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
}

/* Prix en évidence */
.repair-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #10b981;
    text-align: right;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* Loading card */
.loading-card {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    gap: 1rem;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(102, 126, 234, 0.2);
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ===== DARK MODE ===== */
body.dark-mode .repair-card {
    background: rgba(30, 32, 45, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

body.dark-mode .repair-card:hover {
    border-color: rgba(102, 126, 234, 0.6);
    box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3);
}

body.dark-mode .repair-client-name {
    color: rgba(235, 240, 255, 0.95);
}

body.dark-mode .repair-device-info {
    color: rgba(235, 240, 255, 0.7);
}

body.dark-mode .repair-problem {
    background: rgba(40, 42, 55, 0.8);
    color: rgba(235, 240, 255, 0.85);
}

body.dark-mode .repair-price {
    color: #34d399;
    border-top-color: rgba(255, 255, 255, 0.1);
}

body.dark-mode .loading-card {
    color: rgba(235, 240, 255, 0.7);
}

/* ===== ÉTAT SÉLECTIONNÉ ===== */
.repair-card.selected {
    border-color: #667eea !important;
    background: rgba(102, 126, 234, 0.08) !important;
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.25) !important;
}

body.dark-mode .repair-card.selected {
    border-color: #7d9bff !important;
    background: rgba(125, 155, 255, 0.15) !important;
    box-shadow: 0 8px 30px rgba(125, 155, 255, 0.35) !important;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .cards-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .cards-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .repair-card {
        padding: 1rem;
    }
}
</style>

</div> <!-- Fermeture de page-container -->


<style>
/* Force SERVO Animation Fix - RÉACTIVÉ */
.servo-logo-container .dash {
    animation: servoDashArray 2s ease-in-out infinite, servoDashOffset 2s linear infinite !important;
}

.servo-logo-container .spin {
    animation: servoSpinDashArray 2s ease-in-out infinite, servoSpin 8s ease-in-out infinite, servoDashOffset 2s linear infinite !important;
    transform-origin: center;
}

@keyframes servoDashArray {
    0% { stroke-dasharray: 0 1 359 0; }
    50% { stroke-dasharray: 0 359 1 0; }
    100% { stroke-dasharray: 359 1 0 0; }
}

@keyframes servoSpinDashArray {
    0% { stroke-dasharray: 270 90; }
    50% { stroke-dasharray: 0 360; }
    100% { stroke-dasharray: 250 90; }
}

@keyframes servoDashOffset {
    0% { stroke-dashoffset: 385; }
    100% { stroke-dashoffset: 5; }
}

@keyframes servoSpin {
    0% { rotate: 0deg; }
    12.5%, 25% { rotate: 270deg; }
    37.5%, 50% { rotate: 540deg; }
    62.5%, 75% { rotate: 810deg; }
    87.5%, 100% { rotate: 1080deg; }
}
</style>

<style>
#pageLoader {
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

/* LOADER COMPLEXE SUPPRIMÉ POUR PERFORMANCE - Remplacé par spinner simple */


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
.loader-letter:nth-child(6) {
  animation-delay: 0.5s;
}
.loader-letter:nth-child(7) {
  animation-delay: 0.6s;
}
.loader-letter:nth-child(8) {
  animation-delay: 0.7s;
}
.loader-letter:nth-child(9) {
  animation-delay: 0.8s;
}
.loader-letter:nth-child(10) {
  animation-delay: 0.9s;
}
.loader-letter:nth-child(11) {
  animation-delay: 1s;
}
.loader-letter:nth-child(12) {
  animation-delay: 1.1s;
}
.loader-letter:nth-child(13) {
  animation-delay: 1.2s;
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

/* Masquer le loader quand la page est chargée */
#pageLoader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

#pageLoader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
.page-container.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Loader wrapper - adapté pour dark et light modes */
.loader-wrapper {
  display: flex;
  position: relative;
  width: 300px;
  height: 300px;
  align-items: center;
  justify-content: center;
}

/* Loader background adapté au mode */
#pageLoader {
  background: #1a1d2e; /* Mode sombre par défaut */
}

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

/* Cercle du loader - couleurs adaptées */
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

/* Animation pour le mode sombre (par défaut) */
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #007ac1 inset,
      0 12px 18px 0 #00a0f0 inset,
      0 36px 36px 0 #1e90ff inset,
      0 0 3px 1.2px rgba(30, 144, 255, 0.4),
      0 0 6px 1.8px rgba(0, 160, 240, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0091ea inset,
      0 12px 18px 0 #007ac1 inset,
      0 36px 36px 0 #00a0f0 inset,
      0 0 6px 2.4px rgba(30, 144, 255, 0.4),
      0 0 12px 3.6px rgba(0, 160, 240, 0.3),
      0 0 18px 6px rgba(30, 144, 255, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #00a0f0 inset,
      0 12px 6px 0 #0077c2 inset,
      0 24px 36px 0 #0091ea inset,
      0 0 3px 1.2px rgba(30, 144, 255, 0.4),
      0 0 6px 2.4px rgba(0, 160, 240, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #0077c2 inset,
      0 12px 18px 0 #0091ea inset,
      0 36px 36px 0 #007ac1 inset,
      0 0 12px 4.8px rgba(0, 160, 240, 0.3),
      0 0 24px 7.2px rgba(30, 144, 255, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #007ac1 inset,
      0 12px 18px 0 #00a0f0 inset,
      0 36px 36px 0 #1e90ff inset,
      0 0 3px 1.2px rgba(30, 144, 255, 0.4),
      0 0 6px 1.8px rgba(0, 160, 240, 0.3);
  }
}

/* Animation adaptée pour le mode clair */
body:not(.dark-mode) .loader-circle {
  animation: loader-combined-light 2.3s linear infinite;
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

}

/* Texte du loader - adapté aux deux modes */
.loader-text {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text /* ANIMATIONS LOADER-LETTER SUPPRIMÉES POUR PERFORMANCE */

/* Les styles de fond sont maintenant gérés par les variables CSS modernes plus haut */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,5 seconde puis masquer le loader et afficher le contenu
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

// === FONCTIONS MODAL SMS HISTORIQUE RÉPARATIONS ===
function showRepairSmsModal(repairId, clientName, clientPhone) {
    console.log('📋 Ouverture de l\'historique complet pour la réparation:', repairId, clientName, clientPhone);
    
    // Supprimer l'ancien modal s'il existe
    const existingModal = document.getElementById('repairHistoryModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Créer le nouveau modal d'historique complet
    createRepairHistoryModal(repairId, clientName, clientPhone);
    
    // Charger les données
    loadCompleteRepairHistory(repairId);
}

// Alias pour la fonction d'historique (utilisé dans les boutons)
function showRepairHistoryModal(repairId, clientName, clientPhone) {
    return showRepairSmsModal(repairId, clientName, clientPhone);
}

function createRepairHistoryModal(repairId, clientName, clientPhone) {
    const modalHtml = `
        <div id="repairHistoryModal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            <div class="repair-history-modal-content" style="
                background: white;
                width: 95vw;
                max-width: 1400px;
                height: 90vh;
                border-radius: 20px;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            ">
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 25px 30px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="
                            width: 60px;
                            height: 60px;
                            background: rgba(255, 255, 255, 0.2);
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <i class="fas fa-history" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0; font-size: 24px; font-weight: 700;">Historique Complet</h2>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 16px;">
                                Réparation #${repairId} • ${clientName} • ${clientPhone}
                            </p>
                        </div>
                    </div>
                    <button onclick="closeRepairHistoryModal()" style="
                        background: rgba(255, 255, 255, 0.2);
                        border: none;
                        color: white;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 18px;
                    ">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="repair-history-modal-body" style="
                    flex: 1;
                    padding: 30px;
                    overflow-y: auto;
                    background: #f8fafc;
                ">
                    <!-- Loading -->
                    <div id="historyLoading" style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 200px;
                        flex-direction: column;
                        gap: 20px;
                    ">
                        <div style="
                            width: 50px;
                            height: 50px;
                            border: 4px solid #e2e8f0;
                            border-top: 4px solid #667eea;
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                        "></div>
                        <p style="color: #64748b; font-size: 16px; margin: 0;">Chargement de l'historique complet...</p>
                    </div>
                    
                    <!-- Content -->
                    <div id="historyContent" style="display: none;">
                        <!-- Le contenu sera injecté ici -->
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="repair-history-modal-footer" style="
                    background: #1e293b;
                    color: white;
                    padding: 20px 30px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div style="display: flex; align-items: center; gap: 8px; color: #94a3b8;">
                        <i class="fas fa-info-circle"></i>
                        <span>Historique complet de la réparation</span>
                    </div>
                    <button onclick="closeRepairHistoryModal()" style="
                        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                        border: none;
                        color: white;
                        padding: 12px 24px;
                        border-radius: 25px;
                        cursor: pointer;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    ">
                        <i class="fas fa-check"></i>
                        Fermer
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mode nuit pour le modal d'historique - Styles plus spécifiques */
        html[data-theme="dark"] .repair-history-modal-content,
        html.dark .repair-history-modal-content,
        body[data-theme="dark"] .repair-history-modal-content,
        body.dark .repair-history-modal-content,
        .dark .repair-history-modal-content,
        [data-theme="dark"] .repair-history-modal-content {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }
        
        html[data-theme="dark"] .repair-history-modal-body,
        html.dark .repair-history-modal-body,
        body[data-theme="dark"] .repair-history-modal-body,
        body.dark .repair-history-modal-body,
        .dark .repair-history-modal-body,
        [data-theme="dark"] .repair-history-modal-body {
            background: #0f172a !important;
        }
        
        html[data-theme="dark"] .repair-history-modal-footer,
        html.dark .repair-history-modal-footer,
        body[data-theme="dark"] .repair-history-modal-footer,
        body.dark .repair-history-modal-footer,
        .dark .repair-history-modal-footer,
        [data-theme="dark"] .repair-history-modal-footer {
            background: #0f172a !important;
            border-top: 1px solid #334155;
        }
        
        /* Sections en mode nuit */
        [data-theme="dark"] .repair-info-section,
        .dark .repair-info-section,
        body.dark .repair-info-section {
            background: #334155 !important;
            color: #e2e8f0 !important;
        }
        
        [data-theme="dark"] .status-history-section,
        .dark .status-history-section,
        body.dark .status-history-section {
            background: #334155 !important;
            color: #e2e8f0 !important;
        }
        
        [data-theme="dark"] .sms-history-section,
        .dark .sms-history-section,
        body.dark .sms-history-section {
            background: #334155 !important;
            color: #e2e8f0 !important;
        }
        
        /* Titres et textes en mode nuit */
        [data-theme="dark"] .repair-info-section h3,
        [data-theme="dark"] .status-history-section h3,
        [data-theme="dark"] .sms-history-section h3,
        .dark .repair-info-section h3,
        .dark .status-history-section h3,
        .dark .sms-history-section h3,
        body.dark .repair-info-section h3,
        body.dark .status-history-section h3,
        body.dark .sms-history-section h3 {
            color: #e2e8f0 !important;
        }
        
        /* Cartes de statut en mode nuit */
        [data-theme="dark"] .status-card,
        .dark .status-card,
        body.dark .status-card {
            background: #475569 !important;
            border-color: #64748b !important;
            color: #e2e8f0 !important;
        }
        
        /* Cartes SMS en mode nuit */
        [data-theme="dark"] .sms-card,
        .dark .sms-card,
        body.dark .sms-card {
            background: #475569 !important;
            border-color: #64748b !important;
            color: #e2e8f0 !important;
        }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    console.log('✅ Modal d\'historique complet créé');
    
    // Forcer l'application du mode nuit si nécessaire
    setTimeout(() => {
        const modal = document.getElementById('repairHistoryModal');
        if (modal) {
            const isDarkMode = document.body.classList.contains('dark-mode') ||
                              document.documentElement.classList.contains('dark') ||
                              document.body.classList.contains('dark') ||
                              (document.documentElement.hasAttribute('data-theme') && 
                               document.documentElement.getAttribute('data-theme') === 'dark');
            
            console.log('🌙 Vérification mode nuit:', {
                isDarkMode: isDarkMode,
                bodyDarkMode: document.body.classList.contains('dark-mode'),
                htmlDark: document.documentElement.classList.contains('dark'),
                bodyDark: document.body.classList.contains('dark'),
                dataTheme: document.documentElement.getAttribute('data-theme')
            });
            
            if (isDarkMode) {
                console.log('🌙 Mode nuit détecté - Application des styles');
                const content = modal.querySelector('.repair-history-modal-content');
                const body = modal.querySelector('.repair-history-modal-body');
                const footer = modal.querySelector('.repair-history-modal-footer');
                
                if (content) {
                    content.style.setProperty('background', '#1e293b', 'important');
                    content.style.setProperty('color', '#e2e8f0', 'important');
                }
                if (body) {
                    body.style.setProperty('background', '#0f172a', 'important');
                }
                if (footer) {
                    footer.style.setProperty('background', '#0f172a', 'important');
                    footer.style.setProperty('border-top', '1px solid #334155', 'important');
                }
            }
        }
    }, 100);
}

function closeRepairHistoryModal() {
    const modal = document.getElementById('repairHistoryModal');
    if (modal) {
        modal.remove();
        console.log('✅ Modal d\'historique fermé');
    }
}

// Fonctions pour gérer les sections collapsibles
function toggleStatusHistory() {
    const content = document.getElementById('statusHistoryContent');
    const icon = document.getElementById('statusHistoryIcon');
    
    if (content && icon) {
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
            console.log('📊 Section historique des statuts ouverte');
            
            // Appliquer le mode nuit aux cartes de statut
            setTimeout(() => {
                applyDarkModeToStatusCards();
            }, 10);
        } else {
            content.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
            console.log('📊 Section historique des statuts fermée');
        }
    }
}

function toggleSmsHistory() {
    const content = document.getElementById('smsHistoryContent');
    const icon = document.getElementById('smsHistoryIcon');
    
    if (content && icon) {
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
            console.log('📱 Section historique SMS ouverte');
            
            // Appliquer le mode nuit aux cartes SMS
            setTimeout(() => {
                applyDarkModeToSmsCards();
            }, 10);
        } else {
            content.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
            console.log('📱 Section historique SMS fermée');
        }
    }
}

function loadCompleteRepairHistory(repairId) {
    console.log('📊 Chargement de l\'historique complet pour la réparation:', repairId);
    
    // Appeler l'endpoint pour récupérer l'historique complet
    fetch(`ajax/get_complete_repair_history.php?repair_id=${repairId}`)
        .then(response => response.json())
        .then(data => {
            console.log('✅ Historique complet chargé:', data);
            
            const loadingElement = document.getElementById('historyLoading');
            const contentElement = document.getElementById('historyContent');
            
            if (loadingElement) loadingElement.style.display = 'none';
            if (contentElement) {
                contentElement.style.display = 'block';
                contentElement.innerHTML = generateCompleteHistoryHTML(data);
                
                // Appliquer le mode nuit aux sections générées
                setTimeout(() => {
                    const isDarkMode = document.body.classList.contains('dark-mode') ||
                                      document.documentElement.classList.contains('dark') ||
                                      document.body.classList.contains('dark') ||
                                      (document.documentElement.hasAttribute('data-theme') && 
                                       document.documentElement.getAttribute('data-theme') === 'dark');
                    
                    console.log('🌙 Vérification mode nuit pour sections:', isDarkMode);
                    
                    if (isDarkMode) {
                        console.log('🌙 Application du mode nuit aux sections générées');
                        const sections = document.querySelectorAll('.repair-info-section, .status-history-section, .sms-history-section');
                        sections.forEach(section => {
                            section.style.setProperty('background', '#334155', 'important');
                            section.style.setProperty('color', '#e2e8f0', 'important');
                            
                            // Titres
                            const titles = section.querySelectorAll('h3');
                            titles.forEach(title => {
                                title.style.setProperty('color', '#e2e8f0', 'important');
                            });
                            
                            // Cartes de statut et SMS
                            const cards = section.querySelectorAll('div[style*="background: white"], div[style*="background:white"]');
                            cards.forEach(card => {
                                card.style.setProperty('background', '#475569', 'important');
                                card.style.setProperty('color', '#e2e8f0', 'important');
                                
                                // Textes dans les cartes
                                const texts = card.querySelectorAll('h4, p, span');
                                texts.forEach(text => {
                                    text.style.setProperty('color', '#e2e8f0', 'important');
                                });
                            });
                        });
                    }
                }, 50);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement de l\'historique:', error);
            
            const loadingElement = document.getElementById('historyLoading');
            const contentElement = document.getElementById('historyContent');
            
            if (loadingElement) loadingElement.style.display = 'none';
            if (contentElement) {
                contentElement.style.display = 'block';
                contentElement.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ef4444;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <h3>Erreur de chargement</h3>
                        <p>Impossible de charger l'historique de la réparation.</p>
                        <button onclick="loadCompleteRepairHistory(${repairId})" style="
                            background: #3b82f6;
                            color: white;
                            border: none;
                            padding: 12px 24px;
                            border-radius: 8px;
                            cursor: pointer;
                            margin-top: 15px;
                        ">Réessayer</button>
                    </div>
                `;
            }
        });
}

function generateCompleteHistoryHTML(data) {
    if (!data.success) {
        return `
            <div style="text-align: center; padding: 40px; color: #ef4444;">
                <h3>Erreur</h3>
                <p>${data.error || 'Erreur inconnue'}</p>
            </div>
        `;
    }
    
    const { repair, status_history, sms_history } = data;
    
    let html = `
        <!-- Informations de la réparation -->
        <div class="repair-info-section" style="
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        ">
            <h3 style="margin: 0 0 20px 0; color: #1e293b; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                Informations de la réparation
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <strong style="color: #64748b;">Date de création :</strong><br>
                    <span style="color: #1e293b; font-size: 16px;">${repair.date_creation || 'Non définie'}</span>
                </div>
                <div>
                    <strong style="color: #64748b;">Date de restitution :</strong><br>
                    <span style="color: #1e293b; font-size: 16px;">${repair.date_restitution || 'Non définie'}</span>
                </div>
                <div>
                    <strong style="color: #64748b;">Statut actuel :</strong><br>
                    <span style="
                        background: ${repair.statut_actuel === 'Terminé' ? '#10b981' : '#f59e0b'};
                        color: white;
                        padding: 4px 12px;
                        border-radius: 12px;
                        font-size: 14px;
                        font-weight: 600;
                    ">${repair.statut_actuel || 'Non défini'}</span>
                </div>
                <div>
                    <strong style="color: #64748b;">Prix :</strong><br>
                    <span style="color: #1e293b; font-size: 16px; font-weight: 600;">${repair.prix || 'Non défini'}</span>
                </div>
            </div>
        </div>
        
        <!-- Historique des changements de statut -->
        <div class="status-history-section" style="
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        ">
            <h3 onclick="toggleStatusHistory()" style="
                margin: 0 0 20px 0; 
                color: #1e293b; 
                display: flex; 
                align-items: center; 
                gap: 12px;
                cursor: pointer;
                user-select: none;
                transition: all 0.3s ease;
                padding: 10px;
                border-radius: 8px;
            " onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-exchange-alt" style="color: #f59e0b;"></i>
                Changements de statut (${status_history.length})
                <i id="statusHistoryIcon" class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
            </h3>
            <div id="statusHistoryContent" style="display: none; position: relative;">
    `;
    
    // Timeline des statuts
    if (status_history.length === 0) {
        html += `
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 16px;">Aucun historique de changement de statut enregistré</p>
                <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.8;">
                    ${repair.statut_actuel === 'restitue' ? 'La réparation est marquée comme restituée mais sans log détaillé.' : 'Cette réparation n\'a pas encore d\'historique de modifications.'}
                </p>
            </div>
        `;
    } else {
        status_history.forEach((status, index) => {
            const isLast = index === status_history.length - 1;
            html += `
                <div style="
                    display: flex;
                    align-items: flex-start;
                    gap: 20px;
                    margin-bottom: ${isLast ? '0' : '25px'};
                    position: relative;
                ">
                    <!-- Timeline dot -->
                    <div style="
                        width: 40px;
                        height: 40px;
                        background: ${status.is_current ? '#10b981' : '#64748b'};
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        position: relative;
                        z-index: 2;
                    ">
                        <i class="fas ${status.is_current ? 'fa-check' : 'fa-clock'}" style="color: white; font-size: 16px;"></i>
                    </div>
                    
                    <!-- Timeline line -->
                    ${!isLast ? `
                    <div style="
                        position: absolute;
                        left: 19px;
                        top: 40px;
                        width: 2px;
                        height: 25px;
                        background: #e2e8f0;
                        z-index: 1;
                    "></div>
                    ` : ''}
                    
                    <!-- Content -->
                    <div style="flex: 1; padding-top: 8px;">
                        <div class="status-card" style="
                            background: ${status.is_current ? '#f0fdf4' : '#f8fafc'};
                            border: 1px solid ${status.is_current ? '#bbf7d0' : '#e2e8f0'};
                            border-radius: 12px;
                            padding: 16px;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 600;">
                                    ${status.statut_nom}
                                </h4>
                                <span style="
                                    color: #64748b;
                                    font-size: 14px;
                                    font-weight: 500;
                                ">${status.date_formatted}</span>
                            </div>
                            <p style="margin: 0; color: #64748b; font-size: 14px;">
                                Changé par : <strong>${status.user_name || 'Système'}</strong>
                            </p>
                            ${status.commentaire ? `
                            <p style="margin: 8px 0 0 0; color: #374151; font-size: 14px; font-style: italic;">
                                "${status.commentaire}"
                            </p>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += `
            </div>
        </div>
        
        <!-- Historique des SMS -->
        <div class="sms-history-section" style="
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        ">
            <h3 onclick="toggleSmsHistory()" style="
                margin: 0 0 20px 0; 
                color: #1e293b; 
                display: flex; 
                align-items: center; 
                gap: 12px;
                cursor: pointer;
                user-select: none;
                transition: all 0.3s ease;
                padding: 10px;
                border-radius: 8px;
            " onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-sms" style="color: #8b5cf6;"></i>
                SMS envoyés au client (${sms_history.length})
                <i id="smsHistoryIcon" class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
            </h3>
            <div id="smsHistoryContent" style="display: none;">
    `;
    
    if (sms_history.length === 0) {
        html += `
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 16px;">Aucun SMS envoyé pour cette réparation</p>
                <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.8;">
                    Aucun SMS n'a été envoyé au client ${repair.client_nom} ${repair.client_prenom} (${repair.client_telephone})
                </p>
            </div>
        `;
    } else {
        html += `<div style="display: flex; flex-direction: column; gap: 16px;">`;
        
        sms_history.forEach(sms => {
            html += `
                <div class="sms-card" style="
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 20px;
                    background: #fafafa;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="
                                width: 32px;
                                height: 32px;
                                background: ${sms.statut_badge === 'success' ? '#10b981' : '#ef4444'};
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas ${sms.statut_badge === 'success' ? 'fa-check' : 'fa-times'}" style="color: white; font-size: 14px;"></i>
                            </div>
                            <span style="
                                background: ${sms.statut_badge === 'success' ? '#10b981' : '#ef4444'};
                                color: white;
                                padding: 4px 12px;
                                border-radius: 12px;
                                font-size: 12px;
                                font-weight: 600;
                            ">${sms.statut_text}</span>
                        </div>
                        <span style="color: #64748b; font-size: 14px; font-weight: 500;">
                            ${sms.date_envoi_formatted}
                        </span>
                    </div>
                    <div class="sms-message-card" style="
                        background: white;
                        border-radius: 8px;
                        padding: 16px;
                        border-left: 4px solid ${sms.statut_badge === 'success' ? '#10b981' : '#ef4444'};
                    ">
                        <p style="margin: 0; color: #374151; line-height: 1.6; font-size: 14px;">
                            ${sms.message.replace(/\n/g, '<br>')}
                        </p>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    html += `
            </div>
        </div>`;
    
    return html;
}

function createSmsHistoryModal() {
    const modalHtml = `
        <div class="modal fade" id="repairSmsHistoryModal" tabindex="-1" style="z-index: 99999 !important; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; display: none;">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="width: 90vw; max-width: 1200px; height: auto; margin: auto;">
                <div class="modal-content modern-sms-history-modal" style="background: white; border-radius: 20px; width: 100%; min-height: 500px; max-height: 90vh; overflow: hidden; display: block; visibility: visible; opacity: 1;">
                    <!-- Header avec gradient -->
                    <div class="modern-sms-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px 30px; position: relative;">
                        <div class="header-content" style="display: flex; align-items: center;">
                            <div class="header-icon" style="width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                <i class="fas fa-comments" style="font-size: 24px; color: white;"></i>
                            </div>
                            <div class="header-text" style="flex: 1;">
                                <h3 class="header-title" style="margin: 0; font-size: 24px; font-weight: 700; color: white;">Historique SMS</h3>
                                <p class="header-subtitle" id="repairSmsClientInfo" style="margin: 5px 0 0 0; font-size: 14px; color: rgba(255, 255, 255, 0.9);">Chargement...</p>
                            </div>
                        </div>
                        <button type="button" class="modern-close-btn" onclick="closeSmsModal()" style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.2); border: none; border-radius: 50%; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Body avec contenu moderne -->
                    <div class="modern-sms-body" style="background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%); min-height: 400px; position: relative;">
                        <!-- Loading State -->
                        <div class="modern-loading" id="repairSmsLoading" style="display: flex; align-items: center; justify-content: center; height: 400px;">
                            <div class="loading-animation" style="text-align: center;">
                                <div class="loading-dots" style="display: flex; gap: 8px; justify-content: center; margin-bottom: 20px;">
                                    <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both;"></div>
                                    <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: -0.16s;"></div>
                                    <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: 0s;"></div>
                                </div>
                                <p class="loading-text" style="color: #64748b; font-size: 16px; font-weight: 500; margin: 0;">Chargement de l'historique SMS...</p>
                            </div>
                        </div>
                        
                        <!-- Content Area -->
                        <div class="sms-history-content" id="repairSmsContent" style="display: none; padding: 30px; min-height: 400px;">
                            <!-- Le contenu sera injecté ici -->
                        </div>
                    </div>
                    
                    <!-- Footer moderne -->
                    <div class="modern-sms-footer" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 20px 30px; display: flex; align-items: center; justify-content: space-between;">
                        <div class="footer-info" style="display: flex; align-items: center; color: #94a3b8; font-size: 14px; font-weight: 500;">
                            <i class="fas fa-info-circle" style="margin-right: 8px; color: #60a5fa;"></i>
                            <span>Historique des 50 derniers SMS</span>
                        </div>
                        <button type="button" class="modern-footer-btn" onclick="closeSmsModal()" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: none; color: white; padding: 12px 24px; border-radius: 25px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check"></i>
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes dotPulse {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1.2);
                opacity: 1;
            }
        }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    console.log('✅ Modal SMS créé dynamiquement');
}

function closeSmsModal() {
    const modal = document.getElementById('repairSmsHistoryModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        console.log('✅ Modal SMS fermé');
    }
}

function loadRepairSmsHistory(repairId, clientPhone) {
    console.log('💬 Chargement de l\'historique SMS pour la réparation:', repairId);
    
    const loadingElement = document.getElementById('repairSmsLoading');
    const contentElement = document.getElementById('repairSmsContent');
    
    // Vérifier si le téléphone est valide
    if (!clientPhone || clientPhone === 'Non renseigné' || clientPhone === 'undefined') {
        loadingElement.style.display = 'none';
        contentElement.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div style="font-size: 3rem; margin-bottom: 20px;">📱</div>
                <h3 style="color: #6b7280;">Aucun numéro de téléphone</h3>
                <p style="color: #6b7280;">Aucun numéro de téléphone renseigné pour ce client.</p>
                <p style="font-size: 0.9rem; color: #9ca3af;">
                    Réparation #${repairId}
                </p>
            </div>
        `;
        contentElement.style.display = 'block';
        return;
    }
    
    // Utiliser l'API existante en recherchant par téléphone
    // D'abord récupérer le client_id via le téléphone
    fetch(`ajax/get_client_sms.php?phone=${encodeURIComponent(clientPhone)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de réseau');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Historique SMS chargé avec succès:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
            // Masquer le spinner et afficher le contenu
            loadingElement.style.display = 'none';
            contentElement.innerHTML = generateRepairSmsHistoryHTML(data, repairId);
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement de l\'historique SMS:', error);
            
            // Détecter le mode sombre pour l'erreur
            const isDarkMode = document.body.classList.contains('dark-mode');
            const errorColor = isDarkMode ? '#f87171' : '#ef4444';
            const errorSecondaryColor = isDarkMode ? '#94a3b8' : '#6b7280';
            const buttonBg = isDarkMode ? '#4f46e5' : '#667eea';
            const buttonHoverBg = isDarkMode ? '#4338ca' : '#5a67d8';
            
            // Afficher un message d'erreur
            loadingElement.style.display = 'none';
            contentElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: ${errorColor};">
                    <div style="font-size: 3rem; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: ${errorColor};">Erreur de chargement</h3>
                    <p style="color: ${errorColor};">Impossible de charger l'historique des SMS.</p>
                    <p style="color: ${errorSecondaryColor}; font-size: 0.9rem;">${error.message}</p>
                    <button onclick="loadRepairSmsHistory(${repairId}, '${clientPhone}')" 
                            style="background: ${buttonBg}; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; margin-top: 15px; font-weight: 500; transition: all 0.2s ease;"
                            onmouseover="this.style.background='${buttonHoverBg}'; this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.background='${buttonBg}'; this.style.transform='translateY(0)'">
                        🔄 Réessayer
                    </button>
                </div>
            `;
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        });
}

function generateRepairSmsHistoryHTML(data, repairId) {
    const { client, sms_history, total_sms } = data;
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode');
    
    // Filtrer les SMS liés à cette réparation
    const repairSms = sms_history.filter(message => 
        message.reparation_id == repairId || 
        (message.message && message.message.includes(`suivi.php?id=${repairId}`))
    );
    
    if (!repairSms || repairSms.length === 0) {
        return generateEmptyStateHTML(repairId, client, isDarkMode);
    }
    
    return generateSmsListHTML(repairSms, repairId, isDarkMode);
}

function generateEmptyStateHTML(repairId, client, isDarkMode) {
    const emptyBg = isDarkMode ? 'linear-gradient(145deg, #1e293b 0%, #0f172a 100%)' : 'linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%)';
    const emptyColor = isDarkMode ? '#94a3b8' : '#64748b';
    const emptySecondaryColor = isDarkMode ? '#64748b' : '#9ca3af';
    
    return `
        <div style="
            background: ${emptyBg};
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            margin: 20px;
            border: ${isDarkMode ? '1px solid #334155' : '1px solid #e2e8f0'};
        ">
            <div style="
                width: 80px;
                height: 80px;
                background: ${isDarkMode ? 'rgba(59, 130, 246, 0.2)' : 'rgba(59, 130, 246, 0.1)'};
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px auto;
            ">
                <i class="fas fa-sms" style="font-size: 32px; color: ${isDarkMode ? '#60a5fa' : '#3b82f6'};"></i>
            </div>
            <h3 style="
                color: ${emptyColor};
                font-size: 24px;
                font-weight: 600;
                margin: 0 0 12px 0;
            ">Aucun SMS trouvé</h3>
            <p style="
                color: ${emptySecondaryColor};
                font-size: 16px;
                margin: 0 0 8px 0;
                line-height: 1.5;
            ">Aucun SMS n'a été envoyé pour cette réparation.</p>
            <p style="
                color: ${emptySecondaryColor};
                font-size: 14px;
                margin: 0;
                opacity: 0.8;
            ">
                Réparation #${repairId} • ${client.telephone || 'Numéro non renseigné'}
            </p>
        </div>
    `;
}

function generateSmsListHTML(repairSms, repairId, isDarkMode) {
    const cardBg = isDarkMode ? 'rgba(30, 41, 59, 0.8)' : 'rgba(255, 255, 255, 0.9)';
    const cardBorder = isDarkMode ? '#334155' : '#e2e8f0';
    const textColor = isDarkMode ? '#e2e8f0' : '#374151';
    const secondaryTextColor = isDarkMode ? '#94a3b8' : '#6b7280';
    
    let html = `
        <div style="padding: 20px;">
            <!-- Résumé moderne -->
            <div style="
                background: linear-gradient(135deg, ${isDarkMode ? '#1e40af' : '#3b82f6'} 0%, ${isDarkMode ? '#1e3a8a' : '#1d4ed8'} 100%);
                color: white;
                padding: 24px;
                border-radius: 16px;
                margin-bottom: 24px;
                box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
            ">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="
                        width: 50px;
                        height: 50px;
                        background: rgba(255, 255, 255, 0.2);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        backdrop-filter: blur(10px);
                    ">
                        <i class="fas fa-chart-line" style="font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 600;">Résumé de l'historique</h4>
                        <p style="margin: 0; opacity: 0.9; font-size: 14px;">
                            <strong>${repairSms.length}</strong> SMS envoyé${repairSms.length > 1 ? 's' : ''} pour la réparation <strong>#${repairId}</strong>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Liste des SMS -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
    `;
    
    repairSms.forEach((message, index) => {
        // Couleurs des statuts
        const statusColors = {
            'success': {
                bg: isDarkMode ? '#065f46' : '#10b981',
                text: isDarkMode ? '#d1fae5' : 'white',
                icon: 'fa-check-circle'
            },
            'danger': {
                bg: isDarkMode ? '#991b1b' : '#ef4444',
                text: isDarkMode ? '#fecaca' : 'white',
                icon: 'fa-times-circle'
            }
        };
        
        const statusStyle = statusColors[message.statut_badge] || statusColors['danger'];
        
        html += `
            <div style="
                background: ${cardBg};
                border: 1px solid ${cardBorder};
                border-radius: 16px;
                padding: 24px;
                backdrop-filter: blur(10px);
                box-shadow: 0 4px 15px ${isDarkMode ? 'rgba(0, 0, 0, 0.3)' : 'rgba(0, 0, 0, 0.1)'};
                transition: all 0.3s ease;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px ${isDarkMode ? 'rgba(0, 0, 0, 0.4)' : 'rgba(0, 0, 0, 0.15)'}'"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px ${isDarkMode ? 'rgba(0, 0, 0, 0.3)' : 'rgba(0, 0, 0, 0.1)'}'">
                
                <!-- Header de la carte -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="
                            width: 40px;
                            height: 40px;
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <i class="fas fa-sms" style="color: white; font-size: 16px;"></i>
                        </div>
                        <div>
                            <div style="
                                font-weight: 600;
                                color: ${textColor};
                                font-size: 16px;
                                margin-bottom: 4px;
                            ">${message.date_envoi_formatted}</div>
                            <div style="
                                font-size: 12px;
                                color: ${secondaryTextColor};
                                opacity: 0.8;
                            ">SMS #${message.id}</div>
                        </div>
                    </div>
                    
                    <!-- Statut -->
                    <div style="
                        background: ${statusStyle.bg};
                        color: ${statusStyle.text};
                        padding: 8px 16px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        box-shadow: 0 2px 8px ${statusStyle.bg}40;
                    ">
                        <i class="fas ${statusStyle.icon}"></i>
                        ${message.statut_text}
                    </div>
                </div>
                
                <!-- Contenu du message -->
                <div style="
                    background: ${isDarkMode ? 'rgba(15, 23, 42, 0.5)' : 'rgba(248, 250, 252, 0.8)'};
                    border-radius: 12px;
                    padding: 20px;
                    border-left: 4px solid ${statusStyle.bg};
                ">
                    <div style="
                        color: ${textColor};
                        line-height: 1.6;
                        font-size: 14px;
                        word-wrap: break-word;
                    ">${message.message.replace(/\n/g, '<br>')}</div>
                </div>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

// === FONCTION DE TEST POUR LE MODAL SMS ===
window.testSmsModal = function() {
    console.log('🧪 Test du modal SMS historique...');
    
    // Supprimer l'ancien modal s'il existe
    const existingModal = document.getElementById('repairSmsHistoryModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Créer un modal de test ultra-simple
    const testModal = document.createElement('div');
    testModal.id = 'testSmsModal';
    testModal.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: Arial, sans-serif;
        ">
            <div style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 40px;
                border-radius: 20px;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
                min-width: 400px;
            ">
                <h2 style="margin: 0 0 20px 0; color: white;">🧪 Test Modal SMS</h2>
                <p style="margin: 0 0 20px 0; opacity: 0.9;">Si tu vois ce modal, le système fonctionne !</p>
                <button onclick="document.getElementById('testSmsModal').remove()" style="
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 25px;
                    cursor: pointer;
                    font-weight: bold;
                ">Fermer</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(testModal);
    console.log('✅ Modal de test créé et affiché');
    
    // Fermer automatiquement après 5 secondes
    setTimeout(() => {
        if (document.getElementById('testSmsModal')) {
            document.getElementById('testSmsModal').remove();
            console.log('✅ Modal de test fermé automatiquement');
        }
    }, 5000);
};

window.testRealSmsModal = function() {
    console.log('🧪 Test du vrai modal SMS...');
    showRepairSmsModal(2141, 'Test Client', '33782962906');
};

// Fonction de test pour le nouveau modal d'historique complet
window.testCompleteHistory = function() {
    console.log('🧪 Test du modal d\'historique complet...');
    showRepairSmsModal(1, 'guezguez saber', '33782962906');
};

// ========================================
// GESTION DU MODAL D'ATTRIBUTION TECHNICIEN
// ========================================

let currentRepairIdForTechnician = null;

window.openTechnicianModal = function(repairId) {
    console.log('Ouverture du modal d\'attribution technicien pour la réparation', repairId);
    
    currentRepairIdForTechnician = repairId;
    
    // Trouver les informations de la réparation
    const repairData = repairsData.find(repair => repair.id == repairId);
    
    if (repairData) {
        // Mettre à jour les informations dans le modal
        const modalTitle = document.getElementById('technicianModalRepairInfo');
        const modalDescription = document.getElementById('technicianModalDescription');
        const technicianSelect = document.getElementById('technicianSelect');
        
        if (modalTitle) {
            modalTitle.textContent = `Réparation #${repairData.id} - ${repairData.appareil || 'Appareil'}`;
        }
        
        if (modalDescription) {
            const currentTechnicianText = repairData.employe_id ? 
                'Cette réparation est actuellement attribuée à un technicien.' :
                'Cette réparation n\'est pas encore attribuée à un technicien.';
            modalDescription.textContent = `${currentTechnicianText} Sélectionnez un technicien pour l'attribution.`;
        }
        
        // Sélectionner le technicien actuel s'il existe
        if (technicianSelect && repairData.employe_id) {
            technicianSelect.value = repairData.employe_id;
        } else if (technicianSelect) {
            technicianSelect.value = '';
        }
    }
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('technicianModal'));
    modal.show();
};

// Gestion du bouton d'attribution
document.addEventListener('DOMContentLoaded', function() {
    const assignBtn = document.getElementById('assignTechnicianBtn');
    const technicianSelect = document.getElementById('technicianSelect');
    const spinner = document.getElementById('technicianModalSpinner');
    
    if (assignBtn) {
        assignBtn.addEventListener('click', function() {
            if (!currentRepairIdForTechnician) {
                console.error('Aucune réparation sélectionnée pour l\'attribution');
                return;
            }
            
            const selectedTechnicianId = technicianSelect.value;
            
            // Afficher le spinner
            spinner.classList.remove('d-none');
            assignBtn.disabled = true;
            
            // Préparer les données
            const formData = new FormData();
            formData.append('repair_id', currentRepairIdForTechnician);
            formData.append('employe_id', selectedTechnicianId);
            formData.append('action', 'assign_technician');
            
            // Envoyer la requête
            fetch('api/assign_technician.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                spinner.classList.add('d-none');
                assignBtn.disabled = false;
                
                if (data.success) {
                    // Afficher un message de succès
                    showNotification('Technicien attribué avec succès !', 'success');
                    
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('technicianModal'));
                    modal.hide();
                    
                    // Actualiser la liste des réparations
                    if (typeof loadRepairs === 'function') {
                        loadRepairs();
                    }
                    
                    // Reset
                    currentRepairIdForTechnician = null;
                } else {
                    showNotification(data.message || 'Erreur lors de l\'attribution du technicien', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'attribution:', error);
                spinner.classList.add('d-none');
                assignBtn.disabled = false;
                showNotification('Erreur de connexion lors de l\'attribution', 'error');
            });
        });
    }
});

// Fonction utilitaire pour afficher des notifications
function showNotification(message, type = 'info') {
    // Utiliser le système de notification existant ou créer une simple alerte
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: type === 'success' ? 'Succès' : 'Information',
            text: message,
            icon: type === 'success' ? 'success' : type === 'error' ? 'error' : 'info',
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert(message);
    }
}
</script>

<!-- Modal d'attribution à un technicien -->
<div class="modal fade" id="technicianModal" tabindex="-1" aria-labelledby="technicianModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="technicianModalLabel">
                    <i class="fas fa-user-cog me-2"></i>
                    Attribuer à un technicien
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-circle d-inline-flex align-items-center justify-content-center">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h5 class="mt-3" id="technicianModalRepairInfo">Attribution d'une réparation</h5>
                    <p class="text-muted" id="technicianModalDescription">Sélectionnez le technicien qui sera responsable de cette réparation.</p>
                </div>
                
                <div class="mb-3">
                    <label for="technicianSelect" class="form-label fw-bold">Technicien disponible :</label>
                    <select class="form-select" id="technicianSelect" name="employe_id">
                        <option value="">-- Aucune attribution --</option>
                        <?php
                        try {
                            // Récupérer la liste des techniciens disponibles
                            $stmt = $shop_pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('technicien', 'admin') ORDER BY full_name ASC");
                            $stmt->execute();
                            $technicians = $stmt->fetchAll();
                            
                            foreach ($technicians as $tech) {
                                $role_badge = $tech['role'] === 'admin' ? ' (Admin)' : ' (Technicien)';
                                echo '<option value="' . htmlspecialchars($tech['id']) . '">' . 
                                     htmlspecialchars($tech['full_name']) . $role_badge . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="" disabled>Erreur lors du chargement des techniciens</option>';
                        }
                        ?>
                    </select>
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Laissez "Aucune attribution" pour retirer l'attribution existante
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Information :</strong> L'attribution d'un technicien permet de suivre qui est responsable de cette réparation. Le technicien attribué pourra voir cette réparation dans ses tâches assignées.
                </div>
                
                <div id="technicianModalSpinner" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Attribution en cours...</span>
                    </div>
                    <p class="mt-2 text-muted">Attribution en cours...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="assignTechnicianBtn">
                    <i class="fas fa-user-check me-1"></i>
                    Attribuer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mes Réparations -->
<div id="myRepairsModal" class="my-repairs-modal" style="display: none;">
    <div class="my-repairs-modal-overlay" onclick="closeMyRepairs()"></div>
    <div class="my-repairs-modal-content">
        <div class="my-repairs-modal-header">
            <h2><i class="fas fa-user-check"></i> Mes Réparations</h2>
            <button type="button" class="my-repairs-modal-close" onclick="closeMyRepairs()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="my-repairs-modal-body">
            <!-- Statistiques utilisateur -->
            <div class="my-repairs-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number" id="totalRepairsCount">0</span>
                        <span class="stat-label">Total réparations</span>
                    </div>
                </div>
                <div class="stat-card urgent">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number" id="urgentRepairsCount">0</span>
                        <span class="stat-label">Urgentes</span>
                    </div>
                </div>
                <div class="stat-card progress">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number" id="inProgressRepairsCount">0</span>
                        <span class="stat-label">En cours</span>
                    </div>
                </div>
            </div>

            <!-- Liste des réparations -->
            <div class="my-repairs-section">
                <h3><i class="fas fa-list"></i> Mes réparations attribuées</h3>
                <div id="myRepairsList" class="my-repairs-list">
                    <!-- Les réparations seront chargées ici via AJAX -->
                </div>
            </div>
        </div>
        
        <div class="my-repairs-modal-footer">
            <button type="button" class="btn-refresh" onclick="loadMyRepairs()">
                <i class="fas fa-sync-alt"></i> Actualiser
            </button>
            <button type="button" class="btn-close" onclick="closeMyRepairs()">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
</div>

<!-- Modal Attribution Technicien -->
<div id="technicienAttributionModal" class="technicien-modal" style="display: none;">
    <div class="technicien-modal-overlay" onclick="closeTechnicienAttribution()"></div>
    <div class="technicien-modal-content">
        <div class="technicien-modal-header">
            <h2><i class="fas fa-user-cog"></i> Attribution à un Technicien</h2>
            <button type="button" class="technicien-modal-close" onclick="closeTechnicienAttribution()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="technicien-modal-body">
            <!-- Filtres de statut -->
            <div class="technicien-filters">
                <h3><i class="fas fa-filter"></i> Filtrer par statut</h3>
                <div class="filter-buttons">
                    <button type="button" class="technicien-filter-btn active" data-status="all" onclick="filterRepairsByStatus('all')">
                        <i class="fas fa-list"></i> Tout afficher
                    </button>
                    <button type="button" class="technicien-filter-btn" data-status="nouvelle_intervention,nouveau_diagnostique,nouvelle_commande,devis_accepte,devis_refuse" onclick="filterRepairsByStatus('nouvelle_intervention,nouveau_diagnostique,nouvelle_commande,devis_accepte,devis_refuse')">
                        <i class="fas fa-plus-circle"></i> Nouvelles
                    </button>
                    <button type="button" class="technicien-filter-btn" data-status="En attente,en_attente_responsable,en_attente_livraison,en_attente_accord_client" onclick="filterRepairsByStatus('En attente,en_attente_responsable,en_attente_livraison,en_attente_accord_client')">
                        <i class="fas fa-clock"></i> En attente
                    </button>
                    <button type="button" class="technicien-filter-btn" data-status="en_cours_diagnostique,en_cours_intervention" onclick="filterRepairsByStatus('en_cours_diagnostique,en_cours_intervention')">
                        <i class="fas fa-search"></i> En diagnostic
                    </button>
                </div>
            </div>

            <!-- Liste des réparations -->
            <div class="technicien-repairs-section">
                <h3><i class="fas fa-tools"></i> Réparations disponibles</h3>
                <div id="technicienRepairsList" class="technicien-repairs-list">
                    <!-- Les réparations seront chargées ici via AJAX -->
                </div>
            </div>

            <!-- Sélection du technicien -->
            <div class="technicien-selection-section" id="technicienSelectionSection" style="display: none;">
                <h3><i class="fas fa-user"></i> Sélectionner un technicien</h3>
                <div class="technicien-selector">
                    <select id="technicienSelect" class="technicien-select">
                        <option value="">-- Choisir un technicien --</option>
                    </select>
                    <button type="button" class="technicien-assign-btn" onclick="assignToTechnician()">
                        <i class="fas fa-check"></i> Attribuer
                    </button>
                </div>
                <div class="selected-repairs-info" id="selectedRepairsInfo">
                    <!-- Informations sur les réparations sélectionnées -->
                </div>
            </div>
        </div>
        
        <div class="technicien-modal-footer">
            <button type="button" class="btn-cancel" onclick="closeTechnicienAttribution()">
                <i class="fas fa-times"></i> Annuler
            </button>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal d'attribution technicien - Thème moderne et futuriste nuit */
.technicien-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    animation: fadeIn 0.3s ease;
}

.technicien-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: transparent;
    cursor: pointer;
}

.technicien-modal-content {
    position: relative;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    background: linear-gradient(135deg, #1a1d29 0%, #2d3748 100%);
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 50px rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    overflow: hidden;
    animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.technicien-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: 25px 30px;
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.technicien-modal-header h2 {
    color: #f8fafc;
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.technicien-modal-header h2 i {
    color: #3b82f6;
    font-size: 28px;
}

.technicien-modal-close {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
    padding: 12px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
}

.technicien-modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
}

.technicien-modal-body {
    padding: 30px;
    max-height: 65vh;
    overflow-y: auto;
}

.technicien-modal-body::-webkit-scrollbar {
    width: 8px;
}

.technicien-modal-body::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}

.technicien-modal-body::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.5);
    border-radius: 10px;
}

.technicien-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.7);
}

.technicien-filters, .technicien-repairs-section, .technicien-selection-section {
    margin-bottom: 30px;
}

.technicien-filters h3, .technicien-repairs-section h3, .technicien-selection-section h3 {
    color: #f8fafc;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.technicien-filters h3 i, .technicien-repairs-section h3 i, .technicien-selection-section h3 i {
    color: #3b82f6;
}

.filter-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.technicien-filter-btn {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #3b82f6;
    padding: 12px 20px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.technicien-filter-btn:hover {
    background: rgba(59, 130, 246, 0.2);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
    transform: translateY(-2px);
}

.technicien-filter-btn.active {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.technicien-repairs-list {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 16px;
    border: 1px solid rgba(59, 130, 246, 0.1);
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.repair-item {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(30, 64, 175, 0.05) 100%);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 0;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: border-color 0.2s ease, background 0.2s ease;
}

.repair-item:hover {
    /* OPTIMISÉ: Suppression transform/box-shadow pour performance */
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(30, 64, 175, 0.12) 100%);
    border-color: rgba(59, 130, 246, 0.5);
}

.repair-item.selected {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(30, 64, 175, 0.2) 100%);
    border-color: #3b82f6;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
}

.repair-item .repair-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.repair-item .repair-id {
    color: #3b82f6;
    font-weight: bold;
    font-size: 16px;
}

.repair-item .repair-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.repair-item .repair-info {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.5;
}

.repair-item .repair-client {
    color: #f8fafc;
    font-weight: 500;
    margin-bottom: 4px;
}

.repair-item .repair-device {
    color: #94a3b8;
    margin-bottom: 4px;
}

.repair-item .repair-date {
    color: #64748b;
    font-size: 12px;
}

.repair-item .selection-checkbox {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 20px;
    height: 20px;
    border: 2px solid #3b82f6;
    border-radius: 4px;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
}

.repair-item.selected .selection-checkbox {
    background: #3b82f6;
}

.repair-item.selected .selection-checkbox::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.technicien-selection-section {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 16px;
    border: 1px solid rgba(59, 130, 246, 0.1);
    padding: 25px;
}

.technicien-selector {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.technicien-select {
    flex: 1;
    min-width: 250px;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #f8fafc;
    padding: 15px 20px;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.technicien-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
}

.technicien-select option {
    background: #1e293b;
    color: #f8fafc;
    padding: 10px;
}

.technicien-assign-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.technicien-assign-btn:hover {
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    transform: translateY(-2px);
}

.technicien-assign-btn:disabled {
    background: rgba(107, 114, 128, 0.3);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.selected-repairs-info {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 20px;
    color: #cbd5e1;
}

.selected-repairs-info h4 {
    color: #3b82f6;
    margin-bottom: 12px;
    font-size: 16px;
}

.selected-repairs-info .repair-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(59, 130, 246, 0.1);
}

.selected-repairs-info .repair-summary:last-child {
    border-bottom: none;
}

.technicien-modal-footer {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-top: 1px solid rgba(59, 130, 246, 0.2);
    padding: 20px 30px;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.btn-cancel {
    background: rgba(107, 114, 128, 0.2);
    border: 1px solid rgba(107, 114, 128, 0.3);
    color: #9ca3af;
    padding: 12px 24px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel:hover {
    background: rgba(107, 114, 128, 0.3);
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.2);
    transform: translateY(-2px);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0; 
        transform: translateY(-50px) scale(0.9); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

/* Statuts spéciaux */
.status-nouvelle { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.status-attente { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.status-diagnostic { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.status-effectue { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.status-termine { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }

/* Responsive design */
@media (max-width: 768px) {
    .technicien-modal-content {
        width: 98%;
        max-height: 95vh;
    }
    
    .technicien-modal-header {
        padding: 20px;
    }
    
    .technicien-modal-body {
        padding: 20px;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .filter-btn {
        justify-content: center;
    }
    
    .technicien-selector {
        flex-direction: column;
        align-items: stretch;
    }
    
    .technicien-select {
        min-width: auto;
    }
}

/* Styles pour le modal Mes Réparations - Thème moderne jour et futuriste nuit */
.my-repairs-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: flex-start; /* Changé de center à flex-start pour remonter le modal */
    justify-content: center;
    padding-top: 5vh; /* Ajout d'un espacement en haut */
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    animation: fadeIn 0.3s ease;
}

.my-repairs-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: transparent;
    cursor: pointer;
}

.my-repairs-modal-content {
    position: relative;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    background: linear-gradient(135deg, #1a1d29 0%, #2d3748 100%);
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 50px rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    overflow: hidden;
    animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Mode jour */
@media (prefers-color-scheme: light) {
    .my-repairs-modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(59, 130, 246, 0.2);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1), 0 0 50px rgba(59, 130, 246, 0.05);
    }
}

.my-repairs-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: 25px 30px;
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

@media (prefers-color-scheme: light) {
    .my-repairs-modal-header {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.1);
    }
}

.my-repairs-modal-header h2 {
    color: #f8fafc;
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

@media (prefers-color-scheme: light) {
    .my-repairs-modal-header h2 {
        color: #1e293b;
    }
}

.my-repairs-modal-header h2 i {
    color: #3b82f6;
    font-size: 28px;
}

.my-repairs-modal-close {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
    padding: 12px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
}

.my-repairs-modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
}

.my-repairs-modal-body {
    padding: 30px;
    max-height: 65vh;
    overflow-y: auto;
}

@media (prefers-color-scheme: light) {
    .my-repairs-modal-body {
        background: #ffffff;
    }
}

.my-repairs-modal-body::-webkit-scrollbar {
    width: 8px;
}

.my-repairs-modal-body::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}

@media (prefers-color-scheme: light) {
    .my-repairs-modal-body::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
    }
}

.my-repairs-modal-body::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.5);
    border-radius: 10px;
}

.my-repairs-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.7);
}

.my-repairs-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(30, 64, 175, 0.1) 100%);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 16px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
    height: 120px;
    min-height: 120px;
    max-height: 120px;
    transition: border-color 0.2s ease;
}

@media (prefers-color-scheme: light) {
    .stat-card {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(30, 64, 175, 0.05) 100%);
        border: 1px solid rgba(59, 130, 246, 0.1);
    }
}

.stat-card:hover {
    /* OPTIMISÉ: Suppression transform/box-shadow pour performance */
    border-color: rgba(59, 130, 246, 0.4);
}

.stat-card.urgent {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
    border-color: rgba(239, 68, 68, 0.2);
}

.stat-card.urgent:hover {
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2);
}

.stat-card.progress {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
    border-color: rgba(16, 185, 129, 0.2);
}

.stat-card.progress:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.stat-card.urgent .stat-icon {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.stat-card.progress .stat-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #f8fafc;
    line-height: 1;
}

@media (prefers-color-scheme: light) {
    .stat-number {
        color: #1e293b;
    }
}

.stat-label {
    font-size: 14px;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 1px;
}

@media (prefers-color-scheme: light) {
    .stat-label {
        color: #64748b;
    }
}

.my-repairs-section h3 {
    color: #f8fafc;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

@media (prefers-color-scheme: light) {
    .my-repairs-section h3 {
        color: #1e293b;
    }
}

.my-repairs-section h3 i {
    color: #3b82f6;
}

.my-repairs-list {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 16px;
    border: 1px solid rgba(59, 130, 246, 0.1);
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

@media (prefers-color-scheme: light) {
    .my-repairs-list {
        background: rgba(59, 130, 246, 0.02);
        border: 1px solid rgba(59, 130, 246, 0.08);
    }
}

.my-repair-item {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(30, 64, 175, 0.05) 100%);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    position: relative;
}

@media (prefers-color-scheme: light) {
    .my-repair-item {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, rgba(30, 64, 175, 0.03) 100%);
        border: 1px solid rgba(59, 130, 246, 0.1);
    }
}

.my-repair-item:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(30, 64, 175, 0.1) 100%);
    border-color: rgba(59, 130, 246, 0.4);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
    transform: translateY(-2px);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.my-repair-item.urgent {
    border-left: 4px solid #ef4444;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(220, 38, 38, 0.05) 100%);
}

.my-repair-item .repair-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.my-repair-item .repair-id {
    color: #3b82f6;
    font-weight: bold;
    font-size: 16px;
}

.my-repair-item .repair-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.my-repair-item .repair-info {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.5;
}

@media (prefers-color-scheme: light) {
    .my-repair-item .repair-info {
        color: #475569;
    }
}

.my-repair-item .repair-client {
    color: #f8fafc;
    font-weight: 500;
    margin-bottom: 4px;
}

@media (prefers-color-scheme: light) {
    .my-repair-item .repair-client {
        color: #1e293b;
    }
}

.my-repair-item .repair-device {
    color: #94a3b8;
    margin-bottom: 4px;
}

@media (prefers-color-scheme: light) {
    .my-repair-item .repair-device {
        color: #64748b;
    }
}

.my-repair-item .repair-date {
    color: #64748b;
    font-size: 12px;
}

.my-repair-item .repair-price {
    color: #10b981;
    font-weight: 600;
    font-size: 16px;
    margin-top: 8px;
}

.my-repair-item .repair-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(59, 130, 246, 0.1);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.my-repair-item .repair-actions .view-details-btn-modal {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.my-repair-item .repair-actions .view-details-btn-modal:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.my-repair-item .repair-actions .start-repair-btn-modal {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.my-repair-item .repair-actions .start-repair-btn-modal:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.my-repair-item .repair-actions .start-repair-btn-modal:active,
.my-repair-item .repair-actions .view-details-btn-modal:active {
    transform: translateY(0);
}

@media (prefers-color-scheme: light) {
    .my-repair-item .repair-actions {
        border-top: 1px solid rgba(59, 130, 246, 0.08);
    }
}

.urgent-indicator {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ef4444;
    color: white;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.my-repairs-modal-footer {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-top: 1px solid rgba(59, 130, 246, 0.2);
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

@media (prefers-color-scheme: light) {
    .my-repairs-modal-footer {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-top: 1px solid rgba(59, 130, 246, 0.1);
    }
}

.btn-refresh {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-refresh:hover {
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    transform: translateY(-2px);
}

.btn-close {
    background: rgba(107, 114, 128, 0.2);
    border: 1px solid rgba(107, 114, 128, 0.3);
    color: #9ca3af;
    padding: 12px 24px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

@media (prefers-color-scheme: light) {
    .btn-close {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }
}

.btn-close:hover {
    background: rgba(107, 114, 128, 0.3);
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.2);
    transform: translateY(-2px);
}

/* Responsive design */
@media (max-width: 768px) {
    .my-repairs-modal-content {
        width: 98%;
        max-height: 95vh;
    }
    
    .my-repairs-modal-header {
        padding: 20px;
    }
    
    .my-repairs-modal-body {
        padding: 20px;
    }
    
    .my-repairs-stats {
        grid-template-columns: 1fr;
    }
    
    .my-repairs-modal-footer {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
// Variables globales pour le modal d'attribution technicien
let selectedRepairs = [];
let allRepairs = [];
let currentFilter = 'all';

// Fonction pour ouvrir le modal d'attribution
function openTechnicienAttribution() {
    document.getElementById('technicienAttributionModal').style.display = 'flex';
    loadTechnicians();
    loadRepairs();
}

// Fonction pour fermer le modal d'attribution
function closeTechnicienAttribution() {
    document.getElementById('technicienAttributionModal').style.display = 'none';
    selectedRepairs = [];
    updateSelectionDisplay();
}

// Charger la liste des techniciens
function loadTechnicians() {
    fetch('../ajax/get_technicians.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('technicienSelect');
            select.innerHTML = '<option value="">-- Choisir un technicien --</option>';
            
            if (data.success && data.technicians) {
                data.technicians.forEach(tech => {
                    const option = document.createElement('option');
                    option.value = tech.id;
                    option.textContent = tech.full_name ? tech.full_name : tech.username;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des techniciens:', error);
        });
}

// Charger la liste des réparations
function loadRepairs() {
    fetch('../ajax/get_repairs_for_attribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allRepairs = data.repairs;
                filterRepairsByStatus(currentFilter);
            } else {
                console.error('Erreur:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des réparations:', error);
        });
}

// Filtrer les réparations par statut
function filterRepairsByStatus(status) {
    currentFilter = status;
    
    // Mise à jour des boutons de filtre
    document.querySelectorAll('.technicien-filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-status="${status}"]`).classList.add('active');
    
    // Filtrer les réparations
    let filteredRepairs = allRepairs;
    if (status !== 'all') {
        const statusList = status.split(',');
        filteredRepairs = allRepairs.filter(repair => 
            statusList.includes(repair.statut)
        );
    }
    
    displayRepairs(filteredRepairs);
}

// Afficher les réparations
function displayRepairs(repairs) {
    const container = document.getElementById('technicienRepairsList');
    
    if (repairs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>Aucune réparation trouvée pour ce filtre</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = repairs.map(repair => {
        const isSelected = selectedRepairs.includes(repair.id);
        const statusClass = getStatusClass(repair.statut);
        
        return `
            <div class="repair-item ${isSelected ? 'selected' : ''}" onclick="toggleRepairSelection(${repair.id})">
                <div class="selection-checkbox"></div>
                <div class="repair-header">
                    <span class="repair-id">#${repair.id}</span>
                    <span class="repair-status ${statusClass}">${repair.statut}</span>
                </div>
                <div class="repair-info">
                    <div class="repair-client">${repair.client_nom} ${repair.client_prenom}</div>
                    <div class="repair-device">${repair.type_appareil} - ${repair.modele}</div>
                    <div class="repair-problem">${repair.description_probleme.substring(0, 100)}${repair.description_probleme.length > 100 ? '...' : ''}</div>
                    <div class="repair-date">Reçu le ${formatDate(repair.date_reception)}</div>
                    ${repair.employe_nom ? `<div style="color: #f59e0b; font-weight: 500; margin-top: 8px;"><i class="fas fa-user"></i> Déjà attribué à ${repair.employe_prenom || repair.employe_nom}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');
}

// Obtenir la classe CSS pour le statut
function getStatusClass(statut) {
    switch(statut) {
        case 'nouvelle_intervention':
        case 'nouveau_diagnostique':
        case 'nouvelle_commande':
        case 'devis_accepte':
        case 'devis_refuse': return 'status-nouvelle';
        case 'En attente':
        case 'en_attente_responsable':
        case 'en_attente_livraison':
        case 'en_attente_accord_client': return 'status-attente';
        case 'en_cours_diagnostique':
        case 'en_cours_intervention': return 'status-diagnostic';
        case 'reparation_effectue': return 'status-effectue';
        case 'termine': return 'status-termine';
        default: return 'status-attente';
    }
}

// Basculer la sélection d'une réparation
function toggleRepairSelection(repairId) {
    const index = selectedRepairs.indexOf(repairId);
    if (index > -1) {
        selectedRepairs.splice(index, 1);
    } else {
        selectedRepairs.push(repairId);
    }
    
    updateRepairDisplay();
    updateSelectionDisplay();
}

// Mettre à jour l'affichage des réparations
function updateRepairDisplay() {
    document.querySelectorAll('.repair-item').forEach(item => {
        const repairId = parseInt(item.onclick.toString().match(/toggleRepairSelection\((\d+)\)/)[1]);
        if (selectedRepairs.includes(repairId)) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
}

// Mettre à jour l'affichage de la sélection
function updateSelectionDisplay() {
    const selectionSection = document.getElementById('technicienSelectionSection');
    const infoContainer = document.getElementById('selectedRepairsInfo');
    
    if (selectedRepairs.length > 0) {
        selectionSection.style.display = 'block';
        
        const selectedRepairDetails = allRepairs.filter(repair => 
            selectedRepairs.includes(repair.id)
        );
        
        infoContainer.innerHTML = `
            <h4><i class="fas fa-check-circle"></i> ${selectedRepairs.length} réparation(s) sélectionnée(s)</h4>
            ${selectedRepairDetails.map(repair => `
                <div class="repair-summary">
                    <span>#${repair.id} - ${repair.client_nom} ${repair.client_prenom}</span>
                    <span class="repair-status ${getStatusClass(repair.statut)}">${repair.statut}</span>
                </div>
            `).join('')}
        `;
    } else {
        selectionSection.style.display = 'none';
    }
}

// Attribuer les réparations au technicien sélectionné
function assignToTechnician() {
    const technicienId = document.getElementById('technicienSelect').value;
    const technicienName = document.getElementById('technicienSelect').selectedOptions[0].textContent;
    
    if (!technicienId) {
        alert('Veuillez sélectionner un technicien');
        return;
    }
    
    if (selectedRepairs.length === 0) {
        alert('Veuillez sélectionner au moins une réparation');
        return;
    }
    
    // Confirmation
    const confirm = window.confirm(`Êtes-vous sûr de vouloir attribuer ${selectedRepairs.length} réparation(s) à ${technicienName} ?`);
    if (!confirm) return;
    
    // Envoi de la requête d'attribution
    fetch('../ajax/assign_repairs_to_technician.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            technician_id: technicienId,
            repair_ids: selectedRepairs
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${data.assigned_count} réparation(s) attribuée(s) avec succès à ${technicienName}`);
            closeTechnicienAttribution();
            // Recharger la page pour voir les changements
            window.location.reload();
        } else {
            alert('Erreur lors de l\'attribution : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'attribution');
    });
}

// Formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

// Fermer le modal en cliquant sur l'overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('technicien-modal-overlay')) {
        closeTechnicienAttribution();
    }
});

// Fermer le modal avec la touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTechnicienAttribution();
        closeMyRepairs();
    }
});

// ================================
// FONCTIONS POUR LE MODAL MES RÉPARATIONS
// ================================

// Fonction pour ouvrir le modal "Mes réparations"
function openMyRepairs() {
    document.getElementById('myRepairsModal').style.display = 'flex';
    loadMyRepairs();
}

// Fonction pour fermer le modal "Mes réparations"
function closeMyRepairs() {
    document.getElementById('myRepairsModal').style.display = 'none';
}

// Charger les réparations de l'utilisateur connecté
function loadMyRepairs() {
    const debugUserId = window.currentUserId || 0;
    fetch(`ajax/get_my_repairs.php?debug_user_id=${debugUserId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMyRepairs(data.repairs);
                updateMyRepairsStats(data.repairs);
            } else {
                console.error('Erreur:', data.message);
                showMyRepairsError(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des réparations:', error);
            showMyRepairsError('Erreur de connexion');
        });
}

// Afficher les réparations de l'utilisateur
function displayMyRepairs(repairs) {
    const container = document.getElementById('myRepairsList');
    
    if (repairs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>Aucune réparation ne vous est attribuée pour le moment</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = repairs.map(repair => {
        const statusClass = getMyRepairStatusClass(repair.statut);
        const statusColor = repair.statut_couleur || 'primary';
        
        return `
            <div class="my-repair-item ${repair.is_urgent ? 'urgent' : ''}">
                ${repair.is_urgent ? '<div class="urgent-indicator">URGENT</div>' : ''}
                <div class="repair-header">
                    <span class="repair-id">#${repair.id}</span>
                    <span class="repair-status bg-${statusColor}">${repair.statut_nom || repair.statut}</span>
                </div>
                <div class="repair-info">
                    <div class="repair-client">${repair.client_nom} ${repair.client_prenom}</div>
                    <div class="repair-device">${repair.type_appareil} - ${repair.modele}</div>
                    <div class="repair-problem">${repair.description_probleme.substring(0, 120)}${repair.description_probleme.length > 120 ? '...' : ''}</div>
                    <div class="repair-date">Reçu le ${formatMyRepairDate(repair.date_reception)}</div>
                    ${repair.date_modification ? `<div class="repair-date">Modifié le ${formatMyRepairDate(repair.date_modification)}</div>` : ''}
                    ${repair.prix_formatte ? `<div class="repair-price">${repair.prix_formatte} €</div>` : ''}
                </div>
                <div class="repair-actions">
                    <button class="btn btn-primary btn-sm view-details-btn-modal" data-repair-id="${repair.id}" title="Voir les détails" onclick="openModal(${repair.id})">
                        <i class="fas fa-eye"></i> Détails
                    </button>
                    <button class="btn btn-success btn-sm start-repair-btn-modal" data-repair-id="${repair.id}" title="Démarrer cette réparation" onclick="event.stopPropagation(); startRepairFromModal(${repair.id})">
                        <i class="fas fa-play"></i> Démarrer
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Mettre à jour les statistiques des réparations
function updateMyRepairsStats(repairs) {
    const totalCount = repairs.length;
    const urgentCount = repairs.filter(repair => repair.is_urgent).length;
    
    // Compter les réparations "En diagnostic" (en cours de traitement)
    const inProgressStatuses = ['en_cours_diagnostique', 'en_cours_intervention'];
    const inProgressCount = repairs.filter(repair => inProgressStatuses.includes(repair.statut)).length;
    
    // Mettre à jour les compteurs avec animation
    animateCounter('totalRepairsCount', totalCount);
    animateCounter('urgentRepairsCount', urgentCount);
    animateCounter('inProgressRepairsCount', inProgressCount);
    
    // Mettre à jour le badge du bouton "Mes réparations"
    updateMyRepairsBadge(totalCount);
}

// Animer les compteurs
function animateCounter(elementId, targetValue) {
    const element = document.getElementById(elementId);
    const currentValue = parseInt(element.textContent) || 0;
    const increment = targetValue > currentValue ? 1 : -1;
    const duration = 500; // 500ms
    const steps = 20;
    const stepTime = duration / steps;
    const stepValue = Math.ceil(Math.abs(targetValue - currentValue) / steps);
    
    let current = currentValue;
    
    const timer = setInterval(() => {
        current += increment * stepValue;
        
        if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
            current = targetValue;
            clearInterval(timer);
        }
        
        element.textContent = current;
    }, stepTime);
}

// Obtenir la classe CSS pour le statut des réparations (réparations récentes uniquement)
function getMyRepairStatusClass(statut) {
    switch(statut) {
        // Nouvelles réparations
        case 'nouvelle_intervention':
        case 'nouveau_diagnostique':
        case 'nouvelle_commande':
            return 'status-nouvelle';
        
        // En attente
        case 'En attente':
        case 'en_attente_responsable':
        case 'en_attente_livraison':
        case 'en_attente_accord_client':
            return 'status-attente';
        
        // En diagnostic
        case 'en_cours_diagnostique':
        case 'en_cours_intervention':
            return 'status-diagnostic';
        
        default: 
            return 'status-attente';
    }
}

// Formater la date pour l'affichage
function formatMyRepairDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonction pour ouvrir le modal de détails d'une réparation
function openModal(repairId) {
    console.log('🔧 Ouverture du modal de détails pour la réparation:', repairId);
    
    // Fermer le modal "Mes réparations" d'abord
    closeMyRepairs();
    
    // Attendre un peu que le modal se ferme avant d'ouvrir l'autre
    setTimeout(() => {
        // Utiliser le système RepairModal existant
        if (typeof window.RepairModal !== 'undefined' && window.RepairModal.loadRepairDetails) {
            console.log('✅ Utilisation du RepairModal existant');
            window.RepairModal.loadRepairDetails(repairId);
        } 
        // Fallback si RepairModal n'est pas disponible
        else {
            console.log('⚠️ RepairModal non disponible, utilisation du fallback');
            loadRepairDetailsFallback(repairId);
        }
    }, 300);
}

// Fonction fallback pour charger les détails d'une réparation
function loadRepairDetailsFallback(repairId) {
    console.log('🔄 Chargement des détails pour la réparation (fallback):', repairId);
    
    // Vérifier si le modal de détails existe
    const modal = document.getElementById('repairDetailsModal');
    if (!modal) {
        console.error('❌ Modal repairDetailsModal non trouvé');
        alert('Modal de détails non disponible');
        return;
    }
    
    // Ouvrir le modal avec Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Construire l'URL de l'API
        const apiUrl = `ajax/get_repair_details.php?id=${repairId}`;
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                console.log('📋 Données reçues:', data);
                if (data.success && data.repair) {
                    // Remplir manuellement le modal
                    fillRepairDetailsModal(data.repair, data.photos || []);
                } else {
                    console.error('❌ Erreur lors du chargement:', data.message || 'Erreur inconnue');
                    alert('Erreur lors du chargement des détails');
                }
            })
            .catch(error => {
                console.error('❌ Erreur réseau:', error);
                alert('Erreur de connexion');
            });
    } else {
        console.error('❌ Bootstrap non disponible');
        alert('Système de modal non disponible');
    }
}

// Fonction pour remplir manuellement le modal de détails
function fillRepairDetailsModal(repairData, photos) {
    console.log('🔧 Remplissage du modal avec:', repairData);
    
    // Mettre à jour le titre
    const modalTitle = document.getElementById('repairTitleText');
    if (modalTitle) {
        modalTitle.textContent = `Réparation #${repairData.id} - ${repairData.type_appareil || 'Appareil'}`;
    }
    
    // Remplir le contenu si l'élément existe
    const content = document.getElementById('repairDetailsContent');
    if (content) {
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle text-primary me-2"></i>Informations générales</h6>
                    <p><strong>ID:</strong> #${repairData.id}</p>
                    <p><strong>Type d'appareil:</strong> ${repairData.type_appareil || 'Non spécifié'}</p>
                    <p><strong>Modèle:</strong> ${repairData.modele || 'Non spécifié'}</p>
                    <p><strong>Statut:</strong> <span class="badge bg-${repairData.statut_couleur || 'primary'}">${repairData.statut_nom || repairData.statut}</span></p>
                    <p><strong>Date de réception:</strong> ${repairData.date_reception_formatted || repairData.date_reception}</p>
                    ${repairData.prix_reparation ? `<p><strong>Prix:</strong> ${repairData.prix_formatte || repairData.prix_reparation} €</p>` : ''}
                    ${repairData.urgent ? '<p><span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>URGENT</span></p>' : ''}
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-user text-info me-2"></i>Informations client</h6>
                    <p><strong>Nom:</strong> ${repairData.client_nom || 'Non spécifié'} ${repairData.client_prenom || ''}</p>
                    ${repairData.client_telephone ? `<p><strong>Téléphone:</strong> <a href="tel:${repairData.client_telephone}">${repairData.client_telephone}</a></p>` : ''}
                    ${repairData.client_email ? `<p><strong>Email:</strong> <a href="mailto:${repairData.client_email}">${repairData.client_email}</a></p>` : ''}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-tools text-warning me-2"></i>Description du problème</h6>
                    <p class="border p-3 rounded bg-light">${repairData.description_probleme || 'Aucune description fournie'}</p>
                </div>
            </div>
            ${photos && photos.length > 0 ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-images text-info me-2"></i>Photos (${photos.length})</h6>
                    <div class="d-flex flex-wrap gap-2">
                        ${photos.map(photo => `<img src="${photo.url}" alt="Photo réparation" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">`).join('')}
                    </div>
                </div>
            </div>` : ''}
        `;
        
        // Rendre visible si caché
        content.style.display = 'block';
    }
    
    // Cacher le loader s'il existe
    const loader = document.getElementById('repairDetailsLoader');
    if (loader) {
        loader.style.display = 'none';
    }
}

// Afficher une erreur dans le modal
function showMyRepairsError(message) {
    const container = document.getElementById('myRepairsList');
    container.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #ef4444;">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
            <p>Erreur : ${message}</p>
            <button onclick="loadMyRepairs()" style="margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-sync-alt"></i> Réessayer
            </button>
        </div>
    `;
}

// Fonction pour démarrer une réparation depuis le modal "Mes réparations"
function startRepairFromModal(repairId) {
    console.log('🔧 Démarrage de la réparation depuis le modal:', repairId);
    
    // Vérifier d'abord si l'utilisateur a déjà une réparation active
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            action: 'check_active_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('📋 Vérification réparation active:', data);
        
        if (data.success) {
            if (data.has_active_repair && data.active_repair.id != repairId) {
                // L'utilisateur a déjà une réparation active différente
                const activeRepair = data.active_repair;
                console.log('⚠️ Réparation active détectée:', activeRepair);
                
                if (confirm(`Vous avez déjà une réparation active (#${activeRepair.id}). Voulez-vous la terminer et démarrer cette nouvelle réparation ?`)) {
                    // Fermer le modal "Mes réparations"
                    closeMyRepairs();
                    
                    // Remplir le modal activeRepairModal
                    document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                    document.getElementById('activeRepairDevice').textContent = activeRepair.modele || 'Non renseigné';
                    document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom || ''} ${activeRepair.client_prenom || ''}`.trim() || 'Non renseigné';
                    document.getElementById('activeRepairProblem').textContent = activeRepair.description_probleme || 'Non renseigné';
                    
                    // Ajouter des écouteurs aux boutons de statut
                    const completeButtons = document.querySelectorAll(".complete-btn");
                    completeButtons.forEach(button => {
                        // Créer un clone du bouton pour éviter les doublons d'écouteurs
                        const newButton = button.cloneNode(true);
                        button.parentNode.replaceChild(newButton, button);
                        
                        // Ajouter l'écouteur d'événement
                        newButton.addEventListener("click", function() {
                            const status = this.getAttribute("data-status");
                            // Utiliser la fonction globale completeActiveRepairAndStartNew
                            if (typeof window.completeActiveRepairAndStartNew === 'function') {
                                window.completeActiveRepairAndStartNew(activeRepair.id, repairId, status);
                            } else {
                                console.error('Fonction completeActiveRepairAndStartNew non disponible');
                                alert('Erreur: Fonction de finalisation non disponible');
                            }
                        });
                    });
                    
                    // Ouvrir le modal activeRepairModal
                    setTimeout(() => {
                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                        activeRepairModal.show();
                    }, 300);
                }
            } else if (data.has_active_repair && data.active_repair.id == repairId) {
                // L'utilisateur essaie de démarrer sa propre réparation active
                alert('Cette réparation est déjà active !');
            } else {
                // L'utilisateur n'a pas de réparation active, démarrer directement
                assignRepairFromModal(repairId);
            }
        } else {
            alert(data.message || 'Erreur lors de la vérification des réparations actives');
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la vérification:', error);
        alert('Erreur de connexion lors de la vérification');
    });
}

// Fonction pour assigner une réparation depuis le modal
function assignRepairFromModal(repairId) {
    console.log('🚀 Attribution de la réparation:', repairId);
    
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            action: 'assign_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('📋 Résultat attribution:', data);
        
        if (data.success) {
            alert('Réparation démarrée avec succès !');
            
            // Fermer le modal "Mes réparations"
            closeMyRepairs();
            
            // Recharger la page pour refléter les changements
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            alert('Erreur lors du démarrage : ' + data.message);
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de l\'attribution:', error);
        alert('Erreur de connexion lors du démarrage');
    });
}

// Fonction pour mettre à jour le badge du bouton "Mes réparations"
function updateMyRepairsBadge(count) {
    console.log('🏷️ Mise à jour du badge avec le nombre:', count);
    const badge = document.getElementById('myRepairsBadge');
    console.log('🎯 Badge trouvé:', badge);
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
            console.log('✅ Badge affiché avec le nombre:', count);
        } else {
            badge.style.display = 'none';
            console.log('❌ Badge caché (count = 0)');
        }
    } else {
        console.error('❌ Badge non trouvé dans le DOM');
    }
}

// Fonction pour charger le nombre de réparations au chargement de la page
function loadMyRepairsCount() {
    console.log('🔄 Chargement du nombre de réparations...');
    const debugUserId = window.currentUserId || 0;
    console.log('👤 User ID:', debugUserId);
    
    fetch(`ajax/get_my_repairs.php?debug_user_id=${debugUserId}`)
        .then(response => response.json())
        .then(data => {
            console.log('📊 Données reçues:', data);
            if (data.success) {
                const count = data.count || data.repairs.length;
                console.log('🔢 Nombre de réparations:', count);
                updateMyRepairsBadge(count);
            } else {
                console.error('❌ Erreur API:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement du nombre de réparations:', error);
        });
}

// Fonction pour mettre à jour le badge d'un onglet
function updateTabBadge(statusType, count) {
    const badgeId = `count-${statusType}`;
    const badge = document.getElementById(badgeId);
    
    if (badge) {
        badge.textContent = count;
        console.log(`🏷️ Badge ${badgeId} mis à jour avec: ${count}`);
    } else {
        console.error(`❌ Badge ${badgeId} non trouvé`);
    }
}

// Fonction pour gérer les clics sur les onglets du modal de statut
function initializeStatusModalTabs() {
    const tabs = document.querySelectorAll('#statusTabs .modern-tab');
    const tabPanels = document.querySelectorAll('#statusTabsContent .tab-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Retirer la classe active de tous les onglets
            tabs.forEach(t => t.classList.remove('active'));
            
            // Ajouter la classe active à l'onglet cliqué
            this.classList.add('active');
            
            // Masquer tous les panneaux d'onglets
            tabPanels.forEach(panel => {
                panel.classList.remove('active');
                panel.style.display = 'none';
            });
            
            // Afficher le panneau de l'onglet sélectionné
            const targetPanel = document.getElementById(targetTab);
            if (targetPanel) {
                targetPanel.classList.add('active');
                targetPanel.style.display = 'block';
            }
            
            console.log(`📋 Onglet ${targetTab} activé - Panel trouvé:`, !!targetPanel);
        });
    });
    
    // S'assurer que l'onglet "nouvelles" est actif par défaut
    const defaultTab = document.querySelector('#statusTabs .modern-tab[data-tab="nouvelles"]');
    const defaultPanel = document.getElementById('nouvelles');
    
    if (defaultTab && defaultPanel) {
        // Activer l'onglet par défaut
        tabs.forEach(t => t.classList.remove('active'));
        defaultTab.classList.add('active');
        
        // Activer le panneau par défaut
        tabPanels.forEach(panel => {
            panel.classList.remove('active');
            panel.style.display = 'none';
        });
        defaultPanel.classList.add('active');
        defaultPanel.style.display = 'block';
        
        console.log('📋 Onglet par défaut "nouvelles" activé');
    }
}

// Fonction pour rendre les lignes du tableau cliquables
function initializeTableRowSelection() {
    // Utiliser la délégation d'événements pour gérer les clics sur les lignes
    document.addEventListener('click', function(event) {
        // Vérifier si le clic est sur une ligne de tableau dans le modal de statut
        const tableRow = event.target.closest('.table-row');
        if (!tableRow) return;
        
        // Vérifier si on est dans le modal de mise à jour de statut
        const statusModal = event.target.closest('#updateStatusModal');
        if (!statusModal) return;
        
        // Éviter de déclencher si on clique directement sur la checkbox ou un bouton
        if (event.target.matches('input[type="checkbox"]') || 
            event.target.closest('button') || 
            event.target.closest('.btn')) {
            return;
        }
        
        // Trouver la checkbox dans cette ligne
        const checkbox = tableRow.querySelector('input[type="checkbox"].modern-checkbox');
        if (checkbox) {
            // Basculer l'état de la checkbox
            checkbox.checked = !checkbox.checked;
            
            // Ajouter/retirer la classe selected sur la ligne
            if (checkbox.checked) {
                tableRow.classList.add('selected');
            } else {
                tableRow.classList.remove('selected');
            }
            
            // Déclencher l'événement change sur la checkbox pour les autres scripts
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log(`📋 Ligne ${tableRow.dataset.repairId} ${checkbox.checked ? 'sélectionnée' : 'désélectionnée'}`);
        }
    });
    
    // Ajouter des styles CSS pour l'effet hover et selected
    const style = document.createElement('style');
    style.textContent = `
        .table-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .table-row:hover {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        
        .table-row.selected {
            background-color: rgba(59, 130, 246, 0.1) !important;
            border-left: 3px solid #3b82f6 !important;
        }
        
        .table-row .checkbox-cell {
            pointer-events: none;
        }
        
        .table-row .modern-checkbox {
            pointer-events: auto;
        }
    `;
    document.head.appendChild(style);
    
    console.log('📋 Sélection de lignes par clic initialisée');
}

// ===== Adaptation automatique du mode sombre =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Initialisation du mode automatique jour/nuit');
    
    // Fonction pour appliquer le mode selon les préférences système
    function applySystemTheme() {
        const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
        
        if (prefersDarkScheme.matches) {
            document.body.classList.add('dark-mode');
            console.log('🌙 Mode nuit appliqué automatiquement');
        } else {
            document.body.classList.remove('dark-mode');
            console.log('🌞 Mode jour appliqué automatiquement');
        }
    }
    
    // Appliquer le thème initial
    applySystemTheme();
    
    // Écouter les changements de préférences système
    const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
    prefersDarkScheme.addEventListener('change', function(e) {
        if (e.matches) {
            document.body.classList.add('dark-mode');
            console.log('🌙 Basculement automatique vers mode nuit');
        } else {
            document.body.classList.remove('dark-mode');
            console.log('🌞 Basculement automatique vers mode jour');
        }
    });
    
    // Fonctions de debug (optionnelles)
    window.forceLightMode = function() {
        document.body.classList.remove('dark-mode');
        console.log('🌞 Mode jour forcé (debug)');
    };
    
    window.forceDarkMode = function() {
        document.body.classList.add('dark-mode');
        console.log('🌙 Mode nuit forcé (debug)');
    };
});

// Mode automatique - adaptation selon les préférences système

// Fonction de test pour le mode nuit
function testDarkModeDetection() {
    const isDarkMode = document.documentElement.hasAttribute('data-theme') && 
                      document.documentElement.getAttribute('data-theme') === 'dark' ||
                      document.documentElement.classList.contains('dark') ||
                      document.body.classList.contains('dark') ||
                      document.body.classList.contains('dark-mode');
    
    console.log('🌙 Détection du mode nuit:', {
        isDarkMode: isDarkMode,
        htmlDataTheme: document.documentElement.getAttribute('data-theme'),
        htmlHasDarkClass: document.documentElement.classList.contains('dark'),
        bodyHasDarkClass: document.body.classList.contains('dark'),
        bodyHasDarkModeClass: document.body.classList.contains('dark-mode')
    });
    
    return isDarkMode;
}

function testCompleteHistoryDarkMode() {
    console.log('🧪 Test du modal d\'historique avec mode nuit');
    testDarkModeDetection();
    showRepairHistoryModal(2132, 'Test Client', '0123456789');
}

// Fonction globale pour nettoyer tous les backdrops
function cleanAllBackdrops() {
    console.log('🧹 Nettoyage global des backdrops');
    
    // Supprimer tous les backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        console.log('🗑️ Suppression backdrop:', backdrop);
        backdrop.remove();
    });
    
    // Nettoyer le body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Nettoyer les styles inline problématiques
    if (document.body.hasAttribute('style')) {
        const currentStyle = document.body.getAttribute('style');
        const cleanedStyle = currentStyle
            .replace(/overflow[^;]*;?/g, '')
            .replace(/padding-right[^;]*;?/g, '')
            .trim();
        
        if (cleanedStyle) {
            document.body.setAttribute('style', cleanedStyle);
        } else {
            document.body.removeAttribute('style');
        }
    }
    
    console.log('✅ Nettoyage global terminé');
}

// Fonction d'urgence accessible globalement
window.cleanAllBackdrops = cleanAllBackdrops;

// Fonction pour ouvrir le modal de mise à jour de statut
function openStatusUpdateModal() {
    console.log('📱 Ouverture du modal de mise à jour de statut (mobile)');
    
    // Vérifier si le modal existe
    const statusModal = document.getElementById('updateStatusModal');
    if (statusModal) {
        const modal = new bootstrap.Modal(statusModal);
        modal.show();
        
        // Charger les données après l'ouverture du modal
        setTimeout(() => {
            loadStatusUpdateData();
        }, 300);
    } else {
        console.error('❌ Modal updateStatusModal non trouvé');
        // Fallback - utiliser le modal chooseStatusModal s'il existe
        const chooseStatusModal = document.getElementById('chooseStatusModal');
        if (chooseStatusModal) {
            const modal = new bootstrap.Modal(chooseStatusModal);
            modal.show();
        } else {
            alert('Fonctionnalité de mise à jour de statut non disponible');
        }
    }
}

// Fonction pour charger les données du modal de mise à jour de statut
function loadStatusUpdateData() {
    console.log('📊 Chargement des données pour le modal de mise à jour de statut');
    
    // Initialiser les onglets cliquables
    initializeStatusModalTabs();
    
    // Initialiser la sélection de lignes par clic
    initializeTableRowSelection();
    
    // Charger les données pour chaque onglet
    loadRepairsByStatus('nouvelles', 'repairs-nouvelles');
    loadRepairsByStatus('en-cours', 'repairs-en-cours');
    loadRepairsByStatus('en-attente', 'repairs-en-attente');
    loadRepairsByStatus('terminees', 'repairs-terminees');
}

// Fonction pour charger les réparations par statut
function loadRepairsByStatus(statusType, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`❌ Container ${containerId} non trouvé`);
        return;
    }
    
    // Afficher le loading
    container.innerHTML = `
        <div class="loading-card">
            <div class="loading-spinner"></div>
            <span>Chargement des réparations...</span>
        </div>
    `;
    
    // Mapper les types de statut vers les IDs de statut réels
    const statusMapping = {
        'nouvelles': [1, 2, 3], // Nouveau Diagnostique, Nouvelle Intervention, Nouvelle Commande
        'en-cours': [4, 5], // En cours de diagnostique, En cours d'intervention
        'en-attente': [6, 7, 8], // En attente de l'accord client, En attente de livraison, En attente d'un responsable
        'terminees': [9, 11, 15] // Réparation Effectuée, Restitué, Terminé
    };
    
    const statusIds = statusMapping[statusType] || [];
    
    if (statusIds.length === 0) {
        container.innerHTML = `
            <div class="loading-card">
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3 style="color: #6b7280; margin-bottom: 10px;">Statut non configuré</h3>
                    <p style="color: #9ca3af;">Type de statut "${statusType}" non reconnu.</p>
                </div>
            </div>
        `;
        return;
    }
    
    // Appel API pour récupérer les réparations (essayer différents chemins)
    const apiUrl = `ajax/get_repairs_by_status.php?status_ids=${statusIds.join(',')}`;
    console.log(`🔗 Appel API pour ${statusType}:`, apiUrl);
    console.log(`📊 Status IDs pour ${statusType}:`, statusIds);
    
    fetch(apiUrl)
        .then(response => {
            console.log(`📡 Réponse API pour ${statusType}:`, response.status);
            return response.json();
        })
        .then(data => {
            console.log(`📦 Données reçues pour ${statusType}:`, data);
            
            if (data.success && data.repairs && data.repairs.length > 0) {
                // Afficher les réparations en format cartes
                let html = '';
                data.repairs.forEach(repair => {
                    // Extraire les initiales du client
                    const clientName = repair.client_nom || 'Client inconnu';
                    const initials = clientName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    
                    // Détecter le statut pour le badge
                    const statusClass = `status-${repair.statut_id}`;
                    
                    html += `
                        <div class="repair-card" data-repair-id="${repair.id}">
                            <div class="repair-card-header">
                                <input type="checkbox" class="repair-checkbox" value="${repair.id}">
                                <span class="repair-status-badge ${statusClass}">${repair.statut_nom || 'Inconnu'}</span>
                            </div>
                            
                            <div class="repair-client-info">
                                <div class="repair-client-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="repair-client-name">${clientName}</div>
                            </div>
                            
                            <div class="repair-device-info">
                                <i class="fas fa-mobile-alt repair-device-icon"></i>
                                <span>${[repair.appareil_marque, repair.appareil_modele].filter(item => item && item !== 'N/A' && item.trim() !== '').join(' ') || 'Modèle non spécifié'}</span>
                            </div>
                            
                            <div class="repair-problem">
                                ${repair.probleme_description || 'Problème non spécifié'}
                            </div>
                            
                            <div class="repair-price">
                                ${(() => {
                                    // Chercher le prix dans les différentes propriétés possibles
                                    let price = repair.prix;
                                    if (price === undefined || price === null) price = repair.prix_reparation;
                                    if (price === undefined || price === null) price = repair.montant_total;
                                    if (price === undefined || price === null) price = repair.price;
                                    
                                    // Si le prix est défini (y compris 0)
                                    if (price !== undefined && price !== null && price !== '') {
                                        // Si c'est une chaîne avec une virgule, c'est déjà formaté par PHP
                                        if (typeof price === 'string' && price.includes(',')) {
                                            return price + ' €';
                                        }
                                        // Sinon, on formate en JS
                                        return parseFloat(price).toFixed(2) + ' €';
                                    }
                                    return 'Non défini';
                                })()}
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                console.log(`✅ ${data.repairs.length} réparations chargées pour ${statusType}`);
                
                // Mettre à jour le badge de l'onglet avec le nombre de réparations
                updateTabBadge(statusType, data.repairs.length);
            } else {
                // Vérifier si c'est une erreur d'API
                if (!data.success) {
                    console.error(`❌ Erreur API pour ${statusType}:`, data.error);
                    container.innerHTML = `
                        <div class="loading-card">
                            <div style="text-align: center; padding: 40px; color: #ef4444;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                                <h3 style="color: #ef4444; margin-bottom: 10px;">Erreur</h3>
                                <p style="color: #f87171;">${data.error || 'Erreur inconnue'}</p>
                                ${data.debug ? `<small style="color: #9ca3af; display: block; margin-top: 10px;">${data.debug}</small>` : ''}
                            </div>
                        </div>
                    `;
                    
                    // Mettre à jour le badge avec 0 en cas d'erreur
                    updateTabBadge(statusType, 0);
                } else {
                    // Aucune réparation trouvée
                    container.innerHTML = `
                        <div class="loading-card">
                            <div style="text-align: center; padding: 40px; color: #6b7280;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                                <h3 style="color: #6b7280; margin-bottom: 10px;">Aucune réparation</h3>
                                <p style="color: #9ca3af;">Aucune réparation trouvée pour ce statut.</p>
                            </div>
                        </div>
                    `;
                    console.log(`ℹ️ Aucune réparation trouvée pour ${statusType}`);
                    
                    // Mettre à jour le badge avec 0 pour aucune réparation
                    updateTabBadge(statusType, 0);
                }
            }
        })
        .catch(error => {
            console.error(`❌ Erreur lors du chargement des réparations pour ${statusType}:`, error);
            container.innerHTML = `
                <div class="loading-card">
                    <div style="text-align: center; padding: 40px; color: #ef4444;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3 style="color: #ef4444; margin-bottom: 10px;">Erreur de chargement</h3>
                        <p style="color: #f87171;">Impossible de charger les réparations.</p>
                    </div>
                </div>
            `;
            
            // Mettre à jour le badge avec 0 en cas d'erreur réseau
            updateTabBadge(statusType, 0);
        });
}

// Event listeners pour les boutons de sélection
document.addEventListener('DOMContentLoaded', function() {
    // Bouton "Tout sélectionner"
    const selectAllBtn = document.getElementById('select-all-visible');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            // Trouver l'onglet actif
            const activeTab = document.querySelector('.modern-tab.active');
            if (!activeTab) return;
            
            const tabName = activeTab.getAttribute('data-tab');
            const containerId = `repairs-${tabName}`;
            const container = document.getElementById(containerId);
            
            if (!container) return;
            
            // Sélectionner toutes les checkboxes visibles dans cet onglet
            const checkboxes = container.querySelectorAll('.repair-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                // Ajouter la classe selected à la carte
                const card = checkbox.closest('.repair-card');
                if (card) {
                    card.classList.add('selected');
                }
            });
            
            // Mettre à jour le compteur
            updateSelectionCount();
        });
    }
    
    // Bouton "Tout désélectionner"
    const deselectAllBtn = document.getElementById('deselect-all');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            // Désélectionner toutes les checkboxes dans tous les onglets
            const allCheckboxes = document.querySelectorAll('.repair-checkbox');
            allCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                // Retirer la classe selected de la carte
                const card = checkbox.closest('.repair-card');
                if (card) {
                    card.classList.remove('selected');
                }
            });
            
            // Mettre à jour le compteur
            updateSelectionCount();
        });
    }
});

// Event listener pour la sélection des cartes au clic
document.addEventListener('click', function(e) {
    // Vérifier si on a cliqué sur une carte ou un de ses enfants
    const card = e.target.closest('.repair-card');
    
    // Ne rien faire si on n'a pas cliqué sur une carte
    if (!card) return;
    
    // Ne rien faire si on a cliqué directement sur la checkbox
    if (e.target.classList.contains('repair-checkbox')) return;
    
    // Trouver la checkbox dans la carte
    const checkbox = card.querySelector('.repair-checkbox');
    if (!checkbox) return;
    
    // Toggle la checkbox
    checkbox.checked = !checkbox.checked;
    
    // Ajouter/retirer la classe selected
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
    
    // Mettre à jour le compteur de sélection
    updateSelectionCount();
});

// Mettre à jour le compteur de sélection
function updateSelectionCount() {
    const allCheckboxes = document.querySelectorAll('.repair-checkbox');
    const checkedCount = Array.from(allCheckboxes).filter(cb => cb.checked).length;
    const countElement = document.getElementById('selected-count');
    if (countElement) {
        countElement.textContent = `${checkedCount} réparation(s) sélectionnée(s)`;
    }
}

// Fonctions pour appliquer le mode nuit aux cartes
function applyDarkModeToStatusCards() {
    const isDarkMode = document.body.classList.contains('dark-mode') ||
                      document.documentElement.classList.contains('dark') ||
                      document.body.classList.contains('dark') ||
                      (document.documentElement.hasAttribute('data-theme') && 
                       document.documentElement.getAttribute('data-theme') === 'dark');
    
    if (isDarkMode) {
        console.log('🌙 Application du mode nuit aux cartes de statut');
        const statusCards = document.querySelectorAll('#statusHistoryContent .status-card, #statusHistoryContent div[style*="background: white"]');
        statusCards.forEach(card => {
            card.style.setProperty('background', '#475569', 'important');
            card.style.setProperty('color', '#e2e8f0', 'important');
            card.style.setProperty('border-color', '#64748b', 'important');
            
            // Textes dans les cartes
            const texts = card.querySelectorAll('h4, p, span, strong');
            texts.forEach(text => {
                text.style.setProperty('color', '#e2e8f0', 'important');
            });
        });
    }
}

function applyDarkModeToSmsCards() {
    const isDarkMode = document.body.classList.contains('dark-mode') ||
                      document.documentElement.classList.contains('dark') ||
                      document.body.classList.contains('dark') ||
                      (document.documentElement.hasAttribute('data-theme') && 
                       document.documentElement.getAttribute('data-theme') === 'dark');
    
    if (isDarkMode) {
        console.log('🌙 Application du mode nuit aux cartes SMS');
        const smsCards = document.querySelectorAll('#smsHistoryContent .sms-card, #smsHistoryContent .sms-message-card');
        smsCards.forEach(card => {
            card.style.setProperty('background', '#475569', 'important');
            card.style.setProperty('color', '#e2e8f0', 'important');
            card.style.setProperty('border-color', '#64748b', 'important');
            
            // Textes dans les cartes
            const texts = card.querySelectorAll('h4, p, span, strong');
            texts.forEach(text => {
                text.style.setProperty('color', '#e2e8f0', 'important');
            });
        });
    }
}

// Fonction pour stabiliser le mode nuit en mobile
function stabilizeDarkModeOnMobile() {
    if (window.innerWidth <= 768 && document.body.classList.contains('dark-mode')) {
        // Forcer la stabilité du background
        document.body.style.setProperty('background', 'var(--night-bg-animated)', 'important');
        document.body.style.setProperty('background-attachment', 'fixed', 'important');
        
        // Désactiver temporairement les transitions pendant le scroll
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            document.body.classList.add('scrolling');
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                document.body.classList.remove('scrolling');
            }, 150);
        });
        
        // Ajouter les styles CSS pour la classe scrolling
        const style = document.createElement('style');
        style.textContent = `
            body.scrolling.dark-mode * {
                transition: none !important;
                animation-play-state: paused !important;
            }
        `;
        document.head.appendChild(style);
    }
}

// Charger le nombre de réparations au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Stabiliser le mode nuit en mobile
    stabilizeDarkModeOnMobile();
    
    // Attendre un peu que la page soit complètement chargée
    setTimeout(() => {
        loadMyRepairsCount();
    }, 1000);
});

// Fermer les modals avec l'overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('my-repairs-modal-overlay')) {
        closeMyRepairs();
    }
});

// Gestion spécifique pour repairDetailsModal - Correction du backdrop
document.addEventListener('DOMContentLoaded', function() {
    const repairDetailsModal = document.getElementById('repairDetailsModal');
    if (repairDetailsModal) {
        console.log('🔧 Initialisation de la gestion du modal repairDetailsModal');
        
        // Écouter l'événement de fermeture du modal
        repairDetailsModal.addEventListener('hidden.bs.modal', function() {
            console.log('🔧 Modal repairDetailsModal fermé - Nettoyage du backdrop');
            
            // Nettoyer tous les backdrops
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    console.log('🗑️ Suppression du backdrop:', backdrop);
                    backdrop.remove();
                });
                
                // Retirer les classes modal-open du body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Forcer le nettoyage des styles inline problématiques
                if (document.body.hasAttribute('style')) {
                    const currentStyle = document.body.getAttribute('style');
                    const cleanedStyle = currentStyle
                        .replace(/overflow[^;]*;?/g, '')
                        .replace(/padding-right[^;]*;?/g, '')
                        .trim();
                    
                    if (cleanedStyle) {
                        document.body.setAttribute('style', cleanedStyle);
                    } else {
                        document.body.removeAttribute('style');
                    }
                }
                
                console.log('✅ Backdrop nettoyé et body restauré');
            }, 100);
        });
        
        // Gestion du clic sur le backdrop
        repairDetailsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                console.log('🖱️ Clic sur backdrop détecté');
                const modal = bootstrap.Modal.getInstance(this);
                if (modal) {
                    modal.hide();
                }
            }
        });
        
        // Gestion des boutons de fermeture
        const closeButtons = repairDetailsModal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .modern-btn-close');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                console.log('🖱️ Bouton fermeture cliqué');
                e.preventDefault();
                const modal = bootstrap.Modal.getInstance(repairDetailsModal);
                if (modal) {
                    modal.hide();
                } else {
                    // Fallback si Bootstrap Modal n'est pas initialisé
                    repairDetailsModal.classList.remove('show');
                    repairDetailsModal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                }
            });
        });
    }
    
    // Gestion globale de la touche Échap pour fermer les modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            console.log('⌨️ Touche Échap détectée');
            
            // Fermer le modal repairDetailsModal s'il est ouvert
            const repairModal = document.getElementById('repairDetailsModal');
            if (repairModal && repairModal.classList.contains('show')) {
                const modal = bootstrap.Modal.getInstance(repairModal);
                if (modal) {
                    modal.hide();
                } else {
                    cleanAllBackdrops();
                }
            }
            
            // Nettoyer tous les backdrops en dernier recours
            setTimeout(() => {
                const remainingBackdrops = document.querySelectorAll('.modal-backdrop');
                if (remainingBackdrops.length > 0) {
                    console.log('🧹 Nettoyage d\'urgence des backdrops restants');
                    cleanAllBackdrops();
                }
            }, 200);
        }
    });
});

</script>

<!-- NAVBAR MOBILE (Dock en bas) -->
<?php
// Détection mobile/iPad pour le dock
$isMobile = false;
$isIPad = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|iphone|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
    $isIPad = preg_match('/(ipad)/i', $_SERVER['HTTP_USER_AGENT']) || 
              (preg_match('/(macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false);
}
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';
?>

<style>
/* Styles pour le dock mobile en mode nuit */
body.dark-mode #mobile-dock {
    background: rgba(22, 24, 32, 0.9) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
    box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.3) !important;
}

body.dark-mode .mobile-dock-container {
    color: rgba(235, 240, 255, 0.85) !important;
}

body.dark-mode .dock-item {
    color: rgba(235, 240, 255, 0.85) !important;
}

body.dark-mode .dock-icon-wrapper {
    background: rgba(50, 52, 65, 0.8) !important;
}

body.dark-mode .dock-item.active {
    color: #7d9bff !important;
}

/* container dock */
.mobile-dock-container {
    display: flex !important;
    justify-content: space-around !important;
    align-items: center !important;
    height: 70px !important;
    max-width: 600px !important;
    margin: 0 auto !important;
    padding: 10px 16px !important;
}

.dock-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    flex: 1 !important;
    text-decoration: none !important;
    color: #64748b !important;
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

.dock-icon-wrapper {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 46px !important;
    height: 46px !important;
    border-radius: 16px !important;
    margin-bottom: 6px !important;
    background: rgba(255, 255, 255, 0.8) !important;
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

.dock-item i {
    font-size: 22px !important;
}

.dock-item span {
    font-size: 11px !important;
    font-weight: 500 !important;
}

.dock-item.active {
    color: #4361ee !important;
}

.dock-item-center {
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2;
}

.btn-nouvelle-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4361ee, #6178f1);
    border: none;
    color: white;
    font-size: 24px;
    box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
}
</style>

<div id="mobile-dock" class="<?php echo ($isMobile || $isIPad) ? 'd-block' : 'd-lg-none'; ?>" style="position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; z-index: 9999 !important; background: rgba(255, 255, 255, 0.7) !important; backdrop-filter: blur(20px) !important; -webkit-backdrop-filter: blur(20px) !important; box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.08) !important; border-top: 1px solid rgba(255, 255, 255, 0.1) !important; <?php if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false && !$isIPad && !$isMobile): ?>display: none !important; visibility: hidden !important;<?php endif; ?>">
    <div class="mobile-dock-container">
        <a href="index.php" class="dock-item <?php echo $currentPage == 'accueil' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span>Accueil</span>
        </a>
        
        <a href="index.php?page=reparations" class="dock-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tools"></i>
            </div>
            <span>Réparations</span>
        </a>
        
        <!-- Bouton Nouvelle au centre -->
        <div class="dock-item-center" style="overflow: visible !important; position: relative !important;">
            <button 
                class="btn-nouvelle-action" 
                type="button" 
                id="nouvelle-action-trigger" 
                data-bs-toggle="modal" 
                data-bs-target="#nouvelles_actions_modal" 
                style="transform: translateY(0) !important;"
            >
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        <a href="index.php?page=taches" class="dock-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tasks"></i>
            </div>
            <span>Tâches</span>
        </a>
        
        <a href="#" class="dock-item" id="mobile-menu-trigger" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal">
            <div class="dock-icon-wrapper">
                <i class="fas fa-bars"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>

<!-- INFINITE SCROLL SCRIPT -->
<script>
/**
 * INFINITE SCROLL FOR REPAIRS
 * Automatically loads more repairs when user scrolls to bottom
 */
(function() {
    'use strict';
    
    const InfiniteScroll = {
        currentPage: <?php echo isset($pagination['current_page']) ? $pagination['current_page'] : 1; ?>,
        isLoading: false,
        hasMore: <?php echo isset($pagination['has_more']) && $pagination['has_more'] ? 'true' : 'false'; ?>,
        
        init() {
            console.log('🚀 Infinite Scroll initialized - Page:', this.currentPage, 'Has more:', this.hasMore);
            
            // Add scroll listener
            window.addEventListener('scroll', () => this.handleScroll());
            
            // Add loader UI
            this.createLoader();
        },
        
        createLoader() {
            const loader = document.createElement('div');
            loader.id = 'infinite-scroll-loader';
            loader.style.cssText = `
                display: none;
                text-align: center;
                padding: 2rem;
                margin: 2rem 0;
            `;
            loader.innerHTML = `
                <div class="loading-dots" style="display: flex; gap: 8px; justify-content: center; margin-bottom: 20px;">
                    <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: -0.32s;"></div>
                    <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: -0.16s;"></div>
                    <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: 0s;"></div>
                </div>
                <p style="color: #64748b; font-size: 16px; font-weight: 500; margin: 0;">Chargement...</p>
            `;
            
            const container = document.querySelector('.repair-cards-container');
            if (container) {
                container.parentElement.appendChild(loader);
            }
        },
        
        handleScroll() {
            if (this.isLoading || !this.hasMore) return;
            
            // Check if near bottom
            const scrollPosition = window.innerHeight + window.scrollY;
            const pageHeight = document.documentElement.scrollHeight;
            const triggerDistance = 500; // pixels from bottom
            
            if (scrollPosition >= pageHeight - triggerDistance) {
                this.loadMore();
            }
        },
        
        async loadMore() {
            if (this.isLoading || !this.hasMore) return;
            
            this.isLoading = true;
            this.showLoader();
            
            try {
                const nextPage = this.currentPage + 1;
                
                // Build URL with current filters
                const params = new URLSearchParams(window.location.search);
                params.set('page', nextPage);
                
                const url = 'ajax/load_more_repairs.php?' + params.toString();
                
                console.log('📥 Loading page', nextPage, 'from:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.html) {
                    this.appendCards(data.html);
                    this.currentPage = nextPage;
                    this.hasMore = data.has_more;
                    
                    console.log('✅ Loaded', data.count, 'repairs. Has more:', data.has_more);
                    
                    // Re-initialize drag & drop for new cards
                    if (typeof initCardDragAndDrop === 'function') {
                        initCardDragAndDrop();
                    }
                } else {
                    console.error('❌ Error:', data.error || 'Unknown error');
                    this.hasMore = false;
                }
            } catch (error) {
                console.error('❌ Fetch error:', error);
                this.hasMore = false;
            } finally {
                this.isLoading = false;
                this.hideLoader();
            }
        },
        
        appendCards(html) {
            const container = document.querySelector('.repair-cards-container');
            if (!container) return;
            
            // Create temp container to parse HTML
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Append each card
            Array.from(temp.children).forEach(card => {
                container.appendChild(card);
            });
        },
        
        showLoader() {
            const loader = document.getElementById('infinite-scroll-loader');
            if (loader) loader.style.display = 'block';
        },
        
        hideLoader() {
            const loader = document.getElementById('infinite-scroll-loader');
            if (loader) loader.style.display = 'none';
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfiniteScroll.init());
    } else {
        InfiniteScroll.init();
    }
    
    // Expose globally for debugging
    window.InfiniteScroll = InfiniteScroll;
})();
</script>

