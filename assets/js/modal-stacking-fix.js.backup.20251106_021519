/**
 * Solution pour gérer l'ouverture propre des modals
 * Ferme automatiquement le RepairModal quand le modal Nouvelle Commande s'ouvre
 */

console.log('🔄 [MODAL-STACKING] Script de gestion des modals chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 [MODAL-STACKING] DOM chargé, initialisation...');
    
    // Attendre que Bootstrap soit initialisé
    setTimeout(() => {
        initModalStacking();
    }, 1000);
});

function initModalStacking() {
    const repairModal = document.getElementById('repairDetailsModal');
    const commandeModal = document.getElementById('ajouterCommandeModal');
    
    if (!repairModal) {
        console.log('🔄 [MODAL-STACKING] ⚠️ RepairModal non trouvé');
        return;
    }
    
    console.log('🔄 [MODAL-STACKING] ✅ RepairModal trouvé, installation des correctifs...');
    console.log('🔄 [MODAL-STACKING] 📋 Modals gérés: ajouterCommandeModal, photoModal, notesModal, priceModal, chooseStatusModal, smsModal');
    
    // Variables pour tracking
    let repairModalOpen = false;
    let commandeModalOpen = false;
    let photoModalOpen = false;
    let notesModalOpen = false;
    
    // Écouter l'ouverture du modal de réparation
    repairModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📋 RepairModal ouvert');
        repairModalOpen = true;
    });
    
    // Écouter la fermeture du modal de réparation
    repairModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📋 RepairModal fermé');
        repairModalOpen = false;
    });
    
    // Écouter l'ouverture du modal de commande
    if (commandeModal) {
        commandeModal.addEventListener('show.bs.modal', function() {
            console.log('🔄 [MODAL-STACKING] 🛒 Modal commande en cours d\'ouverture...');
            
            // Si le modal de réparation est ouvert, le fermer pour plus d'esthétique
            if (repairModalOpen) {
                console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal pour le modal commande...');
                closeRepairModalGracefully(repairModal);
            }
        });
    }
    
    // Écouter l'ouverture complète du modal de commande
    if (commandeModal) {
        commandeModal.addEventListener('shown.bs.modal', function() {
            console.log('🔄 [MODAL-STACKING] 🛒 Modal commande ouvert');
            commandeModalOpen = true;
            
            // S'assurer que le RepairModal est bien fermé
            if (repairModalOpen) {
                console.log('🔄 [MODAL-STACKING] ⚠️ RepairModal encore ouvert, fermeture forcée...');
                closeRepairModalGracefully(repairModal);
            }
        });
    }
    
    // Écouter la fermeture du modal de commande
    if (commandeModal) {
        commandeModal.addEventListener('hidden.bs.modal', function() {
            console.log('🔄 [MODAL-STACKING] 🛒 Modal commande fermé');
            commandeModalOpen = false;
        });
    }
    
    // ==========================================
    // GESTION DU MODAL PHOTO
    // ==========================================
    
    // Observer pour détecter la création dynamique du modal photo
    const photoModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'photoModal') {
                    console.log('🔄 [MODAL-STACKING] 📸 Modal photo détecté (création dynamique)');
                    setupPhotoModalListeners(node);
                }
            });
        });
    });
    
    // Démarrer l'observation
    photoModalObserver.observe(document.body, { childList: true, subtree: true });
    
    // Vérifier si le modal photo existe déjà
    const existingPhotoModal = document.getElementById('photoModal');
    if (existingPhotoModal) {
        console.log('🔄 [MODAL-STACKING] 📸 Modal photo existant détecté');
        setupPhotoModalListeners(existingPhotoModal);
    }
    
    // ==========================================
    // GESTION DU MODAL NOTES
    // ==========================================
    
    // Observer pour détecter la création dynamique du modal notes
    const notesModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'notesModal') {
                    console.log('🔄 [MODAL-STACKING] 📝 Modal notes détecté (création dynamique)');
                    setupNotesModalListeners(node);
                }
            });
        });
    });
    
    // Démarrer l'observation
    notesModalObserver.observe(document.body, { childList: true, subtree: true });
    
    // Vérifier si le modal notes existe déjà
    const existingNotesModal = document.getElementById('notesModal');
    if (existingNotesModal) {
        console.log('🔄 [MODAL-STACKING] 📝 Modal notes existant détecté');
        setupNotesModalListeners(existingNotesModal);
    }
    
    // ==========================================
    // GESTION DU MODAL PRIX
    // ==========================================
    
    // Observer pour détecter la création dynamique du modal prix
    const priceModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'priceModal') {
                    console.log('🔄 [MODAL-STACKING] 💰 Modal prix détecté (création dynamique)');
                    setupPriceModalListeners(node);
                }
            });
        });
    });
    
    // Démarrer l'observation
    priceModalObserver.observe(document.body, { childList: true, subtree: true });
    
    // Vérifier si le modal prix existe déjà
    const existingPriceModal = document.getElementById('priceModal');
    if (existingPriceModal) {
        console.log('🔄 [MODAL-STACKING] 💰 Modal prix existant détecté');
        setupPriceModalListeners(existingPriceModal);
    }
    
    // ==========================================
    // GESTION DU MODAL STATUT
    // ==========================================
    
    // Observer pour détecter la création dynamique du modal statut
    const statusModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'chooseStatusModal') {
                    console.log('🔄 [MODAL-STACKING] 📊 Modal statut détecté (création dynamique)');
                    setupStatusModalListeners(node);
                }
            });
        });
    });
    
    // Démarrer l'observation
    statusModalObserver.observe(document.body, { childList: true, subtree: true });
    
    // Vérifier si le modal statut existe déjà
    const existingStatusModal = document.getElementById('chooseStatusModal');
    if (existingStatusModal) {
        console.log('🔄 [MODAL-STACKING] 📊 Modal statut existant détecté');
        setupStatusModalListeners(existingStatusModal);
    }
    
    // ==========================================
    // GESTION DU MODAL SMS
    // ==========================================
    
    // Observer pour détecter la création dynamique du modal SMS
    const smsModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'smsModal') {
                    console.log('🔄 [MODAL-STACKING] 📱 Modal SMS détecté (création dynamique)');
                    setupSmsModalListeners(node);
                }
            });
        });
    });
    
    // Démarrer l'observation
    smsModalObserver.observe(document.body, { childList: true, subtree: true });
    
    // Vérifier si le modal SMS existe déjà
    const existingSmsModal = document.getElementById('smsModal');
    if (existingSmsModal) {
        console.log('🔄 [MODAL-STACKING] 📱 Modal SMS existant détecté');
        setupSmsModalListeners(existingSmsModal);
    }
    
    console.log('🔄 [MODAL-STACKING] ✅ Système de gestion des modals installé');
}

function closeRepairModalGracefully(repairModal) {
    console.log('🔄 [MODAL-STACKING] 🎭 Fermeture gracieuse du RepairModal...');
    
    try {
        // Utiliser l'instance Bootstrap existante
        const repairModalInstance = bootstrap.Modal.getInstance(repairModal);
        
        if (repairModalInstance) {
            console.log('🔄 [MODAL-STACKING] ✅ Instance Bootstrap trouvée, fermeture...');
            repairModalInstance.hide();
        } else {
            console.log('🔄 [MODAL-STACKING] ⚠️ Pas d\'instance Bootstrap, fermeture manuelle...');
            // Fermeture manuelle si pas d'instance
            repairModal.classList.remove('show');
            repairModal.style.display = 'none';
            repairModal.setAttribute('aria-hidden', 'true');
            
            // Supprimer les backdrops du repair modal
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach((backdrop, index) => {
                // Ne supprimer que les anciens backdrops, pas celui du nouveau modal
                if (index < backdrops.length - 1) {
                    backdrop.remove();
                }
            });
            
            // Mettre à jour le state
            repairModalOpen = false;
        }
        
        console.log('🔄 [MODAL-STACKING] ✅ RepairModal fermé avec succès');
        
    } catch (error) {
        console.error('🔄 [MODAL-STACKING] ❌ Erreur lors de la fermeture:', error);
    }
}

// ==========================================
// FONCTIONS DE CONFIGURATION DES LISTENERS
// ==========================================

function setupPhotoModalListeners(photoModal) {
    console.log('🔄 [MODAL-STACKING] 📸 Configuration des listeners pour photoModal');
    
    // Écouter l'ouverture du modal photo
    photoModal.addEventListener('show.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📸 Modal photo en cours d\'ouverture...');
        
        const repairModal = document.getElementById('repairDetailsModal');
        const repairModalOpen = repairModal && repairModal.classList.contains('show');
        
        // Si le modal de réparation est ouvert, le fermer
        if (repairModalOpen) {
            console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal pour le modal photo...');
            closeRepairModalGracefully(repairModal);
        }
    });
    
    // Écouter l'ouverture complète du modal photo
    photoModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📸 Modal photo ouvert');
    });
    
    // Écouter la fermeture du modal photo
    photoModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📸 Modal photo fermé');
    });
}

function setupNotesModalListeners(notesModal) {
    console.log('🔄 [MODAL-STACKING] 📝 Configuration des listeners pour notesModal');
    
    // Écouter l'ouverture du modal notes
    notesModal.addEventListener('show.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📝 Modal notes en cours d\'ouverture...');
        
        const repairModal = document.getElementById('repairDetailsModal');
        const repairModalOpen = repairModal && repairModal.classList.contains('show');
        
        // Si le modal de réparation est ouvert, le fermer
        if (repairModalOpen) {
            console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal pour le modal notes...');
            closeRepairModalGracefully(repairModal);
        }
    });
    
    // Écouter l'ouverture complète du modal notes
    notesModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📝 Modal notes ouvert');
    });
    
    // Écouter la fermeture du modal notes
    notesModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📝 Modal notes fermé');
    });
}

function setupPriceModalListeners(priceModal) {
    console.log('🔄 [MODAL-STACKING] 💰 Configuration des listeners pour priceModal');
    
    // Écouter l'ouverture du modal prix
    priceModal.addEventListener('show.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 💰 Modal prix en cours d\'ouverture...');
        
        const repairModal = document.getElementById('repairDetailsModal');
        const repairModalOpen = repairModal && repairModal.classList.contains('show');
        
        // Si le modal de réparation est ouvert, le fermer
        if (repairModalOpen) {
            console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal pour le modal prix...');
            closeRepairModalGracefully(repairModal);
        }
    });
    
    // Écouter l'ouverture complète du modal prix
    priceModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 💰 Modal prix ouvert');
    });
    
    // Écouter la fermeture du modal prix
    priceModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 💰 Modal prix fermé');
    });
}

function setupStatusModalListeners(statusModal) {
    console.log('🔄 [MODAL-STACKING] 📊 Configuration des listeners pour chooseStatusModal');
    
    // Écouter l'ouverture du modal statut
    statusModal.addEventListener('show.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📊 Modal statut en cours d\'ouverture...');
        
        const repairModal = document.getElementById('repairDetailsModal');
        const repairModalOpen = repairModal && repairModal.classList.contains('show');
        
        // Si le modal de réparation est ouvert, le fermer
        if (repairModalOpen) {
            console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal pour le modal statut...');
            closeRepairModalGracefully(repairModal);
        }
    });
    
    // Écouter l'ouverture complète du modal statut
    statusModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📊 Modal statut ouvert');
    });
    
    // Écouter la fermeture du modal statut
    statusModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📊 Modal statut fermé');
    });
}

function setupSmsModalListeners(smsModal) {
    console.log('🔄 [MODAL-STACKING] 📱 Configuration des listeners pour smsModal');
    
    // Écouter l'ouverture du modal SMS
    smsModal.addEventListener('show.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📱 Modal SMS en cours d\'ouverture...');
        
        const repairModal = document.getElementById('repairDetailsModal');
        const repairModalOpen = repairModal && repairModal.classList.contains('show');
        
        // Si le modal de réparation est ouvert, le fermer
        if (repairModalOpen) {
            console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal pour le modal SMS...');
            closeRepairModalGracefully(repairModal);
        }
    });
    
    // Écouter l'ouverture complète du modal SMS
    smsModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📱 Modal SMS ouvert');
    });
    
    // Écouter la fermeture du modal SMS
    smsModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [MODAL-STACKING] 📱 Modal SMS fermé');
    });
}

// Fonction utilitaire pour debug
window.debugModalStacking = function() {
    const repairModal = document.getElementById('repairDetailsModal');
    const commandeModal = document.getElementById('ajouterCommandeModal');
    const photoModal = document.getElementById('photoModal');
    const notesModal = document.getElementById('notesModal');
    const priceModal = document.getElementById('priceModal');
    const statusModal = document.getElementById('chooseStatusModal');
    const smsModal = document.getElementById('smsModal');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    
    console.log('🔄 [DEBUG] État actuel des modals:', {
        repair: {
            element: !!repairModal,
            display: repairModal ? getComputedStyle(repairModal).display : 'N/A',
            isOpen: repairModal ? repairModal.classList.contains('show') : false
        },
        commande: {
            element: !!commandeModal,
            display: commandeModal ? getComputedStyle(commandeModal).display : 'N/A',
            isOpen: commandeModal ? commandeModal.classList.contains('show') : false
        },
        photo: {
            element: !!photoModal,
            display: photoModal ? getComputedStyle(photoModal).display : 'N/A',
            isOpen: photoModal ? photoModal.classList.contains('show') : false
        },
        notes: {
            element: !!notesModal,
            display: notesModal ? getComputedStyle(notesModal).display : 'N/A',
            isOpen: notesModal ? notesModal.classList.contains('show') : false
        },
        price: {
            element: !!priceModal,
            display: priceModal ? getComputedStyle(priceModal).display : 'N/A',
            isOpen: priceModal ? priceModal.classList.contains('show') : false
        },
        status: {
            element: !!statusModal,
            display: statusModal ? getComputedStyle(statusModal).display : 'N/A',
            isOpen: statusModal ? statusModal.classList.contains('show') : false
        },
        sms: {
            element: !!smsModal,
            display: smsModal ? getComputedStyle(smsModal).display : 'N/A',
            isOpen: smsModal ? smsModal.classList.contains('show') : false
        },
        backdrops: backdrops.length,
        totalModalsOpen: document.querySelectorAll('.modal.show').length
    });
};

console.log('🔄 [MODAL-STACKING] ✅ Script prêt');
console.log('🔄 [MODAL-STACKING] 💡 Utilisez window.debugModalStacking() pour diagnostiquer');

// Test immédiat des modals existants
setTimeout(function() {
    console.log('🔄 [MODAL-STACKING] 🔍 Test de détection des modals...');
    
    const modalsToCheck = [
        'repairDetailsModal',
        'ajouterCommandeModal', 
        'photoModal',
        'notesModal',
        'priceModal',
        'chooseStatusModal',
        'smsModal'
    ];
    
    modalsToCheck.forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        console.log('🔄 [MODAL-STACKING] Modal', modalId, ':', modal ? '✅ Trouvé' : '❌ Non trouvé');
    });
    
    // Intercepter les fonctions d'ouverture des modals
    interceptModalFunctions();
}, 2000);

// Fonction pour intercepter les fonctions d'ouverture des modals
function interceptModalFunctions() {
    console.log('🔄 [MODAL-STACKING] 🎯 Installation des intercepteurs de fonctions...');
    
    // Intercepter window.priceModal.show si elle existe
    if (window.priceModal && typeof window.priceModal.show === 'function') {
        const originalPriceShow = window.priceModal.show;
        window.priceModal.show = function() {
            console.log('🔄 [MODAL-STACKING] 💰 Interception priceModal.show()');
            
            const repairModal = document.getElementById('repairDetailsModal');
            if (repairModal && repairModal.classList.contains('show')) {
                console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal avant ouverture du modal prix...');
                closeRepairModalGracefully(repairModal);
            }
            
            return originalPriceShow.apply(this, arguments);
        };
        console.log('🔄 [MODAL-STACKING] ✅ Intercepteur priceModal.show installé');
    }
    
    // Intercepter les fonctions globales openNotesModal, openPriceModal, openPhotosModal, openSmsModal
    const functionsToIntercept = ['openNotesModal', 'openPriceModal', 'openPhotosModal', 'openSmsModal'];
    
    functionsToIntercept.forEach(function(funcName) {
        if (window[funcName] && typeof window[funcName] === 'function') {
            const originalFunc = window[funcName];
            window[funcName] = function() {
                console.log('🔄 [MODAL-STACKING] 🎯 Interception', funcName);
                
                const repairModal = document.getElementById('repairDetailsModal');
                if (repairModal && repairModal.classList.contains('show')) {
                    console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal avant', funcName);
                    closeRepairModalGracefully(repairModal);
                }
                
                return originalFunc.apply(this, arguments);
            };
            console.log('🔄 [MODAL-STACKING] ✅ Intercepteur', funcName, 'installé');
        } else {
            console.log('🔄 [MODAL-STACKING] ⚠️ Fonction', funcName, 'non trouvée');
        }
    });
    
    // Intercepter les méthodes de RepairModal si elles existent
    if (window.RepairModal && window.RepairModal.openNotesModal) {
        const originalOpenNotes = window.RepairModal.openNotesModal;
        window.RepairModal.openNotesModal = function() {
            console.log('🔄 [MODAL-STACKING] 📝 Interception RepairModal.openNotesModal');
            
            const repairModal = document.getElementById('repairDetailsModal');
            if (repairModal && repairModal.classList.contains('show')) {
                console.log('🔄 [MODAL-STACKING] 🎨 Fermeture du RepairModal avant notes...');
                closeRepairModalGracefully(repairModal);
            }
            
            return originalOpenNotes.apply(this, arguments);
        };
        console.log('🔄 [MODAL-STACKING] ✅ Intercepteur RepairModal.openNotesModal installé');
    }
    
    // Surveillance continue des modals qui s'ouvrent
    setInterval(function() {
        const repairModal = document.getElementById('repairDetailsModal');
        const isRepairModalOpen = repairModal && repairModal.classList.contains('show');
        
        if (isRepairModalOpen) {
            const conflictingModals = [
                'priceModal',
                'notesModal', 
                'photoModal',
                'chooseStatusModal',
                'smsModal'
            ];
            
            conflictingModals.forEach(function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal && modal.classList.contains('show')) {
                    console.log('🔄 [MODAL-STACKING] 🚨 Conflit détecté:', modalId, 'ouvert avec RepairModal');
                    console.log('🔄 [MODAL-STACKING] 🎨 Fermeture forcée du RepairModal...');
                    closeRepairModalGracefully(repairModal);
                }
            });
        }
    }, 500); // Vérification toutes les 500ms
}
