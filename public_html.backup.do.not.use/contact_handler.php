<?php
/**
 * Handler de contact pour le site marketing servo.tools
 * Traite les soumissions du formulaire de contact et envoie des emails via SMTP
 */

header('Content-Type: application/json; charset=utf-8');

// Sécurité basique
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Charger les dépendances
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/email.php';

// Fonction de nettoyage
function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

// Récupérer et nettoyer les données du formulaire
$contact_data = [
    'firstName' => clean($_POST['firstName'] ?? ''),
    'lastName' => clean($_POST['lastName'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => clean($_POST['phone'] ?? ''),
    'company' => clean($_POST['company'] ?? ''),
    'employees' => clean($_POST['employees'] ?? ''),
    'repairs' => clean($_POST['repairs'] ?? ''),
    'subject' => clean($_POST['subject'] ?? 'Démo générale'),
    'message' => trim($_POST['message'] ?? '')
];

// Validation des champs obligatoires
$errors = [];

if (empty($contact_data['firstName'])) {
    $errors[] = 'Le prénom est requis';
}

if (empty($contact_data['lastName'])) {
    $errors[] = 'Le nom est requis';
}

if (empty($contact_data['email'])) {
    $errors[] = 'L\'email est requis';
} elseif (!filter_var($contact_data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'L\'email n\'est pas valide';
}

if (empty($contact_data['company'])) {
    $errors[] = 'Le nom de l\'atelier est requis';
}

// Si des erreurs sont présentes, retourner les erreurs
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // 1. Sauvegarder dans la base de données
    $pdo = getMainDBConnection();
    
    // Créer la table si elle n'existe pas
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS contact_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        company VARCHAR(255),
        employees VARCHAR(50),
        repairs VARCHAR(50),
        subject VARCHAR(150) NOT NULL,
        message TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_email (email),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_table_sql);
    
    // Insérer la demande de contact
    $stmt = $pdo->prepare("
        INSERT INTO contact_requests 
        (first_name, last_name, email, phone, company, employees, repairs, subject, message, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $contact_data['firstName'],
        $contact_data['lastName'],
        $contact_data['email'],
        $contact_data['phone'],
        $contact_data['company'],
        $contact_data['employees'],
        $contact_data['repairs'],
        $contact_data['subject'],
        $contact_data['message'],
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    $contact_id = $pdo->lastInsertId();
    
    // 2. Envoyer l'email de notification à l'équipe
    $notification_result = sendContactNotification($contact_data);
    
    // 3. Envoyer l'email de confirmation au client
    $confirmation_result = sendContactConfirmation($contact_data);
    
    // Logger les résultats des emails
    if (!$notification_result['success']) {
        error_log("Erreur notification email (Contact ID: $contact_id): " . $notification_result['message']);
    }
    
    if (!$confirmation_result['success']) {
        error_log("Erreur confirmation email (Contact ID: $contact_id): " . $confirmation_result['message']);
    }
    
    // Réponse de succès (même si les emails ont échoué, la demande est sauvegardée)
    echo json_encode([
        'success' => true, 
        'message' => 'Votre demande a été enregistrée avec succès ! Nous vous recontacterons sous 2 heures.',
        'contact_id' => $contact_id
    ]);
    
} catch (Exception $e) {
    // Logger l'erreur complète
    error_log('CONTACT_HANDLER ERROR: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur technique. Contactez-nous directement au 08 95 79 59 33'
    ]);
}
?> 