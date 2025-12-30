<?php
/**
 * Layout Business - Format A4
 * Design professionnel et structuré - ÉCONOMIE D'ENCRE avec touches de couleur
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$repair_number = str_pad($reparation['id'], 5, '0', STR_PAD_LEFT);

// Récupérer les infos entreprise (passées depuis imprimer_etiquette.php)
$company_name = $reparation['company_name'] ?? 'MAISON DU GEEK';
$company_phone = $reparation['company_phone'] ?? '';
$company_address = $reparation['company_address'] ?? '';
?>
<style>
@page { size: A4; margin: 0; }
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

body { 
    width: 210mm; 
    height: 297mm; 
    margin: 0; 
    padding: 0; 
    font-family: 'Inter', 'Arial', sans-serif; 
    background: white;
    color: #1a1a1a;
}

.label-a4-business { 
    background: white; 
    margin: 12mm; 
    padding: 0; 
    border: 2px solid #1e3a5f;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    min-height: calc(297mm - 24mm);
    overflow: hidden;
}

/* En-tête */
.header-business { 
    border-bottom: 3px solid #1e3a5f;
    padding: 25px 30px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    background: linear-gradient(to right, #f0f4f8, white);
}
.company-info { }
.company-name-a4 { 
    font-size: 30px; 
    font-weight: 800; 
    margin: 0; 
    letter-spacing: 2px;
    color: #1e3a5f;
}
.company-tagline { 
    font-size: 12px; 
    margin: 5px 0 0 0; 
    color: #4a5568;
    font-weight: 600;
    letter-spacing: 1px;
}
.company-contact {
    font-size: 11px;
    margin-top: 6px;
    color: #666;
}
.repair-badge { 
    border: 3px solid #e74c3c; 
    padding: 15px 25px; 
    border-radius: 10px; 
    text-align: center;
    background: white;
}
.repair-number { 
    font-size: 24px; 
    font-weight: 800; 
    margin: 0;
    letter-spacing: 1px;
    color: #e74c3c;
}
.repair-status { 
    font-size: 11px; 
    margin: 5px 0 0 0;
    font-weight: 700;
    color: #1e3a5f;
}

/* Contenu principal */
.main-content { 
    padding: 25px 30px; 
    flex: 1;
}
.section-title { 
    border-left: 4px solid #3498db; 
    background: #f8fafc; 
    color: #1e3a5f; 
    padding: 10px 16px; 
    font-size: 12px; 
    font-weight: 700; 
    margin: 18px 0 12px 0;
    letter-spacing: 0.5px;
}
.section-title:first-child {
    margin-top: 0;
}

/* Tableau d'informations */
.info-table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-bottom: 8px; 
}
.info-table td { 
    padding: 12px 14px; 
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
}
.info-table td:first-child { 
    font-weight: 600; 
    color: #64748b; 
    width: 35%; 
    background: #fafbfc;
}
.info-table td:last-child { 
    color: #1a1a1a; 
    font-weight: 500;
}
.info-table tr:last-child td {
    border-bottom: none;
}

/* Zone problème */
.problem-area { 
    border: 2px solid #fbbf24;
    border-left: 5px solid #fbbf24;
    padding: 18px 22px; 
    border-radius: 0 10px 10px 0; 
    margin: 20px 0;
    background: #fffdf5;
}
.problem-title {
    font-weight: 700;
    color: #92400e;
    margin-bottom: 10px;
    font-size: 13px;
    letter-spacing: 0.5px;
}
.problem-text {
    font-size: 14px;
    line-height: 1.6;
    color: #1a1a1a;
}

/* Pied de page */
.footer-section { 
    border-top: 2px solid #1e3a5f;
    padding: 25px 30px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 35px;
    background: #f8fafc;
}
.qr-wrapper { 
    border: 3px solid #1e3a5f; 
    padding: 12px; 
    border-radius: 10px;
    background: white;
}
.footer-text {
    text-align: left;
}
.footer-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #1e3a5f;
}
.footer-subtitle {
    font-size: 12px;
    color: #4a5568;
    line-height: 1.5;
}

@media print {
    body { background: white; }
    .label-a4-business { margin: 10mm; }
    .header-business, .footer-section { background: white !important; }
}
</style>

<div class="label-a4-business">
    <div class="header-business">
        <div class="company-info">
            <div class="company-name-a4"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
            <div class="company-tagline">Service de Réparation Professionnel</div>
            <?php if (!empty($company_phone) || !empty($company_address)): ?>
            <div class="company-contact">
                <?php if (!empty($company_phone)): ?>📞 <?php echo htmlspecialchars($company_phone); ?><?php endif; ?>
                <?php if (!empty($company_phone) && !empty($company_address)): ?> • <?php endif; ?>
                <?php if (!empty($company_address)): ?>📍 <?php echo htmlspecialchars($company_address); ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="repair-badge">
            <div class="repair-number">N° <?php echo $repair_number; ?></div>
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
                <td><strong><?php echo htmlspecialchars($reparation['modele']); ?></strong></td>
            </tr>
            <tr>
                <td>Code d'accès</td>
                <td style="color: #dc2626; font-weight: 700;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></td>
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
                <td><span style="border: 2px solid #3498db; color: #3498db; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($reparation['statut']); ?></span></td>
            </tr>
            <tr>
                <td>Montant estimé</td>
                <td style="color: #059669; font-size: 16px; font-weight: 700;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></td>
            </tr>
        </table>
        
        <div class="problem-area">
            <div class="problem-title">⚠️ DESCRIPTION DU PROBLÈME</div>
            <div class="problem-text">
                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
            </div>
        </div>
    </div>
    
    <div class="footer-section">
        <div class="qr-wrapper">
            <div id="qrcode_a4_business"></div>
        </div>
        <div class="footer-text">
            <div class="footer-title">🔧 Accès Rapide au Dossier</div>
            <div class="footer-subtitle">Scannez ce QR code pour accéder<br>directement au statut de cette réparation.</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_business"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 110, 
        height: 110, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
