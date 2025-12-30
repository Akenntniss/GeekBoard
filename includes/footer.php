<?php
// Fichier footer.php - Les fonctionnalités de navigation ont été supprimées mais on garde la structure HTML correcte
// Déterminer le bon chemin selon l'emplacement du fichier
$assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>

<!-- Modern Modals Global CSS (Loaded via pages bundle) -->
<!-- CSS loaded in header via pages.css bundle -->

<!-- Scripts nécessaires -->
<!-- Scripts Bootstrap & Popper (Already loaded in Header) -->

<!-- Sécurité : Masquer les modales modernes par défaut (fix temporaire pour cache CSS) -->
<style>
/* Fix: Masquer les modales modernes par défaut */
#gbQRRepairModal, #gbAdjustModal, #gbReasonModal, #gbPartnerSelectModal, #gbOtherReasonModal { display: none; }
#gbQRRepairModal.show, #gbAdjustModal.show, #gbReasonModal.show, #gbPartnerSelectModal.show, #gbOtherReasonModal.show { display: flex !important; }

/* Fix: Forcer l'affichage du bouton hamburger sur TOUS les écrans */
.navbar-toggler.main-menu-btn,
.main-menu-btn,
#desktop-navbar .main-menu-btn {
    display: inline-flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative !important;
    z-index: 99999 !important;
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 6px !important;
    color: #333 !important;
    min-width: 40px !important;
    min-height: 40px !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0.5rem !important;
}
.navbar-toggler.main-menu-btn i,
.main-menu-btn i {
    color: #333 !important;
    font-size: 18px !important;
}

/* Fix: z-index du backdrop réduit pour ne pas bloquer les modales */
.modal-backdrop.fade.show,
.modal-backdrop.show {
    z-index: 100 !important;
}

/* Fix: S'assurer que le modal nouveauClientModal_reparation est au-dessus */
#nouveauClientModal_reparation {
    z-index: 1055 !important;
}

/* Fix: Supprimer la bande verte debug en mode nuit sur la navbar */
body.dark-mode #desktop-navbar,
body.dark-mode .navbar,
body.dark-mode nav.navbar {
    background: rgba(20, 20, 30, 0.95) !important;
    border-top: none !important;
}
body.dark-mode #desktop-navbar::before,
body.dark-mode .navbar::before,
body.dark-mode nav.navbar::before {
    display: none !important;
    content: none !important;
    background: none !important;
}
body.dark-mode #desktop-navbar::after,
body.dark-mode .navbar::after,
body.dark-mode nav.navbar::after {
    background: none !important;
}
</style>

<!-- Inclure les modaux -->
<?php include_once __DIR__ . '/modals.php'; ?>
<?php include_once __DIR__ . '/modal_new_task.php'; ?>
<?php include_once __DIR__ . '/stock-qr-modals.php'; ?>

<!-- Stock QR Workflow - Global JS -->
<script src="<?php echo $assets_path; ?>js/stock-qr-workflow.js?v=<?php echo time(); ?>"></script>

<!-- Stock Modal Diagnostic -->
<script src="<?php echo $assets_path; ?>js/modal-stock-diagnostic.js?v=<?php echo time(); ?>"></script>

<!-- Scripts de modales désactivés pour éviter les conflits avec Bootstrap natif -->
<script src="<?php echo $assets_path; ?>js/modal-nouvelles-actions-fix.js?v=<?php echo time(); ?>"></script>

<!-- Script de test minimal pour diagnostiquer le modal -->
<script src="<?php echo $assets_path; ?>js/modal-test-simple.js"></script>

<!-- Script pour gérer les transitions entre modals -->
<script src="<?php echo $assets_path; ?>js/modal-transitions.js"></script>
<script src="<?php echo $assets_path; ?>js/modal_new_task.js?v=<?php echo time(); ?>"></script>

<!-- Script pour corriger le système de garde des modals -->
<script src="<?php echo $assets_path; ?>js/modal-guard-fix.js"></script>

<!-- Script de debug pour le modal ajouterCommandeModal -->
<script src="<?php echo $assets_path; ?>js/modal-commande-debug.js"></script>

<!-- Script principal pour la gestion du modal de commande -->
<script src="<?php echo $assets_path; ?>js/modal-commande.js"></script>

<!-- Script standalone pour la recherche client (sans dépendances) -->
<script src="<?php echo $assets_path; ?>js/client-search-standalone.js?v=<?php echo time(); ?>"></script>

<!-- Script principal pour corriger l'affichage du modal -->
<script src="<?php echo $assets_path; ?>js/modal-main-fix.js"></script>

<!-- Script pour gérer la superposition des modals -->
<script src="<?php echo $assets_path; ?>js/modal-stacking-fix.js"></script>

<!-- Script de correction spécifique pour le modal SMS -->
<script src="<?php echo $assets_path; ?>js/modal-sms-fix.js"></script>

<!-- Script de debug avancé pour analyser complètement les modals -->
<script src="<?php echo $assets_path; ?>js/modal-deep-debug.js"></script>

<!-- Script de diagnostic pour le scanner universel -->
<script src="<?php echo $assets_path; ?>js/barcode-diagnostic.js"></script>
<script src="<?php echo $assets_path; ?>js/barcode-test.js"></script>
<script src="<?php echo $assets_path; ?>js/barcode-debug-real.js"></script>

<!-- CSS bundles already loaded in header (stock-modal-design.css, product-action-modals.css) -->

<!-- Script d'amélioration du scanner pour une meilleure détection -->
<script src="<?php echo $assets_path; ?>js/bootstrap-focus-fix.js"></script>
<script src="<?php echo $assets_path; ?>js/scanner-enhancement.js"></script>

<!-- Script de diagnostic pour le modal de quantité -->
<script src="<?php echo $assets_path; ?>js/modal-quantity-debug.js"></script>

<!-- Script de debug pour vérifier le chargement des CSS -->
<script src="<?php echo $assets_path; ?>js/css-debug.js"></script>

<!-- Script pour forcer le rendu du modal (solution finale) -->
<script src="<?php echo $assets_path; ?>js/modal-force-render.js"></script>

<!-- Scanner d'étiquettes QR Code -->
<!-- Library html5-qrcode already loaded in header if needed -->
<script src="<?php echo $assets_path; ?>js/scanner-etiquette.js"></script>
<script src="<?php echo $assets_path; ?>js/scanner-modal-fix.js?v=<?php echo time(); ?>"></script>

<!-- Module de recherche avancée -->
<script src="<?php echo $assets_path; ?>js/recherche-avancee.js"></script>

<!-- Modal Configuration Caméra (global root pour éviter clignotements) -->
<div class="modal fade" id="repCameraConfigModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="repCameraConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="repCameraConfigLabel"><i class="fas fa-video me-2"></i>Configuration de la caméra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rep_camera_select" class="form-label">Caméra à utiliser par défaut</label>
                    <select class="form-select" id="rep_camera_select">
                        <option value="">Chargement des caméras...</option>
                    </select>
                    <div class="form-text">Le choix sera mémorisé sur cet appareil.</div>
                </div>
                <div class="alert alert-info d-none" id="rep_camera_permissions_hint">
                    Accordez l'accès à la caméra si la liste est vide.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-outline-primary" id="rep_camera_save">Enregistrer (cet appareil)</button>
                <button type="button" class="btn btn-primary" id="rep_camera_set_default">Définir par défaut</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Client Info (accessible globalement) -->
<div class="modal fade" id="clientInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-user me-2"></i>
                    Détails du client
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- En-tête avec infos de base et actions -->
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-header bg-light py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 client-nom">Nom du client</h4>
                                    <p class="mb-0 text-muted client-telephone">
                                        <i class="fas fa-phone-alt me-1"></i> 
                                        Téléphone
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary rounded-pill px-4 btn-appeler">
                                    <i class="fas fa-phone-alt me-2"></i>Appeler
                                </a>
                                <a href="#" class="btn btn-outline-primary rounded-pill px-4 btn-sms">
                                    <i class="fas fa-sms me-2"></i>SMS
                                </a>
                                <a href="#" class="btn btn-light rounded-pill px-4 btn-editer-client">
                                    <i class="fas fa-pen me-2"></i>Éditer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation par onglets simplifiée (sans Bootstrap tabs) -->
                <div class="mb-4">
                    <div class="d-flex gap-2" id="clientHistoryBtns">
                        <button class="btn btn-primary flex-fill py-2" id="btn-client-reps" onclick="showClientTab('reparationsClient')">
                            <i class="fas fa-tools me-2"></i>Réparations
                        </button>
                        <button class="btn btn-outline-primary flex-fill py-2" id="btn-client-cmds" onclick="showClientTab('commandesClient')">
                            <i class="fas fa-shopping-cart me-2"></i>Commandes
                        </button>
                    </div>
                </div>

                <!-- Contenu des onglets simplifiés -->
                <div class="position-relative">
                    <!-- Historique des réparations -->
                    <div id="reparationsClient" class="client-tab-container">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Historique des réparations</h6>
                                    <a href="#" class="btn btn-sm btn-primary" id="nouvelle-reparation-client">
                                        <i class="fas fa-plus me-1"></i>Nouvelle réparation
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Appareil</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historique_reparations">
                                        <!-- Les données seront chargées ici -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des commandes -->
                    <div id="commandesClient" class="client-tab-container" style="display: none;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Historique des commandes</h6>
                                    <a href="#" class="btn btn-sm btn-primary" id="nouvelle-commande-client">
                                        <i class="fas fa-plus me-1"></i>Nouvelle commande
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>Pièce</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historique_commandes">
                                        <!-- Les données seront chargées ici -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour gérer l'affichage des onglets du client -->
<script>
    function showClientTab(tabId) {
        // Masquer tous les conteneurs
        document.querySelectorAll('.client-tab-container').forEach(container => {
            container.style.display = 'none';
        });
        
        // Afficher le conteneur demandé
        const targetContainer = document.getElementById(tabId);
        if (targetContainer) {
            targetContainer.style.display = 'block';
        }
        
        // Mettre à jour les boutons
        document.querySelectorAll('#clientHistoryBtns button').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        // Activer le bouton correspondant
        if (tabId === 'reparationsClient') {
            document.getElementById('btn-client-reps').classList.remove('btn-outline-primary');
            document.getElementById('btn-client-reps').classList.add('btn-primary');
        } else if (tabId === 'commandesClient') {
            document.getElementById('btn-client-cmds').classList.remove('btn-outline-primary');
            document.getElementById('btn-client-cmds').classList.add('btn-primary');
        }
    }
</script>

<!-- Modal Réparation Info -->
<div class="modal fade" id="reparationInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-tools me-2"></i>
                    Détails de la réparation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="details-reparation-content">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<script>
    // Fonction pour charger les détails d'une réparation avec le nouveau bouton
    function chargerDetailsReparation(reparationId) {
        const detailsContainer = document.getElementById('details-reparation-content');
        if (!detailsContainer) return;
        
        // Afficher un indicateur de chargement
        detailsContainer.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Chargement des détails...</p>
            </div>
        `;
        
        // Charger les détails via AJAX
        fetch('ajax/get_reparation_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${reparationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.reparation) {
                throw new Error(data.message || 'Erreur lors du chargement des détails de la réparation');
            }
            
            const rep = data.reparation;
            
            // Afficher les détails avec le nouveau bouton qui redirige vers la page des réparations
            detailsContainer.innerHTML = `
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                ${rep.appareil} ${rep.modele}
                                <span class="badge bg-${getStatusColor(rep.statut)} ms-2">${formatStatus(rep.statut)}</span>
                            </h5>
                            <div>
                                <a href="index.php?page=reparations&showRepId=${rep.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>Voir page complète
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Client</p>
                                <p class="mb-3 fw-bold">${rep.client_nom} ${rep.client_prenom}</p>
                                
                                <p class="mb-1 text-muted">Date de réception</p>
                                <p class="mb-3 fw-bold">${formatDate(rep.date_reception)}</p>
                                
                                <p class="mb-1 text-muted">Prix</p>
                                <p class="mb-0 fw-bold">${rep.prix || '0'}€</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Appareil</p>
                                <p class="mb-3 fw-bold">${rep.appareil} ${rep.modele}</p>
                                
                                <p class="mb-1 text-muted">Problème</p>
                                <p class="mb-0 fw-bold">${rep.probleme || 'Non spécifié'}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <p class="mb-1 text-muted">Diagnostic</p>
                            <p class="mb-0">${rep.diagnostic || 'Aucun diagnostic enregistré'}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de la réparation:', error);
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur: ${error.message}
                </div>
                <div class="text-center mt-3">
                    <a href="index.php?page=reparations&showRepId=${reparationId}" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Voir la page complète
                    </a>
                </div>
            `;
        });
    }
    
    // Fonctions utilitaires
    function formatStatus(statut) {
        const statusMap = {
            '1': 'Reçu',
            '2': 'En cours',
            '3': 'Terminé',
            '4': 'Livré',
            'en_attente': 'En attente',
            'en_cours': 'En cours',
            'termine': 'Terminé',
            'livre': 'Livré'
        };
        
        return statusMap[statut] || statut;
    }
    
    function getStatusColor(statut) {
        const colorMap = {
            '1': 'info',
            '2': 'warning',
            '3': 'success',
            '4': 'secondary',
            'en_attente': 'info',
            'en_cours': 'warning',
            'termine': 'success',
            'livre': 'secondary'
        };
        
        return colorMap[statut] || 'primary';
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Si la date est invalide
        
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
</script>

<!-- Modal Commande Info -->
<div class="modal fade" id="commandeInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Détails de la commande
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="details-commande-content">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Inclusion du modal de recherche universel -->
<?php include_once __DIR__ . '/../components/modal-recherche-universel.php'; ?>

<!-- Scripts communs pour l'application -->
<!-- Bootstrap JS already loaded in header.php (v5.3.3) - DO NOT LOAD AGAIN -->
<script src="<?php echo $assets_path; ?>js/app.js"></script>
<script src="<?php echo $assets_path; ?>js/dock-effects.js"></script>

<!-- Système de signalement de bugs simple -->
<script src="<?php echo $assets_path; ?>js/bug-reporter-simple.js"></script>

<!-- Script pour la recherche rapide -->
<script>
    // Initialiser la recherche rapide
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Système de recherche moderne initialisé');
    });
</script>

<!-- CSS injectés au footer sont maintenant gérés par pages.css dans le header -->

<!-- Inclure le nouveau modal de recherche moderne -->
<?php include_once __DIR__ . '/../components/modal-recherche-moderne.php'; ?>

<!-- Scripts pour les modals de détails -->
<script src="<?php echo $assets_path; ?>js/commandes-details.js"></script>

<!-- Script du modal de recherche moderne -->
<script src="<?php echo $assets_path; ?>js/modal-recherche-moderne.js?v=<?php echo time(); ?>"></script>

<!-- Script de gestion prioritaire des modals - VERSION CORRIGÉE -->
<script src="<?php echo $assets_path; ?>js/modal-priority-manager-fixed.js"></script>

<!-- Script pour supprimer le backdrop du modal tâche -->
<script src="<?php echo $assets_path; ?>js/modal-no-backdrop.js"></script>

<!-- SCRIPT DE PRIORITÉ MAXIMALE - Correction finale pour les pièces multiples -->
<!-- CE SCRIPT DOIT ÊTRE CHARGÉ EN DERNIER POUR ÉCRASER TOUS LES AUTRES -->
<script src="<?php echo $assets_path; ?>js/modal-commande-priority-fix.js"></script>

<!-- Script de correction du mode sombre pour taskDetailsModal -->
<script src="<?php echo $assets_path; ?>js/fix-modal-dark-mode.js"></script>

<!-- Stock Modals Critical CSS & Aesthetics -->
<style>
/* 1. FORCE VISIBILITY & POSITIONING */
#gbQRRepairModal,
#gbAdjustModal {
    z-index: 20000 !important;
}

/* Modals de 2nd niveau (Raison, Partenaire) doivent être au-dessus */
#gbReasonModal,
#gbPartnerSelectModal,
#gbOtherReasonModal {
    z-index: 20010 !important;
}

#gbQRRepairModal,
#gbAdjustModal,
#gbReasonModal,
#gbPartnerSelectModal,
#gbOtherReasonModal {
    display: none;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(8px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
    overflow: hidden;
}

#gbQRRepairModal.show,
#gbAdjustModal.show,
#gbReasonModal.show,
#gbPartnerSelectModal.show,
#gbOtherReasonModal.show {
    display: flex !important;
}

/* 2. AESTHETICS - DIALOG BOX */
.modern-modal-dialog {
    background: white;
    border-radius: 24px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    position: relative;
    display: flex;
    flex-direction: column;
    z-index: 20001 !important;
    /* No animation to be safe */
    animation: none !important; 
    transition: none !important;
}

/* 3. AESTHETICS - HEADER */
.modern-modal-header {
    padding: 1.5rem 2rem;
    border-radius: 24px 24px 0 0;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modern-modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modern-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    padding-bottom: 4px; /* Align center fix */
}

/* 4. AESTHETICS - BODY & FOOTER */
.modern-modal-body {
    padding: 2rem;
}

.modern-modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    background: #f9fafb;
    border-radius: 0 0 24px 24px;
}

/* 5. AESTHETICS - BUTTONS */
.modern-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    border: none;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.25);
}

.modern-btn--secondary { background: #6b7280; box-shadow: none; }
.modern-btn--success { background: linear-gradient(135deg, #10b981, #059669); }
.modern-btn--primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }

/* 6. AESTHETICS - ADJUST CONTROLS */
.adjust-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin: 2rem 0;
}

.adjust-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    color: white;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.adjust-btn--minus { background: #ef4444; }
.adjust-btn--plus { background: #10b981; }

.adjust-value {
    font-size: 3rem;
    font-weight: 800;
    color: #1f2937;
    line-height: 1;
}

.adjust-product-ref {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border-radius: 8px;
    font-family: monospace;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

/* 7. NIGHT MODE OVERRIDES */
body.day-mode .modern-modal-dialog { background: white; color: #1f2937; }

/* Force dark mode styles on specific modals - AGGRESSIVE SELECTORS */
body.night-mode #gbQRRepairModal .modern-modal-dialog,
body.night-mode #gbAdjustModal .modern-modal-dialog,
body.night-mode #gbReasonModal .modern-modal-dialog,
body.night-mode #gbPartnerSelectModal .modern-modal-dialog,
body.night-mode #gbOtherReasonModal .modern-modal-dialog,
body.dark-mode #gbQRRepairModal .modern-modal-dialog,
body.dark-mode #gbAdjustModal .modern-modal-dialog,
body.dark-mode #gbReasonModal .modern-modal-dialog,
body.dark-mode #gbPartnerSelectModal .modern-modal-dialog,
body.dark-mode #gbOtherReasonModal .modern-modal-dialog,
/* Catch-all for any class containing 'night' or 'dark' */
body[class*="night"] #gbQRRepairModal .modern-modal-dialog,
body[class*="dark"] #gbQRRepairModal .modern-modal-dialog {
    background: #1e293b !important;
    background-color: #1e293b !important;
    color: #f8fafc !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
}

/* Force body background specifically - NO TRANSPARENCY */
body.night-mode #gbQRRepairModal .modern-modal-body,
body.night-mode #gbAdjustModal .modern-modal-body,
body.night-mode #gbReasonModal .modern-modal-body,
body.night-mode #gbPartnerSelectModal .modern-modal-body,
body.night-mode #gbOtherReasonModal .modern-modal-body,
body[class*="night"] #gbQRRepairModal .modern-modal-body,
body[class*="dark"] #gbQRRepairModal .modern-modal-body,
body[class*="night"] #gbAdjustModal .modern-modal-body,
body[class*="dark"] #gbAdjustModal .modern-modal-body {
    background: #1e293b !important;
    background-color: #1e293b !important;
    color: white !important;
}

body.night-mode #gbQRRepairModal .modern-modal-footer,
body.night-mode #gbAdjustModal .modern-modal-footer,
body.night-mode #gbReasonModal .modern-modal-footer,
body.night-mode #gbPartnerSelectModal .modern-modal-footer,
body.night-mode #gbOtherReasonModal .modern-modal-footer,
body.dark-mode #gbQRRepairModal .modern-modal-footer,
body.dark-mode #gbAdjustModal .modern-modal-footer,
body.dark-mode #gbReasonModal .modern-modal-footer,
body.dark-mode #gbPartnerSelectModal .modern-modal-footer,
body.dark-mode #gbOtherReasonModal .modern-modal-footer {
    background: #0f172a !important;
    border-top-color: rgba(255,255,255,0.05) !important;
}

body.night-mode .adjust-value,
body.dark-mode .adjust-value {
    color: white !important;
}

body.night-mode .adjust-unit,
body.dark-mode .adjust-unit {
    color: #94a3b8 !important;
}

/* Ensure inputs in dark mode are readable */
body.night-mode .modern-modal-dialog input,
body.night-mode .modern-modal-dialog select,
body.night-mode .modern-modal-dialog textarea,
body.dark-mode .modern-modal-dialog input,
body.dark-mode .modern-modal-dialog select,
body.dark-mode .modern-modal-dialog textarea {
    background-color: #334155;
    border-color: #475569;
    color: white;
}
</style>

</body>
</html>