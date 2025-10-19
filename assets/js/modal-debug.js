/**
 * ==================== MODAL DEBUG TRACKER ====================
 * Script pour tracker et débugger tous les modals de la page d'accueil
 * Affiche des informations détaillées dans la console
 */

(function() {
    'use strict';
    
    console.log('🔍 MODAL DEBUG TRACKER - Initialisation...');
    
    // Configuration du debug
    const DEBUG_CONFIG = {
        logLevel: 'verbose', // 'minimal', 'normal', 'verbose'
        showInConsole: true,
        showInAlert: false,
        trackClicks: true,
        trackEvents: true
    };
    
    // Liste des modals détectés
    let detectedModals = [];
    
    // Fonction pour logger avec style
    function debugLog(message, type = 'info', data = null) {
        if (!DEBUG_CONFIG.showInConsole) return;
        
        const styles = {
            info: 'color: #3b82f6; font-weight: bold;',
            success: 'color: #059669; font-weight: bold;',
            warning: 'color: #ea580c; font-weight: bold;',
            error: 'color: #dc2626; font-weight: bold;',
            modal: 'color: #8b5cf6; font-weight: bold; font-size: 14px;'
        };
        
        console.log(`%c[MODAL DEBUG] ${message}`, styles[type] || styles.info);
        
        if (data && DEBUG_CONFIG.logLevel === 'verbose') {
            console.log('📊 Données associées:', data);
        }
    }
    
    // Fonction pour détecter tous les modals
    function detectAllModals() {
        debugLog('🔍 Détection des modals...', 'info');
        
        // Sélecteurs pour différents types de modals
        const modalSelectors = [
            '.modal',
            '[id*="modal" i]',
            '[id*="Modal" i]',
            '[class*="modal" i]',
            '[data-bs-toggle="modal"]'
        ];
        
        const foundModals = new Set();
        
        modalSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                if (element.id || element.className) {
                    foundModals.add(element);
                }
            });
        });
        
        detectedModals = Array.from(foundModals);
        
        debugLog(`✅ ${detectedModals.length} modals détectés`, 'success');
        
        detectedModals.forEach((modal, index) => {
            const modalInfo = {
                index: index + 1,
                id: modal.id || 'Pas d\'ID',
                classes: modal.className || 'Pas de classes',
                tagName: modal.tagName,
                isVisible: modal.style.display !== 'none' && !modal.hidden,
                hasBootstrap: modal.classList.contains('modal'),
                element: modal
            };
            
            debugLog(`📋 Modal ${index + 1}: ${modalInfo.id}`, 'modal', modalInfo);
        });
        
        return detectedModals;
    }
    
    // Fonction pour tracker les déclencheurs de modals
    function trackModalTriggers() {
        debugLog('🎯 Tracking des déclencheurs de modals...', 'info');
        
        // Boutons avec data-bs-target
        const triggers = document.querySelectorAll('[data-bs-target*="modal" i], [data-bs-target*="Modal" i]');
        
        triggers.forEach((trigger, index) => {
            const targetModal = trigger.getAttribute('data-bs-target');
            const triggerInfo = {
                element: trigger,
                target: targetModal,
                text: trigger.textContent?.trim() || 'Pas de texte',
                classes: trigger.className,
                tagName: trigger.tagName
            };
            
            debugLog(`🎯 Déclencheur ${index + 1}: "${triggerInfo.text}" → ${targetModal}`, 'modal', triggerInfo);
            
            if (DEBUG_CONFIG.trackClicks) {
                trigger.addEventListener('click', function(e) {
                    debugLog(`🖱️ CLIC sur déclencheur: "${triggerInfo.text}" → ${targetModal}`, 'warning');
                    debugLog(`📍 Élément cliqué:`, 'info', {
                        element: this,
                        event: e,
                        timestamp: new Date().toISOString()
                    });
                });
            }
        });
        
        // Éléments avec onclick contenant "modal"
        const onclickElements = document.querySelectorAll('[onclick*="modal" i], [onclick*="Modal" i]');
        
        onclickElements.forEach((element, index) => {
            const onclick = element.getAttribute('onclick');
            const elementInfo = {
                element: element,
                onclick: onclick,
                text: element.textContent?.trim() || 'Pas de texte',
                classes: element.className,
                tagName: element.tagName
            };
            
            debugLog(`⚡ Onclick Modal ${index + 1}: "${elementInfo.text}"`, 'modal', elementInfo);
            
            if (DEBUG_CONFIG.trackClicks) {
                element.addEventListener('click', function(e) {
                    debugLog(`🖱️ CLIC onclick modal: "${elementInfo.text}"`, 'warning');
                    debugLog(`📍 Fonction onclick: ${onclick}`, 'info');
                });
            }
        });
    }
    
    // Fonction pour tracker les événements Bootstrap Modal
    function trackBootstrapModalEvents() {
        if (!DEBUG_CONFIG.trackEvents) return;
        
        debugLog('📡 Tracking des événements Bootstrap Modal...', 'info');
        
        const modalEvents = [
            'show.bs.modal',
            'shown.bs.modal',
            'hide.bs.modal',
            'hidden.bs.modal',
            'hidePrevented.bs.modal'
        ];
        
        detectedModals.forEach(modal => {
            if (!modal.classList.contains('modal')) return;
            
            modalEvents.forEach(eventName => {
                modal.addEventListener(eventName, function(e) {
                    const eventInfo = {
                        modalId: this.id || 'Pas d\'ID',
                        eventType: eventName,
                        timestamp: new Date().toISOString(),
                        target: e.target,
                        relatedTarget: e.relatedTarget
                    };
                    
                    debugLog(`🎭 ÉVÉNEMENT MODAL: ${eventName} sur "${eventInfo.modalId}"`, 'success', eventInfo);
                    
                    // Affichage spécial pour l'ouverture
                    if (eventName === 'shown.bs.modal') {
                        debugLog(`🎉 MODAL OUVERT: "${eventInfo.modalId}"`, 'success');
                        
                        // Informations détaillées du modal ouvert
                        const modalDetails = {
                            id: this.id,
                            title: this.querySelector('.modal-title')?.textContent || 'Pas de titre',
                            size: this.querySelector('.modal-dialog')?.className || 'Taille standard',
                            hasIframe: !!this.querySelector('iframe'),
                            iframeSrc: this.querySelector('iframe')?.src || 'Pas d\'iframe',
                            bodyContent: this.querySelector('.modal-body')?.innerHTML?.substring(0, 200) + '...' || 'Pas de contenu'
                        };
                        
                        console.group(`📋 DÉTAILS DU MODAL OUVERT: ${modalDetails.id}`);
                        console.log('🏷️ Titre:', modalDetails.title);
                        console.log('📏 Taille:', modalDetails.size);
                        console.log('🖼️ Contient iframe:', modalDetails.hasIframe);
                        if (modalDetails.hasIframe) {
                            console.log('🔗 Source iframe:', modalDetails.iframeSrc);
                        }
                        console.log('📄 Contenu (aperçu):', modalDetails.bodyContent);
                        console.groupEnd();
                        
                        if (DEBUG_CONFIG.showInAlert) {
                            alert(`MODAL OUVERT: ${modalDetails.id}\nTitre: ${modalDetails.title}`);
                        }
                    }
                });
            });
        });
    }
    
    // Fonction pour tracker les fonctions JavaScript personnalisées
    function trackCustomModalFunctions() {
        debugLog('🔧 Tracking des fonctions modals personnalisées...', 'info');
        
        // Liste des fonctions modals connues
        const customModalFunctions = [
            'ouvrirRechercheModerne',
            'afficherDetailsTache',
            'ouvrirModalStatut',
            'openStatsModal',
            'afficherDetailsCommande'
        ];
        
        customModalFunctions.forEach(funcName => {
            if (typeof window[funcName] === 'function') {
                debugLog(`✅ Fonction trouvée: ${funcName}()`, 'success');
                
                // Wrapper pour tracker les appels
                const originalFunc = window[funcName];
                window[funcName] = function(...args) {
                    debugLog(`🚀 APPEL FONCTION: ${funcName}()`, 'warning', {
                        arguments: args,
                        timestamp: new Date().toISOString()
                    });
                    
                    return originalFunc.apply(this, args);
                };
            } else {
                debugLog(`❌ Fonction non trouvée: ${funcName}()`, 'error');
            }
        });
    }
    
    // Fonction pour générer un rapport complet
    function generateModalReport() {
        console.group('📊 RAPPORT COMPLET DES MODALS');
        
        console.log(`🔢 Nombre total de modals: ${detectedModals.length}`);
        
        const modalsByType = {
            bootstrap: detectedModals.filter(m => m.classList.contains('modal')).length,
            custom: detectedModals.filter(m => !m.classList.contains('modal')).length,
            withId: detectedModals.filter(m => m.id).length,
            withoutId: detectedModals.filter(m => !m.id).length
        };
        
        console.log('📈 Répartition par type:', modalsByType);
        
        const triggers = document.querySelectorAll('[data-bs-target*="modal" i], [onclick*="modal" i]');
        console.log(`🎯 Nombre de déclencheurs: ${triggers.length}`);
        
        console.groupEnd();
    }
    
    // Fonction d'initialisation principale
    function initModalDebug() {
        debugLog('🚀 Initialisation du Modal Debug Tracker', 'success');
        
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(startTracking, 100);
            });
        } else {
            setTimeout(startTracking, 100);
        }
    }
    
    function startTracking() {
        debugLog('▶️ Démarrage du tracking...', 'info');
        
        try {
            detectAllModals();
            trackModalTriggers();
            trackBootstrapModalEvents();
            trackCustomModalFunctions();
            generateModalReport();
            
            debugLog('✅ Modal Debug Tracker initialisé avec succès!', 'success');
            
            // Ajouter des commandes globales pour le debug
            window.modalDebug = {
                getDetectedModals: () => detectedModals,
                refreshDetection: () => {
                    detectedModals = [];
                    detectAllModals();
                    return detectedModals;
                },
                generateReport: generateModalReport,
                config: DEBUG_CONFIG
            };
            
            debugLog('🛠️ Commandes debug disponibles: window.modalDebug', 'info');
            
        } catch (error) {
            debugLog('❌ Erreur lors de l\'initialisation:', 'error', error);
        }
    }
    
    // Démarrer le debug
    initModalDebug();
    
})();
