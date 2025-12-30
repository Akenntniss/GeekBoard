<?php
/* ====================================================================
   🧪 TEST DU SYSTÈME UNIFIÉ DE MODE NUIT
   Page de test pour valider l'adaptation au mode nuit
==================================================================== */

session_start();
$_SESSION['user_id'] = 1; // Simuler un utilisateur connecté

include_once 'includes/night-mode-system.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌙 Test Mode Nuit - GeekBoard</title>
    
    <!-- Bootstrap pour les tests -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .theme-info {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            font-size: 12px;
            z-index: 1000;
        }
        
        .color-demo {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
            border: 2px solid currentColor;
        }
    </style>
</head>
<body class="no-flash">
    <!-- Indicateur de thème -->
    <div class="theme-info" id="themeInfo">
        <div>Thème: <span id="currentTheme">Détection...</span></div>
        <div>Système: <span id="systemPreference">Détection...</span></div>
        <div>Stocké: <span id="storedPreference">Détection...</span></div>
    </div>

    <!-- Navigation de test -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-cog"></i> GeekBoard Test</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#"><i class="fas fa-home"></i> Accueil</a>
                <a class="nav-link" href="#"><i class="fas fa-users"></i> Clients</a>
                <a class="nav-link active" href="#"><i class="fas fa-tools"></i> Réparations</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-moon"></i> Test du Système Unifié de Mode Nuit</h1>
                <p class="lead">Cette page teste l'adaptation automatique au mode nuit selon les préférences système.</p>
                
                <!-- Bouton de test -->
                <div class="mb-4">
                    <button class="btn btn-primary" onclick="toggleTheme()">
                        <i class="fas fa-adjust"></i> Basculer le Thème
                    </button>
                    <button class="btn btn-secondary" onclick="resetTheme()">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                    <button class="btn btn-info" onclick="updateInfo()">
                        <i class="fas fa-sync"></i> Actualiser Info
                    </button>
                </div>
            </div>
        </div>

        <!-- Test des cartes -->
        <div class="row">
            <div class="col-md-4">
                <div class="card test-section">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Statistiques</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-primary">1,234</h3>
                        <p class="text-muted">Réparations ce mois</p>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: 75%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-section">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Clients</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-success">567</h3>
                        <p class="text-muted">Clients actifs</p>
                        <small class="text-secondary">+12% ce mois</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-section">
                    <div class="card-header">
                        <h5><i class="fas fa-euro-sign"></i> Revenus</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-warning">€15,678</h3>
                        <p class="text-muted">Chiffre d'affaires</p>
                        <span class="badge bg-success">+8.5%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test des formulaires -->
        <div class="row">
            <div class="col-md-6">
                <div class="card test-section">
                    <div class="card-header">
                        <h5><i class="fas fa-edit"></i> Formulaire de Test</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Nom du client</label>
                                <input type="text" class="form-control" placeholder="Entrez le nom">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type de réparation</label>
                                <select class="form-select">
                                    <option>Écran cassé</option>
                                    <option>Batterie défaillante</option>
                                    <option>Problème logiciel</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3" placeholder="Décrivez le problème..."></textarea>
                            </div>
                            <div class="d-grid gap-2 d-md-flex">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                                <button class="btn btn-secondary" type="button">
                                    <i class="fas fa-times"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card test-section">
                    <div class="card-header">
                        <h5><i class="fas fa-palette"></i> Test des Couleurs</h5>
                    </div>
                    <div class="card-body">
                        <p>Couleurs primaires :</p>
                        <div class="color-demo bg-primary"></div>
                        <div class="color-demo bg-secondary"></div>
                        <div class="color-demo bg-success"></div>
                        <div class="color-demo bg-warning"></div>
                        <div class="color-demo bg-danger"></div>
                        <div class="color-demo bg-info"></div>
                        
                        <hr>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Alerte de succès
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Alerte d'avertissement
                        </div>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Alerte d'erreur
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test du tableau -->
        <div class="row">
            <div class="col-12">
                <div class="card test-section">
                    <div class="card-header">
                        <h5><i class="fas fa-table"></i> Tableau de Test</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>001</td>
                                    <td>Jean Dupont</td>
                                    <td>iPhone 12</td>
                                    <td><span class="badge bg-warning">En cours</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-success"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>002</td>
                                    <td>Marie Martin</td>
                                    <td>Samsung Galaxy</td>
                                    <td><span class="badge bg-success">Terminé</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-info"><i class="fas fa-print"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>003</td>
                                    <td>Pierre Durand</td>
                                    <td>iPad Pro</td>
                                    <td><span class="badge bg-danger">Bloqué</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-warning"><i class="fas fa-tools"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de test -->
    <div class="modal fade" id="testModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Modal de Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Cette modal teste l'adaptation au mode nuit.</p>
                    <div class="form-group">
                        <label>Champ de test :</label>
                        <input type="text" class="form-control" placeholder="Tapez quelque chose...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton pour ouvrir la modal -->
    <div class="position-fixed bottom-0 end-0 p-3">
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#testModal">
            <i class="fas fa-window-maximize"></i> Test Modal
        </button>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fonctions de test
        function toggleTheme() {
            if (window.GeekBoardTheme) {
                window.GeekBoardTheme.toggle();
                updateInfo();
            }
        }
        
        function resetTheme() {
            localStorage.removeItem('geekboard_theme_user_1');
            localStorage.removeItem('geekboard_theme');
            if (window.GeekBoardTheme) {
                window.GeekBoardTheme.apply();
                updateInfo();
            }
        }
        
        function updateInfo() {
            const themeInfo = document.getElementById('themeInfo');
            const currentTheme = document.getElementById('currentTheme');
            const systemPreference = document.getElementById('systemPreference');
            const storedPreference = document.getElementById('storedPreference');
            
            // Détection du thème actuel
            const isNight = document.body.classList.contains('night-mode');
            currentTheme.textContent = isNight ? '🌙 Nuit' : '☀️ Jour';
            
            // Préférence système
            const systemPrefers = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            systemPreference.textContent = systemPrefers ? '🌙 Sombre' : '☀️ Clair';
            
            // Préférence stockée
            const stored = localStorage.getItem('geekboard_theme_user_1') || localStorage.getItem('geekboard_theme');
            storedPreference.textContent = stored || 'Aucune';
            
            // Couleur de l'indicateur
            themeInfo.style.background = isNight ? 'rgba(26, 31, 46, 0.95)' : 'rgba(0, 0, 0, 0.8)';
        }
        
        // Écouter les changements de thème
        document.addEventListener('themeChanged', function(e) {
            console.log('🎨 Thème changé:', e.detail.theme);
            updateInfo();
        });
        
        // Mise à jour initiale
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(updateInfo, 500);
        });
        
        // Mise à jour périodique pour les tests
        setInterval(updateInfo, 2000);
    </script>
</body>
</html>
