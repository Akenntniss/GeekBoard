<?php
/**
 * Layout Professional - Format A4
 * Design classique et élégant - AVEC COULEURS
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>
<style>
@page { size: A4; margin: 0; }
body { width: 210mm; height: 297mm; margin: 0; padding: 0; font-family: 'Georgia', serif; background: #2c3e50; }
.label-a4-pro { background: white; margin: 15mm; padding: 0; border: 2px solid #34495e; }
.letterhead { background: #34495e; color: white; padding: 35px 40px; border-bottom: 4px solid #3498db; }
.company-header { display: flex; justify-content: space-between; align-items: center; }
.company-title { font-size: 40px; font-weight: bold; letter-spacing: 3px; }
.company-subtitle { font-size: 14px; margin-top: 8px; opacity: 0.9; letter-spacing: 2px; }
.document-type { background: #3498db; padding: 12px 25px; border-radius: 5px; text-align: center; font-weight: bold; font-size: 14px; }
.repair-header { background: #ecf0f1; padding: 25px 40px; border-bottom: 3px solid #3498db; }
.repair-title { font-size: 24px; font-weight: bold; color: #2c3e50; }
.repair-id { color: #3498db; }
.content-pro { padding: 30px 40px; }
.data-section { margin-bottom: 25px; }
.section-header { font-size: 14px; font-weight: bold; color: #3498db; text-transform: uppercase; border-bottom: 2px solid #ecf0f1; padding-bottom: 8px; margin-bottom: 15px; letter-spacing: 1px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table tr { border-bottom: 1px solid #ecf0f1; }
.data-table td { padding: 12px 0; }
.data-table td:first-child { font-weight: 600; color: #7f8c8d; width: 40%; }
.data-table td:last-child { color: #2c3e50; }
.highlight-box { background: linear-gradient(to right, #e8f4f8, #ffffff); border-left: 4px solid #3498db; padding: 20px; margin: 20px 0; }
.footer-pro { background: #34495e; padding: 30px 40px; text-align: center; color: white; }
.qr-container-pro { background: white; display: inline-block; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
</style>

<div class="label-a4-pro">
    <div class="letterhead">
        <div class="company-header">
            <div>
                <div class="company-title">MAISON DU GEEK</div>
                <div class="company-subtitle">CENTRE DE RÉPARATION ÉLECTRONIQUE</div>
            </div>
            <div class="document-type">
                DOSSIER<br>TECHNIQUE
            </div>
        </div>
    </div>
    
    <div class="repair-header">
        <div class="repair-title">
            Dossier de Réparation <span class="repair-id">N° <?php echo str_pad($reparation['id'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div style="margin-top: 8px; color: #7f8c8d;">
            Statut actuel: <span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px;"><?php echo htmlspecialchars($reparation['statut']); ?></span>
        </div>
    </div>
    
    <div class="content-pro">
        <div class="data-section">
            <div class="section-header">Informations Client</div>
            <table class="data-table">
                <tr>
                    <td>Nom et Prénom</td>
                    <td><strong><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></strong></td>
                </tr>
                <tr>
                    <td>Numéro de téléphone</td>
                    <td><?php echo htmlspecialchars($reparation['client_telephone']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="data-section">
            <div class="section-header">Caractéristiques de l'Appareil</div>
            <table class="data-table">
                <tr>
                    <td>Type d'appareil</td>
                    <td><?php echo htmlspecialchars($reparation['type_appareil']); ?></td>
                </tr>
                <tr>
                    <td>Modèle exact</td>
                    <td><?php echo htmlspecialchars($reparation['modele']); ?></td>
                </tr>
                <tr>
                    <td>Code d'accès / PIN</td>
                    <td><strong style="color: #e74c3c;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non communiqué'; ?></strong></td>
                </tr>
            </table>
        </div>
        
        <div class="data-section">
            <div class="section-header">Informations Administratives</div>
            <table class="data-table">
                <tr>
                    <td>Date de dépôt</td>
                    <td><?php echo $date_reception; ?></td>
                </tr>
                <tr>
                    <td>Montant de la réparation</td>
                    <td><strong style="color: #27ae60; font-size: 18px;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' € TTC' : 'Devis en cours'; ?></strong></td>
                </tr>
                <tr>
                    <td>Notes techniques présentes</td>
                    <td><?php echo !empty($reparation['notes_techniques']) ? '<span style="color: #27ae60;">✓ Oui</span>' : '<span style="color: #95a5a6;">✗ Non</span>'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="highlight-box">
            <div style="font-weight: bold; color: #2c3e50; margin-bottom: 12px; font-size: 16px;">
                📋 Description détaillée du problème
            </div>
            <div style="font-size: 15px; line-height: 1.7; color: #34495e;">
                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
            </div>
        </div>
    </div>
    
    <div class="footer-pro">
        <div class="qr-container-pro">
            <div id="qrcode_a4_pro"></div>
        </div>
        <div style="font-size: 16px; font-weight: bold; margin-top: 20px;">
            Suivi en Ligne de Votre Réparation
        </div>
        <div style="font-size: 12px; margin-top: 8px; opacity: 0.8;">
            Scannez ce code QR ou utilisez le numéro de dossier sur notre site web
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_pro"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 135, height: 135, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

