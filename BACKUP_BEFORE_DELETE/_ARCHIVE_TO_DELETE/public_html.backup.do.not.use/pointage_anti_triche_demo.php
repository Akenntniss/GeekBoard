<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 Pointage Anti-Triche - Système Sécurisé GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .security-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .tracking-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            margin: 0.2rem;
            display: inline-block;
        }
        
        .security-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .btn-security {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .btn-security:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            color: white;
        }
        
        .tracking-info {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 10px 10px 0;
        }
        
        .security-warning {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 10px 10px 0;
        }
        
        .fingerprint-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .status-active { background-color: #28a745; }
        .status-pending { background-color: #ffc107; }
        .status-inactive { background-color: #6c757d; }
        
        .location-map {
            height: 200px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h1 class="text-white mb-3">
                        <i class="fas fa-shield-alt"></i>
                        Système de Pointage Anti-Triche
                    </h1>
                    <p class="text-white-50 lead">
                        Tracking complet avec géolocalisation, empreinte digitale et sécurité maximale
                    </p>
                </div>

                <!-- Messages Container -->
                <div id="messages-container"></div>

                <div class="row">
                    <!-- Panneau de Contrôle Principal -->
                    <div class="col-lg-6 mb-4">
                        <div class="security-card p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-clock security-icon"></i>
                                <h3>Contrôle de Pointage</h3>
                            </div>

                            <div id="tracking-status" class="mb-4">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <p class="mt-2">Vérification du statut...</p>
                                </div>
                            </div>

                            <div class="d-grid gap-3">
                                <button id="clock-in-btn" class="btn btn-security btn-lg" disabled>
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Pointer l'Arrivée
                                </button>
                                
                                <button id="clock-out-btn" class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Pointer le Départ
                                </button>
                            </div>

                            <div class="mt-4 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Toutes les données sont chiffrées et sécurisées
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Informations de Sécurité -->
                    <div class="col-lg-6 mb-4">
                        <div class="security-card p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-fingerprint security-icon"></i>
                                <h3>Empreinte de Sécurité</h3>
                            </div>

                            <div id="security-info">
                                <div class="tracking-info">
                                    <h6><i class="fas fa-mobile-alt"></i> Informations de l'Appareil</h6>
                                    <div id="device-info">Collecte en cours...</div>
                                </div>

                                <div class="tracking-info">
                                    <h6><i class="fas fa-wifi"></i> Connexion Réseau</h6>
                                    <div id="network-info">Analyse en cours...</div>
                                </div>

                                <div class="tracking-info">
                                    <h6><i class="fas fa-map-marker-alt"></i> Géolocalisation</h6>
                                    <div id="location-info">
                                        <div class="location-map">
                                            <span class="text-muted">
                                                <i class="fas fa-map-marked-alt fa-2x"></i><br>
                                                Position GPS requise
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Données de Tracking Détaillées -->
                    <div class="col-12 mb-4">
                        <div class="security-card p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-database security-icon"></i>
                                <h3>Données de Tracking Collectées</h3>
                                <p class="text-muted">Preview des informations de sécurité capturées</p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-shield-alt text-success"></i> Empreintes de Sécurité</h5>
                                    <div id="security-fingerprints" class="fingerprint-preview">
                                        Génération en cours...
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-code text-info"></i> Métadonnées Techniques</h5>
                                    <div id="technical-metadata" class="fingerprint-preview">
                                        Collecte en cours...
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h5><i class="fas fa-chart-line text-warning"></i> Indicateurs de Sécurité</h5>
                                <div id="security-indicators" class="d-flex flex-wrap">
                                    <span class="tracking-badge">
                                        <span class="status-indicator status-pending"></span>
                                        Géolocalisation: En attente
                                    </span>
                                    <span class="tracking-badge">
                                        <span class="status-indicator status-pending"></span>
                                        Empreinte Canvas: En cours
                                    </span>
                                    <span class="tracking-badge">
                                        <span class="status-indicator status-pending"></span>
                                        Empreinte WebGL: En cours
                                    </span>
                                    <span class="tracking-badge">
                                        <span class="status-indicator status-pending"></span>
                                        Empreinte Audio: En cours
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Avertissements de Sécurité -->
                <div class="row">
                    <div class="col-12">
                        <div class="security-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Politique de Sécurité</h5>
                            <ul class="mb-0">
                                <li>La géolocalisation est <strong>obligatoire</strong> pour tous les pointages</li>
                                <li>L'empreinte digitale de votre appareil est capturée et analysée</li>
                                <li>Les tentatives de contournement sont <strong>automatiquement détectées</strong></li>
                                <li>Toutes les données sont stockées de manière sécurisée et conforme RGPD</li>
                                <li>L'usage de VPN/Proxy est surveillé et signalé</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="time_tracking_enhanced.js"></script>
    
    <script>
        // Extension pour la page de démonstration
        document.addEventListener('DOMContentLoaded', function() {
            // Mettre à jour l'affichage des informations de sécurité
            if (window.timeTracking) {
                updateSecurityDisplay();
            }
        });

        function updateSecurityDisplay() {
            if (!window.timeTracking || !window.timeTracking.securityData) {
                setTimeout(updateSecurityDisplay, 1000);
                return;
            }

            const data = window.timeTracking.securityData;
            
            // Informations de l'appareil
            document.getElementById('device-info').innerHTML = `
                <strong>Écran:</strong> ${data.screen_resolution}<br>
                <strong>Plateforme:</strong> ${data.platform}<br>
                <strong>CPU:</strong> ${data.cpu_cores} cœurs<br>
                <strong>Mémoire:</strong> ${data.memory_gb || 'N/A'} GB<br>
                <strong>Langue:</strong> ${data.browser_language}
            `;

            // Informations réseau
            document.getElementById('network-info').innerHTML = `
                <strong>Type:</strong> ${data.connection_type}<br>
                <strong>Vitesse:</strong> ${data.connection_speed}<br>
                <strong>Mobile:</strong> ${data.is_mobile ? 'Oui' : 'Non'}<br>
                <strong>Support tactile:</strong> ${data.touch_support ? 'Oui' : 'Non'}
            `;

            // Empreintes de sécurité
            document.getElementById('security-fingerprints').innerHTML = `
<strong>Canvas:</strong> ${data.canvas_fingerprint}
<strong>WebGL:</strong> ${data.webgl_fingerprint}
<strong>Audio:</strong> ${data.audio_fingerprint}
<strong>Session:</strong> ${data.session_hash}
<strong>Plugins:</strong> ${data.plugins_count}
<strong>AdBlocker:</strong> ${data.has_ad_blocker ? 'Détecté' : 'Non détecté'}
<strong>DevTools:</strong> ${data.has_dev_tools ? 'Détecté' : 'Non détecté'}
            `;

            // Métadonnées techniques
            document.getElementById('technical-metadata').innerHTML = `
<strong>Timezone:</strong> ${data.timezone_offset} min
<strong>Viewport:</strong> ${data.viewport}
<strong>Orientation:</strong> ${data.device_orientation}
<strong>Batterie:</strong> ${data.battery_level || 'N/A'}%
<strong>En charge:</strong> ${data.is_charging ? 'Oui' : 'Non'}
<strong>Temps de chargement:</strong> ${data.page_load_time}ms
<strong>Referrer:</strong> ${data.referrer || 'Direct'}
            `;

            // Mettre à jour les indicateurs
            updateSecurityIndicators(data);
        }

        function updateSecurityIndicators(data) {
            const indicators = document.getElementById('security-indicators');
            indicators.innerHTML = `
                <span class="tracking-badge">
                    <span class="status-indicator status-active"></span>
                    Empreinte Canvas: Capturée
                </span>
                <span class="tracking-badge">
                    <span class="status-indicator status-active"></span>
                    Empreinte WebGL: Capturée
                </span>
                <span class="tracking-badge">
                    <span class="status-indicator status-active"></span>
                    Empreinte Audio: Capturée
                </span>
                <span class="tracking-badge">
                    <span class="status-indicator ${data.has_ad_blocker ? 'status-pending' : 'status-active'}"></span>
                    AdBlocker: ${data.has_ad_blocker ? 'Détecté' : 'Non détecté'}
                </span>
                <span class="tracking-badge">
                    <span class="status-indicator ${data.has_dev_tools ? 'status-pending' : 'status-active'}"></span>
                    DevTools: ${data.has_dev_tools ? 'Ouvert' : 'Fermé'}
                </span>
            `;
        }
    </script>
</body>
</html>
