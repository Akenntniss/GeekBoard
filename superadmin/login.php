<?php
// Page de connexion du super administrateur
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['superadmin_id'])) {
    header('Location: index.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$error = '';
$username = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Rechercher l'utilisateur dans la base de données
        $pdo = getMainDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM superadmins WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['superadmin_id'] = $user['id'];
            $_SESSION['superadmin_username'] = $user['username'];
            $_SESSION['superadmin_name'] = $user['full_name'];
            
            // Redirection vers le tableau de bord
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects ou compte inactif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion SuperAdmin GeekBoard">
    <meta name="theme-color" content="#2563eb">
    <title>GeekBoard SuperAdmin - Connexion</title>
    
    <!-- Preconnect pour performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Fonts modernes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --login-primary: #2563eb;
            --login-primary-hover: #1d4ed8;
            --login-bg: #f8fafc;
            --login-surface: #ffffff;
            --login-border: #e2e8f0;
            --login-text: #0f172a;
            --login-text-muted: #64748b;
            --login-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shimmer 4s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
        }

        .login-container {
            background: var(--login-surface);
            border-radius: 2rem;
            box-shadow: var(--login-shadow);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(20px);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--login-primary) 0%, #764ba2 100%);
            width: 80px;
            height: 80px;
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .logo-text {
            font-weight: 800;
            font-size: 1.75rem;
            background: linear-gradient(135deg, var(--login-primary) 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .logo-subtitle {
            color: var(--login-text-muted);
            font-size: 1rem;
            font-weight: 500;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 2px solid var(--login-border);
            border-radius: 1rem;
            padding: 1.25rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: var(--login-surface);
        }

        .form-control:focus {
            border-color: var(--login-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: var(--login-surface);
        }

        .form-floating label {
            color: var(--login-text-muted);
            font-weight: 500;
        }

        .btn-login {
            background: var(--login-primary);
            border: 2px solid var(--login-primary);
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 1rem;
            border-radius: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            background: var(--login-primary-hover);
            border-color: var(--login-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.4);
        }

        .alert {
            border-radius: 1rem;
            border: 1px solid transparent;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
            border-color: rgba(220, 38, 38, 0.2);
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: var(--login-text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            color: var(--login-primary);
            transform: translateX(-3px);
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--login-text-muted);
            font-size: 0.875rem;
        }

        /* Animation d'entrée */
        .login-container {
            animation: slideIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(2rem) scale(0.95);
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 2rem 1.5rem;
                border-radius: 1.5rem;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .logo-text {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo et titre -->
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="logo-text">GeekBoard</div>
            <div class="logo-subtitle">Administration centrale</div>
        </div>

        <!-- Formulaire de connexion -->
        <form method="post" action="">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       placeholder="Nom d'utilisateur" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       required 
                       autocomplete="username">
                <label for="username">
                    <i class="fas fa-user me-2"></i>Nom d'utilisateur
                </label>
            </div>
            
            <div class="form-floating">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Mot de passe" 
                       required 
                       autocomplete="current-password">
                <label for="password">
                    <i class="fas fa-lock me-2"></i>Mot de passe
                </label>
            </div>
            
            <button class="w-100 btn btn-login" type="submit">
                <i class="fas fa-sign-in-alt me-2"></i>
                Se connecter
            </button>
        </form>

        <!-- Liens de navigation -->
        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Retour à l'accueil
            </a>
        </div>

        <!-- Footer -->
        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> GeekBoard - Fait avec ❤️ pour les professionnels
        </div>
    </div>
    
    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts d'amélioration UX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus automatique sur le champ nom d'utilisateur si vide
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            } else if (passwordField) {
                passwordField.focus();
            }

            // Animation de secousse en cas d'erreur
            const errorAlert = document.querySelector('.alert-danger');
            if (errorAlert) {
                errorAlert.style.animation = 'shake 0.5s ease-in-out';
            }

            // Validation en temps réel
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('.btn-login');
            
            function validateForm() {
                const username = usernameField.value.trim();
                const password = passwordField.value;
                
                if (username && password) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                }
            }

            usernameField.addEventListener('input', validateForm);
            passwordField.addEventListener('input', validateForm);
            
            // Validation initiale
            validateForm();

            // Animation de chargement lors de la soumission
            form.addEventListener('submit', function() {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connexion...';
                submitBtn.disabled = true;
            });

            // Raccourci clavier Ctrl+L pour focus sur username
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                    e.preventDefault();
                    usernameField.focus();
                    usernameField.select();
                }
            });
        });

        // Styles pour l'animation de secousse
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html> 