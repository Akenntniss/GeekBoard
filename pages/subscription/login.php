<?php
// pages/subscription/login.php
// Logique d'authentification pour l'espace client
require_once __DIR__ . '/../../classes/TokenManager.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subdomain = trim($_POST['subdomain'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($subdomain) || empty($username) || empty($password)) {
        $error = "Tous les champs sont requis.";
    } else {
        try {
            // 1. Chercher le shop via le sous-domaine dans la DB principale
            $main_pdo = getMainDBConnection();
            $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? LIMIT 1");
            $stmt->execute([$subdomain]);
            $shop = $stmt->fetch();

            if ($shop) {
                // 2. Se connecter à la DB du shop
                 // Réutiliser la logique de connection existante (via config manuelle pour être sûr)
                $shop_config = [
                    'host' => $shop['db_host'],
                    'port' => $shop['db_port'] ?? 3306,
                    'user' => $shop['db_user'],
                    'pass' => $shop['db_pass'],
                    'dbname' => $shop['db_name']
                ];

                $shop_pdo = connectToShopDB($shop_config);

                if ($shop_pdo) {
                    // 3. Vérifier les identifiants admin dans la DB du shop
                    // On cherche un user avec ce username
                    $user_stmt = $shop_pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                    $user_stmt->execute([$username]);
                    $user = $user_stmt->fetch();

                    // Vérification du mot de passe (password_verify standard)
                    if ($user && password_verify($password, $user['password'])) {
                        // SUCCÈS : Connexion établie
                        
                        // Génération du Token de sécurité
                        $tokenManager = new TokenManager();
                        $token = $tokenManager->createSession($shop['id'], $user['id']);
                        
                        // Stockage du token en session PHP (seul lien de confiance)
                        $_SESSION['subscription_access_token'] = $token;
                        $_SESSION['client_subscription_logged_in'] = true;
                        
                        // On garde quelques infos d'affichage en session pour éviter des requêtes inutiles, 
                        // mais l'auth critique se fera via le token
                        $_SESSION['client_user_email'] = $user['email'] ?? '';
                        $_SESSION['client_shop_name'] = $shop['name']; // Pour l'affichage
                        
                        // Redirection vers le dashboard
                        header('Location: /subscription_router.php?page=dashboard');
                        exit;
                    } else {
                        $error = "Identifiants incorrects.";
                    }
                } else {
                    $error = "Impossible de se connecter à la base de données du magasin.";
                }
            } else {
                $error = "Sous-domaine introuvable."; // Note: Pour la sécu, on devrait peut-être être moins explicite, mais c'est utile pour l'UX ici.
            }
        } catch (Exception $e) {
            $error = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Espace Client - GeekBoard</title>
    <link rel="stylesheet" href="/assets/css/subscription.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <img src="/assets/images/logo-white.png" alt="GeekBoard" style="height: 40px; margin-bottom: 1rem;">
            <h1>Espace Client</h1>
            <p class="text-muted">Gérez votre abonnement et votre facturation</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="subdomain">Sous-domaine de votre boutique</label>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="text" id="subdomain" name="subdomain" class="form-input" placeholder="ex: mkmkmk" required value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>">
                    <span class="text-muted">.servo.tools</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="ex: admin" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Votre mot de passe principal" required>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </div>
            
            <div class="text-center mt-4">
                <a href="/" class="text-sm text-muted">Retour au site principal</a>
            </div>
        </form>
    </div>
</body>
</html>
