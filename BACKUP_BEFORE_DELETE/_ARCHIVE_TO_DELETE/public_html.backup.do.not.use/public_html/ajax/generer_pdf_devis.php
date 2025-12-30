<?php
/**
 * ================================================================================
 * GÉNÉRATEUR PDF DE DEVIS MODERNE
 * ================================================================================
 * Description: API pour générer des PDF de devis professionnels
 * Date: 2025-01-27
 * ================================================================================
 */

require_once __DIR__ . '/../config/subdomain_database_detector.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../fpdf/fpdf.php';

// Gestion des CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Accepter GET et POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Récupérer les paramètres
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $devis_id = $_GET['devis_id'] ?? null;
        $shop_id = $_GET['shop_id'] ?? null;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $devis_id = $input['devis_id'] ?? null;
        $shop_id = $input['shop_id'] ?? null;
    }

    if (!$devis_id) {
        throw new Exception('ID du devis manquant');
    }

    // Connexion à la base de données
    if ($shop_id) {
        $shop_pdo = getShopDBConnectionById($shop_id);
    } else {
        $detector = new SubdomainDatabaseDetector();
        $shop_pdo = $detector->getShopConnection();
    }

    // Récupérer les informations complètes du devis
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            c.email as client_email,
            c.adresse as client_adresse,
            r.description_probleme as reparation_probleme,
            r.type_appareil as reparation_appareil,
            r.modele as reparation_modele
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE d.id = ?
    ");
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        throw new Exception('Devis non trouvé');
    }

    // Récupérer les pannes
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_pannes 
        WHERE devis_id = ? 
        ORDER BY ordre
    ");
    $stmt->execute([$devis_id]);
    $pannes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les solutions avec leurs éléments
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_solutions 
        WHERE devis_id = ? 
        ORDER BY ordre
    ");
    $stmt->execute([$devis_id]);
    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque solution, récupérer ses éléments
    foreach ($solutions as &$solution) {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM devis_solutions_items 
            WHERE solution_id = ? 
            ORDER BY ordre
        ");
        $stmt->execute([$solution['id']]);
        $solution['elements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Classe PDF personnalisée
    class DevisPDF extends FPDF {
        private $devis;
        private $pannes;
        private $solutions;
        
        public function __construct($devis, $pannes, $solutions) {
            parent::__construct();
            $this->devis = $devis;
            $this->pannes = $pannes;
            $this->solutions = $solutions;
        }
        
        function Header() {
            // Logo et en-tête
            $this->SetFont('Arial', 'B', 20);
            $this->SetTextColor(102, 126, 234);
            $this->Cell(0, 15, 'GEEKBOARD - DEVIS DE REPARATION', 0, 1, 'C');
            
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', '', 12);
            $this->Cell(0, 8, 'Service de reparation professionnelle', 0, 1, 'C');
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - Devis valable 15 jours', 0, 0, 'C');
        }

        function utf8_decode_fallback($str) {
            // Gestion améliorée de l'UTF-8
            $str = str_replace(['€'], ['EUR'], $str);
            return iconv('UTF-8', 'ISO-8859-1//IGNORE', $str);
        }
        
        function addInfoSection() {
            // Informations du devis
            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(102, 126, 234);
            $this->Cell(0, 10, $this->utf8_decode_fallback('DEVIS ' . $this->devis['numero_devis']), 0, 1);
            
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 6, $this->utf8_decode_fallback('Date: ' . date('d/m/Y', strtotime($this->devis['date_creation']))), 0, 1);
            $this->Cell(0, 6, $this->utf8_decode_fallback('Expire le: ' . date('d/m/Y', strtotime($this->devis['date_expiration']))), 0, 1);
            $this->Ln(5);
            
            // Informations client et réparation
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(95, 8, $this->utf8_decode_fallback('INFORMATIONS CLIENT'), 0, 0);
            $this->Cell(95, 8, $this->utf8_decode_fallback('APPAREIL A REPARER'), 0, 1);
            
            $this->SetFont('Arial', '', 10);
            $client_nom = $this->devis['client_nom'] . ' ' . $this->devis['client_prenom'];
            $this->Cell(95, 6, $this->utf8_decode_fallback($client_nom), 0, 0);
            $appareil = $this->devis['reparation_appareil'] . ' ' . $this->devis['reparation_modele'];
            $this->Cell(95, 6, $this->utf8_decode_fallback($appareil), 0, 1);
            
            if ($this->devis['client_telephone']) {
                $this->Cell(95, 6, $this->utf8_decode_fallback('Tel: ' . $this->devis['client_telephone']), 0, 0);
            }
            if ($this->devis['reparation_probleme']) {
                $this->Cell(95, 6, $this->utf8_decode_fallback('Probleme: ' . $this->devis['reparation_probleme']), 0, 1);
            } else {
                $this->Ln(6);
            }
            
            if ($this->devis['client_email']) {
                $this->Cell(95, 6, $this->utf8_decode_fallback('Email: ' . $this->devis['client_email']), 0, 1);
            }
            
            $this->Ln(10);
        }
        
        function addPannesSection() {
            if (empty($this->pannes)) return;
            
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(220, 53, 69);
            $this->Cell(0, 10, $this->utf8_decode_fallback('PANNES IDENTIFIEES'), 0, 1);
            
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(0, 0, 0);
            
            foreach ($this->pannes as $panne) {
                $this->Cell(10, 6, '•', 0, 0);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(50, 6, $this->utf8_decode_fallback($panne['nom'] ?? $panne['titre']), 0, 0);
                $this->SetFont('Arial', '', 10);
                
                if ($panne['gravite']) {
                    $this->Cell(30, 6, $this->utf8_decode_fallback('(' . $panne['gravite'] . ')'), 0, 0);
                }
                
                $this->Ln(6);
                
                if ($panne['description']) {
                    $this->Cell(15, 6, '', 0, 0); // Indentation
                    $this->MultiCell(0, 6, $this->utf8_decode_fallback($panne['description']));
                    $this->Ln(2);
                }
            }
            
            $this->Ln(5);
        }
        
        function addSolutionsSection() {
            if (empty($this->solutions)) return;
            
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(102, 126, 234);
            $this->Cell(0, 10, $this->utf8_decode_fallback('SOLUTIONS PROPOSEES'), 0, 1);
            
            $solution_choisie_id = $this->devis['solution_choisie_id'];
            
            foreach ($this->solutions as $index => $solution) {
                $lettre = chr(65 + $index); // A, B, C...
                $est_choisie = ($solution_choisie_id && $solution['id'] == $solution_choisie_id);
                
                // En-tête de la solution
                $this->SetFont('Arial', 'B', 12);
                if ($est_choisie) {
                    $this->SetTextColor(40, 167, 69); // Vert pour la solution choisie
                    $titre = 'Solution ' . $lettre . ' - ' . $solution['nom'] . ' ✓ CHOISIE';
                } else {
                    $this->SetTextColor(0, 0, 0);
                    $titre = 'Solution ' . $lettre . ' - ' . $solution['nom'];
                }
                
                $this->Cell(0, 8, $this->utf8_decode_fallback($titre), 0, 1);
                
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 10);
                
                if ($solution['description']) {
                    $this->MultiCell(0, 6, $this->utf8_decode_fallback($solution['description']));
                    $this->Ln(2);
                }
                
                // Éléments de la solution
                if (!empty($solution['elements'])) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(80, 6, 'Prestation', 1, 0, 'C');
                    $this->Cell(80, 6, 'Description', 1, 0, 'C');
                    $this->Cell(30, 6, 'Prix', 1, 1, 'C');
                    
                    $this->SetFont('Arial', '', 9);
                    foreach ($solution['elements'] as $element) {
                        $this->Cell(80, 6, $this->utf8_decode_fallback($element['nom']), 1, 0);
                        $this->Cell(80, 6, $this->utf8_decode_fallback($element['description'] ?? ''), 1, 0);
                        $this->Cell(30, 6, number_format($element['prix'], 2, ',', ' ') . ' EUR', 1, 1, 'R');
                    }
                    
                    // Sous-total de la solution
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(160, 6, 'Sous-total Solution ' . $lettre, 1, 0, 'R');
                    $this->Cell(30, 6, number_format($solution['prix_total'], 2, ',', ' ') . ' EUR', 1, 1, 'R');
                }
                
                $this->Ln(5);
            }
        }
        
        function addTotalSection() {
            $solution_choisie_id = $this->devis['solution_choisie_id'];
            
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(102, 126, 234);
            $this->Cell(0, 10, $this->utf8_decode_fallback('RECAPITULATIF'), 0, 1);
            
            $this->SetTextColor(0, 0, 0);
            
            if ($solution_choisie_id) {
                // Solution sélectionnée - calculer uniquement cette solution
                $solution_choisie = null;
                foreach ($this->solutions as $index => $solution) {
                    if ($solution['id'] == $solution_choisie_id) {
                        $solution_choisie = $solution;
                        $lettre = chr(65 + $index);
                        break;
                    }
                }
                
                if ($solution_choisie) {
                    $this->SetFont('Arial', '', 10);
                    $this->Cell(0, 6, $this->utf8_decode_fallback('Solution retenue: Solution ' . $lettre . ' - ' . $solution_choisie['nom']), 0, 1);
                    $this->Ln(5);
                    
                    $this->SetFont('Arial', 'B', 12);
                    $this->Cell(140, 8, '', 0, 0);
                    $this->Cell(50, 8, 'MONTANT A PAYER', 1, 1, 'C');
                    
                    $this->SetFont('Arial', '', 11);
                    $this->Cell(140, 8, 'Total HT', 0, 0, 'R');
                    $total_ht = $solution_choisie['prix_total'] / 1.20; // Supposant 20% TVA
                    $this->Cell(50, 8, number_format($total_ht, 2, ',', ' ') . ' EUR', 1, 1, 'R');
                    
                    $this->Cell(140, 8, 'TVA (20%)', 0, 0, 'R');
                    $tva = $solution_choisie['prix_total'] - $total_ht;
                    $this->Cell(50, 8, number_format($tva, 2, ',', ' ') . ' EUR', 1, 1, 'R');
                    
                    $this->SetFont('Arial', 'B', 12);
                    $this->Cell(140, 10, 'TOTAL TTC', 0, 0, 'R');
                    $this->SetFillColor(40, 167, 69);
                    $this->SetTextColor(255, 255, 255);
                    $this->Cell(50, 10, number_format($solution_choisie['prix_total'], 2, ',', ' ') . ' EUR', 1, 1, 'C', true);
                }
            } else {
                // Aucune solution choisie - afficher toutes les options
                $this->SetFont('Arial', '', 11);
                $this->SetTextColor(255, 152, 0);
                $this->Cell(0, 8, $this->utf8_decode_fallback('⚠ PLUSIEURS CHOIX DISPONIBLES'), 0, 1, 'C');
                
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 10);
                $this->Cell(0, 6, $this->utf8_decode_fallback('Le client doit choisir une solution parmi les options proposees.'), 0, 1, 'C');
                $this->Ln(5);
                
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(0, 6, $this->utf8_decode_fallback('Options tarifaires:'), 0, 1);
                
                $this->SetFont('Arial', '', 10);
                foreach ($this->solutions as $index => $solution) {
                    $lettre = chr(65 + $index);
                    $this->Cell(10, 6, '', 0, 0);
                    $this->Cell(100, 6, $this->utf8_decode_fallback('Solution ' . $lettre . ': ' . $solution['nom']), 0, 0);
                    $this->Cell(80, 6, number_format($solution['prix_total'], 2, ',', ' ') . ' EUR TTC', 0, 1, 'R');
                }
                
                $this->Ln(5);
                $this->SetFont('Arial', 'I', 10);
                $this->SetTextColor(128, 128, 128);
                $this->Cell(0, 6, $this->utf8_decode_fallback('Le montant final dependra de la solution choisie par le client.'), 0, 1, 'C');
            }
        }
        
        function addNotesSection() {
            if ($this->devis['notes_techniques'] || $this->devis['message_client']) {
                $this->Ln(10);
                
                $this->SetFont('Arial', 'B', 12);
                $this->SetTextColor(102, 126, 234);
                $this->Cell(0, 8, $this->utf8_decode_fallback('INFORMATIONS COMPLEMENTAIRES'), 0, 1);
                
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 10);
                
                if ($this->devis['notes_techniques']) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(0, 6, $this->utf8_decode_fallback('Notes techniques:'), 0, 1);
                    $this->SetFont('Arial', '', 10);
                    $this->MultiCell(0, 6, $this->utf8_decode_fallback($this->devis['notes_techniques']));
                    $this->Ln(3);
                }
                
                if ($this->devis['message_client']) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(0, 6, $this->utf8_decode_fallback('Message pour le client:'), 0, 1);
                    $this->SetFont('Arial', '', 10);
                    $this->MultiCell(0, 6, $this->utf8_decode_fallback($this->devis['message_client']));
                }
            }
        }
    }

    // Créer le PDF
    $pdf = new DevisPDF($devis, $pannes, $solutions);
    $pdf->AddPage();
    $pdf->addInfoSection();
    $pdf->addPannesSection();
    $pdf->addSolutionsSection();
    $pdf->addTotalSection();
    $pdf->addNotesSection();

    // Générer le nom du fichier
    $filename = 'Devis_' . $devis['numero_devis'] . '_' . date('Y-m-d') . '.pdf';

    // Envoyer le PDF
    $pdf->Output('D', $filename);

} catch (Exception $e) {
    error_log("Erreur génération PDF devis : " . $e->getMessage());
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "<h1>Erreur</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    } else {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 