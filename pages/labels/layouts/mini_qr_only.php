<?php
/**
 * Layout Mini - QR Code Uniquement
 * Format: 2x2 pouces (50x50mm) - Pour petites étiquettes
 * NOIR ET BLANC UNIQUEMENT
 */
?>
<style>
@page { size: 2in 2in !important; margin: 0 !important; }
body { 
    width: 2in; 
    height: 2in; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', sans-serif; 
    background: white; 
    color: black;
    display: flex;
    align-items: center;
    justify-content: center;
}
.mini-qr-only { 
    width: 100%; 
    height: 100%; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    flex-direction: column;
    padding: 0.15in;
    box-sizing: border-box;
}
.qr-wrapper {
    border: 3px solid #000;
    padding: 8px;
    background: white;
}
.brand-mini {
    font-size: 10px;
    font-weight: bold;
    text-align: center;
    margin-top: 5px;
    letter-spacing: 1px;
}
@media print { 
    body { filter: grayscale(100%); } 
}
</style>

<div class="mini-qr-only">
    <div class="qr-wrapper">
        <div id="qrcode_mini_only"></div>
    </div>
    <div class="brand-mini">MDG</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_mini_only"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 110,
        height: 110,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

