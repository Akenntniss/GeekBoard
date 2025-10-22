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
/* STYLES SCANNER UNIVERSEL MODERNE ADAPTATIF */
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

/* Modal principal */
.modern-scanner-modal {
    border-radius: 24px !important;
    overflow: hidden;
    background: var(--scanner-bg);
    border: 2px solid var(--scanner-border);
    box-shadow: 
        0 25px 50px -12px var(--scanner-shadow),
        0 0 50px var(--scanner-glow);
}

/* Header du scanner */
.scanner-header {
    background: linear-gradient(135deg, var(--scanner-primary), var(--scanner-secondary));
    position: relative;
    overflow: hidden;
    padding: 1.5rem;
}

.scanner-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 2;
}

.scanner-title-group {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.scanner-icon-wrapper {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.scanner-pulse-icon {
    font-size: 1.8rem;
    color: white;
    animation: scannerPulse 2s ease-in-out infinite;
}

@keyframes scannerPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.scanner-title-text h5 {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.scanner-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 400;
}

.scanner-close-btn {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.scanner-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

/* Particules animées */
.scanner-particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    animation: particleFloat 3s ease-in-out infinite;
}

@keyframes particleFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.6; }
    50% { transform: translateY(-20px) scale(1.2); opacity: 1; }
}

/* Body du scanner */
.scanner-body {
    background: var(--scanner-bg);
    position: relative;
}

/* Grille cyber en arrière-plan */
.cyber-grid-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.1;
    background-image: 
        linear-gradient(var(--scanner-primary) 1px, transparent 1px),
        linear-gradient(90deg, var(--scanner-primary) 1px, transparent 1px);
    background-size: 20px 20px;
    animation: gridMove 20s linear infinite;
    pointer-events: none;
}

@keyframes gridMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(20px, 20px); }
}

/* Sélecteur de mode moderne */
.scan-mode-selector-modern {
    background: var(--scanner-surface);
    border-bottom: 1px solid var(--scanner-border);
}

.scan-mode-title {
    color: var(--scanner-text);
    font-weight: 600;
    margin-bottom: 1rem;
}

.scan-modes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

@media (max-width: 768px) {
    .scan-modes-grid {
        grid-template-columns: 1fr;
    }
}

.scan-mode-card {
    position: relative;
}

.scan-mode-input {
    display: none;
}

.scan-mode-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--scanner-bg);
    border: 2px solid var(--scanner-border);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.scan-mode-label:hover {
    border-color: var(--scanner-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--scanner-shadow);
}

.scan-mode-input:checked + .scan-mode-label {
    border-color: var(--scanner-primary);
    background: linear-gradient(135deg, var(--scanner-primary), var(--scanner-secondary));
    color: white;
    box-shadow: 0 0 30px var(--scanner-glow);
}

.scan-mode-icon {
    width: 50px;
    height: 50px;
    background: var(--scanner-surface);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--scanner-primary);
    transition: all 0.3s ease;
}

.scan-mode-input:checked + .scan-mode-label .scan-mode-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: scale(1.1);
}

.scan-mode-content {
    flex: 1;
}

.scan-mode-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: var(--scanner-text);
}

.scan-mode-desc {
    font-size: 0.9rem;
    color: var(--scanner-text-secondary);
    margin: 0;
}

.scan-mode-input:checked + .scan-mode-label .scan-mode-name,
.scan-mode-input:checked + .scan-mode-label .scan-mode-desc {
    color: white;
}

.scan-mode-indicator {
    width: 20px;
    height: 20px;
    border: 2px solid var(--scanner-border);
    border-radius: 50%;
    position: relative;
    transition: all 0.3s ease;
}

.scan-mode-input:checked + .scan-mode-label .scan-mode-indicator {
    border-color: white;
    background: white;
}

.scan-mode-input:checked + .scan-mode-label .scan-mode-indicator::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 8px;
    height: 8px;
    background: var(--scanner-primary);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

/* Container du scanner */
.scanner-container-modern {
    position: relative;
    height: 400px;
    background: #000;
    overflow: hidden;
}

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

/* Modal principal avec design moderne */
#universal_scanner_modal .modal-content {
    border-radius: 24px !important;
    overflow: hidden;
    background: var(--scanner-bg) !important;
    border: 2px solid var(--scanner-border) !important;
    box-shadow: 
        0 25px 50px -12px var(--scanner-shadow),
        0 0 50px var(--scanner-glow) !important;
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
<!-- MODAL: SCANNER UNIVERSEL - DESIGN MODERNE ADAPTATIF -->
<!-- ========================================= -->
<div class="modal fade" id="universal_scanner_modal" tabindex="-1" aria-labelledby="universal_scanner_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg modern-scanner-modal">
            <!-- Header avec design adaptatif -->
            <div class="modal-header border-0 scanner-header">
                <div class="scanner-header-content">
                    <div class="scanner-title-group">
                        <div class="scanner-icon-wrapper">
                            <i class="fas fa-qrcode scanner-pulse-icon"></i>
                        </div>
                        <div class="scanner-title-text">
                            <h5 class="modal-title fw-bold mb-0" id="universal_scanner_modal_label">
                                Scanner Universel
                            </h5>
                            <p class="scanner-subtitle mb-0">Technologie de scan avancée</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close scanner-close-btn" data-bs-dismiss="modal" aria-label="Close" onclick="stopUniversalScanner()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Particules d'arrière-plan -->
                <div class="scanner-particles">
                    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
                    <div class="particle" style="left: 20%; animation-delay: 0.5s;"></div>
                    <div class="particle" style="left: 30%; animation-delay: 1s;"></div>
                    <div class="particle" style="left: 40%; animation-delay: 1.5s;"></div>
                    <div class="particle" style="left: 50%; animation-delay: 2s;"></div>
                    <div class="particle" style="left: 60%; animation-delay: 0.3s;"></div>
                    <div class="particle" style="left: 70%; animation-delay: 0.8s;"></div>
                    <div class="particle" style="left: 80%; animation-delay: 1.3s;"></div>
                    <div class="particle" style="left: 90%; animation-delay: 1.8s;"></div>
                </div>
            </div>
            
            <div class="modal-body p-0 position-relative scanner-body">
                <!-- Grille cyber en arrière-plan -->
                <div class="cyber-grid-background"></div>
                
                <!-- Sélecteur de mode de scan moderne -->
                <div class="scan-mode-selector-modern p-4">
                    <h6 class="scan-mode-title mb-3">
                        <i class="fas fa-cogs me-2"></i>
                        Mode de Scan
                    </h6>
                    <div class="scan-modes-grid">
                        <div class="scan-mode-card" data-mode="auto">
                            <input type="radio" class="scan-mode-input" name="scanMode" id="modeAuto" value="auto" checked>
                            <label class="scan-mode-label" for="modeAuto">
                                <div class="scan-mode-icon">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <div class="scan-mode-content">
                                    <h6 class="scan-mode-name">Scan Universel</h6>
                                    <p class="scan-mode-desc">QR Code + Code-barres</p>
                                </div>
                                <div class="scan-mode-indicator"></div>
                            </label>
                        </div>
                        
                        <div class="scan-mode-card" data-mode="barcode">
                            <input type="radio" class="scan-mode-input" name="scanMode" id="modeBarcode" value="barcode">
                            <label class="scan-mode-label" for="modeBarcode">
                                <div class="scan-mode-icon">
                                    <i class="fas fa-barcode"></i>
                                </div>
                                <div class="scan-mode-content">
                                    <h6 class="scan-mode-name">Code-barres</h6>
                                    <p class="scan-mode-desc">Uniquement codes-barres</p>
                                </div>
                                <div class="scan-mode-indicator"></div>
                            </label>
                        </div>
                        
                        <div class="scan-mode-card" data-mode="qr">
                            <input type="radio" class="scan-mode-input" name="scanMode" id="modeQR" value="qr">
                            <label class="scan-mode-label" for="modeQR">
                                <div class="scan-mode-icon">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <div class="scan-mode-content">
                                    <h6 class="scan-mode-name">QR Code</h6>
                                    <p class="scan-mode-desc">Uniquement QR codes</p>
                                </div>
                                <div class="scan-mode-indicator"></div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Scanner Video avec overlay moderne -->
                <div class="scanner-container-modern">
                    <video id="universal_scanner_video" autoplay muted playsinline></video>
                    
                    <!-- Overlay de scan moderne -->
                    <div class="scanner-overlay-modern">
                        <!-- Cadre de scan avec animations -->
                        <div class="scanner-frame-modern">
                            <div class="scanner-corner top-left"></div>
                            <div class="scanner-corner top-right"></div>
                            <div class="scanner-corner bottom-left"></div>
                            <div class="scanner-corner bottom-right"></div>
                            <div class="scanner-line-modern"></div>
                        </div>
                        
                        <!-- Indicateurs de performance -->
                        <div class="scanner-performance">
                            <div class="performance-indicator">
                                <div class="performance-bar"></div>
                                <span class="performance-label">Signal</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status moderne -->
                    <div class="scanner-status-modern" id="universal_scanner_status">
                        <div class="status-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="status-text">
                            <span class="status-primary">Scanner Actif</span>
                            <span class="status-secondary">Positionnez le code dans le cadre</span>
                        </div>
                    </div>
                </div>
                
                <!-- Contrôles modernes -->
                <div class="scanner-controls-modern p-4">
                    <div class="controls-grid">
                        <button class="control-btn flash-btn" onclick="toggleScannerFlash()">
                            <div class="control-icon">
                                <i class="fas fa-flashlight" id="flashIcon"></i>
                            </div>
                            <span class="control-label">Flash</span>
                            <div class="control-indicator" id="flashIndicator"></div>
                        </button>
                        
                        <button class="control-btn camera-btn" onclick="switchCamera()">
                            <div class="control-icon">
                                <i class="fas fa-camera-rotate"></i>
                            </div>
                            <span class="control-label">Caméra</span>
                        </button>
                        
                        <button class="control-btn manual-btn" onclick="manualCodeEntry()">
                            <div class="control-icon">
                                <i class="fas fa-keyboard"></i>
                            </div>
                            <span class="control-label">Manuel</span>
                        </button>
                        
                        <button class="control-btn settings-btn" onclick="openScannerSettings()">
                            <div class="control-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <span class="control-label">Réglages</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Footer avec informations techniques -->
            <div class="modal-footer border-0 scanner-footer">
                <div class="scanner-info">
                    <div class="info-item">
                        <i class="fas fa-microchip me-1"></i>
                        <span id="scannerEngine">Engine: Auto</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        <span id="scannerFPS">FPS: --</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-crosshairs me-1"></i>
                        <span id="scannerResolution">Résolution: --</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: MENU NAVIGATION - DESIGN FUTURISTE -->
<!-- Ancien menu navigation modal supprimé - remplacé par futuristicMenuModal -->
<!-- <div class="modal fade" id="menu_navigation_modal" tabindex="-1" aria-labelledby="menu_navigation_modal_label" aria-hidden="true">
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

                    <!-- MENU REORGANISE SELON DEMANDE -->
                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-layer-group me-2"></i>Gestion Principale</h6></div>
                    <div class="curated-row">
                        <a href="index.php" class="modern-nav-card home-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-home"><i class="fas fa-home"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Accueil</h6><p class="nav-subtitle">Tableau de bord</p></div>
                        </a>
                        <a href="index.php?page=reparations" class="modern-nav-card repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-repair"><i class="fas fa-tools"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Réparations</h6><p class="nav-subtitle">Gestion</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=taches" class="modern-nav-card tasks-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-tasks"><i class="fas fa-tasks"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Tâches</h6><p class="nav-subtitle">Gestion</p></div>
                        </a>
                        <a href="index.php?page=commandes_pieces" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-orders"><i class="fas fa-shopping-cart"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Commandes</h6><p class="nav-subtitle">Pièces</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=rachat_appareils" class="modern-nav-card rachat-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-rachat"><i class="fas fa-exchange-alt"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Rachats</h6><p class="nav-subtitle">Appareils</p></div>
                        </a>
                        <a href="index.php?page=inventaire" class="modern-nav-card inventory-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-orders"><i class="fas fa-boxes"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Inventaire</h6><p class="nav-subtitle">Stock</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=base_connaissances" class="modern-nav-card knowledge-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-knowledge"><i class="fas fa-book"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Base de connaissance</h6><p class="nav-subtitle">Documentation</p></div>
                        </a>
                        <a href="index.php?page=mes_missions" class="modern-nav-card my-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-my-missions"><i class="fas fa-clipboard-check"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Mes missions</h6><p class="nav-subtitle">Personnel</p></div>
                        </a>
                    </div>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-comments me-2"></i>Communication</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=sms_historique" class="modern-nav-card sms-history-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-history"><i class="fas fa-history"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Historique SMS</h6><p class="nav-subtitle">Envois</p></div>
                        </a>
                        <a href="index.php?page=campagne_sms" class="modern-nav-card sms-campaign-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-history"><i class="fas fa-bullhorn"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Campagne SMS</h6><p class="nav-subtitle">Marketing</p></div>
                        </a>
                    </div>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-shield-alt me-2"></i>Administration</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=employes" class="modern-nav-card employees-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-employees"><i class="fas fa-user-tie"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Employé</h6><p class="nav-subtitle">Gestion</p></div>
                        </a>
                        <a href="index.php?page=reparation_logs" class="modern-nav-card logs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logs"><i class="fas fa-clipboard-list"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Journaux réparation</h6><p class="nav-subtitle">Logs</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=presence_gestion" class="modern-nav-card absences-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-absences"><i class="fas fa-user-clock"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Absence et retard</h6><p class="nav-subtitle">Présence</p></div>
                        </a>
                        <a href="index.php?page=admin_timetracking" class="modern-nav-card timetracking-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-administration"><i class="fas fa-clock"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Pointage Admin</h6><p class="nav-subtitle">Suivi</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=kpi_dashboard" class="modern-nav-card kpi-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-special"><i class="fas fa-chart-line"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">KPI Dashboard</h6><p class="nav-subtitle">Statistiques</p></div>
                        </a>
                        <a href="index.php?page=admin_missions" class="modern-nav-card admin-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-admin-missions"><i class="fas fa-tasks"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Mission Admin</h6><p class="nav-subtitle">Gestion</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=bug-reports" class="modern-nav-card bug-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logout"><i class="fas fa-bug"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Signalement de bug</h6><p class="nav-subtitle">Qualité</p></div>
                        </a>
                        <a href="index.php?page=parametre" class="modern-nav-card settings-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-special"><i class="fas fa-cog"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Paramètre</h6><p class="nav-subtitle">Configuration</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=clients" class="modern-nav-card clients-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-clients"><i class="fas fa-users"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Client</h6><p class="nav-subtitle">Base clients</p></div>
                        </a>
                        <a href="index.php?action=logout" class="modern-nav-card logout-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logout"><i class="fas fa-sign-out-alt"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Déconnexion</h6><p class="nav-subtitle">Sortie</p></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
