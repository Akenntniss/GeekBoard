<!-- MODAL DE RECHERCHE PREMIUM - Design moderne intégré à GeekBoard -->
<div class="modal fade modal-recherche-premium" id="rechercheModal" tabindex="-1" aria-labelledby="rechercheModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Header Moderne -->
            <div class="recherche-header">
                <h5 class="recherche-title" id="rechercheModalLabel">
                    <div class="icon">
                        <i class="fas fa-search"></i>
                    </div>
                    Recherche Intelligente
                </h5>
                <button type="button" class="recherche-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Section Recherche -->
            <div class="recherche-section">
                <div class="recherche-container">
                    <div class="recherche-input-group">
                        <input type="text" class="recherche-input" id="rechercheInput" 
                               placeholder="Rechercher un client, réparation, commande..." 
                               autocomplete="off">
                        <button class="recherche-btn" id="rechercheBtn">
                            <i class="fas fa-search me-2"></i>
                            Rechercher
                        </button>
                    </div>
                    <div class="recherche-help">
                        <i class="fas fa-info-circle me-1"></i>
                        Recherche intelligente dans tous les clients, réparations et commandes
                    </div>
                </div>
            </div>

            <!-- Tabs Modernes -->
            <div class="recherche-tabs">
                <button class="recherche-tab btn-outline-primary" id="clients-tab">
                    <i class="fas fa-users"></i>
                    Clients
                    <span class="badge">0</span>
                </button>
                <button class="recherche-tab btn-outline-primary" id="reparations-tab">
                    <i class="fas fa-tools"></i>
                    Réparations
                    <span class="badge">0</span>
                </button>
                <button class="recherche-tab btn-outline-primary" id="commandes-tab">
                    <i class="fas fa-shopping-cart"></i>
                    Commandes
                    <span class="badge">0</span>
                </button>
            </div>

            <!-- Résultats -->
            <div class="recherche-results">
                <!-- Message vide -->
                <div id="rechercheEmpty" class="recherche-empty">
                    <div class="icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h6>Commencez votre recherche</h6>
                    <p>Saisissez au moins 2 caractères pour rechercher dans tous vos données</p>
                </div>

                <!-- Résultats Clients -->
                <div id="clients-results" class="result-container">
                    <div class="result-card">
                        <div class="result-header">
                            <h6 class="result-title">
                                <div class="result-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                Clients trouvés
                            </h6>
                        </div>
                        <div class="result-body">
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user me-1"></i>Client</th>
                                            <th><i class="fas fa-envelope me-1"></i>Contact</th>
                                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientsTableBody">
                                        <!-- Résultats générés dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résultats Réparations -->
                <div id="reparations-results" class="result-container">
                    <div class="result-card">
                        <div class="result-header">
                            <h6 class="result-title">
                                <div class="result-icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                Réparations trouvées
                            </h6>
                        </div>
                        <div class="result-body">
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                            <th><i class="fas fa-user me-1"></i>Client</th>
                                            <th><i class="fas fa-mobile-alt me-1"></i>Appareil</th>
                                            <th><i class="fas fa-exclamation-triangle me-1"></i>Problème</th>
                                            <th><i class="fas fa-clock me-1"></i>Statut</th>
                                            <th><i class="fas fa-calendar me-1"></i>Date</th>
                                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reparationsTableBody">
                                        <!-- Résultats générés dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résultats Commandes -->
                <div id="commandes-results" class="result-container">
                    <div class="result-card">
                        <div class="result-header">
                            <h6 class="result-title">
                                <div class="result-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                Commandes trouvées
                            </h6>
                        </div>
                        <div class="result-body">
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-box me-1"></i>Pièce</th>
                                            <th><i class="fas fa-user me-1"></i>Client</th>
                                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="commandesTableBody">
                                        <!-- Résultats générés dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour les actions rapides -->
<style>
.action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 15px;
    transition: all 0.3s ease;
    border: none;
    margin: 0 0.1rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.action-btn.btn-primary {
    background: linear-gradient(135deg, #4361ee, #6178f1);
}

.action-btn.btn-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.action-btn.btn-warning {
    background: linear-gradient(135deg, #f1c40f, #f39c12);
}

.action-btn.btn-info {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.phone-number {
    font-family: monospace;
    font-weight: 600;
    color: #4361ee;
}

.device-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Effet de pulsation pour les compteurs */
@keyframes pulse-counter {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.badge-updated {
    animation: pulse-counter 0.5s ease-in-out;
}

/* Effet de brillance pour les lignes de tableau */
.table tbody tr {
    position: relative;
    overflow: hidden;
}

.table tbody tr::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(67, 97, 238, 0.1), transparent);
    transition: left 0.5s ease;
}

.table tbody tr:hover::before {
    left: 100%;
}

/* Améliorations pour le mode sombre */
.dark-mode .phone-number {
    color: #7dd3fc !important;
}

.dark-mode .device-info {
    color: #9ca3af !important;
}

.dark-mode .action-btn {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.dark-mode .action-btn:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
}

/* ========== MODAL RECHERCHE MODERNE ========== */
.recherche-modal {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #4a6741 100%);
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-primary: 0 20px 40px rgba(0, 0, 0, 0.1);
    --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.08);
    --border-radius: 16px;
    --border-radius-lg: 24px;
}

.modal-recherche-premium {
    backdrop-filter: blur(20px);
    background: rgba(15, 23, 42, 0.8);
}

.modal-recherche-premium .modal-dialog {
    max-width: 95vw;
    width: 1400px;
    margin: 1rem auto;
}

.modal-recherche-premium .modal-content {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-primary);
    overflow: hidden;
    animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* ========== HEADER MODERNE ========== */
.recherche-header {
    background: var(--primary-gradient);
    padding: 1.5rem 2rem;
    position: relative;
    overflow: hidden;
}

.recherche-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.recherche-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0;
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.recherche-title .icon {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    animation: iconFloat 3s ease-in-out infinite;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.recherche-close {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: white;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    z-index: 2;
}

.recherche-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

/* ========== SECTION RECHERCHE ========== */
.recherche-section {
    padding: 2rem;
    background: rgba(30, 41, 59, 0.5);
}

.recherche-container {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.recherche-input-group {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--glass-bg);
    border: 2px solid var(--glass-border);
    border-radius: var(--border-radius);
    backdrop-filter: blur(20px);
    transition: all 0.3s ease;
    overflow: hidden;
}

.recherche-input-group:focus-within {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.recherche-input-group::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.recherche-input-group:focus-within::before {
    transform: translateX(100%);
}

.recherche-input {
    flex: 1;
    background: transparent;
    border: none;
    padding: 1rem 1.5rem;
    color: white;
    font-size: 1.1rem;
    outline: none;
}

.recherche-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.recherche-btn {
    background: var(--primary-gradient);
    border: none;
    color: white;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.recherche-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.recherche-btn:hover::before {
    left: 100%;
}

.recherche-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.recherche-help {
    text-align: center;
    margin-top: 1rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

/* ========== TABS MODERNES ========== */
.recherche-tabs {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2rem 0;
    background: rgba(30, 41, 59, 0.3);
}

.recherche-tab {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: rgba(255, 255, 255, 0.8);
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.recherche-tab::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--primary-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.recherche-tab:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.recherche-tab.btn-primary {
    background: var(--primary-gradient);
    color: white;
    box-shadow: var(--shadow-card);
}

.recherche-tab.btn-primary::before {
    transform: scaleX(1);
}

.recherche-tab .badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* ========== RÉSULTATS MODERNES ========== */
.recherche-results {
    padding: 2rem;
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(51, 65, 85, 0.6) 100%);
    min-height: 400px;
}

.result-container {
    display: none;
    animation: resultsFadeIn 0.5s ease-out;
}

@keyframes resultsFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.result-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    backdrop-filter: blur(20px);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    transition: all 0.3s ease;
}

.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.result-header {
    background: var(--dark-gradient);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--glass-border);
}

.result-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
}

.result-icon {
    width: 2rem;
    height: 2rem;
    background: var(--glass-bg);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
}

.result-body {
    padding: 0;
}

/* ========== TABLEAUX MODERNES ========== */
.table-modern {
    margin: 0;
    background: transparent;
    color: white;
}

.table-modern thead th {
    background: rgba(30, 41, 59, 0.7);
    border: none;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 1.5rem;
    position: sticky;
    top: 0;
    z-index: 1;
}

.table-modern tbody tr {
    border: none;
    transition: all 0.3s ease;
    position: relative;
}

.table-modern tbody tr::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background: var(--primary-gradient);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.table-modern tbody tr:hover::before {
    opacity: 0.1;
}

.table-modern tbody tr:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.table-modern tbody td {
    border: none;
    padding: 1rem 1.5rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 1;
}

.table-modern .fw-bold {
    font-weight: 600;
    color: white;
}

.table-modern .text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
    font-size: 0.9rem;
}

/* ========== BADGES MODERNES ========== */
.badge-modern {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.badge-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.badge-modern:hover::before {
    left: 100%;
}

.bg-warning.badge-modern {
    background: var(--warning-gradient) !important;
    color: white;
}

.bg-success.badge-modern {
    background: var(--success-gradient) !important;
    color: white;
}

.bg-primary.badge-modern {
    background: var(--primary-gradient) !important;
    color: white;
}

/* ========== BOUTONS MODERNES ========== */
.btn-modern {
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-modern:hover::before {
    left: 100%;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.btn-outline-primary.btn-modern {
    background: var(--glass-bg);
    border: 1px solid #667eea;
    color: #667eea;
}

.btn-outline-primary.btn-modern:hover {
    background: var(--primary-gradient);
    color: white;
}

.btn-outline-info.btn-modern {
    background: var(--glass-bg);
    border: 1px solid #4facfe;
    color: #4facfe;
}

.btn-outline-info.btn-modern:hover {
    background: var(--success-gradient);
    color: white;
}

/* ========== ÉTAT VIDE ========== */
.recherche-empty {
    text-align: center;
    padding: 3rem 2rem;
    color: rgba(255, 255, 255, 0.7);
}

.recherche-empty .icon {
    width: 4rem;
    height: 4rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: rgba(255, 255, 255, 0.5);
    font-size: 1.5rem;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
    .modal-recherche-premium .modal-dialog {
        width: 95vw;
        margin: 0.5rem auto;
    }
    
    .recherche-header {
        padding: 1rem 1.5rem;
    }
    
    .recherche-section {
        padding: 1.5rem;
    }
    
    .recherche-tabs {
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .recherche-tab {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .table-modern thead th,
    .table-modern tbody td {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}

/* ========== ANIMATIONS D'ENTRÉE ========== */
.animate-in {
    animation: slideInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stagger-animation .result-card {
    animation: slideInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.stagger-animation .result-card:nth-child(1) { animation-delay: 0.1s; }
.stagger-animation .result-card:nth-child(2) { animation-delay: 0.2s; }
.stagger-animation .result-card:nth-child(3) { animation-delay: 0.3s; }
</style>

<!-- Script pour l'animation des compteurs -->
<script>
// Animation des compteurs
function animateCounter(element, finalValue) {
    const duration = 800; // milliseconds
    const startValue = 0;
    const startTime = Date.now();
    
    function updateCounter() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Fonction d'easing pour une animation plus fluide
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentValue = Math.floor(startValue + (finalValue - startValue) * easeOutQuart);
        
        element.textContent = currentValue;
        element.classList.add('badge-updated');
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            setTimeout(() => {
                element.classList.remove('badge-updated');
            }, 500);
        }
    }
    
    updateCounter();
}

// Fonction pour mettre à jour les compteurs avec animation
function updateCountersWithAnimation(clients, reparations, commandes) {
    const clientsCountEl = document.getElementById('clientsCount');
    const reparationsCountEl = document.getElementById('reparationsCount');
    const commandesCountEl = document.getElementById('commandesCount');
    
    if (clientsCountEl) animateCounter(clientsCountEl, clients);
    if (reparationsCountEl) animateCounter(reparationsCountEl, reparations);
    if (commandesCountEl) animateCounter(commandesCountEl, commandes);
}

// Fonction pour formater les numéros de téléphone
function formatPhoneNumber(phone) {
    if (!phone) return '';
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1.$2.$3.$4.$5');
    }
    return phone;
}

// Fonction pour créer des badges de statut colorés
function createStatusBadge(status) {
    const statusMap = {
        'en_attente': { class: 'badge-warning', text: 'En attente', icon: 'fas fa-clock' },
        'en_cours': { class: 'badge-info', text: 'En cours', icon: 'fas fa-cog fa-spin' },
        'termine': { class: 'badge-success', text: 'Terminé', icon: 'fas fa-check' },
        'livre': { class: 'badge-primary', text: 'Livré', icon: 'fas fa-truck' },
        'annule': { class: 'badge-danger', text: 'Annulé', icon: 'fas fa-times' },
        'commande': { class: 'badge-info', text: 'Commandé', icon: 'fas fa-shopping-cart' },
        'recu': { class: 'badge-success', text: 'Reçu', icon: 'fas fa-box-open' }
    };
    
    const statusInfo = statusMap[status] || { class: 'badge-secondary', text: status, icon: 'fas fa-question' };
    
    return `<span class="status-badge ${statusInfo.class}">
                <i class="${statusInfo.icon} me-1"></i>
                ${statusInfo.text}
            </span>`;
}

// Fonction pour créer des boutons d'action
function createActionButtons(type, id, clientId = null) {
    const buttons = [];
    
    if (type === 'client') {
        buttons.push(`<button class="action-btn btn-primary" onclick="voirClient(${id})" title="Voir le client">
                        <i class="fas fa-eye"></i>
                    </button>`);
        buttons.push(`<button class="action-btn btn-info" onclick="ajouterReparation(${id})" title="Nouvelle réparation">
                        <i class="fas fa-plus"></i>
                    </button>`);
    } else if (type === 'reparation') {
        buttons.push(`<button class="action-btn btn-primary" onclick="voirReparation(${id})" title="Voir la réparation">
                        <i class="fas fa-eye"></i>
                    </button>`);
        buttons.push(`<button class="action-btn btn-success" onclick="imprimerEtiquette(${id})" title="Imprimer étiquette">
                        <i class="fas fa-print"></i>
                    </button>`);
    } else if (type === 'commande') {
        buttons.push(`<button class="action-btn btn-primary" onclick="voirCommande(${id})" title="Voir la commande">
                        <i class="fas fa-eye"></i>
                    </button>`);
        buttons.push(`<button class="action-btn btn-warning" onclick="modifierCommande(${id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>`);
    }
    
    return buttons.join('');
}

// Fonctions de navigation (à adapter selon votre système)
function voirClient(id) {
    window.location.href = `?page=clients&action=voir&id=${id}`;
}

function ajouterReparation(clientId) {
    window.location.href = `?page=ajouter_reparation&client_id=${clientId}`;
}

function voirReparation(id) {
    window.location.href = `?page=reparations&action=voir&id=${id}`;
}

function imprimerEtiquette(id) {
    window.open(`imprimer_etiquette.php?id=${id}`, '_blank');
}

function voirCommande(id) {
    window.location.href = `?page=commandes&action=voir&id=${id}`;
}

function modifierCommande(id) {
    window.location.href = `?page=commandes&action=modifier&id=${id}`;
}
</script> 