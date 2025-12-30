<?php
/**
 * Layout Mini - QR Code + Numéro de Réparation
 * Format: 2x3 pouces (50x75mm) - Pour petites étiquettes thermiques
 * NOIR ET BLANC - Optimisé impression thermique (Marges de sécurité)
 */
$repair_number = str_pad($reparation['id'], 4, '0', STR_PAD_LEFT);

// Récupérer le nom de l'entreprise
$company_name = $reparation['company_name'] ?? 'MDG';
// Générer les initiales si le nom est long (> 10 car.)
if (strlen($company_name) > 10) {
    $words = explode(' ', $company_name);
    if (count($words) > 1) {
        $brand_short = '';
        foreach ($words as $w) {
            $brand_short .= strtoupper(substr($w, 0, 1));
        }
    } else {
        $brand_short = strtoupper(substr($company_name, 0, 3));
    }
} else {
    $brand_short = strtoupper($company_name);
}
?>
<style>
@page { size: 2in 3in !important; margin: 0 !important; }
body { 
    width: 2in; 
    height: 3in; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', 'Helvetica', sans-serif; 
    background: white; 
    color: black;
    overflow: hidden; /* Empêche tout débordement */
}
.mini-qr-number { 
    width: 100%; 
    height: 100%; 
    padding: 2mm; /* Marge de sécurité interne */
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

.inner-border {
    border: 3px solid #000;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    padding: 2px;
}

/* Header */
.header-mini {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 4px;
    margin-bottom: 6px;
}
.brand-name-mini {
    font-size: 14px;
    font-weight: 900;
    letter-spacing: 2px;
    margin: 4px 0 0 0;
}

/* Badge ID */
.repair-id-mini {
    background: #000;
    color: white;
    padding: 6px 4px;
    text-align: center;
    margin-bottom: 6px;
}
.repair-label {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 2px;
}
.repair-value {
    font-size: 24px;
    font-weight: 900;
    letter-spacing: 2px;
    margin-top: 1px;
}

/* Zone QR */
.qr-section-mini {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.qr-border {
    border: 3px solid #000;
    padding: 5px;
    display: inline-block;
    background: white;
}

/* Footer */
.scan-footer {
    text-align: center;
    border-top: 2px solid #000;
    padding-top: 4px;
    margin-top: 6px;
    margin-bottom: 4px;
}
.scan-text {
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 3px;
}

@media print { 
    body, .mini-qr-number { filter: grayscale(100%); } 
}
</style>

<div class="mini-qr-number">
    <div class="inner-border">
        <div class="header-mini">
            <div class="brand-name-mini"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
        </div>
        
        <div class="repair-id-mini">
            <div class="repair-label">RÉPARATION</div>
            <div class="repair-value">#<?php echo $repair_number; ?></div>
        </div>
        
        <div class="qr-section-mini">
            <div class="qr-border">
                <div id="qrcode_mini_number"></div>
            </div>
        </div>
        
        <div class="scan-footer">
            <div class="scan-text">▶ SCAN ◀</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_mini_number"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 90, /* Réduit pour sécurité */
        height: 90,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
