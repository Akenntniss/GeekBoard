<?php
// Débogage de session
error_log("============= DÉBUT IMPRIMER_ETIQUETTE =============");
error_log("Session ID: " . session_id());
error_log("Variables de session: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));
error_log("shop_id en session: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini'));
error_log("user_id en session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non défini'));
error_log("============= FIN DEBUG SESSION =============");

// Si la session utilisateur n'est pas active, essayer une autre méthode d'authentification
if (!isset($_SESSION['user_id'])) {
    error_log("Tentative d'accès à imprimer_etiquette sans session utilisateur");
    
    // Validation de l'ID de réparation comme critère minimal de sécurité
    $repair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($repair_id <= 0) {
        error_log("ID de réparation invalide pour imprimer_etiquette: " . $_GET['id']);
        redirect("reparations");
        exit;
    }
    
    // Si l'ID du magasin n'est pas défini, utiliser une valeur par défaut ou essayer de la récupérer
    if (!isset($_SESSION['shop_id'])) {
        // Essayer de récupérer depuis un cookie
        if (isset($_COOKIE['current_shop'])) {
            $_SESSION['shop_id'] = $_COOKIE['current_shop'];
            error_log("Shop ID récupéré depuis cookie pour impression: " . $_SESSION['shop_id']);
        }
        // Ou définir une valeur par défaut (généralement shop_id=1 pour le magasin principal)
        else {
            $_SESSION['shop_id'] = 1;
            error_log("Utilisation du shop_id par défaut (1) pour impression");
        }
    }
    
    // Définir un user_id temporaire pour l'opération d'impression
    $_SESSION['temp_auth_for_print'] = true;
    error_log("Session temporaire créée pour impression d'étiquette");
}

// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];

// Récupérer les informations de la réparation
try {
    // Utiliser explicitement la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        error_log("Impossible d'obtenir une connexion à la base de données du magasin");
        throw new Exception("Impossible de se connecter à la base de données.");
    }
    
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    error_log("Erreur PDO dans imprimer_etiquette.php: " . $e->getMessage());
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
} catch (Exception $e) {
    error_log("Exception dans imprimer_etiquette.php: " . $e->getMessage());
    set_message("Erreur: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Formater la date d'entrée
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <!-- Boutons de contrôle -->
            <div class="pwa-controls">
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="mb-3">Étiquette de réparation #<?php echo $reparation_id; ?></h4>
                        <div class="button-container">
                            <button id="btnImprimer" class="btn btn-primary">
                                <i class="fas fa-print"></i><span>Imprimer</span>
                            </button>
                            <button id="btnImprimerAlt" class="btn btn-outline-primary">
                                <i class="fas fa-print"></i><span>Imprimer (Alt)</span>
                            </button>
                            <button id="btnImprimerPDF" class="btn btn-outline-success">
                                <i class="fas fa-file-pdf"></i><span>PDF</span>
                            </button>
                            <button id="btnTelecharger" class="btn btn-outline-info">
                                <i class="fas fa-external-link-alt"></i><span>Ouvrir</span>
                            </button>
                            <a href="index.php?page=reparations" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i><span>Retour</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Étiquette à imprimer -->
            <div class="card">
                <div class="card-body">
                    <div id="etiquette" class="p-2" style="width: 4in; height: 6in; border: 1px dashed #ccc; margin: 0 auto; background-color: white;">
                        <!-- Contenu de l'étiquette -->
                        <div class="text-center mb-2">
                            <h2 class="fw-bold" style="text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 6px; font-size: 1.3rem;">MAISON DU GEEK</h2>
                        </div>
                        
                        <!-- En-tête avec ID et statut -->
                        <div class="header" style="border: 1px solid #000; padding: 6px; border-radius: 4px; background-color: white; display: flex; justify-content: space-between; margin-bottom: 0.2in;">
                            <div class="label" style="font-weight: bold; font-size: 14px;">N° <?php echo $reparation_id; ?></div>
                            <div class="label" style="font-weight: bold; font-size: 14px; text-transform: uppercase;"><?php echo htmlspecialchars($reparation['statut']); ?></div>
                        </div>
                        
                        <!-- Deux colonnes: Client et Notes techniques (anciennement Problème) -->
                        <div style="display: flex; margin-bottom: 0.15in;">
                            <!-- Client -->
                            <div style="flex: 1; padding-right: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">CLIENT</div>
                                    <div style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                                    <div style="font-size: 12px;"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
                                </div>
                            </div>
                            
                            <!-- Notes techniques -->
                            <div style="margin-bottom: 0.15in;">
                                <div style="border-left: 3px solid #000; padding-left: 8px;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">NOTES TECHNIQUES</div>
                                    <div style="font-size: 11px; background-color: white; padding: 4px; border-radius: 3px; border: 1px solid #000;">
                                        <?php if (!empty($reparation['notes_techniques'])): ?>
                                            <span style="font-weight: bold;">OUI</span>
                                        <?php else: ?>
                                            <span style="font-weight: bold;">NON</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modèle et Mot de passe -->
                        <div style="display: flex; margin-bottom: 0.15in;">
                            <!-- Modèle -->
                            <div style="flex: 1; padding-right: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">MODÈLE</div>
                                    <div style="font-size: 12px;"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?></div>
                                </div>
                            </div>
                            
                            <!-- Mot de passe -->
                            <div style="flex: 1; padding-left: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">MOT DE PASSE</div>
                                    <div style="font-size: 12px; background-color: white; padding: 2px 4px; border-radius: 3px; display: inline-block; border: 1px solid #000;"><?php echo !empty($reparation['mot_de_passe']) ? htmlspecialchars($reparation['mot_de_passe']) : 'Non défini'; ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Problème (anciennement Notes techniques) -->
                        <div style="margin-bottom: 0.15in;">
                            <div style="border-left: 3px solid #000; padding-left: 8px;">
                                <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">PROBLÈME</div>
                                <div style="font-size: 11px; max-height: 40px; overflow: auto; background-color: white; padding: 4px; border-radius: 3px; margin-bottom: 4px; border: 1px solid #000;"><?php echo html_entity_decode(htmlspecialchars(substr($reparation['description_probleme'], 0, 120))); ?><?php echo strlen($reparation['description_probleme']) > 120 ? '...' : ''; ?></div>
                            </div>
                        </div>
                        
                        <!-- Date d'entrée et Prix -->
                        <div style="display: flex; margin-bottom: 0.15in;">
                            <!-- Date d'entrée -->
                            <div style="flex: 1; padding-right: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">DATE D'ENTRÉE</div>
                                    <div style="font-size: 12px;"><?php echo $date_reception; ?></div>
                                </div>
                            </div>
                            
                            <!-- Prix -->
                            <div style="flex: 1; padding-left: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">PRIX</div>
                                    <div style="font-size: 12px; font-weight: 500;"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'Non défini'; ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- QR Codes -->
                        <div class="text-center mb-2" style="background-color: white; padding: 6px; border-radius: 4px; border: 1px solid #000;">
                            <div class="row g-2">
                                <div class="col-12">
                                    <!-- QR Code - Statut Rapide -->
                                    <div id="qrcode_statut" style="display: inline-block; background-color: white; padding: 4px; border: 1px solid #000;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour générer le QR code -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // URL pour les QR codes
    const qrUrlStatut = window.location.origin + '/index.php?page=statut_rapide&id=<?php echo $reparation_id; ?>';
    
    // Générer le QR code pour le statut rapide
    try {
        new QRCode(document.getElementById("qrcode_statut"), {
            text: qrUrlStatut,
            width: 95,
            height: 95,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        console.log("QR code statut généré avec succès");
    } catch (error) {
        console.error("Erreur lors de la génération du QR code statut:", error);
        document.getElementById("qrcode_statut").innerHTML = "<p>Erreur QR</p>";
    }
    
    // Fonction pour imprimer l'étiquette
    document.getElementById('btnImprimer').addEventListener('click', function() {
        try {
            console.log("Bouton imprimer cliqué");
            
            // S'assurer que le QR code est bien généré avant d'imprimer
            const qrCodeStatutImg = document.querySelector('#qrcode_statut img');
            
            if (!qrCodeStatutImg) {
                console.error("Le QR code n'est pas complètement généré");
                alert("Veuillez patienter, le QR code est en cours de génération...");
                return;
            }

            // Sauvegarder l'état actuel de la page
            const originalContent = document.body.innerHTML;
            const etiquetteContent = document.getElementById('etiquette').outerHTML;
            
            // Simplifier la page pour l'impression
            document.body.innerHTML = `
                <style>
                    @page {
                        size: 4in 6in !important;
                        margin: 0 !important;
                    }
                    html, body {
                        width: 4in !important;
                        height: 6in !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        overflow: hidden !important;
                        filter: grayscale(100%);
                    }
                    #etiquette {
                        width: 4in !important;
                        height: 6in !important;
                        margin: 0 !important;
                        padding: 0.3in !important;
                        box-sizing: border-box !important;
                        background-color: white !important;
                        font-family: Arial, sans-serif !important;
                        position: absolute !important;
                        top: 0 !important;
                        left: 0 !important;
                        border: none !important;
                        filter: grayscale(100%);
                    }
                    #backButton {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        z-index: 9999;
                        background-color: #333;
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 60px;
                        height: 60px;
                        font-size: 24px;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                        display: none;
                        cursor: pointer;
                    }
                    #actionsMenu {
                        position: fixed;
                        bottom: 20px;
                        left: 20px;
                        z-index: 9999;
                        background-color: white;
                        border-radius: 10px;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                        display: none;
                        padding: 10px;
                    }
                    #actionsMenu button {
                        display: block;
                        width: 100%;
                        padding: 10px;
                        margin: 5px 0;
                        background-color: white;
                        border: 1px solid #000;
                        border-radius: 5px;
                        text-align: left;
                        cursor: pointer;
                    }
                    #actionsMenu button:hover {
                        background-color: #eee;
                    }
                    @media print {
                        #backButton, #actionsMenu {
                            display: none !important;
                        }
                    }
                </style>
                ${etiquetteContent}
                <div id="afterPrintControls">
                    <button id="backButton" title="Retour">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div id="actionsMenu">
                        <button id="btnReturnToApp">
                            <i class="fas fa-home"></i> Retour à l'application
                        </button>
                        <button id="btnReprint">
                            <i class="fas fa-print"></i> Réimprimer
                        </button>
                        <button id="btnReload">
                            <i class="fas fa-sync"></i> Restaurer l'interface
                        </button>
                    </div>
                </div>
            `;
            
            // Lancer l'impression
            window.print();
            
            // Afficher le bouton de retour après impression
            setTimeout(function() {
                const backButton = document.getElementById('backButton');
                if (backButton) backButton.style.display = 'block';
                
                // Ajouter les gestionnaires d'événements
                const afterPrintControls = document.getElementById('afterPrintControls');
                if (afterPrintControls) {
                    // Configurer le bouton de retour
                    document.getElementById('backButton')?.addEventListener('click', function() {
                        const actionsMenu = document.getElementById('actionsMenu');
                            if (actionsMenu.style.display === 'block') {
                                actionsMenu.style.display = 'none';
                            } else {
                                actionsMenu.style.display = 'block';
                        }
                    });
                    
                    // Configurer les actions du menu
                    document.getElementById('btnReturnToApp')?.addEventListener('click', function() {
                        window.location.href = 'index.php?page=reparations';
                    });
                    
                    document.getElementById('btnReprint')?.addEventListener('click', function() {
                        window.print();
                    });
                    
                    document.getElementById('btnReload')?.addEventListener('click', function() {
                        window.location.reload();
                    });
                }
            }, 1000);
            
            // Également afficher le bouton après la fin ou l'annulation de l'impression
            window.addEventListener('afterprint', function() {
                const backButton = document.getElementById('backButton');
                if (backButton) backButton.style.display = 'block';
            });
            
        } catch (error) {
            console.error("Erreur d'impression:", error);
            alert("Une erreur est survenue lors de l'impression. Détails: " + error.message);
        }
    });
    
    // Méthode d'impression alternative qui utilise une iframe
    document.getElementById('btnImprimerAlt').addEventListener('click', function() {
        try {
            console.log("Méthode d'impression alternative activée");
            
            // S'assurer que le QR code est bien généré
            const qrCodeStatutImg = document.querySelector('#qrcode_statut img');
            
            if (!qrCodeStatutImg) {
                console.error("Le QR code n'est pas complètement généré");
                alert("Veuillez patienter, le QR code est en cours de génération...");
                return;
            }
            
            // Obtenir les URLs des images QR
            const qrCodeStatutSrc = qrCodeStatutImg.src;
            
            // Créer une iframe invisible
            const iframe = document.createElement('iframe');
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.position = 'absolute';
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);
            
            // Contenu de l'étiquette
            const etiquetteContent = document.getElementById('etiquette').innerHTML;
            const clientNom = "<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>";
            const clientTel = "<?php echo htmlspecialchars($reparation['client_telephone']); ?>";
            const appareil = "<?php echo htmlspecialchars($reparation['type_appareil']); ?>";
            const modele = "<?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?>";
            const problemDescription = "<?php echo addslashes(html_entity_decode(htmlspecialchars(substr($reparation['description_probleme'], 0, 120)))); ?><?php echo strlen($reparation['description_probleme']) > 120 ? '...' : ''; ?>";
            const motDePasse = "<?php echo addslashes(htmlspecialchars($reparation['mot_de_passe'])); ?>";
            const hasPassword = <?php echo !empty($reparation['mot_de_passe']) ? 'true' : 'false'; ?>;
            const notesTechniques = "<?php echo !empty($reparation['notes_techniques']) ? addslashes(htmlspecialchars(substr($reparation['notes_techniques'], 0, 150))) . (strlen($reparation['notes_techniques']) > 150 ? '...' : '') : 'Aucune note'; ?>";
            const dateReception = "<?php echo $date_reception; ?>";
            const prix = "<?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'Non défini'; ?>";
            const statut = "<?php echo htmlspecialchars($reparation['statut']); ?>";
            const repId = "<?php echo $reparation_id; ?>";
            
            // Définir le contenu de l'iframe
            const frameDoc = iframe.contentWindow.document;
            frameDoc.open();
            frameDoc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Étiquette #${repId}</title>
                    <style>
                        @page {
                            size: 4in 6in !important;
                            margin: 0 !important;
                        }
                        body {
                            width: 4in;
                            height: 6in;
                            margin: 0;
                            padding: 0.3in;
                            box-sizing: border-box;
                            font-family: Arial, sans-serif;
                            overflow: hidden;
                        }
                        .title {
                            text-align: center;
                            font-size: 1.3rem;
                            font-weight: bold;
                            margin-bottom: 0.2in;
                        }
                        .header {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 0.15in;
                        }
                        .section {
                            margin-bottom: 0.25in;
                        }
                        .label {
                            font-weight: bold;
                        }
                        .qr-container {
                            display: flex;
                            justify-content: center;
                            margin: 0.15in 0;
                        }
                        .qr-code {
                            text-align: center;
                        }
                        .footer {
                            text-align: center;
                            margin-top: 0.25in;
                        }
                    </style>
                </head>
                <body>
                    <div class="etiquette">
                        <div class="title" style="text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 6px; text-align: center; font-size: 1.3rem; font-weight: bold; margin-bottom: 0.2in;">MAISON DU GEEK</div>
                        
                        <div class="header" style="border: 1px solid #000; padding: 6px; border-radius: 4px; background-color: white; display: flex; justify-content: space-between; margin-bottom: 0.15in;">
                            <div class="label" style="font-weight: bold; font-size: 14px;">N° ${repId}</div>
                            <div class="label" style="font-weight: bold; font-size: 14px; text-transform: uppercase;">${statut}</div>
                        </div>
                        
                        <!-- Deux colonnes: Client et Notes techniques -->
                        <div style="display: flex; margin-bottom: 0.15in;">
                            <!-- Client -->
                            <div style="flex: 1; padding-right: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">CLIENT</div>
                                    <div style="font-size: 13px; font-weight: 500;">${clientNom}</div>
                                    <div style="font-size: 12px;">${clientTel}</div>
                                </div>
                            </div>
                            
                            <!-- Notes techniques -->
                            <div style="margin-bottom: 0.15in;">
                                <div style="border-left: 3px solid #000; padding-left: 8px;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">NOTES TECHNIQUES</div>
                                    <div style="font-size: 11px; background-color: white; padding: 4px; border-radius: 3px; border: 1px solid #000;">
                                        ${notesTechniques ? '<span style="font-weight: bold;">OUI</span>' : '<span style="font-weight: bold;">NON</span>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modèle et Mot de passe -->
                        <div style="display: flex; margin-bottom: 0.15in;">
                            <!-- Modèle -->
                            <div style="flex: 1; padding-right: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">MODÈLE</div>
                                    <div style="font-size: 12px;">${modele}</div>
                                </div>
                            </div>
                            
                            <!-- Mot de passe -->
                            <div style="flex: 1; padding-left: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">MOT DE PASSE</div>
                                    <div style="font-size: 12px; background-color: white; padding: 2px 4px; border-radius: 3px; display: inline-block; border: 1px solid #000;">${hasPassword ? motDePasse : 'Non défini'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Problème -->
                        <div style="margin-bottom: 0.15in;">
                            <div style="border-left: 3px solid #000; padding-left: 8px;">
                                <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">PROBLÈME</div>
                                <div style="font-size: 11px; height: 40px; overflow: hidden; text-overflow: ellipsis; background-color: white; padding: 4px; border-radius: 3px; margin-bottom: 4px; border: 1px solid #000;">${problemDescription}</div>
                            </div>
                        </div>
                        
                        <!-- Date d'entrée et Prix -->
                        <div style="display: flex; margin-bottom: 0.15in;">
                            <!-- Date d'entrée -->
                            <div style="flex: 1; padding-right: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">DATE D'ENTRÉE</div>
                                    <div style="font-size: 12px;">${dateReception}</div>
                                </div>
                            </div>
                            
                            <!-- Prix -->
                            <div style="flex: 1; padding-left: 8px;">
                                <div style="border-left: 3px solid #000; padding-left: 8px; height: 100%;">
                                    <div class="label" style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">PRIX</div>
                                    <div style="font-size: 12px; font-weight: 500;">${prix}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- QR Code -->
                        <div class="qr-container">
                            <div class="qr-code">
                                <img src="${qrCodeStatutSrc}" width="85" height="85" alt="QR Code Statut">
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `);
            frameDoc.close();
            
            // Imprimer et supprimer l'iframe après
            iframe.onload = function() {
                setTimeout(function() {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    
                    // Nettoyer après l'impression
                    setTimeout(function() {
                        document.body.removeChild(iframe);
                    }, 1000);
                }, 500);
            };
            
        } catch (error) {
            console.error("Erreur d'impression alternative:", error);
            alert("Une erreur est survenue lors de l'impression alternative. Détails: " + error.message);
        }
    });
    
    // Nouvelle fonction pour ouvrir dans un nouvel onglet
    document.getElementById('btnTelecharger').addEventListener('click', function() {
        try {
            console.log("Ouverture dans un nouvel onglet demandée");
            
            // S'assurer que le QR code est bien généré
            const qrCodeStatutImg = document.querySelector('#qrcode_statut img');
            
            if (!qrCodeStatutImg) {
                console.error("Le QR code n'est pas complètement généré");
                alert("Veuillez patienter, le QR code est en cours de génération...");
                return;
            }
            
            // Obtenir l'URL de l'image QR
            const qrCodeStatutSrc = qrCodeStatutImg.src;
            
            // Ouvrir dans une nouvelle fenêtre/onglet
            const newWindow = window.open('', '_blank');
            if (!newWindow) {
                alert("Impossible d'ouvrir une nouvelle fenêtre. Veuillez autoriser les pop-ups pour ce site.");
                return;
            }
            
            // Paramètres de l'étiquette
            const clientNom = "<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>";
            const clientTel = "<?php echo htmlspecialchars($reparation['client_telephone']); ?>";
            const modele = "<?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?>";
            const problemDescription = "<?php echo addslashes(html_entity_decode(htmlspecialchars(substr($reparation['description_probleme'], 0, 120)))); ?><?php echo strlen($reparation['description_probleme']) > 120 ? '...' : ''; ?>";
            const motDePasse = "<?php echo addslashes(htmlspecialchars($reparation['mot_de_passe'])); ?>";
            const hasPassword = <?php echo !empty($reparation['mot_de_passe']) ? 'true' : 'false'; ?>;
            const notesTechniques = "<?php echo !empty($reparation['notes_techniques']) ? addslashes(htmlspecialchars(substr($reparation['notes_techniques'], 0, 150))) . (strlen($reparation['notes_techniques']) > 150 ? '...' : '') : 'Aucune note'; ?>";
            const dateReception = "<?php echo $date_reception; ?>";
            const prix = "<?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'Non défini'; ?>";
            const statut = "<?php echo htmlspecialchars($reparation['statut']); ?>";
            const repId = "<?php echo $reparation_id; ?>";
            
            // Écrire le contenu HTML dans la nouvelle fenêtre
            newWindow.document.write('<!DOCTYPE html>');
            newWindow.document.write('<html>');
            newWindow.document.write('<head>');
            newWindow.document.write('<title>Étiquette #' + repId + '</title>');
            newWindow.document.write('<meta charset="UTF-8">');
            newWindow.document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
            newWindow.document.write('<style>');
            newWindow.document.write('@page { size: 4in 6in; margin: 0; }');
            newWindow.document.write('body { width: 4in; height: 6in; margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; overflow: hidden; }');
            newWindow.document.write('.etiquette { width: 4in; height: 6in; padding: 0.3in; box-sizing: border-box; background-color: white; }');
            newWindow.document.write('.title { text-align: center; font-size: 1.3rem; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 0.2in; }');
            newWindow.document.write('.header { border: 1px solid #000; padding: 6px; border-radius: 4px; background-color: white; display: flex; justify-content: space-between; margin-bottom: 0.15in; }');
            newWindow.document.write('.header-item { font-weight: bold; font-size: 14px; }');
            newWindow.document.write('.header-item.right { text-transform: uppercase; }');
            newWindow.document.write('.row { display: flex; margin-bottom: 0.15in; }');
            newWindow.document.write('.col { flex: 1; }');
            newWindow.document.write('.col-left { padding-right: 8px; }');
            newWindow.document.write('.col-right { padding-left: 8px; }');
            newWindow.document.write('.section { border-left: 3px solid #000; padding-left: 8px; height: 100%; }');
            newWindow.document.write('.label { font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px; }');
            newWindow.document.write('.value { font-size: 12px; }');
            newWindow.document.write('.value.name { font-size: 13px; font-weight: 500; }');
            newWindow.document.write('.value.desc { font-size: 11px; max-height: 40px; overflow: auto; background-color: white; padding: 4px; border-radius: 3px; margin-bottom: 4px; }');
            newWindow.document.write('.value.password { background-color: white; padding: 2px 4px; border-radius: 3px; display: inline-block; border: 1px solid #000; }');
            newWindow.document.write('.qr-container { background-color: white; padding: 6px; border-radius: 4px; text-align: center; margin: 0.15in 0; }');
            newWindow.document.write('.qr-code { background-color: white; padding: 4px; border: 1px solid #000; display: inline-block; }');
            newWindow.document.write('.qr-label { font-weight: 500; margin-top: 2px; font-size: 11px; }');
            newWindow.document.write('.footer { text-align: center; border-top: 1px solid #ccc; padding-top: 6px; }');
            newWindow.document.write('.footer-title { font-weight: bold; font-size: 12px; margin-bottom: 2px; }');
            newWindow.document.write('.footer-subtitle { font-size: 10px; }');
            newWindow.document.write('@media print { @page { size: 4in 6in; margin: 0; } body { width: 4in; height: 6in; } }');
            newWindow.document.write('</style>');
            newWindow.document.write('</head>');
            newWindow.document.write('<body>');
            newWindow.document.write('<div class="etiquette">');
            newWindow.document.write('<div class="title" style="text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 6px; text-align: center; font-size: 1.3rem; font-weight: bold; margin-bottom: 0.2in;">MAISON DU GEEK</div>');
            
            newWindow.document.write('<div class="header">');
            newWindow.document.write('<div class="header-item">N° ' + repId + '</div>');
            newWindow.document.write('<div class="header-item right">' + statut + '</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('<div class="row">');
            newWindow.document.write('<div class="col col-left">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">CLIENT</div>');
            newWindow.document.write('<div class="value name">' + clientNom + '</div>');
            newWindow.document.write('<div class="value">' + clientTel + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('<div class="col col-right">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">NOTES TECHNIQUES</div>');
            newWindow.document.write('<div class="value desc">' + notesTechniques + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('<div class="row">');
            newWindow.document.write('<div class="col col-left">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">MODÈLE</div>');
            newWindow.document.write('<div class="value">' + modele + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('<div class="col col-right">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">MOT DE PASSE</div>');
            newWindow.document.write('<div class="value password">' + (hasPassword ? motDePasse : 'Non défini') + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('<div class="row">');
            newWindow.document.write('<div class="col">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">PROBLÈME</div>');
            newWindow.document.write('<div class="value desc">' + problemDescription + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('<div class="row">');
            newWindow.document.write('<div class="col col-left">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">DATE D\'ENTRÉE</div>');
            newWindow.document.write('<div class="value">' + dateReception + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('<div class="col col-right">');
            newWindow.document.write('<div class="section">');
            newWindow.document.write('<div class="label">PRIX</div>');
            newWindow.document.write('<div class="value">' + prix + '</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('<div class="qr-container">');
            newWindow.document.write('<div class="qr-code">');
            newWindow.document.write('<img src="' + qrCodeStatutSrc + '" width="85" height="85" alt="QR Code Statut">');
            newWindow.document.write('</div>');
            newWindow.document.write('<div class="qr-label">Statut</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('<div class="footer">');
            newWindow.document.write('<div class="footer-title">MAISON DU GEEK</div>');
            newWindow.document.write('<div class="footer-subtitle">Scannez le QR code pour accéder aux détails</div>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            
            newWindow.document.write('</body>');
            newWindow.document.write('</html>');
            newWindow.document.close();
            
        } catch (error) {
            console.error("Erreur lors de l'ouverture du document:", error);
            alert("Une erreur est survenue lors de l'ouverture du document. Détails: " + error.message);
        }
    });
    
    // Méthode d'impression PDF avec html2pdf.js
    document.getElementById('btnImprimerPDF').addEventListener('click', function() {
        try {
            console.log("Export PDF demandé");
            
            // S'assurer que le QR code est bien généré
            const qrCodeStatutImg = document.querySelector('#qrcode_statut img');
            
            if (!qrCodeStatutImg) {
                console.error("Le QR code n'est pas complètement généré");
                alert("Veuillez patienter, le QR code est en cours de génération...");
                return;
            }
            
            // Créer une copie de l'étiquette pour ne pas modifier l'original
            const etiquetteOriginal = document.getElementById('etiquette');
            const etiquetteClone = etiquetteOriginal.cloneNode(true);
            
            // Capturer l'URL du QR code
            const qrImgSrc = qrCodeStatutImg.src;
            
            // Remplacer le div QR Code par une image statique
            const qrDiv = etiquetteClone.querySelector('#qrcode_statut');
            if (qrDiv) {
                qrDiv.innerHTML = '';
                const qrImg = document.createElement('img');
                qrImg.src = qrImgSrc;
                qrImg.width = 85;
                qrImg.height = 85;
                qrImg.alt = "QR Code";
                qrImg.style.backgroundColor = "white";
                qrImg.style.padding = "4px";
                qrImg.style.border = "1px solid #000";
                qrDiv.appendChild(qrImg);
            }
            
            // Définir le style pour l'impression PDF
            etiquetteClone.style.width = '4in';
            etiquetteClone.style.height = '6in';
            etiquetteClone.style.padding = '0.3in';
            etiquetteClone.style.boxSizing = 'border-box';
            etiquetteClone.style.border = 'none';
            etiquetteClone.style.backgroundColor = 'white';
            
            // Options pour html2pdf
            const optionsPDF = {
                margin: 0,
                filename: 'etiquette_reparation_<?php echo $reparation_id; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: [4, 6], orientation: 'portrait' },
                honorMarginPadding: false,
                honorColor: false
            };
            
            // Ouvrir dans une nouvelle fenêtre pour l'impression
            const newWindow = window.open('', '_blank');
            if (!newWindow) {
                alert("Impossible d'ouvrir une nouvelle fenêtre. Veuillez autoriser les pop-ups pour ce site.");
                return;
            }
            
            const repId = "<?php echo $reparation_id; ?>";
            
            // Préparer le contenu HTML
            newWindow.document.write('<!DOCTYPE html>');
            newWindow.document.write('<html>');
            newWindow.document.write('<head>');
            newWindow.document.write('<title>Étiquette PDF #' + repId + '</title>');
            newWindow.document.write('<meta charset="UTF-8">');
            newWindow.document.write('<style>');
            newWindow.document.write('@page { size: 4in 6in; margin: 0; }');
            newWindow.document.write('body { width: 4in; height: 6in; margin: 0; padding: 0; box-sizing: border-box; }');
            newWindow.document.write('#etiquette { width: 4in; height: 6in; padding: 0.3in; box-sizing: border-box; background-color: white; }');
            newWindow.document.write('#backButton { position: fixed; bottom: 20px; right: 20px; z-index: 9999; background-color: #333; color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 24px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); cursor: pointer; display: none; }');
            newWindow.document.write('#actionsMenu { position: fixed; bottom: 20px; left: 20px; z-index: 9999; background-color: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); display: none; padding: 10px; }');
            newWindow.document.write('#actionsMenu button { display: block; width: 100%; padding: 10px; margin: 5px 0; background-color: white; border: 1px solid #000; border-radius: 5px; text-align: left; cursor: pointer; }');
            newWindow.document.write('#actionsMenu button:hover { background-color: #eee; }');
            newWindow.document.write('@media print { #backButton, #actionsMenu { display: none !important; } }');
            newWindow.document.write('</style>');
            newWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
            newWindow.document.write('</head>');
            newWindow.document.write('<body>');
            
            // Ajouter l'étiquette modifiée avec un titre
            if (!etiquetteClone.querySelector('h2')) {
                const titleDiv = document.createElement('div');
                titleDiv.className = 'text-center mb-2';
                titleDiv.innerHTML = '<h2 class="fw-bold" style="text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 6px; font-size: 1.3rem;">MAISON DU GEEK</h2>';
                etiquetteClone.insertBefore(titleDiv, etiquetteClone.firstChild);
            }
            
            // Ajouter l'étiquette modifiée
            newWindow.document.write(etiquetteClone.outerHTML);
            
            // Ajouter le bouton de retour et le menu d'actions
            newWindow.document.write('<div id="afterPrintControls">');
            newWindow.document.write('<button id="backButton" title="Retour"><i class="fas fa-arrow-left"></i></button>');
            newWindow.document.write('<div id="actionsMenu">');
            newWindow.document.write('<button id="btnReturnToApp"><i class="fas fa-home"></i> Retour à l\'application</button>');
            newWindow.document.write('<button id="btnReprint"><i class="fas fa-print"></i> Imprimer</button>');
            newWindow.document.write('</div>');
            newWindow.document.write('</div>');
            
            // Ajouter script pour impression automatique et gestion des boutons
            newWindow.document.write('<script>');
            newWindow.document.write('window.onload = function() {');
            newWindow.document.write('  setTimeout(function() {');
            newWindow.document.write('    window.print();');
            newWindow.document.write('    setTimeout(function() {');
            newWindow.document.write('      document.getElementById("backButton").style.display = "block";');
            newWindow.document.write('    }, 1000);');
            newWindow.document.write('  }, 500);');
            newWindow.document.write('  document.getElementById("backButton").addEventListener("click", function() {');
            newWindow.document.write('    const menu = document.getElementById("actionsMenu");');
            newWindow.document.write('    menu.style.display = menu.style.display === "block" ? "none" : "block";');
            newWindow.document.write('  });');
            newWindow.document.write('  document.getElementById("btnReturnToApp").addEventListener("click", function() {');
            newWindow.document.write('    window.location.href = "index.php?page=reparations";');
            newWindow.document.write('  });');
            newWindow.document.write('  document.getElementById("btnReprint").addEventListener("click", function() {');
            newWindow.document.write('    window.print();');
            newWindow.document.write('  });');
            newWindow.document.write('  window.addEventListener("afterprint", function() {');
            newWindow.document.write('    document.getElementById("backButton").style.display = "block";');
            newWindow.document.write('  });');
            newWindow.document.write('};');
            newWindow.document.write('<\/script>');
            
            newWindow.document.write('</body>');
            newWindow.document.write('</html>');
            newWindow.document.close();
            
        } catch (error) {
            console.error("Erreur lors de l'export PDF:", error);
            alert("Une erreur est survenue lors de l'export PDF. Détails: " + error.message);
        }
    });
});
</script>

<style>
/* Styles pour l'étiquette à imprimer */
#etiquette {
    width: 4in;
    height: 6in;
    border: 1px dashed #ccc;
    margin: 0 auto;
    background-color: white;
    font-family: Arial, sans-serif;
    padding: 0.3in;
    box-sizing: border-box;
}

#qrcode {
    background-color: white;
    padding: 4px;
    border: 1px solid #000;
    display: inline-block;
}

#qrcode img {
    display: inline-block;
    margin: 0 auto;
}

/* Styles pour l'impression */
@media print {
    @page {
        size: 4in 6in !important;
        margin: 0 !important;
    }
    
    html, body {
        height: 6in !important;
        width: 4in !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        filter: grayscale(100%);
    }
    
    body * {
        visibility: hidden;
    }
    
    #etiquette, #etiquette * {
        visibility: visible !important;
    }
    
    #etiquette {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 4in !important;
        height: 6in !important;
        padding: 0.3in !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        border: none !important;
        transform: none !important;
        display: block !important;
        overflow: visible !important;
        page-break-after: avoid !important;
        page-break-inside: avoid !important;
        filter: grayscale(100%);
        background-color: white !important;
    }
    
    .btn, .card-body:not(#etiquette .card-body) {
        display: none !important;
    }
}
</style> 