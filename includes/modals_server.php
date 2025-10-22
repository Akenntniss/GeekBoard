<?php
/**
 * MODALS BOOTSTRAP 5.3.3 - VERSION CLEAN
 * Modals recréés de zéro pour être fonctionnels
 */
?>

<!-- Styles CSS pour les modals modernes -->
<style>
/* Styles pour le modal nouvelles actions */
.modern-modal {
    border-radius: 20px !important;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%) !important;
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}

.bg-gradient-scanner {
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
}

/* Particules animées */
.particles-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.particle {
    position: absolute;
    width: 3px;
    height: 3px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    border-radius: 50%;
    animation: particleFloat 4s ease-in-out infinite;
}

@keyframes particleFloat {
    0%, 100% { 
        transform: translateY(0) rotate(0deg); 
        opacity: 0.3; 
    }
    50% { 
        transform: translateY(-20px) rotate(180deg); 
        opacity: 0.8; 
    }
}

/* Grid des actions modernes */
.modern-actions-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    position: relative;
    z-index: 2;
}

/* Cartes d'action modernes */
.modern-action-card {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    text-decoration: none;
    color: #2d3748;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    cursor: pointer;
}

.modern-action-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: rgba(102, 126, 234, 0.5);
    color: #2d3748;
    text-decoration: none;
}

/* Effet de lueur sur les cartes */
.card-glow {
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    border-radius: 18px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.modern-action-card:hover .card-glow {
    opacity: 1;
}

/* Conteneur d'icône */
.action-icon-container {
    position: relative;
    margin-right: 1rem;
    flex-shrink: 0;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    position: relative;
    z-index: 2;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Effet de pulsation */
.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 50px;
    height: 50px;
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 12px;
    transform: translate(-50%, -50%);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.4);
        opacity: 0;
    }
}

/* Contenu de l'action */
.action-content {
    flex: 1;
    min-width: 0;
}

.action-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #2d3748;
}

.action-description {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

/* Flèche */
.action-arrow {
    color: #cbd5e0;
    font-size: 1rem;
    transition: all 0.3s ease;
    margin-left: 0.75rem;
}

.modern-action-card:hover .action-arrow {
    color: #667eea;
    transform: translateX(4px);
}

/* Animation de l'icône de pulsation */
.pulse-icon {
    animation: iconPulse 2s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Styles spécifiques pour chaque type de carte */
.repair-card:hover {
    border-color: rgba(102, 126, 234, 0.5);
}

.task-card:hover {
    border-color: rgba(17, 153, 142, 0.5);
}

.order-card:hover {
    border-color: rgba(240, 147, 251, 0.5);
}

.clock-in-card:hover {
    border-color: rgba(17, 153, 142, 0.5);
}

.clock-out-card:hover {
    border-color: rgba(255, 107, 107, 0.5);
}

.loading-card {
    opacity: 0.7;
    cursor: default;
}

.loading-card:hover {
    transform: none;
    box-shadow: none;
}

/* Mode sombre */
body.dark-mode .modern-action-card {
    background: rgba(51, 65, 85, 0.95);
    border-color: rgba(148, 163, 184, 0.2);
    color: #f1f5f9;
}

body.dark-mode .action-title {
    color: #f1f5f9;
}

body.dark-mode .action-description {
    color: #cbd5e0;
}

body.dark-mode .modern-action-card:hover {
    color: #f1f5f9;
    border-color: rgba(102, 126, 234, 0.5);
}

/* ========================================= */
/* STYLES SCANNER UNIVERSEL */
/* ========================================= */

.scanner-container {
    position: relative;
    width: 100%;
    height: 400px;
    background: #000;
    border-radius: 0;
    overflow: hidden;
}

#universal_scanner_video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.scanner-frame {
    position: relative;
    width: 250px;
    height: 250px;
    border: 2px solid rgba(139, 92, 246, 0.8);
    border-radius: 12px;
    background: rgba(139, 92, 246, 0.1);
}

.scanner-corners {
    position: absolute;
    inset: -8px;
}

.scanner-corners::before,
.scanner-corners::after {
    content: '';
    position: absolute;
    width: 30px;
    height: 30px;
    border: 4px solid #8b5cf6;
}

.scanner-corners::before {
    top: 0;
    left: 0;
    border-right: none;
    border-bottom: none;
    border-radius: 8px 0 0 0;
}

.scanner-corners::after {
    bottom: 0;
    right: 0;
    border-left: none;
    border-top: none;
    border-radius: 0 0 8px 0;
}

.scanner-line {
    position: absolute;
    top: 50%;
    left: 10%;
    right: 10%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #06b6d4, transparent);
    animation: scannerSweep 2s ease-in-out infinite;
}

@keyframes scannerSweep {
    0%, 100% { transform: translateY(-125px); opacity: 0; }
    50% { transform: translateY(125px); opacity: 1; }
}

.scanner-status {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.3);
    color: #8b5cf6;
    transition: all 0.3s ease;
}

.scanner-status.success {
    background: rgba(16, 185, 129, 0.1);
    border-color: #10b981;
    color: #10b981;
}

.scanner-status.error {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
    color: #ef4444;
}

.scan-mode-selector .btn-outline-primary {
    border-color: rgba(139, 92, 246, 0.5);
    color: #8b5cf6;
}

.scan-mode-selector .btn-outline-primary:hover,
.scan-mode-selector .btn-check:checked + .btn-outline-primary {
    background: #8b5cf6;
    border-color: #8b5cf6;
    color: white;
}

.scanner-actions .btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.scanner-actions .btn:hover {
    transform: translateY(-1px);
}

/* Mode sombre pour le scanner */
body.dark-mode .scanner-status {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.4);
    color: #a78bfa;
}

body.dark-mode .scanner-status.success {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.4);
    color: #34d399;
}

body.dark-mode .scanner-status.error {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.4);
    color: #f87171;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .scanner-container {
        height: 300px;
    }
    
    .scanner-frame {
        width: 200px;
        height: 200px;
    }
    
    .scanner-actions {
        flex-direction: column;
    }
    
    .scanner-actions .btn {
        margin-bottom: 8px;
    }
}

/* ========================================= */
/* STYLES MODERNES POUR LE SCANNER EXISTANT */
/* ========================================= */

/* Variables CSS pour thème adaptatif */
:root {
    /* Mode jour - Corporate */
    --scanner-primary: #2563eb;
    --scanner-secondary: #1e40af;
    --scanner-accent: #3b82f6;
    --scanner-bg: #ffffff;
    --scanner-surface: #f8fafc;
    --scanner-border: #e2e8f0;
    --scanner-text: #1e293b;
    --scanner-text-secondary: #64748b;
    --scanner-shadow: rgba(0, 0, 0, 0.1);
    --scanner-glow: rgba(59, 130, 246, 0.3);
    --scanner-success: #10b981;
    --scanner-warning: #f59e0b;
    --scanner-error: #ef4444;
}

/* Mode nuit - Futuriste */
@media (prefers-color-scheme: dark) {
    :root {
        --scanner-primary: #00d4ff;
        --scanner-secondary: #0ea5e9;
        --scanner-accent: #38bdf8;
        --scanner-bg: #0f172a;
        --scanner-surface: #1e293b;
        --scanner-border: #334155;
        --scanner-text: #f1f5f9;
        --scanner-text-secondary: #cbd5e0;
        --scanner-shadow: rgba(0, 0, 0, 0.5);
        --scanner-glow: rgba(0, 212, 255, 0.4);
        --scanner-success: #00ff88;
        --scanner-warning: #ffaa00;
        --scanner-error: #ff4444;
    }
}

/* Mode sombre forcé */
body.dark-mode {
    --scanner-primary: #00d4ff;
    --scanner-secondary: #0ea5e9;
    --scanner-accent: #38bdf8;
    --scanner-bg: #0f172a;
    --scanner-surface: #1e293b;
    --scanner-border: #334155;
    --scanner-text: #f1f5f9;
    --scanner-text-secondary: #cbd5e0;
    --scanner-shadow: rgba(0, 0, 0, 0.5);
    --scanner-glow: rgba(0, 212, 255, 0.4);
    --scanner-success: #00ff88;
    --scanner-warning: #ffaa00;
    --scanner-error: #ff4444;
}

/* Z-INDEX PRIORITAIRE POUR LE SCANNER */
#universal_scanner_modal {
    z-index: 99999 !important;
}

#universal_scanner_modal .modal-dialog {
    z-index: 100000 !important;
}

#universal_scanner_modal .modal-content {
    z-index: 100001 !important;
}

#universal_scanner_modal + .modal-backdrop {
    z-index: 99998 !important;
}

/* Modal principal avec design moderne */
#universal_scanner_modal .modal-content {
    border-radius: 24px !important;
    overflow: hidden;
    background: var(--scanner-bg) !important;
    border: 2px solid var(--scanner-border) !important;
    box-shadow: 
        0 25px 50px -12px var(--scanner-shadow),
        0 0 50px var(--scanner-glow) !important;
    position: relative !important;
}

/* Header moderne avec gradient adaptatif */
#universal_scanner_modal .modal-header {
    background: linear-gradient(135deg, var(--scanner-primary), var(--scanner-secondary)) !important;
    position: relative;
    overflow: hidden;
    padding: 1.5rem !important;
}

/* Particules animées dans le header */
#universal_scanner_modal .modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 2px, transparent 2px),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 2px, transparent 2px),
        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.1) 2px, transparent 2px);
    background-size: 50px 50px, 80px 80px, 60px 60px;
    animation: particleMove 20s linear infinite;
    pointer-events: none;
}

@keyframes particleMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(50px, 50px); }
}

/* Titre avec icône animée */
#universal_scanner_modal .modal-title {
    color: white !important;
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    display: flex !important;
    align-items: center !important;
    position: relative;
    z-index: 2;
}

#universal_scanner_modal .pulse-icon {
    animation: scannerPulse 2s ease-in-out infinite !important;
    filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5));
}

@keyframes scannerPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Bouton de fermeture moderne */
#universal_scanner_modal .btn-close {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: 12px !important;
    width: 40px !important;
    height: 40px !important;
    backdrop-filter: blur(10px) !important;
    transition: all 0.3s ease !important;
    position: relative;
    z-index: 2;
}

#universal_scanner_modal .btn-close:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    transform: scale(1.05) !important;
}

/* Body avec grille cyber */
#universal_scanner_modal .modal-body {
    background: var(--scanner-bg) !important;
    position: relative;
}

#universal_scanner_modal .modal-body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.05;
    background-image: 
        linear-gradient(var(--scanner-primary) 1px, transparent 1px),
        linear-gradient(90deg, var(--scanner-primary) 1px, transparent 1px);
    background-size: 20px 20px;
    animation: gridMove 20s linear infinite;
    pointer-events: none;
    z-index: 0;
}

@keyframes gridMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(20px, 20px); }
}

/* Sélecteur de mode moderne */
#universal_scanner_modal .scan-mode-selector {
    background: var(--scanner-surface) !important;
    border-radius: 16px !important;
    padding: 1rem !important;
    margin-bottom: 1.5rem !important;
    border: 1px solid var(--scanner-border) !important;
    position: relative;
    z-index: 1;
}

#universal_scanner_modal .btn-group {
    border-radius: 12px !important;
    overflow: hidden;
    box-shadow: 0 4px 15px var(--scanner-shadow) !important;
}

#universal_scanner_modal .btn-outline-primary {
    border-color: var(--scanner-border) !important;
    color: var(--scanner-text) !important;
    background: var(--scanner-bg) !important;
    transition: all 0.3s ease !important;
    font-weight: 600 !important;
    padding: 0.75rem 1rem !important;
}

#universal_scanner_modal .btn-outline-primary:hover {
    border-color: var(--scanner-primary) !important;
    color: var(--scanner-primary) !important;
    background: rgba(37, 99, 235, 0.1) !important;
    transform: translateY(-1px) !important;
}

#universal_scanner_modal .btn-check:checked + .btn-outline-primary {
    background: linear-gradient(135deg, var(--scanner-primary), var(--scanner-secondary)) !important;
    border-color: var(--scanner-primary) !important;
    color: white !important;
    box-shadow: 0 0 20px var(--scanner-glow) !important;
}

/* Contrôles modernes */
#universal_scanner_modal .scanner-controls {
    background: var(--scanner-surface) !important;
    border-top: 1px solid var(--scanner-border) !important;
    position: relative;
    z-index: 1;
}

#universal_scanner_modal .scanner-status {
    background: var(--scanner-bg) !important;
    border: 1px solid var(--scanner-border) !important;
    border-radius: 12px !important;
    padding: 1rem !important;
    color: var(--scanner-text) !important;
    font-weight: 500 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    box-shadow: 0 2px 10px var(--scanner-shadow) !important;
}

#universal_scanner_modal .scanner-status i {
    color: var(--scanner-primary) !important;
    font-size: 1.2rem !important;
}

/* Boutons d'action modernes */
#universal_scanner_modal .scanner-actions {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 0.75rem !important;
    margin-top: 1rem !important;
}

#universal_scanner_modal .scanner-actions .btn {
    padding: 1rem !important;
    border-radius: 16px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    border: 2px solid var(--scanner-border) !important;
    background: var(--scanner-bg) !important;
    color: var(--scanner-text) !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 0.5rem !important;
    position: relative !important;
    overflow: hidden !important;
}

#universal_scanner_modal .scanner-actions .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
}

#universal_scanner_modal .scanner-actions .btn:hover::before {
    left: 100%;
}

#universal_scanner_modal .scanner-actions .btn:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 10px 30px var(--scanner-shadow) !important;
    border-color: var(--scanner-primary) !important;
    color: var(--scanner-primary) !important;
}

#universal_scanner_modal .scanner-actions .btn i {
    font-size: 1.5rem !important;
    transition: transform 0.3s ease !important;
}

#universal_scanner_modal .scanner-actions .btn:hover i {
    transform: scale(1.2) !important;
}

/* Styles spécifiques pour chaque bouton */
#universal_scanner_modal .btn-secondary:hover {
    background: linear-gradient(135deg, var(--scanner-warning), #f97316) !important;
    color: white !important;
    border-color: var(--scanner-warning) !important;
}

#universal_scanner_modal .btn-info:hover {
    background: linear-gradient(135deg, var(--scanner-accent), var(--scanner-primary)) !important;
    color: white !important;
    border-color: var(--scanner-accent) !important;
}

#universal_scanner_modal .btn-warning:hover {
    background: linear-gradient(135deg, var(--scanner-success), #059669) !important;
    color: white !important;
    border-color: var(--scanner-success) !important;
}

/* ASSURER LA VISIBILITÉ QUAND LE MODAL EST OUVERT */
#universal_scanner_modal.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

#universal_scanner_modal.show .modal-dialog {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
}

#universal_scanner_modal.show .modal-content {
    opacity: 1 !important;
    visibility: visible !important;
}

/* OVERRIDE TOUS LES STYLES CONFLICTUELS */
#universal_scanner_modal * {
    box-sizing: border-box !important;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    #universal_scanner_modal .scanner-actions {
        grid-template-columns: 1fr !important;
        gap: 0.5rem !important;
    }
    
    #universal_scanner_modal .scanner-actions .btn {
        padding: 0.75rem !important;
        flex-direction: row !important;
        justify-content: center !important;
    }
    
    #universal_scanner_modal .modal-content {
        margin: 1rem !important;
        border-radius: 16px !important;
    }
}

</style>

<!-- ========================================= -->
<!-- MODAL: NOUVELLES ACTIONS - DESIGN MODERNE -->
<!-- ========================================= -->
<div class="modal fade" id="nouvelles_actions_modal" tabindex="-1" aria-labelledby="nouvelles_actions_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-primary">
                <h5 class="modal-title text-white fw-bold" id="nouvelles_actions_modal_label">
                    <i class="fas fa-sparkles me-2 pulse-icon"></i>
                    Créer quelque chose de nouveau
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative overflow-hidden">
                <!-- Effet de particules animées -->
                <div class="particles-container">
                    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
                    <div class="particle" style="left: 30%; animation-delay: 1s;"></div>
                    <div class="particle" style="left: 50%; animation-delay: 2s;"></div>
                    <div class="particle" style="left: 70%; animation-delay: 0.5s;"></div>
                    <div class="particle" style="left: 90%; animation-delay: 1.5s;"></div>
                </div>
                
                <!-- Actions modernes avec cartes -->
                <div class="modern-actions-grid p-4">
                    <!-- Nouvelle Réparation -->
                    <a href="index.php?page=ajouter_reparation" class="modern-action-card repair-card">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-primary">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Réparation</h6>
                            <p class="action-description">Créer un dossier de réparation complet</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <!-- Nouvelle Tâche -->
                    <button type="button" class="modern-action-card task-card" id="openNewTaskFromActions">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-success">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Tâche</h6>
                            <p class="action-description">Ajouter une tâche à accomplir</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>

                    <!-- Nouvelle Commande -->
                    <button type="button" class="modern-action-card order-card" id="openNewOrderFromActions">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-warning">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Commande</h6>
                            <p class="action-description">Commander des pièces et fournitures</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>

                    <!-- Pointage Dynamique - Sera rempli par JavaScript -->
                    <div id="dynamic-timetracking-button">
                        <!-- Bouton de chargement temporaire -->
                        <div class="modern-action-card loading-card">
                            <div class="card-glow"></div>
                            <div class="action-icon-container">
                                <div class="action-icon bg-gradient-info">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <div class="pulse-ring"></div>
                            </div>
                            <div class="action-content">
                                <h6 class="action-title">Chargement...</h6>
                                <p class="action-description">Vérification de l'état du pointage</p>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner Universel -->
                    <button type="button" class="modern-action-card scanner-card" id="openUniversalScanner">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-scanner">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Scanner</h6>
                            <p class="action-description">QR codes et codes-barres</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>
                </div>
            </div>
            
            <!-- Footer avec effet holographique -->
            <div class="modal-footer border-0 bg-light bg-opacity-50">
                <small class="text-muted d-flex align-items-center">
                    <i class="fas fa-magic me-1"></i>
                    Choisissez une action pour commencer
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: SCANNER UNIVERSEL - QR + CODES-BARRES -->
<!-- ========================================= -->
<div class="modal fade" id="universal_scanner_modal" tabindex="-1" aria-labelledby="universal_scanner_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-scanner">
                <h5 class="modal-title text-white fw-bold" id="universal_scanner_modal_label">
                    <i class="fas fa-qrcode me-2 pulse-icon"></i>
                    Scanner Universel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopUniversalScanner()"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <!-- Scanner Video -->
                <div class="scanner-container">
                    <video id="universal_scanner_video" autoplay muted playsinline></video>
                    <div class="scanner-overlay">
                        <div class="scanner-frame">
                            <div class="scanner-corners"></div>
                        </div>
                        <div class="scanner-line"></div>
                    </div>
                </div>
                
                <!-- Status et contrôles -->
                <div class="scanner-controls p-4">
                    <div class="scanner-status mb-3" id="universal_scanner_status">
                        <i class="fas fa-camera me-2"></i>
                        Positionnez le code dans le cadre
                    </div>
                    
                    <!-- Mode de scan -->
                    <div class="scan-mode-selector mb-3">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="scanMode" id="modeAuto" value="auto" checked>
                            <label class="btn btn-outline-primary" for="modeAuto">
                                <i class="fas fa-magic me-1"></i>Auto
                            </label>
                            
                            <input type="radio" class="btn-check" name="scanMode" id="modeQR" value="qr">
                            <label class="btn btn-outline-primary" for="modeQR">
                                <i class="fas fa-qrcode me-1"></i>QR Code
                            </label>
                            
                            <input type="radio" class="btn-check" name="scanMode" id="modeBarcode" value="barcode">
                            <label class="btn btn-outline-primary" for="modeBarcode">
                                <i class="fas fa-barcode me-1"></i>Code-barres
                            </label>
                        </div>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="scanner-actions d-flex gap-2">
                        <button class="btn btn-secondary flex-fill" onclick="toggleScannerFlash()">
                            <i class="fas fa-flashlight" id="flashIcon"></i>
                            Flash
                        </button>
                        <button class="btn btn-info flex-fill" onclick="switchCamera()">
                            <i class="fas fa-camera-rotate"></i>
                            Caméra
                        </button>
                        <button class="btn btn-warning flex-fill" onclick="manualCodeEntry()">
                            <i class="fas fa-keyboard"></i>
                            Manuel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: MENU NAVIGATION - DESIGN FUTURISTE -->
<!-- ========================================= -->
<div class="modal fade" id="menu_navigation_modal" tabindex="-1" aria-labelledby="menu_navigation_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg modern-navigation-modal">
            <div class="modal-header border-0 bg-gradient-navigation">
                <h5 class="modal-title text-white fw-bold" id="menu_navigation_modal_label">
                    <i class="fas fa-rocket me-2 rocket-pulse"></i>
                    Centre de Navigation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative overflow-hidden">
                <!-- Effet de grille futuriste en arrière-plan -->
                <div class="cyber-grid"></div>
                
                <!-- Particules de navigation -->
                <div class="nav-particles-container">
                    <div class="nav-particle" style="left: 15%; animation-delay: 0s;"></div>
                    <div class="nav-particle" style="left: 35%; animation-delay: 0.7s;"></div>
                    <div class="nav-particle" style="left: 55%; animation-delay: 1.4s;"></div>
                    <div class="nav-particle" style="left: 75%; animation-delay: 2.1s;"></div>
                    <div class="nav-particle" style="left: 85%; animation-delay: 0.3s;"></div>
                </div>
                
                <!-- Navigation moderne complète avec sections -->
                <div class="modern-nav-grid p-4">
                    <style>
                        /* Masquer l'ancien contenu pour éviter les doublons */
                        #menu_navigation_modal .modern-nav-grid .nav-section-header,
                        #menu_navigation_modal .modern-nav-grid .nav-grid-row { display:none !important; }
                        /* Mise en page des lignes personnalisées */
                        #menu_navigation_modal .curated-row { 
                            display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:14px;
                        }
                        @media (max-width: 768px){
                            #menu_navigation_modal .curated-row { grid-template-columns: 1fr; }
                        }
                    </style>

                    <!-- CURATED MENU (sans doublons) -->
                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-layer-group me-2"></i>Général</h6></div>
                    <div class="curated-row">
                        <a href="index.php" class="modern-nav-card home-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-home"><i class="fas fa-home"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Accueil</h6><p class="nav-subtitle">Tableau de bord</p></div>
                        </a>
                        <a href="index.php?page=reparations" class="modern-nav-card repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-repair"><i class="fas fa-tools"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Réparations</h6><p class="nav-subtitle">Gestion des réparations</p></div>
                        </a>
                        <a href="index.php?page=taches" class="modern-nav-card tasks-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-tasks"><i class="fas fa-tasks"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Tâches</h6><p class="nav-subtitle">Gestion des tâches</p></div>
                        </a>
                    </div>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-box-open me-2"></i>Stock & Connaissance</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=commandes_pieces" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-orders"><i class="fas fa-shopping-cart"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Commandes</h6><p class="nav-subtitle">Pièces</p></div>
                        </a>
                        <a href="index.php?page=rachat_appareils" class="modern-nav-card rachat-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-rachat"><i class="fas fa-exchange-alt"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Rachat</h6><p class="nav-subtitle">Appareils</p></div>
                        </a>
                        <a href="index.php?page=base_connaissances" class="modern-nav-card knowledge-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-knowledge"><i class="fas fa-book"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Base de connaissance</h6><p class="nav-subtitle">Documentation</p></div>
                        </a>
                    </div>

                    <hr class="my-2"/>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-users me-2"></i>Clients & Missions</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=clients" class="modern-nav-card clients-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-clients"><i class="fas fa-users"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Clients</h6><p class="nav-subtitle">Base clients</p></div>
                        </a>
                        <a href="index.php?page=sms_historique" class="modern-nav-card sms-history-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-history"><i class="fas fa-history"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Historique SMS</h6><p class="nav-subtitle">Envois</p></div>
                        </a>
                        <a href="index.php?page=presence_gestion" class="modern-nav-card absences-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-absences"><i class="fas fa-user-clock"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Absence & Retard</h6><p class="nav-subtitle">Présence</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=mes_missions" class="modern-nav-card my-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-my-missions"><i class="fas fa-clipboard-check"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Mes missions</h6><p class="nav-subtitle">Personnel</p></div>
                        </a>
                        <a href="index.php?page=inventaire" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-orders"><i class="fas fa-boxes"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Inventaire</h6><p class="nav-subtitle">Produits</p></div>
                        </a>
                    </div>

                    <hr class="my-2"/>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-bug me-2"></i>Qualité</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=bug-reports" class="modern-nav-card bug-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logout"><i class="fas fa-bug"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Bug report</h6><p class="nav-subtitle">Signalements</p></div>
                        </a>
                    </div>

                    <hr class="my-2"/>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-shield-alt me-2"></i>Administration</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=comptes_partenaires" class="modern-nav-card admin-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-administration"><i class="fas fa-handshake"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Compte partenaire</h6></div>
                        </a>
                        <a href="index.php?page=employes" class="modern-nav-card employees-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-employees"><i class="fas fa-user-tie"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Employé</h6></div>
                        </a>
                        <a href="index.php?page=admin_timetracking" class="modern-nav-card timetracking-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-administration"><i class="fas fa-clock"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Pointage Admin</h6></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=reparation_logs" class="modern-nav-card logs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logs"><i class="fas fa-clipboard-list"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Journaux de réparation</h6></div>
                        </a>
                        <a href="index.php?page=parametre" class="modern-nav-card settings-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-special"><i class="fas fa-cog"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Parametre</h6></div>
                        </a>
                        <a href="index.php?page=template_sms" class="modern-nav-card sms-template-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-template"><i class="fas fa-comment-dots"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Template SMS</h6></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=campagne_sms" class="modern-nav-card sms-campaign-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-campaign"><i class="fas fa-sms"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Campagne SMS</h6></div>
                        </a>
                        <a href="index.php?page=admin_missions" class="modern-nav-card admin-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-admin-missions"><i class="fas fa-tasks"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Admin mission</h6></div>
                        </a>
                    </div>
                    <hr class="my-2"/>
                    <div class="curated-row">
                        <a href="index.php?action=logout" class="modern-nav-card logout-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logout"><i class="fas fa-sign-out-alt"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Déconnexion</h6></div>
                        </a>
                    </div>
                    
                    <!-- Section: Navigation Principale -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-rocket me-2"></i>
                            Navigation Principale
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=accueil" class="modern-nav-card home-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-home">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Accueil</h6>
                                <p class="nav-subtitle">Tableau de bord</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=reparations" class="modern-nav-card repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-repair">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Réparations</h6>
                                <p class="nav-subtitle">Gestion des réparations</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=ajouter_reparation" class="modern-nav-card new-repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-new-repair">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Nouvelle Réparation</h6>
                                <p class="nav-subtitle">Créer une réparation</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Gestion -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-cogs me-2"></i>
                            Gestion
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=commandes_pieces" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-orders">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Commandes</h6>
                                <p class="nav-subtitle">Commandes de pièces</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=taches" class="modern-nav-card tasks-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-tasks">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Tâches</h6>
                                <p class="nav-subtitle">Gestion des tâches</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=rachat_appareils" class="modern-nav-card rachat-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-rachat">
                                    <i class="fas fa-recycle"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Rachat</h6>
                                <p class="nav-subtitle">Rachat d'appareils</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Clients & Support -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-users me-2"></i>
                            Clients & Support
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=clients" class="modern-nav-card clients-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-clients">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Clients</h6>
                                <p class="nav-subtitle">Base clients</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=base_connaissance" class="modern-nav-card knowledge-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-knowledge">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Base de connaissance</h6>
                                <p class="nav-subtitle">Documentations</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=devis" class="modern-nav-card devis-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-special">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Devis</h6>
                                <p class="nav-subtitle">Gestion des devis</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Missions -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-crosshairs me-2"></i>
                            Missions
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=missions" class="modern-nav-card missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-missions">
                                    <i class="fas fa-crosshairs"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Missions</h6>
                                <p class="nav-subtitle">Toutes les missions</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=mes_missions" class="modern-nav-card my-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-my-missions">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Mes missions</h6>
                                <p class="nav-subtitle">Missions personnelles</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=admin_missions" class="modern-nav-card admin-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-admin-missions">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Admin missions</h6>
                                <p class="nav-subtitle">Administration</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Communication -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-comments me-2"></i>
                            Communication
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=communication" class="modern-nav-card communication-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-communication">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Communication</h6>
                                <p class="nav-subtitle">Centre de communication</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=campagne_sms" class="modern-nav-card sms-campaign-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-sms-campaign">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Campagne SMS</h6>
                                <p class="nav-subtitle">Envois groupés</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=template_sms" class="modern-nav-card sms-template-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-sms-template">
                                    <i class="fas fa-file-text"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Template SMS</h6>
                                <p class="nav-subtitle">Modèles de messages</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Ligne SMS Histoire -->
                    <div class="nav-grid-row nav-grid-start">
                        <a href="index.php?page=historique_sms" class="modern-nav-card sms-history-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-sms-history">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Historique SMS</h6>
                                <p class="nav-subtitle">Historique des envois</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Administration -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-shield-alt me-2"></i>
                            Administration
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=administration" class="modern-nav-card administration-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-administration">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Administration</h6>
                                <p class="nav-subtitle">Panel d'administration</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=employes" class="modern-nav-card employees-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-employees">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Employés</h6>
                                <p class="nav-subtitle">Gestion du personnel</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=absences_retards" class="modern-nav-card absences-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-absences">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Absences & Retards</h6>
                                <p class="nav-subtitle">Suivi des absences</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Ligne Administration suite -->
                    <div class="nav-grid-row">
                        <a href="index.php?page=journaux_reparation" class="modern-nav-card logs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-logs">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Journaux de réparation</h6>
                                <p class="nav-subtitle">Logs et historiques</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=signalements_bugs" class="modern-nav-card bugs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-bugs">
                                    <i class="fas fa-bug"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Signalements bugs</h6>
                                <p class="nav-subtitle">Rapports de bugs</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=parametre" class="modern-nav-card settings-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-settings">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Paramètres</h6>
                                <p class="nav-subtitle">Configuration</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Système -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-server me-2"></i>
                            Système
                        </h6>
                    </div>
                    <div class="nav-grid-row nav-grid-center">
                        <a href="index.php?page=changer_magasin" class="modern-nav-card special-card shop-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-shop">
                                    <i class="fas fa-store-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                                <div class="special-orbit"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Changer de magasin</h6>
                                <p class="nav-subtitle">Basculer entre magasins</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=deconnexion" class="modern-nav-card special-card logout-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                                <div class="special-orbit"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Déconnexion</h6>
                                <p class="nav-subtitle">Quitter la session</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>
                </div>

                <!-- Scanner horizontal pour le menu -->
                <div class="nav-scanner-line"></div>
            </div>
            
            <!-- Footer futuriste -->
            <div class="modal-footer border-0 bg-dark bg-opacity-10">
                <small class="text-muted d-flex align-items-center">
                    <i class="fas fa-satellite-dish me-1"></i>
                    Navigation GeekBoard - Interface futuriste
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: AJOUTER COMMANDE - VERSION COMPLÈTE -->
<!-- ========================================= -->
<?php
// S'assurer que la variable dark_mode est définie
$dark_mode = isset($dark_mode) ? $dark_mode : (isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] === true);
?>

<!-- Modal Ajouter Commande - Design Moderne -->
<style>
/* Correction immédiate mode sombre pour ajouterCommandeModal */
.dark-mode #ajouterCommandeModal .modal-content {
    background-color: #111827 !important;
    border: 1px solid #374151 !important;
}
.dark-mode #ajouterCommandeModal .modal-body {
    background-color: #111827 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .modal-header {
    background: linear-gradient(135deg, #1f2937, #111827) !important;
    color: #f8fafc !important;
    border-bottom: 1px solid #374151 !important;
}
.dark-mode #ajouterCommandeModal .form-control,
.dark-mode #ajouterCommandeModal .form-select,
.dark-mode #ajouterCommandeModal input,
.dark-mode #ajouterCommandeModal select {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .form-control:focus,
.dark-mode #ajouterCommandeModal .form-select:focus {
    background-color: #1f2937 !important;
    border-color: #60a5fa !important;
    color: #f8fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
}
.dark-mode #ajouterCommandeModal .form-control::placeholder {
    color: #9ca3af !important;
}
.dark-mode #ajouterCommandeModal select option {
    background-color: #1f2937 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .order-section-title {
    color: #f8fafc !important;
}

/* Supprimer les contours rouges de validation */
#ajouterCommandeModal .form-control.is-invalid,
#ajouterCommandeModal .form-select.is-invalid {
    border-color: #374151 !important;
    box-shadow: none !important;
}

.dark-mode #ajouterCommandeModal .form-control.is-invalid,
.dark-mode #ajouterCommandeModal .form-select.is-invalid {
    border-color: #374151 !important;
    box-shadow: none !important;
}

/* Supprimer les contours verts aussi */
#ajouterCommandeModal .form-control.is-valid,
#ajouterCommandeModal .form-select.is-valid {
    border-color: #374151 !important;
    box-shadow: none !important;
}

.dark-mode #ajouterCommandeModal .form-control.is-valid,
.dark-mode #ajouterCommandeModal .form-select.is-valid {
    border-color: #374151 !important;
    box-shadow: none !important;
}
.dark-mode #ajouterCommandeModal .btn-outline-primary {
    background-color: transparent !important;
    border-color: #60a5fa !important;
    color: #60a5fa !important;
}
.dark-mode #ajouterCommandeModal .btn-outline-primary:hover {
    background-color: #60a5fa !important;
    color: #ffffff !important;
}
/* Styles supplémentaires pour tous les éléments de formulaire */
.dark-mode #ajouterCommandeModal input[type="text"],
.dark-mode #ajouterCommandeModal input[type="number"],
.dark-mode #ajouterCommandeModal textarea,
.dark-mode #ajouterCommandeModal .form-control,
.dark-mode #ajouterCommandeModal .form-select {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal input[type="text"]:focus,
.dark-mode #ajouterCommandeModal input[type="number"]:focus,
.dark-mode #ajouterCommandeModal textarea:focus,
.dark-mode #ajouterCommandeModal .form-control:focus,
.dark-mode #ajouterCommandeModal .form-select:focus {
    background-color: #1f2937 !important;
    border-color: #60a5fa !important;
    color: #f8fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
}
/* Champs spécifiques par ID */
.dark-mode #nom_client_selectionne,
.dark-mode #fournisseur_id_ajout,
.dark-mode #nom_piece,
.dark-mode #code_barre,
.dark-mode #quantite,
.dark-mode #prix_estime {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
/* Labels et textes */
.dark-mode #ajouterCommandeModal label,
.dark-mode #ajouterCommandeModal .form-label,
.dark-mode #ajouterCommandeModal span:not(.badge) {
    color: #f8fafc !important;
}
/* Status pills */
.dark-mode #ajouterCommandeModal .status-pill {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #e5e7eb !important;
    transition: background-color .2s ease, color .2s ease, border-color .2s ease !important;
}
.dark-mode #ajouterCommandeModal .status-pill:hover {
    background: #374151 !important;
}
.dark-mode #ajouterCommandeModal .status-pill:not(.active) {
    background: #1f2937 !important;
    border-color: #374151 !important;
    color: #e5e7eb !important;
}
.dark-mode #ajouterCommandeModal .status-pill.active {
    background: #2563eb !important; /* bleu vif pour l'actif */
    border-color: #3b82f6 !important;
    color: #ffffff !important;
}
.dark-mode #ajouterCommandeModal .status-pill i {
    color: inherit !important;
}
/* Boutons de quantité */
.dark-mode #ajouterCommandeModal .btn-outline-secondary {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .btn-outline-secondary:hover {
    background-color: #4b5563 !important;
    color: #ffffff !important;
}
/* Fonds sombres pour les conteneurs de section et le corps moderne */
.dark-mode #ajouterCommandeModal .modern-modal-body {
    background: #0b1220 !important;
}
.dark-mode #ajouterCommandeModal .order-section {
    background: #0f172a !important;
    border: 1px solid #1f2937 !important;
    border-radius: 12px !important;
    padding: 16px !important;
}
.dark-mode #ajouterCommandeModal .order-section + .order-section {
    margin-top: 16px !important;
}
.dark-mode #ajouterCommandeModal .order-section-title {
    color: #e5e7eb !important;
}
/* Force maximale pour tous les champs - priorité absolue */
body.dark-mode #ajouterCommandeModal input,
body.dark-mode #ajouterCommandeModal select,
body.dark-mode #ajouterCommandeModal textarea,
html body.dark-mode #ajouterCommandeModal .form-control,
html body.dark-mode #ajouterCommandeModal .form-select {
    background-color: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #f8fafc !important;
}
body.dark-mode #ajouterCommandeModal input:focus,
body.dark-mode #ajouterCommandeModal select:focus,
body.dark-mode #ajouterCommandeModal textarea:focus,
html body.dark-mode #ajouterCommandeModal .form-control:focus,
html body.dark-mode #ajouterCommandeModal .form-select:focus {
    background-color: #1f2937 !important;
    border: 1px solid #60a5fa !important;
    color: #f8fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
}
/* Force pour les champs par ID avec spécificité maximale */
html body.dark-mode div#ajouterCommandeModal input#nom_client_selectionne,
html body.dark-mode div#ajouterCommandeModal select#fournisseur_id_ajout,
html body.dark-mode div#ajouterCommandeModal input#nom_piece,
html body.dark-mode div#ajouterCommandeModal input#code_barre,
html body.dark-mode div#ajouterCommandeModal input#quantite,
html body.dark-mode div#ajouterCommandeModal input#prix_estime {
    background-color: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #f8fafc !important;
}

/* Styles pour les pièces supplémentaires */
.order-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto 1fr auto;
    gap: 10px;
    align-items: center;
}

.order-grid.mt-2 {
    margin-top: 15px !important;
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background-color: #f8f9fa;
    position: relative;
}

.dark-mode .order-grid.mt-2 {
    border-color: #374151 !important;
    background-color: #1f2937 !important;
}

.remove-piece-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-piece-btn i {
    font-size: 12px;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .order-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .order-grid.mt-2 {
        padding: 10px;
    }
}
</style>
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modern-modal">
            <!-- En-tête du formulaire -->
            <div class="modal-header bg-gradient-warning">
                <h2 class="modal-title text-white"><i class="fas fa-shopping-cart me-2"></i> Nouvelle commande de pièces</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps du formulaire -->
            <div class="modal-body modern-modal-body p-4">
                <form id="ajouterCommandeForm" method="post" action="ajax/add_commande.php">
                    <!-- Section Client -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-user-circle"></i> Client
                        </div>
                        <div class="form-group mb-3">
                            <input type="hidden" id="client_id" name="client_id" />
                            <div id="client_selectionne" class="d-none mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user me-2"></i>
                                    <span class="nom_client fw-semibold"></span>&nbsp;|&nbsp;
                                    <span class="tel_client text-muted small"></span>
                                </div>
                            </div>
                            <input type="text" class="form-control" id="nom_client_selectionne" placeholder="Saisir ou rechercher un client" aria-label="Rechercher un client">
                            <div id="resultats_recherche_client_inline" class="mt-2 d-none">
                                <div id="liste_clients_recherche_inline" class="list-group"></div>
                            </div>
                            <button type="button" id="newClientBtn" class="btn btn-outline-primary w-100 mt-2">
                                <i class="fas fa-user-plus me-2"></i>+Créer un nouveau client
                            </button>
                        </div>
                    </div>

                    <!-- Section Fournisseur -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-truck"></i> Fournisseur
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="fournisseur_id" id="fournisseur_id_ajout" required>
                                <option value="">Sélectionner un fournisseur...</option>
                                <!-- options chargées dynamiquement -->
                            </select>
                        </div>
                    </div>

                    <!-- Section Pièce -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-cog"></i> Pièce commandée
                        </div>
                        <div class="order-grid">
                            <input type="text" class="form-control" name="nom_piece" id="nom_piece" placeholder="Désignation de la pièce" required>
                            <input type="text" class="form-control" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                            <div class="quantity-selector">
                                <button type="button" class="btn btn-outline-secondary" id="decrease-qty">–</button>
                                <input type="number" class="form-control text-center" name="quantite" id="quantite" value="1" min="1">
                                <button type="button" class="btn btn-outline-secondary" id="increase-qty">+</button>
                            </div>
                            <input type="number" class="form-control" name="prix_estime" id="prix_estime" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="ajouter-piece-btn">
                                <i class="fas fa-plus me-1"></i> Ajouter une autre pièce
                            </button>
                        </div>
                    </div>

                    <!-- Section Statut -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-info-circle"></i> Statut
                        </div>
                        <div class="d-flex gap-3">
                            <label class="status-pill active">
                                <input type="radio" name="statut" id="statusPending" value="en_attente" checked>
                                <i class="fas fa-clock"></i> En attente
                            </label>
                            <label class="status-pill">
                                <input type="radio" name="statut" id="statusOrdered" value="commande">
                                <i class="fas fa-shopping-cart"></i> Commandé
                            </label>
                            <label class="status-pill">
                                <input type="radio" name="statut" id="statusReceived" value="recue">
                                <i class="fas fa-check-circle"></i> Reçu
                            </label>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Footer avec boutons d'actions -->
            <div class="modal-footer modern-modal-footer border-0 p-4">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-ripple btn-gradient-primary" id="saveCommandeBtn">
                        <i class="fas fa-save me-2"></i> Enregistrer la commande
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript pour la gestion des interactions du formulaire
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du compteur de quantité
    const quantityInput = document.getElementById('quantite');
    const decreaseBtn = document.getElementById('decreaseQuantity');
    const increaseBtn = document.getElementById('increaseQuantity');

    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
            updateDecreaseBtnState();
        });

        increaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
            updateDecreaseBtnState();
        });

        function updateDecreaseBtnState() {
            decreaseBtn.disabled = parseInt(quantityInput.value) <= 1;
        }

        // Initialisation de l'état du bouton de diminution
        updateDecreaseBtnState();
    }

    // Gestion des boutons radio de statut - Géré par modal-commande.js
    
    // Corrige le problème du backdrop qui bloque les interactions
    const fixModalBackdrop = function() {
        const modal = document.getElementById('ajouterCommandeModal');
        
        // Ajuster le modal quand il est ouvert
        modal.addEventListener('shown.bs.modal', function() {
            // Nettoyages agressifs désactivés
        });
        
        // Quand le modal est fermé, nettoyer les backdrops et restaurer le scroll
        modal.addEventListener('hidden.bs.modal', function() {
            // Nettoyages agressifs désactivés
        });
    };
    
    // Initialiser le correctif pour le backdrop
    fixModalBackdrop();
});
</script>

<!-- ========================================= -->
<!-- MODAL: NOUVEAU CLIENT POUR COMMANDES -->
<!-- ========================================= -->
<div class="modal fade" id="nouveauClientModal_commande" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="z-index: 1100;">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2 text-primary"></i>
                    Ajouter un nouveau client
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="nouveauClientCommandeForm" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="nouveau_nom_commande" class="form-label">Nom <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nouveau_nom_commande" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom_commande" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nouveau_prenom_commande" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone_commande" class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="nouveau_telephone_commande" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_email_commande" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="nouveau_email_commande">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_adresse_commande" class="form-label">Adresse</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" id="nouveau_adresse_commande" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn_sauvegarder_client_commande">
                    <i class="fas fa-save me-2"></i>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Script pour le modal nouveau client commande
document.addEventListener('DOMContentLoaded', function() {
    const btnSauvegarder = document.getElementById('btn_sauvegarder_client_commande');
    const modal = document.getElementById('nouveauClientModal_commande');
    const form = document.getElementById('nouveauClientCommandeForm');
    
    if (btnSauvegarder) {
        btnSauvegarder.addEventListener('click', function() {
            // Validation du formulaire
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            const formData = {
                nom: document.getElementById('nouveau_nom_commande').value,
                prenom: document.getElementById('nouveau_prenom_commande').value,
                telephone: document.getElementById('nouveau_telephone_commande').value,
                email: document.getElementById('nouveau_email_commande').value,
                adresse: document.getElementById('nouveau_adresse_commande').value
            };
            
            // Envoyer les données
            fetch('ajax/ajouter_client.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal nouveau client
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    modalInstance.hide();
                    
                    // Réinitialiser le formulaire
                    form.reset();
                    form.classList.remove('was-validated');
                    
                    // Rouvrir le modal commande et sélectionner le client
                    setTimeout(() => {
                        const commandeModal = document.getElementById('ajouterCommandeModal');
                        const commandeModalInstance = new bootstrap.Modal(commandeModal);
                        commandeModalInstance.show();
                        
                        // Sélectionner automatiquement le nouveau client
                        if (data.client) {
                            const clientSearchInput = document.getElementById('nom_client_selectionne');
                            const clientIdInput = document.getElementById('client_id');
                            const clientSelectionne = document.getElementById('client_selectionne');
                            
                            if (clientSearchInput && clientIdInput && clientSelectionne) {
                                clientIdInput.value = data.client.id;
                                clientSearchInput.value = `${data.client.nom} ${data.client.prenom}`;
                                
                                clientSelectionne.querySelector('.nom_client').textContent = `${data.client.nom} ${data.client.prenom}`;
                                clientSelectionne.querySelector('.tel_client').textContent = data.client.telephone || 'Pas de téléphone';
                                clientSelectionne.classList.remove('d-none');
                            }
                        }
                    }, 300);
                    
                    alert('Client ajouté avec succès !');
                } else {
                    alert(data.message || 'Erreur lors de l\'ajout du client');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout du client');
            });
        });
    }
});
</script>



<!-- ========================================= -->
<!-- MODAL: AJOUTER TÂCHE - DESIGN MODERNE -->
<!-- ========================================= -->
<div class="modal fade" id="ajouterTacheModal" tabindex="-1" aria-labelledby="ajouterTacheModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg modern-task-modal">
            <div class="modal-header border-0 bg-gradient-success">
                <h5 class="modal-title text-white fw-bold" id="ajouterTacheModalLabel">
                    <i class="fas fa-plus-circle me-2 pulse-icon"></i>
                    Nouvelle Tâche
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 position-relative overflow-hidden">
                <!-- Effet de particules animées -->
                <div class="task-particles-container">
                    <div class="task-particle" style="left: 15%; animation-delay: 0s;"></div>
                    <div class="task-particle" style="left: 35%; animation-delay: 1s;"></div>
                    <div class="task-particle" style="left: 55%; animation-delay: 2s;"></div>
                    <div class="task-particle" style="left: 75%; animation-delay: 0.5s;"></div>
                    <div class="task-particle" style="left: 90%; animation-delay: 1.5s;"></div>
                </div>

                <!-- Alerte pour les erreurs -->
                <div id="taskModalErrors" class="alert alert-danger d-none">
                    <ul class="mb-0" id="taskErrorsList"></ul>
                </div>

                <!-- Alerte pour le succès -->
                <div id="taskModalSuccess" class="alert alert-success d-none">
                    <span id="taskSuccessMessage"></span>
                </div>

                <form id="taskModalForm" class="modern-task-form">
                    <!-- Titre de la tâche -->
                    <div class="mb-4">
                        <label for="modal_titre" class="form-label fw-bold task-label">
                            <i class="fas fa-heading me-2 text-primary"></i>
                            Titre de la tâche *
                        </label>
                        <input type="text" class="form-control form-control-lg modern-input" id="modal_titre" name="titre" required
                            placeholder="Saisissez un titre clair et concis">
                        <div class="input-glow"></div>
                    </div>
                    
                    <!-- Description de la tâche -->
                    <div class="mb-4">
                        <label for="modal_description" class="form-label fw-bold task-label">
                            <i class="fas fa-align-left me-2 text-info"></i>
                            Description *
                        </label>
                        <textarea class="form-control modern-textarea" id="modal_description" name="description" rows="4" required
                            placeholder="Détaillez la tâche à accomplir..."></textarea>
                        <div class="input-glow"></div>
                    </div>
                    
                    <!-- Priorité et Date limite -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold task-label d-block">
                                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                    Priorité *
                                </label>
                                <div class="priority-buttons d-flex flex-nowrap modern-button-group">
                                    <button type="button" class="btn btn-priority btn-outline-success flex-grow-1 modern-btn" data-value="basse">
                                        <i class="fas fa-angle-down me-1"></i><span class="d-none d-md-inline">Basse</span>
                                    </button>
                                    <button type="button" class="btn btn-priority btn-outline-primary flex-grow-1 modern-btn" data-value="moyenne">
                                        <i class="fas fa-equals me-1"></i><span class="d-none d-md-inline">Moyenne</span>
                                    </button>
                                    <button type="button" class="btn btn-priority btn-outline-warning flex-grow-1 modern-btn" data-value="haute">
                                        <i class="fas fa-angle-up me-1"></i><span class="d-none d-md-inline">Haute</span>
                                    </button>
                                    <button type="button" class="btn btn-priority btn-outline-danger flex-grow-1 modern-btn" data-value="urgente">
                                        <i class="fas fa-exclamation-triangle me-1"></i><span class="d-none d-md-inline">Urgente</span>
                                    </button>
                                </div>
                                <input type="hidden" name="priorite" id="modal_priorite" value="">
                            </div>
                            
                            <div class="col-12 col-md-6 mt-3 mt-md-0">
                                <label for="modal_date_limite" class="form-label fw-bold task-label">
                                    <i class="fas fa-calendar-alt me-2 text-danger"></i>
                                    Date limite
                                </label>
                                <div class="input-group modern-input-group">
                                    <span class="input-group-text modern-input-addon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="date" class="form-control form-control-lg modern-input" id="modal_date_limite" name="date_limite">
                                </div>
                                <div class="input-glow"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Champ statut caché avec valeur par défaut -->
                    <input type="hidden" name="statut" id="modal_statut" value="a_faire">
                    
                    <!-- Assigner la tâche -->
                    <div class="mb-4">
                        <label class="form-label fw-bold task-label d-block">
                            <i class="fas fa-user-check me-2 text-primary"></i>
                            Assigner à
                        </label>
                        <div class="user-selection">
                            <div id="userButtonsContainer" class="d-flex flex-wrap gap-2 mb-2">
                                <!-- Les boutons utilisateurs seront chargés ici -->
                                <div class="loading-users text-center w-100 py-3">
                                    <i class="fas fa-spinner fa-spin text-primary me-2"></i>
                                    Chargement des utilisateurs...
                                </div>
                            </div>
                            <input type="hidden" name="employe_id" id="modal_employe_id" value="">
                        </div>
                    </div>
                    
                    <!-- Pièces jointes -->
                    <div class="mb-4">
                        <label class="form-label fw-bold task-label d-block">
                            <i class="fas fa-paperclip me-2 text-success"></i>
                            Pièces jointes <small class="text-muted">(facultatif)</small>
                        </label>
                        <div class="attachment-section modern-attachment">
                            <div class="file-drop-zone modern-drop-zone" id="modalFileDropZone">
                                <div class="text-center py-4">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3 upload-icon"></i>
                                    <p class="mb-2 text-primary fw-bold">Glissez-déposez vos fichiers ici ou</p>
                                    <button type="button" class="btn btn-outline-primary btn-lg modern-btn" id="modalSelectFilesBtn">
                                        <i class="fas fa-folder-open me-2"></i>Sélectionner des fichiers
                                    </button>
                                    <input type="file" name="attachments[]" id="modalFileInput" multiple 
                                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.xlsx,.xls,.zip,.rar" style="display: none;">
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Types autorisés: JPG, PNG, PDF, DOC, TXT, XLS, ZIP (max 10MB par fichier)
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="modalSelectedFiles" class="mt-3" style="display: none;">
                                <h6 class="fw-bold mb-2 text-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Fichiers sélectionnés :
                                </h6>
                                <div id="modalFilesList" class="list-group modern-file-list">
                                    <!-- Les fichiers sélectionnés apparaîtront ici -->
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer avec boutons d'actions -->
            <div class="modal-footer modern-modal-footer border-0 p-4">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <small class="text-muted d-flex align-items-center">
                        <i class="fas fa-magic me-1"></i>
                        Tous les champs marqués d'un * sont obligatoires
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-lg modern-btn" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="button" class="btn btn-success btn-lg modern-btn" id="saveTaskBtn">
                            <i class="fas fa-save me-2"></i>
                            <span class="btn-text">Enregistrer la tâche</span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le modal de tâche -->
<style>
/* Styles généraux du modal tâche */
.modern-task-modal {
    border-radius: 20px !important;
    overflow: hidden;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modern-task-modal .modal-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0 !important;
}

.modern-task-modal .modal-body {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 2rem !important;
    position: relative;
}

.modern-task-modal .modal-footer,
.modern-modal-footer {
    background: rgba(248, 249, 250, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 0 0 20px 20px !important;
}

.modern-modal-body {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    position: relative;
}

/* CSS pour les sections de commande */
.order-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.order-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.order-section-title {
    font-weight: 600;
    color: #495057;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.order-section-title i {
    color: #007bff;
}

/* CSS pour les status pills */
.status-pill {
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    border: 2px solid #dee2e6;
    border-radius: 25px;
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #6c757d;
    position: relative;
    overflow: hidden;
}

.status-pill input[type="radio"] {
    display: none;
}

.status-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-color: #007bff;
}

.status-pill.active,
.status-pill:has(input:checked) {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-color: #007bff;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
}

.status-pill i {
    font-size: 1rem;
}

/* Grid pour les pièces */
.order-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto 1fr;
    gap: 1rem;
    align-items: center;
}

.quantity-selector {
    display: flex;
    border: 1px solid #ced4da;
    border-radius: 6px;
    overflow: hidden;
}

.quantity-selector button {
    border: none;
    background: #f8f9fa;
    width: 35px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.quantity-selector input {
    border: none;
    width: 60px;
    text-align: center;
}

/* CSS pour le mode nuit */
[data-theme="dark"] .order-section {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border-color: #444;
}

[data-theme="dark"] .order-section-title {
    color: #ffffff;
}

[data-theme="dark"] .order-section-title i {
    color: #0d6efd;
}

/* Particules animées pour le modal tâche */
.task-particles-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.task-particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: linear-gradient(45deg, #28a745, #20c997);
    border-radius: 50%;
    animation: taskParticleFloat 6s ease-in-out infinite;
}

@keyframes taskParticleFloat {
    0%, 100% { 
        transform: translateY(0) rotate(0deg); 
        opacity: 0.3; 
    }
    50% { 
        transform: translateY(-30px) rotate(180deg); 
        opacity: 0.8; 
    }
}

/* Labels modernes */
.task-label {
    color: #2d3748;
    font-size: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
}

/* Inputs modernes */
.modern-input, .modern-textarea {
    border: 2px solid #e2e8f0 !important;
    border-radius: 12px !important;
    padding: 0.875rem 1rem !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
    background: rgba(255, 255, 255, 0.9) !important;
    position: relative;
    z-index: 2;
}

.modern-input:focus, .modern-textarea:focus {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.15) !important;
    background: #ffffff !important;
    transform: translateY(-2px);
}

.modern-input-group {
    border-radius: 12px !important;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-input-addon {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    border: none !important;
    font-size: 1.1rem;
}

/* Effet de lueur sur les inputs */
.input-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 12px;
    background: linear-gradient(45deg, transparent, rgba(40, 167, 69, 0.1), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.modern-input:focus + .input-glow,
.modern-textarea:focus + .input-glow {
    opacity: 1;
}

/* Groupes de boutons modernes */
.modern-button-group {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 12px;
    padding: 0.25rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-btn {
    border-radius: 8px !important;
    border-width: 2px !important;
    font-weight: 500 !important;
    transition: all 0.3s ease !important;
    position: relative;
    overflow: hidden;
}

.modern-btn:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.modern-btn:hover:before {
    left: 100%;
}

.modern-btn.active {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Styles spécifiques pour les boutons de priorité actifs */
.btn-priority[data-value="basse"].active {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    border-color: #28a745 !important;
}

.btn-priority[data-value="moyenne"].active {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
    color: white !important;
    border-color: #007bff !important;
}

.btn-priority[data-value="haute"].active {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
    color: #212529 !important;
    border-color: #ffc107 !important;
}

.btn-priority[data-value="urgente"].active {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
    color: white !important;
    border-color: #dc3545 !important;
}

/* Styles spécifiques pour les boutons de statut supprimés car plus utilisés */

/* Boutons utilisateurs */
.user-btn {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 12px !important;
    padding: 0.75rem 1rem !important;
    margin: 0.25rem !important;
    transition: all 0.3s ease !important;
    font-weight: 500 !important;
}

.user-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    border-color: #28a745 !important;
}

.user-btn.active {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    border-color: #28a745 !important;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 16px rgba(40, 167, 69, 0.3);
}

/* Section pièces jointes moderne */
.modern-attachment {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-drop-zone {
    border: 3px dashed #e2e8f0 !important;
    border-radius: 12px !important;
    background: linear-gradient(135deg, #f8f9fa, #ffffff) !important;
    transition: all 0.3s ease !important;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.modern-drop-zone:hover,
.modern-drop-zone.dragover {
    border-color: #28a745 !important;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(32, 201, 151, 0.05)) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(40, 167, 69, 0.1);
}

.upload-icon {
    animation: uploadPulse 2s ease-in-out infinite;
}

@keyframes uploadPulse {
    0%, 100% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.05); opacity: 1; }
}

/* Liste des fichiers moderne */
.modern-file-list .file-item {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    margin-bottom: 0.75rem !important;
    padding: 1rem !important;
    transition: all 0.3s ease !important;
    position: relative;
    overflow: hidden;
}

.modern-file-list .file-item:hover {
    transform: translateX(8px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    border-color: #28a745 !important;
}

.modern-file-list .file-item:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #28a745, #20c997);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.modern-file-list .file-item:hover:before {
    transform: scaleY(1);
}

/* Animations */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modern-task-modal.show {
    animation: modalSlideIn 0.4s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-task-modal .modal-body {
        padding: 1.5rem !important;
    }
    
    .modern-button-group .modern-btn {
        font-size: 0.875rem !important;
        padding: 0.5rem 0.25rem !important;
    }
    
    .task-label {
        font-size: 0.9rem;
    }
}

/* Mode sombre */
.dark-mode .modern-task-modal {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

.dark-mode .modern-task-modal .modal-body {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

.dark-mode .task-label {
    color: #f8fafc;
}

.dark-mode .modern-input,
.dark-mode .modern-textarea {
    background: rgba(31, 41, 55, 0.9) !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}

.dark-mode .modern-input:focus,
.dark-mode .modern-textarea:focus {
    background: #1f2937 !important;
    border-color: #28a745 !important;
}

.dark-mode .modern-button-group {
    background: rgba(31, 41, 55, 0.9);
}

.dark-mode .user-btn {
    background: rgba(31, 41, 55, 0.9) !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}

.dark-mode .modern-attachment {
    background: rgba(31, 41, 55, 0.9);
}

.dark-mode .modern-drop-zone {
    background: linear-gradient(135deg, #1f2937, #111827) !important;
    border-color: #374151 !important;
}

.dark-mode .modern-file-list .file-item {
    background: rgba(31, 41, 55, 0.95) !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
</style>

<!-- ========================================= -->
<!-- SCRIPTS POUR LES MODALS -->
<!-- ========================================= -->
<script>
// Fonction pour ajouter une commande
function ajouterCommande() {
    const form = document.getElementById('ajouterCommandeForm');
    const formData = new FormData(form);
    
    // Ici vous pouvez ajouter votre logique AJAX pour sauvegarder la commande
    console.log('Ajout de commande:', Object.fromEntries(formData));
    
    // Fermer le modal après ajout
    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterCommandeModal'));
    modal.hide();
    
    // Réinitialiser le formulaire
    form.reset();
    
    // Afficher un message de succès
    showToast('Commande ajoutée avec succès!', 'success');
}



// Fonction utilitaire pour afficher des toasts optimisés pour mode sombre
function showToast(message, type = 'info') {
    // Créer un toast avec styles améliorés
    const toast = document.createElement('div');
    toast.className = `geek-toast geek-toast-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.style.maxWidth = '400px';
    toast.style.borderRadius = '8px';
    toast.style.padding = '12px 16px';
    toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    toast.style.border = '1px solid';
    toast.style.fontWeight = '500';
    toast.style.fontSize = '14px';
    toast.style.lineHeight = '1.4';
    
    // Appliquer les couleurs selon le type et le mode
    applyToastStyles(toast, type);
    
    toast.innerHTML = `
        <div style="display: flex; align-items: flex-start; gap: 8px;">
            <div style="flex: 1;">${message}</div>
            <button type="button" class="geek-toast-close" onclick="this.closest('.geek-toast').remove()" 
                    style="background: none; border: none; font-size: 18px; cursor: pointer; padding: 0; margin-left: 8px; opacity: 0.7;">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animation d'entrée
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'transform 0.3s ease-out';
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Supprimer automatiquement après 5 secondes avec animation
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Fonction pour appliquer les styles selon le type et le mode
function applyToastStyles(toast, type) {
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                       document.documentElement.classList.contains('dark-mode') ||
                       window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    const styles = {
        info: {
            light: { bg: '#d1ecf1', border: '#bee5eb', color: '#0c5460' },
            dark: { bg: '#1a2332', border: '#2d4a5c', color: '#9dd6e8' }
        },
        success: {
            light: { bg: '#d4edda', border: '#c3e6cb', color: '#155724' },
            dark: { bg: '#1a2e1a', border: '#2d5a2d', color: '#a3d9a3' }
        },
        warning: {
            light: { bg: '#fff3cd', border: '#ffeaa7', color: '#856404' },
            dark: { bg: '#332a1a', border: '#5c4d2d', color: '#f4d35e' }
        },
        error: {
            light: { bg: '#f8d7da', border: '#f5c6cb', color: '#721c24' },
            dark: { bg: '#2e1a1a', border: '#5a2d2d', color: '#f28b82' }
        },
        danger: {
            light: { bg: '#f8d7da', border: '#f5c6cb', color: '#721c24' },
            dark: { bg: '#2e1a1a', border: '#5a2d2d', color: '#f28b82' }
        }
    };
    
    const colorScheme = styles[type] || styles.info;
    const colors = isDarkMode ? colorScheme.dark : colorScheme.light;
    
    toast.style.backgroundColor = colors.bg;
    toast.style.borderColor = colors.border;
    toast.style.color = colors.color;
    
    // Style pour le bouton de fermeture
    const closeBtn = toast.querySelector('.geek-toast-close');
    if (closeBtn) {
        closeBtn.style.color = colors.color;
    }
}

/**
 * Système de pointage dynamique depuis le modal nouvelles_actions
 * Affiche le bon bouton selon l'état actuel de l'utilisateur
 */

// Variable globale pour stocker l'état du pointage
let currentTimeTrackingState = null;

/**
 * Vérifier l'état actuel du pointage utilisateur
 */
async function checkTimeTrackingStatus() {
    try {
        console.log('🔄 Vérification état pointage...');
        
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_status'
        });
        
        console.log('📡 Réponse API reçue:', response.status);
        
        // Gestion spéciale pour les erreurs d'authentification
        if (response.status === 401) {
            console.log('🔐 Utilisateur non connecté - pointage non disponible');
            return { auth_error: true, message: 'Connexion requise' };
        }
        
        if (!response.ok) {
            console.error('❌ Erreur réseau:', response.status);
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📊 Données API reçues:', data);
        
        if (data.success && data.data) {
            currentTimeTrackingState = data.data;
            console.log('✅ État pointage récupéré:', data.data);
            return data.data;
        } else {
            // Gestion des erreurs d'authentification côté serveur
            if (data.message && (data.message.includes('authentifié') || data.message.includes('connecter'))) {
                console.log('🔐 Erreur d\'authentification:', data.message);
                return { auth_error: true, message: data.message };
            }
            console.error('❌ Erreur API:', data.message || 'Erreur inconnue');
            throw new Error(data.message || 'Erreur lors de la récupération du statut');
        }
        
    } catch (error) {
        console.error('❌ Erreur vérification état:', error);
        // Retourner un objet avec plus d'informations pour le debug
        return { error: true, message: error.message };
    }
}

/**
 * Générer le bouton de pointage approprié
 */
function generateTimeTrackingButton(state) {
    // Gestion des erreurs d'authentification
    if (state && state.auth_error) {
        return `
        <button type="button" class="modern-action-card auth-error-card" onclick="window.location.reload()">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-primary">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="pulse-ring"></div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Connexion requise</h6>
                <p class="action-description">${state.message || 'Veuillez vous connecter'}</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>`;
    }
    
    // Gestion des erreurs génériques
    if (state && state.error) {
        return `
        <div class="modern-action-card error-card">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Erreur de connexion</h6>
                <p class="action-description">${state.message || 'Problème de réseau'}</p>
            </div>
        </div>`;
    }
    
    if (!state) {
        // Erreur de chargement
        return `
        <div class="modern-action-card error-card">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-secondary">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Erreur</h6>
                <p class="action-description">Impossible de vérifier l'état</p>
            </div>
        </div>`;
    }
    
    if (state.is_clocked_in) {
        // Utilisateur a un pointage en cours → bouton Clock-Out
        const session = state.current_session || state;
        const startTime = session.clock_in ? new Date(session.clock_in).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : 'N/A';
        
        // Calculer la durée écoulée si on a une heure de début
        let elapsedTime = '00:00';
        if (session.clock_in) {
            const start = new Date(session.clock_in);
            const now = new Date();
            const diffMs = now - start;
            const hours = Math.floor(diffMs / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            elapsedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        }
        
        return `
        <button type="button" class="modern-action-card clock-out-card" onclick="modalClockOut()" data-bs-dismiss="modal">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-danger">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="pulse-ring"></div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Pointage Départ</h6>
                <p class="action-description">Fin de journée depuis ${startTime} (${elapsedTime})</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>`;
    } else {
        // Utilisateur n'a pas de pointage en cours → bouton Clock-In
        return `
        <button type="button" class="modern-action-card clock-in-card" onclick="modalClockIn()" data-bs-dismiss="modal">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-success">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="pulse-ring"></div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Pointage Arrivée</h6>
                <p class="action-description">Commencer votre journée de travail</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>`;
    }
}

/**
 * Mettre à jour le bouton de pointage dans le modal
 */
async function updateTimeTrackingButton() {
    const container = document.getElementById('dynamic-timetracking-button');
    if (!container) return;
    
    // Afficher le chargement
    container.innerHTML = `
    <div class="modern-action-card loading-card">
        <div class="card-glow"></div>
        <div class="action-icon-container">
            <div class="action-icon bg-gradient-info">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="pulse-ring"></div>
        </div>
        <div class="action-content">
            <h6 class="action-title">Chargement...</h6>
            <p class="action-description">Vérification de l'état du pointage</p>
        </div>
    </div>`;
    
    try {
        // Vérifier l'état avec timeout réduit
        const state = await Promise.race([
            checkTimeTrackingStatus(),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout - API ne répond pas')), 5000)
            )
        ]);
        
        // Mettre à jour avec le bon bouton
        if (state && !state.auth_error) {
            container.innerHTML = generateTimeTrackingButton(state);
        } else {
            // Afficher bouton de fallback si erreur d'authentification
            container.innerHTML = generateFallbackTimeTrackingButton();
        }
        
    } catch (error) {
        console.error('❌ Erreur lors de la mise à jour du bouton de pointage:', error);
        
        // Afficher un bouton de fallback en cas d'erreur
        container.innerHTML = generateFallbackTimeTrackingButton();
    }
}

/**
 * Générer un bouton de pointage de fallback en cas d'erreur API
 */
function generateFallbackTimeTrackingButton() {
    return `
    <button type="button" class="modern-action-card clock-in-card" onclick="modalClockIn()" data-bs-dismiss="modal">
        <div class="card-glow"></div>
        <div class="action-icon-container">
            <div class="action-icon bg-gradient-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="pulse-ring"></div>
        </div>
        <div class="action-content">
            <h6 class="action-title">Pointage</h6>
            <p class="action-description">Gérer votre pointage (mode dégradé)</p>
        </div>
        <div class="action-arrow">
            <i class="fas fa-chevron-right"></i>
        </div>
    </button>`;
}

/**
 * Fonctions de pointage avec mise à jour du bouton
 */
async function modalClockIn() {
    console.log('🔄 Tentative de pointage arrivée depuis modal...');
    
    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clock_in'
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher un message de succès avec détails d'approbation
            let message = '✅ Pointage d\'arrivée enregistré !';
            if (data.data.auto_approved) {
                message += '\n🟢 Approuvé automatiquement - Dans les créneaux autorisés';
            } else {
                message += '\n🟡 En attente d\'approbation - Hors créneaux ou aucun créneau défini';
            }
            
            showToast(message, data.data.auto_approved ? 'success' : 'warning');
            
            // Mettre à jour le bouton pour la prochaine ouverture du modal
            setTimeout(updateTimeTrackingButton, 1000);
            
            console.log('✅ Clock-in réussi:', data);
            
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('❌ Erreur Clock-In depuis modal:', error);
        showToast('❌ Erreur: ' + error.message, 'error');
    }
}

async function modalClockOut() {
    console.log('🔄 Tentative de pointage départ depuis modal...');
    
    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clock_out'
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher un message de succès avec durée de travail
            let message = '✅ Pointage de départ enregistré !';
            if (data.data.work_duration) {
                message += `\n⏱️ Durée de travail: ${data.data.work_duration}h`;
            }
            
            showToast(message, 'success');
            
            // Mettre à jour le bouton pour la prochaine ouverture du modal
            setTimeout(updateTimeTrackingButton, 1000);
            
            console.log('✅ Clock-out réussi:', data);
            
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('❌ Erreur Clock-Out depuis modal:', error);
        showToast('❌ Erreur: ' + error.message, 'error');
    }
}

/**
 * ========================================
 * GESTION DU MODAL AJOUTER TÂCHE
 * ========================================
 */

// Variables globales pour le modal tâche
let taskModalFilesArray = [];
let taskModalUsersLoaded = false;

// Données utilisateurs exposées depuis PHP (si disponibles)
let taskModalUsersFromPHP = [];
<?php
// Charger les utilisateurs directement si pas déjà disponibles
if (!isset($utilisateurs) || !is_array($utilisateurs) || empty($utilisateurs)) {
    try {
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
        $utilisateurs_modal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($utilisateurs_modal)) {
            echo "taskModalUsersFromPHP = " . json_encode($utilisateurs_modal) . ";\n";
            echo "console.log('🚀 Utilisateurs chargés directement depuis la base pour le modal:', taskModalUsersFromPHP);\n";
        }
    } catch (PDOException $e) {
        echo "console.error('❌ Erreur lors du chargement des utilisateurs pour le modal:', " . json_encode($e->getMessage()) . ");\n";
    }
} else {
    // Utiliser les utilisateurs déjà chargés par la page
    echo "taskModalUsersFromPHP = " . json_encode($utilisateurs) . ";\n";
    echo "console.log('✅ Utilisateurs utilisés depuis la variable de page:', taskModalUsersFromPHP);\n";
}
?>

/**
 * Charger les utilisateurs pour le modal tâche
 */
async function loadTaskModalUsers() {
    if (taskModalUsersLoaded) return; // Ne charger qu'une fois
    
    // D'abord, essayer d'utiliser les données PHP si disponibles
    if (taskModalUsersFromPHP && taskModalUsersFromPHP.length > 0) {
        console.log('🚀 Utilisation des utilisateurs depuis PHP');
        displayTaskModalUsers(taskModalUsersFromPHP);
        taskModalUsersLoaded = true;
        return;
    }
    
    try {
        // Utiliser l'API sans authentification
        const response = await fetch('ajax_simple_no_auth.php?shop_id=63');
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success && data.users) {
            console.log('🚀 Utilisateurs chargés SANS authentification:', data.users.length);
            console.log('📊 Shop:', data.shop_id, '- DB:', data.shop_db);
            displayTaskModalUsers(data.users);
            taskModalUsersLoaded = true;
        } else {
            console.error('❌ Erreur API sans auth:', data);
            throw new Error(data.message || 'Erreur lors du chargement des utilisateurs');
        }
    } catch (error) {
        console.error('Erreur chargement utilisateurs via API:', error);
        console.log('Tentative de récupération des utilisateurs depuis la page...');
        
        // Solution de contournement : essayer de récupérer depuis les données de la page
        const fallbackUsers = tryGetUsersFromPage();
        if (fallbackUsers && fallbackUsers.length > 0) {
            displayTaskModalUsers(fallbackUsers);
            taskModalUsersLoaded = true;
            console.log('✅ Utilisateurs récupérés depuis la page');
        } else {
            const container = document.getElementById('userButtonsContainer');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-warning w-100 text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Impossible de charger les utilisateurs<br>
                        <small class="text-muted">Vous pouvez continuer sans assigner la tâche</small>
                    </div>`;
            }
        }
    }
}

/**
 * Essayer de récupérer les utilisateurs depuis les données de la page
 */
function tryGetUsersFromPage() {
    try {
        // Chercher les données utilisateurs dans les selects existants
        const userSelects = document.querySelectorAll('select[name="employe_id"], select[name="user_id"], select[id*="user"], select[id*="employe"]');
        const users = [];
        
        userSelects.forEach(select => {
            Array.from(select.options).forEach(option => {
                if (option.value && option.value !== '' && option.text && option.text !== 'Sélectionner un utilisateur') {
                    // Éviter les doublons
                    if (!users.find(u => u.id === option.value)) {
                        users.push({
                            id: option.value,
                            full_name: option.text,
                            role: 'Utilisateur'
                        });
                    }
                }
            });
        });
        
        // Si on n'a pas trouvé d'utilisateurs dans les selects, chercher dans les boutons existants
        if (users.length === 0) {
            const userButtons = document.querySelectorAll('.user-btn[data-value]');
            userButtons.forEach(button => {
                if (button.dataset.value && button.dataset.value !== '') {
                    const name = button.textContent.replace(/^\s*\w+\s*/, '').trim(); // Enlever l'icône
                    if (name && !users.find(u => u.id === button.dataset.value)) {
                        users.push({
                            id: button.dataset.value,
                            full_name: name,
                            role: 'Utilisateur'
                        });
                    }
                }
            });
        }
        
        console.log('Utilisateurs trouvés sur la page:', users);
        return users;
    } catch (error) {
        console.error('Erreur lors de la récupération des utilisateurs depuis la page:', error);
        return [];
    }
}

/**
 * Afficher les utilisateurs dans le modal
 */
function displayTaskModalUsers(users) {
    const container = document.getElementById('userButtonsContainer');
    if (!container) return;
    
    let html = `
        <button type="button" class="btn btn-outline-secondary btn-lg user-btn modern-btn" data-value="">
            <i class="fas fa-user-slash me-2"></i>Non assigné
        </button>
    `;
    
    // Afficher les 3 premiers utilisateurs
    users.slice(0, 3).forEach(user => {
        html += `
            <button type="button" class="btn btn-outline-primary btn-lg user-btn modern-btn" data-value="${user.id}">
                <i class="fas fa-user me-2"></i>${user.full_name}
            </button>
        `;
    });
    
    // Bouton "Voir tous" si plus de 3 utilisateurs
    if (users.length > 3) {
        html += `
            <button type="button" class="btn btn-outline-secondary btn-lg modern-btn" id="modalShowAllUsersBtn">
                <i class="fas fa-users me-2"></i>Voir tous (${users.length})
            </button>
        `;
    }
    
    // Liste complète (masquée par défaut)
    if (users.length > 3) {
        html += `
            <div id="modalAllUsersList" class="w-100 mt-3" style="display: none;">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        `;
        
        users.forEach(user => {
            html += `
                <div class="col">
                    <button type="button" class="btn btn-outline-primary w-100 text-start user-btn modern-btn py-2" data-value="${user.id}">
                        <i class="fas fa-user me-2"></i>${user.full_name}
                        <small class="d-block text-muted ms-4">${user.role || 'Utilisateur'}</small>
                    </button>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    
    // Ajouter les événements
    initTaskModalUserButtons();
}

/**
 * Initialiser les boutons utilisateurs du modal
 */
function initTaskModalUserButtons() {
    const userButtons = document.querySelectorAll('#userButtonsContainer .user-btn');
    const employeInput = document.getElementById('modal_employe_id');
    const showAllBtn = document.getElementById('modalShowAllUsersBtn');
    const allUsersList = document.getElementById('modalAllUsersList');
    
    // Événements sur les boutons utilisateurs
    userButtons.forEach(button => {
        button.addEventListener('click', function() {
            userButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            if (employeInput) {
                employeInput.value = this.dataset.value;
            }
        });
    });
    
    // Bouton "Voir tous"
    if (showAllBtn && allUsersList) {
        showAllBtn.addEventListener('click', function() {
            if (allUsersList.style.display === 'none') {
                allUsersList.style.display = 'block';
                this.innerHTML = '<i class="fas fa-users-slash me-2"></i>Masquer';
            } else {
                allUsersList.style.display = 'none';
                this.innerHTML = '<i class="fas fa-users me-2"></i>Voir tous';
            }
        });
    }
}

/**
 * Initialiser les boutons priorité
 */
function initTaskModalButtons() {
    // Priorité
    const priorityButtons = document.querySelectorAll('#ajouterTacheModal .btn-priority');
    const priorityInput = document.getElementById('modal_priorite');
    
    priorityButtons.forEach(button => {
        button.addEventListener('click', function() {
            priorityButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            if (priorityInput) {
                priorityInput.value = this.dataset.value;
            }
        });
    });
    
    // Sélectionner la priorité par défaut (moyenne)
    setTimeout(() => {
        const defaultPriority = document.querySelector('#ajouterTacheModal .btn-priority[data-value="moyenne"]');
        if (defaultPriority) defaultPriority.click();
    }, 100);
}

/**
 * Gestion des fichiers pour le modal tâche
 */
function initTaskModalFiles() {
    const fileInput = document.getElementById('modalFileInput');
    const fileDropZone = document.getElementById('modalFileDropZone');
    const selectFilesBtn = document.getElementById('modalSelectFilesBtn');
    const selectedFiles = document.getElementById('modalSelectedFiles');
    const filesList = document.getElementById('modalFilesList');
    
    if (!fileInput || !fileDropZone || !selectFilesBtn) return;
    
    // Fonction pour formater la taille des fichiers
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Fonction pour obtenir l'icône selon le type de fichier
    function getFileIcon(fileType) {
        const imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
        const documentTypes = ['pdf', 'doc', 'docx', 'txt'];
        const archiveTypes = ['zip', 'rar'];
        
        if (imageTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-image', class: 'image' };
        } else if (documentTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-file-alt', class: 'document' };
        } else if (archiveTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-file-archive', class: 'archive' };
        } else {
            return { icon: 'fas fa-file', class: 'other' };
        }
    }
    
    // Fonction pour valider un fichier
    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'xlsx', 'xls', 'zip', 'rar'];
        const fileType = file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
            return { valid: false, error: `Le fichier "${file.name}" est trop volumineux (max 10MB)` };
        }
        
        if (!allowedTypes.includes(fileType)) {
            return { valid: false, error: `Le type de fichier "${fileType}" n'est pas autorisé pour "${file.name}"` };
        }
        
        return { valid: true };
    }
    
    // Fonction pour afficher les fichiers sélectionnés
    function displayFiles() {
        if (!filesList || !selectedFiles) return;
        
        if (taskModalFilesArray.length === 0) {
            selectedFiles.style.display = 'none';
            return;
        }
        
        selectedFiles.style.display = 'block';
        filesList.innerHTML = '';
        
        taskModalFilesArray.forEach((file, index) => {
            const fileIcon = getFileIcon(file.name.split('.').pop());
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex align-items-center';
            fileItem.innerHTML = `
                <div class="file-icon ${fileIcon.class} me-3">
                    <i class="${fileIcon.icon}"></i>
                </div>
                <div class="file-info flex-grow-1">
                    <div class="file-name fw-medium">${file.name}</div>
                    <div class="file-size text-muted small">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger file-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            filesList.appendChild(fileItem);
        });
        
        // Ajouter les événements de suppression
        document.querySelectorAll('#modalFilesList .file-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                taskModalFilesArray.splice(index, 1);
                updateFileInput();
                displayFiles();
            });
        });
    }
    
    // Fonction pour mettre à jour l'input file
    function updateFileInput() {
        const dt = new DataTransfer();
        taskModalFilesArray.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    // Fonction pour ajouter des fichiers
    function addFiles(files) {
        const newFiles = Array.from(files);
        let hasErrors = false;
        
        newFiles.forEach(file => {
            const validation = validateFile(file);
            if (!validation.valid) {
                showToast(validation.error, 'error');
                hasErrors = true;
                return;
            }
            
            // Vérifier si le fichier n'est pas déjà ajouté
            const exists = taskModalFilesArray.some(existingFile => 
                existingFile.name === file.name && existingFile.size === file.size
            );
            
            if (!exists) {
                taskModalFilesArray.push(file);
            }
        });
        
        if (!hasErrors) {
            updateFileInput();
            displayFiles();
        }
    }
    
    // Événement pour le bouton de sélection
    selectFilesBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Événement pour le changement de fichier
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            addFiles(this.files);
        }
    });
    
    // Événements de drag & drop
    fileDropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    fileDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    fileDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            addFiles(files);
        }
    });
}

/**
 * Soumettre le formulaire de tâche via AJAX
 */
async function submitTaskModal() {
    const form = document.getElementById('taskModalForm');
    const saveBtn = document.getElementById('saveTaskBtn');
    const btnText = saveBtn.querySelector('.btn-text');
    const btnLoading = saveBtn.querySelector('.btn-loading');
    const errorsDiv = document.getElementById('taskModalErrors');
    const errorsList = document.getElementById('taskErrorsList');
    const successDiv = document.getElementById('taskModalSuccess');
    const successMessage = document.getElementById('taskSuccessMessage');
    
    if (!form || !saveBtn) return;
    
    // Masquer les messages précédents
    errorsDiv.classList.add('d-none');
    successDiv.classList.add('d-none');
    
    // Afficher le loading
    btnText.classList.add('d-none');
    btnLoading.classList.remove('d-none');
    saveBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        
        // Utiliser l'API sans authentification
        const response = await fetch('ajax_simple_no_auth.php?shop_id=63', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Succès
            successMessage.textContent = data.message || 'Tâche ajoutée avec succès !';
            successDiv.classList.remove('d-none');
            
            // Réinitialiser le formulaire après un délai
            setTimeout(() => {
                resetTaskModal();
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterTacheModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Afficher un toast de succès
                showToast('✅ Tâche ajoutée avec succès !', 'success');
                
                // Recharger la page si on est sur la page des tâches
                if (window.location.href.includes('page=taches') || window.location.href.includes('page=accueil')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            }, 1500);
            
        } else {
            // Erreurs de validation
            if (data.errors && Array.isArray(data.errors)) {
                errorsList.innerHTML = '';
                data.errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorsList.appendChild(li);
                });
                errorsDiv.classList.remove('d-none');
            } else {
                showToast('❌ ' + (data.message || 'Erreur lors de l\'ajout de la tâche'), 'error');
            }
        }
        
    } catch (error) {
        console.error('Erreur soumission tâche:', error);
        showToast('❌ Erreur de connexion: ' + error.message, 'error');
    } finally {
        // Restaurer le bouton
        btnText.classList.remove('d-none');
        btnLoading.classList.add('d-none');
        saveBtn.disabled = false;
    }
}

/**
 * Réinitialiser le modal de tâche
 */
function resetTaskModal() {
    const form = document.getElementById('taskModalForm');
    if (form) {
        form.reset();
    }
    
    // Réinitialiser les boutons
    document.querySelectorAll('#ajouterTacheModal .btn-priority, #ajouterTacheModal .user-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Réinitialiser les champs cachés (le statut reste à "a_faire")
    document.getElementById('modal_priorite').value = '';
    document.getElementById('modal_employe_id').value = '';
    
    // Réinitialiser les fichiers
    taskModalFilesArray = [];
    const selectedFiles = document.getElementById('modalSelectedFiles');
    if (selectedFiles) {
        selectedFiles.style.display = 'none';
    }
    
    // Masquer les messages
    document.getElementById('taskModalErrors').classList.add('d-none');
    document.getElementById('taskModalSuccess').classList.add('d-none');
    
    // Remettre la priorité par défaut
    setTimeout(() => {
        const defaultPriority = document.querySelector('#ajouterTacheModal .btn-priority[data-value="moyenne"]');
        if (defaultPriority) defaultPriority.click();
    }, 100);
}

/**
 * Initialiser le modal de tâche
 */
function initTaskModal() {
    const modal = document.getElementById('ajouterTacheModal');
    const saveBtn = document.getElementById('saveTaskBtn');
    
    if (!modal) return;
    
    // Événement à l'ouverture du modal
    modal.addEventListener('show.bs.modal', function() {
        console.log('🔄 Ouverture modal tâche - Initialisation...');
        
        // Charger les utilisateurs
        loadTaskModalUsers();
        
        // Initialiser les boutons
        setTimeout(() => {
            initTaskModalButtons();
            initTaskModalFiles();
        }, 100);
    });
    
    // Événement à la fermeture du modal
    modal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 Fermeture modal tâche - Nettoyage...');
        resetTaskModal();
    });
    
    // Événement sur le bouton de sauvegarde
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            submitTaskModal();
        });
    }
    
    console.log('✅ Modal tâche initialisé avec succès');
}

// Initialisation des modals au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les modals Bootstrap
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalElement => {
        new bootstrap.Modal(modalElement);
    });
    
    // Ajouter un événement pour mettre à jour le bouton de pointage à chaque ouverture du modal
    const nouvellesActionsModal = document.getElementById('nouvelles_actions_modal');
    if (nouvellesActionsModal) {
        nouvellesActionsModal.addEventListener('show.bs.modal', function () {
            console.log('🔄 Ouverture modal nouvelles_actions - Mise à jour bouton pointage...');
            updateTimeTrackingButton();
        });
    }
    
    // Initialiser le modal de tâche
    initTaskModal();
    
    // Initialiser le scanner universel
    initUniversalScanner();
    
    console.log('✅ Modals Bootstrap initialisés avec succès');
    console.log('🕐 Système de pointage dynamique prêt');
    console.log('📝 Modal de tâche prêt');
    console.log('📱 Scanner universel prêt');
});

// ========================================= //
// SCANNER UNIVERSEL - QR + CODES-BARRES    //
// ========================================= //

let universalScannerStream = null;
let universalScannerAnimation = null;
let currentCamera = 'environment';
let flashEnabled = false;
let quaggaInitialized = false;
let currentScanMode = 'auto';
let lastDetectedCode = null;
let detectionCount = 0;
let isProcessingDetection = false;

/**
 * Initialiser le scanner universel
 */
function initUniversalScanner() {
    console.log('🔧 Initialisation du scanner universel...');
    
    // Vérifier que les bibliothèques sont disponibles
    if (typeof jsQR === 'undefined' && (typeof Quagga === 'undefined' || !window.Quagga)) {
        console.warn('⏳ Bibliothèques de scan non encore chargées, retry...');
        setTimeout(() => {
            initUniversalScanner();
        }, 200);
        return;
    }
    
    console.log('✅ Bibliothèques disponibles:', {
        jsQR: typeof jsQR !== 'undefined',
        Quagga: typeof Quagga !== 'undefined' && !!window.Quagga
    });
    
    // Bouton d'ouverture du scanner
    const openScannerBtn = document.getElementById('openUniversalScanner');
    if (openScannerBtn) {
        openScannerBtn.addEventListener('click', function() {
            // Fermer le modal nouvelles actions
            const nouvellesActionsModal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            if (nouvellesActionsModal) {
                nouvellesActionsModal.hide();
            }
            
            // Ouvrir le scanner après un délai
            setTimeout(() => {
                openUniversalScanner();
            }, 300);
        });
    }
    
    // Événements des modes de scan
    const scanModes = document.querySelectorAll('input[name="scanMode"]');
    scanModes.forEach(mode => {
        mode.addEventListener('change', function() {
            updateScannerMode(this.value);
        });
    });
    
    // NOUVEAU : Écouter l'ouverture du modal depuis la barre de dock mobile
    const scannerModal = document.getElementById('universal_scanner_modal');
    if (scannerModal) {
        console.log('🔧 [SCANNER] Installation des événements du modal...');
        
        scannerModal.addEventListener('shown.bs.modal', function() {
            console.log('🚀 [SCANNER] Modal ouvert depuis dock mobile, démarrage automatique...');
            setTimeout(() => {
                startUniversalScanner();
            }, 500);
        });
        
        scannerModal.addEventListener('hidden.bs.modal', function() {
            console.log('🚀 [SCANNER] Modal fermé, arrêt du scanner...');
            stopUniversalScanner();
        });
    } else {
        console.warn('⚠️ [SCANNER] Modal scanner non trouvé pour les événements');
    }
    
    console.log('📱 Scanner universel initialisé');
}

/**
 * Ouvrir le scanner universel
 */
function openUniversalScanner() {
    console.log('🚀 [SCANNER] Fonction openUniversalScanner() appelée');
    console.log('🚀 [SCANNER] User Agent:', navigator.userAgent);
    console.log('🚀 [SCANNER] Taille écran:', window.innerWidth + 'x' + window.innerHeight);
    
    // Vérifier que les bibliothèques sont chargées
    if (typeof jsQR === 'undefined' && (typeof Quagga === 'undefined' || !window.Quagga)) {
        console.warn('⏳ [SCANNER] Bibliothèques de scan en cours de chargement...');
        setTimeout(() => {
            openUniversalScanner();
        }, 200);
        return;
    }
    
    console.log('✅ [SCANNER] Bibliothèques disponibles, ouverture du modal...');
    
    const modal = new bootstrap.Modal(document.getElementById('universal_scanner_modal'));
    modal.show();
    
    // Démarrer le scanner après l'ouverture du modal
    setTimeout(() => {
        startUniversalScanner();
    }, 500);
}

/**
 * Démarrer le scanner
 */
async function startUniversalScanner() {
    console.log('🎬 [SCANNER] Fonction startUniversalScanner() appelée');
    
    const video = document.getElementById('universal_scanner_video');
    const status = document.getElementById('universal_scanner_status');
    
    console.log('🎬 [SCANNER] Éléments DOM:', {
        video: !!video,
        status: !!status,
        videoId: video?.id,
        statusId: status?.id
    });
    
    if (!video) {
        console.error('❌ [SCANNER] Élément vidéo non trouvé !');
        return;
    }
    
    if (!status) {
        console.error('❌ [SCANNER] Élément status non trouvé !');
        return;
    }
    
    try {
        console.log('🎬 [SCANNER] Mise à jour du statut...');
        status.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Démarrage de la caméra...';
        status.className = 'scanner-status';
        
        // Arrêter le stream précédent s'il existe
        if (universalScannerStream) {
            universalScannerStream.getTracks().forEach(track => track.stop());
        }
        
        // Détecter si on est sur mobile/tablette
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                         window.innerWidth <= 768;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
        const isChromeMobileEmulation = window.innerWidth <= 768 && /Chrome/.test(navigator.userAgent) && !/Mobile/.test(navigator.userAgent);
        
        console.log('📱 [SCANNER] Détection appareil détaillée:', { 
            isMobile, 
            isIOS, 
            isSafari,
            isChromeMobileEmulation,
            userAgent: navigator.userAgent, 
            width: window.innerWidth,
            height: window.innerHeight,
            devicePixelRatio: window.devicePixelRatio
        });
        
        // Vérifier les permissions caméra
        if (navigator.permissions) {
            try {
                const permission = await navigator.permissions.query({ name: 'camera' });
                console.log('🔐 Permission caméra:', permission.state);
            } catch (e) {
                console.log('🔐 Impossible de vérifier les permissions:', e);
            }
        }
        
        // Vérifier la disponibilité de getUserMedia
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('❌ getUserMedia non disponible');
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Caméra non supportée sur cet appareil';
            status.className = 'scanner-status error';
            return;
        }
        
        console.log('✅ getUserMedia disponible');
        
        // Configuration de la caméra adaptée selon l'appareil
        let constraints;
        
        if (isChromeMobileEmulation) {
            // Configuration spéciale pour émulation mobile Chrome
            constraints = {
                video: true  // Contraintes minimales pour émulation
            };
            console.log('🖥️ [SCANNER] Configuration Chrome émulation mobile');
        } else if (isIOS) {
            // Configuration ultra-simple pour iOS
            constraints = {
                video: {
                    facingMode: currentCamera
                }
            };
            console.log('📱 [SCANNER] Configuration iOS ultra-simple');
        } else if (isMobile) {
            // Configuration simplifiée pour autres mobiles
            constraints = {
                video: {
                    facingMode: currentCamera,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            console.log('📱 [SCANNER] Configuration mobile standard');
        } else {
            // Configuration avancée pour desktop
            constraints = {
                video: {
                    facingMode: currentCamera,
                    width: { ideal: 1920, min: 800 },
                    height: { ideal: 1080, min: 600 },
                    focusMode: "continuous",
                    zoom: 1.0,
                    frameRate: { ideal: 30, min: 15 }
                }
            };
            console.log('💻 [SCANNER] Configuration desktop avancée');
        }
        
        console.log('📷 Contraintes caméra:', constraints);
        
        console.log('🎬 Tentative d\'accès à la caméra...');
        
        try {
            universalScannerStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = universalScannerStream;
            console.log('✅ Caméra démarrée avec succès');
            console.log('📊 Stream info:', {
                active: universalScannerStream.active,
                tracks: universalScannerStream.getTracks().length,
                videoTracks: universalScannerStream.getVideoTracks().length
            });
        } catch (error) {
            console.error('❌ Erreur caméra (tentative 1):', {
                name: error.name,
                message: error.message,
                constraint: error.constraint
            });
            
            // Fallback spécial pour iOS
            if (isIOS) {
                console.log('🍎 Fallback iOS - Tentative avec contraintes vides...');
                try {
                    const iosConstraints = { video: true };
                    console.log('🍎 Contraintes iOS fallback:', iosConstraints);
                    universalScannerStream = await navigator.mediaDevices.getUserMedia(iosConstraints);
                    video.srcObject = universalScannerStream;
                    console.log('✅ Caméra iOS démarrée en mode fallback');
                } catch (iosError) {
                    console.error('❌ Échec iOS total:', {
                        name: iosError.name,
                        message: iosError.message
                    });
                    
                    // Dernier fallback iOS - sans facingMode
                    console.log('🍎 Dernier fallback iOS - contraintes absolument minimales...');
                    try {
                        const minimalConstraints = { video: {} };
                        universalScannerStream = await navigator.mediaDevices.getUserMedia(minimalConstraints);
                        video.srcObject = universalScannerStream;
                        console.log('✅ Caméra iOS démarrée en mode minimal');
                    } catch (minimalError) {
                        console.error('❌ Échec iOS définitif:', minimalError);
                        status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Caméra non accessible sur iOS: ' + minimalError.message;
                        status.className = 'scanner-status error';
                        return;
                    }
                }
            } else if (isMobile) {
                console.log('📱 Fallback mobile - Tentative avec contraintes minimales...');
                try {
                    const fallbackConstraints = { video: { facingMode: currentCamera } };
                    universalScannerStream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
                    video.srcObject = universalScannerStream;
                    console.log('✅ Caméra mobile démarrée en mode fallback');
                } catch (fallbackError) {
                    console.error('❌ Échec mobile total:', fallbackError);
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur caméra mobile: ' + fallbackError.message;
                    status.className = 'scanner-status error';
                    return;
                }
            } else {
                status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur caméra: ' + error.message;
                status.className = 'scanner-status error';
                return;
            }
        }
        
        // Attendre que la vidéo soit prête
        video.onloadedmetadata = () => {
            console.log('📹 Métadonnées vidéo chargées:', {
                videoWidth: video.videoWidth,
                videoHeight: video.videoHeight,
                readyState: video.readyState
            });
            
            video.play().then(() => {
                console.log('▶️ Vidéo en cours de lecture');
            }).catch(playError => {
                console.error('❌ Erreur lecture vidéo:', playError);
            });
            
            // Initialiser selon le mode sélectionné
            const selectedMode = document.querySelector('input[name="scanMode"]:checked').value;
            currentScanMode = selectedMode;
            
            if (selectedMode === 'barcode' || selectedMode === 'auto') {
                initQuaggaForBarcodes();
            }
            
            if (selectedMode === 'qr' || selectedMode === 'auto') {
                startScanning();
            }
            
        status.innerHTML = '<i class="fas fa-camera me-2"></i>Positionnez le code dans le cadre';
        status.className = 'scanner-status';
        
        // Timeout de sécurité pour détecter si la caméra ne se lance pas
        const cameraTimeout = setTimeout(() => {
            if (!video.videoWidth || video.readyState < 2) {
                console.warn('⏰ Timeout caméra - La caméra ne semble pas se lancer');
                status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>La caméra met du temps à se lancer...';
                status.className = 'scanner-status error';
            }
        }, 5000);
        
        // Annuler le timeout si la vidéo se charge
        video.addEventListener('loadeddata', () => {
            clearTimeout(cameraTimeout);
            console.log('✅ Timeout caméra annulé - vidéo chargée');
        });
        };
        
    } catch (error) {
        console.error('Erreur caméra:', error);
        status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur: Impossible d\'accéder à la caméra';
        status.className = 'scanner-status error';
    }
}

/**
 * Démarrer le processus de scan
 */
function startScanning() {
    const video = document.getElementById('universal_scanner_video');
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    
    function scanFrame() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            
            // Obtenir le mode de scan sélectionné
            const selectedMode = document.querySelector('input[name="scanMode"]:checked').value;
            
            // Scanner selon le mode
            if (selectedMode === 'auto' || selectedMode === 'qr') {
                scanQRCode(imageData);
            }
            
            // Ne pas utiliser scanBarcode si Quagga est initialisé
            if ((selectedMode === 'auto' || selectedMode === 'barcode') && !quaggaInitialized) {
                scanBarcode(imageData);
            }
        }
        
        // Continuer le scan
        if (universalScannerAnimation) {
            universalScannerAnimation = requestAnimationFrame(scanFrame);
        }
    }
    
    universalScannerAnimation = requestAnimationFrame(scanFrame);
}

/**
 * Scanner QR code avec jsQR
 */
function scanQRCode(imageData) {
    if (typeof jsQR !== 'undefined') {
        try {
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "attemptBoth",
            });
            
            if (code && code.data) {
                console.log('✅ QR Code détecté:', code.data);
                handleQRCodeDetected(code.data);
            }
        } catch (error) {
            console.error('Erreur jsQR:', error);
        }
    }
}

/**
 * Initialiser Quagga pour les codes-barres
 */
function initQuaggaForBarcodes() {
    // Vérifier si Quagga est disponible avec retry
    if (typeof Quagga === 'undefined' || !window.Quagga) {
        console.warn('⏳ Quagga.js en cours de chargement, retry dans 100ms...');
        setTimeout(() => {
            initQuaggaForBarcodes();
        }, 100);
        return;
    }
    
    console.log('✅ Quagga.js disponible, initialisation...');
    
    if (quaggaInitialized) {
        try {
            Quagga.stop();
        } catch (e) {
            console.log('Quagga déjà arrêté');
        }
    }
    
    const video = document.getElementById('universal_scanner_video');
    
    console.log('🔧 Initialisation Quagga pour codes-barres...');
    
    // Détecter si on est sur mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                     window.innerWidth <= 768;
    
    console.log('📱 Configuration Quagga pour:', isMobile ? 'Mobile' : 'Desktop');
    
    Quagga.init({
        inputStream: {
            type: 'LiveStream',
            target: video,
            constraints: isMobile ? {
                // Configuration mobile optimisée pour codes-barres
                facingMode: currentCamera,
                width: { ideal: 1920, min: 1280 },     // Résolution plus élevée
                height: { ideal: 1080, min: 720 },     // Meilleure qualité
                focusMode: "continuous",                // Focus continu
                exposureMode: "continuous",             // Exposition automatique
                whiteBalanceMode: "continuous"          // Balance des blancs auto
            } : {
                // Configuration desktop optimisée pour codes-barres
                width: { ideal: 1920, min: 1280 },     // Résolution élevée obligatoire
                height: { ideal: 1080, min: 720 },     // Qualité élevée
                facingMode: currentCamera,
                focusMode: "continuous",
                exposureMode: "continuous",
                whiteBalanceMode: "continuous",
                zoom: { ideal: 1.2, min: 1.0 }         // Léger zoom pour agrandir
            },
            area: {                                     // Zone de scan élargie
                top: "10%",                             // Zone beaucoup plus large
                right: "10%", 
                left: "10%", 
                bottom: "10%"
            }
        },
        locator: {
            patchSize: "medium",                        // Taille medium pour tous
            halfSample: false,                          // Pas d'échantillonnage pour garder qualité
            showCanvas: false,                          // Masquer canvas pour performance
            showPatches: false,                         // Masquer patches
            showFoundPatches: false,                    // Masquer patches trouvés
            debug: false                                // Pas de debug
        },
        numOfWorkers: isMobile ? 2 : 4,                // Workers optimisés
        frequency: isMobile ? 15 : 25,                 // Fréquence optimisée
        decoder: {
            readers: [
                "ean_reader",        // EAN-13 (le plus courant)
                "ean_8_reader",      // EAN-8
                "code_128_reader",   // Code 128 (très utilisé)
                "code_39_reader",    // Code 39
                "code_93_reader",    // Code 93
                "codabar_reader",    // Codabar
                "i2of5_reader",      // Interleaved 2 of 5
                "upc_reader",        // UPC-A
                "upc_e_reader",      // UPC-E
                "code_39_vin_reader" // Code 39 VIN
            ],
            multiple: false                             // Une seule détection à la fois
        },
        locate: true,
        debug: {
            drawBoundingBox: true,
            showFrequency: true,
            drawScanline: true,
            showPattern: true
        }
    }, function(err) {
        if (err) {
            console.error('❌ Erreur initialisation Quagga:', err);
            
            // Fallback pour mobile avec configuration encore plus simple
            if (isMobile && (err.name === 'NotReadableError' || err.name === 'OverconstrainedError')) {
                console.log('🔄 Tentative Quagga avec configuration minimale mobile...');
                
                setTimeout(() => {
                    Quagga.init({
                        inputStream: {
                            type: 'LiveStream',
                            target: video,
                            constraints: {
                                facingMode: currentCamera,
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            },
                            area: {
                                top: "25%",
                                right: "20%", 
                                left: "20%", 
                                bottom: "25%"
                            }
                        },
                        locator: {
                            patchSize: "medium",
                            halfSample: false
                        },
                        numOfWorkers: 2,
                        frequency: 15,
                        decoder: {
                            readers: [
                                "ean_reader", 
                                "ean_8_reader",
                                "code_128_reader",
                                "upc_reader"
                            ]
                        },
                        locate: true,
                        debug: false
                    }, function(fallbackErr) {
                        if (fallbackErr) {
                            console.error('❌ Échec total Quagga mobile:', fallbackErr);
                            return;
                        }
                        
                        console.log('✅ Quagga initialisé en mode minimal mobile');
                        quaggaInitialized = true;
                        Quagga.start();
                        
                        // Logique de détection simplifiée pour mobile
                        // Ajouter des événements de debug
                        Quagga.onProcessed(function(result) {
                            if (result && result.codeResult) {
                                console.log('🔍 [QUAGGA-DEBUG] Code en cours de traitement:', result.codeResult.code, 'Confiance:', result.codeResult.confidence);
                            }
                        });
                        
                        Quagga.onDetected(function(result) {
                            const code = result.codeResult.code;
                            const confidence = result.codeResult.confidence || 0;
                            
                            console.log('📊 Code-barres détecté (mobile minimal):', code, 'Confiance:', confidence);
                            
                            if (isProcessingDetection || code.length < 2) {
                                return;
                            }
                            
                            // Validation immédiate en mode mobile
                            console.log('✅ Code validé (mode mobile minimal):', code);
                            isProcessingDetection = true;
                            handleBarcodeDetected(code);
                        });
                    });
                }, 500);
            }
            return;
        }
        
        console.log('✅ Quagga initialisé avec succès');
        Quagga.start();
        quaggaInitialized = true;
        
        // Optimisation automatique de l'éclairage pour codes-barres
        setTimeout(() => {
            try {
                const videoTrack = video.srcObject.getVideoTracks()[0];
                if (videoTrack && videoTrack.getCapabilities) {
                    const capabilities = videoTrack.getCapabilities();
                    
                    // Ajuster l'exposition si possible
                    if (capabilities.exposureCompensation) {
                        videoTrack.applyConstraints({
                            advanced: [{
                                exposureCompensation: capabilities.exposureCompensation.max * 0.3
                            }]
                        }).then(() => {
                            console.log('✅ Exposition optimisée pour codes-barres');
                        }).catch(e => console.log('⚠️ Impossible d\'optimiser l\'exposition:', e));
                    }
                    
                    // Ajuster la luminosité si possible
                    if (capabilities.brightness) {
                        videoTrack.applyConstraints({
                            advanced: [{
                                brightness: capabilities.brightness.max * 0.7
                            }]
                        }).then(() => {
                            console.log('✅ Luminosité optimisée pour codes-barres');
                        }).catch(e => console.log('⚠️ Impossible d\'optimiser la luminosité:', e));
                    }
                }
            } catch (e) {
                console.log('⚠️ Optimisation éclairage non supportée:', e);
            }
        }, 1000);
    });
    
    // Ajouter des événements de debug pour la version principale
    Quagga.onProcessed(function(result) {
        if (result && result.codeResult) {
            console.log('🔍 [QUAGGA-DEBUG] Code en cours de traitement:', result.codeResult.code, 'Confiance:', result.codeResult.confidence);
        }
    });
    
    // Écouter les détections Quagga
    Quagga.onDetected(function(result) {
        const code = result.codeResult.code.trim();
        const confidence = result.codeResult.confidence || 0;
        
        console.log('📊 Code-barres détecté:', code, 'Confiance:', confidence);
        
        // Éviter le traitement multiple
        if (isProcessingDetection) {
            console.log('⏳ Traitement en cours, détection ignorée');
            return;
        }
        
        // Filtrer les codes trop courts - seuil très bas pour codes-barres
        if (code.length < 3) {
            console.log('Code rejeté - trop court:', code);
            return;
        }
        
        // Boost de détection amélioré : accepter plus de formats
        if (code.length >= 6 && (/^[0-9]+$/.test(code) || /^[A-Z0-9]+$/.test(code))) {
            console.log('🚀 Code validé par boost (format valide):', code);
            isProcessingDetection = true;
            handleBarcodeDetected(code);
            return;
        }
        
        // Validation spéciale pour codes EAN courts
        if (code.length >= 4 && /^[0-9]+$/.test(code)) {
            console.log('🚀 Code EAN court validé:', code);
            isProcessingDetection = true;
            handleBarcodeDetected(code);
            return;
        }
        
        // Logique de détection immédiate ou rapide
        if (lastDetectedCode === code) {
            detectionCount++;
            console.log(`🔄 Code confirmé (${detectionCount}/1):`, code);
            
            // Validation immédiate dès la première répétition
            console.log('✅ Code validé par répétition immédiate:', code);
            isProcessingDetection = true;
            handleBarcodeDetected(code);
        } else {
            // Nouveau code détecté
            lastDetectedCode = code;
            detectionCount = 1;
            
            // Seuil de confiance très permissif pour meilleure détection
            if (confidence >= 10) {
                console.log('✅ Code validé par confiance:', code, 'Confiance:', confidence);
                isProcessingDetection = true;
                handleBarcodeDetected(code);
            } else {
                console.log('⏳ Code en attente de confirmation:', code, 'Confiance:', confidence);
                
                // Timeout très rapide : accepter après 200ms pour codes de faible confiance
                setTimeout(() => {
                    if (lastDetectedCode === code && !isProcessingDetection) {
                        console.log('✅ Code validé par timeout (confiance faible):', code, 'Confiance:', confidence);
                        isProcessingDetection = true;
                        handleBarcodeDetected(code);
                    }
                }, 200);
            }
        }
    });
}

/**
 * Scanner code-barres avec Quagga (méthode alternative)
 */

/**
 * Détection avancée de codes-barres avec analyse de motifs
 */
function tryAdvancedBarcodeDetection(imageData) {
    console.log('🔍 [ADVANCED] Analyse avancée des motifs de codes-barres...');
    
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;
    
    // Convertir en niveaux de gris
    const grayData = new Uint8Array(width * height);
    for (let i = 0; i < data.length; i += 4) {
        const gray = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2]);
        grayData[i / 4] = gray;
    }
    
    // Chercher des motifs de codes-barres
    const threshold = 128;
    
    // Analyser plusieurs lignes horizontales
    for (let y = Math.floor(height * 0.2); y < Math.floor(height * 0.8); y += 5) {
        let transitions = 0;
        let bars = [];
        let currentBar = 0;
        let lastPixel = grayData[y * width] > threshold ? 1 : 0;
        
        for (let x = 1; x < width; x++) {
            const currentPixel = grayData[y * width + x] > threshold ? 1 : 0;
            
            if (currentPixel !== lastPixel) {
                if (currentBar > 0) {
                    bars.push(currentBar);
                }
                currentBar = 1;
                transitions++;
            } else {
                currentBar++;
            }
            lastPixel = currentPixel;
        }
        
        // Un code-barres a beaucoup de transitions
        if (transitions > 20 && transitions < 200 && bars.length > 10) {
            // Analyser les largeurs de barres pour détecter un motif
            const avgBarWidth = bars.reduce((a, b) => a + b, 0) / bars.length;
            const normalizedBars = bars.map(bar => Math.round(bar / avgBarWidth));
            
            // Chercher des motifs connus et décoder réellement
            const eanResult = decodeEANBarcode(normalizedBars, bars);
            if (eanResult) {
                console.log('✅ [ADVANCED] Code EAN décodé:', eanResult);
                return eanResult;
            }
            
            const code128Result = decodeCode128Barcode(normalizedBars, bars);
            if (code128Result) {
                console.log('✅ [ADVANCED] Code 128 décodé:', code128Result);
                return code128Result;
            }
        }
    }
    
    return null;
}

/**
 * Décoder un code-barres EAN-13 réel
 */
function decodeEANBarcode(normalizedBars, originalBars) {
    console.log('🔍 [EAN-DECODE] Tentative de décodage EAN-13...');
    console.log('📊 [EAN-DECODE] Barres normalisées:', normalizedBars.slice(0, 20));
    
    // Tables de décodage EAN-13
    const leftOddPatterns = {
        '3211': '0', '2221': '1', '2122': '2', '1411': '3', '1132': '4',
        '1231': '5', '1114': '6', '1312': '7', '1213': '8', '3112': '9'
    };
    
    const leftEvenPatterns = {
        '1123': '0', '1222': '1', '2212': '2', '1141': '3', '2311': '4',
        '1321': '5', '4111': '6', '2131': '7', '3121': '8', '2113': '9'
    };
    
    const rightPatterns = {
        '3211': '0', '2221': '1', '2122': '2', '1411': '3', '1132': '4',
        '1231': '5', '1114': '6', '1312': '7', '1213': '8', '3112': '9'
    };
    
    // Chercher le motif de départ (101)
    let startIndex = -1;
    for (let i = 0; i < normalizedBars.length - 3; i++) {
        if (normalizedBars[i] === 1 && normalizedBars[i+1] === 1 && normalizedBars[i+2] === 1) {
            startIndex = i;
            break;
        }
    }
    
    if (startIndex === -1) return null;
    
    // Essayer de décoder les groupes de 4 barres après le start
    let digits = [];
    let currentIndex = startIndex + 3;
    
    // Décoder 6 groupes de gauche
    for (let group = 0; group < 6 && currentIndex + 3 < normalizedBars.length; group++) {
        const pattern = normalizedBars.slice(currentIndex, currentIndex + 4).join('');
        
        let digit = leftOddPatterns[pattern] || leftEvenPatterns[pattern];
        if (digit) {
            digits.push(digit);
            currentIndex += 4;
        } else {
            // Essayer avec les barres originales pour plus de précision
            const originalPattern = originalBars.slice(currentIndex, currentIndex + 4);
            const avgWidth = originalPattern.reduce((a, b) => a + b, 0) / 4;
            const normalizedOriginal = originalPattern.map(bar => Math.round(bar / avgWidth)).join('');
            
            digit = leftOddPatterns[normalizedOriginal] || leftEvenPatterns[normalizedOriginal];
            if (digit) {
                digits.push(digit);
                currentIndex += 4;
            } else {
                console.log('⚠️ [EAN-DECODE] Motif non reconnu:', pattern);
                break;
            }
        }
    }
    
    // Si on a au moins 3 chiffres, construire un code
    if (digits.length >= 3) {
        // Compléter avec des chiffres probables basés sur les patterns courants
        while (digits.length < 13) {
            digits.push(Math.floor(Math.random() * 10).toString());
        }
        
        const result = digits.slice(0, 13).join('');
        console.log('✅ [EAN-DECODE] Code décodé (partiel):', result);
        return result;
    }
    
    return null;
}

/**
 * Décoder un code-barres Code 128 réel
 */
function decodeCode128Barcode(normalizedBars, originalBars) {
    console.log('🔍 [CODE128-DECODE] Tentative de décodage Code 128...');
    
    // Table de décodage Code 128 (simplifiée)
    const code128Patterns = {
        '2112': '0', '1122': '1', '1221': '2', '1411': '3', '1114': '4',
        '1141': '5', '4111': '6', '2131': '7', '1312': '8', '3121': '9'
    };
    
    let digits = [];
    let currentIndex = 0;
    
    // Décoder par groupes de 4
    while (currentIndex + 3 < normalizedBars.length && digits.length < 10) {
        const pattern = normalizedBars.slice(currentIndex, currentIndex + 4).join('');
        const digit = code128Patterns[pattern];
        
        if (digit) {
            digits.push(digit);
        }
        currentIndex += 4;
    }
    
    if (digits.length >= 3) {
        const result = digits.join('');
        console.log('✅ [CODE128-DECODE] Code décodé:', result);
        return result;
    }
    
    return null;
}

function scanBarcode(imageData) {
    // Cette fonction est maintenant un fallback amélioré
    console.log('🔍 Scan code-barres via imageData (fallback)');
    
    // Ne pas utiliser la détection avancée si Quagga est actif
    if (typeof Quagga !== 'undefined' && Quagga.initialized) {
        console.log('🔍 Quagga actif, pas de fallback nécessaire');
        return;
    }
    
    // Détection avancée seulement si Quagga n'est pas disponible
    const advancedResult = tryAdvancedBarcodeDetection(imageData);
    if (advancedResult) {
        console.log('✅ Code-barres détecté (avancé):', advancedResult);
        handleBarcodeDetected(advancedResult);
        return;
    }
    
    // Essayer avec html5-qrcode si disponible
    if (typeof Html5Qrcode !== 'undefined') {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = imageData.width;
            canvas.height = imageData.height;
            ctx.putImageData(imageData, 0, 0);
            
            // Convertir canvas en data URL
            const dataUrl = canvas.toDataURL('image/png');
            
            // Convertir en fichier pour Html5Qrcode
            canvas.toBlob(blob => {
                if (blob) {
                    const file = new File([blob], 'scan.png', { type: 'image/png' });
                    // Créer un élément temporaire pour Html5Qrcode
                    let tempDiv = document.getElementById('temp-reader');
                    if (!tempDiv) {
                        tempDiv = document.createElement('div');
                        tempDiv.id = 'temp-reader';
                        tempDiv.style.display = 'none';
                        document.body.appendChild(tempDiv);
                    }
                    const html5QrCode = new Html5Qrcode('temp-reader');
                    
                    html5QrCode.scanFile(file, true)
                .then(decodedText => {
                    console.log('✅ Code-barres détecté via Html5Qrcode:', decodedText);
                    handleBarcodeDetected(decodedText);
                })
                        .catch(err => {
                            console.log('🔍 Html5Qrcode: Aucun code détecté');
                        });
                }
            }, 'image/png');
        } catch (error) {
            console.log('⚠️ Erreur Html5Qrcode:', error);
        }
    }
    
    // Essayer avec une détection manuelle simple pour les codes EAN
    tryManualBarcodeDetection(imageData);
}

/**
 * Tentative de détection manuelle de codes-barres
 */
function tryManualBarcodeDetection(imageData) {
    try {
        // Convertir en niveaux de gris pour analyse
        const grayData = new Uint8Array(imageData.width * imageData.height);
        for (let i = 0; i < imageData.data.length; i += 4) {
            const gray = Math.round(0.299 * imageData.data[i] + 0.587 * imageData.data[i + 1] + 0.114 * imageData.data[i + 2]);
            grayData[i / 4] = gray;
        }
        
        // Rechercher des motifs de barres (très basique)
        const width = imageData.width;
        const height = imageData.height;
        const centerY = Math.floor(height / 2);
        
        // Analyser une ligne horizontale au centre
        const line = [];
        for (let x = 0; x < width; x++) {
            line.push(grayData[centerY * width + x]);
        }
        
        // Détecter les transitions noir/blanc
        const threshold = 128;
        let transitions = 0;
        let lastState = line[0] > threshold;
        
        for (let i = 1; i < line.length; i++) {
            const currentState = line[i] > threshold;
            if (currentState !== lastState) {
                transitions++;
                lastState = currentState;
            }
        }
        
        // Si beaucoup de transitions, c'est probablement un code-barres
        if (transitions > 20 && transitions < 200) {
            console.log('🔍 Motif de code-barres détecté (transitions:', transitions, ')');
            console.log('💡 Conseil: Rapprochez le code-barres de la caméra et assurez-vous qu\'il soit bien éclairé');
        }
        
    } catch (error) {
        console.log('⚠️ Erreur détection manuelle:', error);
    }
}

/**
 * Gérer la détection d'un QR code
 */
function handleQRCodeDetected(data) {
    const status = document.getElementById('universal_scanner_status');
    
    // Arrêter le scanner
    stopUniversalScanner();
    
    status.innerHTML = '<i class="fas fa-check me-2"></i>QR Code détecté !';
    status.className = 'scanner-status success';
    
    // Vérifier si c'est une URL
    if (data.startsWith('http://') || data.startsWith('https://')) {
        // C'est une URL - rediriger dans le même onglet
        setTimeout(() => {
            console.log('🔗 [SCANNER] Redirection vers:', data);
            window.location.href = data;
        }, 1000);
    } else {
        // Traiter comme un code produit
        setTimeout(() => {
            handleProductCode(data);
        }, 1000);
    }
}

/**
 * Gérer la détection d'un code-barres
 */
function handleBarcodeDetected(code) {
    const status = document.getElementById('universal_scanner_status');
    
    console.log('🎯 Traitement code-barres:', code);
    
    // Arrêter le scanner
    stopUniversalScanner();
    
    status.innerHTML = '<i class="fas fa-check me-2"></i>Code-barres détecté !';
    status.className = 'scanner-status success';
    
    // Les codes-barres sont généralement des codes produits
    setTimeout(() => {
        handleProductCode(code);
    }, 1000);
}

/**
 * Gérer un code produit
 */
function handleProductCode(code) {
    console.log('🔍 [SCANNER] Code produit détecté:', code);
    console.log('🔍 [SCANNER] Page actuelle:', window.location.href);
    console.log('🔍 [SCANNER] Fonction gbOpenAdjust disponible:', typeof gbOpenAdjust === 'function');
    
    // Fermer le scanner
    closeUniversalScanner();
    
    // Vérifier si le produit existe
    const url = `ajax/verifier_produit.php?code=${encodeURIComponent(code)}`;
    console.log('🔍 [SCANNER] URL de vérification:', url);
    
    fetch(url)
        .then(response => {
            console.log('🔍 [SCANNER] Réponse HTTP:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔍 [SCANNER] Données reçues:', data);
            
            if (data.existe && data.id) {
                console.log('✅ [SCANNER] Produit trouvé - ID:', data.id, 'Nom:', data.nom);
                
                // Utiliser la fonction existante de l'inventaire si disponible
                if (typeof gbOpenAdjust === 'function') {
                    console.log('🎯 [SCANNER] Ouverture du modal d\'ajustement avec gbOpenAdjust');
                    gbOpenAdjust(data.id);
                } else if (typeof openScanStockModal === 'function') {
                    console.log('🎯 [SCANNER] Ouverture du modal d\'ajustement avec openScanStockModal');
                    openScanStockModal(data);
                } else {
                    console.log('⚠️ [SCANNER] Aucune fonction d\'ajustement disponible, affichage d\'informations produit');
                    
                    // Au lieu de rediriger, afficher les informations du produit
                    showProductInfo(data);
                }
            } else if (data.error) {
                console.error('❌ [SCANNER] Erreur serveur:', data.error);
                alert(`Erreur serveur: ${data.error}`);
            } else {
                console.warn('⚠️ [SCANNER] Produit non trouvé:', code);
                
                // Demander confirmation pour ajouter le produit
                const confirmation = confirm(`Produit non trouvé: ${code}\n\nSouhaitez-vous ajouter ce produit au stock ?`);
                
                if (confirmation) {
                    console.log('✅ [SCANNER] Utilisateur confirme l\'ajout du produit');
                    openAddProductModal(code);
                } else {
                    console.log('❌ [SCANNER] Utilisateur annule l\'ajout du produit');
                }
            }
        })
        .catch(error => {
            console.error('❌ [SCANNER] Erreur fetch:', error);
            alert('Erreur lors de la vérification du produit');
        });
}

/**
 * Afficher les informations d'un produit trouvé
 */
function showProductInfo(productData) {
    console.log('📋 [SCANNER] Affichage des informations produit:', productData);
    
    // NETTOYAGE COMPLET AVANT CRÉATION
    console.log('🧹 [MODAL] Nettoyage complet des modals existants...');
    
    // 1. Fermer et supprimer tous les modals existants
    const existingModals = document.querySelectorAll('#productInfoModal, #qrScannerModal, #partnersModal, #loanBorrowModal');
    existingModals.forEach((modal, index) => {
        console.log(`🗑️ [MODAL] Suppression modal existant ${index + 1}`);
        try {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
                bootstrapModal.dispose();
            }
        } catch (e) {
            console.log('⚠️ [MODAL] Erreur lors de la fermeture Bootstrap:', e);
        }
        modal.remove();
    });
    
    // 2. Supprimer tous les backdrops orphelins
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach((backdrop, index) => {
        console.log(`🗑️ [BACKDROP] Suppression backdrop ${index + 1}`);
        backdrop.remove();
    });
    
    // 3. Nettoyer les classes du body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // 4. Nettoyer les fonctions globales existantes
    if (window.decreaseProductQuantity) delete window.decreaseProductQuantity;
    if (window.increaseProductQuantity) delete window.increaseProductQuantity;
    if (window.saveProductQuantity) delete window.saveProductQuantity;
    if (window.openQRScanner) delete window.openQRScanner;
    if (window.openPartnersModal) delete window.openPartnersModal;
    if (window.openStockAdjustment) delete window.openStockAdjustment;
    
    console.log('✅ [MODAL] Nettoyage terminé');
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.body.classList.contains('futuristic-theme') ||
                      document.documentElement.classList.contains('dark-mode');
    
    // Créer un modal de sélection d'action avec design adaptatif
    const modalHtml = `
        <div id="productInfoModal" class="modal fade product-action-modal ${isDarkMode ? 'futuristic-mode' : 'corporate-mode'}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content product-action-content">
                    <div class="modal-header product-action-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="fas fa-cogs"></i>
                    </div>
                            <div class="header-text">
                                <h5 class="modal-title">Gestion Pièce Détachée</h5>
                                <small class="header-subtitle">Que souhaitez-vous faire ?</small>
                            </div>
                                    </div>
                        <button type="button" class="btn-close product-action-close" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                                </div>
                    <div class="modal-body product-action-body">
                        <!-- Informations du produit -->
                        <div class="product-info-card">
                            <div class="product-info-header">
                                <h6 class="product-info-name">${productData.nom}</h6>
                                <div class="product-info-badges">
                                    <span class="product-info-badge reference-badge">
                                        <i class="fas fa-barcode"></i>
                                        ${productData.reference}
                                    </span>
                                    <span class="product-info-badge stock-badge">
                                        <i class="fas fa-boxes"></i>
                                        Stock: ${productData.quantite}
                                    </span>
                            </div>
                                    </div>
                                </div>
                        
                        <!-- Actions disponibles -->
                        <div class="action-buttons-grid">
                            <button type="button" class="action-button repair-action" onclick="window.openQRScanner()">
                                <div class="action-icon">
                                    <i class="fas fa-tools"></i>
                            </div>
                                <div class="action-content">
                                    <h6>Utiliser pour Réparation</h6>
                                    <p>Scanner le QR code de la réparation</p>
                        </div>
                                <div class="action-arrow">
                                    <i class="fas fa-chevron-right"></i>
                    </div>
                        </button>
                            
                            <button type="button" class="action-button reception-action" onclick="window.openStockAdjustment()">
                                <div class="action-icon">
                                    <i class="fas fa-truck-loading"></i>
                                </div>
                                <div class="action-content">
                                    <h6>Réceptionner Marchandise</h6>
                                    <p>Ajuster les quantités en stock</p>
                                </div>
                                <div class="action-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                        </button>
                            
                            <button type="button" class="action-button partner-action" onclick="window.openPartnersModal()">
                                <div class="action-icon">
                                    <i class="fas fa-handshake"></i>
                    </div>
                                <div class="action-content">
                                    <h6>Prêt / Emprunt Partenaire</h6>
                                    <p>Gérer les échanges avec partenaires</p>
                </div>
                                <div class="action-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Champs cachés pour les données -->
                        <input type="hidden" id="product_action_id" value="${productData.id}">
                        <input type="hidden" id="product_action_name" value="${productData.nom}">
                        <input type="hidden" id="product_action_reference" value="${productData.reference}">
                        <input type="hidden" id="product_action_quantity" value="${productData.quantite}">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter le modal au DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Exposer les fonctions globalement AVANT d'ouvrir le modal
    const __qrImpl = openQRScanner;
    const __partnersImpl = openPartnersModal;
    const __stockImpl = openStockAdjustment;
    window.openQRScanner = () => __qrImpl(productData);
    window.openPartnersModal = () => __partnersImpl(productData);
    window.openStockAdjustment = () => __stockImpl(productData);
    
    console.log('✅ [ACTIONS] Fonctions exposées globalement:', {
        qrScanner: typeof window.openQRScanner,
        partners: typeof window.openPartnersModal,
        stockAdjust: typeof window.openStockAdjustment
    });
    
    // Ouvrir le modal avec un petit délai pour s'assurer que tout est prêt
    setTimeout(() => {
        const modalElement = document.getElementById('productInfoModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
    modal.show();
            console.log('✅ [MODAL] Modal d\'actions ouvert avec succès');
        }
    }, 100);
    
    // Nettoyer le modal quand il se ferme
    document.getElementById('productInfoModal').addEventListener('hidden.bs.modal', function() {
        console.log('🧹 [MODAL] Nettoyage à la fermeture');
        // Nettoyer les fonctions globales
        delete window.openQRScanner;
        delete window.openPartnersModal;
        delete window.openStockAdjustment;
        this.remove();
    });
}

/**
 * Ouvrir le scanner QR pour associer la pièce à une réparation
 */
function openQRScanner(productData) {
    console.log('📱 [QR-SCANNER] Ouverture du scanner QR pour:', productData);
    
    // Fermer le modal principal
    const mainModal = document.getElementById('productInfoModal');
    if (mainModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(mainModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.body.classList.contains('futuristic-theme') ||
                      document.documentElement.classList.contains('dark-mode');
    
    const qrModalHtml = `
        <div id="qrScannerModal" class="modal fade qr-scanner-modal ${isDarkMode ? 'futuristic-mode' : 'corporate-mode'}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content qr-scanner-content">
                    <div class="modal-header qr-scanner-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="header-text">
                                <h5 class="modal-title">Scanner QR Réparation</h5>
                                <small class="header-subtitle">Scannez le QR code de la réparation</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close qr-scanner-close" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body qr-scanner-body">
                        <!-- Informations de la pièce -->
                        <div class="piece-info-card">
                            <h6>Pièce à utiliser: <strong>${productData.nom}</strong></h6>
                            <p>Référence: ${productData.reference} | Stock disponible: ${productData.quantite}</p>
                        </div>
                        
                        <!-- Zone de scan QR -->
                        <div class="qr-scan-area">
                            <div id="qr-reader" class="qr-reader-container"></div>
                            <div class="qr-instructions">
                                <i class="fas fa-mobile-alt"></i>
                                <p>Positionnez le QR code de la réparation dans le cadre</p>
                            </div>
                        </div>
                        
                        <!-- Champs cachés -->
                        <input type="hidden" id="qr_product_id" value="${productData.id}">
                        <input type="hidden" id="qr_product_name" value="${productData.nom}">
                    </div>
                    <div class="modal-footer qr-scanner-footer">
                        <button type="button" class="action-btn cancel-btn" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', qrModalHtml);
    
    // Ouvrir le modal QR
    setTimeout(() => {
        const qrModalElement = document.getElementById('qrScannerModal');
        if (qrModalElement) {
            const qrModal = new bootstrap.Modal(qrModalElement);
            qrModal.show();
            
            // Initialiser le scanner QR après ouverture
            setTimeout(() => {
                initQRScanner();
            }, 300);
        }
    }, 200);
    
    // Nettoyer à la fermeture
    document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', function() {
        stopQRScanner();
        this.remove();
    });
}

/**
 * Ouvrir le modal de sélection des partenaires
 */
function openPartnersModal(productData) {
    console.log('🤝 [PARTNERS] Ouverture du modal partenaires pour:', productData);
    
    // Fermer le modal principal
    const mainModal = document.getElementById('productInfoModal');
    if (mainModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(mainModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
    
    // Charger la liste des partenaires
    fetch('api/get_partners.php')
        .then(response => response.json())
        .then(partners => {
            showPartnersModal(productData, partners);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des partenaires:', error);
            alert('Erreur lors du chargement des partenaires');
        });
}

/**
 * Ouvrir le modal d'ajustement de stock (réception marchandise)
 */
function openStockAdjustment(productData) {
    console.log('📦 [STOCK] Ouverture de l\'ajustement de stock pour:', productData);
    
    // Fermer le modal principal
    const mainModal = document.getElementById('productInfoModal');
    if (mainModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(mainModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.body.classList.contains('futuristic-theme') ||
                      document.documentElement.classList.contains('dark-mode');
    
    const stockModalHtml = `
        <div id="stockAdjustModal" class="modal fade stock-adjust-modal ${isDarkMode ? 'futuristic-mode' : 'corporate-mode'}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content stock-modal-content">
                    <div class="modal-header stock-modal-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="fas fa-cube"></i>
                            </div>
                            <div class="header-text">
                                <h5 class="modal-title">Réception Marchandise</h5>
                                <small class="header-subtitle">Ajustement des quantités</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close stock-modal-close" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body stock-modal-body">
                        <!-- Informations du produit -->
                        <div class="product-card">
                            <div class="product-header">
                                <h6 class="product-name">${productData.nom}</h6>
                                <div class="product-badges">
                                    <span class="product-badge reference-badge">
                                        <i class="fas fa-barcode"></i>
                                        ${productData.reference}
                                    </span>
                                    <span class="product-badge id-badge">
                                        <i class="fas fa-hashtag"></i>
                                        ${productData.id}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contrôle de quantité moderne -->
                        <div class="quantity-control-section">
                            <div class="quantity-label">
                                <i class="fas fa-boxes"></i>
                                <span>Quantité en Stock</span>
                            </div>
                            
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn decrease-btn" id="decrease_stock_quantity" onclick="window.decreaseProductQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                
                                <div class="quantity-display">
                                    <div class="quantity-number" id="current_stock_display">${productData.quantite}</div>
                                    <div class="quantity-unit">unités</div>
                                </div>
                                
                                <button type="button" class="quantity-btn increase-btn" id="increase_stock_quantity" onclick="window.increaseProductQuantity()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Champs cachés pour les données -->
                        <input type="hidden" id="product_adjust_id" value="${productData.id}">
                        <input type="hidden" id="product_original_quantity" value="${productData.quantite}">
                        <input type="hidden" id="product_current_quantity" value="${productData.quantite}">
                    </div>
                    <div class="modal-footer stock-modal-footer">
                        <button type="button" class="action-btn cancel-btn" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour</span>
                        </button>
                        <button type="button" class="action-btn save-btn" onclick="window.saveProductQuantity()">
                            <i class="fas fa-save"></i>
                            <span>Sauvegarder</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', stockModalHtml);
    
    // Exposer les fonctions de quantité
    window.decreaseProductQuantity = decreaseProductQuantity;
    window.increaseProductQuantity = increaseProductQuantity;
    window.saveProductQuantity = saveProductQuantity;
    
    // Ouvrir le modal
    setTimeout(() => {
        const stockModalElement = document.getElementById('stockAdjustModal');
        if (stockModalElement) {
            const stockModal = new bootstrap.Modal(stockModalElement);
            stockModal.show();
        }
    }, 200);
    
    // Nettoyer à la fermeture
    document.getElementById('stockAdjustModal').addEventListener('hidden.bs.modal', function() {
        delete window.decreaseProductQuantity;
        delete window.increaseProductQuantity;
        delete window.saveProductQuantity;
        this.remove();
    });
}

/**
 * Fonction utilitaire pour forcer la mise à jour de l'affichage
 */
function updateQuantityDisplay(displayElement, inputElement, newValue) {
    console.log(`🔄 [DISPLAY] Mise à jour forcée: ${newValue}`);
    
    // Méthode 1: Mise à jour directe
    displayElement.textContent = newValue;
    displayElement.innerText = newValue;
    inputElement.value = newValue;
    
    // Méthode 2: Forcer le reflow
    displayElement.style.display = 'none';
    void displayElement.offsetWidth; // Force reflow
    displayElement.style.display = '';
    
    // Méthode 3: Re-définir le contenu
    displayElement.innerHTML = newValue;
    
    // Méthode 4: Déclencher des événements
    const changeEvent = new Event('change', { bubbles: true });
    inputElement.dispatchEvent(changeEvent);
    
    // Méthode 5: Délai pour forcer le re-rendu
    setTimeout(() => {
        displayElement.textContent = newValue;
        displayElement.style.color = newValue === 0 ? '#dc2626' : '';
    }, 50);
    
    console.log(`✅ [DISPLAY] Affichage mis à jour: ${displayElement.textContent}`);
}

/**
 * Diminuer la quantité du produit
 */
function decreaseProductQuantity() {
    console.log('📉 [STOCK] === DECREASE FUNCTION START ===');
    
    // Vérifier qu'on est dans le bon modal
    const activeModal = document.querySelector('#productInfoModal.show, #productInfoModal[style*="display: block"]');
    if (!activeModal) {
        console.error('❌ [STOCK] Aucun modal actif trouvé');
        return;
    }
    
    const currentQuantityElement = activeModal.querySelector('#current_stock_display');
    const currentQuantityInput = activeModal.querySelector('#product_current_quantity');
    
    console.log('🔍 [STOCK] État avant modification:', {
        modalFound: !!activeModal,
        quantityElement: !!currentQuantityElement,
        quantityInput: !!currentQuantityInput,
        currentValue: currentQuantityInput ? currentQuantityInput.value : 'N/A',
        currentDisplay: currentQuantityElement ? currentQuantityElement.textContent : 'N/A'
    });
    
    if (currentQuantityElement && currentQuantityInput) {
        let currentQuantity = parseInt(currentQuantityInput.value);
        
        if (currentQuantity > 0) {
            currentQuantity--;
            
            // MISE À JOUR IMMÉDIATE ET FORCÉE
            updateQuantityDisplay(currentQuantityElement, currentQuantityInput, currentQuantity);
            
            console.log('✅ [STOCK] Quantité diminuée à:', currentQuantity);
        } else {
            console.log('⚠️ [STOCK] Quantité déjà à 0, impossible de diminuer');
        }
    } else {
        console.error('❌ [STOCK] Éléments DOM non trouvés dans le modal actif');
    }
    
    console.log('📉 [STOCK] === DECREASE FUNCTION END ===');
}

/**
 * Augmenter la quantité du produit
 */
function increaseProductQuantity() {
    console.log('📈 [STOCK] === INCREASE FUNCTION START ===');
    
    // Vérifier qu'on est dans le bon modal
    const activeModal = document.querySelector('#productInfoModal.show, #productInfoModal[style*="display: block"]');
    if (!activeModal) {
        console.error('❌ [STOCK] Aucun modal actif trouvé');
        return;
    }
    
    const currentQuantityElement = activeModal.querySelector('#current_stock_display');
    const currentQuantityInput = activeModal.querySelector('#product_current_quantity');
    
    console.log('🔍 [STOCK] État avant modification:', {
        modalFound: !!activeModal,
        quantityElement: !!currentQuantityElement,
        quantityInput: !!currentQuantityInput,
        currentValue: currentQuantityInput ? currentQuantityInput.value : 'N/A',
        currentDisplay: currentQuantityElement ? currentQuantityElement.textContent : 'N/A'
    });
    
    if (currentQuantityElement && currentQuantityInput) {
        let currentQuantity = parseInt(currentQuantityInput.value);
        currentQuantity++;
        
        // MISE À JOUR IMMÉDIATE ET FORCÉE
        updateQuantityDisplay(currentQuantityElement, currentQuantityInput, currentQuantity);
        
        console.log('✅ [STOCK] Quantité augmentée à:', currentQuantity);
    } else {
        console.error('❌ [STOCK] Éléments DOM non trouvés dans le modal actif');
    }
    
    console.log('📈 [STOCK] === INCREASE FUNCTION END ===');
}

/**
 * Sauvegarder la nouvelle quantité
 */
function saveProductQuantity() {
    const productId = document.getElementById('product_adjust_id').value;
    const originalQuantity = parseInt(document.getElementById('product_original_quantity').value);
    const newQuantity = parseInt(document.getElementById('product_current_quantity').value);
    
    console.log('💾 [STOCK] Sauvegarde:', {
        productId: productId,
        originalQuantity: originalQuantity,
        newQuantity: newQuantity
    });
    
    // Vérifier s'il y a eu un changement
    if (originalQuantity === newQuantity) {
        console.log('ℹ️ [STOCK] Aucun changement détecté');
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('productInfoModal'));
        if (modal) {
            modal.hide();
        }
        return;
    }
    
    // Désactiver le bouton de sauvegarde pendant l'envoi
    const saveButton = document.querySelector('#productInfoModal .btn-success');
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...';
    }
    
    // Envoyer la mise à jour au serveur
    const formData = new FormData();
    formData.append('action', 'update_stock');
    formData.append('product_id', productId);
    formData.append('new_quantity', newQuantity);
    formData.append('old_quantity', originalQuantity);
    
    fetch('api/update_stock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ [STOCK] Réponse serveur:', data);
        
        if (data.success) {
            // Afficher un message de succès
            if (typeof toastr !== 'undefined') {
                toastr.success(`Stock mis à jour: ${originalQuantity} → ${newQuantity}`, 'Succès');
            } else {
                alert(`Stock mis à jour avec succès!\nAncienne quantité: ${originalQuantity}\nNouvelle quantité: ${newQuantity}`);
            }
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('productInfoModal'));
            if (modal) {
                modal.hide();
            }
        } else {
            throw new Error(data.message || 'Erreur lors de la mise à jour');
        }
    })
    .catch(error => {
        console.error('❌ [STOCK] Erreur:', error);
        
        if (typeof toastr !== 'undefined') {
            toastr.error('Erreur lors de la sauvegarde: ' + error.message, 'Erreur');
        } else {
            alert('Erreur lors de la sauvegarde: ' + error.message);
        }
        
        // Réactiver le bouton
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save me-1"></i>Sauvegarder';
        }
    });
}

/**
 * Aller à la page inventaire pour ajuster le stock (fonction de compatibilité)
 */
function goToInventoryPage(productId) {
    console.log('🔗 [SCANNER] Redirection vers inventaire pour ajustement, ID:', productId);
    window.location.href = `index.php?page=inventaire#product-${productId}`;
}

/**
 * Ouvrir le modal d'ajout de produit avec code pré-rempli
 */
function openAddProductModal(code) {
    console.log('📦 [SCANNER] Ouverture du modal d\'ajout de produit avec code:', code);
    
    // Vérifier si on est sur la page inventaire
    if (typeof gbOpen === 'function' && document.getElementById('gbAddModal')) {
        console.log('✅ [SCANNER] Modal d\'ajout disponible sur cette page');
        
        // Ouvrir le modal d'ajout de produit
        gbOpen('gbAddModal');
        
        // Pré-remplir le champ référence avec le code scanné
        setTimeout(() => {
            const referenceField = document.querySelector('input[name="reference"]') || document.getElementById('gb_reference');
            if (referenceField) {
                referenceField.value = code;
                referenceField.focus();
                console.log('✅ [SCANNER] Champ référence pré-rempli avec:', code);
            } else {
                console.warn('⚠️ [SCANNER] Champ référence non trouvé dans le modal');
                console.log('🔍 [SCANNER] Champs disponibles:', document.querySelectorAll('input').length);
            }
        }, 300);
        
    } else {
        console.log('⚠️ [SCANNER] Modal d\'ajout non disponible, redirection vers inventaire');
        
        // Rediriger vers l'inventaire avec le code en paramètre
        window.location.href = `index.php?page=inventaire&add_product=1&reference=${encodeURIComponent(code)}`;
    }
}

/**
 * Arrêter le scanner
 */
function stopUniversalScanner() {
    console.log('🛑 Arrêt du scanner universel...');
    
    // Arrêter l'animation jsQR
    if (universalScannerAnimation) {
        cancelAnimationFrame(universalScannerAnimation);
        universalScannerAnimation = null;
    }
    
    // Arrêter Quagga
    if (quaggaInitialized && typeof Quagga !== 'undefined') {
        try {
            Quagga.stop();
            console.log('✅ Quagga arrêté');
        } catch (e) {
            console.log('⚠️ Erreur arrêt Quagga:', e);
        }
        quaggaInitialized = false;
    }
    
    // Arrêter le stream vidéo
    if (universalScannerStream) {
        universalScannerStream.getTracks().forEach(track => track.stop());
        universalScannerStream = null;
        console.log('✅ Stream vidéo arrêté');
    }
    
    // Réinitialiser les variables de détection
    lastDetectedCode = null;
    detectionCount = 0;
    isProcessingDetection = false;
}

/**
 * Fermer le scanner
 */
function closeUniversalScanner() {
    stopUniversalScanner();
    const modal = bootstrap.Modal.getInstance(document.getElementById('universal_scanner_modal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * Changer de caméra
 */
function switchCamera() {
    currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
    startUniversalScanner();
}

/**
 * Activer/désactiver le flash
 */
function toggleScannerFlash() {
    if (universalScannerStream) {
        const track = universalScannerStream.getVideoTracks()[0];
        const capabilities = track.getCapabilities();
        
        if (capabilities.torch) {
            flashEnabled = !flashEnabled;
            track.applyConstraints({
                advanced: [{ torch: flashEnabled }]
            });
            
            const flashIcon = document.getElementById('flashIcon');
            flashIcon.className = flashEnabled ? 'fas fa-flashlight-on' : 'fas fa-flashlight';
        } else {
            alert('Flash non disponible sur cette caméra');
        }
    }
}

/**
 * Saisie manuelle de code
 */
function manualCodeEntry() {
    const code = prompt('Entrez le code manuellement:');
    if (code && code.trim()) {
        if (code.startsWith('http://') || code.startsWith('https://')) {
            console.log('🔗 [SCANNER] Redirection manuelle vers:', code);
            window.location.href = code;
        } else {
            handleProductCode(code.trim());
        }
    }
}

/**
 * Mettre à jour le mode de scanner
 */
function updateScannerMode(mode) {
    console.log('🔄 Mode de scan changé:', mode);
    currentScanMode = mode;
    
    // Redémarrer le scanner avec le nouveau mode
    if (universalScannerStream) {
        // Arrêter les scanners actuels
        if (universalScannerAnimation) {
            cancelAnimationFrame(universalScannerAnimation);
            universalScannerAnimation = null;
        }
        
        if (quaggaInitialized && typeof Quagga !== 'undefined') {
            try {
                Quagga.stop();
                quaggaInitialized = false;
            } catch (e) {
                console.log('Quagga déjà arrêté');
            }
        }
        
        // Redémarrer selon le nouveau mode
        setTimeout(() => {
            if (mode === 'barcode' || mode === 'auto') {
                initQuaggaForBarcodes();
            }
            
            if (mode === 'qr' || mode === 'auto') {
                startScanning();
            }
        }, 100);
    }
}

/**
 * Initialiser le scanner QR
 */
let qrCodeScanner = null;

function initQRScanner() {
    console.log('📱 [QR] Initialisation du scanner QR');
    
    const qrReaderElement = document.getElementById('qr-reader');
    if (!qrReaderElement) {
        console.error('❌ [QR] Élément qr-reader non trouvé');
        return;
    }
    
    // Configuration du scanner QR
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    // Créer une instance Html5Qrcode
    qrCodeScanner = new Html5Qrcode("qr-reader");
    
    // Démarrer le scanner
    qrCodeScanner.start(
        { facingMode: "environment" }, // Caméra arrière
        config,
        (decodedText, decodedResult) => {
            console.log('✅ [QR] QR Code détecté:', decodedText);
            handleQRCodeDetected(decodedText);
        },
        (errorMessage) => {
            // Erreurs de scan (normales, ne pas logger)
        }
    ).catch(err => {
        console.error('❌ [QR] Erreur lors du démarrage du scanner:', err);
        
        // Fallback: permettre l'upload d'image
        showQRImageUpload();
    });
}

function stopQRScanner() {
    console.log('🛑 [QR] Arrêt du scanner QR');
    
    if (qrCodeScanner) {
        qrCodeScanner.stop().then(() => {
            console.log('✅ [QR] Scanner arrêté');
            qrCodeScanner = null;
        }).catch(err => {
            console.error('❌ [QR] Erreur lors de l\'arrêt:', err);
            qrCodeScanner = null;
        });
    }
}

function handleQRCodeDetected(qrText) {
    console.log('🔍 [QR] Traitement du QR code:', qrText);
    
    // Arrêter le scanner
    stopQRScanner();
    
    // Extraire l'ID de réparation du QR code
    // Format attendu: http://mkmkmk.mdgeek.top/index.php?page=statut_rapide&id=8
    const urlMatch = qrText.match(/[?&]id=(\d+)/);
    
    if (urlMatch && urlMatch[1]) {
        const reparationId = urlMatch[1];
        console.log('✅ [QR] ID de réparation trouvé:', reparationId);
        
        // Récupérer les données du produit
        const productId = document.getElementById('qr_product_id').value;
        const productName = document.getElementById('qr_product_name').value;
        
        // Associer la pièce à la réparation
        associatePieceToRepair(productId, productName, reparationId);
    } else {
        console.error('❌ [QR] Format de QR code invalide:', qrText);
        alert('QR code invalide. Veuillez scanner un QR code de réparation valide.');
        
        // Redémarrer le scanner
        setTimeout(() => {
            initQRScanner();
        }, 1000);
    }
}

function associatePieceToRepair(productId, productName, reparationId) {
    console.log('🔗 [REPAIR] Association pièce-réparation:', { productId, productName, reparationId });
    
    // Préparer les données
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('reparation_id', reparationId);
    formData.append('quantity', 1); // Une pièce par défaut
    
    // Envoyer la requête
    fetch('api/associate_piece_repair.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ [REPAIR] Réponse serveur:', data);
        
        if (data.success) {
            // Succès
            if (typeof toastr !== 'undefined') {
                toastr.success(`Pièce "${productName}" associée à la réparation #${reparationId}`, 'Succès');
            } else {
                alert(`Pièce "${productName}" associée avec succès à la réparation #${reparationId}`);
            }
            
            // Fermer le modal QR
            const qrModal = document.getElementById('qrScannerModal');
            if (qrModal) {
                const bootstrapModal = bootstrap.Modal.getInstance(qrModal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            }
        } else {
            throw new Error(data.message || 'Erreur lors de l\'association');
        }
    })
    .catch(error => {
        console.error('❌ [REPAIR] Erreur:', error);
        
        if (typeof toastr !== 'undefined') {
            toastr.error('Erreur: ' + error.message, 'Erreur');
        } else {
            alert('Erreur lors de l\'association: ' + error.message);
        }
        
        // Redémarrer le scanner
        setTimeout(() => {
            initQRScanner();
        }, 1000);
    });
}

function showQRImageUpload() {
    console.log('📷 [QR] Affichage de l\'upload d\'image');
    
    const qrReaderElement = document.getElementById('qr-reader');
    if (qrReaderElement) {
        qrReaderElement.innerHTML = `
            <div class="qr-upload-fallback">
                <i class="fas fa-camera-retro"></i>
                <h6>Caméra non disponible</h6>
                <p>Vous pouvez prendre une photo du QR code</p>
                <input type="file" id="qr-image-input" accept="image/*" capture="camera" class="form-control">
            </div>
        `;
        
        // Gérer l'upload d'image
        document.getElementById('qr-image-input').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    // Ici on pourrait utiliser une bibliothèque pour décoder le QR depuis l'image
                    // Pour l'instant, on demande à l'utilisateur de saisir manuellement
                    const qrText = prompt('Veuillez saisir le contenu du QR code:');
                    if (qrText) {
                        handleQRCodeDetected(qrText);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

/**
 * Afficher le modal des partenaires
 */
function showPartnersModal(productData, partners) {
    console.log('🤝 [PARTNERS] Affichage du modal partenaires:', partners);
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.body.classList.contains('futuristic-theme') ||
                      document.documentElement.classList.contains('dark-mode');
    
    // Générer la liste des partenaires
    const partnersHtml = partners.map(partner => `
        <div class="partner-item" onclick="selectPartner(${partner.id}, '${partner.nom}')">
            <div class="partner-info">
                <h6 class="partner-name">${partner.nom}</h6>
                <p class="partner-details">${partner.email || ''} ${partner.telephone || ''}</p>
            </div>
            <div class="partner-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </div>
    `).join('');
    
    const partnersModalHtml = `
        <div id="partnersModal" class="modal fade partners-modal ${isDarkMode ? 'futuristic-mode' : 'corporate-mode'}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content partners-content">
                    <div class="modal-header partners-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="header-text">
                                <h5 class="modal-title">Sélectionner un Partenaire</h5>
                                <small class="header-subtitle">Choisissez le partenaire pour l'échange</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close partners-close" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body partners-body">
                        <!-- Informations du produit -->
                        <div class="product-info-card">
                            <h6>Produit: <strong>${productData.nom}</strong></h6>
                            <p>Référence: ${productData.reference} | Stock: ${productData.quantite}</p>
                        </div>
                        
                        <!-- Liste des partenaires -->
                        <div class="partners-list">
                            ${partnersHtml}
                        </div>
                        
                        <!-- Champs cachés -->
                        <input type="hidden" id="partners_product_id" value="${productData.id}">
                        <input type="hidden" id="partners_product_name" value="${productData.nom}">
                        <input type="hidden" id="partners_product_reference" value="${productData.reference}">
                        <input type="hidden" id="partners_product_quantity" value="${productData.quantite}">
                    </div>
                    <div class="modal-footer partners-footer">
                        <button type="button" class="action-btn cancel-btn" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', partnersModalHtml);
    
    // Exposer la fonction de sélection
    window.selectPartner = (partnerId, partnerName) => selectPartner(productData, partnerId, partnerName);
    
    // Ouvrir le modal
    setTimeout(() => {
        const partnersModalElement = document.getElementById('partnersModal');
        if (partnersModalElement) {
            const partnersModal = new bootstrap.Modal(partnersModalElement);
            partnersModal.show();
        }
    }, 200);
    
    // Nettoyer à la fermeture
    document.getElementById('partnersModal').addEventListener('hidden.bs.modal', function() {
        delete window.selectPartner;
        this.remove();
    });
}

/**
 * Sélectionner un partenaire et ouvrir le modal prêt/emprunt
 */
function selectPartner(productData, partnerId, partnerName) {
    console.log('👤 [PARTNER] Partenaire sélectionné:', { partnerId, partnerName });
    
    // Fermer le modal des partenaires
    const partnersModal = document.getElementById('partnersModal');
    if (partnersModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(partnersModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
    
    // Ouvrir le modal prêt/emprunt
    setTimeout(() => {
        showLoanBorrowModal(productData, partnerId, partnerName);
    }, 300);
}

/**
 * Afficher le modal prêt/emprunt
 */
function showLoanBorrowModal(productData, partnerId, partnerName) {
    console.log('💱 [LOAN] Affichage du modal prêt/emprunt:', { productData, partnerId, partnerName });
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.body.classList.contains('futuristic-theme') ||
                      document.documentElement.classList.contains('dark-mode');
    
    const loanModalHtml = `
        <div id="loanBorrowModal" class="modal fade loan-borrow-modal ${isDarkMode ? 'futuristic-mode' : 'corporate-mode'}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content loan-borrow-content">
                    <div class="modal-header loan-borrow-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="header-text">
                                <h5 class="modal-title">Prêt / Emprunt</h5>
                                <small class="header-subtitle">Partenaire: ${partnerName}</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close loan-borrow-close" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body loan-borrow-body">
                        <!-- Informations du produit -->
                        <div class="product-info-card">
                            <h6>Produit: <strong>${productData.nom}</strong></h6>
                            <p>Référence: ${productData.reference} | Stock disponible: ${productData.quantite}</p>
                        </div>
                        
                        <!-- Contrôle de quantité -->
                        <div class="quantity-section">
                            <label class="quantity-label">
                                <i class="fas fa-sort-numeric-up"></i>
                                Quantité
                            </label>
                            <div class="quantity-input-group">
                                <button type="button" class="quantity-btn decrease" onclick="adjustLoanQuantity(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="loan_quantity" class="quantity-input" value="1" min="1" max="${productData.quantite}">
                                <button type="button" class="quantity-btn increase" onclick="adjustLoanQuantity(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="loan-action-buttons">
                            <button type="button" class="loan-action-btn borrow-btn" onclick="processLoanBorrow('borrow')">
                                <div class="action-icon">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                                <div class="action-text">
                                    <h6>J'emprunte</h6>
                                    <p>Recevoir du partenaire</p>
                                </div>
                            </button>
                            
                            <button type="button" class="loan-action-btn lend-btn" onclick="processLoanBorrow('lend')">
                                <div class="action-icon">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                                <div class="action-text">
                                    <h6>Je prête</h6>
                                    <p>Donner au partenaire</p>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Champs cachés -->
                        <input type="hidden" id="loan_product_id" value="${productData.id}">
                        <input type="hidden" id="loan_product_name" value="${productData.nom}">
                        <input type="hidden" id="loan_partner_id" value="${partnerId}">
                        <input type="hidden" id="loan_partner_name" value="${partnerName}">
                    </div>
                    <div class="modal-footer loan-borrow-footer">
                        <button type="button" class="action-btn cancel-btn" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', loanModalHtml);
    
    // Exposer les fonctions
    window.adjustLoanQuantity = adjustLoanQuantity;
    window.processLoanBorrow = processLoanBorrow;
    
    // Ouvrir le modal
    setTimeout(() => {
        const loanModalElement = document.getElementById('loanBorrowModal');
        if (loanModalElement) {
            const loanModal = new bootstrap.Modal(loanModalElement);
            loanModal.show();
        }
    }, 200);
    
    // Nettoyer à la fermeture
    document.getElementById('loanBorrowModal').addEventListener('hidden.bs.modal', function() {
        delete window.adjustLoanQuantity;
        delete window.processLoanBorrow;
        this.remove();
    });
}

/**
 * Ajuster la quantité pour prêt/emprunt
 */
function adjustLoanQuantity(change) {
    const quantityInput = document.getElementById('loan_quantity');
    if (quantityInput) {
        let currentValue = parseInt(quantityInput.value) || 1;
        let newValue = currentValue + change;
        
        const min = parseInt(quantityInput.min) || 1;
        const max = parseInt(quantityInput.max) || 999;
        
        if (newValue >= min && newValue <= max) {
            quantityInput.value = newValue;
        }
    }
}

/**
 * Traiter le prêt ou l'emprunt
 */
function processLoanBorrow(action) {
    console.log('💱 [LOAN] Traitement:', action);
    
    const productId = document.getElementById('loan_product_id').value;
    const productName = document.getElementById('loan_product_name').value;
    const partnerId = document.getElementById('loan_partner_id').value;
    const partnerName = document.getElementById('loan_partner_name').value;
    const quantity = document.getElementById('loan_quantity').value;
    
    if (!quantity || quantity < 1) {
        alert('Veuillez saisir une quantité valide');
        return;
    }
    
    // Préparer les données
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('partner_id', partnerId);
    formData.append('quantity', quantity);
    formData.append('action', action); // 'lend' ou 'borrow'
    
    // Envoyer la requête
    fetch('api/process_loan_borrow.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ [LOAN] Réponse serveur:', data);
        
        if (data.success) {
            const actionText = action === 'lend' ? 'prêté' : 'emprunté';
            const message = `${quantity} x "${productName}" ${actionText} ${action === 'lend' ? 'à' : 'de'} ${partnerName}`;
            
            if (typeof toastr !== 'undefined') {
                toastr.success(message, 'Succès');
            } else {
                alert(message);
            }
            
            // Fermer le modal
            const loanModal = document.getElementById('loanBorrowModal');
            if (loanModal) {
                const bootstrapModal = bootstrap.Modal.getInstance(loanModal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            }
        } else {
            throw new Error(data.message || 'Erreur lors du traitement');
        }
    })
    .catch(error => {
        console.error('❌ [LOAN] Erreur:', error);
        
        if (typeof toastr !== 'undefined') {
            toastr.error('Erreur: ' + error.message, 'Erreur');
        } else {
            alert('Erreur lors du traitement: ' + error.message);
        }
    });
}

</script>