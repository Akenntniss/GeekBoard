<?php
/**
 * Layout Business - Format 4x6" (Imprimante thermique)
 * Design professionnel et structuré - NOIR ET BLANC PUR
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
.label-business { 
    width: 4in; 
    height: 6in; 
    padding: 3mm; 
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

/* Conteneur structuré */
.inner-business {
    border: 3px solid #000;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* En-tête corporate */
.header-business { 
    text-align: center; 
    padding: 10px 12px;
    border-bottom: 3px solid #000;
}
.company-business { 
    font-size: 18px; 
    font-weight: 900; 
    letter-spacing: 3px; 
    margin: 0; 
}
.tagline-business {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 2px;
    margin-top: 4px;
}

/* Badge dossier */
.badge-business { 
    background: #000; 
    color: #fff; 
    padding: 10px 12px; 
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.badge-num {
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 1px;
}
.badge-stat {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 1px;
}

/* Contenu structuré */
.content-business {
    flex: 1;
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
}

/* Sections avec titres */
.section-business {
    margin-bottom: 8px;
}
.section-title {
    font-size: 8px;
    font-weight: 900;
    letter-spacing: 1px;
    background: #000;
    color: #fff;
    padding: 3px 8px;
    margin-bottom: 6px;
}

/* Lignes de données */
.data-row {
    display: flex;
    border-bottom: 1px solid #000;
    padding: 5px 0;
}
.data-row:last-child {
    border-bottom: none;
}
.data-label {
    width: 35%;
    font-size: 9px;
    font-weight: 900;
}
.data-value {
    width: 65%;
    font-size: 10px;
    font-weight: 700;
}

/* Zone problème */
.problem-business { 
    border: 2px solid #000; 
    padding: 8px;
    flex: 1;
    min-height: 35px;
}
.problem-title-bus {
    font-size: 8px;
    font-weight: 900;
    letter-spacing: 1px;
    border-bottom: 1px solid #000;
    padding-bottom: 4px;
    margin-bottom: 5px;
}
.problem-text-bus {
    font-size: 9px;
    font-weight: 700;
    line-height: 1.4;
}

/* QR Footer */
.qr-business { 
    border-top: 3px solid #000;
    padding: 8px 10px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}
.qr-wrap {
    border: 2px solid #000;
    padding: 4px;
}
.qr-text-bus {
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1px;
    text-align: left;
    line-height: 1.3;
}

@media print { 
    body, .label-business { filter: grayscale(100%); }
}
</style>

<div class="label-business">
    <div class="inner-business">
        <div class="header-business">
            <div class="company-business"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
            <div class="tagline-business">SERVICE REPARATION</div>
        </div>
        
        <div class="badge-business">
            <span class="badge-num">DOSSIER #<?php echo $repair_number; ?></span>
            <span class="badge-stat"><?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?></span>
        </div>
        
        <div class="content-business">
            <div class="section-business">
                <div class="section-title">CLIENT</div>
                <div class="data-row">
                    <div class="data-label">NOM</div>
                    <div class="data-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                </div>
                <div class="data-row">
                    <div class="data-label">TEL</div>
                    <div class="data-value"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
                </div>
            </div>
            
            <div class="section-business">
                <div class="section-title">APPAREIL</div>
                <div class="data-row">
                    <div class="data-label">TYPE</div>
                    <div class="data-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
                </div>
                <div class="data-row">
                    <div class="data-label">MODELE</div>
                    <div class="data-value"><?php echo htmlspecialchars($reparation['modele']); ?></div>
                </div>
                <div class="data-row">
                    <div class="data-label">CODE</div>
                    <div class="data-value"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : '—'; ?></div>
                </div>
            </div>
            
            <div class="section-business">
                <div class="section-title">DETAILS</div>
                <div class="data-row">
                    <div class="data-label">DATE</div>
                    <div class="data-value"><?php echo $date_reception; ?></div>
                </div>
                <div class="data-row">
                    <div class="data-label">PRIX</div>
                    <div class="data-value"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' E' : 'A definir'; ?></div>
                </div>
            </div>
            
            <div class="problem-business">
                <div class="problem-title-bus">PROBLEME SIGNALE</div>
                <div class="problem-text-bus">
                    <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 80)); ?><?php echo strlen($reparation['description_probleme']) > 80 ? '...' : ''; ?>
                </div>
            </div>
        </div>
        
        <div class="qr-business">
            <div class="qr-wrap">
                <div id="qrcode_business"></div>
            </div>
            <div class="qr-text-bus">SCAN POUR<br>ACCES RAPIDE<br>AU DOSSIER</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_business"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 65, height: 65, colorDark: "#000000", colorLight: "#ffffff", correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
