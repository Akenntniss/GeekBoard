<?php
/**
 * Layout Startup - Format 4x6" (Imprimante thermique)
 * Design dynamique et créatif - NOIR ET BLANC UNIQUEMENT
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: 4in 6in !important; margin: 0 !important; }
body { width: 4in; height: 6in; margin: 0; padding: 0; font-family: 'Arial', sans-serif; background: white; color: black; }
.label-startup { width: 4in; height: 6in; padding: 0.25in; box-sizing: border-box; position: relative; }
.label-startup .corner-design { position: absolute; width: 30px; height: 30px; border: 3px solid #000; }
.label-startup .corner-tl { top: 10px; left: 10px; border-right: none; border-bottom: none; }
.label-startup .corner-tr { top: 10px; right: 10px; border-left: none; border-bottom: none; }
.label-startup .corner-bl { bottom: 10px; left: 10px; border-right: none; border-top: none; }
.label-startup .corner-br { bottom: 10px; right: 10px; border-left: none; border-top: none; }
.label-startup .brand { text-align: center; margin: 20px 0 15px 0; }
.label-startup .brand-name { font-size: 26px; font-weight: 900; margin: 0; letter-spacing: 2px; }
.label-startup .id-badge { background: #000; color: white; padding: 10px; text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 12px; transform: skewX(-5deg); }
.label-startup .info-card { background: #f0f0f0; border: 2px solid #000; padding: 8px; margin-bottom: 8px; transform: skewX(-2deg); }
.label-startup .info-card-inner { transform: skewX(2deg); }
.label-startup .label-text { font-size: 9px; font-weight: bold; text-transform: uppercase; }
.label-startup .value-text { font-size: 12px; font-weight: 600; margin-top: 2px; }
.label-startup .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.label-startup .qr-area { text-align: center; padding: 10px; border: 3px solid #000; margin-top: 10px; }
@media print { body, .label-startup { filter: grayscale(100%); } }
</style>

<div class="label-startup">
    <div class="corner-design corner-tl"></div>
    <div class="corner-design corner-tr"></div>
    <div class="corner-design corner-bl"></div>
    <div class="corner-design corner-br"></div>
    
    <div class="brand">
        <div class="brand-name">MAISON DU GEEK</div>
    </div>
    
    <div class="id-badge">
        #<?php echo $reparation['id']; ?> • <?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?>
    </div>
    
    <div class="info-card">
        <div class="info-card-inner">
            <div class="label-text">👤 Client</div>
            <div class="value-text"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            <div style="font-size: 11px; margin-top: 2px;">📞 <?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
        </div>
    </div>
    
    <div class="grid-2">
        <div class="info-card">
            <div class="info-card-inner">
                <div class="label-text">📱 Appareil</div>
                <div class="value-text" style="font-size: 10px;"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?></div>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-inner">
                <div class="label-text">📅 Date</div>
                <div class="value-text"><?php echo $date_reception; ?></div>
            </div>
        </div>
    </div>
    
    <div class="grid-2">
        <div class="info-card">
            <div class="info-card-inner">
                <div class="label-text">🔐 Code</div>
                <div class="value-text"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'N/A'; ?></div>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-inner">
                <div class="label-text">💰 Prix</div>
                <div class="value-text"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . '€' : 'N/D'; ?></div>
            </div>
        </div>
    </div>
    
    <div class="info-card" style="min-height: 50px;">
        <div class="info-card-inner">
            <div class="label-text">⚠️ Problème</div>
            <div style="font-size: 10px; margin-top: 3px; line-height: 1.3;">
                <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 100)); ?>
            </div>
        </div>
    </div>
    
    <div class="qr-area">
        <div id="qrcode_startup" style="display: inline-block;"></div>
        <div style="font-size: 10px; font-weight: bold; margin-top: 5px;">SCAN ME!</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_startup"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 85, height: 85, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

