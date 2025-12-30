/**
 * Script de pointage simplifié - Vérification WiFi uniquement
 * Version respectueuse de la vie privée
 */

class SimpleWiFiTimeTracking {
    constructor() {
        this.isTracking = false;
        this.currentSSID = null;
        this.init();
    }

    async init() {
        console.log('📶 Initialisation du système de pointage WiFi...');
        
        // Détecter le WiFi si possible
        await this.detectWiFi();
        
        // Mettre à jour l'interface
        this.updateUI();
        
        // Vérifier le statut actuel
        this.checkCurrentStatus();
        
        console.log('✅ Système de pointage WiFi initialisé');
    }

    async detectWiFi() {
        try {
            // Tentative de détection du WiFi via les APIs disponibles
            if ('connection' in navigator) {
                const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                if (connection && connection.type === 'wifi') {
                    console.log('📶 Connexion WiFi détectée');
                }
            }

            // Note: L'API pour obtenir le SSID n'est pas disponible dans les navigateurs web
            // pour des raisons de sécurité. L'utilisateur devra saisir manuellement le SSID.
            
        } catch (error) {
            console.log('ℹ️ Détection automatique du WiFi non disponible');
        }
    }

    getWiFiSSID() {
        // Demander à l'utilisateur de saisir le SSID
        const ssid = document.getElementById('wifi-ssid-input')?.value;
        if (ssid) {
            return ssid.trim();
        }

        // Si pas de champ de saisie, demander via prompt
        const userSSID = prompt('Veuillez saisir le nom du réseau WiFi (SSID) auquel vous êtes connecté:');
        return userSSID ? userSSID.trim() : null;
    }

    async clockIn() {
        if (this.isTracking) {
            this.showMessage('⏱️ Pointage déjà en cours...', 'warning');
            return;
        }

        this.isTracking = true;
        this.showMessage('🔄 Vérification du WiFi...', 'info');

        try {
            // Obtenir le SSID
            const wifi_ssid = this.getWiFiSSID();
            
            if (!wifi_ssid) {
                this.showMessage('❌ SSID WiFi requis pour pointer', 'error');
                this.isTracking = false;
                return;
            }

            console.log('📶 SSID utilisé:', wifi_ssid);

            // Préparer les données simples
            const trackingData = {
                action: 'clock_in',
                wifi_ssid: wifi_ssid
            };

            console.log('📊 Données envoyées:', trackingData);

            // Envoyer à l'API
            const response = await fetch('time_tracking_api_wifi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(trackingData)
            });

            const result = await response.json();
            console.log('📨 Réponse API:', result);

            if (result.success) {
                this.showMessage(result.message, 'success');
                this.currentSSID = wifi_ssid;
                this.updateUI();
                
                // Sauvegarder le SSID pour la sortie
                localStorage.setItem('last_wifi_ssid', wifi_ssid);
            } else {
                this.showMessage(`❌ ${result.message}`, 'error');
            }

        } catch (error) {
            console.error('❌ Erreur lors du pointage:', error);
            this.showMessage(`❌ Erreur: ${error.message}`, 'error');
        } finally {
            this.isTracking = false;
        }
    }

    async clockOut() {
        if (this.isTracking) {
            this.showMessage('⏱️ Opération en cours...', 'warning');
            return;
        }

        this.isTracking = true;
        this.showMessage('🔄 Vérification du WiFi pour la sortie...', 'info');

        try {
            // Utiliser le même SSID que pour l'entrée ou demander à nouveau
            let wifi_ssid = localStorage.getItem('last_wifi_ssid');
            
            if (!wifi_ssid) {
                wifi_ssid = this.getWiFiSSID();
            }
            
            if (!wifi_ssid) {
                this.showMessage('❌ SSID WiFi requis pour pointer la sortie', 'error');
                this.isTracking = false;
                return;
            }

            console.log('📶 SSID pour sortie:', wifi_ssid);

            // Préparer les données de sortie
            const trackingData = {
                action: 'clock_out',
                wifi_ssid: wifi_ssid
            };

            console.log('📊 Données de sortie envoyées:', trackingData);

            const response = await fetch('time_tracking_api_wifi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(trackingData)
            });

            const result = await response.json();
            console.log('📨 Réponse API sortie:', result);

            if (result.success) {
                this.showMessage(result.message, 'success');
                this.updateUI();
                
                // Nettoyer le SSID sauvegardé
                localStorage.removeItem('last_wifi_ssid');
            } else {
                this.showMessage(`❌ ${result.message}`, 'error');
            }

        } catch (error) {
            console.error('❌ Erreur lors de la sortie:', error);
            this.showMessage(`❌ Erreur: ${error.message}`, 'error');
        } finally {
            this.isTracking = false;
        }
    }

    async checkCurrentStatus() {
        try {
            const response = await fetch('time_tracking_api_wifi.php?action=get_status');
            const result = await response.json();

            if (result.success && result.data) {
                console.log('📊 Statut actuel:', result.data);
                this.updateStatusDisplay(result.data);
            }
        } catch (error) {
            console.error('❌ Erreur lors de la vérification du statut:', error);
        }
    }

    updateStatusDisplay(data) {
        const statusElement = document.getElementById('tracking-status');
        if (!statusElement) return;

        if (data.status === 'active') {
            statusElement.innerHTML = `
                <div class="alert alert-success">
                    <h5>✅ Pointage en cours</h5>
                    <p><strong>Début:</strong> ${data.clock_in}</p>
                    <p><strong>Durée actuelle:</strong> ${data.current_duration}h</p>
                    <p><strong>WiFi:</strong> ${data.wifi_ssid || 'Non spécifié'}</p>
                    ${data.auto_approved ? '<span class="badge bg-success">✅ Auto-approuvé</span>' : '<span class="badge bg-warning">⏱️ En attente d\'approbation</span>'}
                </div>
            `;
        } else if (data.status === 'completed') {
            statusElement.innerHTML = `
                <div class="alert alert-info">
                    <h5>📋 Dernier pointage terminé</h5>
                    <p><strong>Durée:</strong> ${data.work_duration}h</p>
                    <p><strong>Fin:</strong> ${data.clock_out}</p>
                    <p><strong>WiFi:</strong> ${data.wifi_ssid || 'Non spécifié'}</p>
                </div>
            `;
        } else {
            statusElement.innerHTML = `
                <div class="alert alert-secondary">
                    <h5>📝 Aucun pointage</h5>
                    <p>Vous pouvez commencer un nouveau pointage</p>
                </div>
            `;
        }
    }

    updateUI() {
        // Mettre à jour les boutons en fonction du statut
        const clockInBtn = document.getElementById('clock-in-btn');
        const clockOutBtn = document.getElementById('clock-out-btn');
        
        if (clockInBtn) {
            clockInBtn.disabled = this.isTracking;
        }
        if (clockOutBtn) {
            clockOutBtn.disabled = this.isTracking;
        }
    }

    showMessage(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const messageHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const container = document.getElementById('messages-container') || document.body;
        container.insertAdjacentHTML('afterbegin', messageHtml);

        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
}

// Initialiser le système de pointage WiFi
let timeTrackingWiFi;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation du système de pointage WiFi...');
    timeTrackingWiFi = new SimpleWiFiTimeTracking();
    
    // Attacher les événements aux boutons
    const clockInBtn = document.getElementById('clock-in-btn');
    const clockOutBtn = document.getElementById('clock-out-btn');
    
    if (clockInBtn) {
        clockInBtn.addEventListener('click', () => timeTrackingWiFi.clockIn());
    }
    
    if (clockOutBtn) {
        clockOutBtn.addEventListener('click', () => timeTrackingWiFi.clockOut());
    }
    
    console.log('✅ Système de pointage WiFi initialisé avec succès');
});

// Exporter pour utilisation globale
window.SimpleWiFiTimeTracking = SimpleWiFiTimeTracking;
