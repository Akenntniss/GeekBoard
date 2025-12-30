<!-- ================================================================================
     MODAL PROPRE DE CRÉATION DE DEVIS - 3 ÉTAPES
     ================================================================================
     Description: Modal simple et fonctionnel pour créer des devis en 3 étapes
     Date: 2025-01-27
     ================================================================================ -->

<div class="modal fade" id="devisModalClean" tabindex="-1" aria-labelledby="devisModalCleanLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            
            <!-- En-tête du modal -->
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="devisModalCleanLabel">
                    <i class="fas fa-file-invoice me-2"></i>Créer un devis
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Indicateur d'étapes -->
            <div class="bg-light border-bottom p-3">
                <div class="step-indicator d-flex justify-content-center">
                    <div class="step-item active" data-step="1">
                        <div class="step-circle">1</div>
                        <div class="step-text">Informations</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="2">
                        <div class="step-circle">2</div>
                        <div class="step-text">Pannes</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="3">
                        <div class="step-circle">3</div>
                        <div class="step-text">Solutions</div>
                    </div>
                </div>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body">
                <form id="devisFormClean">
                    <input type="hidden" id="devis_reparation_id" name="reparation_id">
                    
                    <!-- ÉTAPE 1: Informations générales -->
                    <div class="step-content" id="step-1">
                        <div class="text-center mb-4">
                            <h5 class="text-primary">Étape 1/3 : Informations générales</h5>
                            <p class="text-muted">Définissez les informations de base du devis</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="devis_titre" class="form-label fw-bold">Titre du devis *</label>
                                    <input type="text" class="form-control" id="devis_titre" name="titre" required
                                           placeholder="Ex: Réparation écran iPhone 12">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="devis_description" class="form-label fw-bold">Description</label>
                                    <textarea class="form-control" id="devis_description" name="description" rows="3"
                                              placeholder="Description de l'appareil..."></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="devis_garantie" class="form-label fw-bold">Garantie</label>
                                            <input type="text" class="form-control" id="devis_garantie" name="garantie"
                                                   value="3 mois" placeholder="Ex: 3 mois">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ÉTAPE 2: Pannes -->
                    <div class="step-content" id="step-2" style="display: none;">
                        <div class="text-center mb-4">
                            <h5 class="text-primary">Étape 2/3 : Diagnostic des pannes</h5>
                            <p class="text-muted">Listez les problèmes identifiés</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Pannes identifiées</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="ajouterPanneBtn">
                                        <i class="fas fa-plus me-1"></i>Ajouter une panne
                                    </button>
                                </div>
                                
                                <div id="pannesContainer">
                                    <!-- Les pannes seront ajoutées ici -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ÉTAPE 3: Solutions -->
                    <div class="step-content" id="step-3" style="display: none;">
                        <div class="text-center mb-4">
                            <h5 class="text-primary">Étape 3/3 : Solutions proposées</h5>
                            <p class="text-muted">Proposez des solutions de réparation</p>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6><i class="fas fa-lightbulb text-success me-2"></i>Solutions disponibles</h6>
                                    <button type="button" class="btn btn-outline-success btn-sm" id="ajouterSolutionBtn">
                                        <i class="fas fa-plus me-1"></i>Ajouter une solution
                                    </button>
                                </div>
                                
                                <div id="solutionsContainer">
                                    <!-- Les solutions seront ajoutées ici -->
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pied de page avec boutons -->
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="precedentBtn" style="display: none;">
                    <i class="fas fa-chevron-left me-1"></i>Précédent
                </button>
                <div>
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary" id="suivantBtn">
                        Suivant<i class="fas fa-chevron-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="sauvegarderBtn" style="display: none;">
                        <span class="btn-text">
                            <i class="fas fa-save me-1"></i>Sauvegarder
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Envoi en cours...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Templates cachés -->
<template id="panneTemplate">
    <div class="panne-item card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="card-title text-danger mb-0">
                    <i class="fas fa-bug me-2"></i>Panne
                </h6>
                <button type="button" class="btn btn-outline-danger btn-sm supprimer-panne">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description *</label>
                        <input type="text" class="form-control panne-nom" name="pannes[][nom]" required
                               placeholder="Ex: Écran cassé">
                    </div>
                                                        <div class="mb-3">
                                        <label class="form-label fw-bold">Détails</label>
                                        <textarea class="form-control panne-description" name="pannes[][description]" rows="2"
                                                  placeholder="Description détaillée du problème..."></textarea>
                                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gravité</label>
                        <select class="form-select panne-gravite" name="pannes[][gravite]">
                            <option value="faible">Faible</option>
                            <option value="moyenne" selected>Moyenne</option>
                            <option value="elevee">Élevée</option>
                            <option value="critique">Critique</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="solutionTemplate">
    <div class="solution-item card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="card-title text-success mb-0">
                    <i class="fas fa-wrench me-2"></i>Solution
                </h6>
                <button type="button" class="btn btn-outline-danger btn-sm supprimer-solution">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom de la solution *</label>
                        <input type="text" class="form-control solution-nom" name="solutions[][nom]" required
                               placeholder="Ex: Remplacement écran complet">
                    </div>
                                                        <div class="mb-3">
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea class="form-control solution-description" name="solutions[][description]" rows="2"
                                                  placeholder="Description détaillée des interventions..."></textarea>
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
</template>

<style>
/* Styles spécifiques au modal de devis - Suppression de l'effet glassmorphism */
#devisModalClean .modal-content {
    background: #ffffff !important;
    backdrop-filter: none !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    opacity: 1 !important;
}

#devisModalClean .modal-header {
    background: #0d6efd !important;
    border-bottom: 1px solid #dee2e6 !important;
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

#devisModalClean .modal-body {
    background: #ffffff !important;
    padding: 2rem !important;
}

#devisModalClean .modal-footer {
    background: #f8f9fa !important;
    border-top: 1px solid #dee2e6 !important;
    border-radius: 0 0 0.5rem 0.5rem !important;
}

#devisModalClean .bg-light {
    background: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6 !important;
}

/* Styles pour les cartes dans le modal */
#devisModalClean .card {
    background: #ffffff !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    backdrop-filter: none !important;
}

#devisModalClean .card-body {
    background: #ffffff !important;
    padding: 1.25rem !important;
}

/* Suppression de tous les effets de transparence */
#devisModalClean * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Styles pour le mode sombre */
[data-bs-theme="dark"] #devisModalClean .modal-content,
.dark-mode #devisModalClean .modal-content,
body.dark-mode #devisModalClean .modal-content {
    background: #2b3035 !important;
    border: 1px solid #495057 !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] #devisModalClean .modal-header,
.dark-mode #devisModalClean .modal-header,
body.dark-mode #devisModalClean .modal-header {
    background: #0d6efd !important;
    border-bottom: 1px solid #495057 !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] #devisModalClean .modal-body,
.dark-mode #devisModalClean .modal-body,
body.dark-mode #devisModalClean .modal-body {
    background: #2b3035 !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] #devisModalClean .modal-footer,
.dark-mode #devisModalClean .modal-footer,
body.dark-mode #devisModalClean .modal-footer {
    background: #343a40 !important;
    border-top: 1px solid #495057 !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] #devisModalClean .bg-light,
.dark-mode #devisModalClean .bg-light,
body.dark-mode #devisModalClean .bg-light {
    background: #343a40 !important;
    border-bottom: 1px solid #495057 !important;
    color: #ffffff !important;
}

/* Cartes en mode sombre */
[data-bs-theme="dark"] #devisModalClean .card,
.dark-mode #devisModalClean .card,
body.dark-mode #devisModalClean .card {
    background: #343a40 !important;
    border: 1px solid #495057 !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] #devisModalClean .card-body,
.dark-mode #devisModalClean .card-body,
body.dark-mode #devisModalClean .card-body {
    background: #343a40 !important;
    color: #ffffff !important;
}

/* Champs de formulaire en mode sombre */
[data-bs-theme="dark"] #devisModalClean .form-control,
.dark-mode #devisModalClean .form-control,
body.dark-mode #devisModalClean .form-control {
    background: #495057 !important;
    border: 1px solid #6c757d !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] #devisModalClean .form-control:focus,
.dark-mode #devisModalClean .form-control:focus,
body.dark-mode #devisModalClean .form-control:focus {
    background: #495057 !important;
    border-color: #0d6efd !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

[data-bs-theme="dark"] #devisModalClean .form-control::placeholder,
.dark-mode #devisModalClean .form-control::placeholder,
body.dark-mode #devisModalClean .form-control::placeholder {
    color: #adb5bd !important;
}

/* Select en mode sombre */
[data-bs-theme="dark"] #devisModalClean .form-select,
.dark-mode #devisModalClean .form-select,
body.dark-mode #devisModalClean .form-select {
    background: #495057 !important;
    border: 1px solid #6c757d !important;
    color: #ffffff !important;
}

/* Labels en mode sombre */
[data-bs-theme="dark"] #devisModalClean .form-label,
.dark-mode #devisModalClean .form-label,
body.dark-mode #devisModalClean .form-label {
    color: #ffffff !important;
}

/* Texte en mode sombre */
[data-bs-theme="dark"] #devisModalClean .text-muted,
.dark-mode #devisModalClean .text-muted,
body.dark-mode #devisModalClean .text-muted {
    color: #adb5bd !important;
}

[data-bs-theme="dark"] #devisModalClean .text-primary,
.dark-mode #devisModalClean .text-primary,
body.dark-mode #devisModalClean .text-primary {
    color: #6ea8fe !important;
}

/* Titres de cartes en mode sombre */
[data-bs-theme="dark"] #devisModalClean .card-title,
.dark-mode #devisModalClean .card-title,
body.dark-mode #devisModalClean .card-title {
    color: #ffffff !important;
}

/* Input group text en mode sombre */
[data-bs-theme="dark"] #devisModalClean .input-group-text,
.dark-mode #devisModalClean .input-group-text,
body.dark-mode #devisModalClean .input-group-text {
    background: #495057 !important;
    border: 1px solid #6c757d !important;
    color: #ffffff !important;
}

/* Styles pour les indicateurs d'étapes */
.step-indicator {
    max-width: 500px;
    margin: 0 auto;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #6c757d;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.step-item.active .step-circle {
    background: #0d6efd;
    border-color: #0d6efd;
    color: white;
    transform: scale(1.1);
}

.step-item.completed .step-circle {
    background: #198754;
    border-color: #198754;
    color: white;
}

.step-text {
    font-size: 0.9rem;
    color: #6c757d;
    text-align: center;
}

.step-item.active .step-text {
    color: #0d6efd;
    font-weight: 600;
}

.step-item.completed .step-text {
    color: #198754;
    font-weight: 600;
}

.step-line {
    flex: 1;
    height: 2px;
    background: #dee2e6;
    margin: 0 15px;
    margin-top: 20px;
}

/* Animations */
.step-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Animations pour l'envoi du devis */
#sauvegarderBtn {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

#sauvegarderBtn.loading {
    background: #198754 !important;
    cursor: not-allowed;
    pointer-events: none;
}

#sauvegarderBtn .btn-text {
    transition: opacity 0.3s ease;
}

#sauvegarderBtn .btn-loading {
    transition: opacity 0.3s ease;
}

#sauvegarderBtn.loading .btn-text {
    opacity: 0;
}

#sauvegarderBtn.loading .btn-loading {
    opacity: 1;
}

/* Animation de succès */
@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

#sauvegarderBtn.success {
    background: #198754 !important;
    animation: successPulse 0.6s ease;
}

/* Animation du modal lors de l'envoi */
#devisModalClean.sending {
    animation: modalShake 0.5s ease;
}

@keyframes modalShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

/* Animation de fermeture avec succès */
@keyframes slideOutUp {
    from { 
        opacity: 1; 
        transform: translateY(0); 
    }
    to { 
        opacity: 0; 
        transform: translateY(-50px); 
    }
}

#devisModalClean.success-exit {
    animation: slideOutUp 0.5s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .step-indicator {
        flex-direction: column;
        gap: 10px;
    }
    
    .step-line {
        display: none;
    }
    
    .modal-xl .modal-dialog {
        max-width: 95%;
        margin: 10px auto;
    }
}
</style>

