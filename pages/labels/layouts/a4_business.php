<?php
/**
 * Layout Business - Format A4
 * Design professionnel et structuré - AVEC COULEURS
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: A4; margin: 0; }
body { width: 210mm; height: 297mm; margin: 0; padding: 0; font-family: 'Georgia', serif; background: #f8f9fa; }
.label-a4-business { background: white; margin: 15mm; padding: 0; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 3px solid #2c3e50; }
.header-business { background: linear-gradient(to right, #2c3e50, #34495e); color: white; padding: 30px; display: flex; justify-content: space-between; align-items: center; }
.company-info { flex: 1; }
.company-name-a4 { font-size: 36px; font-weight: bold; margin: 0; letter-spacing: 2px; }
.company-tagline { font-size: 14px; margin: 5px 0 0 0; opacity: 0.9; }
.repair-badge { background: #e74c3c; padding: 15px 25px; border-radius: 10px; text-align: center; }
.repair-number { font-size: 24px; font-weight: bold; margin: 0; }
.repair-status { font-size: 14px; margin: 5px 0 0 0; }
.main-content { padding: 30px; }
.section-title { background: #ecf0f1; color: #2c3e50; padding: 12px 20px; font-size: 16px; font-weight: bold; border-left: 5px solid #3498db; margin: 20px 0 15px 0; }
.info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.info-table td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
.info-table td:first-child { font-weight: bold; color: #7f8c8d; width: 35%; }
.info-table td:last-child { color: #2c3e50; }
.problem-area { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 10px; margin: 20px 0; }
.footer-section { background: #34495e; color: white; padding: 25px; text-align: center; }
.qr-wrapper { display: inline-block; background: white; padding: 15px; border-radius: 10px; }
</style>

<div class="label-a4-business">
    <div class="header-business">
        <div class="company-info">
            <div class="company-name-a4">MAISON DU GEEK</div>
            <div class="company-tagline">Service de Réparation Professionnel</div>
        </div>
        <div class="repair-badge">
            <div class="repair-number">N° <?php echo str_pad($reparation['id'], 5, '0', STR_PAD_LEFT); ?></div>
            <div class="repair-status"><?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?></div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="section-title">📋 INFORMATIONS CLIENT</div>
        <table class="info-table">
            <tr>
                <td>Nom complet</td>
                <td><strong><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></strong></td>
            </tr>
            <tr>
                <td>Téléphone</td>
                <td><?php echo htmlspecialchars($reparation['client_telephone']); ?></td>
            </tr>
        </table>
        
        <div class="section-title">📱 DÉTAILS DE L'APPAREIL</div>
        <table class="info-table">
            <tr>
                <td>Type d'appareil</td>
                <td><?php echo htmlspecialchars($reparation['type_appareil']); ?></td>
            </tr>
            <tr>
                <td>Modèle</td>
                <td><?php echo htmlspecialchars($reparation['modele']); ?></td>
            </tr>
            <tr>
                <td>Code d'accès</td>
                <td><strong style="color: #e74c3c;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></strong></td>
            </tr>
        </table>
        
        <div class="section-title">📅 INFORMATIONS RÉPARATION</div>
        <table class="info-table">
            <tr>
                <td>Date de dépôt</td>
                <td><?php echo $date_reception; ?></td>
            </tr>
            <tr>
                <td>Statut actuel</td>
                <td><span style="background: #3498db; color: white; padding: 5px 15px; border-radius: 20px;"><?php echo htmlspecialchars($reparation['statut']); ?></span></td>
            </tr>
            <tr>
                <td>Montant estimé</td>
                <td><strong style="color: #27ae60; font-size: 20px;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></strong></td>
            </tr>
        </table>
        
        <div class="problem-area">
            <div style="font-weight: bold; color: #856404; margin-bottom: 10px; font-size: 16px;">⚠️ DESCRIPTION DU PROBLÈME</div>
            <div style="font-size: 15px; line-height: 1.6; color: #333;">
                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
            </div>
        </div>
    </div>
    
    <div class="footer-section">
        <div class="qr-wrapper">
            <div id="qrcode_a4_business"></div>
        </div>
        <div style="font-size: 16px; font-weight: bold; margin-top: 15px;">Scannez pour suivre votre réparation en ligne</div>
        <div style="font-size: 12px; margin-top: 10px; opacity: 0.8;">
            Ou rendez-vous sur notre site web avec le numéro de dossier
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_business"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 140, height: 140, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

