<?php
// Page d'export PDF des événements de présence
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-download me-2"></i>Exporter les Données</h1>
                <a href="index.php?page=presence_gestion" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la gestion
                </a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Paramètres d'export -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-cog me-2"></i>Paramètres d'export</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="index.php?page=presence_export">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="export_type" class="form-label">Type d'export</label>
                                            <select class="form-select" id="export_type" name="export_type" required>
                                                <option value="">Sélectionner le type</option>
                                                <option value="daily">Rapport journalier</option>
                                                <option value="weekly">Rapport hebdomadaire</option>
                                                <option value="monthly">Rapport mensuel</option>
                                                <option value="custom">Période personnalisée</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="format" class="form-label">Format</label>
                                            <select class="form-select" id="format" name="format">
                                                <option value="pdf" selected>PDF</option>
                                                <option value="excel">Excel (XLSX)</option>
                                                <option value="csv">CSV</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="date_range" style="display: none;">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_start" class="form-label">Date de début</label>
                                            <input type="date" class="form-control" id="date_start" name="date_start">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_end" class="form-label">Date de fin</label>
                                            <input type="date" class="form-control" id="date_end" name="date_end">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                    <label for="employee_filter" class="form-label">Utilisateur</label>
                                    <select class="form-select" id="employee_filter" name="employee_filter">
                                        <option value="">Tous les utilisateurs</option>
                                        <?php
                                        // Récupérer les utilisateurs depuis la base de données
                                        if (function_exists('getShopDBConnection')) {
                                            try {
                                                $shop_pdo = getShopDBConnection();
                                                $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
                                                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($users as $user) {
                                                    echo '<option value="' . $user['id'] . '">' . 
                                                         htmlspecialchars($user['full_name'] ?: $user['username']) . 
                                                         '</option>';
                                                }
                                            } catch (Exception $e) {
                                                echo '<option value="">Erreur de chargement</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="event_type_filter" class="form-label">Type d'événement</label>
                                            <select class="form-select" id="event_type_filter" name="event_type_filter">
                                                <option value="">Tous les types</option>
                                                <option value="retard">Retards uniquement</option>
                                                <option value="absence">Absences uniquement</option>
                                                <option value="conge_paye">Congés payés uniquement</option>
                                                <option value="conge_sans_solde">Congés sans solde uniquement</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="include_summary" name="include_summary" checked>
                                        <label class="form-check-label" for="include_summary">
                                            Inclure un résumé statistique
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="include_details" name="include_details" checked>
                                        <label class="form-check-label" for="include_details">
                                            Inclure les détails des événements
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="include_comments" name="include_comments">
                                        <label class="form-check-label" for="include_comments">
                                            Inclure les commentaires
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="previewExport()">
                                        <i class="fas fa-eye"></i> Aperçu
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-download"></i> Générer l'export
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Exports rapides -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-bolt me-2"></i>Exports rapides</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="quickExport('today')">
                                    <i class="fas fa-calendar-day"></i> Aujourd'hui
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="quickExport('week')">
                                    <i class="fas fa-calendar-week"></i> Cette semaine
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="quickExport('month')">
                                    <i class="fas fa-calendar-alt"></i> Ce mois
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="quickExport('last_month')">
                                    <i class="fas fa-calendar"></i> Mois dernier
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="fas fa-chart-bar me-2"></i>Statistiques rapides</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-primary">0</h4>
                                    <small>Cette semaine</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success">0</h4>
                                    <small>Ce mois</small>
                                </div>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-6">
                                    <h4 class="text-warning">0</h4>
                                    <small>En attente</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info">0</h4>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="fas fa-history me-2"></i>Derniers exports</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="small">Aucun export récent</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion de l'affichage conditionnel des dates
document.getElementById('export_type').addEventListener('change', function() {
    const dateRange = document.getElementById('date_range');
    if (this.value === 'custom') {
        dateRange.style.display = 'block';
    } else {
        dateRange.style.display = 'none';
    }
});

// Fonction d'export rapide
function quickExport(period) {
    const form = document.querySelector('form');
    const exportType = document.getElementById('export_type');
    
    switch(period) {
        case 'today':
            exportType.value = 'daily';
            break;
        case 'week':
            exportType.value = 'weekly';
            break;
        case 'month':
            exportType.value = 'monthly';
            break;
        case 'last_month':
            exportType.value = 'monthly';
            // Ici on pourrait définir le mois précédent
            break;
    }
    
    // Simulation de l'export
    alert('Export ' + period + ' en cours...');
    // form.submit();
}

// Fonction d'aperçu
function previewExport() {
    alert('Aperçu de l\'export - Fonctionnalité à implémenter');
}
</script>