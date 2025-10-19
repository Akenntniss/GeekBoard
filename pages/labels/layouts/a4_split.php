<?php
/**
 * Layout Split - Format A4 à découper
 * 75% CLIENT (Confirmation de dépôt) + 25% ATELIER (Infos confidentielles)
 * AVEC COULEURS
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: A4; margin: 0; }
body { width: 210mm; height: 297mm; margin: 0; padding: 0; font-family: 'Arial', sans-serif; background: white; }
.split-container { width: 100%; height: 100%; display: flex; flex-direction: column; }

/* PARTIE CLIENT (75%) */
.client-section { height: 75%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; box-sizing: border-box; position: relative; }
.client-content { background: white; height: 100%; border-radius: 15px; padding: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
.client-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
.client-title { font-size: 32px; font-weight: bold; margin: 0; letter-spacing: 3px; }
.client-subtitle { font-size: 16px; margin: 8px 0 0 0; }
.confirmation-badge { background: #FFD700; color: #000; display: inline-block; padding: 10px 20px; border-radius: 25px; font-weight: bold; font-size: 18px; margin-top: 10px; }
.client-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
.client-card { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 15px; border-radius: 10px; border-left: 4px solid #667eea; }
.card-label { font-size: 12px; font-weight: bold; color: #667eea; text-transform: uppercase; margin-bottom: 5px; }
.card-value { font-size: 16px; font-weight: 600; color: #333; }
.qr-section-client { text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; color: white; margin-top: 15px; }

/* LIGNE DE DÉCOUPE */
.cut-line { height: 15px; background: white; border-top: 2px dashed #999; border-bottom: 2px dashed #999; display: flex; align-items: center; justify-content: center; position: relative; }
.cut-icon { background: white; padding: 5px 15px; font-size: 18px; z-index: 10; }

/* PARTIE ATELIER (25%) */
.workshop-section { height: calc(25% - 15px); background: #2c3e50; padding: 15px; box-sizing: border-box; color: white; }
.workshop-header { font-size: 20px; font-weight: bold; margin-bottom: 12px; text-align: center; background: #e74c3c; padding: 10px; border-radius: 8px; }
.workshop-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.workshop-info { background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; }
.workshop-label { font-size: 10px; text-transform: uppercase; opacity: 0.8; margin-bottom: 3px; }
.workshop-value { font-size: 14px; font-weight: bold; }
.confidential-note { background: #e74c3c; padding: 8px; border-radius: 5px; text-align: center; margin-top: 10px; font-size: 11px; font-weight: bold; }
</style>

<div class="split-container">
    <!-- PARTIE CLIENT (75%) -->
    <div class="client-section">
        <div class="client-content">
            <div class="client-header">
                <div class="client-title">MAISON DU GEEK</div>
                <div class="client-subtitle">Confirmation de Dépôt d'Appareil</div>
                <div class="confirmation-badge">DOSSIER #<?php echo $reparation['id']; ?></div>
            </div>
            
            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                <div style="font-size: 18px; font-weight: bold; color: #856404;">✓ Votre appareil a bien été déposé</div>
                <div style="font-size: 14px; color: #856404; margin-top: 5px;">Conservez ce document</div>
            </div>
            
            <div class="client-info-grid">
                <div class="client-card">
                    <div class="card-label">👤 Client</div>
                    <div class="card-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                </div>
                
                <div class="client-card">
                    <div class="card-label">📱 Appareil</div>
                    <div class="card-value" style="font-size: 14px;"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                    <div style="font-size: 12px; color: #666; margin-top: 3px;"><?php echo htmlspecialchars($reparation['modele']); ?></div>
                </div>
                
                <div class="client-card">
                    <div class="card-label">📅 Date de Dépôt</div>
                    <div class="card-value"><?php echo $date_reception; ?></div>
                </div>
                
                <div class="client-card">
                    <div class="card-label">📊 Statut</div>
                    <div class="card-value"><?php echo htmlspecialchars($reparation['statut']); ?></div>
                </div>
                
                <div class="client-card" style="grid-column: 1 / -1;">
                    <div class="card-label">⚠️ Problème Signalé</div>
                    <div style="font-size: 13px; margin-top: 5px; line-height: 1.4; color: #666;">
                        <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 200)); ?><?php echo strlen($reparation['description_probleme']) > 200 ? '...' : ''; ?>
                    </div>
                </div>
            </div>
            
            <div class="qr-section-client">
                <div style="background: white; display: inline-block; padding: 12px; border-radius: 10px;">
                    <div id="qrcode_client"></div>
                </div>
                <div style="font-size: 14px; font-weight: bold; margin-top: 10px;">SUIVEZ VOTRE RÉPARATION EN LIGNE</div>
                <div style="font-size: 11px; margin-top: 5px; opacity: 0.9;">Scannez ce QR code avec votre smartphone</div>
            </div>
        </div>
    </div>
    
    <!-- LIGNE DE DÉCOUPE -->
    <div class="cut-line">
        <span class="cut-icon">✂️ DÉCOUPER ICI ✂️ DÉCOUPER ICI ✂️ DÉCOUPER ICI ✂️</span>
    </div>
    
    <!-- PARTIE ATELIER (25%) - CONFIDENTIEL -->
    <div class="workshop-section">
        <div class="workshop-header">
            🔒 PARTIE ATELIER - CONFIDENTIEL 🔒
        </div>
        
        <div class="workshop-grid">
            <div class="workshop-info">
                <div class="workshop-label">Dossier N°</div>
                <div class="workshop-value"><?php echo $reparation['id']; ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">Client</div>
                <div class="workshop-value" style="font-size: 12px;"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">📞 Téléphone</div>
                <div class="workshop-value" style="font-size: 12px;"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">🔐 Code Accès</div>
                <div class="workshop-value" style="color: #FFD700;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'AUCUN'; ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">💰 Prix</div>
                <div class="workshop-value" style="color: #2ecc71;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'N/D'; ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">📱 Modèle</div>
                <div class="workshop-value" style="font-size: 11px;"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?></div>
            </div>
        </div>
        
        <div class="confidential-note">
            ⚠️ DOCUMENT INTERNE - NE PAS COMMUNIQUER AU CLIENT ⚠️
        </div>
        
        <?php if (!empty($reparation['notes_techniques'])): ?>
        <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 5px; margin-top: 8px;">
            <div style="font-size: 10px; font-weight: bold; margin-bottom: 3px;">📝 Notes Techniques:</div>
            <div style="font-size: 11px; line-height: 1.3;">
                <?php echo htmlspecialchars(substr($reparation['notes_techniques'], 0, 150)); ?><?php echo strlen($reparation['notes_techniques']) > 150 ? '...' : ''; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_client"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 100, height: 100, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

