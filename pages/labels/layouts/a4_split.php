<?php
/**
 * Layout Split - Format A4 à découper
 * 75% CLIENT (Confirmation de dépôt) + 25% ATELIER (Infos confidentielles)
 * VERSION MODERNE ÉCONOMIE D'ENCRE : accents de couleur sans fonds pleins
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$repair_number = str_pad($reparation['id'], 4, '0', STR_PAD_LEFT);

// Récupérer les infos entreprise (passées depuis imprimer_etiquette.php)
$company_name = $reparation['company_name'] ?? 'MAISON DU GEEK';
$company_phone = $reparation['company_phone'] ?? '';
$company_address = $reparation['company_address'] ?? '';
?>
<style>
@page { size: A4; margin: 0; }
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

body { 
    width: 210mm; 
    height: 297mm; 
    margin: 0; 
    padding: 0; 
    font-family: 'Inter', 'Arial', sans-serif; 
    background: white;
    color: #1a1a1a;
}
.split-container { 
    width: 100%; 
    height: 100%; 
    display: flex; 
    flex-direction: column; 
}

/* PARTIE CLIENT (75%) */
.client-section { 
    height: 75%; 
    padding: 20px; 
    box-sizing: border-box; 
}
.client-content { 
    height: 100%; 
    padding: 25px; 
    border: 2px solid #6366f1;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
}

/* En-tête client */
.client-header { 
    border-left: 5px solid #6366f1;
    padding: 18px 22px; 
    background: linear-gradient(to right, #f5f3ff, white);
    border-radius: 0 12px 12px 0; 
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px; 
}
.client-brand {
    text-align: left;
}
.client-title { 
    font-size: 26px; 
    font-weight: 900; 
    margin: 0; 
    letter-spacing: 2px;
    color: #6366f1;
}
.client-subtitle { 
    font-size: 12px; 
    margin: 4px 0 0 0;
    font-weight: 600;
    color: #333;
}
.client-contact {
    font-size: 11px;
    margin-top: 5px;
    color: #555;
    line-height: 1.4;
}
.confirmation-badge { 
    background: #6366f1;
    color: white;
    padding: 14px 24px; 
    border-radius: 12px; 
    font-weight: 900; 
    font-size: 22px;
}

/* Confirmation box */
.confirmation-box {
    border: 2px solid #10b981;
    border-left: 5px solid #10b981;
    padding: 14px 18px;
    border-radius: 0 10px 10px 0;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.confirmation-icon {
    font-size: 28px;
}
.confirmation-text {
    font-size: 16px;
    font-weight: 700;
    color: #059669;
}
.confirmation-sub {
    font-size: 12px;
    color: #555;
    margin-top: 2px;
}

/* Grille d'infos client */
.client-info-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 12px; 
    margin-bottom: 12px;
    flex: 1;
}
.client-card { 
    border: 1px solid #e0e0e0;
    border-left: 4px solid #6366f1;
    padding: 14px 16px; 
    border-radius: 0 10px 10px 0;
    background: #fafafa;
}
.card-label { 
    font-size: 10px; 
    font-weight: 700; 
    text-transform: uppercase;
    color: #6366f1;
    margin-bottom: 5px;
    letter-spacing: 0.5px;
}
.card-value { 
    font-size: 14px; 
    font-weight: 600;
    color: #1a1a1a;
}
.card-sub {
    font-size: 12px;
    color: #555;
    margin-top: 3px;
}
.full-width {
    grid-column: 1 / -1;
}

/* Section QR client */
.qr-section-client { 
    border: 2px solid #6366f1;
    padding: 16px 20px; 
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 25px;
    background: linear-gradient(to right, #f5f3ff, white);
}
.qr-wrapper-client {
    border: 2px solid #6366f1;
    padding: 8px;
    border-radius: 10px;
    background: white;
}
.qr-text-client {
    text-align: left;
}
.qr-title-client {
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 1px;
    color: #6366f1;
}
.qr-sub-client {
    font-size: 11px;
    margin-top: 4px;
    color: #555;
}

/* LIGNE DE DÉCOUPE */
.cut-line { 
    height: 0; 
    border-top: 2px dashed #999; 
    margin: 8px 20px;
}

/* PARTIE ATELIER (25%) */
.workshop-section { 
    height: calc(25% - 8px); 
    border: 2px solid #1e293b;
    border-left: 6px solid #ef4444;
    margin: 0 20px 20px 20px;
    padding: 16px 20px; 
    box-sizing: border-box;
    border-radius: 0 12px 12px 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: #fafafa;
}
.workshop-header { 
    font-size: 14px; 
    font-weight: 800; 
    text-align: center; 
    background: #1e293b;
    color: white;
    padding: 8px 12px; 
    border-radius: 6px;
    margin-bottom: 10px;
    letter-spacing: 1px;
}
.workshop-grid { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 10px; 
}
.workshop-qr-section {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}
.workshop-qr-wrapper {
    background: white;
    padding: 6px;
    border: 2px solid #1e293b;
    border-radius: 6px;
}
.workshop-qr-text {
    font-size: 11px;
    font-weight: 700;
    text-align: right;
    letter-spacing: 1px;
    color: #1e293b;
}
.workshop-info { 
    border: 1px solid #ccc;
    padding: 10px; 
    border-radius: 6px;
    background: white;
}
.workshop-label { 
    font-size: 9px; 
    text-transform: uppercase; 
    color: #666;
    margin-bottom: 3px;
    letter-spacing: 0.5px;
}
.workshop-value { 
    font-size: 12px; 
    font-weight: 700;
    color: #1a1a1a;
}
.workshop-value.highlight {
    color: #ef4444;
}
.workshop-value.price {
    color: #059669;
}
.confidential-note { 
    background: #fef2f2;
    border: 2px solid #ef4444;
    color: #b91c1c;
    padding: 6px 12px; 
    border-radius: 6px; 
    text-align: center; 
    font-size: 9px; 
    font-weight: 700;
    letter-spacing: 1px;
}

@media print {
    body { background: white; }
    .client-header, .qr-section-client { background: white !important; }
}
</style>

<div class="split-container">
    <!-- PARTIE CLIENT (75%) -->
    <div class="client-section">
        <div class="client-content">
            <div class="client-header">
                <div class="client-brand">
                    <div class="client-title"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
                    <div class="client-subtitle">Confirmation de Dépôt d'Appareil</div>
                    <?php if (!empty($company_phone) || !empty($company_address)): ?>
                    <div class="client-contact">
                        <?php if (!empty($company_phone)): ?>📞 <?php echo htmlspecialchars($company_phone); ?><?php endif; ?>
                        <?php if (!empty($company_phone) && !empty($company_address)): ?> • <?php endif; ?>
                        <?php if (!empty($company_address)): ?>📍 <?php echo htmlspecialchars($company_address); ?><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="confirmation-badge">#<?php echo $repair_number; ?></div>
            </div>
            
            <div class="confirmation-box">
                <div class="confirmation-icon">✓</div>
                <div>
                    <div class="confirmation-text">Votre appareil a bien été déposé</div>
                    <div class="confirmation-sub">Conservez précieusement ce document</div>
                </div>
            </div>
            
            <div class="client-info-grid">
                <div class="client-card">
                    <div class="card-label">👤 Client</div>
                    <div class="card-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                </div>
                
                <div class="client-card">
                    <div class="card-label">📱 Appareil</div>
                    <div class="card-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                    <div class="card-sub"><?php echo htmlspecialchars($reparation['modele']); ?></div>
                </div>
                
                <div class="client-card">
                    <div class="card-label">📅 Date de Dépôt</div>
                    <div class="card-value"><?php echo $date_reception; ?></div>
                </div>
                
                <div class="client-card">
                    <div class="card-label">📊 Statut</div>
                    <div class="card-value"><?php echo htmlspecialchars($reparation['statut']); ?></div>
                </div>
                
                <div class="client-card full-width">
                    <div class="card-label">⚠️ Problème Signalé</div>
                    <div class="card-sub" style="margin-top: 6px; line-height: 1.5; color: #000;">
                        <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 200)); ?><?php echo strlen($reparation['description_probleme']) > 200 ? '...' : ''; ?>
                    </div>
                </div>
            </div>
            
            <div class="qr-section-client">
                <div class="qr-wrapper-client">
                    <div id="qrcode_client"></div>
                </div>
                <div class="qr-text-client">
                    <div class="qr-title-client">SUIVEZ VOTRE RÉPARATION</div>
                    <div class="qr-sub-client">Scannez ce QR code avec votre smartphone</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- LIGNE DE DÉCOUPE -->
    <div class="cut-line"></div>
    
    <!-- PARTIE ATELIER (25%) - CONFIDENTIEL -->
    <div class="workshop-section">
        <div class="workshop-header">
            🔒 PARTIE ATELIER - CONFIDENTIEL 🔒
        </div>
        
        <div class="workshop-grid">
            <div class="workshop-info">
                <div class="workshop-label">Dossier N°</div>
                <div class="workshop-value">#<?php echo $repair_number; ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">Client</div>
                <div class="workshop-value" style="font-size: 11px;"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">📞 Téléphone</div>
                <div class="workshop-value" style="font-size: 11px;"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">🔐 Code Accès</div>
                <div class="workshop-value highlight"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'AUCUN'; ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">💰 Prix</div>
                <div class="workshop-value price"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'N/D'; ?></div>
            </div>
            
            <div class="workshop-info">
                <div class="workshop-label">📱 Modèle</div>
                <div class="workshop-value" style="font-size: 10px;"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?></div>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
            <div class="confidential-note" style="margin: 0; flex: 1;">
                ⚠️ DOCUMENT INTERNE - NE PAS COMMUNIQUER AU CLIENT ⚠️
            </div>
            <div class="workshop-qr-section" style="margin-left: 15px;">
                <div class="workshop-qr-text">SCAN<br>RAPIDE</div>
                <div class="workshop-qr-wrapper">
                    <div id="qrcode_workshop"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // QR code partie client
    new QRCode(document.getElementById("qrcode_client"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 90, 
        height: 90, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // QR code partie atelier (confidentiel)
    new QRCode(document.getElementById("qrcode_workshop"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 75, 
        height: 75, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
