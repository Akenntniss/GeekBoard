<?php
/**
 * Layout Moderne - Format 4x6" (Imprimante thermique)
 * Design minimaliste et moderne - NOIR ET BLANC UNIQUEMENT
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: 4in 6in !important; margin: 0 !important; }
body { width: 4in; height: 6in; margin: 0; padding: 0; font-family: 'Arial', sans-serif; background: white; color: black; }
.label-moderne { width: 4in; height: 6in; padding: 0.3in; box-sizing: border-box; }
.label-moderne .brand { text-align: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
.label-moderne .brand-name { font-size: 24px; font-weight: 900; letter-spacing: 3px; margin: 0; }
.label-moderne .repair-header { display: flex; justify-content: space-between; background: #000; color: white; padding: 8px 12px; margin-bottom: 12px; font-weight: bold; }
.label-moderne .info-block { margin-bottom: 10px; padding: 8px; border-left: 4px solid #000; }
.label-moderne .info-label { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
.label-moderne .info-value { font-size: 13px; font-weight: 500; }
.label-moderne .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; }
.label-moderne .problem-box { background: #f5f5f5; border: 2px solid #000; padding: 8px; margin-bottom: 10px; min-height: 50px; }
.label-moderne .qr-section { text-align: center; padding: 10px; border: 2px solid #000; }
@media print { body, .label-moderne { filter: grayscale(100%); } }
</style>

<div class="label-moderne">
    <div class="brand"><h1 class="brand-name">MAISON DU GEEK</h1></div>
    <div class="repair-header">
        <span>N° <?php echo $reparation['id']; ?></span>
        <span><?php echo htmlspecialchars($reparation['statut']); ?></span>
    </div>
    <div class="info-block">
        <div class="info-label">CLIENT</div>
        <div class="info-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
        <div class="info-value" style="font-size: 12px;"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <div class="info-label">MODÈLE</div>
            <div class="info-value" style="font-size: 11px;"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">DATE</div>
            <div class="info-value"><?php echo $date_reception; ?></div>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <div class="info-label">MOT DE PASSE</div>
            <div class="info-value"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">PRIX</div>
            <div class="info-value"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'N/D'; ?></div>
        </div>
    </div>
    <div class="problem-box">
        <div class="info-label">PROBLÈME</div>
        <div class="info-value" style="font-size: 11px;"><?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 150)); ?></div>
    </div>
    <div class="qr-section">
        <div id="qrcode_moderne"></div>
        <div style="font-size: 10px; margin-top: 5px; font-weight: bold;">SCAN STATUT</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_moderne"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 90, height: 90, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

