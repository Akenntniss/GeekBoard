<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Colonnes Simplifié</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
    body {
        padding: 20px;
        background: #f8f9fa;
    }
    
    .test-section {
        background: white;
        margin: 20px 0;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .highlight-problem {
        border: 3px solid #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.05) !important;
    }
    
    .highlight-ok {
        border: 3px solid #28a745 !important;
        background-color: rgba(40, 167, 69, 0.05) !important;
    }
    
    .results {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        font-family: monospace;
        white-space: pre-wrap;
    }
    
    .btn-test {
        margin: 5px;
        padding: 10px 20px;
    }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Diagnostic Simplifié des Colonnes</h1>
    
    <div class="test-section">
        <h2>🛠️ Contrôles de diagnostic</h2>
        <button class="btn btn-primary btn-test" onclick="analyzeAll()">
            <i class="fas fa-search"></i> Analyser
        </button>
        <button class="btn btn-success btn-test" onclick="applyCSSFix()">
            <i class="fas fa-wrench"></i> Corriger CSS
        </button>
        <button class="btn btn-info btn-test" onclick="resetAll()">
            <i class="fas fa-undo"></i> Reset
        </button>
        
        <div id="results" class="results">
            Cliquez sur "Analyser" pour commencer...
        </div>
    </div>
    
    <!-- Test 1: Sans CSS personnalisés -->
    <div class="test-section">
        <h3>🧪 Test Bootstrap Pur</h3>
        <table class="table table-striped" id="test-basic">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Modèle</th>
                    <th>SIN</th>
                    <th>État</th>
                    <th>Photos</th>
                    <th>Prix</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>25/01/2025</td>
                    <td>Test Client</td>
                    <td>iPhone 12</td>
                    <td>ABC123</td>
                    <td><span class="badge bg-success">OK</span></td>
                    <td>Photos</td>
                    <td>100€</td>
                    <td>Actions</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Test 2: Avec les classes exactes de votre app -->
    <div class="test-section">
        <h3>📋 Test Classes Exactes</h3>
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle w-100 border-light shadow-sm rounded overflow-hidden" id="test-exact">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Modèle</th>
                        <th>SIN</th>
                        <th>État</th>
                        <th>Photos</th>
                        <th class="text-end">Prix</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>25/01/2025</td>
                        <td>Client Test</td>
                        <td>iPhone 13</td>
                        <td>DEF456</td>
                        <td><span class="badge bg-success">Fonctionnel</span></td>
                        <td>Photos</td>
                        <td class="text-end fw-bold">120,00 €</td>
                        <td class="text-end">Actions</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Test 3: Avec tous vos CSS -->
    <div class="test-section">
        <h3>🎨 Test Avec Tous les CSS</h3>
        <div id="css-loader">
            <button class="btn btn-warning" onclick="loadAllCSS()">
                <i class="fas fa-download"></i> Charger tous les CSS
            </button>
        </div>
        
        <div class="table-responsive d-none d-md-block" style="display: none;" id="test-with-css-container">
            <table class="table table-hover align-middle w-100 border-light shadow-sm rounded overflow-hidden" id="test-with-css">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Modèle</th>
                        <th>SIN</th>
                        <th>État</th>
                        <th>Photos</th>
                        <th class="text-end">Prix</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>26/01/2025</td>
                        <td>Client CSS</td>
                        <td>Samsung S21</td>
                        <td>GHI789</td>
                        <td><span class="badge bg-warning">Test</span></td>
                        <td>Photos</td>
                        <td class="text-end fw-bold">150,00 €</td>
                        <td class="text-end">Actions</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
console.log('🚀 Diagnostic simplifié initialisé');

function analyzeAll() {
    console.log('🔍 Analyse en cours...');
    
    const results = document.getElementById('results');
    let output = '📊 RÉSULTATS DE L\'ANALYSE :\n\n';
    
    const tables = [
        { id: 'test-basic', name: 'Bootstrap Pur' },
        { id: 'test-exact', name: 'Classes Exactes' },
        { id: 'test-with-css', name: 'Avec tous les CSS' }
    ];
    
    let problemsFound = 0;
    
    tables.forEach(tableInfo => {
        const table = document.getElementById(tableInfo.id);
        if (table && table.offsetParent !== null) {
            const analysis = analyzeTable(table);
            
            output += `${tableInfo.name} :\n`;
            output += `  - En-têtes : ${analysis.headerCount}\n`;
            output += `  - Cellules : ${analysis.cellCount}\n`;
            output += `  - Aligné : ${analysis.aligned ? '✅ OUI' : '❌ NON'}\n`;
            
            if (analysis.issues.length > 0) {
                output += `  - Problèmes :\n`;
                analysis.issues.forEach(issue => {
                    output += `    • ${issue}\n`;
                });
                problemsFound++;
                table.classList.add('highlight-problem');
            } else {
                table.classList.add('highlight-ok');
            }
            
            output += '\n';
        }
    });
    
    if (problemsFound > 0) {
        output += '🚨 PROBLÈMES DÉTECTÉS !\n';
        output += 'Cliquez sur "Corriger CSS" pour appliquer une correction.\n';
    } else {
        output += '✅ Tous les tableaux sont correctement alignés.\n';
        output += 'Le problème pourrait être dans les données dynamiques AJAX.\n';
    }
    
    results.textContent = output;
}

function analyzeTable(table) {
    const headerRow = table.querySelector('thead tr');
    const firstDataRow = table.querySelector('tbody tr:first-child');
    
    const analysis = {
        headerCount: 0,
        cellCount: 0,  
        aligned: true,
        issues: []
    };
    
    if (headerRow) {
        analysis.headerCount = headerRow.querySelectorAll('th').length;
    }
    
    if (firstDataRow) {
        analysis.cellCount = firstDataRow.querySelectorAll('td').length;
    }
    
    // Vérifier nombre de colonnes
    if (analysis.headerCount !== analysis.cellCount) {
        analysis.aligned = false;
        analysis.issues.push(`${analysis.headerCount} en-têtes vs ${analysis.cellCount} cellules`);
    }
    
    // Vérifier alignement visuel
    if (headerRow && firstDataRow) {
        const headerCells = Array.from(headerRow.querySelectorAll('th'));
        const dataCells = Array.from(firstDataRow.querySelectorAll('td'));
        
        for (let i = 0; i < Math.min(headerCells.length, dataCells.length); i++) {
            const headerRect = headerCells[i].getBoundingClientRect();
            const dataRect = dataCells[i].getBoundingClientRect();
            
            const diff = Math.abs(headerRect.left - dataRect.left);
            
            if (diff > 5) {
                analysis.aligned = false;
                analysis.issues.push(`Colonne ${i + 1} décalée de ${Math.round(diff)}px`);
            }
        }
    }
    
    return analysis;
}

function loadAllCSS() {
    console.log('📥 Chargement de tous les CSS...');
    
    const cssFiles = [
        'assets/css/professional-desktop.css',
        'assets/css/modern-effects.css',
        'assets/css/tablet-friendly.css',
        'assets/css/responsive.css',
        'assets/css/navbar.css',
        'assets/css/mobile-navigation.css',
        'assets/css/status-colors.css',
        'assets/css/rachat-styles.css'
    ];
    
    cssFiles.forEach(cssFile => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = cssFile;
        document.head.appendChild(link);
    });
    
    // Afficher le tableau de test
    document.getElementById('test-with-css-container').style.display = 'block';
    
    const results = document.getElementById('results');
    results.textContent = '📥 CSS chargés ! Relancez "Analyser" pour voir les différences.\n';
    
    setTimeout(() => {
        analyzeAll();
    }, 2000);
}

function applyCSSFix() {
    console.log('🔧 Application de la correction CSS...');
    
    const style = document.createElement('style');
    style.id = 'table-fix';
    style.textContent = `
        /* CORRECTION DU DÉCALAGE DES COLONNES */
        .table {
            table-layout: fixed !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }
        
        .table th,
        .table td {
            box-sizing: border-box !important;
            position: relative !important;
        }
        
        /* Supprimer tous les pseudo-éléments */
        .table th::before,
        .table td::before,
        .table th::after,
        .table td::after {
            display: none !important;
        }
    `;
    
    document.head.appendChild(style);
    
    const results = document.getElementById('results');
    results.textContent = '🔧 Correction CSS appliquée !\nRelancez "Analyser" pour voir si c\'est corrigé.\n';
}

function resetAll() {
    // Supprimer les highlights
    document.querySelectorAll('.highlight-problem, .highlight-ok').forEach(el => {
        el.classList.remove('highlight-problem', 'highlight-ok');
    });
    
    // Supprimer la correction CSS
    const fix = document.getElementById('table-fix');
    if (fix) fix.remove();
    
    const results = document.getElementById('results');
    results.textContent = 'Reset effectué. Cliquez sur "Analyser" pour recommencer.\n';
}

// Auto-analyse après chargement
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        analyzeAll();
    }, 1000);
});

</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 