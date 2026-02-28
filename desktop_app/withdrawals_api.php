<?php
/**
 * API REST v2 - Withdrawals (Demandes de retrait)
 * Handles: list, request, validate, reject
 */
require_once __DIR__ . '/../config.php';

// Auth & Shop Context
$payload = require_auth();
$user_id = $payload['id'];
$user_role = $payload['role'] ?? 'user';
$shop_id = $payload['shop_id'] ?? null;

if (!initialize_api_shop_context($shop_id)) {
    error_response("Erreur connexion magasin", 500);
}
global $shop_pdo;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    // ------------------------------------------------------------------
    // GET: List Withdrawals
    // ------------------------------------------------------------------
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $params = [];
        $where = [];

        // If user is NOT admin, restrict to own requests
        // Assuming 'admin' role check. Adjust as needed.
        if ($user_role !== 'admin') {
            $where[] = "dr.user_id = :uid";
            $params['uid'] = $user_id;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Count
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM demandes_retrait dr $whereClause");
        foreach ($params as $k => $v)
            $stmt->bindValue(":$k", $v);
        $stmt->execute();
        $total = $stmt->fetchColumn();

        // List
        $sql = "
            SELECT dr.*, u.full_name as user_name, u.email as user_email
            FROM demandes_retrait dr
            LEFT JOIN users u ON dr.user_id = u.id
            $whereClause
            ORDER BY dr.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $shop_pdo->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue(":$k", $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also return user balance context if listing own
        $balance = null;
        if ($user_role !== 'admin') {
            $stmt = $shop_pdo->prepare("SELECT solde_euros FROM user_cagnotte WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $balance = $stmt->fetchColumn() ?: 0;
        }

        success_response([
            'requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit)
            ],
            'my_balance' => $balance
        ]);
    }

    // ------------------------------------------------------------------
    // POST: Actions
    // ------------------------------------------------------------------
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        // --- REQUEST (User) ---
        if ($action === 'request') {
            $amount = floatval($input['amount'] ?? 0);
            $method_payment = $input['method'] ?? 'virement'; // virement, paypal, especes
            $details = $input['details'] ?? '';

            if ($amount <= 0)
                error_response("Montant invalide", 400);

            // Check Balance
            $stmt = $shop_pdo->prepare("SELECT solde_euros FROM user_cagnotte WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_balance = $stmt->fetchColumn() ?: 0;

            // Check Pending Requests ? Optional: prevent multiple pending requests or check total pending
            $stmt = $shop_pdo->prepare("SELECT SUM(montant) FROM demandes_retrait WHERE user_id = ? AND statut = 'en_attente'");
            $stmt->execute([$user_id]);
            $pending_amount = $stmt->fetchColumn() ?: 0;

            if (($current_balance - $pending_amount) < $amount) {
                error_response("Solde insuffisant (Solde: $current_balance €, En attente: $pending_amount €)", 400);
            }

            $stmt = $shop_pdo->prepare("
                INSERT INTO demandes_retrait (user_id, montant, methode_paiement, details_paiement, statut, created_at)
                VALUES (?, ?, ?, ?, 'en_attente', NOW())
            ");
            $stmt->execute([$user_id, $amount, $method_payment, $details]);

            success_response(["message" => "Demande enregistrée"]);
        }

        // --- VALIDATE (Admin) ---
        elseif ($action === 'validate') {
            // Role check
            if ($user_role !== 'admin')
                error_response("Non autorisé", 403);

            $request_id = $input['request_id'] ?? null;
            $comment = $input['comment'] ?? '';

            if (!$request_id)
                error_response("ID requis", 400);

            $shop_pdo->beginTransaction();

            try {
                // Get Request
                $stmt = $shop_pdo->prepare("SELECT * FROM demandes_retrait WHERE id = ? FOR UPDATE");
                $stmt->execute([$request_id]);
                $req = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$req)
                    throw new Exception("Demande introuvable");
                if ($req['statut'] !== 'en_attente')
                    throw new Exception("Demande déjà traitée");

                // Check Balance Again
                $stmt = $shop_pdo->prepare("SELECT solde_euros FROM user_cagnotte WHERE user_id = ? FOR UPDATE");
                $stmt->execute([$req['user_id']]);
                $balance = $stmt->fetchColumn() ?: 0;

                if ($balance < $req['montant']) {
                    throw new Exception("Solde utilisateur insuffisant lors de la validation");
                }

                // 1. Update Request
                $stmt = $shop_pdo->prepare("
                    UPDATE demandes_retrait 
                    SET statut = 'payee', commentaire_admin = ?, processed_at = NOW(), processed_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([$comment, $user_id, $request_id]);

                // 2. Deduct Balance
                $stmt = $shop_pdo->prepare("UPDATE user_cagnotte SET solde_euros = solde_euros - ? WHERE user_id = ?");
                $stmt->execute([$req['montant'], $req['user_id']]);

                // 3. Log History
                $stmt = $shop_pdo->prepare("
                    INSERT INTO cagnotte_historique (user_id, montant, type, description, admin_id, date_creation)
                    VALUES (?, ?, 'debit', ?, ?, NOW())
                ");
                $stmt->execute([$req['user_id'], $req['montant'], "Retrait validé #$request_id", $user_id]);

                $shop_pdo->commit();
                success_response(["message" => "Retrait validé et solde débité"]);

            } catch (Exception $e) {
                $shop_pdo->rollBack();
                error_response($e->getMessage(), 400);
            }
        }

        // --- REJECT (Admin) ---
        elseif ($action === 'reject') {
            if ($user_role !== 'admin')
                error_response("Non autorisé", 403);

            $request_id = $input['request_id'] ?? null;
            $comment = $input['comment'] ?? '';

            if (!$request_id)
                error_response("ID requis", 400);

            $stmt = $shop_pdo->prepare("
                UPDATE demandes_retrait 
                SET statut = 'refusee', commentaire_admin = ?, processed_at = NOW(), processed_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$comment, $user_id, $request_id]);

            success_response(["message" => "Demande refusée"]);
        } else {
            error_response("Action inconnue", 400);
        }
    }

} catch (Exception $e) {
    error_log("Withdrawals API Error: " . $e->getMessage());
    error_response("Erreur serveur: " . $e->getMessage(), 500);
}
?>