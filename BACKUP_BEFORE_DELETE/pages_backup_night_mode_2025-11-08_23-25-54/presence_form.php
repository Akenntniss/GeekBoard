<?php
// Page de formulaire spécialisé selon le type d'événement
// S'assurer que la session est démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$type = $_GET['type'] ?? '';
$allowed_types = ['retard', 'absence', 'conge_paye', 'conge_sans_solde'];

if (!in_array($type, $allowed_types)) {
    header('Location: index.php?page=presence_ajouter');
    exit;
}

// Traitement du formulaire POST
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Inclure les fonctions de base de données si nécessaire
        include_once 'includes/presence_auto_init.php';
        
        if (function_exists('getShopDBConnection')) {
            $shop_pdo = getShopDBConnection();
            
            // Récupérer les données du formulaire
            $user_id = $_POST['user_id'] ?? null;
            $comment = trim($_POST['comment'] ?? '');
            $current_user_id = $_SESSION['user_id'] ?? null;
            
            // Validation de base
            if (!$user_id || !$comment) {
                throw new Exception("Tous les champs sont obligatoires.");
            }
            
            // Obtenir l'ID du type de présence
            $stmt = $shop_pdo->prepare("SELECT id FROM presence_types WHERE name = ?");
            $stmt->execute([$type]);
            $type_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$type_data) {
                throw new Exception("Type d'événement non valide.");
            }
            
            $type_id = $type_data['id'];
            
            // Préparer les données selon le type
            if ($type === 'retard') {
                $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
                if ($duration_minutes <= 0 || $duration_minutes > 480) {
                    throw new Exception("Durée invalide (entre 1 et 480 minutes).");
                }
                
                // Pour les retards, gérer la date
                $date_retard = $_POST['date_retard'] ?? '';
                if (empty($date_retard)) {
                    $date_start = new DateTime(); // Aujourd'hui par défaut
                } else {
                    $date_start = new DateTime($date_retard);
                }
                $date_end = null;
                
            } else {
                // Pour absence, congé payé, congé sans solde
                $date_debut = $_POST['date_debut'] ?? '';
                $date_fin = $_POST['date_fin'] ?? '';
                
                if (!$date_debut || !$date_fin) {
                    throw new Exception("Les dates de début et fin sont obligatoires.");
                }
                
                $date_start = new DateTime($date_debut);
                $date_end = new DateTime($date_fin);
                $duration_minutes = null;
                
                if ($date_start > $date_end) {
                    throw new Exception("La date de fin doit être après la date de début.");
                }
            }
            
            // Insérer l'événement
            $sql = "INSERT INTO presence_events (employee_id, type_id, date_start, date_end, duration_minutes, comment, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $shop_pdo->prepare($sql);
            $stmt->execute([
                $user_id,
                $type_id,
                $date_start->format('Y-m-d H:i:s'),
                $date_end ? $date_end->format('Y-m-d H:i:s') : null,
                $duration_minutes,
                $comment,
                $current_user_id
            ]);
            
            $success_message = "Événement créé avec succès ! Vous pouvez consulter tous vos événements dans la page de gestion.";
            
        } else {
            throw new Exception("Connexion à la base de données impossible.");
        }
        
    } catch (Exception $e) {
        $error_message = "Erreur : " . $e->getMessage();
    }
}

// Configuration selon le type
$config = [
    'retard' => [
        'title' => 'Déclarer un Retard',
        'icon' => 'fas fa-clock',
        'color' => 'warning',
        'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
        'description' => 'Enregistrer une arrivée tardive ou un départ anticipé'
    ],
    'absence' => [
        'title' => 'Déclarer une Absence',
        'icon' => 'fas fa-user-times',
        'color' => 'danger',
        'gradient' => 'linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%)',
        'description' => 'Enregistrer une absence non planifiée'
    ],
    'conge_paye' => [
        'title' => 'Déclarer un Congé Payé',
        'icon' => 'fas fa-umbrella-beach',
        'color' => 'success',
        'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'description' => 'Enregistrer des vacances ou RTT'
    ],
    'conge_sans_solde' => [
        'title' => 'Déclarer un Congé Sans Solde',
        'icon' => 'fas fa-hand-paper',
        'color' => 'secondary',
        'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'description' => 'Enregistrer un congé non rémunéré'
    ]
];

$current_config = $config[$type];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <div class="mt-2">
                        <a href="index.php?page=presence_gestion" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-list me-1"></i>Voir tous les événements
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <!-- Header avec breadcrumb -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item">
                                <a href="index.php?page=presence_gestion">Gestion</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="index.php?page=presence_ajouter">Ajouter</a>
                            </li>
                            <li class="breadcrumb-item active"><?php echo $current_config['title']; ?></li>
                        </ol>
                    </nav>
                    <h1>
                        <i class="<?php echo $current_config['icon']; ?> me-2"></i>
                        <?php echo $current_config['title']; ?>
                    </h1>
                </div>
                <a href="index.php?page=presence_ajouter" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Changer de type
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Carte principale -->
                    <div class="card shadow-lg border-0">
                        <div class="card-header text-white text-center py-4" 
                             style="background: <?php echo $current_config['gradient']; ?>; border-radius: 15px 15px 0 0;">
                            <div class="event-icon mb-3">
                                <i class="<?php echo $current_config['icon']; ?>"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $current_config['title']; ?></h4>
                            <p class="mb-0 mt-2 opacity-75"><?php echo $current_config['description']; ?></p>
                        </div>
                        
                        <div class="card-body p-4">
                            <form method="POST" action="index.php?page=presence_form&type=<?php echo $type; ?>" id="eventForm">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="event_type" value="<?php echo $type; ?>">
                                
                                <!-- Sélection utilisateur -->
                                <div class="mb-4">
                                    <label for="user_id" class="form-label">
                                        <i class="fas fa-user me-2"></i>Utilisateur concerné
                                    </label>
                                    <?php 
                                    // Debug de la session
                                    error_log("DEBUG PRESENCE FORM - Session: " . json_encode($_SESSION));
                                    
                                    $current_user_id = $_SESSION['user_id'] ?? null;
                                    $current_user_name = '';
                                    
                                    // Récupérer le nom de l'utilisateur d'abord
                                    if (function_exists('getShopDBConnection') && $current_user_id) {
                                        try {
                                            $shop_pdo = getShopDBConnection();
                                            $stmt = $shop_pdo->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
                                            $stmt->execute([$current_user_id]);
                                            $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
                                            if ($current_user) {
                                                $current_user_name = $current_user['full_name'] ?: $current_user['username'];
                                            }
                                        } catch (Exception $e) {
                                            $current_user_name = 'Utilisateur inconnu';
                                        }
                                    }
                                    
                                    // Détecter les administrateurs (plusieurs possibilités)
                                    $is_admin = (
                                        (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'administrateur', 'superadmin'])) ||
                                        (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'administrateur', 'superadmin'])) ||
                                        (isset($_SESSION['superadmin_id']) && $_SESSION['superadmin_id']) ||
                                        (strpos(strtolower($current_user_name), 'administrateur') !== false)
                                    );
                                    
                                    // Debug du rôle
                                    error_log("DEBUG PRESENCE FORM - User ID: " . $current_user_id . ", Role: " . ($_SESSION['role'] ?? 'non défini') . ", User Role: " . ($_SESSION['user_role'] ?? 'non défini') . ", Is Admin: " . ($is_admin ? 'true' : 'false'));
                                    
                                    if ($is_admin): ?>
                                        <!-- Admin peut sélectionner n'importe quel utilisateur -->
                                        <select class="form-select form-select-lg" id="user_id" name="user_id" required>
                                            <?php
                                            if (function_exists('getShopDBConnection')) {
                                                try {
                                                    $shop_pdo = getShopDBConnection();
                                                    $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
                                                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($users as $user) {
                                                        $selected = ($user['id'] == $current_user_id) ? 'selected' : '';
                                                        echo '<option value="' . $user['id'] . '" ' . $selected . '>' . 
                                                             htmlspecialchars($user['full_name'] ?: $user['username']) . 
                                                             '</option>';
                                                    }
                                                } catch (Exception $e) {
                                                    echo '<option value="">Erreur de chargement</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    <?php else: ?>
                                        <!-- Utilisateur normal ne peut que se sélectionner lui-même -->
                                        <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                                        <div class="form-control form-control-lg bg-light" style="border: 2px solid #e9ecef;">
                                            <i class="fas fa-user-check me-2 text-success"></i>
                                            <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                                            <small class="text-muted ms-2">(Vous)</small>
                                        </div>
                                        <small class="form-text text-muted mt-1">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Vous ne pouvez déclarer des événements que pour votre propre compte
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <?php if ($type === 'retard'): ?>
                                    <!-- FORMULAIRE RETARD -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-day me-2"></i>Date du retard
                                        </label>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <button type="button" class="btn btn-primary btn-lg w-100" onclick="setToday()">
                                                    <i class="fas fa-clock me-2"></i>Aujourd'hui
                                                </button>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" class="form-control form-control-lg" 
                                                       id="retard_date" name="event_date" 
                                                       value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="duration_minutes" class="form-label">
                                            <i class="fas fa-stopwatch me-2"></i>Durée du retard (en minutes)
                                        </label>
                                        <div class="row g-2">
                                            <div class="col-8">
                                                <input type="number" class="form-control form-control-lg" 
                                                       id="duration_minutes" name="duration_minutes" 
                                                       min="1" max="480" value="30" required
                                                       placeholder="Exemple: 45">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Saisissez la durée en minutes (max: 8h = 480min)
                                                </small>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-control form-control-lg bg-light text-center" 
                                                     style="border: 2px solid #e9ecef;">
                                                    <i class="fas fa-clock me-1 text-primary"></i>
                                                    <strong id="duration_display">30min</strong>
                                                </div>
                                                <small class="form-text text-muted text-center d-block">
                                                    Équivalent
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    <!-- FORMULAIRE ABSENCE/CONGÉS -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-alt me-2"></i>Période d'absence
                                        </label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="date_start" class="form-label small">Date de début</label>
                                                <input type="date" class="form-control form-control-lg" 
                                                       id="date_start" name="date_start" 
                                                       value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="date_end" class="form-label small">Date de fin</label>
                                                <input type="date" class="form-control form-control-lg" 
                                                       id="date_end" name="date_end" 
                                                       value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Durée calculée : <span id="calculated_duration" class="fw-bold">1 jour</span>
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Commentaire -->
                                <div class="mb-4">
                                    <label for="comment" class="form-label">
                                        <i class="fas fa-comment me-2"></i>Commentaire 
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="comment" name="comment" 
                                              rows="3" placeholder="Précisions, justification..." required></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        Veuillez expliquer la raison de cet événement
                                    </small>
                                </div>

                                <!-- Boutons d'action -->
                                <div class="d-flex justify-content-between pt-3">
                                    <a href="index.php?page=presence_ajouter" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Retour
                                    </a>
                                    <button type="submit" class="btn btn-<?php echo $current_config['color']; ?> btn-lg">
                                        <i class="fas fa-save me-2"></i>Enregistrer l'événement
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.event-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: rgba(255,255,255,0.2);
    color: white;
    font-size: 1.5rem;
}

.card {
    border-radius: 15px;
}

.form-control:focus,
.form-select:focus {
    border-color: #<?php echo $type === 'retard' ? 'ffc107' : ($type === 'absence' ? 'dc3545' : ($type === 'conge_paye' ? '198754' : '6c757d')); ?>;
    box-shadow: 0 0 0 0.2rem rgba(<?php echo $type === 'retard' ? '255, 193, 7' : ($type === 'absence' ? '220, 53, 69' : ($type === 'conge_paye' ? '25, 135, 84' : '108, 117, 125')); ?>, 0.25);
}

.opacity-75 {
    opacity: 0.75;
}

/* Animation */
.card {
    animation: slideInUp 0.5s ease;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
// Fonction pour définir la date d'aujourd'hui (retard)
function setToday() {
    document.getElementById('retard_date').value = '<?php echo date('Y-m-d'); ?>';
}

// Calcul de la durée pour les retards
<?php if ($type === 'retard'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const durationMinutes = document.getElementById('duration_minutes');
    const durationDisplay = document.getElementById('duration_display');
    
    function updateDurationDisplay() {
        const minutes = parseInt(durationMinutes.value) || 0;
        
        if (minutes === 0) {
            durationDisplay.innerHTML = '<span class="text-muted">-</span>';
        } else if (minutes < 60) {
            durationDisplay.innerHTML = minutes + 'min';
        } else {
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            
            if (remainingMinutes === 0) {
                durationDisplay.innerHTML = hours + 'h';
            } else {
                durationDisplay.innerHTML = hours + 'h ' + remainingMinutes + 'min';
            }
        }
        
        // Validation visuelle
        if (minutes > 480) {
            durationMinutes.style.borderColor = '#dc3545';
            durationDisplay.innerHTML = '<span class="text-danger">⚠️ Trop long</span>';
        } else if (minutes > 0) {
            durationMinutes.style.borderColor = '#198754';
        } else {
            durationMinutes.style.borderColor = '#ced4da';
        }
    }
    
    durationMinutes.addEventListener('input', updateDurationDisplay);
    durationMinutes.addEventListener('change', updateDurationDisplay);
    updateDurationDisplay(); // Initial call
});
<?php else: ?>
// Calcul de la durée pour les absences/congés
document.addEventListener('DOMContentLoaded', function() {
    const dateStart = document.getElementById('date_start');
    const dateEnd = document.getElementById('date_end');
    const calculatedDuration = document.getElementById('calculated_duration');
    
    function updateDuration() {
        if (dateStart.value && dateEnd.value) {
            const start = new Date(dateStart.value);
            const end = new Date(dateEnd.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (diffDays === 1) {
                calculatedDuration.textContent = '1 jour';
            } else {
                calculatedDuration.textContent = diffDays + ' jours';
            }
        }
    }
    
    dateStart.addEventListener('change', updateDuration);
    dateEnd.addEventListener('change', updateDuration);
    updateDuration(); // Initial call
});
<?php endif; ?>
</script>
