// Fix temporaire pour l'onglet garantie
// Créer les fonctions warranty immédiatement si elles n'existent pas
if (typeof window.saveWarrantySettings === 'undefined') {
    console.log('🔧 Pré-création des fonctions warranty...');
    createWarrantyFunctionsEarly();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Fix warranty tab loaded');
    
    // Vérifier si l'onglet warranty existe
    const warrantyTab = document.querySelector('[data-tab="warranty"]');
    const warrantySection = document.getElementById('warranty');
    
    console.log('Warranty tab found:', warrantyTab);
    console.log('Warranty section found:', warrantySection);
    
    if (warrantyTab && warrantySection) {
        warrantyTab.addEventListener('click', function() {
            console.log('Warranty tab clicked');
            
            // Masquer toutes les sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            
            // Désactiver tous les onglets
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            
            // Activer l'onglet warranty
            warrantyTab.classList.add('active');
            
            // Afficher la section warranty
            warrantySection.classList.add('active');
            
            console.log('Warranty section activated');
    } else {
        console.error('Warranty tab or section not found');
        
        // Si l'onglet existe mais pas la section, essayons de la créer dynamiquement
        if (warrantyTab && !warrantySection) {
            console.error('🚨 PROBLÈME: Onglet warranty visible mais section introuvable!');
            console.error('🔧 TENTATIVE: Création dynamique de la section warranty...');
            
            // Créer la section warranty dynamiquement
            const warrantyHTML = `
                <section class="content-section" id="warranty">
                    <div class="section-header">
                        <h3><i class="fas fa-shield-alt"></i>Paramètres de garantie</h3>
                    </div>
                    <div class="section-body">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Système de garantie automatique</strong><br>
                            Les garanties sont automatiquement créées lorsqu'une réparation passe au statut "Réparation Effectuée".
                        </div>
                        
                        <form id="warranty-settings-form" class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" id="garantie_active" class="form-checkbox me-2">
                                    Activer le système de garantie
                                </label>
                                <small class="form-help">Permet l'activation/désactivation complète du système de garantie</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="garantie_duree_defaut" class="form-label">Durée par défaut (en jours)</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-calendar-alt input-icon"></i>
                                    <input type="number" class="form-input" id="garantie_duree_defaut" min="1" max="3650" value="90">
                                </div>
                                <small class="form-help">Durée de garantie appliquée automatiquement (1 à 3650 jours)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="garantie_description_defaut" class="form-label">Description par défaut</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-file-alt input-icon"></i>
                                    <textarea class="form-input" id="garantie_description_defaut" rows="3" placeholder="Garantie pièces et main d'œuvre">Garantie pièces et main d'œuvre</textarea>
                                </div>
                                <small class="form-help">Description qui apparaîtra sur les garanties créées automatiquement</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" id="garantie_auto_creation" class="form-checkbox me-2" checked>
                                    Création automatique des garanties
                                </label>
                                <small class="form-help">Créer automatiquement une garantie quand une réparation est marquée "Effectuée"</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="garantie_notification_expiration" class="form-label">Notification d'expiration</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-bell input-icon"></i>
                                    <input type="number" class="form-input" id="garantie_notification_expiration" min="0" max="365" value="7">
                                </div>
                                <small class="form-help">Nombre de jours avant expiration pour notifier (0 = pas de notification)</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" onclick="saveWarrantySettings()">
                                    <i class="fas fa-save"></i>
                                    Enregistrer les paramètres
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="loadWarrantySettings()">
                                    <i class="fas fa-undo"></i>
                                    Annuler
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 p-3 bg-light rounded">
                            <h5><i class="fas fa-chart-line me-2"></i>Statistiques des garanties</h5>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <div class="stat-number text-success" id="warranties-active">-</div>
                                        <div class="stat-label">Garanties actives</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <div class="stat-number text-warning" id="warranties-expiring">-</div>
                                        <div class="stat-label">Expirent bientôt</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <div class="stat-number text-danger" id="warranties-expired">-</div>
                                        <div class="stat-label">Expirées</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <div class="stat-number text-info" id="warranty-claims">-</div>
                                        <div class="stat-label">Réclamations</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php?page=garanties" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-list"></i>
                                    Voir toutes les garanties
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            `;
            
            // Insérer la section avant la fermeture de main
            const mainElement = document.querySelector('main');
            if (mainElement) {
                mainElement.insertAdjacentHTML('beforeend', warrantyHTML);
                console.log('✅ Section warranty créée dynamiquement');
                
                // Réessayer de trouver la section
                const newWarrantySection = document.getElementById('warranty');
                if (newWarrantySection) {
                    console.log('✅ Section warranty trouvée après création dynamique');
                    
                    // Attacher l'événement click
                    warrantyTab.addEventListener('click', function() {
                        console.log('Warranty tab clicked (dynamic)');
                        
                        // Masquer toutes les sections
                        document.querySelectorAll('.content-section').forEach(section => {
                            section.classList.remove('active');
                        
                        // Désactiver tous les onglets
                        document.querySelectorAll('.nav-item').forEach(item => {
                            item.classList.remove('active');
                        
                        // Activer l'onglet warranty
                        warrantyTab.classList.add('active');
                        
                        // Afficher la section warranty
                        newWarrantySection.classList.add('active');
                        
                        // Charger les paramètres et stats
                        if (typeof loadWarrantySettings === 'function') {
                            loadWarrantySettings();
                        } else {
                            // Créer les fonctions warranty si elles n'existent pas
                            createWarrantyFunctions();
                            loadWarrantySettings();
                        }
                        if (typeof loadWarrantyStats === 'function') {
                            loadWarrantyStats();
                        }
                        
                        console.log('Warranty section activated (dynamic)');
                } else {
                    console.error('❌ Impossible de créer la section warranty dynamiquement');
                }
            } else {
                console.error('❌ Element main non trouvé pour insertion');
            }
        }
    }
    
    // Debug: lister toutes les sections et onglets
    console.log('All tabs:', document.querySelectorAll('.nav-item'));
    console.log('All sections:', document.querySelectorAll('.content-section'));
    
    // Si la section warranty existe (créée dynamiquement), créer les fonctions immédiatement
    setTimeout(() => {
        const warrantySection = document.getElementById('warranty');
        if (warrantySection && typeof saveWarrantySettings === 'undefined') {
            console.log('🔧 Section warranty détectée, création des fonctions...');
            createWarrantyFunctions();
            // Charger les paramètres et stats immédiatement
            loadWarrantySettings();
            loadWarrantyStats();
        }
    }, 500);

// Fonction pour créer les fonctions warranty très tôt (avant DOM ready)
function createWarrantyFunctionsEarly() {
    console.log('🔧 Création précoce des fonctions warranty...');
    
    // Fonction pour sauvegarder les paramètres de garantie
    window.saveWarrantySettings = function() {
        console.log('💾 Sauvegarde des paramètres warranty (early)...');
        
        // Attendre que le DOM soit prêt si nécessaire
        if (!document.getElementById('garantie_active')) {
            console.log('⏳ DOM pas prêt, attente...');
            setTimeout(() => saveWarrantySettings(), 500);
            return;
        }
        
        const formData = {
            garantie_active: document.getElementById('garantie_active').checked
            garantie_duree_defaut: parseInt(document.getElementById('garantie_duree_defaut').value)
            garantie_description_defaut: document.getElementById('garantie_description_defaut').value
            garantie_auto_creation: document.getElementById('garantie_auto_creation').checked
            garantie_notification_expiration: parseInt(document.getElementById('garantie_notification_expiration').value)
        };
        
        const submitBtn = document.querySelector('#warranty-settings-form .btn-primary');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            submitBtn.disabled = true;
        }
        
        fetch('../ajax/update_warranty_settings.php', {
            method: 'POST'
            headers: {
                'Content-Type': 'application/json'
            }
            credentials: 'same-origin'
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Paramètres warranty sauvegardés (early)');
                showWarrantyNotificationEarly('Paramètres de garantie sauvegardés avec succès', 'success');
                if (typeof loadWarrantyStats === 'function') {
                    loadWarrantyStats();
                }
            } else {
                console.error('❌ Erreur lors de la sauvegarde:', data.message);
                showWarrantyNotificationEarly('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ Erreur réseau:', error);
            showWarrantyNotificationEarly('Erreur de connexion', 'error');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || '<i class="fas fa-save"></i> Enregistrer les paramètres';
                submitBtn.disabled = false;
            }
    };
    
    // Fonction pour afficher les notifications (version simplifiée)
    window.showWarrantyNotificationEarly = function(message, type) {
        console.log('🔔 Notification warranty (early):', message, type);
        
        // Créer une notification simple
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <strong>${type === 'success' ? '✅' : '❌'}</strong> ${message}
            <button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    };
    
    console.log('✅ Fonctions warranty créées précocement');
}

// Fonction pour créer les fonctions warranty si elles n'existent pas
function createWarrantyFunctions() {
    console.log('🔧 Création des fonctions warranty dynamiques...');
    
    // Fonction pour charger les paramètres de garantie
    window.loadWarrantySettings = function() {
        console.log('📥 Chargement des paramètres warranty...');
        fetch('../ajax/update_warranty_settings.php', {
            method: 'GET'
            headers: {
                'Content-Type': 'application/json'
            }
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Remplir les champs du formulaire
                document.getElementById('garantie_active').checked = data.data.garantie_active === '1';
                document.getElementById('garantie_duree_defaut').value = data.data.garantie_duree_defaut || 90;
                document.getElementById('garantie_description_defaut').value = data.data.garantie_description_defaut || 'Garantie pièces et main d\'œuvre';
                document.getElementById('garantie_auto_creation').checked = data.data.garantie_auto_creation === '1';
                document.getElementById('garantie_notification_expiration').value = data.data.garantie_notification_expiration || 7;
                
                console.log('✅ Paramètres warranty chargés');
            } else {
                console.error('❌ Erreur lors du chargement des paramètres:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur réseau:', error);
    };
    
    // Fonction pour sauvegarder les paramètres de garantie
    window.saveWarrantySettings = function() {
        console.log('💾 Sauvegarde des paramètres warranty...');
        
        const formData = {
            garantie_active: document.getElementById('garantie_active').checked
            garantie_duree_defaut: parseInt(document.getElementById('garantie_duree_defaut').value)
            garantie_description_defaut: document.getElementById('garantie_description_defaut').value
            garantie_auto_creation: document.getElementById('garantie_auto_creation').checked
            garantie_notification_expiration: parseInt(document.getElementById('garantie_notification_expiration').value)
        };
        
        const submitBtn = document.querySelector('#warranty-settings-form .btn-primary');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        submitBtn.disabled = true;
        
        fetch('../ajax/update_warranty_settings.php', {
            method: 'POST'
            headers: {
                'Content-Type': 'application/json'
            }
            credentials: 'same-origin'
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Paramètres warranty sauvegardés');
                showWarrantyNotification('Paramètres de garantie sauvegardés avec succès', 'success');
                loadWarrantyStats(); // Recharger les statistiques
            } else {
                console.error('❌ Erreur lors de la sauvegarde:', data.message);
                showWarrantyNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ Erreur réseau:', error);
            showWarrantyNotification('Erreur de connexion', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
    };
    
    // Fonction pour charger les statistiques
    window.loadWarrantyStats = function() {
        console.log('📊 Chargement des statistiques warranty...');
        fetch('../ajax/warranty_stats.php', {
            method: 'GET'
            headers: {
                'Content-Type': 'application/json'
            }
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                document.getElementById('warranties-active').textContent = data.data.warranties_active || 0;
                document.getElementById('warranties-expiring').textContent = data.data.warranties_expiring || 0;
                document.getElementById('warranties-expired').textContent = data.data.warranties_expired || 0;
                document.getElementById('warranty-claims').textContent = data.data.warranty_claims || 0;
                
                console.log('✅ Statistiques warranty chargées');
            } else {
                console.error('❌ Erreur lors du chargement des statistiques:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur réseau stats:', error);
    };
    
    // Fonction pour afficher les notifications
    window.showWarrantyNotification = function(message, type) {
        console.log('🔔 Notification warranty:', message, type);
        
        // Créer une notification simple
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <strong>${type === 'success' ? '✅' : '❌'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    };
    
    console.log('✅ Fonctions warranty créées dynamiquement');
}
