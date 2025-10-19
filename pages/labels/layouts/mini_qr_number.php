<?php
/**
 * Layout Mini - QR Code + Numéro de Réparation
 * Format: 2x3 pouces (50x75mm) - Pour petites étiquettes
 * NOIR ET BLANC UNIQUEMENT
 */
?>
<style>
@page { size: 2in 3in !important; margin: 0 !important; }
body { 
    width: 2in; 
    height: 3in; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', sans-serif; 
    background: white; 
    color: black;
}
.mini-qr-number { 
    width: 100%; 
    height: 100%; 
    padding: 0.2in;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.header-mini {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
    margin-bottom: 10px;
}
.brand-name-mini {
    font-size: 16px;
    font-weight: 900;
    letter-spacing: 2px;
    margin: 0;
}
.repair-id-mini {
    background: #000;
    color: white;
    padding: 8px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
}
.qr-section-mini {
    text-align: center;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.qr-border {
    border: 2px solid #000;
    padding: 5px;
    display: inline-block;
}
.scan-text {
    font-size: 9px;
    font-weight: bold;
    margin-top: 5px;
}
@media print { 
    body { filter: grayscale(100%); } 
}
</style>

<div class="mini-qr-number">
    <div class="header-mini">
        <div class="brand-name-mini">MDG</div>
    </div>
    
    <div class="repair-id-mini">
        #<?php echo $reparation['id']; ?>
    </div>
    
    <div class="qr-section-mini">
        <div class="qr-border">
            <div id="qrcode_mini_number"></div>
        </div>
        <div class="scan-text">SCAN</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_mini_number"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 100,
        height: 100,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

