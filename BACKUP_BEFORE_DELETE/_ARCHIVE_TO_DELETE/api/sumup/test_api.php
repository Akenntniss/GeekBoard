<?php
/**
 * Script de test pour l'API SumUp
 * Vérifier que la clé API fonctionne
 * Accès: https://82.29.168.205/MDGEEK/api/sumup/test_api.php
 */

require_once '../../classes/SumUpIntegration.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API SumUp - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-card {
            border-left: 4px solid #28a745;
        }
        .error-card {
            border-left: 4px solid #dc3545;
        }
        .info-card {
            border-left: 4px solid #17a2b8;
        }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1><i class="fas fa-credit-card text-success"></i> Test API SumUp</h1>
                    <p class="lead">Vérification de l'intégration SumUp pour GeekBoard</p>
                </div>

                <?php
                try {
                    echo '<div class="card test-card mb-4">';
                    echo '<div class="card-header bg-success text-white">';
                    echo '<h5 class="mb-0"><i class="fas fa-check-circle"></i> Test de connexion API</h5>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    
                    // Test de base
                    $sumup = new SumUpIntegration();
                    $testResult = $sumup->testConnection();
                    
                    if ($testResult['success']) {
                        echo '<div class="alert alert-success">';
                        echo '<h6><i class="fas fa-thumbs-up"></i> ' . $testResult['message'] . '</h6>';
                        echo '</div>';
                        
                        if (isset($testResult['data'])) {
                            echo '<h6>Informations du compte SumUp:</h6>';
                            echo '<pre>' . json_encode($testResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">';
                        echo '<h6><i class="fas fa-exclamation-triangle"></i> ' . $testResult['message'] . '</h6>';
                        echo '</div>';
                    }
                    
                    echo '</div></div>';
                    
                    // Test de création de checkout (si connexion OK)
                    if ($testResult['success']) {
                        echo '<div class="card info-card mb-4">';
                        echo '<div class="card-header bg-info text-white">';
                        echo '<h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Test de création de checkout</h5>';
                        echo '</div>';
                        echo '<div class="card-body">';
                        
                        try {
                            // Créer un checkout de test
                            $checkout = $sumup->createCheckout(
                                1.00, // 1 euro
                                'Test GeekBoard - ' . date('Y-m-d H:i:s'),
                                9999, // ID de test
                                [
                                    'id' => 1,
                                    'nom' => 'Test',
                                    'prenom' => 'Client',
                                    'email' => 'test@geekboard.com'
                                ]
                            );
                            
                            echo '<div class="alert alert-success">';
                            echo '<h6><i class="fas fa-check"></i> Checkout créé avec succès!</h6>';
                            echo '</div>';
                            
                            echo '<h6>Détails du checkout:</h6>';
                            echo '<pre>' . json_encode($checkout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-warning">';
                            echo '<h6><i class="fas fa-exclamation-triangle"></i> Erreur création checkout</h6>';
                            echo '<p>' . $e->getMessage() . '</p>';
                            echo '</div>';
                        }
                        
                        echo '</div></div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="card error-card mb-4">';
                    echo '<div class="card-header bg-danger text-white">';
                    echo '<h5 class="mb-0"><i class="fas fa-times-circle"></i> Erreur générale</h5>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    echo '<div class="alert alert-danger">';
                    echo '<h6>Exception: ' . $e->getMessage() . '</h6>';
                    echo '</div>';
                    echo '</div></div>';
                }
                ?>

                <!-- Informations de configuration -->
                <div class="card info-card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Configuration</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $config = include('../../config/sumup_config.php');
                        echo '<div class="row">';
                        echo '<div class="col-md-6">';
                        echo '<strong>Environnement:</strong> ' . $config['environment'] . '<br>';
                        echo '<strong>Devise:</strong> ' . $config['currency'] . '<br>';
                        echo '<strong>Base URL:</strong> ' . ($config['environment'] === 'production' ? $config['base_url_production'] : $config['base_url_sandbox']);
                        echo '</div>';
                        echo '<div class="col-md-6">';
                        echo '<strong>Webhook URL:</strong><br>';
                        echo '<code>' . $config['webhook_url'] . '</code><br><br>';
                        echo '<strong>Return URL:</strong><br>';
                        echo '<code>' . $config['return_url'] . '</code>';
                        echo '</div>';
                        echo '</div>';
                        ?>
                    </div>
                </div>

                <!-- Actions de test -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Actions de test</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Webhook Test</h6>
                                <p>Tester le webhook manuellement:</p>
                                <a href="webhook.php" class="btn btn-outline-primary btn-sm" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> Tester webhook
                                </a>
                            </div>
                            <div class="col-md-6">
                                <h6>Logs</h6>
                                <p>Vérifier les logs d'activité:</p>
                                <div class="btn-group">
                                    <a href="../../logs/sumup.log" class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="fas fa-file-alt"></i> Log SumUp
                                    </a>
                                    <a href="../../logs/webhook_sumup.log" class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="fas fa-file-alt"></i> Log Webhook
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="../../pages/statut_rapide.php?id=1" class="btn btn-success">
                        <i class="fas fa-arrow-left"></i> Retour à GeekBoard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 