<?php
// Assurons-nous d'utiliser la connexion à la base de données du magasin
$pdo = getShopDBConnection();

// Vérifier les droits d'admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'gerant'])) {
    die("Accès refusé - Réservé aux administrateurs");
}

$pageTitle = "Test Notifications Push";
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">🔔 Diagnostic Notifications Push</h4>
        </div>
    </div>
</div>

<div class="row">
    <!-- Colonne Gauche: Statut et Actions -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-search"></i> État du système</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Composant</th>
                            <th>Statut</th>
                        </tr>
                        <tr>
                            <td>Date / Heure Serveur</td>
                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                        </tr>
                        <tr>
                            <td>Utilisateur Connecté</td>
                            <td><?php echo $_SESSION['full_name'] ?? 'Inconnu'; ?> (ID: <?php echo $_SESSION['user_id']; ?>)</td>
                        </tr>
                        <tr>
                            <td>Base de données (Shop)</td>
                            <td>
                                <?php 
                                try {
                                    if ($pdo) {
                                        echo '<span class="badge bg-success">Connecté</span>';
                                        
                                        // Vérifier la table push_subscriptions
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM push_subscriptions");
                                        $count = $stmt->fetchColumn();
                                        echo " ($count abonnements totaux)";
                                    } else {
                                        echo '<span class="badge bg-danger">Erreur connexion</span>';
                                    }
                                } catch (Exception $e) {
                                    echo '<span class="badge bg-danger">' . htmlspecialchars($e->getMessage()) . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Vos abonnements</td>
                            <td>
                                <?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $myCount = $stmt->fetchColumn();
                                    
                                    if ($myCount > 0) {
                                        echo '<span class="badge bg-success">' . $myCount . ' actif(s)</span>';
                                    } else {
                                        echo '<span class="badge bg-warning text-dark">Aucun abonnement</span>';
                                    }
                                } catch (Exception $e) {
                                    echo '<span class="text-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Librairie WebPush</td>
                            <td>
                                <?php 
                                $autoload = __DIR__ . '/../vendor/autoload.php';
                                if (file_exists($autoload)) {
                                    require_once $autoload;
                                    if (class_exists('Minishlink\WebPush\WebPush')) {
                                        echo '<span class="badge bg-success">Installée ✓</span>';
                                    } else {
                                        echo '<span class="badge bg-warning">Autoloader présent mais classe non trouvée</span>';
                                    }
                                } else {
                                    echo '<span class="badge bg-danger">Absente (vendor/autoload.php manquant)</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="mt-4">
                    <h5>Actions de test</h5>
                    <div class="d-grid gap-2">
                        <button id="btn-subscribe" class="btn btn-primary">
                            <i class="fas fa-bell"></i> S'abonner aux notifications (Navigateur)
                        </button>
                        
                        <button id="btn-test-send" class="btn btn-warning">
                            <i class="fas fa-paper-plane"></i> M'envoyer une notification de test
                        </button>
                    </div>
                </div>
                
                <div class="mt-3" id="test-result"></div>
            </div>
        </div>
    </div>

    <!-- Colonne Droite: Logs -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0"><i class="fas fa-terminal"></i> Logs JavaScript</h5>
            </div>
            <div class="card-body bg-light text-dark font-monospace" style="height: 400px; overflow-y: auto;" id="console-log">
                <div class="text-muted small">// Les logs JS apparaîtront ici...</div>
            </div>
        </div>
    </div>
</div>

<script>
// Redirection des logs console vers la div d'affichage
(function() {
    var oldLog = console.log;
    var oldError = console.error;
    var logDiv = document.getElementById('console-log');

    function appendLog(message, type) {
        var div = document.createElement('div');
        div.className = type === 'error' ? 'text-danger border-bottom py-1' : 'text-success border-bottom py-1';
        div.textContent = '[' + new Date().toLocaleTimeString() + '] ' + message;
        logDiv.appendChild(div);
        logDiv.scrollTop = logDiv.scrollHeight;
    }

    console.log = function(message) {
        appendLog(message, 'info');
        oldLog.apply(console, arguments);
    };

    console.error = function(message) {
        appendLog(message, 'error');
        oldError.apply(console, arguments);
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    console.log("Page de test chargée");
    
    // Bouton d'abonnement
    document.getElementById('btn-subscribe').addEventListener('click', function() {
        console.log("Tentative d'abonnement...");
        if (window.PwaNotifications) {
            window.PwaNotifications.subscribe();
        } else {
            console.error("Erreur: window.PwaNotifications n'est pas défini. Vérifiez que pwa-notifications.js est chargé.");
        }
    });

    // Bouton de test d'envoi
    document.getElementById('btn-test-send').addEventListener('click', function() {
        console.log("Envoi d'une notification de test au serveur...");
        
        fetch('ajax/test_send_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: <?php echo $_SESSION['user_id']; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log("Réponse serveur: " + JSON.stringify(data));
            if (data.success) {
                document.getElementById('test-result').innerHTML = '<div class="alert alert-success">✓ Notification envoyée ! Vérifiez si vous l\'avez reçue.</div>';
            } else {
                document.getElementById('test-result').innerHTML = '<div class="alert alert-danger">✗ Erreur: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error("Erreur Fetch: " + error);
            document.getElementById('test-result').innerHTML = '<div class="alert alert-danger">Erreur réseau: ' + error + '</div>';
        });
    });
});
</script>
