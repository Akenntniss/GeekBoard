<?php
/**
 * Layout Business - Format 4x6" (Imprimante thermique)
 * Design professionnel et structuré - NOIR ET BLANC UNIQUEMENT
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: 4in 6in !important; margin: 0 !important; }
body { width: 4in; height: 6in; margin: 0; padding: 0; font-family: 'Times New Roman', serif; background: white; color: black; }
.label-business { width: 4in; height: 6in; padding: 0.25in; box-sizing: border-box; border: 3px double #000; }
.label-business .header { text-align: center; border-bottom: 2px solid #000; padding: 12px 0; margin-bottom: 12px; }
.label-business .company-name { font-size: 22px; font-weight: bold; margin: 0 0 5px 0; }
.label-business .tagline { font-size: 9px; font-style: italic; margin: 0; }
.label-business .repair-info { background: #000; color: white; padding: 8px; text-align: center; margin-bottom: 12px; font-weight: bold; }
.label-business .section { border: 1px solid #000; padding: 8px; margin-bottom: 8px; }
.label-business .section-title { font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 5px; }
.label-business .section-content { font-size: 11px; }
.label-business .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.label-business .footer { text-align: center; border-top: 1px solid #000; padding-top: 8px; margin-top: 8px; }
@media print { body, .label-business { filter: grayscale(100%); } }
</style>

<div class="label-business">
    <div class="header">
        <div class="company-name">MAISON DU GEEK</div>
        <div class="tagline">Service de Réparation Professionnel</div>
    </div>
    
    <div class="repair-info">
        RÉPARATION N° <?php echo $reparation['id']; ?> - <?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?>
    </div>
    
    <div class="section">
        <div class="section-title">INFORMATIONS CLIENT</div>
        <div class="section-content">
            <strong><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></strong><br>
            Tél: <?php echo htmlspecialchars($reparation['client_telephone']); ?>
        </div>
    </div>
    
    <div class="two-col">
        <div class="section">
            <div class="section-title">APPAREIL</div>
            <div class="section-content" style="font-size: 10px;">
                <?php echo htmlspecialchars($reparation['type_appareil']); ?><br>
                <?php echo htmlspecialchars($reparation['modele']); ?>
            </div>
        </div>
        <div class="section">
            <div class="section-title">DATE DÉPÔT</div>
            <div class="section-content">
                <?php echo $date_reception; ?>
            </div>
        </div>
    </div>
    
    <div class="two-col">
        <div class="section">
            <div class="section-title">CODE ACCÈS</div>
            <div class="section-content">
                <?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Aucun'; ?>
            </div>
        </div>
        <div class="section">
            <div class="section-title">MONTANT</div>
            <div class="section-content">
                <strong><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'N/D'; ?></strong>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">DESCRIPTION PANNE</div>
        <div class="section-content" style="font-size: 10px; line-height: 1.3;">
            <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 120)); ?><?php echo strlen($reparation['description_probleme']) > 120 ? '...' : ''; ?>
        </div>
    </div>
    
    <div class="footer">
        <div id="qrcode_business" style="display: inline-block;"></div>
        <div style="font-size: 9px; margin-top: 5px;">Scannez pour suivre votre réparation</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_business"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 80, height: 80, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

