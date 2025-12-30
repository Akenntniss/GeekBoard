<?php
/**
 * Layout Professional - Format 4x6" (Imprimante thermique)
 * Design classique et élégant - NOIR ET BLANC - Optimisé thermique
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$repair_number = str_pad($reparation['id'], 5, '0', STR_PAD_LEFT);
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
.label-pro { 
    width: 4in; 
    height: 6in; 
    padding: 3mm; 
    box-sizing: border-box; 
    display: flex;
    flex-direction: column;
}

/* Conteneur interne avec bordure */
.inner-container {
    border: 3px solid #000;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* En-tête */
.header-pro { 
    text-align: center; 
    padding: 10px 12px; 
    border-bottom: 3px solid #000;
}
.company-pro { 
    font-size: 18px; 
    font-weight: 900; 
    letter-spacing: 3px; 
    margin: 0; 
}
.subtitle-pro { 
    font-size: 9px; 
    margin: 4px 0 0 0;
    letter-spacing: 1px;
    font-weight: 600;
}

/* Badge numéro de dossier */
.repair-badge-pro { 
    text-align: center; 
    font-size: 16px; 
    font-weight: 900; 
    padding: 8px; 
    background: #000; 
    color: white;
    letter-spacing: 2px;
}

/* Section données */
.data-section-pro {
    flex: 1;
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
}
.data-row-pro { 
    display: flex; 
    border-bottom: 1px solid #000; 
    padding: 6px 0; 
}
.data-row-pro:last-child {
    border-bottom: none;
}
.data-label-pro { 
    width: 35%; 
    font-size: 9px; 
    font-weight: 700;
    letter-spacing: 0.5px;
}
.data-value-pro { 
    width: 65%; 
    font-size: 10px;
    font-weight: 700;
    color: #000;
}
.data-value-pro.highlight {
    font-weight: 900;
}
.data-value-pro.code {
    font-weight: 900;
    font-size: 11px;
}
.data-value-pro.price {
    font-weight: 900;
    font-size: 12px;
}

/* Séparateur épais */
.separator-pro {
    border-top: 2px solid #000;
    margin: 4px 0;
}

/* Zone description */
.description-pro { 
    border: 2px solid #000; 
    padding: 8px; 
    margin: 8px 10px;
    flex: 1;
    min-height: 40px;
}
.description-title-pro { 
    font-size: 9px; 
    font-weight: 900; 
    border-bottom: 1px solid #000; 
    padding-bottom: 4px; 
    margin-bottom: 5px;
    letter-spacing: 1px;
}
.description-text-pro { 
    font-size: 9px; 
    line-height: 1.4; 
}

/* Zone QR */
.qr-section-pro { 
    border-top: 3px solid #000;
    padding: 8px 10px; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}
.qr-wrapper-pro {
    border: 2px solid #000;
    padding: 4px;
    background: white;
}
.qr-text-pro {
    font-size: 9px;
    font-weight: 700;
    text-align: left;
    line-height: 1.3;
    letter-spacing: 0.5px;
}

@media print { 
    body, .label-pro { filter: grayscale(100%); } 
}
</style>

<div class="label-pro">
    <div class="inner-container">
        <div class="header-pro">
            <div class="company-pro"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
            <div class="subtitle-pro">CENTRE DE RÉPARATION</div>
        </div>
        
        <div class="repair-badge-pro">
            DOSSIER N° <?php echo $repair_number; ?>
        </div>
        
        <div class="data-section-pro">
            <div class="data-row-pro">
                <div class="data-label-pro">CLIENT</div>
                <div class="data-value-pro highlight"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            </div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">TÉLÉPHONE</div>
                <div class="data-value-pro"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">APPAREIL</div>
                <div class="data-value-pro"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
            </div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">MODÈLE</div>
                <div class="data-value-pro highlight"><?php echo htmlspecialchars($reparation['modele']); ?></div>
            </div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">DATE DÉPÔT</div>
                <div class="data-value-pro"><?php echo $date_reception; ?></div>
            </div>
            
            <div class="separator-pro"></div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">[CODE]</div>
                <div class="data-value-pro code"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : '—'; ?></div>
            </div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">STATUT</div>
                <div class="data-value-pro highlight"><?php echo htmlspecialchars($reparation['statut']); ?></div>
            </div>
            
            <div class="data-row-pro">
                <div class="data-label-pro">PRIX</div>
                <div class="data-value-pro price"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></div>
            </div>
        </div>
        
        <div class="description-pro">
            <div class="description-title-pro">PROBLÈME SIGNALÉ</div>
            <div class="description-text-pro">
                <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 120)); ?><?php echo strlen($reparation['description_probleme']) > 120 ? '...' : ''; ?>
            </div>
        </div>
        
        <div class="qr-section-pro">
            <div class="qr-wrapper-pro">
                <div id="qrcode_pro"></div>
            </div>
            <div class="qr-text-pro">
                SCAN POUR<br>ACCÈS RAPIDE<br>AU DOSSIER
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_pro"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 70, 
        height: 70, 
        colorDark: "#000000", 
        colorLight: "#ffffff", 
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
