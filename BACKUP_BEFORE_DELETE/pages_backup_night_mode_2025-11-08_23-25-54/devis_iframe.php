<?php
/**
 * Page devis spéciale pour iframe - ne nécessite pas d'authentification utilisateur
 * Utilisée par le modal "Devis en attente" dans la page réparations
 */

// Initialiser la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour détecter et initialiser la session du magasin
function initializeShopSessionForDevisIframe() {
    // Détecter le sous-domaine
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = '';
    
    if (strpos($host, 'localhost') !== false) {
        $subdomain = 'mkmkmk'; // Par défaut en local
    } else {
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
        }
    }
    
    // Inclure les fichiers nécessaires dans le bon ordre
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    try {
        // Se connecter à la base principale pour récupérer les infos du magasin
        $main_pdo = getMainDBConnection();
        if ($main_pdo) {
            $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
            $stmt->execute([$subdomain]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shop) {
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
                $_SESSION['current_database'] = $shop['db_name'];
                error_log("Session magasin initialisée pour iframe devis: " . $shop['name'] . " (ID: " . $shop['id'] . ")");
                return $shop['id'];
            } else {
                throw new Exception("Aucun magasin trouvé pour le sous-domaine: " . $subdomain);
            }
        } else {
            throw new Exception("Impossible de se connecter à la base principale");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'initialisation de la session magasin pour iframe devis: " . $e->getMessage());
        throw $e;
    }
}

// Initialiser la session magasin
try {
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        $shop_id = initializeShopSessionForDevisIframe();
    } else {
        $shop_id = $_SESSION['shop_id'];
    }
    
    // Obtenir la connexion à la base de données du magasin - Méthode directe
    error_log("DEVIS IFRAME: Connexion directe au magasin ID: $shop_id");
    
    // Se connecter directement comme dans la page de debug qui fonctionnait
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ? AND active = 1 LIMIT 1");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        throw new Exception("Magasin non trouvé (ID: $shop_id)");
    }
    
    // Connexion directe
    $shop_dsn = "mysql:host={$shop['db_host']};port=" . ($shop['db_port'] ?? '3306') . ";dbname={$shop['db_name']};charset=utf8mb4";
    $shop_pdo = new PDO($shop_dsn, $shop['db_user'], $shop['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    error_log("DEVIS IFRAME: Connexion directe réussie à {$shop['db_name']}");
    
} catch (Exception $e) {
    // Afficher une page d'erreur
    echo '<!DOCTYPE html>
    <html><head><title>Erreur - Devis en attente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light">
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> Erreur</h4>
            <p>Impossible de charger les devis en attente.</p>
            <p>Détails: ' . htmlspecialchars($e->getMessage()) . '</p>
            <button class="btn btn-primary" onclick="window.location.reload()">Réessayer</button>
        </div>
    </div>
    </body></html>';
    exit();
}

// Paramètres de filtrage (devis en attente par défaut)
$statut_ids = 'envoye';
$client_search = '';
$date_debut = '';
$date_fin = '';

try {
    // Récupérer les devis en attente (envoyés et non expirés)
    $query = "
        SELECT d.id, d.date_creation, d.date_expiration, d.total_ttc, d.numero_devis, d.titre,
               c.nom, c.prenom, c.telephone 
        FROM devis d
        LEFT JOIN clients c ON d.client_id = c.id 
        WHERE d.statut = 'envoye' AND d.date_expiration > NOW()
        ORDER BY d.date_creation DESC
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute();
    $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter les devis
    $total_en_attente = count($devis);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des devis: " . $e->getMessage());
    $devis = [];
    $total_en_attente = 0;
}

// Fonctions utilitaires
function getStatutLabel($statut) {
    switch ($statut) {
        case 'envoye':
            return 'En Attente';
        case 'accepte':
            return 'Accepté';
        case 'refuse':
            return 'Refusé';
        case 'brouillon':
            return 'Brouillon';
        case 'expire':
            return 'Expiré';
        default:
            return ucfirst($statut);
    }
}

function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis en attente - <?php echo htmlspecialchars($_SESSION['shop_name'] ?? 'Magasin'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .devis-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .devis-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        .client-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .price-highlight {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="d-flex align-items-center">
            <i class="fas fa-file-invoice-dollar fa-2x me-3"></i>
            <div>
                <h4 class="mb-0">Devis en attente</h4>
                <small><?php echo htmlspecialchars($_SESSION['shop_name'] ?? 'Magasin'); ?></small>
            </div>
            <div class="ms-auto">
                <span class="badge bg-light text-dark fs-6">
                    <?php echo $total_en_attente; ?> devis
                </span>
            </div>
        </div>
    </div>

    <div class="container-fluid px-3">
        <?php if (empty($devis)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun devis en attente</h5>
                <p class="text-muted">Tous les devis ont été traités ou ont expiré.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($devis as $devis_item): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card devis-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Devis #<?php echo htmlspecialchars($devis_item['id']); ?>
                                    </h6>
                                    <span class="badge bg-warning status-badge">
                                        <?php echo getStatutLabel($devis_item['statut']); ?>
                                    </span>
                                </div>
                                
                                <div class="client-info mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    <strong><?php echo htmlspecialchars($devis_item['nom'] . ' ' . $devis_item['prenom']); ?></strong>
                                    <?php if (!empty($devis_item['telephone'])): ?>
                                        <br><i class="fas fa-phone me-2"></i>
                                        <?php echo htmlspecialchars($devis_item['telephone']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Créé le <?php echo date('d/m/Y', strtotime($devis_item['date_creation'])); ?>
                                    </small>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Expire le <?php echo date('d/m/Y', strtotime($devis_item['date_expiration'])); ?>
                                    </small>
                                </div>
                                
                                <?php if (!empty($devis_item['total_ttc'])): ?>
                                    <div class="text-center mt-3">
                                        <div class="price-highlight">
                                            <?php echo formatPrice($devis_item['total_ttc']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" 
                                            onclick="viewDevis(<?php echo $devis_item['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i> Voir
                                    </button>
                                    <button class="btn btn-sm btn-outline-success flex-fill"
                                            onclick="sendReminder(<?php echo $devis_item['id']; ?>)">
                                        <i class="fas fa-paper-plane me-1"></i> Relancer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDevis(devisId) {
            // Ouvrir le devis dans un nouvel onglet
            window.open(`devis_client.php?id=${devisId}`, '_blank');
        }
        
        function sendReminder(devisId) {
            if (confirm('Voulez-vous renvoyer une relance pour ce devis ?')) {
                // Implémenter la logique de relance
                fetch('../ajax/renvoyer_devis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ devis_id: devisId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Relance envoyée avec succès !');
                    } else {
                        alert('Erreur lors de l\'envoi de la relance: ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'envoi de la relance');
                });
            }
        }
        
        // Rafraîchir la page toutes les 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 5 * 60 * 1000);
    </script>
</body>
</html>
