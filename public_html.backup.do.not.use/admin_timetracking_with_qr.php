<?php
/**
 * Page d'administration du système de pointage avec QR Code
 * Version avec système QR Code intégré
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';
session_start();

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Connexion à la base de données échouée");
    }
} catch (Exception $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Récupérer les données nécessaires pour la page
// ... (code de récupération des données identique à la version complète)

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ GeekBoard Admin - Pointage QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styles existants + nouveaux styles QR */
        .qr-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .qr-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="qrgrid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23qrgrid)"/></svg>');
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .qr-section > * {
            position: relative;
            z-index: 1;
        }
        
        .btn-qr {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-qr:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .qr-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .qr-display {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 2rem;
            text-align: center;
            border-radius: 15px;
        }
        
        .qr-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header et navigation existants -->
    
    <div class="container-fluid py-4">
        <!-- Navigation tabs existante -->
        <ul class="nav nav-tabs nav-tabs-custom mb-4" id="adminTabs" role="tablist">
            <!-- Tous les onglets existants -->
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                    <i class="fas fa-cog"></i> Paramètres
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="adminTabsContent">
            <!-- Onglet Paramètres avec QR Code -->
            <div class="tab-pane fade" id="settings">
                <div class="w-100">
                    
                    <!-- Section QR Code -->
                    <div class="qr-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-3">
                                    <i class="fas fa-qrcode fa-2x me-3"></i>
                                    Pointage QR Code
                                </h3>
                                <p class="lead mb-3">
                                    🎯 Générez un QR Code pour permettre aux employés de pointer facilement avec leur smartphone
                                </p>
                                <div class="qr-info">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                                            <div><small>Pointage Mobile</small></div>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-clock fa-2x mb-2"></i>
                                            <div><small>Temps Réel</small></div>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                            <div><small>Sécurisé</small></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <button class="btn btn-qr btn-lg" onclick="showQRCodeModal()">
                                    <i class="fas fa-qrcode fa-2x d-block mb-2"></i>
                                    <strong>AFFICHER QR CODE</strong>
                                    <div><small>Pour le pointage mobile</small></div>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sections de paramètres existantes -->
                    <!-- ... (toutes les sections existantes : créneaux globaux, créneaux spécifiques, etc.) ... -->
                    
                </div>
            </div>
            
            <!-- Autres onglets existants -->
            <!-- ... -->
        </div>
    </div>
    
    <!-- Modal QR Code -->
    <div class="modal fade qr-modal" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="qrCodeModalLabel">
                        <i class="fas fa-qrcode"></i> QR Code de Pointage Mobile
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-display">
                                <h6 class="mb-3">📱 Scanner avec votre smartphone</h6>
                                <div id="qrcode-container">
                                    <!-- QR Code sera généré ici -->
                                </div>
                                <p class="mt-3 text-muted">
                                    <small>
                                        <i class="fas fa-info-circle"></i>
                                        Scanner ce QR Code pour accéder à la page de pointage mobile
                                    </small>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-mobile-alt"></i> Instructions d'utilisation</h6>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item">
                                    <strong>1. Scanner le QR Code</strong>
                                    <br><small class="text-muted">Utilisez l'appareil photo de votre smartphone</small>
                                </div>
                                <div class="list-group-item">
                                    <strong>2. Ouvrir la page</strong>
                                    <br><small class="text-muted">Cliquez sur le lien qui apparaît</small>
                                </div>
                                <div class="list-group-item">
                                    <strong>3. Pointer</strong>
                                    <br><small class="text-muted">Utilisez les boutons Arrivée/Départ</small>
                                </div>
                                <div class="list-group-item">
                                    <strong>4. Validation automatique</strong>
                                    <br><small class="text-muted">Selon les créneaux horaires configurés</small>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6><i class="fas fa-link"></i> Lien direct</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="qr-link" readonly>
                                    <button class="btn btn-outline-secondary" onclick="copyQRLink()">
                                        <i class="fas fa-copy"></i> Copier
                                    </button>
                                </div>
                                <small class="text-muted">
                                    Vous pouvez aussi partager ce lien directement
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printQRCode()">
                        <i class="fas fa-print"></i> Imprimer QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <script>
        // URL de la page de pointage QR
        const QR_POINTAGE_URL = `${window.location.origin}/pointage_qr.php`;
        
        function showQRCodeModal() {
            // Générer le QR Code
            generateQRCode();
            
            // Remplir le lien
            document.getElementById('qr-link').value = QR_POINTAGE_URL;
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
            modal.show();
        }
        
        async function generateQRCode() {
            const container = document.getElementById('qrcode-container');
            container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Génération...</span></div>';
            
            try {
                // Créer le QR Code
                const canvas = document.createElement('canvas');
                await QRCode.toCanvas(canvas, QR_POINTAGE_URL, {
                    width: 256,
                    height: 256,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    },
                    errorCorrectionLevel: 'M',
                    margin: 2
                });
                
                // Remplacer le spinner par le QR Code
                container.innerHTML = '';
                container.appendChild(canvas);
                
                console.log('✅ QR Code généré pour:', QR_POINTAGE_URL);
                
            } catch (error) {
                console.error('❌ Erreur génération QR Code:', error);
                container.innerHTML = `
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erreur de génération
                    </div>
                `;
            }
        }
        
        function copyQRLink() {
            const linkInput = document.getElementById('qr-link');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // Pour mobile
            
            try {
                document.execCommand('copy');
                
                // Feedback visuel
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
                btn.classList.replace('btn-outline-secondary', 'btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
                
            } catch (err) {
                console.error('Erreur copie:', err);
                alert('Impossible de copier. Sélectionnez et copiez manuellement.');
            }
        }
        
        function printQRCode() {
            const qrCanvas = document.querySelector('#qrcode-container canvas');
            if (!qrCanvas) {
                alert('QR Code non généré');
                return;
            }
            
            // Créer une nouvelle fenêtre pour l'impression
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QR Code Pointage - GeekBoard</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            text-align: center;
                            padding: 2rem;
                        }
                        .qr-container {
                            background: white;
                            padding: 2rem;
                            border: 2px solid #333;
                            border-radius: 10px;
                            display: inline-block;
                            margin: 2rem 0;
                        }
                        canvas {
                            border: 1px solid #ddd;
                        }
                        .instructions {
                            margin-top: 2rem;
                            font-size: 0.9rem;
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h1>📱 Pointage Mobile - GeekBoard</h1>
                    <div class="qr-container">
                        <h2>Scanner ce QR Code</h2>
                        ${qrCanvas.outerHTML}
                        <p><strong>Pointage par smartphone</strong></p>
                    </div>
                    <div class="instructions">
                        <h3>Instructions :</h3>
                        <ol style="text-align: left; display: inline-block;">
                            <li>Ouvrez l'appareil photo de votre smartphone</li>
                            <li>Pointez vers le QR Code</li>
                            <li>Cliquez sur le lien qui apparaît</li>
                            <li>Utilisez les boutons pour pointer</li>
                        </ol>
                        <p><strong>URL directe :</strong> ${QR_POINTAGE_URL}</p>
                    </div>
                    <button class="no-print" onclick="window.print()" style="padding: 1rem 2rem; font-size: 1.1rem; margin: 1rem;">
                        🖨️ Imprimer
                    </button>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            // Lancer l'impression automatiquement
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.print();
                }, 500);
            };
        }
        
        // Générer le QR Code au chargement de la page (pré-cache)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔗 URL de pointage QR:', QR_POINTAGE_URL);
        });
    </script>
</body>
</html>
