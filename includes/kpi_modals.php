<!-- Modals HTML pour KPI Dashboard -->

<!-- Modal Notes Employés -->
<div class="modal fade" id="employeeNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>
                    <span id="noteModalTitle">Ajouter une note employé</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="employeeNoteForm">
                    <input type="hidden" id="noteId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-user text-primary me-1"></i> Employé *
                            </label>
                            <select class="form-select" id="noteEmployeeId" name="employee_id" required>
                                <option value="">Sélectionner un employé</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tag text-warning me-1"></i> Type *
                            </label>
                            <select class="form-select" id="noteType" name="note_type" required>
                                <option value="avertissement">🚨 Avertissement</option>
                                <option value="incident">⚠️ Incident</option>
                                <option value="appreciation">👍 Appréciation</option>
                                <option value="remarque">📌 Remarque</option>
                                <option value="sanction">🔴 Sanction</option>
                                <option value="autre">📋 Autre</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-exclamation-triangle text-danger me-1"></i> Gravité
                            </label>
                            <select class="form-select" id="noteSeverity" name="severity">
                                <option value="info">ℹ️ Info</option>
                                <option value="low">⚡ Faible</option>
                                <option value="medium" selected>⚠️ Moyen</option>
                                <option value="high">🔴 Élevé</option>
                                <option value="critical">🚨 Critique</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar text-info me-1"></i> Date de l'incident
                            </label>
                            <input type="date" class="form-control" id="noteDateIncident" 
                                   name="date_incident" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-heading text-success me-1"></i> Titre *
                            </label>
                            <input type="text" class="form-control" id="noteTitle" 
                                   name="title" placeholder="Ex: Retard répété" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-align-left text-secondary me-1"></i> Description *
                            </label>
                            <textarea class="form-control" id="noteDescription" name="description" 
                                      rows="4" placeholder="Détails de la remarque..." required></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="noteIncludeAI" 
                                       name="include_in_ai_analysis" checked>
                                <label class="form-check-label" for="noteIncludeAI">
                                    <i class="fas fa-robot me-1"></i> Inclure dans analyse IA
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notePrivate" 
                                       name="is_private" checked>
                                <label class="form-check-label" for="notePrivate">
                                    <i class="fas fa-lock me-1"></i> Note privée
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="noteResolved" 
                                       name="is_resolved">
                                <label class="form-check-label" for="noteResolved">
                                    <i class="fas fa-check-circle me-1"></i> Problème résolu
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" onclick="saveEmployeeNote()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Notes Magasin -->
<div class="modal fade" id="shopNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-store me-2"></i>
                    <span id="shopNoteModalTitle">Ajouter un événement magasin</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="shopNoteForm">
                    <input type="hidden" id="shopNoteId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tag text-primary me-1"></i> Type d'événement *
                            </label>
                            <select class="form-select" id="shopNoteType" name="note_type" required>
                                <option value="fermeture">🚪 Fermeture</option>
                                <option value="travaux">🛠️ Travaux</option>
                                <option value="evenement">🎉 Événement</option>
                                <option value="probleme_technique">⚡ Problème technique</option>
                                <option value="stock">📦 Stock/Approvisionnement</option>
                                <option value="autre">📋 Autre</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-exclamation-circle text-warning me-1"></i> Niveau d'impact
                            </label>
                            <select class="form-select" id="shopImpactLevel" name="impact_level">
                                <option value="info">ℹ️ Info</option>
                                <option value="low">🟢 Faible</option>
                                <option value="medium" selected>🟠 Moyen</option>
                                <option value="high">🔴 Élevé</option>
                                <option value="critical">🚨 Critique</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar-alt text-success me-1"></i> Date début *
                            </label>
                            <input type="date" class="form-control" id="shopDateStart" 
                                   name="date_start" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar-check text-info me-1"></i> Date fin
                            </label>
                            <input type="date" class="form-control" id="shopDateEnd" name="date_end">
                            <small class="text-muted">Laisser vide pour événement ponctuel</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-heading text-primary me-1"></i> Titre *
                            </label>
                            <input type="text" class="form-control" id="shopTitle" 
                                   name="title" placeholder="Ex: Fermeture travaux" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-align-left text-secondary me-1"></i> Description *
                            </label>
                            <textarea class="form-control" id="shopDescription" name="description" 
                                      rows="4" placeholder="Détails de l'événement..." required></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="shopAffectsKPI" 
                                       name="affects_kpi" checked>
                                <label class="form-check-label" for="shopAffectsKPI">
                                    <i class="fas fa-chart-line me-1"></i> Affecte les KPI
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="shopIncludeAI" 
                                       name="include_in_ai_analysis" checked>
                                <label class="form-check-label" for="shopIncludeAI">
                                    <i class="fas fa-robot me-1"></i> Inclure dans analyse IA
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" onclick="saveShopNote()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Profil IA -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-robot me-2"></i>
                    <span id="profileModalTitle">Créer un profil d'expert IA</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <input type="hidden" id="profileId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">
                                <i class="fas fa-signature text-primary me-1"></i> Nom du profil *
                            </label>
                            <input type="text" class="form-control" id="profileName" name="name" 
                                   placeholder="Ex: Expert Marketing Digital" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-icons text-warning me-1"></i> Icône Font Awesome
                            </label>
                            <input type="text" class="form-control" id="profileIcon" name="icon" 
                                   value="fas fa-user" placeholder="fas fa-user">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-info-circle text-info me-1"></i> Description
                            </label>
                            <input type="text" class="form-control" id="profileDescription" name="description" 
                                   placeholder="Courte description du rôle">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-code text-success me-1"></i> Prompt système (Instructions pour l'IA) *
                            </label>
                            <textarea class="form-control font-monospace" id="profilePrompt" 
                                      name="system_prompt" rows="8" required 
                                      placeholder="Tu es un expert en... Ton rôle est de... Structure ton rapport en..."></textarea>
                            <small class="text-muted">
                                💡 Définissez le rôle, le style d'analyse et la structure attendue
                            </small>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>📝 Exemples de prompts :</strong>
                                <ul class="mb-0 mt-2">
                                    <li>"Tu es un expert en optimisation opérationnelle..."</li>
                                    <li>"Tu es un consultant RH spécialisé en bien-être au travail..."</li>
                                    <li>"Tu es un analyste financier avec vision long terme..."</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="profileActive" 
                                       name="active" checked>
                                <label class="form-check-label" for="profileActive">
                                    <i class="fas fa-toggle-on me-1"></i> Profil actif
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-outline-info" onclick="testProfile()">
                    <i class="fas fa-vial me-2"></i>Tester
                </button>
                <button type="button" class="btn btn-primary" onclick="saveProfile()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
    </div>
</div>

<!-- Modal Info Profil IA -->
<div class="modal fade" id="profileInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content ai-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-robot me-2"></i>
                    <span id="profileInfoName">Profil IA</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Avatar du profil -->
                <div class="text-center mb-4">
                    <div id="profileInfoAvatar" class="ai-profile-avatar-large">
                        <!-- Avatar généré dynamiquement -->
                    </div>
                </div>
                
                <!-- Description complète -->
                <div class="profile-info-section">
                    <h6><i class="fas fa-align-left me-2"></i>Description</h6>
                    <p id="profileInfoDescription" class="profile-description"></p>
                </div>
                
                <!-- Statut -->
                <div class="profile-info-section">
                    <h6><i class="fas fa-toggle-on me-2"></i>Statut</h6>
                    <span id="profileInfoStatus" class="badge"></span>
                </div>
                
                <!-- Expertise -->
                <div class="profile-info-section">
                    <h6><i class="fas fa-brain me-2"></i>Domaine d'Expertise</h6>
                    <p id="profileInfoExpertise"></p>
                </div>
                
                <!-- Date de création -->
                <div class="profile-info-section">
                    <h6><i class="fas fa-calendar me-2"></i>Créé le</h6>
                    <p id="profileInfoDate"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
                <button type="button" class="btn btn-warning" onclick="editAIProfile()">
                    <i class="fas fa-edit me-2"></i>Modifier le Profil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Analyse IA Futuriste -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ai-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-robot me-2"></i>
                    <span id="aiModalProfileName">Expert IA</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Description du profil -->
                <div id="aiProfileDescription" class="ai-profile-desc mb-4">
                    <p class="text-muted mb-0"></p>
                </div>
                
                <!-- Choix du type d'analyse -->
                <div id="aiChoiceSection">
                    <h6 class="text-center mb-4" style="color: #60a5fa; font-weight: 600;">
                        <i class="fas fa-layer-group me-2"></i>
                        Choisissez le type d'analyse
                    </h6>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="ai-choice-card" onclick="selectAnalysisType('global')">
                                <div class="ai-choice-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <h5>Analyse Globale</h5>
                                <p>Analyse complète du magasin et de tous les KPI généraux</p>
                                <div class="ai-choice-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ai-choice-card" onclick="selectAnalysisType('employee')">
                                <div class="ai-choice-icon" style="background: linear-gradient(135deg, #28a745 0%, #00ff88 100%);">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h5>Analyse par Employé</h5>
                                <p>Analyse détaillée d'un employé avec KPI et notes</p>
                                <div class="ai-choice-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sélection employé (masqué par défaut) -->
                <div id="aiEmployeeSection" style="display: none;">
                    <button class="btn btn-sm btn-outline-secondary mb-3" onclick="backToChoice()">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </button>
                    <h6 class="mb-3" style="color: #60a5fa; font-weight: 600;">
                        <i class="fas fa-users me-2"></i>
                        Sélectionnez un employé
                    </h6>
                    <div id="employeeList" class="employee-list">
                        <!-- Liste générée dynamiquement -->
                    </div>
                </div>
                
                <!-- Récapitulatif KPI Employé (masqué par défaut) -->
                <div id="aiEmployeeKPISection" style="display: none;">
                    <button class="btn btn-sm btn-outline-secondary mb-3" onclick="backToEmployeeList()">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </button>
                    <h6 class="mb-3" style="color: #60a5fa; font-weight: 600;">
                        <i class="fas fa-chart-bar me-2"></i>
                        KPI à analyser pour <span id="selectedEmployeeName"></span>
                    </h6>
                    
                    <div id="employeeKPIGrid" class="kpi-grid mb-4">
                        <!-- KPI générés dynamiquement -->
                    </div>
                    
                    <div class="text-center">
                        <button class="btn btn-ai-launch" onclick="confirmAndLaunchAnalysis()">
                            <i class="fas fa-magic me-2"></i>
                            Lancer l'Analyse avec ces données
                        </button>
                    </div>
                </div>
                
                <!-- Récapitulatif KPI Globaux (masqué par défaut) -->
                <div id="aiGlobalKPISection" style="display: none;">
                    <button class="btn btn-sm btn-outline-secondary mb-3" onclick="backToChoice()">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </button>
                    <h6 class="mb-3" style="color: #60a5fa; font-weight: 600;">
                        <i class="fas fa-chart-bar me-2"></i>
                        KPI Globaux à analyser
                    </h6>
                    
                    <div id="globalKPIGrid" class="kpi-grid mb-4">
                        <!-- KPI générés dynamiquement -->
                    </div>
                    
                    <div class="text-center">
                        <button class="btn btn-ai-launch" onclick="confirmAndLaunchGlobalAnalysis()">
                            <i class="fas fa-magic me-2"></i>
                            Lancer l'Analyse Globale
                        </button>
                    </div>
                </div>
                
                <!-- Animation de chargement -->
                <div id="aiLoadingSection" class="ai-loading" style="display: none;">
                    <div class="ai-loader">
                        <div class="ai-brain">
                            <div class="ai-pulse"></div>
                            <div class="ai-pulse"></div>
                            <div class="ai-pulse"></div>
                        </div>
                        <p class="mt-4 text-center ai-loading-text">
                            <i class="fas fa-cog fa-spin me-2"></i>
                            Analyse en cours...
                        </p>
                    </div>
                </div>
                
                <!-- Résultats -->
                <div id="aiResultsSection" style="display: none;">
                    <div class="ai-results-container">
                        <h6 class="mb-3">
                            <i class="fas fa-chart-line me-2"></i>
                            Résultats de l'Analyse
                        </h6>
                        <div id="aiAnalysisContent" class="ai-content">
                            <!-- Contenu généré ici -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal IA Futuriste */
#aiAnalysisModal {
    z-index: 99999 !important;
}

#aiAnalysisModal .modal-backdrop {
    z-index: 99998 !important;
}

#aiAnalysisModal .modal-dialog {
    z-index: 100000 !important;
    position: relative !important;
    width: 90vw !important;
    max-width: 1200px !important;
    height: auto !important;
    margin: 1.75rem auto !important;
    display: flex !important;
    align-items: center !important;
}

.modal.show #aiAnalysisModal {
    display: block !important;
}

.ai-modal {
    background: linear-gradient(135deg, #1a1d3a 0%, #2a2d4a 100%);
    border: 1px solid rgba(96, 165, 250, 0.3);
    border-radius: 20px;
    color: #e0e6ed;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    position: relative !important;
    z-index: 100001 !important;
    width: 100% !important;
    min-height: 400px !important;
    display: block !important;
}

.ai-modal .modal-header {
    padding: 1.5rem 2rem;
    background: rgba(96, 165, 250, 0.1);
}

.ai-modal .modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #60a5fa 0%, #00e5ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ai-modal .btn-close-white {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.ai-modal .btn-close-white:hover {
    opacity: 1;
}

.ai-profile-desc {
    padding: 1.5rem;
    background: rgba(96, 165, 250, 0.1);
    border-left: 4px solid #60a5fa;
    border-radius: 12px;
    font-size: 1.05rem;
    line-height: 1.6;
}

.btn-ai-launch {
    padding: 1.2rem 3.5rem;
    background: linear-gradient(135deg, #0078e8 0%, #00d4ff 100%);
    border: none;
    border-radius: 50px;
    color: white;
    font-weight: 700;
    font-size: 1.15rem;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 10px 30px rgba(0, 120, 232, 0.4);
    position: relative;
    overflow: hidden;
}

.btn-ai-launch::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.6s ease;
}

.btn-ai-launch:hover::before {
    left: 100%;
}

.btn-ai-launch:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 40px rgba(0, 212, 255, 0.6);
}

.btn-ai-launch:active {
    transform: translateY(-1px) scale(1.02);
}

/* Animation de chargement */
.ai-loading {
    padding: 4rem 2rem;
    text-align: center;
}

.ai-brain {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto;
}

.ai-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 4px solid #60a5fa;
    border-radius: 50%;
    opacity: 0;
    animation: pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.ai-pulse:nth-child(1) {
    animation-delay: 0s;
    border-color: #0078e8;
}

.ai-pulse:nth-child(2) {
    animation-delay: 0.8s;
    border-color: #60a5fa;
}

.ai-pulse:nth-child(3) {
    animation-delay: 1.6s;
    border-color: #00d4ff;
}

@keyframes pulse {
    0% {
        transform: scale(0.3);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.6;
    }
    100% {
        transform: scale(1.6);
        opacity: 0;
    }
}

.ai-loading-text {
    font-size: 1.2rem;
    font-weight: 600;
    color: #60a5fa;
}

/* Résultats */
.ai-results-container {
    padding: 2rem;
    background: rgba(30, 33, 57, 0.6);
    border-radius: 15px;
    border: 1px solid rgba(96, 165, 250, 0.3);
    animation: fadeInUp 0.6s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ai-results-container h6 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #60a5fa;
}

.ai-content {
    line-height: 1.9;
    color: #cbd5e1;
    font-size: 1.05rem;
}

.ai-content strong {
    color: #60a5fa;
    font-weight: 600;
}

.ai-content p {
    margin-bottom: 1.2rem;
}

.ai-content code {
    background: rgba(96, 165, 250, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

/* Sections d'analyse structurées */
.ai-section {
    margin-bottom: 1.5rem;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid;
    transition: all 0.3s ease;
}

.ai-section-header {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 700;
}

.ai-section-header i {
    font-size: 1.5rem;
}

.ai-section-header h4 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}

.ai-section-content {
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
}

.ai-section-content p {
    margin-bottom: 1rem;
    line-height: 1.7;
}

.ai-section-content p:last-child {
    margin-bottom: 0;
}

.ai-subtitle {
    color: #60a5fa;
    font-weight: 600;
    font-size: 1.05rem;
    margin: 1.5rem 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ai-list {
    list-style: none;
    padding-left: 0;
    margin: 1rem 0;
}

.ai-list li {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: rgba(96, 165, 250, 0.08);
    border-left: 3px solid #60a5fa;
    border-radius: 6px;
}

.ai-list li strong {
    color: #60a5fa;
}

/* Variantes de couleurs pour sections */
.ai-section-primary {
    border-color: #60a5fa;
}

.ai-section-primary .ai-section-header {
    background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    color: white;
}

.ai-section-success {
    border-color: #22c55e;
}

.ai-section-success .ai-section-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.ai-section-warning {
    border-color: #fbbf24;
}

.ai-section-warning .ai-section-header {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #1e293b;
}

.ai-section-info {
    border-color: #06b6d4;
}

.ai-section-info .ai-section-header {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: white;
}

.ai-section-purple {
    border-color: #a855f7;
}

.ai-section-purple .ai-section-header {
    background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
    color: white;
}

.ai-section-teal {
    border-color: #14b8a6;
}

.ai-section-teal .ai-section-header {
    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
    color: white;
}

.ai-content br {
    display: block;
    margin: 0.5rem 0;
    content: "";
}

/* Cartes de choix */
.ai-choice-card {
    background: rgba(96, 165, 250, 0.1);
    border: 2px solid rgba(96, 165, 250, 0.3);
    border-radius: 15px;
    padding: 2rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.ai-choice-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.2), transparent);
    transition: left 0.5s ease;
}

.ai-choice-card:hover::before {
    left: 100%;
}

.ai-choice-card:hover {
    transform: translateY(-5px) scale(1.02);
    border-color: #60a5fa;
    box-shadow: 0 10px 30px rgba(96, 165, 250, 0.3);
}

.ai-choice-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #0078e8 0%, #00d4ff 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.ai-choice-card h5 {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #60a5fa;
}

.ai-choice-card p {
    font-size: 0.9rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.ai-choice-arrow {
    font-size: 1.5rem;
    color: #60a5fa;
    opacity: 0;
    transition: all 0.3s ease;
}

.ai-choice-card:hover .ai-choice-arrow {
    opacity: 1;
    transform: translateX(10px);
}

/* Liste employés */
.employee-list {
    max-height: 400px;
    overflow-y: auto;
}

.employee-item {
    padding: 1rem;
    background: rgba(96, 165, 250, 0.05);
    border: 1px solid rgba(96, 165, 250, 0.2);
    border-radius: 10px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.employee-item:hover {
    background: rgba(96, 165, 250, 0.15);
    border-color: #60a5fa;
    transform: translateX(5px);
}

.employee-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.employee-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #0078e8 0%, #00d4ff 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.employee-name {
    font-weight: 600;
    color: #cbd5e1;
}

.employee-role {
    font-size: 0.85rem;
    color: #94a3b8;
}

/* Grille KPI */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    max-height: 500px;
    overflow-y: auto;
    padding: 0.5rem;
}

.kpi-card {
    background: rgba(96, 165, 250, 0.08);
    border: 1px solid rgba(96, 165, 250, 0.25);
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.kpi-card:hover {
    background: rgba(96, 165, 250, 0.12);
    border-color: #60a5fa;
    transform: translateY(-2px);
}

.kpi-card.selected {
    background: rgba(96, 165, 250, 0.2);
    border: 2px solid #60a5fa;
    box-shadow: 0 0 20px rgba(96, 165, 250, 0.4);
    transform: scale(1.02);
}

.kpi-card.selected::after {
    content: '\f00c';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    top: 10px;
    right: 10px;
    color: #60a5fa;
    font-size: 1.2rem;
    animation: checkBounce 0.3s ease;
}

@keyframes checkBounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}

.kpi-card-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.kpi-card-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #60a5fa;
    margin-bottom: 0.25rem;
}

.kpi-card-subtitle {
    font-size: 0.75rem;
    color: #64748b;
}

/* Variantes de couleurs pour les notes */
.kpi-card.kpi-warning {
    border-color: rgba(251, 191, 36, 0.4);
    background: rgba(251, 191, 36, 0.08);
}

.kpi-card.kpi-warning .kpi-card-value {
    color: #fbbf24;
}

.kpi-card.kpi-danger {
    border-color: rgba(239, 68, 68, 0.4);
    background: rgba(239, 68, 68, 0.08);
}

.kpi-card.kpi-danger .kpi-card-value {
    color: #ef4444;
}

.kpi-card.kpi-critical {
    border-color: rgba(220, 38, 38, 0.5);
    background: rgba(220, 38, 38, 0.1);
}

.kpi-card.kpi-critical .kpi-card-value {
    color: #dc2626;
}

.kpi-card.kpi-success {
    border-color: rgba(34, 197, 94, 0.4);
    background: rgba(34, 197, 94, 0.08);
}

.kpi-card.kpi-success .kpi-card-value {
    color: #22c55e;
}

.kpi-card.kpi-info {
    border-color: rgba(59, 130, 246, 0.4);
    background: rgba(59, 130, 246, 0.08);
}

.kpi-card.kpi-info .kpi-card-value {
    color: #3b82f6;
}

.kpi-card.kpi-neutral {
    border-color: rgba(148, 163, 184, 0.3);
    background: rgba(148, 163, 184, 0.05);
}

.kpi-card.kpi-neutral .kpi-card-value {
    color: #94a3b8;
}

/* Modal Info Profil */
.ai-profile-avatar-large {
    width: 150px;
    height: 150px;
    margin: 0 auto 1rem;
}

.profile-info-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(96, 165, 250, 0.05);
    border-radius: 10px;
    border-left: 3px solid #60a5fa;
}

.profile-info-section h6 {
    color: #60a5fa;
    font-weight: 600;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.profile-info-section p {
    margin: 0;
    color: #cbd5e1;
    line-height: 1.6;
}

.profile-description {
    white-space: pre-line;
}

/* Mode jour */
[data-theme="light"] .ai-modal {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-color: rgba(0, 120, 232, 0.2);
    color: #1e293b;
}

[data-theme="light"] .ai-modal .modal-header {
    background: rgba(0, 120, 232, 0.05);
}

[data-theme="light"] .ai-modal .modal-title {
    background: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

[data-theme="light"] .ai-modal .btn-close-white {
    filter: none;
    opacity: 1;
}

[data-theme="light"] .ai-profile-desc {
    background: rgba(0, 120, 232, 0.08);
    border-left-color: #0078e8;
    color: #475569;
}

[data-theme="light"] .ai-pulse {
    border-color: #0078e8;
}

[data-theme="light"] .ai-pulse:nth-child(1) {
    border-color: #0056b3;
}

[data-theme="light"] .ai-pulse:nth-child(2) {
    border-color: #0078e8;
}

[data-theme="light"] .ai-pulse:nth-child(3) {
    border-color: #00a8ff;
}

[data-theme="light"] .ai-loading-text {
    color: #0078e8;
}

[data-theme="light"] .ai-results-container {
    background: rgba(248, 250, 252, 0.9);
    border-color: rgba(0, 120, 232, 0.15);
}

[data-theme="light"] .ai-results-container h6 {
    color: #0078e8;
}

[data-theme="light"] .ai-content {
    color: #475569;
}

[data-theme="light"] .ai-content strong {
    color: #0056b3;
}
</style>
