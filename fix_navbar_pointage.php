<?php
/**
 * Script pour améliorer la robustesse du système de pointage dans la navbar
 * Ajoute une vérification et nettoyage automatique des pointages orphelins
 */

require_once 'config/database.php';

echo "🔧 AMÉLIORATION DU SYSTÈME DE POINTAGE NAVBAR\n";
echo "==============================================\n\n";

// 1. Créer une API améliorée pour vérifier l'état du pointage
$enhanced_status_api = '<?php
/**
 * API améliorée pour vérifier le statut de pointage
 * Inclut un nettoyage automatique des pointages orphelins anciens
 */

require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");
session_start();
initializeShopSession();

try {
    $pdo = getShopDBConnection();
    $user_id = $_SESSION["user_id"] ?? null;
    
    if (!$user_id) {
        echo json_encode([
            "success" => false,
            "message" => "Utilisateur non authentifié"
        ]);
        exit;
    }
    
    // ÉTAPE 1: Nettoyage automatique des pointages orphelins anciens (plus de 24h)
    $cleanup_stmt = $pdo->prepare("
        UPDATE time_tracking 
        SET clock_out = DATE_ADD(clock_in, INTERVAL 8 HOUR),
            status = \'completed\',
            updated_at = NOW()
        WHERE user_id = ? 
        AND clock_out IS NULL 
        AND clock_in < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $cleanup_stmt->execute([$user_id]);
    $cleaned_count = $cleanup_stmt->rowCount();
    
    if ($cleaned_count > 0) {
        error_log("Auto-nettoyage: $cleaned_count pointages orphelins fermés pour user $user_id");
    }
    
    // ÉTAPE 2: Vérifier l\'état actuel
    $stmt = $pdo->prepare("
        SELECT id, clock_in, clock_out, status,
               CASE WHEN clock_out IS NULL THEN 1 ELSE 0 END as is_active
        FROM time_tracking 
        WHERE user_id = ? 
        ORDER BY clock_in DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        // Aucun pointage trouvé
        echo json_encode([
            "success" => true,
            "is_clocked_in" => false,
            "message" => "Aucun pointage trouvé",
            "cleaned_entries" => $cleaned_count
        ]);
    } else {
        // Pointage trouvé
        $is_clocked_in = (bool)$entry["is_active"];
        
        echo json_encode([
            "success" => true,
            "is_clocked_in" => $is_clocked_in,
            "last_entry" => [
                "id" => $entry["id"],
                "clock_in" => $entry["clock_in"],
                "clock_out" => $entry["clock_out"],
                "status" => $entry["status"]
            ],
            "cleaned_entries" => $cleaned_count,
            "message" => $is_clocked_in ? "Pointage en cours" : "Prêt à pointer"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur: " . $e->getMessage()
    ]);
}
?>';

// Écrire l'API améliorée
$enhanced_api_path = '/Users/admin/Documents/GeekBoard/ajax/enhanced_timetracking_status.php';
if (!is_dir(dirname($enhanced_api_path))) {
    mkdir(dirname($enhanced_api_path), 0755, true);
}

file_put_contents($enhanced_api_path, $enhanced_status_api);
echo "✅ API améliorée créée: ajax/enhanced_timetracking_status.php\n";

// 2. Créer un JavaScript amélioré pour la navbar
$enhanced_js = 'class RobustTimeTracking {
    constructor() {
        this.apiUrl = "time_tracking_api.php";
        this.statusUrl = "ajax/enhanced_timetracking_status.php";
        this.currentStatus = { is_clocked_in: false };
        this.init();
    }
    
    async init() {
        await this.checkStatus();
        this.updateUI();
        this.bindEvents();
        
        // Vérifier le statut toutes les 5 minutes
        setInterval(() => this.checkStatus(), 5 * 60 * 1000);
    }
    
    async checkStatus() {
        try {
            const response = await fetch(this.statusUrl);
            const data = await response.json();
            
            if (data.success) {
                this.currentStatus.is_clocked_in = data.is_clocked_in;
                
                // Afficher un message si des pointages ont été nettoyés
                if (data.cleaned_entries > 0) {
                    this.showMessage(`🔧 ${data.cleaned_entries} pointage(s) orphelin(s) nettoyé(s) automatiquement`, "info");
                }
                
                console.log("📊 Statut pointage:", data);
            } else {
                console.error("❌ Erreur statut:", data.message);
            }
        } catch (error) {
            console.error("❌ Erreur vérification statut:", error);
        }
    }
    
    async clockIn() {
        try {
            // Vérifier le statut avant de pointer
            await this.checkStatus();
            
            if (this.currentStatus.is_clocked_in) {
                this.showMessage("⚠️ Vous êtes déjà pointé", "warning");
                return;
            }
            
            this.showMessage("🔄 Pointage en cours...", "info");
            
            const response = await fetch(this.apiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=clock_in"
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage("✅ Pointage d\'entrée réussi", "success");
                this.currentStatus.is_clocked_in = true;
                this.updateUI();
            } else {
                // Si erreur de pointage déjà en cours, essayer le nettoyage
                if (data.message && data.message.includes("déjà un pointage")) {
                    this.showMessage("🔧 Nettoyage automatique en cours...", "info");
                    await this.checkStatus(); // Le nettoyage se fait dans checkStatus
                    
                    // Réessayer après nettoyage
                    setTimeout(() => {
                        this.showMessage("🔄 Nouvelle tentative de pointage...", "info");
                        this.clockIn();
                    }, 2000);
                } else {
                    this.showMessage(`❌ ${data.message}`, "error");
                }
            }
        } catch (error) {
            this.showMessage(`❌ Erreur: ${error.message}`, "error");
        }
    }
    
    async clockOut() {
        try {
            await this.checkStatus();
            
            if (!this.currentStatus.is_clocked_in) {
                this.showMessage("⚠️ Vous n\'êtes pas pointé", "warning");
                return;
            }
            
            this.showMessage("🔄 Pointage de sortie...", "info");
            
            const response = await fetch(this.apiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=clock_out"
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage("✅ Pointage de sortie réussi", "success");
                this.currentStatus.is_clocked_in = false;
                this.updateUI();
            } else {
                this.showMessage(`❌ ${data.message}`, "error");
            }
        } catch (error) {
            this.showMessage(`❌ Erreur: ${error.message}`, "error");
        }
    }
    
    updateUI() {
        const clockButton = document.getElementById("clock-button");
        const statusDisplay = document.getElementById("time-status-display");
        
        if (clockButton) {
            if (this.currentStatus.is_clocked_in) {
                clockButton.innerHTML = \'<i class="fas fa-sign-out-alt"></i> Clock-Out\';
                clockButton.className = "btn btn-danger btn-sm mx-1";
                clockButton.onclick = () => this.clockOut();
            } else {
                clockButton.innerHTML = \'<i class="fas fa-sign-in-alt"></i> Clock-In\';
                clockButton.className = "btn btn-success btn-sm mx-1";
                clockButton.onclick = () => this.clockIn();
            }
        }
        
        if (statusDisplay) {
            const status = this.currentStatus.is_clocked_in ? 
                \'<small class="text-success">Pointé</small>\' : 
                \'<small class="text-muted">Non pointé</small>\';
            statusDisplay.innerHTML = status;
        }
    }
    
    bindEvents() {
        // Raccourcis clavier
        document.addEventListener("keydown", (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === "I") {
                e.preventDefault();
                if (!this.currentStatus.is_clocked_in) this.clockIn();
            }
            if (e.ctrlKey && e.shiftKey && e.key === "O") {
                e.preventDefault();
                if (this.currentStatus.is_clocked_in) this.clockOut();
            }
        });
    }
    
    showMessage(message, type = "info") {
        // Utiliser les toasts existants si disponibles
        if (typeof showGeekToast === "function") {
            showGeekToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
}

// Initialiser le système robuste
let robustTimeTracking;
document.addEventListener("DOMContentLoaded", () => {
    robustTimeTracking = new RobustTimeTracking();
});

// Fonctions globales pour compatibilité
function safeClockIn() {
    if (robustTimeTracking) robustTimeTracking.clockIn();
}

function safeClockOut() {
    if (robustTimeTracking) robustTimeTracking.clockOut();
}';

// Écrire le JavaScript amélioré
$enhanced_js_path = '/Users/admin/Documents/GeekBoard/assets/js/robust_time_tracking.js';
if (!is_dir(dirname($enhanced_js_path))) {
    mkdir(dirname($enhanced_js_path), 0755, true);
}

file_put_contents($enhanced_js_path, $enhanced_js);
echo "✅ JavaScript amélioré créé: assets/js/robust_time_tracking.js\n";

echo "\n🎯 INSTRUCTIONS D'INSTALLATION:\n";
echo "================================\n";
echo "1. D'abord, exécutez le diagnostic: php diagnostic_pointage.php\n";
echo "2. Remplacez l'inclusion JS dans la navbar par:\n";
echo "   <script src=\"assets/js/robust_time_tracking.js\"></script>\n";
echo "3. Déployez les fichiers sur le serveur:\n";
echo "   - ajax/enhanced_timetracking_status.php\n";
echo "   - assets/js/robust_time_tracking.js\n\n";

echo "✨ AMÉLIORATIONS APPORTÉES:\n";
echo "- ✅ Nettoyage automatique des pointages orphelins anciens\n";
echo "- ✅ Vérification d'état avant chaque action\n";
echo "- ✅ Nouvelle tentative automatique en cas d'erreur\n";
echo "- ✅ Messages informatifs pour l'utilisateur\n";
echo "- ✅ Logs pour le debugging\n\n";

echo "🔧 Améliorations terminées!\n";
?>
