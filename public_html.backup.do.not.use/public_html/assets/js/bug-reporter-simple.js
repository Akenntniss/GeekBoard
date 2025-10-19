/**
 * Système simple de signalement de bugs
 * Utilise l'API existante add_bug_report.php
 */

// Variables globales
let bugModal = null;
let isBugSubmitting = false;

// Initialisation du système
document.addEventListener('DOMContentLoaded', function() {
    initBugReporter();
    console.log('✅ Système de signalement de bugs initialisé');
});

// Initialisation du système de signalement
function initBugReporter() {
    createBugReportButton();
    createBugModal();
    setupEventListeners();
}

// Créer le bouton flottant
function createBugReportButton() {
    const button = document.createElement('button');
    button.className = 'bug-report-btn';
    button.title = 'Signaler un problème';
    button.innerHTML = '<i class="fas fa-bug"></i>';
    
    document.body.appendChild(button);
    
    // Gérer le clic
    button.addEventListener('click', openBugModal);
}

// Créer le modal
function createBugModal() {
    const modalHTML = `
        <div class="bug-modal" id="bugModal">
            <div class="bug-modal-content">
                <div class="bug-modal-header">
                    <h4><i class="fas fa-bug"></i> Signaler un problème</h4>
                    <button class="bug-modal-close" onclick="closeBugModal()">×</button>
                </div>
                
                <form id="bugReportForm">
                    <div class="bug-form-group">
                        <label for="bugDescription">Description du problème *</label>
                        <textarea 
                            id="bugDescription" 
                            name="description" 
                            placeholder="Décrivez le problème que vous rencontrez en détail..."
                            required
                        ></textarea>
                    </div>
                    
                    <div class="bug-form-group">
                        <label for="bugPageUrl">Page concernée</label>
                        <input 
                            type="text" 
                            id="bugPageUrl" 
                            name="page_url" 
                            readonly
                        >
                    </div>
                    
                    <div class="bug-modal-footer">
                        <button type="button" class="bug-btn bug-btn-secondary" onclick="closeBugModal()">
                            Annuler
                        </button>
                        <button type="submit" class="bug-btn bug-btn-primary" id="submitBugBtn">
                            <i class="fas fa-paper-plane"></i>
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    bugModal = document.getElementById('bugModal');
}

// Configurer les écouteurs d'événements
function setupEventListeners() {
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bugModal && bugModal.classList.contains('show')) {
            closeBugModal();
        }
    });
    
    // Fermer en cliquant sur le fond
    document.addEventListener('click', function(e) {
        if (e.target === bugModal) {
            closeBugModal();
        }
    });
    
    // Gérer la soumission du formulaire
    const form = document.getElementById('bugReportForm');
    if (form) {
        form.addEventListener('submit', handleBugSubmit);
    }
}

// Ouvrir le modal
function openBugModal() {
    if (!bugModal) return;
    
    // Remplir l'URL actuelle
    const pageUrlInput = document.getElementById('bugPageUrl');
    if (pageUrlInput) {
        pageUrlInput.value = window.location.href;
    }
    
    // Réinitialiser le formulaire
    const form = document.getElementById('bugReportForm');
    if (form) {
        form.reset();
        // Remettre l'URL après le reset
        if (pageUrlInput) {
            pageUrlInput.value = window.location.href;
        }
    }
    
    // Afficher le modal
    bugModal.style.display = 'flex';
    setTimeout(() => {
        bugModal.classList.add('show');
        
        // Focus sur le textarea
        const textarea = document.getElementById('bugDescription');
        if (textarea) {
            textarea.focus();
        }
    }, 10);
    
    // Bloquer le scroll
    document.body.style.overflow = 'hidden';
    
    console.log('📝 Modal de signalement ouvert');
}

// Fermer le modal
function closeBugModal() {
    if (!bugModal) return;
    
    bugModal.classList.remove('show');
    
    setTimeout(() => {
        bugModal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
    
    console.log('🔄 Modal de signalement fermé');
}

// Gérer la soumission du formulaire
async function handleBugSubmit(e) {
    e.preventDefault();
    
    if (isBugSubmitting) return;
    
    const form = e.target;
    const formData = new FormData(form);
    const description = formData.get('description').trim();
    
    // Validation
    if (!description) {
        showToast('Veuillez décrire le problème', 'error');
        return;
    }
    
    if (description.length < 10) {
        showToast('La description doit contenir au moins 10 caractères', 'error');
        return;
    }
    
    // Désactiver le bouton de soumission
    isBugSubmitting = true;
    const submitBtn = document.getElementById('submitBugBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
    }
    
    try {
        console.log('📤 Envoi du rapport de bug...');
        
        // Envoi vers l'API existante
        const response = await fetch('/ajax/add_bug_report.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Rapport envoyé avec succès ! Merci pour votre contribution.', 'success');
            closeBugModal();
            console.log('✅ Rapport de bug envoyé avec succès');
        } else {
            console.error('❌ Erreur serveur:', result.debug || result.message);
            throw new Error(result.message || 'Erreur lors de l\'envoi');
        }
        
    } catch (error) {
        console.error('❌ Erreur lors de l\'envoi du rapport:', error);
        showToast('Erreur lors de l\'envoi du rapport. Veuillez réessayer.', 'error');
    } finally {
        // Réactiver le bouton
        isBugSubmitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
        }
    }
}

// Afficher un toast de notification
function showToast(message, type = 'success') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.bug-toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Créer le nouveau toast
    const toast = document.createElement('div');
    toast.className = `bug-toast ${type}`;
    
    const icon = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Animer l'apparition
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Fonctions globales pour compatibilité
window.openBugModal = openBugModal;
window.closeBugModal = closeBugModal;
