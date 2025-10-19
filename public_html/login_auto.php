<?php
require_once __DIR__ . "/../config/session_config.php";
// Vérification abonnement: rediriger si inactif/expiré
require_once __DIR__ . '/../includes/trial_check_middleware.php';
require_once __DIR__ . '/../config/database.php';

// ✅ SYSTÈME DYNAMIQUE - Utiliser la détection automatique depuis la base de données
$host = $_SERVER["HTTP_HOST"] ?? "";

// Fonction pour détecter le magasin basé sur le sous-domaine de façon dynamique
function detectShopFromHost($host) {
    try {
        // Récupérer la connexion à la base principale
        $pdo_general = getMainDBConnection();
        
        // Construire le mapping dynamiquement pour les deux domaines
        $stmt = $pdo_general->query("SELECT id, subdomain, name FROM shops WHERE active = 1");
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($shops as $shop) {
            if (!empty($shop['subdomain'])) {
                // Support pour les deux domaines principaux
                $mdgeek_domain = $shop['subdomain'] . '.mdgeek.top';
                $servo_domain = $shop['subdomain'] . '.servo.tools';
                
                if ($host === $mdgeek_domain || $host === $servo_domain) {
                    return [
                        'id' => (int)$shop['id'],
                        'name' => $shop['name'],
                        'db' => 'geekboard_' . $shop['subdomain'],
                        'subdomain' => $shop['subdomain']
                    ];
                }
            }
        }
        
        // Alias spéciaux
        if ($host === 'cannes.mdgeek.top') {
            $stmt = $pdo_general->prepare("SELECT id, subdomain, name FROM shops WHERE id = 4 AND active = 1");
            $stmt->execute();
            $shop = $stmt->fetch();
            if ($shop) {
                return [
                    'id' => (int)$shop['id'],
                    'name' => $shop['name'],
                    'db' => 'geekboard_' . $shop['subdomain'],
                    'subdomain' => $shop['subdomain']
                ];
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Erreur détection magasin: " . $e->getMessage());
        return null;
    }
}

$current_shop = detectShopFromHost($host);

// Si on a détecté un shop, vérifier son statut d'abonnement dans la base principale
if ($current_shop && !empty($current_shop['id'])) {
    $_SESSION['shop_id'] = $current_shop['id'];
    $trial_status = checkTrialStatus($current_shop['id']);
    if (in_array($trial_status['status'], ['expired', 'inactive'])) {
        // Rediriger vers la page d'abonnement
        // Construire l'URL dynamique basée sur le domaine actuel
        $domain = (strpos($_SERVER['HTTP_HOST'], 'servo.tools') !== false) ? 'servo.tools' : 'mdgeek.top';
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        header('Location: ' . $protocol . '://' . $domain . '/subscription_required.php?shop_id=' . $current_shop['id']);
        exit;
    }
}

// Récupérer le logo configuré pour ce magasin s'il existe
$company_logo = '/assets/images/logo/logoservo.png'; // Logo par défaut
if ($current_shop && !empty($current_shop['id'])) {
    try {
        // Connexion à la base du magasin pour récupérer le logo
        $pdo_shop = new PDO("mysql:host=localhost;dbname=" . $current_shop['db'], 'geekboard_user', 'GeekBoard2024#');
        $pdo_shop->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo_shop->prepare("SELECT valeur FROM parametres WHERE cle = 'company_logo' LIMIT 1");
        $stmt->execute();
        $custom_logo = $stmt->fetchColumn();
        
        // Si un logo personnalisé existe et que le fichier est accessible, l'utiliser
        if (!empty($custom_logo) && file_exists('/var/www/mdgeek.top/' . $custom_logo)) {
            $company_logo = '/' . $custom_logo;
        }
    } catch (Exception $e) {
        // En cas d'erreur, garder le logo par défaut
        error_log("Erreur lors de la récupération du logo personnalisé: " . $e->getMessage());
    }
}
$error = '';
$success = '';

// Traitement du formulaire
if ($_POST && $current_shop) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            // Connexion à la base du magasin
            $pdo = new PDO("mysql:host=localhost;dbname=" . $current_shop['db'], 'geekboard_user', 'GeekBoard2024#');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // D'abord, récupérer l'utilisateur sans vérifier le mot de passe
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? AND role IN ('admin', 'technicien') LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            $login_success = false;
            
            if ($user) {
                // Vérifier le type de hachage du mot de passe
                if (password_get_info($user['password'])['algo'] !== null) {
                    // Mot de passe avec bcrypt (nouveau système)
                    if (password_verify($password, $user['password'])) {
                        $login_success = true;
                    }
                } else {
                    // Mot de passe avec MD5 (ancien système)
                    if ($user['password'] === md5($password)) {
                        $login_success = true;
                        
                        // Optionnel : Mettre à jour vers bcrypt
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_stmt->execute([$new_hash, $user['id']]);
                    }
                }
            }
            
            if ($login_success) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['shop_id'] = $current_shop['id'];
                $_SESSION['shop_name'] = $current_shop['name'];
                $_SESSION['shop_subdomain'] = $shop_subdomain;
                
                $success = "Connexion réussie ! Redirection...";
                
                // Construire l'URL de redirection post-auth avec propagation des paramètres utiles
                $redirectTarget = '/';
                $qs = [];
                
                // Si une page cible a été fournie (via index.php -> login.php)
                if (!empty($_GET['redirect'])) {
                    $qs[] = 'page=' . urlencode($_GET['redirect']);
                }
                // Propager id si présent
                if (!empty($_GET['id'])) {
                    $qs[] = 'id=' . urlencode($_GET['id']);
                }
                // Propager open_modal si présent
                if (!empty($_GET['open_modal'])) {
                    $qs[] = 'open_modal=' . urlencode($_GET['open_modal']);
                }
                
                if (!empty($qs)) {
                    $redirectTarget = '/index.php?' . implode('&', $qs);
                }
                
                // Redirection JavaScript pour éviter les problèmes d'en-têtes
                echo "<script>setTimeout(function(){ window.location.href = '" . $redirectTarget . "'; }, 800);</script>";
            } else {
                $error = "Identifiants incorrects pour le magasin " . $current_shop['name'];
            }
        } catch (Exception $e) {
            $error = "Erreur de connexion à la base " . $current_shop['db'] . ": " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Connexion - <?php echo $current_shop ? $current_shop['name'] : 'SERVO'; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Exo 2', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 25%, #16213e 50%, #0f3460 75%, #533483 100%);
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        /* Animation de fond futuriste */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            animation: cosmic 20s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes cosmic {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        /* Particules flottantes */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(100vh) translateX(0px); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) translateX(100px); opacity: 0; }
        }
        
        .container {
            position: relative;
            z-index: 10;
            max-width: 450px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(15, 15, 30, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: slideUp 1s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            filter: drop-shadow(0 0 20px rgba(0, 191, 255, 0.5));
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(0, 191, 255, 0.5)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 30px rgba(0, 191, 255, 0.8)); }
        }
        
        .title {
            font-family: 'Orbitron', monospace;
            font-size: 28px;
            font-weight: 900;
            color: #00bfff;
            text-shadow: 0 0 20px rgba(0, 191, 255, 0.5);
            margin-bottom: 10px;
            letter-spacing: 3px;
        }
        
        .subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .alert.error {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #ff6b7a;
        }
        
        .alert.success {
            background: rgba(40, 167, 69, 0.1);
            border-color: #28a745;
            color: #4caf50;
        }
        
        .form-group {
            margin: 25px 0;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-input:focus {
            border-color: #00bfff;
            box-shadow: 0 0 20px rgba(0, 191, 255, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #00bfff 0%, #0080ff 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 191, 255, 0.4);
        }
        
        .error-container {
            text-align: center;
            color: #ff6b7a;
            background: rgba(220, 53, 69, 0.1);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .error-container h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #ff4757;
        }
        
        .error-container code {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
        }
        
        /* Media queries pour responsive */
        @media (max-width: 480px) {
            .container {
                margin: 20px;
                padding: 25px;
            }
            
            .title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Particules animées -->
    <div class="particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 14s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 16s;"></div>
    </div>

    <div class="container">
        <div class="header">
            <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="SERVO" class="logo">
            <h1 class="title">SERVO</h1>
            <p class="subtitle">Interface de Connexion</p>
        </div>
        
        <?php if ($current_shop): ?>
            <?php if ($error): ?>
                <div class="alert error">
                    <strong>⚠️ Erreur:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success">
                    <strong>✅ Connexion établie:</strong> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Identifiant</label>
                    <input type="text" name="username" class="form-input" placeholder="Votre nom d'utilisateur" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Code d'accès</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••••••" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    Accéder au Système
                </button>
            </form>
            
        <?php else: ?>
            <div class="error-container">
                <h3>⚠️ Accès Non Autorisé</h3>
                <p><strong>Sous-domaine détecté:</strong> <code><?php echo htmlspecialchars($shop_subdomain); ?></code></p>
                <p><strong>Domaine complet:</strong> <code><?php echo htmlspecialchars($host); ?></code></p>
                <p style="margin-top: 15px; color: rgba(255, 255, 255, 0.7);">Veuillez contacter l'administrateur système.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Animation du logo au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.querySelector('.logo');
            const title = document.querySelector('.title');
            
            setTimeout(() => {
                logo.style.transform = 'rotate(360deg)';
                logo.style.transition = 'transform 1s ease-in-out';
            }, 500);
            
            // Effet de typing sur le titre
            const originalText = title.textContent;
            title.textContent = '';
            let i = 0;
            
            function typeWriter() {
                if (i < originalText.length) {
                    title.textContent += originalText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 150);
                }
            }
            
            setTimeout(typeWriter, 1000);
        });
        
        // Animation des particules supplémentaires au clic
        document.addEventListener('click', function(e) {
            const particle = document.createElement('div');
            particle.style.position = 'fixed';
            particle.style.left = e.clientX + 'px';
            particle.style.top = e.clientY + 'px';
            particle.style.width = '4px';
            particle.style.height = '4px';
            particle.style.background = '#00bfff';
            particle.style.borderRadius = '50%';
            particle.style.pointerEvents = 'none';
            particle.style.zIndex = '1000';
            particle.style.animation = 'clickParticle 1s ease-out forwards';
            
            document.body.appendChild(particle);
            
            setTimeout(() => {
                document.body.removeChild(particle);
            }, 1000);
        });
        
        // Ajouter l'animation CSS pour les particules de clic
        const style = document.createElement('style');
        style.textContent = `
            @keyframes clickParticle {
                0% { transform: scale(1); opacity: 1; }
                100% { transform: scale(10); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
