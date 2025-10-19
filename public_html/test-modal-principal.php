<?php
/**
 * Test du modal principal - Diagnostic complet
 */
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Simulation d'une session active
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['shop_id'] = 63;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modal Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .debug-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .debug-item {
            margin: 5px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .debug-success { color: #28a745; }
        .debug-error { color: #dc3545; }
        .debug-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔧 Test Modal Principal</h1>
        
        <!-- Bouton pour déclencher le modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rechercheModal">
            Ouvrir le Modal de Recherche
        </button>

        <!-- Info de diagnostic -->
        <div class="debug-info">
            <div class="debug-title">📋 Informations de Debug</div>
            <div id="debugInfo"></div>
        </div>

        <!-- Log des actions -->
        <div class="debug-info">
            <div class="debug-title">📝 Log des Actions</div>
            <div id="actionLog"></div>
        </div>
    </div>

    <!-- Inclure le modal -->
    <?php include 'components/quick-actions.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales pour le debug
        let debugInfo = document.getElementById('debugInfo');
        let actionLog = document.getElementById('actionLog');
        let logCounter = 0;

        // Fonction pour logger les actions
        function logAction(message, type = 'info') {
            logCounter++;
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'debug-error' : 
                            type === 'warning' ? 'debug-warning' : 
                            type === 'success' ? 'debug-success' : '';
            
            actionLog.innerHTML += `<div class="debug-item ${className}">[${timestamp}] ${logCounter}. ${message}</div>`;
            actionLog.scrollTop = actionLog.scrollHeight;
            console.log(`[${timestamp}] ${message}`);
        }

        // Fonction pour vérifier l'état des éléments
        function checkElements() {
            const elements = {
                'searchInput': document.getElementById('searchInput'),
                'btnSearch': document.getElementById('btnSearch'),
                'clientsResults': document.getElementById('clientsResults'),
                'reparationsResults': document.getElementById('reparationsResults'),
                'commandesResults': document.getElementById('commandesResults'),
                'count-clients': document.getElementById('count-clients'),
                'count-reparations': document.getElementById('count-reparations'),
                'count-commandes': document.getElementById('count-commandes')
            };

            debugInfo.innerHTML = '';
            
            for (const [name, element] of Object.entries(elements)) {
                if (element) {
                    const display = window.getComputedStyle(element).display;
                    const visibility = window.getComputedStyle(element).visibility;
                    const opacity = window.getComputedStyle(element).opacity;
                    
                    debugInfo.innerHTML += `
                        <div class="debug-item debug-success">
                            ✅ ${name}: OK (display: ${display}, visibility: ${visibility}, opacity: ${opacity})
                        </div>
                    `;
                } else {
                    debugInfo.innerHTML += `
                        <div class="debug-item debug-error">
                            ❌ ${name}: MANQUANT
                        </div>
                    `;
                }
            }
        }

        // Fonction de recherche pour test
        function performSearch(searchTerm) {
            logAction(`🔍 Début de la recherche pour: "${searchTerm}"`);
            
            // Vérifier les éléments cibles
            const clientsResults = document.getElementById('clientsResults');
            const reparationsResults = document.getElementById('reparationsResults');
            const commandesResults = document.getElementById('commandesResults');
            
            if (!clientsResults || !reparationsResults || !commandesResults) {
                logAction('❌ Éléments cibles manquants pour l\'injection', 'error');
                return;
            }

            // Test d'injection directe
            const testHTML = `
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Injection directe</td>
                            <td><span class="badge bg-success">Réussi</span></td>
                        </tr>
                    </tbody>
                </table>
            `;

            logAction('🔄 Injection du HTML de test...');
            clientsResults.innerHTML = testHTML;
            
            // Vérifier l'injection
            if (clientsResults.innerHTML.includes('Injection directe')) {
                logAction('✅ Injection réussie dans clientsResults', 'success');
            } else {
                logAction('❌ Échec de l\'injection dans clientsResults', 'error');
            }

            // Mettre à jour le compteur
            const countClients = document.getElementById('count-clients');
            if (countClients) {
                countClients.textContent = '1';
                logAction('✅ Compteur clients mis à jour', 'success');
            }

            // Simuler les vraies données
            fetch('ajax/recherche-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `terme=${encodeURIComponent(searchTerm)}`
            })
            .then(response => response.json())
            .then(data => {
                logAction(`📋 Réponse API reçue: ${data.success ? 'SUCCESS' : 'ERREUR'}`, data.success ? 'success' : 'error');
                
                if (data.success && data.resultats && data.resultats.length > 0) {
                    logAction(`📊 ${data.resultats.length} résultats trouvés`, 'success');
                    
                    // Grouper les résultats
                    const groupes = {
                        clients: data.resultats.filter(r => r.type === 'client'),
                        reparations: data.resultats.filter(r => r.type === 'reparation'),
                        commandes: data.resultats.filter(r => r.type === 'commande')
                    };
                    
                    logAction(`📈 Groupes: ${groupes.clients.length} clients, ${groupes.reparations.length} réparations, ${groupes.commandes.length} commandes`);
                    
                    // Afficher les clients
                    if (groupes.clients.length > 0) {
                        const clientsHTML = generateClientsHTML(groupes.clients);
                        clientsResults.innerHTML = clientsHTML;
                        logAction('✅ HTML clients injecté', 'success');
                    }
                    
                    // Afficher les réparations
                    if (groupes.reparations.length > 0) {
                        const reparationsHTML = generateReparationsHTML(groupes.reparations);
                        reparationsResults.innerHTML = reparationsHTML;
                        logAction('✅ HTML réparations injecté', 'success');
                    }
                    
                } else {
                    logAction('⚠️ Pas de résultats trouvés', 'warning');
                }
            })
            .catch(error => {
                logAction(`❌ Erreur API: ${error.message}`, 'error');
            });
        }

        // Fonction pour générer le HTML des clients
        function generateClientsHTML(clients) {
            let html = '<table class="table table-striped table-hover">';
            html += '<thead><tr><th>Nom</th><th>Téléphone</th><th>Actions</th></tr></thead>';
            html += '<tbody>';
            
            clients.forEach(client => {
                html += `
                    <tr>
                        <td>${client.nom || ''} ${client.prenom || ''}</td>
                        <td>${client.telephone || ''}</td>
                        <td>
                            <a href="index.php?page=fiche_client&id=${client.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            return html;
        }

        // Fonction pour générer le HTML des réparations
        function generateReparationsHTML(reparations) {
            let html = '<table class="table table-striped table-hover">';
            html += '<thead><tr><th>Appareil</th><th>Modèle</th><th>Problème</th><th>Statut</th><th>Actions</th></tr></thead>';
            html += '<tbody>';
            
            reparations.forEach(reparation => {
                html += `
                    <tr>
                        <td>${reparation.type_appareil || ''}</td>
                        <td>${reparation.modele || ''}</td>
                        <td>${reparation.description_probleme || ''}</td>
                        <td><span class="badge bg-primary">${reparation.statut || ''}</span></td>
                        <td>
                            <a href="index.php?page=fiche_reparation&id=${reparation.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            return html;
        }

        // Événements
        document.addEventListener('DOMContentLoaded', function() {
            logAction('🚀 Page chargée, initialisation...');
            
            // Vérifier les éléments au chargement
            setTimeout(checkElements, 100);
            
            // Événement sur l'ouverture du modal
            document.getElementById('rechercheModal').addEventListener('shown.bs.modal', function() {
                logAction('📱 Modal ouvert, vérification des éléments...');
                checkElements();
                
                // Ajouter l'événement de recherche
                const btnSearch = document.getElementById('btnSearch');
                const searchInput = document.getElementById('searchInput');
                
                if (btnSearch) {
                    btnSearch.addEventListener('click', function() {
                        const searchTerm = searchInput.value.trim();
                        if (searchTerm) {
                            performSearch(searchTerm);
                        }
                    });
                    logAction('✅ Événement de recherche ajouté', 'success');
                }
                
                if (searchInput) {
                    searchInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            const searchTerm = searchInput.value.trim();
                            if (searchTerm) {
                                performSearch(searchTerm);
                            }
                        }
                    });
                    logAction('✅ Événement Enter ajouté', 'success');
                }
            });
        });

        // Test automatique après 2 secondes
        setTimeout(function() {
            logAction('🔄 Test automatique dans 2 secondes...');
            // Déclencher l'ouverture du modal
            const modalButton = document.querySelector('[data-bs-target="#rechercheModal"]');
            if (modalButton) {
                modalButton.click();
                
                // Attendre que le modal soit ouvert et faire un test
                setTimeout(function() {
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.value = 'iu';
                        performSearch('iu');
                    }
                }, 1000);
            }
        }, 2000);
    </script>
</body>
</html> 