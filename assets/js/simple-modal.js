/**
 * Modal Simple - Sans dépendance Bootstrap
 * Solution de fallback pour les modals qui ne fonctionnent pas
 */

console.log('🚀 [SIMPLE-MODAL] Initialisation du système de modal simple');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [SIMPLE-MODAL] DOM chargé, configuration des modals...');
    
    // Créer le modal HTML s'il n'existe pas
    function createModal() {
        const existingModal = document.getElementById('futuristicMenuModal');
        if (existingModal) {
            console.log('🚀 [SIMPLE-MODAL] Modal complet existant trouvé, utilisation de celui-ci');
            // Si c'est le modal complet (avec navigation moderne), on l'utilise directement
            if (existingModal.querySelector('.modern-navigation-modal') || existingModal.querySelector('.modern-nav-grid')) {
                console.log('🚀 [SIMPLE-MODAL] Modal complet détecté avec toutes les pages');
                return existingModal;
            }
        }
        
        console.log('🚀 [SIMPLE-MODAL] Création d\'un nouveau modal');
        
        const modal = document.createElement('div');
        modal.id = 'futuristicMenuModal';
        modal.className = 'simple-modal';
        modal.innerHTML = `
            <div class="simple-modal-backdrop"></div>
            <div class="simple-modal-dialog">
                <div class="simple-modal-content">
                    <div class="simple-modal-header">
                        <div class="d-flex align-items-center">
                            <img src="assets/images/logo/logoservo.png" alt="SERVO" height="40" style="margin-right: 15px;">
                            <div>
                                <h5 style="margin: 0; color: #333;">SERVO</h5>
                                <small style="color: #666;">Command Center</small>
                            </div>
                        </div>
                        <button type="button" class="simple-modal-close" aria-label="Fermer">×</button>
                    </div>
                    <div class="simple-modal-body">
                        <div style="margin-bottom: 20px;">
                            <h6 style="text-transform: uppercase; color: #666; font-size: 12px; margin-bottom: 15px;">Navigation</h6>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                                <a href="index.php" class="simple-modal-btn">
                                    <i class="fas fa-home"></i>
                                    <span>Accueil</span>
                                </a>
                                <a href="index.php?page=reparations" class="simple-modal-btn">
                                    <i class="fas fa-tools"></i>
                                    <span>Réparations</span>
                                </a>
                                <a href="index.php?page=clients" class="simple-modal-btn">
                                    <i class="fas fa-users"></i>
                                    <span>Clients</span>
                                </a>
                                <a href="index.php?page=devis" class="simple-modal-btn">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Devis</span>
                                </a>
                                <a href="index.php?page=taches" class="simple-modal-btn">
                                    <i class="fas fa-tasks"></i>
                                    <span>Tâches</span>
                                </a>
                                <a href="index.php?page=kpi_dashboard" class="simple-modal-btn">
                                    <i class="fas fa-chart-line"></i>
                                    <span>KPI</span>
                                </a>
                            </div>
                        </div>
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                        <div style="margin-bottom: 20px;">
                            <h6 style="text-transform: uppercase; color: #666; font-size: 12px; margin-bottom: 15px;">Actions Rapides</h6>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                                <button class="simple-modal-btn simple-modal-btn-success" onclick="openNewActionModal()">
                                    <i class="fas fa-plus"></i>
                                    <span>Nouveau</span>
                                </button>
                                <a href="index.php?page=ajouter_client" class="simple-modal-btn simple-modal-btn-info">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Client</span>
                                </a>
                                <a href="index.php?page=ajouter_reparation" class="simple-modal-btn simple-modal-btn-warning">
                                    <i class="fas fa-wrench"></i>
                                    <span>Réparation</span>
                                </a>
                                <a href="index.php?page=ajouter_tache" class="simple-modal-btn simple-modal-btn-secondary">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Tâche</span>
                                </a>
                            </div>
                        </div>
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                        <div>
                            <h6 style="text-transform: uppercase; color: #666; font-size: 12px; margin-bottom: 15px;">Système</h6>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                                <button class="simple-modal-btn simple-modal-btn-secondary" onclick="toggleDarkMode()">
                                    <i class="fas fa-moon"></i>
                                    <span>Mode Nuit</span>
                                </button>
                                <a href="logout.php" class="simple-modal-btn simple-modal-btn-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Déconnexion</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        return modal;
    }
    
    // Fonction pour ouvrir le modal
    function openModal() {
        console.log('🚀 [SIMPLE-MODAL] Ouverture du modal');
        const modal = createModal();
        
        // Si c'est le modal complet avec Bootstrap, utiliser Bootstrap
        if (modal.querySelector('.modern-navigation-modal') || modal.querySelector('.modern-nav-grid')) {
            console.log('🚀 [SIMPLE-MODAL] Utilisation de Bootstrap pour le modal complet');
            if (typeof bootstrap !== 'undefined') {
                try {
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                    return;
                } catch (error) {
                    console.warn('🚀 [SIMPLE-MODAL] Erreur Bootstrap, fallback vers modal simple:', error);
                }
            }
        }
        
        // Fallback : modal simple
        console.log('🚀 [SIMPLE-MODAL] Utilisation du modal simple');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Animation d'entrée
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    // Fonction pour fermer le modal
    function closeModal() {
        console.log('🚀 [SIMPLE-MODAL] Fermeture du modal');
        const modal = document.getElementById('futuristicMenuModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
    }
    
    // Fonction pour ouvrir le modal nouvelles actions
    window.openNewActionModal = function() {
        closeModal();
        // Essayer d'ouvrir le modal nouvelles actions
        const newActionModal = document.getElementById('nouvelles_actions_modal');
        if (newActionModal && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(newActionModal);
            modal.show();
        } else {
            // Fallback : rediriger vers une page
            window.location.href = 'index.php?page=ajouter_reparation';
        }
    };
    
    // Fonction pour basculer le mode sombre
    window.toggleDarkMode = function() {
        document.body.classList.toggle('dark-mode');
        document.body.classList.toggle('dark-theme');
        
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        
        closeModal();
    };
    
    // Appliquer le mode sombre au chargement
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode', 'dark-theme');
    }
    
    // Attacher les événements aux boutons hamburger
    const hamburgerButtons = document.querySelectorAll('[data-bs-target="#futuristicMenuModal"], .main-menu-btn, #mobile-menu-trigger');
    console.log('🚀 [SIMPLE-MODAL] Boutons hamburger trouvés:', hamburgerButtons.length);
    
    hamburgerButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log(`🚀 [SIMPLE-MODAL] Clic sur bouton hamburger ${index + 1}`);
            openModal();
        });
    });
    
    // Événements de fermeture
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('futuristicMenuModal');
        if (modal && modal.style.display === 'flex') {
            // Fermer si clic sur backdrop
            if (e.target.classList.contains('simple-modal-backdrop')) {
                closeModal();
            }
            // Fermer si clic sur bouton fermer
            if (e.target.classList.contains('simple-modal-close')) {
                closeModal();
            }
        }
    });
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('futuristicMenuModal');
            if (modal && modal.style.display === 'flex') {
                closeModal();
            }
        }
    });
    
    console.log('✅ [SIMPLE-MODAL] Système de modal simple configuré');
});
