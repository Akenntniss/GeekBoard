<?php
/**
 * API - Créer une nouvelle conversation
 */

// Initialiser la session via la configuration globale
require_once __DIR__ . '/../../config/session_config.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Vérifier les données
// Vérifier le titre (obligatoire uniquement pour les groupes)
if (($input['type'] === 'groupe' || $input['type'] === 'annonce') && (!isset($input['titre']) || trim($input['titre']) === '')) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Titre de conversation manquant']);
    exit;
}

// Si c'est un message direct et pas de titre, on en génère un par défaut (sera géré par create_conversation ou vide)
if (!isset($input['titre']) || trim($input['titre']) === '') {
    $input['titre'] = ($input['type'] === 'direct') ? 'Conversation directe' : 'Nouvelle conversation';
}

if (!isset($input['type']) || !in_array($input['type'], ['direct', 'groupe', 'annonce'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Type de conversation invalide']);
    exit;
}

// Récupérer les participants, l'objet et la priorité
$participants = isset($input['participants']) ? $input['participants'] : [];
$objet = isset($input['objet']) ? $input['objet'] : null;
$priorite = isset($input['priorite']) ? $input['priorite'] : null;
$first_message = isset($input['first_message']) ? $input['first_message'] : null;

// Convertir les participants en entiers
$participants = array_map('intval', $participants);

// Inclure les fonctions
require_once '../includes/functions.php';

// Créer la conversation
$result = create_conversation($input['titre'], $input['type'], $_SESSION['user_id'], $participants, $objet, $priorite);

if (is_array($result) && isset($result['error'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit;
}

// ID de la conversation créée ou existante
$conversation_id = $result;

// Envoyer un premier message si fourni
if (!empty($first_message)) {
    // Vérifier si une signature est demandée
    $requires_signature = 0;
    if (isset($input['requires_signature']) && $input['requires_signature'] == 1) {
        $requires_signature = 1;
        // Vérification du rôle admin (simplifiée ici, send_message a aussi sa propre vérification mais on peut filtrer avant)
        // La verification stricte sera refaite dans send_message (si on modifie send_message) ou ici
    }

    $message_result = send_message($conversation_id, $_SESSION['user_id'], $first_message, 'text', [], $requires_signature);
    
    if (is_array($message_result) && isset($message_result['error'])) {
        // La conversation a été créée mais le message a échoué - on continue quand même
        log_error('Erreur lors de l\'envoi du premier message', $message_result['error']);
    }
}

// Renvoyer la réponse avec l'ID de la conversation
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Conversation créée avec succès',
    'conversation_id' => $conversation_id
]); 