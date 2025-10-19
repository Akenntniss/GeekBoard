<?php
/**
 * Layout Professional - Format 4x6" (Imprimante thermique)
 * Design classique et élégant - NOIR ET BLANC UNIQUEMENT
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: 4in 6in !important; margin: 0 !important; }
body { width: 4in; height: 6in; margin: 0; padding: 0; font-family: 'Georgia', serif; background: white; color: black; }
.label-pro { width: 4in; height: 6in; padding: 0.35in; box-sizing: border-box; border: 1px solid #000; }
.label-pro .header-box { text-align: center; padding: 12px; border: 2px solid #000; margin-bottom: 15px; }
.label-pro .company { font-size: 20px; font-weight: bold; letter-spacing: 4px; margin: 0; }
.label-pro .subtitle { font-size: 10px; margin: 5px 0 0 0; font-style: italic; }
.label-pro .repair-number { text-align: center; font-size: 18px; font-weight: bold; padding: 8px; background: #000; color: white; margin-bottom: 12px; }
.label-pro .data-row { display: flex; border-bottom: 1px solid #000; padding: 6px 0; }
.label-pro .data-label { width: 35%; font-size: 10px; font-weight: bold; }
.label-pro .data-value { width: 65%; font-size: 11px; }
.label-pro .description-box { border: 1px solid #000; padding: 8px; margin: 10px 0; min-height: 55px; }
.label-pro .description-title { font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 5px; }
.label-pro .description-text { font-size: 10px; line-height: 1.4; }
.label-pro .qr-container { text-align: center; padding-top: 10px; border-top: 2px solid #000; }
@media print { body, .label-pro { filter: grayscale(100%); } }
</style>

<div class="label-pro">
    <div class="header-box">
        <div class="company">MAISON DU GEEK</div>
        <div class="subtitle">Centre de Réparation Électronique</div>
    </div>
    
    <div class="repair-number">
        DOSSIER N° <?php echo str_pad($reparation['id'], 5, '0', STR_PAD_LEFT); ?>
    </div>
    
    <div class="data-row">
        <div class="data-label">CLIENT:</div>
        <div class="data-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
    </div>
    
    <div class="data-row">
        <div class="data-label">TÉLÉPHONE:</div>
        <div class="data-value"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
    </div>
    
    <div class="data-row">
        <div class="data-label">APPAREIL:</div>
        <div class="data-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
    </div>
    
    <div class="data-row">
        <div class="data-label">MODÈLE:</div>
        <div class="data-value"><?php echo htmlspecialchars($reparation['modele']); ?></div>
    </div>
    
    <div class="data-row">
        <div class="data-label">DATE DÉPÔT:</div>
        <div class="data-value"><?php echo $date_reception; ?></div>
    </div>
    
    <div class="data-row">
        <div class="data-label">CODE:</div>
        <div class="data-value"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non renseigné'; ?></div>
    </div>
    
    <div class="data-row" style="border-bottom: 2px solid #000;">
        <div class="data-label">STATUT:</div>
        <div class="data-value"><strong><?php echo htmlspecialchars($reparation['statut']); ?></strong></div>
    </div>
    
    <div class="data-row" style="border-bottom: 2px solid #000;">
        <div class="data-label">MONTANT:</div>
        <div class="data-value"><strong><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></strong></div>
    </div>
    
    <div class="description-box">
        <div class="description-title">DESCRIPTION DE LA PANNE</div>
        <div class="description-text">
            <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 140)); ?><?php echo strlen($reparation['description_probleme']) > 140 ? '...' : ''; ?>
        </div>
    </div>
    
    <div class="qr-container">
        <div id="qrcode_pro" style="display: inline-block;"></div>
        <div style="font-size: 9px; margin-top: 5px;">Suivi en ligne</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_pro"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 75, height: 75, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

