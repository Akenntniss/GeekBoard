<?php
/**
 * ================================================================================
 * PAGE D'IMPRESSION DEVIS - VERSION HTML IMPRIMABLE
 * ================================================================================
 * Description: Version imprimable du devis pour remplacement du PDF
 * Date: 2025-01-27
 * ================================================================================
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Inclure les dépendances
require_once '../config/subdomain_database_detector.php';

// Récupérer les paramètres depuis l'URL
$lien_securise = $_GET['lien'] ?? '';
$devis_id = $_GET['devis_id'] ?? '';
$shop_id = $_GET['shop_id'] ?? '';

// Vérifier qu'on a au moins un paramètre valide
if (empty($lien_securise) && empty($devis_id)) {
    http_response_code(404);
    echo '<h1>Erreur 404</h1><p>Paramètres manquants</p>';
    exit;
}

// Nettoyer les paramètres
if ($lien_securise) {
    $lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);
}
if ($devis_id) {
    $devis_id = intval($devis_id);
}
if ($shop_id) {
    $shop_id = intval($shop_id);
}

try {
    // Récupérer la connexion à la base de données
    if ($shop_id) {
        require_once '../config/database.php';
        $shop_pdo = getShopDBConnectionById($shop_id);
    } else {
        $detector = new SubdomainDatabaseDetector();
        $shop_pdo = $detector->getConnection();
    }
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Récupérer le devis complet avec toutes les informations
    if ($lien_securise) {
        // Recherche par lien sécurisé (client)
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
            WHERE d.lien_securise = ?
        ");
        $stmt->execute([$lien_securise]);
    } else {
        // Recherche par ID (admin)
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
            WHERE d.id = ?
        ");
        $stmt->execute([$devis_id]);
    }
    
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        http_response_code(404);
        echo '<h1>Erreur 404</h1><p>Devis non trouvé</p>';
        exit;
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

    // Traiter les solutions pour séparer les éléments
    $solutions = [];
    foreach ($solutions_raw as $solution) {
        $solution['elements'] = [];
        if (!empty($solution['elements_concat'])) {
            $elements_data = explode(';;;', $solution['elements_concat']);
            foreach ($elements_data as $element_data) {
                if (!empty($element_data)) {
                    $parts = explode('|', $element_data);
                    if (count($parts) >= 4) {
                        $solution['elements'][] = [
                            'nom' => $parts[0],
                            'quantite' => intval($parts[1]),
                            'prix_unitaire' => floatval($parts[2]),
                            'type' => $parts[3],
                            'prix' => intval($parts[1]) * floatval($parts[2])
                        ];
                    }
                }
            }
        }
        unset($solution['elements_concat']);
        $solutions[] = $solution;
    }

    // Fonction pour obtenir le statut en français
    function getStatutFrancais($statut) {
        switch($statut) {
            case 'envoye': return 'En attente';
            case 'accepte': return 'Accepté';
            case 'refuse': return 'Refusé';
            case 'expire': return 'Expiré';
            default: return ucfirst($statut);
        }
    }

    // Fonction pour obtenir l'icône du statut
    function getStatutIcon($statut) {
        switch($statut) {
            case 'envoye': return '⏳';
            case 'accepte': return '✅';
            case 'refuse': return '❌';
            case 'expire': return '⏰';
            default: return '📄';
        }
    }

    // Vérifier si le devis n'est pas expiré
    $date_expiration = new DateTime($devis['date_expiration']);
    $maintenant = new DateTime();
    $devis_expire = $maintenant > $date_expiration;

} catch (Exception $e) {
    error_log("Erreur devis print: " . $e->getMessage());
    echo '<h1>Erreur</h1><p>Une erreur est survenue lors du chargement du devis.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis <?php echo htmlspecialchars($devis['numero_devis']); ?> - GeekBoard</title>
    <style>
        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: white;
        }

        /* Variables CSS */
        :root {
            --primary-color: #3B82F6;
            --success-color: #10B981;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-600: #4B5563;
            --gray-800: #1F2937;
        }

        /* Styles d'impression */
        @media print {
            body {
                margin: 0;
                font-size: 12pt;
                line-height: 1.4;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            h1, h2, h3 {
                break-after: avoid;
            }
            
            .solution-card {
                break-inside: avoid;
                margin-bottom: 15pt;
            }
            
            .container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }

        /* Styles écran */
        @media screen {
            body {
                background: #f8fafc;
                padding: 20px 0;
            }
            
            .print-actions {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 15px;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                z-index: 1000;
            }
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* En-tête */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 20px;
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header .subtitle {
            color: var(--gray-600);
            font-size: 16px;
        }

        /* Section devis */
        .devis-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: var(--gray-100);
            border-radius: 8px;
        }

        .devis-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .devis-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .status-envoye { background: var(--primary-color); color: white; }
        .status-accepte { background: var(--success-color); color: white; }
        .status-refuse { background: var(--danger-color); color: white; }
        .status-expire { background: var(--gray-800); color: white; }

        /* Grille d'informations */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-section {
            background: var(--gray-100);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .info-section h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-section p {
            margin: 5px 0;
            font-size: 14px;
        }

        /* Sections */
        .section {
            margin: 25px 0;
        }

        .section-title {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-200);
        }

        /* Pannes */
        .panne-item {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid var(--danger-color);
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
        }

        .panne-title {
            font-weight: bold;
            color: var(--danger-color);
            margin-bottom: 5px;
        }

        .panne-gravite {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            background: var(--warning-color);
            color: white;
        }

        /* Solutions */
        .solution-card {
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            position: relative;
        }

        .solution-card.selected {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .solution-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .solution-name {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .solution-card.selected .solution-name::after {
            content: ' ✓ CHOISIE';
            color: var(--success-color);
            font-size: 14px;
        }

        .solution-price {
            font-size: 24px;
            font-weight: bold;
            color: var(--success-color);
        }

        .elements-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .elements-table th,
        .elements-table td {
            border: 1px solid var(--gray-200);
            padding: 8px 12px;
            text-align: left;
        }

        .elements-table th {
            background: var(--gray-100);
            font-weight: bold;
        }

        .elements-table .price-cell {
            text-align: right;
            font-weight: bold;
        }

        /* Récapitulatif */
        .recap-section {
            background: var(--gray-100);
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .total-line.final {
            border-bottom: none;
            font-size: 18px;
            font-weight: bold;
            color: var(--success-color);
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid var(--gray-200);
            text-align: center;
            color: var(--gray-600);
            font-size: 12px;
        }

        /* Boutons d'action */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            margin: 5px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background: var(--gray-600);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Messages d'alerte */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .alert-warning {
            background: #fef3cd;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .alert-info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <!-- Boutons d'action (écran seulement) -->
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Imprimer
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            ❌ Fermer
        </button>
    </div>

    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <h1>GEEKBOARD</h1>
            <div class="subtitle">Service de réparation professionnelle</div>
        </div>

        <!-- Informations du devis -->
        <div class="devis-header">
            <div class="devis-number">
                Devis <?php echo htmlspecialchars($devis['numero_devis']); ?>
            </div>
            <div class="devis-status status-<?php echo $devis['statut']; ?>">
                <?php echo getStatutIcon($devis['statut']) . ' ' . getStatutFrancais($devis['statut']); ?>
            </div>
        </div>

        <!-- Grille d'informations -->
        <div class="info-grid">
            <div class="info-section">
                <h3>📋 Informations client</h3>
                <p><strong><?php echo htmlspecialchars($devis['client_prenom'] . ' ' . $devis['client_nom']); ?></strong></p>
                <?php if ($devis['client_telephone']): ?>
                <p>📞 <?php echo htmlspecialchars($devis['client_telephone']); ?></p>
                <?php endif; ?>
                <?php if ($devis['client_email']): ?>
                <p>📧 <?php echo htmlspecialchars($devis['client_email']); ?></p>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h3>📱 Appareil concerné</h3>
                <p><strong><?php echo htmlspecialchars($devis['type_appareil']); ?></strong></p>
                <?php if ($devis['appareil_modele']): ?>
                <p>Modèle: <?php echo htmlspecialchars($devis['appareil_modele']); ?></p>
                <?php endif; ?>
                <?php if ($devis['description_probleme']): ?>
                <p>Problème: <?php echo htmlspecialchars($devis['description_probleme']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations devis -->
        <div class="info-grid">
            <div class="info-section">
                <h3>📅 Dates importantes</h3>
                <p>Création: <?php echo date('d/m/Y', strtotime($devis['date_creation'])); ?></p>
                <p>Expiration: <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?></p>
                <?php if ($devis['date_reponse']): ?>
                <p>Réponse: <?php echo date('d/m/Y à H:i', strtotime($devis['date_reponse'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h3>💰 Montant total</h3>
                <p class="solution-price"><?php echo number_format($devis['total_ttc'], 2, ',', ' '); ?> € TTC</p>
                <?php if ($devis_expire && $devis['statut'] == 'envoye'): ?>
                <div class="alert alert-warning">
                    ⚠️ Ce devis a expiré
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pannes identifiées -->
        <?php if (!empty($pannes)): ?>
        <div class="section">
            <h2 class="section-title">🔧 Pannes identifiées</h2>
            <?php foreach ($pannes as $panne): ?>
            <div class="panne-item">
                <div class="panne-title">
                    <?php echo htmlspecialchars($panne['titre'] ?? ''); ?>
                    <?php if ($panne['gravite']): ?>
                    <span class="panne-gravite"><?php echo htmlspecialchars($panne['gravite']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($panne['description']): ?>
                <p><?php echo htmlspecialchars($panne['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Solutions proposées -->
        <?php if (!empty($solutions)): ?>
        <div class="section">
            <h2 class="section-title">💡 Solutions proposées</h2>
            
            <?php 
            $solution_choisie_id = $devis['solution_choisie_id'];
            foreach ($solutions as $index => $solution): 
                $lettre = chr(65 + $index); // A, B, C...
                $est_choisie = ($solution_choisie_id && $solution['id'] == $solution_choisie_id);
            ?>
            <div class="solution-card <?php echo $est_choisie ? 'selected' : ''; ?>">
                <div class="solution-header">
                    <div class="solution-name">
                        Solution <?php echo $lettre; ?>: <?php echo htmlspecialchars($solution['nom']); ?>
                    </div>
                    <div class="solution-price">
                        <?php echo number_format($solution['prix_total'], 2, ',', ' '); ?> € TTC
                    </div>
                </div>

                <?php if ($solution['description']): ?>
                <p><?php echo htmlspecialchars($solution['description']); ?></p>
                <?php endif; ?>

                <?php if (!empty($solution['elements'])): ?>
                <table class="elements-table">
                    <thead>
                        <tr>
                            <th>Prestation</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solution['elements'] as $element): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($element['nom']); ?></td>
                            <td><?php echo $element['quantite']; ?></td>
                            <td class="price-cell"><?php echo number_format($element['prix_unitaire'], 2, ',', ' '); ?> €</td>
                            <td class="price-cell"><?php echo number_format($element['prix'], 2, ',', ' '); ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Récapitulatif -->
        <div class="recap-section">
            <h2 class="section-title">📊 Récapitulatif</h2>
            
            <?php if ($solution_choisie_id): ?>
                <?php 
                $solution_choisie = null;
                foreach ($solutions as $index => $solution) {
                    if ($solution['id'] == $solution_choisie_id) {
                        $solution_choisie = $solution;
                        $lettre = chr(65 + $index);
                        break;
                    }
                }
                ?>
                <?php if ($solution_choisie): ?>
                <div class="alert alert-info">
                    ✅ Solution retenue: Solution <?php echo $lettre; ?> - <?php echo htmlspecialchars($solution_choisie['nom']); ?>
                </div>
                
                <div class="total-line">
                    <span>Total HT:</span>
                    <span><?php echo number_format($solution_choisie['prix_total'] / 1.20, 2, ',', ' '); ?> €</span>
                </div>
                <div class="total-line">
                    <span>TVA (20%):</span>
                    <span><?php echo number_format($solution_choisie['prix_total'] - ($solution_choisie['prix_total'] / 1.20), 2, ',', ' '); ?> €</span>
                </div>
                <div class="total-line final">
                    <span>TOTAL TTC:</span>
                    <span><?php echo number_format($solution_choisie['prix_total'], 2, ',', ' '); ?> €</span>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ PLUSIEURS CHOIX DISPONIBLES<br>
                    Le client doit choisir une solution parmi les options proposées.
                </div>
                
                <h4>Options tarifaires:</h4>
                <?php foreach ($solutions as $index => $solution): ?>
                    <?php $lettre = chr(65 + $index); ?>
                    <div class="total-line">
                        <span>Solution <?php echo $lettre; ?>: <?php echo htmlspecialchars($solution['nom']); ?></span>
                        <span><?php echo number_format($solution['prix_total'], 2, ',', ' '); ?> € TTC</span>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 15px; font-style: italic; color: #666;">
                    Le montant final dépendra de la solution choisie par le client.
                </div>
            <?php endif; ?>
        </div>

        <!-- Notes additionnelles -->
        <?php if (!empty($devis['description_generale']) || !empty($devis['notes_acceptation'])): ?>
        <div class="section">
            <h2 class="section-title">📝 Informations complémentaires</h2>
            
            <?php if (!empty($devis['description_generale'])): ?>
            <div class="info-section">
                <h3>Description générale:</h3>
                <p><?php echo nl2br(htmlspecialchars($devis['description_generale'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($devis['notes_acceptation'])): ?>
            <div class="info-section">
                <h3>Notes d'acceptation:</h3>
                <p><?php echo nl2br(htmlspecialchars($devis['notes_acceptation'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><strong>GeekBoard - Service de réparation professionnelle</strong></p>
            <p>Ce devis est valable jusqu'au <?php echo date('d/m/Y', strtotime($devis['date_expiration'])); ?></p>
            <p>Les prix sont exprimés en euros TTC - TVA applicable 20%</p>
        </div>
    </div>

    <script>
        // Auto-impression si paramètre print=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html> 