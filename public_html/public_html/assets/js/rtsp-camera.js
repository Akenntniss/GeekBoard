/**
 * Gestionnaire de caméra RTSP pour la capture d'images de surveillance
 * Intégration dans le formulaire d'ajout de réparation
 */

class RTSPCameraManager {
    constructor() {
        this.apiUrl = '/api/rtsp_capture.php';
        this.captureContainer = null;
        this.currentCaptures = [];
        this.refreshInterval = null;
        this.isActive = false;
        
        this.init();
    }
    
    init() {
        // Vérifier si nous sommes sur la page d'ajout de réparation
        if (!document.getElementById('rep_etape3')) {
            return;
        }
        
        this.createRTSPInterface();
        this.bindEvents();
        this.testConnection();
    }
    
    /**
     * Créer l'interface RTSP dans l'étape 3 du formulaire
     */
    createRTSPInterface() {
        const photoSection = document.querySelector('#rep_etape3 .mb-4:has(#rep_photo_file)');
        if (!photoSection) return;
        
        // Créer le conteneur pour la caméra RTSP
        const rtspContainer = document.createElement('div');
        rtspContainer.className = 'rtsp-camera-container mb-3';
        rtspContainer.innerHTML = `
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-video me-2"></i>
                        Caméra de surveillance
                    </h6>
                    <div class="rtsp-status">
                        <span class="badge bg-secondary" id="rtsp-status-badge">
                            <i class="fas fa-spinner fa-spin me-1"></i>Test...
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Contrôles -->
                        <div class="col-12 mb-3">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary" id="capture-rtsp-photo" disabled>
                                    <i class="fas fa-camera me-2"></i>Capturer image
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="refresh-rtsp-list">
                                    <i class="fas fa-sync me-2"></i>Actualiser
                                </button>
                                <button type="button" class="btn btn-outline-info" id="toggle-auto-capture">
                                    <i class="fas fa-play me-2"></i>Auto (5s)
                                </button>
                            </div>
                        </div>
                        
                        <!-- Zone d'affichage des captures -->
                        <div class="col-12">
                            <div class="rtsp-captures-gallery" id="rtsp-captures-gallery">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-image fa-2x mb-2"></i>
                                    <p class="mb-0">Aucune capture disponible</p>
                                    <small>Cliquez sur "Capturer image" pour prendre une photo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Insérer avant la section photo classique
        photoSection.parentNode.insertBefore(rtspContainer, photoSection);
        
        this.captureContainer = rtspContainer;
    }
    
    /**
     * Lier les événements
     */
    bindEvents() {
        // Bouton de capture
        document.getElementById('capture-rtsp-photo')?.addEventListener('click', () => {
            this.capturePhoto();
        });
        
        // Bouton d'actualisation
        document.getElementById('refresh-rtsp-list')?.addEventListener('click', () => {
            this.loadRecentCaptures();
        });
        
        // Bouton de capture automatique
        document.getElementById('toggle-auto-capture')?.addEventListener('click', () => {
            this.toggleAutoCapture();
        });
    }
    
    /**
     * Tester la connexion RTSP
     */
    async testConnection() {
        try {
            const response = await fetch(`${this.apiUrl}?action=test`);
            const result = await response.json();
            
            const statusBadge = document.getElementById('rtsp-status-badge');
            const captureBtn = document.getElementById('capture-rtsp-photo');
            
            if (result.success) {
                statusBadge.innerHTML = '<i class="fas fa-check me-1"></i>Connecté';
                statusBadge.className = 'badge bg-success';
                captureBtn.disabled = false;
                
                // Charger les captures récentes
                this.loadRecentCaptures();
            } else {
                statusBadge.innerHTML = '<i class="fas fa-times me-1"></i>Hors ligne';
                statusBadge.className = 'badge bg-danger';
                captureBtn.disabled = true;
                
                console.error('Erreur connexion RTSP:', result.message);
            }
        } catch (error) {
            console.error('Erreur test connexion:', error);
            const statusBadge = document.getElementById('rtsp-status-badge');
            statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur';
            statusBadge.className = 'badge bg-warning';
        }
    }
    
    /**
     * Capturer une photo du flux RTSP
     */
    async capturePhoto() {
        const captureBtn = document.getElementById('capture-rtsp-photo');
        const originalText = captureBtn.innerHTML;
        
        try {
            // Afficher l'état de chargement
            captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Capture...';
            captureBtn.disabled = true;
            
            const response = await fetch(`${this.apiUrl}?action=capture`, {
                method: 'POST'
            });
            const result = await response.json();
            
            if (result.success) {
                // Actualiser la galerie
                this.loadRecentCaptures();
                
                // Notification de succès
                this.showNotification('Image capturée avec succès!', 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erreur capture:', error);
            this.showNotification('Erreur lors de la capture: ' + error.message, 'error');
        } finally {
            // Restaurer le bouton
            captureBtn.innerHTML = originalText;
            captureBtn.disabled = false;
        }
    }
    
    /**
     * Charger les captures récentes
     */
    async loadRecentCaptures() {
        try {
            const response = await fetch(`${this.apiUrl}?action=list`);
            const result = await response.json();
            
            if (result.success) {
                this.currentCaptures = result.captures;
                this.renderCapturesGallery();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erreur chargement captures:', error);
        }
    }
    
    /**
     * Afficher la galerie des captures
     */
    renderCapturesGallery() {
        const gallery = document.getElementById('rtsp-captures-gallery');
        if (!gallery) return;
        
        if (this.currentCaptures.length === 0) {
            gallery.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="fas fa-image fa-2x mb-2"></i>
                    <p class="mb-0">Aucune capture disponible</p>
                    <small>Cliquez sur "Capturer image" pour prendre une photo</small>
                </div>
            `;
            return;
        }
        
        const capturesHtml = this.currentCaptures.map(capture => {
            const date = new Date(capture.timestamp * 1000);
            const timeStr = date.toLocaleTimeString('fr-FR');
            
            return `
                <div class="rtsp-capture-item" data-path="${capture.path}">
                    <div class="capture-image-container">
                        <img src="${capture.path}" alt="Capture ${timeStr}" class="capture-image">
                        <div class="capture-overlay">
                            <div class="capture-time">${timeStr}</div>
                            <div class="capture-actions">
                                <button type="button" class="btn btn-sm btn-primary select-capture" title="Utiliser cette image">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary preview-capture" title="Aperçu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        gallery.innerHTML = `
            <div class="rtsp-captures-grid">
                ${capturesHtml}
            </div>
        `;
        
        // Lier les événements des captures
        this.bindCaptureEvents();
    }
    
    /**
     * Lier les événements des captures individuelles
     */
    bindCaptureEvents() {
        // Sélection d'une capture
        document.querySelectorAll('.select-capture').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const captureItem = e.target.closest('.rtsp-capture-item');
                const imagePath = captureItem.dataset.path;
                this.selectCapture(imagePath);
            });
        });
        
        // Aperçu d'une capture
        document.querySelectorAll('.preview-capture').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const captureItem = e.target.closest('.rtsp-capture-item');
                const imagePath = captureItem.dataset.path;
                this.previewCapture(imagePath);
            });
        });
    }
    
    /**
     * Sélectionner une capture pour l'utiliser dans le formulaire
     */
    async selectCapture(imagePath) {
        try {
            // Convertir l'image en base64 pour l'intégrer au formulaire
            const base64Data = await this.imageToBase64(imagePath);
            
            // Mettre à jour le champ photo du formulaire
            const photoField = document.getElementById('rep_photo_appareil');
            if (photoField) {
                photoField.value = base64Data;
            }
            
            // Afficher un aperçu dans l'interface existante
            const photoPreview = document.getElementById('rep_photo_preview');
            if (photoPreview) {
                photoPreview.src = imagePath;
                photoPreview.style.display = 'block';
            }
            
            // Marquer visuellement la capture sélectionnée
            document.querySelectorAll('.rtsp-capture-item').forEach(item => {
                item.classList.remove('selected');
            });
            document.querySelector(`[data-path="${imagePath}"]`).classList.add('selected');
            
            this.showNotification('Image de surveillance sélectionnée!', 'success');
            
        } catch (error) {
            console.error('Erreur sélection capture:', error);
            this.showNotification('Erreur lors de la sélection: ' + error.message, 'error');
        }
    }
    
    /**
     * Convertir une image en base64
     */
    async imageToBase64(imagePath) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                canvas.width = this.naturalWidth;
                canvas.height = this.naturalHeight;
                
                ctx.drawImage(this, 0, 0);
                
                const dataURL = canvas.toDataURL('image/jpeg', 0.8);
                resolve(dataURL);
            };
            
            img.onerror = () => reject(new Error('Impossible de charger l\'image'));
            img.src = imagePath;
        });
    }
    
    /**
     * Afficher un aperçu de capture dans un modal
     */
    previewCapture(imagePath) {
        // Créer un modal simple pour l'aperçu
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aperçu capture surveillance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${imagePath}" class="img-fluid" alt="Aperçu capture">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" onclick="rtspCamera.selectCapture('${imagePath}')" data-bs-dismiss="modal">
                            Utiliser cette image
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Supprimer le modal après fermeture
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
    
    /**
     * Basculer la capture automatique
     */
    toggleAutoCapture() {
        const btn = document.getElementById('toggle-auto-capture');
        
        if (this.isActive) {
            // Arrêter la capture automatique
            clearInterval(this.refreshInterval);
            this.isActive = false;
            btn.innerHTML = '<i class="fas fa-play me-2"></i>Auto (5s)';
            btn.className = 'btn btn-outline-info';
        } else {
            // Démarrer la capture automatique
            this.refreshInterval = setInterval(() => {
                this.capturePhoto();
            }, 5000);
            
            this.isActive = true;
            btn.innerHTML = '<i class="fas fa-stop me-2"></i>Arrêter auto';
            btn.className = 'btn btn-outline-danger';
            
            // Première capture immédiate
            this.capturePhoto();
        }
    }
    
    /**
     * Afficher une notification
     */
    showNotification(message, type = 'info') {
        // Utiliser le système de notification existant ou créer une simple alerte
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Fallback avec une alerte simple
            const alertClass = type === 'success' ? 'alert-success' : 
                             type === 'error' ? 'alert-danger' : 'alert-info';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }
    }
    
    /**
     * Nettoyer les ressources
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Styles CSS pour l'interface RTSP
const rtspStyles = `
<style>
.rtsp-camera-container {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    transition: border-color 0.3s;
}

.rtsp-camera-container:hover {
    border-color: #0d6efd;
}

.rtsp-captures-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
}

.rtsp-capture-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
    cursor: pointer;
}

.rtsp-capture-item:hover {
    transform: scale(1.05);
}

.rtsp-capture-item.selected {
    box-shadow: 0 0 0 3px #0d6efd;
}

.capture-image-container {
    position: relative;
    width: 100%;
    height: 120px;
    overflow: hidden;
}

.capture-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.capture-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 8px;
}

.rtsp-capture-item:hover .capture-overlay {
    opacity: 1;
}

.capture-time {
    color: white;
    font-size: 12px;
    text-align: center;
    background: rgba(0,0,0,0.5);
    border-radius: 4px;
    padding: 2px 6px;
}

.capture-actions {
    display: flex;
    justify-content: center;
    gap: 5px;
}

.capture-actions .btn {
    padding: 4px 8px;
}

/* Mode sombre */
.dark-mode .rtsp-camera-container {
    border-color: #374151;
    background-color: #1f2937;
}

.dark-mode .rtsp-camera-container:hover {
    border-color: #3b82f6;
}

.dark-mode .card {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
}

/* Responsive */
@media (max-width: 768px) {
    .rtsp-captures-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
    
    .capture-image-container {
        height: 100px;
    }
}
</style>
`;

// Injecter les styles
document.head.insertAdjacentHTML('beforeend', rtspStyles);

// Initialiser le gestionnaire RTSP
let rtspCamera;
document.addEventListener('DOMContentLoaded', function() {
    rtspCamera = new RTSPCameraManager();
});

// Nettoyer lors du déchargement de la page
window.addEventListener('beforeunload', function() {
    if (rtspCamera) {
        rtspCamera.destroy();
    }
}); 