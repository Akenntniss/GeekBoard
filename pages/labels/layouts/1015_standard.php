<?php
/**
 * Layout 1015 Standard - Format Étiquette Thermique 10x15cm
 * Design standard pour imprimante thermique - NOIR ET BLANC UNIQUEMENT
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$company_name = $reparation['company_name'] ?? 'MAISON DU GEEK';
?>
<style>
@page { size: 10cm 15cm !important; margin: 0 !important; }
body { 
    width: 10cm; 
    height: 15cm; 
    margin: 0; 
    padding: 0; 
    font-family: 'Arial', sans-serif; 
    background: white; 
    color: black;
}
.label-1015 { 
    width: 10cm; 
    height: 15cm; 
    padding: 0.5cm; 
    box-sizing: border-box; 
    border: 2px solid #000;
}

/* En-tête avec logo */
.header-1015 { 
    text-align: center; 
    border-bottom: 3px solid #000; 
    padding-bottom: 12px; 
    margin-bottom: 15px; 
}
.brand-name-1015 { 
    font-size: 28px; 
    font-weight: 900; 
    letter-spacing: 4px; 
    margin: 0; 
}
.subtitle-1015 { 
    font-size: 12px; 
    margin: 5px 0 0 0; 
    font-weight: bold;
}

/* Numéro de réparation */
.repair-header-1015 { 
    display: flex; 
    justify-content: space-between; 
    background: #000; 
    color: white; 
    padding: 12px 15px; 
    margin-bottom: 15px; 
    font-weight: bold; 
}
.repair-id-1015 { 
    font-size: 18px; 
}
.repair-status-1015 { 
    font-size: 14px; 
}

/* Informations client */
.info-section-1015 { 
    margin-bottom: 12px; 
    padding: 10px; 
    border-left: 4px solid #000; 
}
.info-label-1015 { 
    font-size: 11px; 
    font-weight: bold; 
    text-transform: uppercase; 
    letter-spacing: 1px; 
    margin-bottom: 3px; 
}
.info-value-1015 { 
    font-size: 14px; 
    font-weight: 500; 
}

/* Grille d'informations */
.info-grid-1015 { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 10px; 
    margin-bottom: 12px; 
}

/* Problème */
.problem-section-1015 { 
    background: #f5f5f5; 
    border: 2px solid #000; 
    padding: 10px; 
    margin-bottom: 15px; 
    min-height: 60px; 
}
.problem-title-1015 { 
    font-size: 11px; 
    font-weight: bold; 
    text-transform: uppercase; 
    margin-bottom: 5px; 
}
.problem-text-1015 { 
    font-size: 12px; 
    line-height: 1.4; 
}

/* QR Code */
.qr-section-1015 { 
    text-align: center; 
    padding: 15px; 
    border: 2px solid #000; 
}
.qr-title-1015 { 
    font-size: 11px; 
    margin-top: 8px; 
    font-weight: bold; 
}

@media print { 
    body, .label-1015 { 
        filter: grayscale(100%); 
    } 
}
</style>

<div class="label-1015">
    <!-- En-tête -->
    <div class="header-1015">
        <h1 class="brand-name-1015"><?php echo htmlspecialchars(strtoupper($company_name)); ?></h1>
        <div class="subtitle-1015">Service de Réparation</div>
    </div>
    
    <!-- Numéro de réparation et statut -->
    <div class="repair-header-1015">
        <span class="repair-id-1015">N° <?php echo $reparation['id']; ?></span>
        <span class="repair-status-1015"><?php echo strtoupper(htmlspecialchars($reparation['statut'])); ?></span>
    </div>
    
    <!-- Informations client -->
    <div class="info-section-1015">
        <div class="info-label-1015">CLIENT</div>
        <div class="info-value-1015"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
        <div class="info-value-1015" style="font-size: 13px; color: #666;"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
    </div>
    
    <!-- Grille d'informations -->
    <div class="info-grid-1015">
        <div class="info-section-1015">
            <div class="info-label-1015">APPAREIL</div>
            <div class="info-value-1015" style="font-size: 12px;"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
            <div class="info-value-1015" style="font-size: 11px;"><?php echo htmlspecialchars($reparation['modele']); ?></div>
        </div>
        <div class="info-section-1015">
            <div class="info-label-1015">DATE DÉPÔT</div>
            <div class="info-value-1015"><?php echo $date_reception; ?></div>
        </div>
    </div>
    
    <div class="info-grid-1015">
        <div class="info-section-1015">
            <div class="info-label-1015">MOT DE PASSE</div>
            <div class="info-value-1015"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></div>
        </div>
        <div class="info-section-1015">
            <div class="info-label-1015">PRIX</div>
            <div class="info-value-1015"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?></div>
        </div>
    </div>
    
    <!-- Description du problème -->
    <div class="problem-section-1015">
        <div class="problem-title-1015">PROBLÈME SIGNALÉ</div>
        <div class="problem-text-1015"><?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 200)); ?></div>
    </div>
    
    <!-- QR Code -->
    <div class="qr-section-1015">
        <div id="qrcode_1015"></div>
        <div class="qr-title-1015">SCAN POUR SUIVI</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_1015"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 120,
        height: 120,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
