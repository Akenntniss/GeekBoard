<?php
/**
 * Layout Mini - QR Code Uniquement
 * Format: 2x2 pouces (50x50mm) - Pour petites étiquettes
 * NOIR ET BLANC UNIQUEMENT
 * Version améliorée : design plus impactant
 */
?>
<style>
@page { size: 2in 2in !important; margin: 0 !important; }
body { 
    width: 2in; 
    height: 2in; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', 'Helvetica', sans-serif; 
    background: white; 
    color: black;
}
.mini-qr-only { 
    width: 100%; 
    height: 100%; 
    display: flex; 
    flex-direction: column;
    align-items: center; 
    justify-content: center;
    padding: 0.1in;
    box-sizing: border-box;
    border: 3px solid #000;
}

/* Header */
.mini-header {
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 2px;
    margin-bottom: 8px;
    text-align: center;
}

/* QR Wrapper */
.qr-frame {
    border: 3px solid #000;
    padding: 6px;
    background: white;
}

/* Numéro de réparation */
.repair-id {
    background: #000;
    color: white;
    font-size: 14px;
    font-weight: 800;
    padding: 4px 12px;
    margin-top: 8px;
    letter-spacing: 1px;
}

@media print { 
    body, .mini-qr-only { filter: grayscale(100%); } 
}
</style>

<div class="mini-qr-only">
    <div class="mini-header">MDG</div>
    <div class="qr-frame">
        <div id="qrcode_mini_only"></div>
    </div>
    <div class="repair-id">#<?php echo $reparation['id']; ?></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_mini_only"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 100,
        height: 100,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
