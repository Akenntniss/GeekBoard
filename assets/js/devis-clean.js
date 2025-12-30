/**
 * ================================================================================
 * GESTIONNAIRE PROPRE POUR LE MODAL DE DEVIS - 3 ÉTAPES
 * ================================================================================
 * Description: Script simple et fonctionnel pour gérer le modal de devis
 * Date: 2025-01-27
 * ================================================================================
 */

class DevisCleanManager {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 3;
        this.reparationId = null;

        console.log('🚀 [DEVIS-CLEAN] Initialisation du gestionnaire propre');
        this.init();
    }

    init() {
        this.attachEvents();
        console.log('✅ [DEVIS-CLEAN] Gestionnaire initialisé');
    }

    attachEvents() {
        console.log('🔧 [DEVIS-CLEAN] Attachement des événements...');

        // Événements de navigation
        document.addEventListener('click', (e) => {
            const modal = e.target.closest('#devisModalClean');
            if (!modal) return;

            console.log('🎯 [DEVIS-CLEAN] Clic détecté dans le modal, élément:', e.target.id || e.target.className);

            if (e.target.id === 'suivantBtn') {
                e.preventDefault();
                console.log('➡️ [DEVIS-CLEAN] Bouton suivant cliqué');
                this.nextStep();
            }

            if (e.target.id === 'precedentBtn') {
                e.preventDefault();
                console.log('⬅️ [DEVIS-CLEAN] Bouton précédent cliqué');
                this.prevStep();
            }

            if (e.target.id === 'sauvegarderBtn') {
                e.preventDefault();
                console.log('🔴 [DEVIS-CLEAN] Bouton sauvegarder cliqué !');
                this.saveDevis();
            }

            if (e.target.id === 'ajouterPanneBtn') {
                e.preventDefault();
                this.ajouterPanne();
            }

            if (e.target.id === 'ajouterSolutionBtn') {
                e.preventDefault();
                this.ajouterSolution();
            }

            if (e.target.classList.contains('supprimer-panne')) {
                e.preventDefault();
                this.supprimerPanne(e.target);
            }

            if (e.target.classList.contains('supprimer-solution')) {
                e.preventDefault();
                this.supprimerSolution(e.target);
            }
        });

        // Événement d'ouverture du modal
        const modalElement = document.getElementById('devisModalClean');
        if (modalElement) {
            modalElement.addEventListener('show.bs.modal', (e) => {
                this.onModalShow(e);

                // Ajouter un événement direct sur le bouton sauvegarder
                const sauvegarderBtn = modalElement.querySelector('#sauvegarderBtn');
                if (sauvegarderBtn) {
                    sauvegarderBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('🔴 [DEVIS-CLEAN] Bouton sauvegarder cliqué directement !');
                        this.saveDevis();
                    });
                    console.log('✅ [DEVIS-CLEAN] Événement direct attaché au bouton sauvegarder');
                } else {
                    console.error('❌ [DEVIS-CLEAN] Bouton sauvegarder non trouvé !');
                }
            });
        }
    }

    onModalShow(event) {
        console.log('📂 [DEVIS-CLEAN] Ouverture du modal');

        // Récupérer l'ID de réparation
        const trigger = event.relatedTarget;
        if (trigger && trigger.dataset.reparationId) {
            this.reparationId = trigger.dataset.reparationId;
            document.getElementById('devis_reparation_id').value = this.reparationId;
            console.log('🔍 [DEVIS-CLEAN] ID de réparation:', this.reparationId);
        }

        // Réinitialiser le modal
        this.resetModal();
        this.goToStep(1);
    }

    resetModal() {
        console.log('🔄 [DEVIS-CLEAN] Réinitialisation du modal');

        // Réinitialiser le formulaire
        const form = document.getElementById('devisFormClean');
        if (form) {
            form.reset();
        }

        // Vider les conteneurs
        document.getElementById('pannesContainer').innerHTML = '';
        document.getElementById('solutionsContainer').innerHTML = '';

        // Ajouter une panne et une solution par défaut
        this.ajouterPanne();
        this.ajouterSolution();
    }

    goToStep(step) {
        if (step < 1 || step > this.totalSteps) return;

        console.log(`🚶 [DEVIS-CLEAN] Navigation vers l'étape ${step}`);

        this.currentStep = step;

        // Cacher toutes les étapes
        document.querySelectorAll('.step-content').forEach(content => {
            content.style.display = 'none';
        });

        // Afficher l'étape courante
        const currentContent = document.getElementById(`step-${step}`);
        if (currentContent) {
            currentContent.style.display = 'block';
        }

        // Mettre à jour les indicateurs
        this.updateStepIndicators();
        this.updateButtons();
    }

    updateStepIndicators() {
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const stepNumber = index + 1;
            item.classList.remove('active', 'completed');

            if (stepNumber === this.currentStep) {
                item.classList.add('active');
            } else if (stepNumber < this.currentStep) {
                item.classList.add('completed');
            }
        });
    }

    updateButtons() {
        const precedentBtn = document.getElementById('precedentBtn');
        const suivantBtn = document.getElementById('suivantBtn');
        const sauvegarderBtn = document.getElementById('sauvegarderBtn');

        // Bouton précédent
        if (precedentBtn) {
            precedentBtn.style.display = this.currentStep > 1 ? 'block' : 'none';
        }

        // Bouton suivant
        if (suivantBtn) {
            suivantBtn.style.display = this.currentStep < this.totalSteps ? 'block' : 'none';
        }

        // Bouton sauvegarder
        if (sauvegarderBtn) {
            sauvegarderBtn.style.display = this.currentStep === this.totalSteps ? 'block' : 'none';
        }

        console.log(`🔘 [DEVIS-CLEAN] Boutons mis à jour pour l'étape ${this.currentStep}`);
    }

    nextStep() {
        if (this.currentStep < this.totalSteps) {
            if (this.validateCurrentStep()) {
                this.goToStep(this.currentStep + 1);
            }
        }
    }

    prevStep() {
        if (this.currentStep > 1) {
            this.goToStep(this.currentStep - 1);
        }
    }

    validateCurrentStep() {
        const errors = [];
        console.log('🔍 [DEVIS-CLEAN] Validation étape:', this.currentStep);

        if (this.currentStep === 1) {
            // Valider l'étape 1
            const titre = document.getElementById('devis_titre').value.trim();
            console.log('📝 [DEVIS-CLEAN] Titre:', titre);
            if (!titre) {
                errors.push('Le titre du devis est obligatoire');
            }
        }

        if (this.currentStep === 2) {
            // Valider l'étape 2 - au moins une panne
            const pannes = document.querySelectorAll('.panne-item');
            if (pannes.length === 0) {
                errors.push('Ajoutez au moins une panne');
            } else {
                pannes.forEach((panne, index) => {
                    const nom = panne.querySelector('.panne-nom').value.trim();
                    if (!nom) {
                        errors.push(`La panne ${index + 1} doit avoir une description`);
                    }
                });
            }
        }

        if (this.currentStep === 3) {
            // Valider l'étape 3 - au moins une solution
            const solutions = document.querySelectorAll('.solution-item');
            if (solutions.length === 0) {
                errors.push('Ajoutez au moins une solution');
            } else {
                solutions.forEach((solution, index) => {
                    const nom = solution.querySelector('.solution-nom').value.trim();
                    const prix = solution.querySelector('.solution-prix').value;

                    if (!nom) {
                        errors.push(`La solution ${index + 1} doit avoir un nom`);
                    }
                    if (!prix || parseFloat(prix) <= 0) {
                        errors.push(`La solution ${index + 1} doit avoir un prix valide`);
                    }
                });
            }
        }

        if (errors.length > 0) {
            console.log('❌ [DEVIS-CLEAN] Erreurs de validation:', errors);
            alert('Erreurs de validation :\n• ' + errors.join('\n• '));
            return false;
        }

        console.log('✅ [DEVIS-CLEAN] Validation réussie');
        return true;
    }

    ajouterPanne() {
        const template = document.getElementById('panneTemplate');
        const container = document.getElementById('pannesContainer');

        if (template && container) {
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
            console.log('➕ [DEVIS-CLEAN] Panne ajoutée');
        }
    }

    supprimerPanne(button) {
        const panneItem = button.closest('.panne-item');
        if (panneItem) {
            panneItem.remove();
            console.log('➖ [DEVIS-CLEAN] Panne supprimée');
        }
    }

    ajouterSolution() {
        const template = document.getElementById('solutionTemplate');
        const container = document.getElementById('solutionsContainer');

        if (template && container) {
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
            console.log('➕ [DEVIS-CLEAN] Solution ajoutée');
        }
    }

    supprimerSolution(button) {
        const solutionItem = button.closest('.solution-item');
        if (solutionItem) {
            solutionItem.remove();
            console.log('➖ [DEVIS-CLEAN] Solution supprimée');
        }
    }

    async saveDevis() {
        console.log('💾 [DEVIS-CLEAN] Sauvegarde du devis...');

        if (!this.validateCurrentStep()) {
            console.log('❌ [DEVIS-CLEAN] Validation échouée');
            return;
        }

        if (!this.reparationId) {
            console.log('❌ [DEVIS-CLEAN] ID de réparation manquant:', this.reparationId);
            alert('Erreur: ID de réparation manquant');
            return;
        }

        console.log('✅ [DEVIS-CLEAN] Validation OK, ID réparation:', this.reparationId);

        // Démarrer l'animation de chargement
        this.startLoadingAnimation();

        try {
            // Collecter les données du formulaire
            const formData = this.collectFormData();

            console.log('📤 [DEVIS-CLEAN] Envoi des données:', formData);

            // Envoyer les données
            const response = await fetch('ajax/creer_devis_clean.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                console.log('✅ [DEVIS-CLEAN] Devis sauvegardé avec succès');
                console.log('📱 [DEVIS-CLEAN] SMS:', result.sms_message);

                // Animation de succès
                this.showSuccessAnimation();

                // Message de succès avec numéro de devis et statut SMS
                let message = `Devis créé avec succès !\nNuméro: ${result.numero_devis}\nTotal HT: ${result.data.total_ht}€\nTotal TTC: ${result.data.total_ttc}€`;

                if (result.sms_sent) {
                    message += `\n✅ SMS envoyé au client`;
                } else {
                    message += `\n❌ SMS non envoyé: ${result.sms_message}`;
                }

                // Attendre un peu avant d'afficher le message
                setTimeout(() => {
                    alert(message);

                    // Animation de fermeture
                    this.closeWithAnimation();

                    // Recharger la page après l'animation
                    setTimeout(() => {
                        location.reload();
                    }, 600);
                }, 1000);

            } else {
                this.stopLoadingAnimation();
                throw new Error(result.message || 'Erreur lors de la sauvegarde');
            }

        } catch (error) {
            this.stopLoadingAnimation();
            console.error('❌ [DEVIS-CLEAN] Erreur:', error);
            alert('Erreur lors de la sauvegarde du devis: ' + error.message);
        }
    }

    startLoadingAnimation() {
        const btn = document.getElementById('sauvegarderBtn');
        const modal = document.getElementById('devisModalClean');

        if (btn) {
            btn.classList.add('loading');
            btn.querySelector('.btn-loading').style.display = 'inline-block';
            btn.querySelector('.btn-text').style.display = 'none';
        }

        if (modal) {
            modal.classList.add('sending');
        }

        console.log('🔄 [DEVIS-CLEAN] Animation de chargement démarrée');
    }

    stopLoadingAnimation() {
        const btn = document.getElementById('sauvegarderBtn');
        const modal = document.getElementById('devisModalClean');

        if (btn) {
            btn.classList.remove('loading');
            btn.querySelector('.btn-loading').style.display = 'none';
            btn.querySelector('.btn-text').style.display = 'inline-block';
        }

        if (modal) {
            modal.classList.remove('sending');
        }

        console.log('⏸️ [DEVIS-CLEAN] Animation de chargement arrêtée');
    }

    showSuccessAnimation() {
        const btn = document.getElementById('sauvegarderBtn');

        if (btn) {
            btn.classList.remove('loading');
            btn.classList.add('success');
            btn.querySelector('.btn-loading').style.display = 'none';
            btn.querySelector('.btn-text').innerHTML = '<i class="fas fa-check me-1"></i>Envoyé !';
            btn.querySelector('.btn-text').style.display = 'inline-block';
        }

        console.log('✨ [DEVIS-CLEAN] Animation de succès affichée');
    }

    closeWithAnimation() {
        const modal = document.getElementById('devisModalClean');

        if (modal) {
            modal.classList.add('success-exit');

            setTimeout(() => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }, 500);
        }

        console.log('🚪 [DEVIS-CLEAN] Fermeture avec animation');
    }

    collectFormData() {
        const data = {
            reparation_id: this.reparationId,
            titre: document.getElementById('devis_titre').value.trim(),
            description: document.getElementById('devis_description').value.trim(),
            garantie: document.getElementById('devis_garantie').value.trim(),
            pannes: [],
            solutions: []
        };

        // Collecter les pannes
        document.querySelectorAll('.panne-item').forEach(panne => {
            const panneData = {
                nom: panne.querySelector('.panne-nom').value.trim(),
                description: panne.querySelector('.panne-description').value.trim(),
                gravite: panne.querySelector('.panne-gravite').value
            };
            if (panneData.nom) {
                data.pannes.push(panneData);
            }
        });

        // Collecter les solutions
        document.querySelectorAll('.solution-item').forEach(solution => {
            const solutionData = {
                nom: solution.querySelector('.solution-nom').value.trim(),
                description: solution.querySelector('.solution-description').value.trim(),
                garantie: solution.querySelector('.solution-garantie').value.trim(),
                prix: parseFloat(solution.querySelector('.solution-prix').value) || 0
            };
            if (solutionData.nom && solutionData.prix > 0) {
                data.solutions.push(solutionData);
            }
        });

        return data;
    }
}

// Fonction globale pour ouvrir le modal
window.ouvrirDevisClean = function (reparationId) {
    console.log('🎯 [DEVIS-CLEAN] Ouverture du modal pour la réparation', reparationId);

    const modal = document.getElementById('devisModalClean');
    if (!modal) {
        console.error('❌ [DEVIS-CLEAN] Modal non trouvé');
        return;
    }

    // Créer un bouton temporaire avec l'ID de réparation
    const tempButton = document.createElement('button');
    tempButton.dataset.reparationId = reparationId;

    // Ouvrir le modal
    const modalInstance = new bootstrap.Modal(modal);

    // Déclencher l'événement avec le bouton temporaire
    const event = new Event('show.bs.modal');
    event.relatedTarget = tempButton;
    modal.dispatchEvent(event);

    modalInstance.show();
};

// Alias pour compatibilité
window.ouvrirModalDevis = window.ouvrirDevisClean;
window.ouvrirNouveauModalDevis = window.ouvrirDevisClean;

// Initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.devisCleanManager = new DevisCleanManager();
    console.log('✅ [DEVIS-CLEAN] Gestionnaire prêt');
});
