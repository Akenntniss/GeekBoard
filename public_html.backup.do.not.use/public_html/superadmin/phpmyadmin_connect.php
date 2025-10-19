<?php
// Connexion directe à PhpMyAdmin pour un magasin spécifique
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si un ID de magasin est fourni
if (!isset($_GET['shop_id']) || empty($_GET['shop_id'])) {
    $_SESSION['message'] = 'ID de magasin manquant';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$shop_id = (int)$_GET['shop_id'];

// Inclure la configuration de la base de données
require_once('../config/database.php');

try {
    // Récupérer les informations du magasin
    $pdo = getMainDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ? AND active = 1 LIMIT 1");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        $_SESSION['message'] = 'Magasin non trouvé ou inactif';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
    
    // Tester la connexion à la base de données du magasin
    try {
        $shop_dsn = "mysql:host={$shop['db_host']};port={$shop['db_port']};dbname={$shop['db_name']};charset=utf8mb4";
        $shop_pdo = new PDO($shop_dsn, $shop['db_user'], $shop['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        // Test simple pour vérifier la connexion
        $test = $shop_pdo->query("SELECT 1")->fetch();
        
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Impossible de se connecter à la base de données du magasin: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['message'] = 'Erreur lors de la récupération des informations du magasin: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès PhpMyAdmin - <?php echo htmlspecialchars($shop['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .connection-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }
        .icon-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 2rem;
        }
        .shop-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            text-align: left;
        }
        .connection-form {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
        }
        .btn-connect {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 25px;
            text-decoration: none;
            margin: 10px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-connect:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            margin: 10px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #667eea;
            font-family: 'Courier New', monospace;
        }
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: none;
            border-radius: 10px;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="connection-card">
        <div class="icon-container">
            <i class="fas fa-database"></i>
        </div>
        
        <h2 class="mb-4">Accès à PhpMyAdmin</h2>
        
        <div class="shop-info">
            <h5 class="text-center mb-3">
                <i class="fas fa-store me-2"></i><?php echo htmlspecialchars($shop['name']); ?>
            </h5>
            
            <div class="info-row">
                <span class="info-label">Base de données:</span>
                <span class="info-value"><?php echo htmlspecialchars($shop['db_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Serveur:</span>
                <span class="info-value"><?php echo htmlspecialchars($shop['db_host'] . ':' . $shop['db_port']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Utilisateur:</span>
                <span class="info-value"><?php echo htmlspecialchars($shop['db_user']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Connexion vérifiée</span>
            </div>
        </div>
        
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Instructions:</strong> Cliquez sur le bouton ci-dessous pour ouvrir PhpMyAdmin. 
            Vous devrez saisir les identifiants affichés ci-dessus.
        </div>
        
        <div class="connection-form">
            <h6 class="mb-3"><i class="fas fa-key me-2"></i>Informations de connexion à copier</h6>
            
            <div class="row text-start">
                <div class="col-md-6">
                    <label class="form-label"><strong>Serveur:</strong></label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($shop['db_host']); ?>" readonly onclick="this.select()">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>Nom d'utilisateur:</strong></label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($shop['db_user']); ?>" readonly onclick="this.select()">
                </div>
            </div>
            
            <div class="row mt-3 text-start">
                <div class="col-md-6">
                    <label class="form-label"><strong>Mot de passe:</strong></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" value="<?php echo htmlspecialchars($shop['db_pass']); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>Base de données:</strong></label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($shop['db_name']); ?>" readonly onclick="this.select()">
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="/phpmyadmin/" target="_blank" class="btn-connect">
                <i class="fas fa-external-link-alt me-2"></i>Ouvrir PhpMyAdmin
            </a>
            <br>
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
            </a>
        </div>
        
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Connexion sécurisée - Les identifiants sont chargés depuis la base de données principale
            </small>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
                passwordField.select();
            } else {
                passwordField.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }
        
        // Ajouter des raccourcis pour copier facilement
        document.querySelectorAll('input[readonly]').forEach(input => {
            input.addEventListener('click', function() {
                this.select();
                document.execCommand('copy');
                
                // Feedback visuel
                const originalBg = this.style.backgroundColor;
                this.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    this.style.backgroundColor = originalBg;
                }, 500);
            });
        });
    </script>
</body>
</html> 