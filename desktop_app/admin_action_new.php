<?php
/**
 * API REST v2 - Admin Missions Actions
 * Handles: create, update, delete, validate_task, reject_task
 */
require_once __DIR__ . '/../config.php';

// Auth & Shop Context
$payload = require_auth();
$admin_user_id = $payload['id'];
$shop_id = $payload['shop_id'] ?? null;

// Verify Admin (simple check, enhance as needed)
// if ($payload['role'] !== 'admin') error_response("Unauthorized", 403);

if (!initialize_api_shop_context($shop_id)) {
    error_response("Erreur connexion magasin", 500);
}
global $shop_pdo;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    error_response("Action requise", 400);
}

$action = $input['action'];

try {

    // ------------------------------------------------------------------
    // 1. CREATE MISSION
    // ------------------------------------------------------------------
    if ($action === 'create_mission') {
        $titre = $input['titre'] ?? null;
        $type_id = $input['type_id'] ?? 1;
        $description = $input['description'] ?? '';
        $objectif_quantite = $input['objectif_quantite'] ?? 1;
        $recompense_euros = $input['recompense_euros'] ?? 0;
        $recompense_points = $input['recompense_points'] ?? 0;
        $date_debut = $input['date_debut'] ?? date('Y-m-d H:i:s');
        $date_fin = $input['date_fin'] ?? date('Y-m-d H:i:s', strtotime('+30 days'));

        if (!$titre)
            error_response("Titre requis", 400);

        $stmt = $shop_pdo->prepare("
            INSERT INTO missions (titre, description, mission_type_id, objectif_quantite, recompense_euros, recompense_points, statut, date_debut, date_fin, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, NOW())
        ");
        $stmt->execute([$titre, $description, $type_id, $objectif_quantite, $recompense_euros, $recompense_points, $date_debut, $date_fin]);

        success_response(["message" => "Mission créée avec succès", "id" => $shop_pdo->lastInsertId()]);
    }

    // ------------------------------------------------------------------
    // 2. UPDATE MISSION
    // ------------------------------------------------------------------
    elseif ($action === 'update_mission') {
        $mission_id = $input['mission_id'] ?? null;
        if (!$mission_id)
            error_response("ID Mission requis", 400);

        $fields = [];
        $values = [];

        if (isset($input['titre'])) {
            $fields[] = "titre=?";
            $values[] = $input['titre'];
        }
        if (isset($input['description'])) {
            $fields[] = "description=?";
            $values[] = $input['description'];
        }
        if (isset($input['statut'])) {
            $fields[] = "statut=?";
            $values[] = $input['statut'];
        }
        if (isset($input['recompense_euros'])) {
            $fields[] = "recompense_euros=?";
            $values[] = $input['recompense_euros'];
        }
        if (isset($input['recompense_points'])) {
            $fields[] = "recompense_points=?";
            $values[] = $input['recompense_points'];
        }
        if (isset($input['objectif_quantite'])) {
            $fields[] = "objectif_quantite=?";
            $values[] = $input['objectif_quantite'];
        }
        if (isset($input['type_id'])) {
            $fields[] = "mission_type_id=?";
            $values[] = $input['type_id'];
        }
        if (isset($input['date_debut'])) {
            $fields[] = "date_debut=?";
            $values[] = $input['date_debut'];
        }
        if (isset($input['date_fin'])) {
            $fields[] = "date_fin=?";
            $values[] = $input['date_fin'];
        }

        if (empty($fields))
            error_response("Aucune donnée à modifier", 400);

        $values[] = $mission_id;
        $sql = "UPDATE missions SET " . implode(', ', $fields) . " WHERE id=?";
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($values);

        success_response(["message" => "Mission mise à jour"]);
    }

    // ------------------------------------------------------------------
    // 3. DELETE MISSION
    // ------------------------------------------------------------------
    elseif ($action === 'delete_mission') {
        $mission_id = $input['mission_id'] ?? null;
        if (!$mission_id)
            error_response("ID Mission requis", 400);

        // Check participants
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE mission_id = ?");
        $stmt->execute([$mission_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Soft delete
            $stmt = $shop_pdo->prepare("UPDATE missions SET statut = 'archivee' WHERE id = ?");
            $stmt->execute([$mission_id]);
            success_response(["message" => "Mission archivée (car des participants existent)"]);
        } else {
            $stmt = $shop_pdo->prepare("DELETE FROM missions WHERE id = ?");
            $stmt->execute([$mission_id]);
            success_response(["message" => "Mission supprimée"]);
        }
    }

    // ------------------------------------------------------------------
    // 4. VALIDATE / REJECT TASK
    // ------------------------------------------------------------------
    elseif ($action === 'validate_task' || $action === 'reject_task') {
        $validation_id = $input['validation_id'] ?? null;
        $commentaire = $input['commentaire'] ?? '';

        if (!$validation_id)
            error_response("ID Validation requis", 400);

        // Get Validation Info
        $stmt = $shop_pdo->prepare("SELECT * FROM mission_validations WHERE id = ?");
        $stmt->execute([$validation_id]);
        $validation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$validation)
            error_response("Validation introuvable", 404);
        if ($validation['statut'] !== 'en_attente')
            error_response("Déjà traitée", 400);

        $new_statut = ($action === 'validate_task') ? 'approuvee' : 'rejetee';

        // Update Validation
        $stmt = $shop_pdo->prepare("
            UPDATE mission_validations 
            SET statut = ?, commentaire_admin = ?, date_traitement = NOW(), traite_par = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_statut, $commentaire, $admin_user_id, $validation_id]);

        // Logic if Approved: Increment progress, check completion, reward
        if ($new_statut === 'approuvee') {
            // 1. Increment User Mission Progress
            $stmt = $shop_pdo->prepare("UPDATE user_missions SET progression = progression + 1 WHERE id = ?");
            $stmt->execute([$validation['user_mission_id']]);

            // 2. Check if Completed
            $stmt = $shop_pdo->prepare("
                SELECT um.progression, m.objectif_nombre, m.recompense_euros, m.recompense_points, um.user_id, m.id as mission_id
                FROM user_missions um 
                JOIN missions m ON um.mission_id = m.id 
                WHERE um.id = ?
             ");
            $stmt->execute([$validation['user_mission_id']]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($details && $details['progression'] >= $details['objectif_nombre']) {
                // Mark as Terminated
                $stmt = $shop_pdo->prepare("UPDATE user_missions SET statut = 'terminee', date_completion = NOW() WHERE id = ?");
                $stmt->execute([$validation['user_mission_id']]);

                // Give Rewards
                $euros = (float) $details['recompense_euros'];
                $points = (int) $details['recompense_points'];
                $participant_id = $details['user_id'];

                if ($euros > 0 || $points > 0) {
                    // Update User Wallet (user_cagnotte)
                    $stmt = $shop_pdo->prepare("SELECT id FROM user_cagnotte WHERE user_id = ?");
                    $stmt->execute([$participant_id]);
                    if ($stmt->fetch()) {
                        $sql = "UPDATE user_cagnotte SET solde_euros = solde_euros + ?, solde_points = solde_points + ?, total_gagne_euros = total_gagne_euros + ?, total_gagne_points = total_gagne_points + ? WHERE user_id = ?";
                        $shop_pdo->prepare($sql)->execute([$euros, $points, $euros, $points, $participant_id]);
                    } else {
                        $sql = "INSERT INTO user_cagnotte (user_id, solde_euros, solde_points, total_gagne_euros, total_gagne_points) VALUES (?, ?, ?, ?, ?)";
                        $shop_pdo->prepare($sql)->execute([$participant_id, $euros, $points, $euros, $points]);
                    }

                    // History
                    $sql = "INSERT INTO historique_gains (user_id, mission_id, user_mission_id, montant_euros, points_attribues, type_gain, description, created_at) VALUES (?, ?, ?, ?, ?, 'mission_completee', 'Mission terminée', NOW())";
                    $shop_pdo->prepare($sql)->execute([$participant_id, $details['mission_id'], $validation['user_mission_id'], $euros, $points]);

                    // Update User Table Stats
                    $shop_pdo->prepare("UPDATE users SET cagnotte = cagnotte + ?, points_experience = points_experience + ? WHERE id = ?")->execute([$euros, $points, $participant_id]);
                }
            }
        }

        success_response(["message" => "Validation enregistrée"]);
    } else {
        error_response("Action non reconnue", 400);
    }

} catch (Exception $e) {
    error_log("Admin Missions Action Error: " . $e->getMessage());
    error_response("Erreur serveur: " . $e->getMessage(), 500);
}
?>