<?php
/**
 * Version simplifiée du Dashboard KPI - pour debug
 */

// Variables de base
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Utilisateur';

if (!$current_user_id) {
    echo "<div class='alert alert-danger'>Utilisateur non authentifié. Session user_id manquante.</div>";
    return;
}
?>

<style>
    .page-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .kpi-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        padding: 2rem;
        margin: -20px -20px 20px -20px;
        border-radius: 0 0 15px 15px;
    }

    .kpi-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 15px;
        padding: 20px;
    }

    .kpi-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2563eb;
    }

    .kpi-label {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    @media (max-width: 768px) {
        .kpi-header {
            margin: -20px -15px 20px -15px;
            padding: 1.5rem;
        }
    }
</style>

<div class="page-container">
    <!-- Header KPI -->
    <div class="kpi-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-chart-line me-3"></i>
                    Dashboard KPI (Version Simplifiée)
                </h1>
                <p class="mb-0 opacity-75">Test d'intégration - Indicateurs de performance</p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-light text-dark fs-6">
                    <i class="fas fa-user me-2"></i>
                    <?php echo htmlspecialchars($user_name); ?>
                    <?php if ($is_admin): ?>
                        <span class="badge bg-warning ms-2">Admin</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Informations de session -->
    <div class="kpi-card">
        <h5><i class="fas fa-info-circle me-2"></i>Informations de Session</h5>
        <div class="row">
            <div class="col-md-6">
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($current_user_id); ?></p>
                <p><strong>Nom:</strong> <?php echo htmlspecialchars($user_name); ?></p>
                <p><strong>Rôle:</strong> <?php echo $is_admin ? 'Administrateur' : 'Employé'; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Shop ID:</strong> <?php echo $_SESSION['shop_id'] ?? 'Non défini'; ?></p>
                <p><strong>Shop Name:</strong> <?php echo $_SESSION['shop_name'] ?? 'Non défini'; ?></p>
                <p><strong>Database:</strong> <?php echo $_SESSION['current_database'] ?? 'Non défini'; ?></p>
            </div>
        </div>
    </div>

    <!-- Test de base de données -->
    <div class="kpi-card">
        <h5><i class="fas fa-database me-2"></i>Test Base de Données</h5>
        <?php
        try {
            $pdo = getShopDBConnection();
            if ($pdo) {
                echo "<div class='alert alert-success'>✅ Connexion à la base de données réussie</div>";
                
                // Test simple de requête
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'technicien')");
                $stmt->execute();
                $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p><strong>Nombre d'utilisateurs:</strong> " . $userCount['count'] . "</p>";
                
                // Test requête réparations
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE employe_id = ?");
                $stmt->execute([$current_user_id]);
                $repairCount = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p><strong>Réparations assignées à vous:</strong> " . $repairCount['count'] . "</p>";
                
            } else {
                echo "<div class='alert alert-danger'>❌ Échec de la connexion à la base de données</div>";
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>

    <!-- KPI Cards basiques -->
    <div class="row g-4">
        <div class="col-md-3">
            <div class="kpi-card text-center">
                <div class="kpi-icon bg-success bg-opacity-10 text-success mb-3 mx-auto" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <div class="kpi-value">--</div>
                <div class="kpi-label">Réparations Terminées</div>
                <small class="text-muted">Données en cours de chargement...</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="kpi-card text-center">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary mb-3 mx-auto" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-tachometer-alt fa-2x"></i>
                </div>
                <div class="kpi-value">--</div>
                <div class="kpi-label">Réparations/Heure</div>
                <small class="text-muted">Calcul en cours...</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="kpi-card text-center">
                <div class="kpi-icon bg-info bg-opacity-10 text-info mb-3 mx-auto" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <div class="kpi-value">--</div>
                <div class="kpi-label">Heures Travaillées</div>
                <small class="text-muted">Données en cours...</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="kpi-card text-center">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning mb-3 mx-auto" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-euro-sign fa-2x"></i>
                </div>
                <div class="kpi-value">--€</div>
                <div class="kpi-label">Chiffre d'Affaires</div>
                <small class="text-muted">Calcul en cours...</small>
            </div>
        </div>
    </div>

    <!-- Message d'information -->
    <div class="kpi-card mt-4">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Version Simplifiée:</strong> Cette page teste l'intégration de base du système KPI dans GeekBoard. 
            Si cette page s'affiche correctement, la version complète avec graphiques peut être activée.
        </div>
        
        <div class="d-flex gap-2">
            <a href="index.php?page=kpi_debug" class="btn btn-outline-primary">
                <i class="fas fa-bug me-2"></i>Debug Détaillé
            </a>
            <a href="index.php?page=reparations" class="btn btn-outline-success">
                <i class="fas fa-tools me-2"></i>Page Réparations
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Accueil
            </a>
        </div>
    </div>
</div>

<script>
console.log('KPI Dashboard Simple - Page chargée avec succès');
console.log('User ID:', '<?php echo $current_user_id; ?>');
console.log('Is Admin:', <?php echo $is_admin ? 'true' : 'false'; ?>);
</script>

