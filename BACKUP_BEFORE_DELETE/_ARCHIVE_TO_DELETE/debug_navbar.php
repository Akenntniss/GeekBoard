<?php
/**
 * Debug de la navbar et du bouton de pointage
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

// Initialiser la session magasin
initializeShopSession();

echo "<h1>🔍 Debug Navbar & Pointage</h1>";

echo "<h2>📋 Session Utilisateur</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🔗 Test API Status</h2>";
$api_url = "https://mdg.servo.tools/ajax/get_timetracking_status.php";
echo "<p><a href='$api_url' target='_blank'>Tester l'API de statut</a></p>";

echo "<h2>⏰ État Actuel du Pointage</h2>";
try {
    $pdo = getShopDBConnection();
    if ($pdo && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, status, clock_in, clock_out FROM time_tracking WHERE user_id = ? ORDER BY id DESC LIMIT 3");
        $stmt->execute([$_SESSION['user_id']]);
        $pointages = $stmt->fetchAll();
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Status</th><th>Entrée</th><th>Sortie</th></tr>";
        foreach ($pointages as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td><strong>{$p['status']}</strong></td>";
            echo "<td>{$p['clock_in']}</td>";
            echo "<td>" . ($p['clock_out'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}

echo "<h2>🔍 Test JavaScript</h2>";
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DEBUG NAVBAR ===');
    
    // Vérifier si l'élément existe
    const dynamicBtn = document.getElementById('dynamic-timetracking-button-circular');
    console.log('Élément dynamic-timetracking-button-circular:', dynamicBtn);
    
    if (dynamicBtn) {
        console.log('✅ Bouton trouvé !');
        console.log('Contenu actuel:', dynamicBtn.innerHTML);
        console.log('Dataset clockedIn:', dynamicBtn.dataset.clockedIn);
        
        const textElement = dynamicBtn.querySelector('.links__text');
        const clockTextElement = dynamicBtn.querySelector('.clock-text');
        
        console.log('Élément text:', textElement);
        console.log('Élément clock-text:', clockTextElement);
        
        if (textElement) {
            console.log('Texte actuel:', textElement.textContent);
        }
        
    } else {
        console.log('❌ Bouton NON trouvé !');
        console.log('Éléments avec "timetracking" dans l\'ID:');
        const allElements = document.querySelectorAll('[id*="timetracking"], [class*="timetracking"]');
        allElements.forEach(el => {
            console.log('- Élément trouvé:', el.id, el.className, el);
        });
    }
    
    // Tester l'API manuellement
    console.log('=== TEST API ===');
    fetch('ajax/get_timetracking_status.php', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Réponse API:', data);
        
        // Afficher les infos importantes
        document.getElementById('api-result').innerHTML = `
            <h3>Résultat API :</h3>
            <p><strong>Success:</strong> ${data.success}</p>
            <p><strong>Is Clocked In:</strong> ${data.is_clocked_in}</p>
            <p><strong>Debug User ID:</strong> ${data.debug_user_id}</p>
            <p><strong>Status:</strong> ${data.status}</p>
        `;
    })
    .catch(error => {
        console.error('❌ Erreur API:', error);
        document.getElementById('api-result').innerHTML = `<p style="color: red;">Erreur API: ${error.message}</p>`;
    });
});
</script>

<div id="api-result">
    <p>Chargement du test API...</p>
</div>

<h2>🔧 Actions de Test</h2>
<button onclick="updateTimeTrackingButtonCircular()" style="padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px;">
    Forcer la mise à jour du bouton
</button>

<script>
// Copie de la fonction pour test
function updateTimeTrackingButtonCircular() {
    console.log('=== FORCE UPDATE ===');
    const dynamicBtn = document.getElementById('dynamic-timetracking-button-circular');
    if (!dynamicBtn) {
        console.log('❌ Bouton non trouvé lors de la mise à jour');
        return;
    }
    
    console.log('🔄 Mise à jour du bouton...');
    
    fetch('ajax/get_timetracking_status.php', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('📊 Données reçues:', data);
        
        const textElement = dynamicBtn.querySelector('.links__text');
        const clockTextElement = dynamicBtn.querySelector('.clock-text');
        
        if (data.success) {
            if (data.is_clocked_in) {
                console.log('🟢 Utilisateur pointé IN - proposer OUT');
                if (textElement) textElement.textContent = 'Pointer OUT';
                if (clockTextElement) clockTextElement.textContent = 'OUT';
                dynamicBtn.dataset.clockedIn = 'true';
            } else {
                console.log('🔴 Utilisateur pas pointé - proposer IN');
                if (textElement) textElement.textContent = 'Pointer IN';
                if (clockTextElement) clockTextElement.textContent = 'IN';
                dynamicBtn.dataset.clockedIn = 'false';
            }
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la mise à jour:', error);
    });
}
</script>
