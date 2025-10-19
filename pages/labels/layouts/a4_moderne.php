<?php
/**
 * Layout Moderne - Format A4
 * Design minimaliste et moderne - AVEC COULEURS
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: A4; margin: 0; }
body { width: 210mm; height: 297mm; margin: 0; padding: 20mm; font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.label-a4 { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.label-a4 .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; margin-bottom: 25px; }
.label-a4 .brand { font-size: 42px; font-weight: 900; margin: 0; letter-spacing: 5px; }
.label-a4 .id-badge { display: inline-block; background: white; color: #667eea; padding: 15px 30px; border-radius: 50px; font-size: 28px; font-weight: bold; margin-top: 15px; }
.label-a4 .status-badge { display: inline-block; background: #FFD700; color: #000; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold; margin-left: 15px; }
.label-a4 .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.label-a4 .info-card { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; border-radius: 15px; border-left: 5px solid #667eea; }
.label-a4 .info-label { font-size: 14px; font-weight: bold; color: #667eea; text-transform: uppercase; margin-bottom: 8px; }
.label-a4 .info-value { font-size: 18px; font-weight: 600; color: #333; }
.label-a4 .problem-section { background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%); padding: 20px; border-radius: 15px; margin-bottom: 20px; min-height: 100px; }
.label-a4 .qr-section { text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; color: white; }
@media print { body { background: white; } }
</style>

<div class="label-a4">
    <div class="header">
        <div class="brand">MAISON DU GEEK</div>
        <div>
            <span class="id-badge">RÉPARATION #<?php echo $reparation['id']; ?></span>
            <span class="status-badge"><?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?></span>
        </div>
    </div>
    
    <div class="content-grid">
        <div class="info-card">
            <div class="info-label">👤 Client</div>
            <div class="info-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            <div style="font-size: 16px; color: #666; margin-top: 5px;">📞 <?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-label">📱 Appareil</div>
            <div class="info-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
            <div style="font-size: 16px; color: #666; margin-top: 5px;"><?php echo htmlspecialchars($reparation['modele']); ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-label">📅 Date de Dépôt</div>
            <div class="info-value"><?php echo $date_reception; ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-label">💰 Prix</div>
            <div class="info-value" style="color: #27ae60;">
                <?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label">🔐 Code d'Accès</div>
            <div class="info-value"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-label">📊 Notes Techniques</div>
            <div class="info-value" style="font-size: 16px;">
                <?php echo !empty($reparation['notes_techniques']) ? 'Oui ✓' : 'Non'; ?>
            </div>
        </div>
    </div>
    
    <div class="problem-section">
        <div class="info-label" style="color: #d63031;">⚠️ Description du Problème</div>
        <div style="font-size: 16px; line-height: 1.6; margin-top: 10px; color: #333;">
            <?php echo htmlspecialchars($reparation['description_probleme']); ?>
        </div>
    </div>
    
    <div class="qr-section">
        <div id="qrcode_a4_moderne" style="background: white; display: inline-block; padding: 15px; border-radius: 15px;"></div>
        <div style="font-size: 18px; font-weight: bold; margin-top: 15px;">SCANNEZ POUR SUIVRE VOTRE RÉPARATION</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_moderne"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 150, height: 150, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

