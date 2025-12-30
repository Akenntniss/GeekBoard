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
