<?php
/**
 * Layout Moderne - Format A4
 * Design minimaliste avec couleurs vives - ÉCONOMIE D'ENCRE
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

.label-a4-moderne { 
    background: white; 
    margin: 12mm; 
    padding: 0; 
    border: 2px solid #8b5cf6;
    border-radius: 16px;
    min-height: calc(297mm - 24mm);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* En-tête */
.header-moderne { 
    border-bottom: 3px solid #8b5cf6;
    padding: 28px 32px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to right, #faf5ff, white);
}
.brand-moderne {
    text-align: left;
}
.brand-title { 
    font-size: 34px; 
    font-weight: 900; 
    color: #8b5cf6;
    margin: 0; 
    letter-spacing: 3px; 
}
.brand-subtitle {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    font-weight: 600;
    letter-spacing: 1px;
}
.brand-contact {
    font-size: 11px;
    color: #888;
    margin-top: 8px;
}
.id-moderne { 
    border: 4px solid #8b5cf6;
    color: #8b5cf6; 
    padding: 18px 28px; 
    border-radius: 12px;
    text-align: center;
}
.id-label { 
    font-size: 10px; 
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 4px;
}
.id-number { 
    font-size: 32px; 
    font-weight: 900;
}

/* Statut */
.status-bar {
    padding: 15px 32px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
}
.status-label {
    font-size: 12px;
    color: #666;
    font-weight: 600;
}
.status-badge {
    border: 2px solid #10b981;
    color: #10b981;
    padding: 6px 18px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

/* Contenu */
.content-moderne { 
    padding: 28px 32px; 
    flex: 1;
}

/* Grille de cartes */
.cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
.info-card { 
    border: 1px solid #e9d5ff;
    border-left: 4px solid #8b5cf6;
    padding: 18px 20px; 
    border-radius: 0 12px 12px 0;
    background: #fefbff;
}
.info-card.full {
    grid-column: 1 / -1;
}
.card-label { 
    font-size: 10px; 
    font-weight: 700; 
    color: #8b5cf6; 
    text-transform: uppercase; 
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}
.card-value { 
    font-size: 16px; 
    font-weight: 600; 
    color: #1a1a1a;
}
.card-sub {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

/* Zone problème */
.problem-moderne { 
    border: 2px solid #f59e0b;
    border-left: 5px solid #f59e0b;
    padding: 20px 24px; 
    border-radius: 0 12px 12px 0;
    background: #fffbeb;
    margin-top: 8px;
}
.problem-title {
    font-size: 11px;
    font-weight: 700;
    color: #b45309;
    margin-bottom: 12px;
    letter-spacing: 0.5px;
}
.problem-text {
    font-size: 14px;
    line-height: 1.7;
    color: #1a1a1a;
}

/* Section QR */
.qr-section-moderne { 
    border-top: 2px solid #8b5cf6;
    padding: 28px 32px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 32px;
    background: linear-gradient(to right, #faf5ff, white);
}
.qr-wrapper-moderne {
    border: 3px solid #8b5cf6;
    padding: 12px;
    border-radius: 12px;
    background: white;
}
.qr-text-moderne {
    text-align: left;
}
.qr-title-moderne {
    font-size: 18px;
    font-weight: 800;
    color: #8b5cf6;
    letter-spacing: 1px;
}
.qr-sub-moderne {
    font-size: 12px;
    margin-top: 6px;
    color: #666;
    line-height: 1.5;
}

@media print {
    body { background: white; }
    .label-a4-moderne { margin: 10mm; }
    .header-moderne, .qr-section-moderne { background: white !important; }
}
</style>

<div class="label-a4-moderne">
    <div class="header-moderne">
        <div class="brand-moderne">
            <div class="brand-title"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
            <div class="brand-subtitle">Centre de Réparation</div>
            <?php if (!empty($company_phone) || !empty($company_address)): ?>
            <div class="brand-contact">
                <?php if (!empty($company_phone)): ?>📞 <?php echo htmlspecialchars($company_phone); ?><?php endif; ?>
                <?php if (!empty($company_phone) && !empty($company_address)): ?> • <?php endif; ?>
                <?php if (!empty($company_address)): ?>📍 <?php echo htmlspecialchars($company_address); ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="id-moderne">
            <div class="id-label">Dossier</div>
            <div class="id-number">#<?php echo $repair_number; ?></div>
        </div>
    </div>
    
    <div class="status-bar">
        <div class="status-label">📊 Statut de la réparation</div>
        <div class="status-badge"><?php echo htmlspecialchars($reparation['statut']); ?></div>
    </div>
    
    <div class="content-moderne">
        <div class="cards-grid">
            <div class="info-card">
                <div class="card-label">👤 Client</div>
                <div class="card-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                <div class="card-sub">📞 <?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
            
            <div class="info-card">
                <div class="card-label">📱 Appareil</div>
                <div class="card-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                <div class="card-sub"><?php echo htmlspecialchars($reparation['modele']); ?></div>
            </div>
            
            <div class="info-card">
                <div class="card-label">📅 Date de dépôt</div>
                <div class="card-value"><?php echo $date_reception; ?></div>
            </div>
            
            <div class="info-card">
                <div class="card-label">🔐 Code d'accès</div>
                <div class="card-value" style="color: #dc2626;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></div>
            </div>
            
            <div class="info-card">
                <div class="card-label">💰 Montant</div>
                <div class="card-value" style="color: #059669; font-size: 18px;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></div>
            </div>
            
            <div class="info-card">
                <div class="card-label">📝 Notes</div>
                <div class="card-value" style="font-size: 13px;"><?php echo !empty($reparation['notes_techniques']) ? 'Oui' : 'Non'; ?></div>
            </div>
        </div>
        
        <div class="problem-moderne">
            <div class="problem-title">⚠️ DESCRIPTION DU PROBLÈME</div>
            <div class="problem-text">
                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
            </div>
        </div>
    </div>
    
    <div class="qr-section-moderne">
        <div class="qr-wrapper-moderne">
            <div id="qrcode_a4_moderne"></div>
        </div>
        <div class="qr-text-moderne">
            <div class="qr-title-moderne">🔧 ACCÈS RAPIDE</div>
            <div class="qr-sub-moderne">Scannez pour accéder au<br>statut de cette réparation</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_moderne"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 110, 
        height: 110, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
