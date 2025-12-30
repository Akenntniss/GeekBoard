<?php
/**
 * Layout A4 Devis Client - Format document à fournir au client
 * Design professionnel pour devis/confirmation - AVEC COULEURS
 */
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
$date_actuelle = date('d/m/Y');
$company_name = $reparation['company_name'] ?? 'MAISON DU GEEK';
?>
<style>
@page { size: A4; margin: 0; }
body { 
    width: 210mm; 
    height: 297mm; 
    margin: 0; 
    padding: 20mm; 
    font-family: 'Arial', sans-serif; 
    background: white; 
    color: #333;
}

.devis-container { 
    background: white; 
    padding: 0; 
    height: 100%; 
}

/* En-tête entreprise */
.letterhead { 
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
    color: white; 
    padding: 30px 40px; 
    margin-bottom: 30px; 
}
.company-info { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}
.company-name { 
    font-size: 36px; 
    font-weight: 900; 
    letter-spacing: 3px; 
    margin: 0; 
}
.company-tagline { 
    font-size: 14px; 
    margin-top: 8px; 
    opacity: 0.9; 
}
.document-type { 
    background: #3498db; 
    padding: 15px 25px; 
    border-radius: 8px; 
    text-align: center; 
    font-weight: bold; 
    font-size: 16px; 
}

/* Informations document */
.document-header { 
    display: flex; 
    justify-content: space-between; 
    margin-bottom: 30px; 
    padding: 20px; 
    background: #ecf0f1; 
    border-left: 5px solid #3498db; 
}
.doc-info { 
    flex: 1; 
}
.doc-title { 
    font-size: 24px; 
    font-weight: bold; 
    color: #2c3e50; 
    margin: 0 0 10px 0; 
}
.doc-number { 
    font-size: 18px; 
    color: #3498db; 
    font-weight: bold; 
}
.doc-dates { 
    text-align: right; 
}
.date-item { 
    margin-bottom: 8px; 
}
.date-label { 
    font-size: 12px; 
    color: #7f8c8d; 
    font-weight: bold; 
}
.date-value { 
    font-size: 14px; 
    color: #2c3e50; 
    font-weight: bold; 
}

/* Informations client */
.client-section { 
    background: #f8f9fa; 
    padding: 25px; 
    border-radius: 10px; 
    margin-bottom: 30px; 
    border: 1px solid #dee2e6; 
}
.section-title { 
    font-size: 18px; 
    font-weight: bold; 
    color: #2c3e50; 
    margin-bottom: 15px; 
    padding-bottom: 8px; 
    border-bottom: 2px solid #3498db; 
}
.client-info { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
}
.info-item { 
    display: flex; 
    flex-direction: column; 
}
.info-label { 
    font-size: 12px; 
    color: #7f8c8d; 
    font-weight: bold; 
    text-transform: uppercase; 
    margin-bottom: 5px; 
}
.info-value { 
    font-size: 16px; 
    color: #2c3e50; 
    font-weight: 600; 
}

/* Détails réparation */
.repair-details { 
    background: white; 
    border: 2px solid #3498db; 
    border-radius: 10px; 
    padding: 25px; 
    margin-bottom: 30px; 
}
.device-info { 
    display: grid; 
    grid-template-columns: 1fr 1fr 1fr; 
    gap: 20px; 
    margin-bottom: 20px; 
}
.problem-description { 
    background: #fff3cd; 
    border: 1px solid #ffeaa7; 
    border-radius: 8px; 
    padding: 20px; 
    margin-bottom: 20px; 
}
.problem-title { 
    font-size: 14px; 
    font-weight: bold; 
    color: #856404; 
    margin-bottom: 10px; 
    text-transform: uppercase; 
}
.problem-text { 
    font-size: 14px; 
    line-height: 1.6; 
    color: #333; 
}

/* Prix et conditions */
.pricing-section { 
    background: #d4edda; 
    border: 2px solid #27ae60; 
    border-radius: 10px; 
    padding: 25px; 
    margin-bottom: 30px; 
}
.price-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
}
.price-item { 
    text-align: center; 
}
.price-label { 
    font-size: 14px; 
    color: #27ae60; 
    font-weight: bold; 
    margin-bottom: 8px; 
}
.price-value { 
    font-size: 28px; 
    color: #2c3e50; 
    font-weight: bold; 
}

/* QR Code et suivi */
.tracking-section { 
    text-align: center; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; 
    padding: 30px; 
    border-radius: 15px; 
    margin-bottom: 30px; 
}
.qr-container { 
    background: white; 
    display: inline-block; 
    padding: 20px; 
    border-radius: 15px; 
    margin-bottom: 15px; 
}
.tracking-text { 
    font-size: 18px; 
    font-weight: bold; 
    margin-bottom: 10px; 
}
.tracking-url { 
    font-size: 14px; 
    opacity: 0.9; 
}

/* Pied de page */
.footer { 
    background: #2c3e50; 
    color: white; 
    padding: 20px; 
    text-align: center; 
    font-size: 12px; 
    margin-top: auto; 
}

@media print { 
    body { 
        padding: 15mm; 
    } 
}
</style>

<div class="devis-container">
    <!-- En-tête entreprise -->
    <div class="letterhead">
        <div class="company-info">
            <div>
                <div class="company-name"><?php echo htmlspecialchars(strtoupper($company_name)); ?></div>
                <div class="company-tagline">Réparation & Service Technique</div>
            </div>
            <div class="document-type">
                DEVIS / CONFIRMATION
            </div>
        </div>
    </div>
    
    <!-- Informations document -->
    <div class="document-header">
        <div class="doc-info">
            <div class="doc-title">Réparation d'Appareil</div>
            <div class="doc-number">N° <?php echo $reparation['id']; ?></div>
        </div>
        <div class="doc-dates">
            <div class="date-item">
                <div class="date-label">Date de dépôt</div>
                <div class="date-value"><?php echo $date_reception; ?></div>
            </div>
            <div class="date-item">
                <div class="date-label">Date d'édition</div>
                <div class="date-value"><?php echo $date_actuelle; ?></div>
            </div>
        </div>
    </div>
    
    <!-- Informations client -->
    <div class="client-section">
        <div class="section-title">👤 Informations Client</div>
        <div class="client-info">
            <div class="info-item">
                <div class="info-label">Nom et Prénom</div>
                <div class="info-value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Téléphone</div>
                <div class="info-value"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Détails de la réparation -->
    <div class="repair-details">
        <div class="section-title">🔧 Détails de la Réparation</div>
        
        <div class="device-info">
            <div class="info-item">
                <div class="info-label">Type d'appareil</div>
                <div class="info-value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Modèle</div>
                <div class="info-value"><?php echo htmlspecialchars($reparation['modele']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Statut</div>
                <div class="info-value"><?php echo htmlspecialchars($reparation['statut']); ?></div>
            </div>
        </div>
        
        <div class="problem-description">
            <div class="problem-title">⚠️ Problème signalé</div>
            <div class="problem-text"><?php echo htmlspecialchars($reparation['description_probleme']); ?></div>
        </div>
        
        <?php if (!empty($reparation['mot_de_passe'])): ?>
        <div class="info-item">
            <div class="info-label">Code d'accès fourni</div>
            <div class="info-value">Oui (conservé en sécurité)</div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Prix et conditions -->
    <div class="pricing-section">
        <div class="section-title">💰 Tarification</div>
        <div class="price-grid">
            <div class="price-item">
                <div class="price-label">Prix estimé</div>
                <div class="price-value">
                    <?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'À définir'; ?>
                </div>
            </div>
            <div class="price-item">
                <div class="price-label">Diagnostic</div>
                <div class="price-value">Gratuit</div>
            </div>
        </div>
    </div>
    
    <!-- Suivi en ligne -->
    <div class="tracking-section">
        <div class="tracking-text">📱 Suivez votre réparation en ligne</div>
        <div class="qr-container">
            <div id="qrcode_devis"></div>
        </div>
        <div class="tracking-text">Scannez ce QR code avec votre téléphone</div>
        <div class="tracking-url">ou rendez-vous sur notre site web</div>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <p><strong><?php echo htmlspecialchars(strtoupper($company_name)); ?></strong> - Service de réparation professionnel</p>
        <p>Ce document confirme la prise en charge de votre appareil • Conservez-le précieusement</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode_devis"), {
        text: window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>',
        width: 150,
        height: 150,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
