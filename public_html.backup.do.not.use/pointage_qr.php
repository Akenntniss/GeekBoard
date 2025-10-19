<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 Pointage QR Code - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .container-fluid {
            padding: 0;
        }
        
        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: 20px;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }
        
        .header-section > * {
            position: relative;
            z-index: 1;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        
        .btn-pointage {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            min-height: 80px;
        }
        
        .btn-pointage:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-pointage:disabled {
            background: #6c757d;
            box-shadow: none;
            transform: none;
        }
        
        .btn-sortie {
            background: linear-gradient(135deg, #dc3545, #c82333);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        
        .btn-sortie:hover {
            box-shadow: 0 12px 35px rgba(220, 53, 69, 0.4);
        }
        
        .qr-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .status-active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 5px solid #28a745;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #cce7ff, #b8daff);
            border-left: 5px solid #007bff;
        }
        
        .duration-display {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .loading-spinner {
            display: none;
        }
        
        .loading .loading-spinner {
            display: inline-block;
        }
        
        .loading .btn-text {
            display: none;
        }
        
        @media (max-width: 768px) {
            .main-card {
                margin: 10px;
                border-radius: 20px;
            }
            
            .header-section {
                padding: 1.5rem;
            }
            
            .btn-pointage {
                padding: 1rem;
                font-size: 1.1rem;
                min-height: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8 col-md-10">
                <div class="main-card">
                    <!-- Header avec badge QR -->
                    <div class="header-section">
                        <div class="qr-badge">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        
                        <h1 class="display-6 mb-3">
                            <i class="fas fa-clock"></i>
                            Pointage Mobile
                        </h1>
                        
                        <p class="lead mb-0">
                            🎯 Pointage via QR Code
                        </p>
                        
                        <!-- Informations utilisateur -->
                        <div class="user-info" id="user-info">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="spinner-border spinner-border-sm text-light me-2" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <span>Chargement des informations...</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <div id="messages-container" class="px-4 pt-3"></div>
                    
                    <!-- Zone de statut -->
                    <div class="p-4">
                        <div class="status-card" id="status-card">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Vérification du statut...</span>
                                </div>
                                <h5>Vérification du statut de pointage...</h5>
                            </div>
                        </div>
                        
                        <!-- Boutons de pointage -->
                        <div class="row g-3" id="pointage-buttons" style="display: none;">
                            <div class="col-md-6">
                                <button id="clock-in-btn" class="btn btn-pointage w-100 position-relative" disabled>
                                    <div class="loading-spinner spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <span class="btn-text">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Pointer l'Arrivée
                                    </span>
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button id="clock-out-btn" class="btn btn-pointage btn-sortie w-100 position-relative" disabled>
                                    <div class="loading-spinner spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <span class="btn-text">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        Pointer le Départ
                                    </span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Informations additionnelles -->
                        <div class="mt-4 text-center">
                            <div class="row text-muted">
                                <div class="col-6">
                                    <small>
                                        <i class="fas fa-mobile-alt"></i>
                                        Pointage Mobile
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small>
                                        <i class="fas fa-clock"></i>
                                        <span id="current-time"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        class QRTimeTracking {
            constructor() {
                this.currentUser = null;
                this.currentStatus = null;
                this.timeInterval = null;
                this.init();
            }

            async init() {
                console.log('📱 Initialisation du pointage QR...');
                
                // Démarrer l'horloge
                this.startClock();
                
                // Charger les informations utilisateur
                await this.loadUserInfo();
                
                // Charger le statut de pointage
                await this.loadStatus();
                
                // Attacher les événements
                this.attachEvents();
                
                console.log('✅ Pointage QR initialisé');
            }

            async loadUserInfo() {
                try {
                    const response = await fetch('time_tracking_api_qr.php?action=get_user_info');
                    const result = await response.json();

                    const userInfoElement = document.getElementById('user-info');
                    
                    if (result.success && result.data.user) {
                        this.currentUser = result.data.user;
                        userInfoElement.innerHTML = `
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-user-circle fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-0">${result.data.user.full_name}</h6>
                                    <small class="opacity-75">${result.data.user.role}</small>
                                </div>
                            </div>
                        `;
                    } else {
                        userInfoElement.innerHTML = `
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Erreur de chargement utilisateur
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Erreur chargement utilisateur:', error);
                    document.getElementById('user-info').innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur de connexion
                        </div>
                    `;
                }
            }

            async loadStatus() {
                try {
                    const response = await fetch('time_tracking_api_qr.php?action=get_status');
                    const result = await response.json();

                    const statusCard = document.getElementById('status-card');
                    const pointageButtons = document.getElementById('pointage-buttons');
                    
                    if (result.success && result.data) {
                        this.currentStatus = result.data;
                        this.updateStatusDisplay(result.data);
                        
                        // Afficher les boutons
                        pointageButtons.style.display = 'block';
                        this.updateButtons(result.data);
                    } else {
                        statusCard.innerHTML = `
                            <div class="text-center">
                                <h5 class="text-muted">📝 Nouveau pointage</h5>
                                <p class="text-muted">Aucun pointage en cours</p>
                            </div>
                        `;
                        pointageButtons.style.display = 'block';
                        this.updateButtons({ status: 'no_entry' });
                    }
                } catch (error) {
                    console.error('Erreur chargement statut:', error);
                    document.getElementById('status-card').innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h5>Erreur de connexion</h5>
                        </div>
                    `;
                }
            }

            updateStatusDisplay(data) {
                const statusCard = document.getElementById('status-card');
                
                if (data.status === 'active') {
                    statusCard.className = 'status-card status-active';
                    statusCard.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-play-circle fa-3x text-success mb-3"></i>
                            <h4 class="text-success">✅ Pointage en cours</h4>
                            <p class="mb-3">
                                <strong>Début :</strong> ${new Date(data.clock_in).toLocaleString('fr-FR')}
                            </p>
                            <div class="duration-display" id="duration-display">
                                ${data.current_duration}h
                            </div>
                            ${data.qr_code_used ? '<span class="badge bg-warning">📱 QR Code</span>' : ''}
                            ${data.auto_approved ? '<span class="badge bg-success ms-2">✅ Auto-approuvé</span>' : '<span class="badge bg-warning ms-2">⏱️ En attente</span>'}
                        </div>
                    `;
                    
                    // Démarrer le chronomètre
                    this.startDurationCounter(data.clock_in);
                    
                } else if (data.status === 'completed') {
                    statusCard.className = 'status-card status-completed';
                    statusCard.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-3x text-primary mb-3"></i>
                            <h4 class="text-primary">📋 Dernier pointage terminé</h4>
                            <div class="row text-center mt-3">
                                <div class="col-6">
                                    <small class="text-muted">Durée totale</small>
                                    <div class="h5">${data.work_duration}h</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Fin</small>
                                    <div class="h6">${new Date(data.clock_out).toLocaleString('fr-FR')}</div>
                                </div>
                            </div>
                            ${data.qr_code_used ? '<span class="badge bg-warning mt-2">📱 QR Code</span>' : ''}
                        </div>
                    `;
                } else {
                    statusCard.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">📝 Prêt à pointer</h4>
                            <p class="text-muted">Aucun pointage en cours</p>
                        </div>
                    `;
                }
            }

            updateButtons(data) {
                const clockInBtn = document.getElementById('clock-in-btn');
                const clockOutBtn = document.getElementById('clock-out-btn');
                
                if (data.status === 'active') {
                    clockInBtn.disabled = true;
                    clockInBtn.innerHTML = `
                        <span class="btn-text">
                            <i class="fas fa-check me-2"></i>
                            Déjà pointé
                        </span>
                    `;
                    clockOutBtn.disabled = false;
                } else {
                    clockInBtn.disabled = false;
                    clockInBtn.innerHTML = `
                        <div class="loading-spinner spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Pointer l'Arrivée
                        </span>
                    `;
                    clockOutBtn.disabled = true;
                    clockOutBtn.innerHTML = `
                        <span class="btn-text">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Pointer le Départ
                        </span>
                    `;
                }
            }

            attachEvents() {
                document.getElementById('clock-in-btn').addEventListener('click', () => this.clockIn());
                document.getElementById('clock-out-btn').addEventListener('click', () => this.clockOut());
            }

            async clockIn() {
                const btn = document.getElementById('clock-in-btn');
                btn.classList.add('loading');
                btn.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('action', 'clock_in');
                    formData.append('qr_code', '1'); // Marquer comme pointage QR

                    const response = await fetch('time_tracking_api_qr.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showMessage(result.message, 'success');
                        await this.loadStatus();
                    } else {
                        this.showMessage(`❌ ${result.message}`, 'error');
                    }
                } catch (error) {
                    this.showMessage(`❌ Erreur: ${error.message}`, 'error');
                } finally {
                    btn.classList.remove('loading');
                }
            }

            async clockOut() {
                const btn = document.getElementById('clock-out-btn');
                btn.classList.add('loading');
                btn.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('action', 'clock_out');
                    formData.append('qr_code', '1'); // Marquer comme pointage QR

                    const response = await fetch('time_tracking_api_qr.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showMessage(result.message, 'success');
                        await this.loadStatus();
                        this.stopDurationCounter();
                    } else {
                        this.showMessage(`❌ ${result.message}`, 'error');
                    }
                } catch (error) {
                    this.showMessage(`❌ Erreur: ${error.message}`, 'error');
                } finally {
                    btn.classList.remove('loading');
                }
            }

            startDurationCounter(clockIn) {
                this.stopDurationCounter();
                
                this.timeInterval = setInterval(() => {
                    const now = new Date();
                    const start = new Date(clockIn);
                    const duration = (now - start) / (1000 * 60 * 60); // en heures
                    
                    const durationElement = document.getElementById('duration-display');
                    if (durationElement) {
                        durationElement.textContent = `${duration.toFixed(2)}h`;
                    }
                }, 60000); // Mise à jour chaque minute
            }

            stopDurationCounter() {
                if (this.timeInterval) {
                    clearInterval(this.timeInterval);
                    this.timeInterval = null;
                }
            }

            startClock() {
                const updateClock = () => {
                    const now = new Date();
                    document.getElementById('current-time').textContent = now.toLocaleTimeString('fr-FR');
                };
                
                updateClock();
                setInterval(updateClock, 1000);
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

                const container = document.getElementById('messages-container');
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

        // Initialiser le système de pointage QR
        document.addEventListener('DOMContentLoaded', function() {
            new QRTimeTracking();
        });
    </script>
</body>
</html>
