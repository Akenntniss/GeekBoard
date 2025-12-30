<?php
/**
 * Layout Startup - Format A4
 * Design dynamique et créatif - ÉCONOMIE D'ENCRE avec accents colorés
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
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');

body { 
    width: 210mm; 
    height: 297mm; 
    margin: 0; 
    padding: 0; 
    font-family: 'Poppins', 'Arial', sans-serif; 
    background: white;
    color: #1a1a1a;
}

.label-a4-startup { 
    background: white; 
    margin: 12mm; 
    padding: 0; 
    border: 3px solid #ff6b6b;
    border-radius: 20px; 
    overflow: hidden;
    min-height: calc(297mm - 24mm);
    display: flex;
    flex-direction: column;
}

/* En-tête */
.header-startup { 
    border-bottom: 3px solid #ff6b6b;
    padding: 25px 30px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to right, #fff5f5, white);
}
.brand-info {
    text-align: left;
}
.brand-startup { 
    font-size: 32px; 
    font-weight: 900; 
    color: #ff6b6b;
    margin: 0; 
    letter-spacing: 2px; 
}
.brand-subtitle {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
    font-weight: 600;
}
.brand-contact {
    font-size: 11px;
    color: #888;
    margin-top: 6px;
}
.id-badge { 
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 4px solid #ff6b6b;
    color: #ff6b6b; 
    width: 110px; 
    height: 110px; 
    border-radius: 50%; 
    font-weight: bold;
}
.id-label { 
    font-size: 10px; 
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}
.id-number { 
    font-size: 28px; 
    font-weight: 900;
    margin: 2px 0;
}
.id-status {
    font-size: 9px;
    background: #ff6b6b;
    color: white;
    padding: 3px 10px;
    border-radius: 15px;
    font-weight: 600;
}

/* Contenu */
.content-startup { 
    padding: 25px 30px; 
    flex: 1;
}
.card-startup { 
    border: 1px solid #ffd4d4;
    border-left: 4px solid #ff6b6b;
    padding: 16px 20px; 
    border-radius: 0 12px 12px 0; 
    margin-bottom: 14px; 
    background: #fffafa;
}
.card-title { 
    font-size: 10px; 
    font-weight: 700; 
    color: #ff6b6b; 
    text-transform: uppercase; 
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}
.card-value { 
    font-size: 15px; 
    font-weight: 600; 
    color: #1a1a1a; 
}
.card-sub {
    font-size: 13px;
    color: #666;
    margin-top: 3px;
}

/* Grille */
.grid-startup { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 14px; 
}

/* Zone problème */
.problem-card { 
    border: 2px solid #fbbf24;
    border-left: 5px solid #fbbf24;
    padding: 18px 22px; 
    border-radius: 0 12px 12px 0; 
    margin-top: 16px;
    background: #fffdf5;
}
.problem-title {
    font-size: 11px;
    font-weight: 700;
    color: #b45309;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}
.problem-text {
    font-size: 14px;
    line-height: 1.6;
    color: #1a1a1a;
}

/* Section QR */
.qr-section-startup { 
    border-top: 3px solid #ff6b6b;
    padding: 25px 30px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    background: linear-gradient(to right, #fff5f5, white);
}
.qr-wrapper {
    border: 3px solid #ff6b6b;
    padding: 12px;
    border-radius: 15px;
    background: white;
}
.qr-text {
    text-align: left;
}
.qr-title {
    font-size: 20px;
    font-weight: 800;
    color: #ff6b6b;
    letter-spacing: 1px;
}
.qr-subtitle {
    font-size: 12px;
    margin-top: 6px;
    color: #666;
    line-height: 1.5;
}

@media print {
    body { background: white; }
    .label-a4-startup { margin: 10mm; }
    .header-startup, .qr-section-startup { background: white !important; }
}
</style>

<div class="label-a4-startup">
    <div class="header-startup">
        <div class="brand-info">
            <div class="brand-startup"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
            <div class="brand-subtitle">Réparation High-Tech</div>
            <?php if (!empty($company_phone) || !empty($company_address)): ?>
            <div class="brand-contact">
                <?php if (!empty($company_phone)): ?>📞 <?php echo htmlspecialchars($company_phone); ?><?php endif; ?>
                <?php if (!empty($company_phone) && !empty($company_address)): ?> • <?php endif; ?>
                <?php if (!empty($company_address)): ?>📍 <?php echo htmlspecialchars($company_address); ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="id-badge">
            <div class="id-label">Réparation</div>
            <div class="id-number">#<?php echo $repair_number; ?></div>
            <div class="id-status"><?php echo htmlspecialchars($reparation['statut']); ?></div>
        </div>
    </div>
    
    <div class="content-startup">
        <div class="card-startup">
            <div class="card-title">👤 Client</div>
            <div class="card-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            <div class="card-sub">📞 <?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
        </div>
        
        <div class="grid-startup">
            <div class="card-startup">
                <div class="card-title">📱 Appareil</div>
                <div class="card-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                <div class="card-sub"><?php echo htmlspecialchars($reparation['modele']); ?></div>
            </div>
            
            <div class="card-startup">
                <div class="card-title">📅 Date</div>
                <div class="card-value"><?php echo $date_reception; ?></div>
            </div>
            
            <div class="card-startup">
                <div class="card-title">🔐 Code</div>
                <div class="card-value" style="color: #e53e3e;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'N/A'; ?></div>
            </div>
            
            <div class="card-startup">
                <div class="card-title">💰 Prix</div>
                <div class="card-value" style="color: #059669;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></div>
            </div>
        </div>
        
        <div class="problem-card">
            <div class="problem-title">⚠️ PROBLÈME SIGNALÉ</div>
            <div class="problem-text">
                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
            </div>
        </div>
    </div>
    
    <div class="qr-section-startup">
        <div class="qr-wrapper">
            <div id="qrcode_a4_startup"></div>
        </div>
        <div class="qr-text">
            <div class="qr-title">🔧 ACCÈS RAPIDE</div>
            <div class="qr-subtitle">Scannez pour accéder au<br>statut de cette réparation</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_a4_startup"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 110, 
        height: 110, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
