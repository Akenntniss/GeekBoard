/**
 * Script de pointage ANTI-TRICHE avec tracking maximum
 * Collecte géolocalisation, empreinte digitale, et métadonnées de sécurité
 */

class EnhancedTimeTracking {
    constructor() {
        this.isTracking = false;
        this.deviceFingerprint = null;
        this.securityData = {};
        this.init();
    }

    async init() {
        console.log('🔒 Initialisation du système de pointage sécurisé...');
        
        // Collecter immédiatement l'empreinte de l'appareil
        await this.collectDeviceFingerprint();
        
        // Mettre à jour l'interface
        this.updateUI();
        
        // Vérifier le statut actuel
        this.checkCurrentStatus();
        
        console.log('✅ Système de pointage sécurisé initialisé');
    }

    async collectDeviceFingerprint() {
        console.log('🔍 Collecte de l\'empreinte digitale de l\'appareil...');
        
        try {
            this.securityData = {
                // Informations de base
                screen_resolution: `${screen.width}x${screen.height}`,
                browser_language: navigator.language,
                timezone_offset: new Date().getTimezoneOffset(),
                platform: navigator.platform,
                user_agent: navigator.userAgent,
                
                // Informations avancées de l'appareil
                cpu_cores: navigator.hardwareConcurrency || null,
                memory_gb: navigator.deviceMemory || null,
                
                // Informations réseau
                connection_type: this.getConnectionType(),
                connection_speed: this.getConnectionSpeed(),
                
                // Informations de la batterie
                battery_level: await this.getBatteryLevel(),
                is_charging: await this.getChargingStatus(),
                
                // Orientation de l'appareil
                device_orientation: this.getDeviceOrientation(),
                
                // Empreintes de sécurité
                canvas_fingerprint: this.generateCanvasFingerprint(),
                webgl_fingerprint: this.generateWebGLFingerprint(),
                audio_fingerprint: await this.generateAudioFingerprint(),
                
                // Détection de localisation IP
                ip_v6: await this.getIPv6(),
                country_code: await this.getCountryCode(),
                city_name: await this.getCityName(),
                isp_name: await this.getISPName(),
                
                // Horodatage client
                client_timestamp: new Date().toISOString(),
                
                // Détection de plugins et extensions
                plugins_count: navigator.plugins.length,
                has_ad_blocker: this.detectAdBlocker(),
                has_dev_tools: this.detectDevTools(),
                
                // Informations sur la page
                page_load_time: performance.timing.loadEventEnd - performance.timing.navigationStart,
                referrer: document.referrer,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                
                // Détection de manipulation
                is_mobile: /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
                touch_support: 'ontouchstart' in window,
                
                // Hash unique de session
                session_hash: this.generateSessionHash()
            };
            
            console.log('🔒 Empreinte digitale collectée:', this.securityData);
            
        } catch (error) {
            console.error('❌ Erreur lors de la collecte de l\'empreinte:', error);
        }
    }

    getConnectionType() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return connection ? connection.effectiveType : 'unknown';
    }

    getConnectionSpeed() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return connection ? `${connection.downlink}Mbps` : 'unknown';
    }

    async getBatteryLevel() {
        try {
            if ('getBattery' in navigator) {
                const battery = await navigator.getBattery();
                return Math.round(battery.level * 100);
            }
        } catch (error) {
            console.log('Battery API non disponible');
        }
        return null;
    }

    async getChargingStatus() {
        try {
            if ('getBattery' in navigator) {
                const battery = await navigator.getBattery();
                return battery.charging;
            }
        } catch (error) {
            console.log('Battery API non disponible');
        }
        return null;
    }

    getDeviceOrientation() {
        if (screen.orientation) {
            return screen.orientation.type;
        } else if (window.orientation !== undefined) {
            return `${window.orientation}deg`;
        }
        return 'unknown';
    }

    generateCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Dessiner un pattern unique
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('GeekBoard Security 🔒', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Fingerprint: ' + Date.now(), 4, 45);
            
            return canvas.toDataURL().slice(-50); // Prendre les 50 derniers caractères
        } catch (error) {
            return 'canvas_error';
        }
    }

    generateWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) return 'webgl_unsupported';
            
            const info = {
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION),
                extensions: gl.getSupportedExtensions().length
            };
            
            return btoa(JSON.stringify(info)).slice(-50);
        } catch (error) {
            return 'webgl_error';
        }
    }

    async generateAudioFingerprint() {
        try {
            // Créer une empreinte audio alternative sans AudioContext
            // Utiliser les propriétés audio du navigateur
            const audioData = {
                sampleRate: 44100,
                channelCount: 2,
                supported_formats: []
            };
            
            // Vérifier les formats audio supportés
            const audio = document.createElement('audio');
            const formats = ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
            
            formats.forEach(format => {
                if (audio.canPlayType(`audio/${format}`)) {
                    audioData.supported_formats.push(format);
                }
            });
            
            // Créer un hash basé sur les capacités audio
            const audioString = JSON.stringify(audioData);
            let hash = 0;
            for (let i = 0; i < audioString.length; i++) {
                const char = audioString.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            
            return hash.toString(36);
        } catch (error) {
            console.log('Erreur génération empreinte audio:', error);
            return 'audio_fallback';
        }
    }

    async getIPv6() {
        try {
            // Utiliser une API publique pour obtenir l'IPv6
            const response = await fetch('https://api64.ipify.org?format=json');
            const data = await response.json();
            return data.ip || null;
        } catch (error) {
            return null;
        }
    }

    async getCountryCode() {
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
            return data.country_code || null;
        } catch (error) {
            return null;
        }
    }

    async getCityName() {
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
            return data.city || null;
        } catch (error) {
            return null;
        }
    }

    async getISPName() {
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
            return data.org || null;
        } catch (error) {
            return null;
        }
    }

    detectAdBlocker() {
        try {
            const testAd = document.createElement('div');
            testAd.innerHTML = '&nbsp;';
            testAd.className = 'adsbox';
            testAd.style.position = 'absolute';
            testAd.style.left = '-10000px';
            document.body.appendChild(testAd);
            
            const isBlocked = testAd.offsetHeight === 0;
            document.body.removeChild(testAd);
            
            return isBlocked;
        } catch (error) {
            return false;
        }
    }

    detectDevTools() {
        let devtools = false;
        const threshold = 160;
        
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            devtools = true;
        }
        
        return devtools;
    }

    generateSessionHash() {
        const data = `${navigator.userAgent}${screen.width}${screen.height}${Date.now()}${Math.random()}`;
        let hash = 0;
        for (let i = 0; i < data.length; i++) {
            const char = data.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString(36);
    }

    async getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Géolocalisation non supportée'));
                return;
            }

            console.log('📍 Demande de géolocalisation en cours...');
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    console.log('✅ Géolocalisation obtenue:', position.coords);
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        altitude: position.coords.altitude,
                        heading: position.coords.heading,
                        speed: position.coords.speed,
                        timestamp: position.timestamp
                    });
                },
                (error) => {
                    console.error('❌ Erreur géolocalisation:', error.message);
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                }
            );
        });
    }

    async clockIn() {
        if (this.isTracking) {
            this.showMessage('⏱️ Pointage déjà en cours...', 'warning');
            return;
        }

        this.isTracking = true;
        this.showMessage('🔄 Pointage en cours...', 'info');

        try {
            // Collecter la géolocalisation
            const location = await this.getCurrentPosition();
            console.log('📍 Position obtenue:', location);

            // Préparer toutes les données de tracking
            const trackingData = {
                action: 'clock_in',
                ...this.securityData,
                ...location,
                // Données supplémentaires en temps réel
                current_timestamp: new Date().toISOString(),
                page_url: window.location.href,
                screen_orientation: screen.orientation ? screen.orientation.angle : window.orientation
            };

            console.log('📊 Données de tracking envoyées:', trackingData);

            // Envoyer à l'API
            const response = await fetch('time_tracking_api_enhanced.php', {
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
                this.updateUI();
                
                // Afficher les informations de sécurité
                if (result.data && result.data.security_info) {
                    console.log('🔒 Informations de sécurité:', result.data.security_info);
                }
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
        this.showMessage('🔄 Pointage de sortie en cours...', 'info');

        try {
            // Collecter la géolocalisation de sortie
            const location = await this.getCurrentPosition();
            console.log('📍 Position de sortie obtenue:', location);

            // Préparer les données de sortie
            const trackingData = {
                action: 'clock_out',
                ...location,
                current_timestamp: new Date().toISOString(),
                page_url: window.location.href
            };

            console.log('📊 Données de sortie envoyées:', trackingData);

            const response = await fetch('time_tracking_api_enhanced.php', {
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
                
                // Afficher les informations de mouvement
                if (result.data && result.data.security_info) {
                    console.log('🚶 Informations de mouvement:', result.data.security_info);
                    if (result.data.distance_km) {
                        this.showMessage(`📏 Distance parcourue: ${result.data.distance_km} km`, 'info');
                    }
                }
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
            const response = await fetch('time_tracking_api_enhanced.php?action=get_status');
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
                    <p><strong>Localisation:</strong> ${data.location_in}</p>
                    ${data.security_info.gps_tracked ? '<span class="badge bg-success">📍 GPS Tracké</span>' : ''}
                    ${data.security_info.is_vpn_proxy ? '<span class="badge bg-warning">⚠️ VPN/Proxy détecté</span>' : ''}
                </div>
            `;
        } else if (data.status === 'completed') {
            statusElement.innerHTML = `
                <div class="alert alert-info">
                    <h5>📋 Dernier pointage terminé</h5>
                    <p><strong>Durée:</strong> ${data.work_duration}h</p>
                    <p><strong>Fin:</strong> ${data.clock_out}</p>
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

// Initialiser le système de pointage sécurisé
let timeTracking;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation du système de pointage anti-triche...');
    timeTracking = new EnhancedTimeTracking();
    
    // Attacher les événements aux boutons
    const clockInBtn = document.getElementById('clock-in-btn');
    const clockOutBtn = document.getElementById('clock-out-btn');
    
    if (clockInBtn) {
        clockInBtn.addEventListener('click', () => timeTracking.clockIn());
    }
    
    if (clockOutBtn) {
        clockOutBtn.addEventListener('click', () => timeTracking.clockOut());
    }
    
    console.log('✅ Système de pointage anti-triche initialisé avec succès');
});

// Exporter pour utilisation globale
window.EnhancedTimeTracking = EnhancedTimeTracking;