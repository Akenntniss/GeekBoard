<?php
/**
 * Fonctions pour enregistrer les logs des tâches
 */

/**
 * Enregistre un log d'action sur une tâche
 * 
 * @param int $tache_id ID de la tâche
 * @param int $employe_id ID de l'employé qui effectue l'action
 * @param string $action_type Type d'action (demarrage, terminer, changement_statut, etc.)
 * @param string $statut_avant Statut avant l'action (optionnel)
 * @param string $statut_apres Statut après l'action (optionnel)
 * @param string $details Détails supplémentaires (optionnel)
 * @return bool True si le log a été enregistré avec succès
 */
function logTaskAction($tache_id, $employe_id, $action_type, $statut_avant = null, $statut_apres = null, $details = null) {
    try {
        // Initialiser la session magasin si nécessaire (pour les appels depuis les APIs)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['shop_id'])) {
            initializeShopSession();
        }
        
        // Obtenir la connexion à la base de données du magasin
        $shop_pdo = getShopDBConnection();
        
        if (!$shop_pdo) {
            error_log("Erreur: Impossible d'obtenir une connexion à la base de données pour le logging des tâches");
            return false;
        }
        
        // Vérifier que l'employé existe
        $stmt_check = $shop_pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt_check->execute([$employe_id]);
        if (!$stmt_check->fetch()) {
            error_log("Erreur: Employé ID $employe_id n'existe pas dans la table users");
            return false;
        }
        
        // Vérifier que la tâche existe
        $stmt_check_task = $shop_pdo->prepare("SELECT id FROM taches WHERE id = ?");
        $stmt_check_task->execute([$tache_id]);
        if (!$stmt_check_task->fetch()) {
            error_log("Erreur: Tâche ID $tache_id n'existe pas dans la table taches");
            return false;
        }
        
        // Insérer le log
        $stmt = $shop_pdo->prepare("
            INSERT INTO task_logs (tache_id, employe_id, action_type, statut_avant, statut_apres, details) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $tache_id,
            $employe_id,
            $action_type,
            $statut_avant,
            $statut_apres,
            $details
        ]);
        
        if ($result) {
            error_log("Log tâche enregistré: Tâche $tache_id, Employé $employe_id, Action $action_type");
            return true;
        } else {
            error_log("Erreur lors de l'enregistrement du log de tâche");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement du log de tâche: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les logs d'une tâche spécifique
 * 
 * @param int $tache_id ID de la tâche
 * @return array Liste des logs de la tâche
 */
function getTaskLogs($tache_id) {
    try {
        // Initialiser la session magasin si nécessaire
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['shop_id'])) {
            initializeShopSession();
        }
        
        // Obtenir la connexion à la base de données du magasin
        $shop_pdo = getShopDBConnection();
        
        if (!$shop_pdo) {
            return [];
        }
        
        $stmt = $shop_pdo->prepare("
            SELECT tl.*, u.full_name as employe_nom, t.titre as tache_titre
            FROM task_logs tl
            LEFT JOIN users u ON tl.employe_id = u.id
            LEFT JOIN taches t ON tl.tache_id = t.id
            WHERE tl.tache_id = ?
            ORDER BY tl.date_action DESC
        ");
        
        $stmt->execute([$tache_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des logs de tâche: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère tous les logs de tâches (pour la page reparation_logs)
 * 
 * @param int $limit Nombre maximum de logs à récupérer (défaut: 100)
 * @param int $offset Décalage pour la pagination (défaut: 0)
 * @return array Liste des logs de toutes les tâches
 */
function getAllTaskLogs($limit = 100, $offset = 0) {
    try {
        // Initialiser la session magasin si nécessaire
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['shop_id'])) {
            initializeShopSession();
        }
        
        // Obtenir la connexion à la base de données du magasin
        $shop_pdo = getShopDBConnection();
        
        if (!$shop_pdo) {
            return [];
        }
        
        $stmt = $shop_pdo->prepare("
            SELECT tl.*, u.full_name as employe_nom, t.titre as tache_titre
            FROM task_logs tl
            LEFT JOIN users u ON tl.employe_id = u.id
            LEFT JOIN taches t ON tl.tache_id = t.id
            ORDER BY tl.date_action DESC
            LIMIT $limit OFFSET $offset
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de tous les logs de tâches: " . $e->getMessage());
        return [];
    }
}
?>