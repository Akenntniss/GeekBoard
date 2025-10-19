<?php
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class RTSPCaptureService {
    private $rtsp_url;
    private $capture_dir;
    private $max_captures = 5; // Nombre max d'images à conserver
    
    public function __construct() {
        // Configuration RTSP - à adapter selon votre caméra
        $this->rtsp_url = $_ENV['RTSP_CAMERA_URL'] ?? 'rtsp://admin:password@192.168.1.100:554/stream1';
        $this->capture_dir = __DIR__ . '/../uploads/rtsp_captures/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->capture_dir)) {
            mkdir($this->capture_dir, 0755, true);
        }
    }
    
    /**
     * Capturer une image du flux RTSP
     */
    public function captureFrame() {
        try {
            $timestamp = time();
            $filename = "capture_{$timestamp}.jpg";
            $filepath = $this->capture_dir . $filename;
            
            // Utiliser FFmpeg pour capturer une frame du flux RTSP
            $command = sprintf(
                'ffmpeg -i "%s" -frames:v 1 -q:v 2 -y "%s" 2>&1',
                escapeshellarg($this->rtsp_url),
                escapeshellarg($filepath)
            );
            
            error_log("Commande FFmpeg: " . $command);
            
            $output = shell_exec($command);
            error_log("Sortie FFmpeg: " . $output);
            
            // Vérifier si l'image a été créée
            if (file_exists($filepath) && filesize($filepath) > 0) {
                // Nettoyer les anciennes captures
                $this->cleanOldCaptures();
                
                // Retourner le chemin relatif pour l'affichage web
                $relative_path = 'uploads/rtsp_captures/' . $filename;
                
                return [
                    'success' => true,
                    'image_path' => $relative_path,
                    'timestamp' => $timestamp,
                    'message' => 'Image capturée avec succès'
                ];
            } else {
                throw new Exception('Échec de la capture - fichier non créé ou vide');
            }
            
        } catch (Exception $e) {
            error_log("Erreur capture RTSP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la capture: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtenir la liste des captures récentes
     */
    public function getRecentCaptures() {
        $captures = [];
        $files = glob($this->capture_dir . 'capture_*.jpg');
        
        // Trier par date de modification (plus récent en premier)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        foreach (array_slice($files, 0, $this->max_captures) as $file) {
            $filename = basename($file);
            $captures[] = [
                'filename' => $filename,
                'path' => 'uploads/rtsp_captures/' . $filename,
                'timestamp' => filemtime($file),
                'size' => filesize($file)
            ];
        }
        
        return $captures;
    }
    
    /**
     * Nettoyer les anciennes captures
     */
    private function cleanOldCaptures() {
        $files = glob($this->capture_dir . 'capture_*.jpg');
        
        if (count($files) > $this->max_captures) {
            // Trier par date de modification
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Supprimer les plus anciens
            $to_delete = array_slice($files, 0, count($files) - $this->max_captures);
            foreach ($to_delete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Vérifier la connectivité RTSP
     */
    public function testConnection() {
        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_streams "%s" 2>&1',
            escapeshellarg($this->rtsp_url)
        );
        
        $output = shell_exec($command);
        
        if (strpos($output, '"streams"') !== false) {
            return [
                'success' => true,
                'message' => 'Connexion RTSP OK'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Impossible de se connecter au flux RTSP',
                'debug' => $output
            ];
        }
    }
}

// Traitement des requêtes
$service = new RTSPCaptureService();

switch ($_GET['action'] ?? $_POST['action'] ?? '') {
    case 'capture':
        echo json_encode($service->captureFrame());
        break;
        
    case 'list':
        echo json_encode([
            'success' => true,
            'captures' => $service->getRecentCaptures()
        ]);
        break;
        
    case 'test':
        echo json_encode($service->testConnection());
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non spécifiée. Actions disponibles: capture, list, test'
        ]);
        break;
}
?> 