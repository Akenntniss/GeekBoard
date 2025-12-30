<?php
/**
 * Layout QR Simple - QR Code + Numéro uniquement
 * Format: Étiquette minimaliste - NOIR ET BLANC UNIQUEMENT
 */
?>
<style>
@page { size: 6cm 4cm !important; margin: 0 !important; }
body { 
    width: 6cm; 
    height: 4cm; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', sans-serif; 
    background: white; 
    color: black;
}

.qr-simple { 
    width: 100%; 
    height: 100%; 
    padding: 0.3cm;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    border: 2px solid #000;
}

/* Numéro de réparation */
.repair-number { 
    font-size: 24px; 
    font-weight: 900; 
    margin-bottom: 8px;
    padding: 8px 12px;
    background: #000;
    color: white;
    border-radius: 4px;
    letter-spacing: 2px;
}

/* QR Code container */
.qr-container { 
    margin-bottom: 8px;
    border: 1px solid #000;
    padding: 4px;
    background: white;
}

/* Texte sous QR */
.scan-text { 
    font-size: 10px; 
    font-weight: bold; 
    text-transform: uppercase;
    letter-spacing: 1px;
}

@media print { 
    body { 
        filter: grayscale(100%); 
    } 
}
</style>

<div class="qr-simple">
    <!-- Numéro de réparation -->
    <div class="repair-number">
        #<?php echo $reparation['id']; ?>
    </div>
    
    <!-- QR Code -->
    <div class="qr-container">
        <div id="qrcode_simple"></div>
    </div>
    
    <!-- Texte scan -->
    <div class="scan-text">SCAN</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_simple"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 80,
        height: 80,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
