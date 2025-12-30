<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Injection Direct</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test d'Injection Direct - Recherche</h2>
        <div class="alert alert-info">
            <strong>Instructions:</strong> Cliquez sur "Tester Recherche" et regardez la console
        </div>
        
        <button class="btn btn-primary btn-lg" onclick="testerRecherche()">Tester Recherche</button>
        <button class="btn btn-success btn-lg" onclick="testerAPI()">Tester API</button>
        
        <div class="mt-4" id="results"></div>
    </div>

    <!-- Modal de test intégré -->
    <div class="modal fade" id="testModal" tabindex="-1" style="display: block; position: static;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Modal Recherche</h5>
                </div>
                <div class="modal-body">
                    <!-- Onglets de résultats -->
                    <ul class="nav nav-tabs mb-3" id="searchTabs">
                        <li class="nav-item">
                            <button class="nav-link active" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients-container">
                                <i class="fas fa-users me-2"></i>
                                Clients
                                <span class="badge bg-primary ms-2" id="count-clients">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="reparations-tab" data-bs-toggle="tab" data-bs-target="#reparations-container">
                                <i class="fas fa-tools me-2"></i>
                                Réparations
                                <span class="badge bg-primary ms-2" id="count-reparations">0</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Contenu des onglets -->
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="clients-container">
                            <div class="modern-search-results">
                                <div class="results-header">
                                    <h6 class="results-title">
                                        <i class="fas fa-users me-2"></i>
                                        Clients trouvés
                                    </h6>
                                </div>
                                <div class="modern-table-container">
                                    <div id="clientsResults" class="table-responsive">
                                        <!-- Les résultats seront injectés ici -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="reparations-container">
                            <div class="modern-search-results">
                                <div class="results-header">
                                    <h6 class="results-title">
                                        <i class="fas fa-tools me-2"></i>
                                        Réparations trouvées
                                    </h6>
                                </div>
                                <div class="modern-table-container">
                                    <div id="reparationsResults" class="table-responsive">
                                        <!-- Les résultats seront injectés ici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testerRecherche() {
            console.log('🔧 TEST DE RECHERCHE FORCÉ');
            
            // Simuler des données
            const resultats = [
                {
                    type: 'client',
                    id: 1,
                    nom: 'Test Client',
                    telephone: '0123456789'
                },
                {
                    type: 'reparation',
                    id: 1,
                    client: 'Test Client',
                    appareil: 'Trottinette Test',
                    probleme: 'Test problème',
                    statut: 'en_cours'
                }
            ];
            
            console.log('🎯 Appel de displayResults avec:', resultats);
            displayResults(resultats);
        }
        
        function testerAPI() {
            console.log('🔧 TEST API DIRECT');
            
            fetch('ajax/recherche-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'terme=iu'
            })
            .then(response => response.json())
            .then(data => {
                console.log('📋 Réponse API:', data);
                document.getElementById('results').innerHTML = 
                    '<div class="alert alert-success">API: ' + JSON.stringify(data, null, 2) + '</div>';
                
                if (data.success && data.resultats) {
                    console.log('🎯 Appel de displayResults avec les vraies données');
                    displayResults(data.resultats);
                }
            })
            .catch(error => {
                console.error('❌ Erreur API:', error);
                document.getElementById('results').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + error + '</div>';
            });
        }

        // Fonction displayResults inline pour tester
        function displayResults(resultats) {
            console.log('🎯 DISPLAY RESULTS APPELÉE avec:', resultats);
            
            // Grouper les résultats par type
            const groupes = {
                clients: [],
                reparations: [],
                commandes: []
            };
            
            resultats.forEach(item => {
                if (item.type === 'client') {
                    groupes.clients.push(item);
                } else if (item.type === 'reparation') {
                    groupes.reparations.push(item);
                } else if (item.type === 'commande') {
                    groupes.commandes.push(item);
                }
            });
            
            console.log('📊 Groupes créés:', groupes);
            
            // Afficher les clients
            displayClients(groupes.clients);
            
            // Afficher les réparations
            displayReparations(groupes.reparations);
            
            // Activer le premier onglet non vide
            activateFirstNonEmptyTab(groupes);
        }
        
        function displayClients(clients) {
            console.log('🔧 displayClients appelée avec:', clients);
            
            const clientsTableBody = document.getElementById('clientsResults');
            const clientsCount = document.getElementById('count-clients');
            
            console.log('🔧 clientsTableBody element:', clientsTableBody);
            
            let html = '';
            
            if (clients.length > 0) {
                html = `
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Nom</th>
                                <th>Téléphone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                clients.forEach(client => {
                    html += `
                        <tr>
                            <td><strong>${client.nom}</strong></td>
                            <td>${client.telephone}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
            } else {
                html = '<div class="text-center py-4 text-muted">Aucun client trouvé</div>';
            }
            
            console.log('🔧 HTML généré pour clients:', html.length, 'caractères');
            
            if (clientsTableBody) {
                clientsTableBody.innerHTML = html;
                console.log('✅ HTML injecté dans clientsTableBody');
                console.log('🔧 Parent display:', getComputedStyle(clientsTableBody.parentElement).display);
                console.log('🔧 Element display:', getComputedStyle(clientsTableBody).display);
            } else {
                console.error('❌ clientsTableBody non trouvé');
            }
            
            if (clientsCount) {
                clientsCount.textContent = clients.length;
                clientsCount.className = clients.length > 0 ? 'badge bg-success ms-2' : 'badge bg-primary ms-2';
                console.log('✅ Compteur clients mis à jour:', clients.length);
            }
        }
        
        function displayReparations(reparations) {
            console.log('🔧 displayReparations appelée avec:', reparations);
            
            const reparationsTableBody = document.getElementById('reparationsResults');
            const reparationsCount = document.getElementById('count-reparations');
            
            let html = '';
            
            if (reparations.length > 0) {
                html = `
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Client</th>
                                <th>Appareil</th>
                                <th>Problème</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                reparations.forEach(reparation => {
                    html += `
                        <tr>
                            <td>${reparation.client}</td>
                            <td>${reparation.appareil}</td>
                            <td>${reparation.probleme}</td>
                            <td><span class="badge bg-warning">${reparation.statut}</span></td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
            } else {
                html = '<div class="text-center py-4 text-muted">Aucune réparation trouvée</div>';
            }
            
            if (reparationsTableBody) {
                reparationsTableBody.innerHTML = html;
                console.log('✅ HTML injecté dans reparationsTableBody');
            }
            
            if (reparationsCount) {
                reparationsCount.textContent = reparations.length;
                reparationsCount.className = reparations.length > 0 ? 'badge bg-success ms-2' : 'badge bg-primary ms-2';
            }
        }
        
        function activateFirstNonEmptyTab(groupes) {
            console.log('🎯 Activation des onglets:', groupes);
            
            // Réinitialiser tous les onglets
            document.querySelectorAll('#searchTabs .nav-link').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Activer le premier onglet qui a des résultats
            if (groupes.clients.length > 0) {
                const clientsTab = document.getElementById('clients-tab');
                const clientsPane = document.getElementById('clients-container');
                if (clientsTab) clientsTab.classList.add('active');
                if (clientsPane) clientsPane.classList.add('show', 'active');
                console.log('✅ Onglet clients activé');
            } else if (groupes.reparations.length > 0) {
                const reparationsTab = document.getElementById('reparations-tab');
                const reparationsPane = document.getElementById('reparations-container');
                if (reparationsTab) reparationsTab.classList.add('active');
                if (reparationsPane) reparationsPane.classList.add('show', 'active');
                console.log('✅ Onglet réparations activé');
            }
        }
    </script>
</body>
</html> 