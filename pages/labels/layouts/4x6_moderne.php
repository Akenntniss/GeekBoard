<?php
/**
 * Layout Moderne - Format 4x6" (Imprimante thermique)
 * Design minimaliste et moderne - NOIR ET BLANC PUR
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
.label-moderne { 
    width: 4in; 
    height: 6in; 
    padding: 3mm; 
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

/* Conteneur minimaliste */
.inner-moderne {
    border: 2px solid #000;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* En-tête ultra-minimaliste */
.header-moderne { 
    text-align: center; 
    padding: 12px;
    border-bottom: 2px solid #000;
}
.brand-moderne { 
    font-size: 18px; 
    font-weight: 900; 
    letter-spacing: 5px; 
    margin: 0; 
}

/* Badge numéro */
.id-moderne { 
    background: #000; 
    color: #fff; 
    padding: 10px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 900;
}
.id-num {
    font-size: 20px;
    letter-spacing: 2px;
}
.id-stat {
    font-size: 10px;
    letter-spacing: 1px;
}

/* Contenu */
.content-moderne {
    flex: 1;
    padding: 10px;
    display: flex;
    flex-direction: column;
}

/* Blocs d'info minimalistes */
.info-moderne { 
    border-left: 3px solid #000;
    padding: 6px 10px; 
    margin-bottom: 8px;
}
.info-label { 
    font-size: 8px; 
    font-weight: 900; 
    letter-spacing: 2px;
    margin-bottom: 2px;
}
.info-value { 
    font-size: 12px; 
    font-weight: 700;
}
.info-sub {
    font-size: 10px;
    font-weight: 700;
    margin-top: 2px;
}

/* Grille 2 colonnes */
.grid-moderne { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 8px;
    margin-bottom: 8px;
}

/* Zone problème */
.problem-moderne { 
    border: 2px solid #000; 
    padding: 8px 10px;
    flex: 1;
    min-height: 40px;
}
.problem-head {
    font-size: 8px;
    font-weight: 900;
    letter-spacing: 1px;
    border-bottom: 1px solid #000;
    padding-bottom: 4px;
    margin-bottom: 5px;
}
.problem-body {
    font-size: 9px;
    font-weight: 700;
    line-height: 1.4;
}

/* QR Section */
.qr-moderne { 
    border-top: 2px solid #000;
    padding: 10px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}
.qr-box {
    border: 2px solid #000;
    padding: 4px;
}
.qr-label {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 2px;
}

@media print { 
    body, .label-moderne { filter: grayscale(100%); }
}
</style>

<div class="label-moderne">
    <div class="inner-moderne">
        <div class="header-moderne">
            <div class="brand-moderne"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
        </div>
        
        <div class="id-moderne">
            <span class="id-num">N<?php echo $repair_number; ?></span>
            <span class="id-stat"><?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?></span>
        </div>
        
        <div class="content-moderne">
            <div class="info-moderne">
                <div class="info-label">CLIENT</div>
                <div class="info-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                <div class="info-sub"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
            
            <div class="grid-moderne">
                <div class="info-moderne">
                    <div class="info-label">APPAREIL</div>
                    <div class="info-value" style="font-size: 10px;"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                    <div class="info-sub"><?php echo htmlspecialchars($reparation['modele']); ?></div>
                </div>
                <div class="info-moderne">
                    <div class="info-label">DATE</div>
                    <div class="info-value"><?php echo $date_reception; ?></div>
                </div>
            </div>
            
            <div class="grid-moderne">
                <div class="info-moderne">
                    <div class="info-label">CODE</div>
                    <div class="info-value"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : '—'; ?></div>
                </div>
                <div class="info-moderne">
                    <div class="info-label">PRIX</div>
                    <div class="info-value"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' E' : '—'; ?></div>
                </div>
            </div>
            
            <div class="problem-moderne">
                <div class="problem-head">PROBLEME</div>
                <div class="problem-body">
                    <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 100)); ?><?php echo strlen($reparation['description_probleme']) > 100 ? '...' : ''; ?>
                </div>
            </div>
        </div>
        
        <div class="qr-moderne">
            <div class="qr-box">
                <div id="qrcode_moderne"></div>
            </div>
            <div class="qr-label">SCAN</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_moderne"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 70, height: 70, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
