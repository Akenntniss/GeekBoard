<?php
// pages/subscription/company_profile.php

$error = '';
$success = '';

// Connexion à la DB du Shop pour lire/écrire les paramètres
$shop_pdo = getShopDBConnectionById($_SESSION['client_shop_id']);

if (!$shop_pdo) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données du magasin.</div>";
    return;
}

// Fonction utilitaire pour lire un paramètre
function getParam($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetchColumn();
    return $res !== false ? $res : $default;
}

// Fonction utilitaire pour sauvegarder un paramètre
function setParam($pdo, $key, $value) {
    // Upsert (Insert or Update)
    $stmt = $pdo->prepare("INSERT INTO parametres (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?");
    $stmt->execute([$key, $value, $value]);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_siret = trim($_POST['company_siret'] ?? '');
    $billing_email = trim($_POST['billing_email'] ?? '');

    try {
        setParam($shop_pdo, 'shop_name', $company_name);
        setParam($shop_pdo, 'shop_address', $company_address);
        setParam($shop_pdo, 'shop_siret', $company_siret);
        setParam($shop_pdo, 'billing_email', $billing_email);

        // Mise à jour du nom en session aussi
        $_SESSION['client_shop_name'] = $company_name;

        $success = "Informations mises à jour avec succès.";
    } catch (Exception $e) {
        $error = "Erreur lors de la sauvegarde : " . $e->getMessage();
    }
}

// Chargement des valeurs actuelles
$current_name = getParam($shop_pdo, 'shop_name', $_SESSION['client_shop_name']);
$current_address = getParam($shop_pdo, 'shop_address', '');
$current_siret = getParam($shop_pdo, 'shop_siret', '');
$current_email = getParam($shop_pdo, 'billing_email', $_SESSION['client_user_email']);

?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle" style="margin-right: 0.5rem;"></i> <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Informations de l'entreprise</h2>
    <p class="text-muted mb-4">Ces informations apparaîtront sur vos factures et documents officiels.</p>

    <form method="POST" action="">
        <div class="form-group mb-4">
            <label class="form-label" for="company_name">Nom de l'entreprise / Enseigne</label>
            <input type="text" id="company_name" name="company_name" class="form-input" value="<?= htmlspecialchars($current_name) ?>" required>
        </div>

        <div class="form-group mb-4">
            <label class="form-label" for="company_address">Adresse complète (Siège social)</label>
            <textarea id="company_address" name="company_address" class="form-input" rows="3" placeholder="123 Rue de la Réparation, 75000 Paris"><?= htmlspecialchars($current_address) ?></textarea>
        </div>

        <div class="form-group mb-4">
            <label class="form-label" for="company_siret">Numéro SIRET / TVA</label>
            <input type="text" id="company_siret" name="company_siret" class="form-input" value="<?= htmlspecialchars($current_siret) ?>" placeholder="123 456 789 00012">
        </div>

        <div class="form-group mb-4">
            <label class="form-label" for="billing_email">Email de facturation</label>
            <input type="email" id="billing_email" name="billing_email" class="form-input" value="<?= htmlspecialchars($current_email ?? '') ?>" required>
            <p class="text-xs text-muted mt-1">Les factures seront envoyées à cette adresse.</p>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-700 flex justify-end" style="border-top: 1px solid var(--border-color);">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save" style="margin-right: 0.5rem;"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
