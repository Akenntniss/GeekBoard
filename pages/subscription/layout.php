<?php
// pages/subscription/layout.php
// Layout principal pour les pages connectées
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Espace Client') ?> - GeekBoard</title>
    <link rel="stylesheet" href="/assets/css/subscription.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-cube"></i> GeekBoard
            </div>

            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                            <i class="fa-solid fa-chart-line"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=manage_plan" class="<?= $page === 'manage_plan' ? 'active' : '' ?>">
                            <i class="fa-solid fa-shapes"></i> Abonnement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=billing" class="<?= $page === 'billing' ? 'active' : '' ?>">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Facturation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=payment_methods" class="<?= $page === 'payment_methods' ? 'active' : '' ?>">
                            <i class="fa-regular fa-credit-card"></i> Moyens de paiement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=company_profile" class="<?= $page === 'company_profile' ? 'active' : '' ?>">
                            <i class="fa-regular fa-building"></i> Profil Entreprise
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="user-info">
                <div class="shop-name">
                    <strong><?= htmlspecialchars($_SESSION['client_shop_name'] ?? 'Ma Boutique') ?></strong>
                </div>
                <div class="user-email text-sm">
                    <?= htmlspecialchars($_SESSION['client_user_email'] ?? '') ?>
                </div>
                <a href="?page=logout" class="logout-link text-sm">
                    <i class="fa-solid fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <h1 class="page-title"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
            </header>

            <div class="content-wrapper">
                <?php
                if (isset($content_view) && file_exists($content_view)) {
                    include $content_view;
                } else {
                    echo "<div class='alert alert-danger'>Vue introuvable : " . htmlspecialchars($content_view ?? 'Aucune') . "</div>";
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>
