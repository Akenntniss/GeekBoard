<?php
// Vérifier si l'ID du client est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de client invalide";
    header("Location: index.php?page=clients");
    exit();
}

$client_id = (int)$_GET['id'];

// Récupérer les informations du client
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        $_SESSION['error'] = "Client non trouvé";
        header("Location: index.php?page=clients");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération du client: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la récupération du client";
    header("Location: index.php?page=clients");
    exit();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $errors = [];
    
    // Vérification des champs obligatoires
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire";
    }
    
    if (empty($prenom)) {
        $errors[] = "Le prénom est obligatoire";
    }
    
    if (empty($telephone)) {
        $errors[] = "Le numéro de téléphone est obligatoire";
    }
    
    // Vérification du format de l'email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Le format de l'email est invalide";
    }
    
    // Vérification si l'email existe déjà (sauf pour le client actuel)
    if (!empty($email)) {
        try {
            $stmt = $shop_pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
            $stmt->execute([$email, $client_id]);
            if ($stmt->fetch()) {
                $errors[] = "Un autre client utilise déjà cet email";
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'email: " . $e->getMessage());
            $errors[] = "Une erreur est survenue lors de la vérification de l'email";
        }
    }
    
    // Si aucune erreur, on modifie le client
    if (empty($errors)) {
        try {
            $stmt = $shop_pdo->prepare("
                UPDATE clients 
                SET nom = ?, prenom = ?, telephone = ?, email = ?, date_modification = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$nom, $prenom, $telephone, $email, $client_id]);
            
            $_SESSION['success'] = "Client modifié avec succès";
            header("Location: index.php?page=clients");
            exit();
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification du client: " . $e->getMessage());
            $errors[] = "Une erreur est survenue lors de la modification du client";
        }
    }
}
?>

<!-- En-tête de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-0">Modifier un client</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=accueil">Accueil</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=clients">Clients</a></li>
                <li class="breadcrumb-item active">Modifier</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" id="toggleDarkMode">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</div>

<!-- Formulaire de modification de client -->
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Des erreurs sont survenues</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom" value="<?php echo htmlspecialchars($nom ?? $client['nom']); ?>" required>
                        <label for="nom">Nom <span class="text-danger">*</span></label>
                        <div class="invalid-feedback">
                            Veuillez entrer un nom
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Prénom" value="<?php echo htmlspecialchars($prenom ?? $client['prenom']); ?>" required>
                        <label for="prenom">Prénom <span class="text-danger">*</span></label>
                        <div class="invalid-feedback">
                            Veuillez entrer un prénom
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Téléphone" value="<?php echo htmlspecialchars($telephone ?? $client['telephone']); ?>" required>
                        <label for="telephone">Téléphone <span class="text-danger">*</span></label>
                        <div class="invalid-feedback">
                            Veuillez entrer un numéro de téléphone
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? $client['email']); ?>">
                        <label for="email">Email</label>
                        <div class="invalid-feedback">
                            Veuillez entrer un email valide
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Les champs marqués d'un <span class="text-danger">*</span> sont obligatoires.
                    </div>
                </div>
                
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="index.php?page=clients" class="btn btn-light">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Masque pour le numéro de téléphone
    const telephoneInput = document.getElementById('telephone');
    if (telephoneInput) {
        telephoneInput.addEventListener('input', function(e) {
            // Garder uniquement les chiffres
            let value = e.target.value.replace(/\D/g, '');
            
            // Formatage du numéro (exemple pour France: 06 12 34 56 78)
            if (value.length > 0) {
                value = value.match(new RegExp('.{1,2}', 'g')).join(' ');
            }
            
            e.target.value = value;
        });
    }
    
    // Toggle Dark Mode
    const toggleDarkMode = document.getElementById('toggleDarkMode');
    if (toggleDarkMode) {
        toggleDarkMode.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-moon');
                icon.classList.toggle('fa-sun');
            }
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });
        
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            const icon = toggleDarkMode.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }
    }
});
</script> 