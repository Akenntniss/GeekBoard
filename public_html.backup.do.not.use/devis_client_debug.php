<?php
/**
 * ================================================================================
 * PAGE CLIENT - CONSULTATION ET ACCEPTATION DE DEVIS
 * ================================================================================
 * Description: Interface moderne pour que le client consulte et accepte/refuse son devis
 * Date: 2025-01-27
 * ================================================================================
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Inclure les dépendances
require_once '../config/subdomain_database_detector.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

// Récupérer le lien sécurisé depuis l'URL
$lien_securise = $_GET['lien'] ?? '';

if (empty($lien_securise)) {
    http_response_code(404);
    include '../templates/error.php';
    exit;
}

// Nettoyer le lien sécurisé
$lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);

try {
    // Récupérer la connexion à la base de données
    $detector = new SubdomainDatabaseDetector();
    $shop_pdo = $detector->getConnection();
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Récupérer le devis complet avec toutes les informations
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            c.email as client_email,
            r.type_appareil,
            r.modele as appareil_modele,
            r.description_probleme,
            e.nom as employe_nom,
            e.prenom as employe_prenom
        FROM devis d
        LEFT JOIN clients c ON d.client_id = c.id
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN employes e ON d.employe_id = e.id
        WHERE d.lien_securise = ? AND d.statut IN ('envoye', 'accepte', 'refuse')
    ");
    $stmt->execute([$lien_securise]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        http_response_code(404);
        $error_message = "Devis non trouvé ou non accessible";
        include '../templates/error.php';
        exit;
    }

    // Vérifier si le devis n'est pas expiré
    $date_expiration = new DateTime($devis['date_expiration']);
    $maintenant = new DateTime();
    $devis_expire = $maintenant > $date_expiration;

    // Si le devis est expiré et n'a pas encore été marqué comme tel
    if ($devis_expire && $devis['statut'] == 'envoye') {
        $stmt = $shop_pdo->prepare("UPDATE devis SET statut = 'expire' WHERE id = ?");
        $stmt->execute([$devis['id']]);
        $devis['statut'] = 'expire';
    }

    // Récupérer les pannes identifiées
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_pannes 
        WHERE devis_id = ? 
        ORDER BY ordre ASC, id ASC
    ");
    $stmt->execute([$devis['id']]);
    $pannes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les solutions proposées avec leurs éléments
    $stmt = $shop_pdo->prepare("
        SELECT ds.*, 
               GROUP_CONCAT(
                   CONCAT(dsi.nom, '|', dsi.quantite, '|', dsi.prix_unitaire, '|', dsi.type)
                   SEPARATOR ';;;'
               ) as elements_concat
        FROM devis_solutions ds
        LEFT JOIN devis_solutions_items dsi ON ds.id = dsi.solution_id
        WHERE ds.devis_id = ?
        GROUP BY ds.id
        ORDER BY ds.ordre ASC, ds.id ASC
    ");
    $stmt->execute([$devis['id']]);
    $solutions_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traiter les éléments de chaque solution
    $solutions = [];
    foreach ($solutions_raw as $solution) {
        $solution['elements'] = [];
        
        if (!empty($solution['elements_concat'])) {
            $elements_data = explode(';;;', $solution['elements_concat']);
            foreach ($elements_data as $element_data) {
                $parts = explode('|', $element_data);
                if (count($parts) >= 4) {
                    $solution['elements'][] = [
                        'nom' => $parts[0],
                        'quantite' => intval($parts[1]),
                        'prix_unitaire' => floatval($parts[2]),
                        'type' => $parts[3]
                    ];
                }
            }
        }
        
        unset($solution['elements_concat']);
        $solutions[] = $solution;
    }

    // Récupérer l'historique des actions si le devis a été traité
    $logs = [];
    if ($devis['statut'] != 'envoye') {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM devis_logs 
            WHERE devis_id = ? 
            ORDER BY date_action DESC
        ");
        $stmt->execute([$devis['id']]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    error_log("ERREUR PAGE DEVIS CLIENT: " . $e->getMessage());
    http_response_code(500);
    $error_message = "Une erreur s'est produite lors du chargement du devis";
    include '../templates/error.php';
    exit;
}

// Déterminer le titre de la page
$page_title = "Devis #" . $devis['numero_devis'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Signature Pad -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    
    <style>
        :root {
            --primary-color: #3b82f6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --gray-100: #f3f4f6;
            --gray-800: #1f2937;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 1000px;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-envoye { background: var(--primary-color); color: white; }
        .status-accepte { background: var(--success-color); color: white; }
        .status-refuse { background: var(--danger-color); color: white; }
        .status-expire { background: var(--gray-800); color: white; }

        .info-card {
            background: var(--gray-100);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid var(--primary-color);
        }

        .panne-card {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 1rem;
            margin: 0.5rem 0;
            border-left: 4px solid var(--danger-color);
        }

        .solution-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .solution-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }

        .solution-card.selected {
            border-color: var(--success-color);
            background: #f0fdf4;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
        }

        .solution-card.recommandee::before {
            content: "Recommandée";
            position: absolute;
            top: -8px;
            right: 20px;
            background: var(--warning-color);
            color: white;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .price-highlight {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .elements-list {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .element-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .element-item:last-child {
            border-bottom: none;
        }

        .signature-section {
            background: #fafbfc;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            border: 2px dashed #d1d5db;
        }

        #signature-canvas {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            cursor: crosshair;
        }

        .action-buttons {
            padding: 2rem;
            background: var(--gray-100);
            text-align: center;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            min-width: 200px;
            margin: 0.5rem;
        }

        .btn-success {
            background: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background: var(--danger-color);
            border-color: var(--danger-color);
        }

        .countdown {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin: 1rem 0;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .header-section {
                padding: 1.5rem 1rem;
            }
            
            .price-highlight {
                font-size: 1.5rem;
            }
            
            .btn-lg {
                min-width: auto;
                width: 100%;
                margin: 0.25rem 0;
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="main-container fade-in">
            
            <!-- En-tête du devis -->
            <div class="header-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2">
                            <i class="fas fa-file-invoice me-2"></i>
                            Devis <?php echo htmlspecialchars($devis['numero_devis']); ?>
                        </h1>
                        <p class="mb-3 opacity-90">
                            <?php echo htmlspecialchars($devis['titre']); ?>
                        </p>
                        <div class="d-flex gap-3 align-items-center">
                            <span class="status-badge status-<?php echo $devis['statut']; ?>">
                                <?php
                                $status_icons = [
                                    'envoye' => 'fa-paper-plane',
                                    'accepte' => 'fa-check-circle',
                                    'refuse' => 'fa-times-circle',
                                    'expire' => 'fa-clock'
                                ];
                                $status_labels = [
                                    'envoye' => 'En attente',
                                    'accepte' => 'Accepté',
                                    'refuse' => 'Refusé',
                                    'expire' => 'Expiré'
                                ];
                                ?>
                                <i class="fas <?php echo $status_icons[$devis['statut']]; ?>"></i>
                                <?php echo $status_labels[$devis['statut']]; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="price-highlight">
                            <?php echo number_format($devis['total_ttc'], 2, ',', ' '); ?> €
                        </div>
                        <small class="opacity-75">TTC</small>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="p-4">
                
                <!-- Informations générales -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5><i class="fas fa-user text-primary me-2"></i>Informations client</h5>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></strong></p>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($devis['client_telephone']); ?></p>
                            <?php if ($devis['client_email']): ?>
                            <p class="mb-0"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($devis['client_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5><i class="fas fa-mobile-alt text-primary me-2"></i>Appareil concerné</h5>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($devis['type_appareil']); ?></strong></p>
                            <p class="mb-1">Modèle: <?php echo htmlspecialchars($devis['appareil_modele']); ?></p>
                            <p class="mb-0">Problème: <?php echo htmlspecialchars($devis['description_probleme']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Délai d'expiration si applicable -->
                <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
                <div class="countdown">
                    <i class="fas fa-hourglass-half me-2"></i>
                    Ce devis expire le <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?>
                    <div id="countdown-timer" class="mt-2"></div>
                </div>
                <?php endif; ?>

                <!-- Description générale -->
                <?php if (!empty($devis['description_generale'])): ?>
                <div class="mt-4">
                    <h4><i class="fas fa-info-circle text-primary me-2"></i>Description</h4>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($devis['description_generale'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Pannes identifiées -->
                <div class="mt-4">
                    <h4><i class="fas fa-exclamation-triangle text-danger me-2"></i>Pannes identifiées</h4>
                    <?php foreach ($pannes as $panne): ?>
                    <div class="panne-card">
                        <h6 class="text-danger mb-2">
                            <?php
                            $gravite_icons = [
                                'faible' => '🟢',
                                'moyenne' => '🟡',
                                'elevee' => '🟠',
                                'critique' => '🔴'
                            ];
                            echo $gravite_icons[$panne['gravite']] ?? '🟡';
                            ?>
                            <?php echo htmlspecialchars($panne['titre']); ?>
                        </h6>
                        <?php if (!empty($panne['description'])): ?>
                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($panne['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Solutions proposées -->
                <div class="mt-4">
                    <h4><i class="fas fa-tools text-success me-2"></i>Solutions proposées</h4>
                    
                    <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
                    <p class="text-muted mb-3">Cliquez sur la solution de votre choix pour la sélectionner :</p>
                    <?php endif; ?>

                    <div id="solutions-container">
                        <?php foreach ($solutions as $index => $solution): ?>
                        <div class="solution-card <?php echo $solution['recommandee'] ? 'recommandee' : ''; ?>" 
                             data-solution-id="<?php echo $solution['id']; ?>"
                             <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>onclick="selectSolution(<?php echo $solution['id']; ?>)"<?php endif; ?>>
                            
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="text-primary mb-2">
                                        Solution <?php echo chr(65 + $index); ?>: <?php echo htmlspecialchars($solution['nom']); ?>
                                        <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
                                        <input type="radio" name="solution_choisie" value="<?php echo $solution['id']; ?>" 
                                               class="form-check-input ms-2" style="margin-top: 0;">
                                        <?php endif; ?>
                                    </h5>
                                    
                                    <?php if (!empty($solution['description'])): ?>
                                    <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($solution['description'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="row text-sm">
                                        <?php if (!empty($solution['duree_reparation'])): ?>
                                        <div class="col-sm-6">
                                            <i class="fas fa-clock text-muted me-1"></i>
                                            <strong>Durée:</strong> <?php echo htmlspecialchars($solution['duree_reparation']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($solution['garantie'])): ?>
                                        <div class="col-sm-6">
                                            <i class="fas fa-shield-alt text-muted me-1"></i>
                                            <strong>Garantie:</strong> <?php echo htmlspecialchars($solution['garantie']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="price-highlight" style="font-size: 1.8rem;">
                                        <?php echo number_format($solution['prix_total'], 2, ',', ' '); ?> €
                                    </div>
                                    <small class="text-muted">TTC</small>
                                </div>
                            </div>

                            <!-- Détail des éléments si disponible -->
                            <?php if (!empty($solution['elements'])): ?>
                            <div class="elements-list mt-3">
                                <h6 class="text-muted mb-2"><i class="fas fa-list me-1"></i>Détail :</h6>
                                <?php foreach ($solution['elements'] as $element): ?>
                                <div class="element-item">
                                    <span>
                                        <?php echo htmlspecialchars($element['nom']); ?>
                                        <?php if ($element['quantite'] > 1): ?>
                                        <small class="text-muted">(x<?php echo $element['quantite']; ?>)</small>
                                        <?php endif; ?>
                                    </span>
                                    <strong><?php echo number_format($element['prix_unitaire'] * $element['quantite'], 2, ',', ' '); ?> €</strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section signature et acceptation (uniquement si le devis est en attente) -->
                <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
                <div class="signature-section mt-5" id="signature-section" style="display: none;">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-signature text-primary me-2"></i>
                        Signature électronique
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <p class="text-center text-muted mb-4">
                                Signez dans le cadre ci-dessous pour confirmer votre acceptation du devis :
                            </p>
                            
                            <div class="text-center mb-3">
                                <canvas id="signature-canvas" width="600" height="200"></canvas>
                            </div>
                            
                            <div class="text-center">
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="clearSignature()">
                                    <i class="fas fa-eraser me-1"></i>Effacer
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="testSignature()">
                                    <i class="fas fa-pen me-1"></i>Signature de test
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- Message si déjà traité -->
                <?php if ($devis['statut'] != 'envoye'): ?>
                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-info-circle me-2"></i>Statut du devis</h5>
                    <?php if ($devis['statut'] == 'accepte'): ?>
                    <p class="mb-0">Votre devis a été accepté le <?php echo date('d/m/Y à H:i', strtotime($devis['date_reponse'])); ?>. 
                       Nous allons procéder à la réparation de votre appareil.</p>
                    <?php elseif ($devis['statut'] == 'refuse'): ?>
                    <p class="mb-0">Vous avez refusé ce devis le <?php echo date('d/m/Y à H:i', strtotime($devis['date_reponse'])); ?>. 
                       Votre appareil vous attend en magasin.</p>
                    <?php elseif ($devis['statut'] == 'expire'): ?>
                    <p class="mb-0">Ce devis a expiré le <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?>. 
                       Contactez-nous pour établir un nouveau devis.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Message si expiré -->
                <?php if ($devis_expire && $devis['statut'] == 'envoye'): ?>
                <div class="alert alert-warning mt-4">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Devis expiré</h5>
                    <p class="mb-0">Ce devis a expiré le <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?>. 
                       Contactez-nous pour établir un nouveau devis.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Boutons d'action -->
            <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
            <div class="action-buttons">
                <button type="button" class="btn btn-success btn-lg" id="btn-accepter" onclick="accepterDevis()" disabled>
                    <i class="fas fa-check me-2"></i>Accepter le devis
                </button>
                <button type="button" class="btn btn-danger btn-lg" onclick="refuserDevis()">
                    <i class="fas fa-times me-2"></i>Refuser le devis
                </button>
                
                <!-- Boutons supplémentaires disponibles pour tous les statuts -->
                <div class="mt-3">
                    <a href="tel:0493467163" class="btn btn-primary btn-lg">
                        <i class="fas fa-phone me-2"></i>Appelez-nous
                        <small class="d-block" style="font-size: 0.8rem;">04 93 46 71 63</small>
                    </a>
                    <a href="devis_print.php?lien=<?php echo htmlspecialchars($lien_securise); ?>&print=1" 
                       class="btn btn-info btn-lg" target="_blank">
                        <i class="fas fa-print me-2"></i>Imprimer / PDF
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Boutons disponibles pour les devis traités ou expirés -->
            <div class="action-buttons">
                <a href="tel:0493467163" class="btn btn-primary btn-lg">
                    <i class="fas fa-phone me-2"></i>Appelez-nous
                    <small class="d-block" style="font-size: 0.8rem;">04 93 46 71 63</small>
                </a>
                <a href="devis_print.php?lien=<?php echo htmlspecialchars($lien_securise); ?>&print=1" 
                   class="btn btn-info btn-lg" target="_blank">
                    <i class="fas fa-print me-2"></i>Imprimer / PDF
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 pour les notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Variables globales
        let signaturePad;
        let solutionChoisie = null;
        const devisId = <?php echo $devis['id']; ?>;
        const devisExpiration = new Date('<?php echo $devis['date_expiration']; ?>');

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Initialisation de la page devis client');
            
            // Initialiser la signature
            initSignature();
            
            // Initialiser le compte à rebours si nécessaire
            <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
            initCountdown();
            <?php endif; ?>
        });

        // Initialiser le pad de signature
        function initSignature() {
            const canvas = document.getElementById('signature-canvas');
            if (!canvas) return;
            
            // Ajuster la taille du canvas pour la responsivité
            function resizeCanvas() {
                const container = canvas.parentElement;
                const containerWidth = container.offsetWidth;
                const newWidth = Math.min(600, containerWidth - 40);
                
                canvas.width = newWidth;
                canvas.height = 200;
                
                if (signaturePad) {
                    signaturePad.clear();
                }
            }
            
            resizeCanvas();
            
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: '#ffffff',
                penColor: '#000000',
                minWidth: 2,
                maxWidth: 4
            });
            
            // Redimensionner au redimensionnement de la fenêtre
            window.addEventListener('resize', resizeCanvas);
        }

        // Sélectionner une solution
        function selectSolution(solutionId) {
            console.log('✅ Solution sélectionnée:', solutionId);
            
            // Désélectionner toutes les cartes
            document.querySelectorAll('.solution-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Sélectionner la carte cliquée
            const selectedCard = document.querySelector(`[data-solution-id="${solutionId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Cocher le radio button
            const radio = document.querySelector(`input[name="solution_choisie"][value="${solutionId}"]`);
            if (radio) {
                radio.checked = true;
            }
            
            solutionChoisie = solutionId;
            
            // Afficher la section signature
            const signatureSection = document.getElementById('signature-section');
            if (signatureSection) {
                signatureSection.style.display = 'block';
                signatureSection.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Vérifier si on peut activer le bouton accepter
            checkAcceptButton();
        }

        // Vérifier si on peut activer le bouton d'acceptation
        function checkAcceptButton() {
            const btnAccepter = document.getElementById('btn-accepter');
            const hasSignature = signaturePad && !signaturePad.isEmpty();
            
            if (solutionChoisie && hasSignature) {
                btnAccepter.disabled = false;
                btnAccepter.classList.add('pulse');
            } else {
                btnAccepter.disabled = true;
                btnAccepter.classList.remove('pulse');
            }
        }

        // Écouter les changements dans les champs
        document.addEventListener('input', checkAcceptButton);
        document.addEventListener('change', checkAcceptButton);

        // Effacer la signature
        function clearSignature() {
            if (signaturePad) {
                signaturePad.clear();
                checkAcceptButton();
            }
        }

        // Signature de test
        function testSignature() {
            if (signaturePad) {
                signaturePad.clear();
                
                // Dessiner une signature simple
                const ctx = signaturePad._ctx;
                ctx.beginPath();
                ctx.moveTo(50, 100);
                ctx.lineTo(150, 80);
                ctx.lineTo(250, 120);
                ctx.lineTo(350, 70);
                ctx.lineTo(450, 110);
                ctx.stroke();
                
                // Ajouter quelques détails
                ctx.beginPath();
                ctx.moveTo(100, 130);
                ctx.lineTo(200, 140);
                ctx.lineTo(300, 120);
                ctx.stroke();
                
                checkAcceptButton();
            }
        }

        // Accepter le devis
        async function accepterDevis() {
            if (!solutionChoisie) {
                Swal.fire('Erreur', 'Veuillez sélectionner une solution', 'error');
                return;
            }
            
            if (!signaturePad || signaturePad.isEmpty()) {
                Swal.fire('Erreur', 'Veuillez signer le devis', 'error');
                return;
            }
            
            // Confirmation
            const result = await Swal.fire({
                title: 'Confirmer l\'acceptation',
                text: 'Êtes-vous sûr de vouloir accepter ce devis ? Cette action est définitive.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Oui, accepter',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#10b981'
            });
            
            if (!result.isConfirmed) return;
            
            // Afficher le loader
            Swal.fire({
                title: 'Traitement en cours...',
                text: 'Enregistrement de votre acceptation',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                // Préparer les données
                const donnees = {
                    action: 'accepter',
                    devis_id: devisId,
                    solution_choisie_id: solutionChoisie,
                    signature: signaturePad.toDataURL()
                };
                
                const response = await fetch('../ajax/traiter_devis_client.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(donnees)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        title: 'Devis accepté !',
                        text: 'Merci ! Nous allons procéder à la réparation de votre appareil.',
                        icon: 'success',
                        confirmButtonText: 'Parfait'
                    });
                    
                    // Recharger la page
                    location.reload();
                } else {
                    throw new Error(result.message || 'Erreur lors de l\'acceptation');
                }
                
            } catch (error) {
                console.error('Erreur:', error);
                Swal.fire('Erreur', error.message, 'error');
            }
        }

        // Refuser le devis
        async function refuserDevis() {
            const result = await Swal.fire({
                title: 'Refuser le devis',
                text: 'Voulez-vous préciser la raison de votre refus ?',
                input: 'textarea',
                inputPlaceholder: 'Raison du refus (optionnel)...',
                showCancelButton: true,
                confirmButtonText: 'Refuser le devis',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#ef4444'
            });
            
            if (!result.isConfirmed) return;
            
            // Afficher le loader
            Swal.fire({
                title: 'Traitement en cours...',
                text: 'Enregistrement de votre refus',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                const donnees = {
                    action: 'refuser',
                    devis_id: devisId,
                    raison_refus: result.value || ''
                };
                
                const response = await fetch('../ajax/traiter_devis_client.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(donnees)
                });
                
                const resultData = await response.json();
                
                if (resultData.success) {
                    await Swal.fire({
                        title: 'Devis refusé',
                        text: 'Votre refus a été enregistré. Votre appareil vous attend en magasin.',
                        icon: 'info',
                        confirmButtonText: 'D\'accord'
                    });
                    
                    // Recharger la page
                    location.reload();
                } else {
                    throw new Error(resultData.message || 'Erreur lors du refus');
                }
                
            } catch (error) {
                console.error('Erreur:', error);
                Swal.fire('Erreur', error.message, 'error');
            }
        }

        // Initialiser le compte à rebours
        function initCountdown() {
            const countdownElement = document.getElementById('countdown-timer');
            if (!countdownElement) return;
            
            function updateCountdown() {
                const maintenant = new Date();
                const diff = devisExpiration - maintenant;
                
                if (diff <= 0) {
                    countdownElement.innerHTML = '<strong>EXPIRÉ</strong>';
                    location.reload(); // Recharger pour mettre à jour le statut
                    return;
                }
                
                const jours = Math.floor(diff / (1000 * 60 * 60 * 24));
                const heures = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secondes = Math.floor((diff % (1000 * 60)) / 1000);
                
                let texte = '';
                if (jours > 0) texte += `${jours}j `;
                if (heures > 0) texte += `${heures}h `;
                if (minutes > 0) texte += `${minutes}m `;
                texte += `${secondes}s`;
                
                countdownElement.innerHTML = `Temps restant: <strong>${texte}</strong>`;
            }
            
            // Mettre à jour immédiatement puis toutes les secondes
            updateCountdown();
            setInterval(updateCountdown, 1000);
        }

        // Style pour le bouton qui pulse
        const style = document.createElement('style');
        style.textContent = `
            .pulse {
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
                100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html> 