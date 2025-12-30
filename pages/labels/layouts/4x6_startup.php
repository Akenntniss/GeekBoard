<?php
/**
 * Layout Startup - Format 4x6" (Imprimante thermique)
 * Design dynamique et créatif - NOIR ET BLANC PUR
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$repair_number = str_pad($reparation['id'], 4, '0', STR_PAD_LEFT);
$company_name = $reparation['company_name'] ?? 'MAISON DU GEEK';
?>
<style>
@page { size: 4in 6in !important; margin: 0 !important; }
body { 
    width: 4in; 
    height: 6in; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', 'Helvetica', sans-serif; 
    background: #fff; 
    color: #000; 
}
.label-startup { 
    width: 4in; 
    height: 6in; 
    padding: 3mm; 
    box-sizing: border-box; 
    display: flex;
    flex-direction: column;
}

/* Conteneur interne */
.inner-startup {
    border: 4px solid #000;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Marque - design dynamique avec bandes */
.header-startup { 
    text-align: center; 
    padding: 10px;
    border-bottom: 4px solid #000;
    position: relative;
}
.header-startup::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: repeating-linear-gradient(90deg, #000 0px, #000 10px, #fff 10px, #fff 20px);
}
.brand-name-startup { 
    font-size: 20px; 
    font-weight: 900; 
    margin: 8px 0 0 0; 
    letter-spacing: 4px; 
}

/* Badge ID - design dynamique */
.id-badge-startup { 
    background: #000; 
    color: #fff; 
    padding: 10px 12px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.id-number-startup {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: 2px;
}
.id-status-startup {
    background: #fff;
    color: #000;
    padding: 5px 12px;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 1px;
}

/* Contenu */
.content-startup {
    flex: 1;
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
}

/* Cartes d'info avec style géométrique */
.info-card-startup { 
    border: 2px solid #000;
    border-left: 6px solid #000;
    padding: 8px 10px; 
    margin-bottom: 6px; 
    background: #fff;
}
.label-text-startup { 
    font-size: 8px; 
    font-weight: 900; 
    letter-spacing: 2px;
    margin-bottom: 3px;
}
.value-text-startup { 
    font-size: 12px; 
    font-weight: 700;
    color: #000;
}
.sub-text-startup {
    font-size: 10px;
    font-weight: 700;
    margin-top: 2px;
}

/* Grille */
.grid-2-startup { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 6px; 
}

/* Zone problème */
.problem-startup {
    flex: 1;
    border: 3px solid #000;
    padding: 8px 10px;
    margin-top: 6px;
    min-height: 35px;
}
.problem-title-startup {
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1px;
    border-bottom: 2px solid #000;
    padding-bottom: 4px;
    margin-bottom: 5px;
}
.problem-text-startup {
    font-size: 9px;
    font-weight: 700;
    line-height: 1.4;
}

/* Zone QR - design dynamique */
.qr-area-startup { 
    border-top: 4px solid #000;
    padding: 8px 10px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    position: relative;
}
.qr-area-startup::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: repeating-linear-gradient(90deg, #000 0px, #000 10px, #fff 10px, #fff 20px);
}
.qr-wrapper-startup {
    border: 3px solid #000;
    padding: 4px;
    background: #fff;
}
.scan-text-startup {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 3px;
    text-align: center;
    line-height: 1.3;
}

@media print { 
    body, .label-startup { filter: grayscale(100%); }
}
</style>

<div class="label-startup">
    <div class="inner-startup">
        <div class="header-startup">
            <div class="brand-name-startup"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
        </div>
        
        <div class="id-badge-startup">
            <span class="id-number-startup">#<?php echo $repair_number; ?></span>
            <span class="id-status-startup"><?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?></span>
        </div>
        
        <div class="content-startup">
            <div class="info-card-startup">
                <div class="label-text-startup">CLIENT</div>
                <div class="value-text-startup"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                <div class="sub-text-startup">TEL: <?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
            
            <div class="grid-2-startup">
                <div class="info-card-startup">
                    <div class="label-text-startup">APPAREIL</div>
                    <div class="value-text-startup" style="font-size: 10px;"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                    <div class="sub-text-startup"><?php echo htmlspecialchars($reparation['modele']); ?></div>
                </div>
                <div class="info-card-startup">
                    <div class="label-text-startup">DEPOT</div>
                    <div class="value-text-startup"><?php echo $date_reception; ?></div>
                </div>
            </div>
            
            <div class="grid-2-startup">
                <div class="info-card-startup">
                    <div class="label-text-startup">[CODE]</div>
                    <div class="value-text-startup"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : '—'; ?></div>
                </div>
                <div class="info-card-startup">
                    <div class="label-text-startup">PRIX</div>
                    <div class="value-text-startup"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' E' : 'A definir'; ?></div>
                </div>
            </div>
            
            <div class="problem-startup">
                <div class="problem-title-startup">PROBLEME SIGNALE</div>
                <div class="problem-text-startup">
                    <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 100)); ?><?php echo strlen($reparation['description_probleme']) > 100 ? '...' : ''; ?>
                </div>
            </div>
        </div>
        
        <div class="qr-area-startup">
            <div class="qr-wrapper-startup">
                <div id="qrcode_startup"></div>
            </div>
            <div class="scan-text-startup">SCAN<br>RAPIDE</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_startup"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 70, 
        height: 70, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
