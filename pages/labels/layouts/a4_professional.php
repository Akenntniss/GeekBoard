<?php
/**
 * Layout Professional - Format A4
 * Design classique et élégant - ÉCONOMIE D'ENCRE avec accents colorés
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$repair_number = str_pad($reparation['id'], 6, '0', STR_PAD_LEFT);

// Récupérer les infos entreprise (passées depuis imprimer_etiquette.php)
$company_name = $reparation['company_name'] ?? 'MAISON DU GEEK';
$company_phone = $reparation['company_phone'] ?? '';
$company_address = $reparation['company_address'] ?? '';
?>
<style>
@page { size: A4; margin: 0; }
@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&family=Inter:wght@400;500;600;700&display=swap');

body { 
    width: 210mm; 
    height: 297mm; 
    margin: 0; 
    padding: 0; 
    font-family: 'Inter', 'Arial', sans-serif; 
    background: white;
    color: #1a1a1a;
}

.label-a4-pro { 
    background: white; 
    margin: 12mm; 
    padding: 0; 
    border: 2px solid #1a365d;
    border-radius: 8px;
    min-height: calc(297mm - 24mm);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* En-tête style letterhead */
.letterhead { 
    border-bottom: 3px solid #1a365d;
    padding: 28px 35px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.company-info {
    flex: 1;
}
.company-title { 
    font-family: 'Merriweather', Georgia, serif;
    font-size: 32px; 
    font-weight: 900; 
    letter-spacing: 2px;
    color: #1a365d;
}
.company-subtitle { 
    font-size: 12px; 
    margin-top: 6px; 
    color: #4a5568;
    letter-spacing: 1px;
    font-weight: 600;
}
.company-contact {
    font-size: 11px;
    margin-top: 8px;
    color: #666;
}
.document-badge { 
    border: 3px solid #1a365d;
    padding: 15px 25px; 
    border-radius: 8px; 
    text-align: center; 
    font-weight: 700; 
    font-size: 12px;
    letter-spacing: 1px;
    color: #1a365d;
}
.document-badge-title {
    font-size: 10px;
    margin-bottom: 3px;
}
.document-badge-number {
    font-size: 22px;
    font-weight: 900;
}

/* Sous-en-tête */
.repair-header { 
    background: #f8f9fa; 
    padding: 20px 35px; 
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.repair-title { 
    font-family: 'Merriweather', Georgia, serif;
    font-size: 20px; 
    font-weight: 700; 
    color: #1a365d; 
}
.repair-id { 
    color: #4299e1;
    font-weight: 900;
}
.status-pill {
    border: 2px solid #4299e1;
    color: #4299e1;
    padding: 8px 18px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

/* Contenu principal */
.content-pro { 
    padding: 25px 35px; 
    flex: 1;
}
.data-section { 
    margin-bottom: 22px; 
}
.section-header { 
    font-size: 11px; 
    font-weight: 700; 
    color: #1a365d; 
    text-transform: uppercase; 
    border-bottom: 2px solid #1a365d; 
    padding-bottom: 8px; 
    margin-bottom: 12px; 
    letter-spacing: 1px; 
}

/* Tableaux de données */
.data-table { 
    width: 100%; 
    border-collapse: collapse; 
}
.data-table tr { 
    border-bottom: 1px solid #e2e8f0; 
}
.data-table tr:last-child {
    border-bottom: none;
}
.data-table td { 
    padding: 12px 0; 
    font-size: 14px;
}
.data-table td:first-child { 
    font-weight: 600; 
    color: #4a5568; 
    width: 40%; 
}
.data-table td:last-child { 
    color: #1a1a1a; 
    font-weight: 500;
}

/* Zone de description */
.highlight-box { 
    border: 2px solid #1a365d;
    border-left: 5px solid #4299e1;
    padding: 20px 25px; 
    margin: 20px 0;
    border-radius: 0 8px 8px 0;
    background: #f8fafc;
}
.highlight-title {
    font-family: 'Merriweather', Georgia, serif;
    font-weight: 700;
    color: #1a365d;
    margin-bottom: 12px;
    font-size: 15px;
}
.highlight-text {
    font-size: 14px;
    line-height: 1.7;
    color: #2d3748;
}

/* Pied de page */
.footer-pro { 
    border-top: 2px solid #1a365d;
    padding: 25px 35px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 35px;
    background: #f8f9fa;
}
.qr-container-pro { 
    border: 3px solid #1a365d; 
    padding: 12px; 
    border-radius: 10px;
    background: white;
}
.footer-text {
    text-align: left;
}
.footer-title {
    font-family: 'Merriweather', Georgia, serif;
    font-size: 16px;
    font-weight: 700;
    color: #1a365d;
    margin-bottom: 8px;
}
.footer-subtitle {
    font-size: 12px;
    color: #4a5568;
    line-height: 1.6;
}

@media print {
    body { background: white; }
    .label-a4-pro { margin: 10mm; }
}
</style>

<div class="label-a4-pro">
    <div class="letterhead">
        <div class="company-info">
            <div class="company-title"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
            <div class="company-subtitle">CENTRE DE RÉPARATION ÉLECTRONIQUE</div>
            <?php if (!empty($company_phone) || !empty($company_address)): ?>
            <div class="company-contact">
                <?php if (!empty($company_phone)): ?>📞 <?php echo htmlspecialchars($company_phone); ?><?php endif; ?>
                <?php if (!empty($company_phone) && !empty($company_address)): ?> • <?php endif; ?>
                <?php if (!empty($company_address)): ?>📍 <?php echo htmlspecialchars($company_address); ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="document-badge">
            <div class="document-badge-title">DOSSIER</div>
            <div class="document-badge-number">#<?php echo $repair_number; ?></div>
        </div>
    </div>
    
    <div class="repair-header">
        <div class="repair-title">
            Dossier de Réparation <span class="repair-id">N° <?php echo $repair_number; ?></span>
        </div>
        <span class="status-pill"><?php echo htmlspecialchars($reparation['statut']); ?></span>
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
                    <td><strong><?php echo htmlspecialchars($reparation['modele']); ?></strong></td>
                </tr>
                <tr>
                    <td>Code d'accès / PIN</td>
                    <td style="color: #e53e3e; font-weight: 700;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non communiqué'; ?></td>
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
                    <td style="color: #059669; font-size: 16px; font-weight: 700;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' € TTC' : 'Devis en cours'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="highlight-box">
            <div class="highlight-title">📋 Description détaillée du problème</div>
            <div class="highlight-text">
                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
            </div>
        </div>
    </div>
    
    <div class="footer-pro">
        <div class="qr-container-pro">
            <div id="qrcode_a4_pro"></div>
        </div>
        <div class="footer-text">
            <div class="footer-title">🔧 Accès Rapide au Dossier</div>
            <div class="footer-subtitle">Scannez ce code QR pour accéder<br>directement au statut de cette réparation<br>depuis votre terminal.</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_pro"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 120, 
        height: 120, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
