<?php
/**
 * Script de diagnostic pour identifier le décalage des colonnes dans les tableaux
 */

// Démarrer la session
session_start();

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Diagnostic des colonnes de tableaux</h1>";

// Test de chargement des configurations
try {
    if (file_exists(__DIR__ . '/config/subdomain_config.php')) {
        require_once __DIR__ . '/config/subdomain_config.php';
        echo "<p style='color: green;'>✅ subdomain_config.php chargé</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ subdomain_config.php non trouvé</p>";
    }
    
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        echo "<p style='color: green;'>✅ database.php chargé</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ database.php non trouvé</p>";
    }
    
    echo "<p><strong>Shop ID:</strong> " . ($_SESSION['shop_id'] ?? 'Non défini') . "</p>";
    echo "<p><strong>Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de configuration: " . $e->getMessage() . "</p>";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Tableaux</title>
    
    <!-- Bootstrap CSS - Version exacte utilisée dans votre app -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS de votre application dans l'ordre exact -->
    <link href="assets/css/professional-desktop.css" rel="stylesheet">
    <link href="assets/css/modern-effects.css" rel="stylesheet">
    <link href="assets/css/tablet-friendly.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/mobile-navigation.css" rel="stylesheet">
    <link href="assets/css/status-colors.css" rel="stylesheet">
    <link href="assets/css/bug-reporter.css" rel="stylesheet">
    <link href="assets/css/rachat-styles.css" rel="stylesheet">
    <link href="assets/css/pwa-enhancements.css" rel="stylesheet">
    <link href="assets/css/ipad-header-fix.css" rel="stylesheet">
    <link href="assets/css/ipad-pwa-fix.css" rel="stylesheet">
    <link href="assets/css/ipad-statusbar-fix.css" rel="stylesheet">
    <link href="assets/css/neo-dock.css" rel="stylesheet">
    
    <style>
    body {
        padding: 20px;
        background: #f8f9fa;
        font-family: 'Inter', sans-serif;
    }
    
    .diagnostic-card {
        background: white;
        margin: 20px 0;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
    }
    
    .highlight-problem {
        border: 3px solid #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.05) !important;
    }
    
    .highlight-ok {
        border: 3px solid #28a745 !important;
        background-color: rgba(40, 167, 69, 0.05) !important;
    }
    
    .debug-output {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        white-space: pre-wrap;
    }
    
    .btn-diagnostic {
        margin: 5px;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .comparison-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 20px 0;
    }
    
    @media (max-width: 768px) {
        .comparison-container {
            grid-template-columns: 1fr;
        }
    }
    
    .table-test {
        margin: 10px 0;
    }
    
    .result-success {
        background: #d1e7dd;
        border: 1px solid #badbcc;
        color: #0f5132;
        padding: 12px;
        border-radius: 8px;
        margin: 10px 0;
    }
    
    .result-error {
        background: #f8d7da;
        border: 1px solid #f5c2c7;
        color: #842029;
        padding: 12px;
        border-radius: 8px;
        margin: 10px 0;
    }
    
    .result-warning {
        background: #fff3cd;
        border: 1px solid #ffecb5;
        color: #664d03;
        padding: 12px;
        border-radius: 8px;
        margin: 10px 0;
    }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="diagnostic-card">
        <h2>🛠️ Panel de contrôle</h2>
        <div class="d-flex flex-wrap">
            <button class="btn btn-primary btn-diagnostic" onclick="analyzeAll()">
                <i class="fas fa-search"></i> Analyser tout
            </button>
            <button class="btn btn-warning btn-diagnostic" onclick="compareStructures()">
                <i class="fas fa-columns"></i> Comparer structures
            </button>
            <button class="btn btn-success btn-diagnostic" onclick="testCSS()">
                <i class="fas fa-code"></i> Tester CSS
            </button>
            <button class="btn btn-info btn-diagnostic" onclick="showDetails()">
                <i class="fas fa-info-circle"></i> Détails techniques
            </button>
        </div>
        
        <div id="main-output" class="debug-output mt-3">
            Cliquez sur "Analyser tout" pour commencer le diagnostic...
        </div>
    </div>
    
    <!-- Test 1: Tableau EXACT comme dans rachat_appareils.php -->
    <div class="diagnostic-card">
        <h3>📋 Test 1: Tableau rachats (identique à votre page)</h3>
        
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle w-100 border-light shadow-sm rounded overflow-hidden" id="test-rachat-table">
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
                <tbody id="rachat-tbody">
                    <!-- Sera généré par JavaScript comme dans votre page -->
                </tbody>
            </table>
        </div>
        
        <div id="test1-result" class="debug-output">En attente d'analyse...</div>
    </div>
    
    <!-- Test 2: Tableau Bootstrap pur -->
    <div class="diagnostic-card">
        <h3>🧪 Test 2: Tableau Bootstrap pur (référence)</h3>
        
        <table class="table" id="reference-table">
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
        
        <div id="test2-result" class="debug-output">En attente d'analyse...</div>
    </div>
    
    <!-- Données réelles -->
    <?php
    if (function_exists('getShopDBConnection')) {
        echo "<div class='diagnostic-card'>";
        echo "<h3>💾 Test 3: Données réelles de la base</h3>";
        
        try {
            $pdo = getShopDBConnection();
            if ($pdo !== null) {
                $stmt = $pdo->prepare("SELECT 
                        r.id, r.type_appareil, r.date_rachat, r.modele, r.sin, r.fonctionnel, r.prix,
                        c.nom, c.prenom
                    FROM rachat_appareils r
                    JOIN clients c ON r.client_id = c.id
                    ORDER BY r.date_rachat DESC
                    LIMIT 2");
                    
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($results) > 0) {
                    echo "<div class='table-responsive d-none d-md-block'>";
                    echo "<table class='table table-hover align-middle w-100 border-light shadow-sm rounded overflow-hidden' id='real-table'>";
                    echo "<thead class='bg-light'>";
                    echo "<tr>";
                    echo "<th>Date</th>";
                    echo "<th>Client</th>";
                    echo "<th>Modèle</th>";
                    echo "<th>SIN</th>";
                    echo "<th>État</th>";
                    echo "<th>Photos</th>";
                    echo "<th class='text-end'>Prix</th>";
                    echo "<th class='text-end'>Action</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    
                    foreach ($results as $rachat) {
                        $date = new DateTime($rachat['date_rachat']);
                        $formattedDate = $date->format('d/m/Y');
                        $stateBadge = $rachat['fonctionnel'] ? 
                            '<span class="badge bg-success">Fonctionnel</span>' : 
                            '<span class="badge bg-danger">Non fonctionnel</span>';
                        $prix = $rachat['prix'] ? 
                            number_format($rachat['prix'], 2) . ' €' : 'N/A';
                        
                        echo "<tr>";
                        echo "<td>{$formattedDate}</td>";
                        echo "<td>{$rachat['prenom']} {$rachat['nom']}</td>";
                        echo "<td>" . ($rachat['modele'] ?: $rachat['type_appareil']) . "</td>";
                        echo "<td>" . ($rachat['sin'] ?: 'N/A') . "</td>";
                        echo "<td>{$stateBadge}</td>";
                        echo "<td>Photos</td>";
                        echo "<td class='text-end fw-bold'>{$prix}</td>";
                        echo "<td class='text-end'>Actions</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody>";
                    echo "</table>";
                    echo "</div>";
                } else {
                    echo "<p class='text-warning'>Aucune donnée trouvée</p>";
                }
            } else {
                echo "<p class='text-danger'>Connexion base échouée</p>";
            }
        } catch (Exception $e) {
            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
        }
        
        echo "<div id='test3-result' class='debug-output'>En attente d'analyse...</div>";
        echo "</div>";
    }
    ?>
</div>

<script>
console.log('🚀 Diagnostic des colonnes initialisé');

// Simuler le contenu généré par AJAX comme dans votre page
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('rachat-tbody');
    if (tbody) {
        tbody.innerHTML = `
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
        `;
    }
    
    // Auto-diagnostic après chargement
    setTimeout(() => {
        analyzeAll();
    }, 1000);
});

function analyzeAll() {
    console.log('🔍 Analyse complète en cours...');
    
    const output = document.getElementById('main-output');
    output.textContent = '📊 Analyse en cours...\n\n';
    
    const tables = [
        { id: 'test-rachat-table', name: 'Tableau Rachats (avec CSS complets)' },
        { id: 'reference-table', name: 'Tableau Bootstrap (référence)' },
        { id: 'real-table', name: 'Tableau Données Réelles' }
    ];
    
    let results = '📊 RÉSULTATS DE L\'ANALYSE :\n\n';
    let problemFound = false;
    
    tables.forEach(tableInfo => {
        const table = document.getElementById(tableInfo.id);
        if (table) {
            const analysis = analyzeTable(table);
            
            results += `${tableInfo.name} :\n`;
            results += `  - En-têtes : ${analysis.headerCount}\n`;
            results += `  - Cellules : ${analysis.cellCount}\n`;
            results += `  - Alignement : ${analysis.aligned ? '✅ OK' : '❌ DÉCALÉ'}\n`;
            
            if (analysis.issues.length > 0) {
                results += `  - Problèmes :\n`;
                analysis.issues.forEach(issue => {
                    results += `    • ${issue}\n`;
                });
                problemFound = true;
                table.classList.add('highlight-problem');
            } else {
                table.classList.add('highlight-ok');
            }
            
            results += '\n';
        }
    });
    
    if (problemFound) {
        results += '🚨 PROBLÈME DÉTECTÉ !\n';
        results += 'Le décalage est présent dans les tableaux avec CSS complets.\n';
        results += 'Cliquez sur "Tester CSS" pour identifier le CSS responsable.\n';
    } else {
        results += '✅ Aucun problème détecté dans ce test.\n';
        results += 'Le problème pourrait être spécifique à certaines conditions.\n';
    }
    
    output.textContent = results;
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
    
    // Vérifier alignement basique
    if (analysis.headerCount !== analysis.cellCount) {
        analysis.aligned = false;
        analysis.issues.push(`Mismatch: ${analysis.headerCount} en-têtes vs ${analysis.cellCount} cellules`);
    }
    
    // Vérifier alignement visuel
    if (headerRow && firstDataRow) {
        const headerCells = Array.from(headerRow.querySelectorAll('th'));
        const dataCells = Array.from(firstDataRow.querySelectorAll('td'));
        
        let maxChecks = Math.min(headerCells.length, dataCells.length);
        
        for (let i = 0; i < maxChecks; i++) {
            const headerRect = headerCells[i].getBoundingClientRect();
            const dataRect = dataCells[i].getBoundingClientRect();
            
            const diff = Math.abs(headerRect.left - dataRect.left);
            
            if (diff > 3) {
                analysis.aligned = false;
                analysis.issues.push(`Colonne ${i + 1} décalée de ${Math.round(diff)}px`);
            }
        }
    }
    
    return analysis;
}

function compareStructures() {
    console.log('📋 Comparaison des structures...');
    
    const output = document.getElementById('main-output');
    let result = '📋 COMPARAISON DES STRUCTURES :\n\n';
    
    const tables = document.querySelectorAll('table');
    tables.forEach((table, index) => {
        const id = table.id || `table-${index + 1}`;
        const headers = table.querySelectorAll('thead th');
        const firstRow = table.querySelector('tbody tr:first-child');
        const cells = firstRow ? firstRow.querySelectorAll('td') : [];
        
        result += `${id} :\n`;
        result += `  Headers (${headers.length}): `;
        headers.forEach((th, i) => {
            result += `"${th.textContent.trim()}"${i < headers.length - 1 ? ', ' : ''}`;
        });
        result += '\n';
        
        result += `  Cells (${cells.length}): `;
        cells.forEach((td, i) => {
            result += `"${td.textContent.trim()}"${i < cells.length - 1 ? ', ' : ''}`;
        });
        result += '\n';
        
        result += `  Classes: ${table.className}\n\n`;
    });
    
    output.textContent = result;
}

function testCSS() {
    console.log('🎨 Test de correction CSS...');
    
    // Appliquer une correction temporaire
    const style = document.createElement('style');
    style.id = 'temp-fix';
    style.textContent = `
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
        
        .table th::before,
        .table td::before,
        .table th::after,
        .table td::after {
            display: none !important;
        }
    `;
    
    document.head.appendChild(style);
    
    const output = document.getElementById('main-output');
    output.textContent = '🎨 Correction CSS temporaire appliquée !\n\n';
    output.textContent += 'Relancez "Analyser tout" pour voir si le problème est résolu.\n';
    output.textContent += 'Si ça fonctionne, nous ajouterons cette correction à vos fichiers CSS.\n';
}

function showDetails() {
    console.log('ℹ️ Affichage des détails...');
    
    const table = document.getElementById('test-rachat-table');
    if (!table) return;
    
    const output = document.getElementById('main-output');
    const computedStyle = window.getComputedStyle(table);
    
    let details = 'ℹ️ DÉTAILS TECHNIQUES DU TABLEAU :\n\n';
    details += `Display: ${computedStyle.display}\n`;
    details += `Table-layout: ${computedStyle.tableLayout}\n`;
    details += `Border-collapse: ${computedStyle.borderCollapse}\n`;
    details += `Border-spacing: ${computedStyle.borderSpacing}\n`;
    details += `Width: ${computedStyle.width}\n`;
    details += `Position: ${computedStyle.position}\n`;
    details += `Box-sizing: ${computedStyle.boxSizing}\n\n`;
    
    const firstTh = table.querySelector('th:first-child');
    const firstTd = table.querySelector('td:first-child');
    
    if (firstTh && firstTd) {
        const thRect = firstTh.getBoundingClientRect();
        const tdRect = firstTd.getBoundingClientRect();
        
        details += 'POSITIONNEMENT DES CELLULES :\n';
        details += `Premier TH: left=${Math.round(thRect.left)}px, width=${Math.round(thRect.width)}px\n`;
        details += `Premier TD: left=${Math.round(tdRect.left)}px, width=${Math.round(tdRect.width)}px\n`;
        details += `Décalage: ${Math.round(Math.abs(thRect.left - tdRect.left))}px\n`;
    }
    
    output.textContent = details;
}

</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 