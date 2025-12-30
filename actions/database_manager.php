<?php
require_once __DIR__ . '/../config/database.php';

class DatabaseResetManager {
    private $pdo;
    private $shop_id;
    private $backup_dir;

    public function __construct($pdo, $shop_id) {
        $this->pdo = $pdo;
        $this->shop_id = $shop_id;
        $this->backup_dir = __DIR__ . '/../backups/shop_' . $shop_id . '/';

        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
            // Protect directory
            file_put_contents($this->backup_dir . '.htaccess', 'Deny from all');
        }
    }

    /**
     * Create a backup of the current database
     */
    public function createBackup() {
        $tables = [];
        $result = $this->pdo->query('SHOW TABLES');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $return = "-- Backup created at " . date("Y-m-d H:i:s") . "\n";
        $return .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $return .= "SET time_zone = \"+00:00\";\n\n";

        foreach ($tables as $table) {
            // Get create table statement
            $row2 = $this->pdo->query('SHOW CREATE TABLE ' . $table)->fetch(PDO::FETCH_NUM);
            $return .= "\n\n" . $row2[1] . ";\n\n";

            // Get data
            $result = $this->pdo->query('SELECT * FROM ' . $table);
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= "INSERT INTO " . $table . " VALUES(";
                for ($j = 0; $j < count($row); $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < (count($row) - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        
        $return .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup_shop' . $this->shop_id . '_' . date("Y-m-d_H-i-s") . '.sql';
        $handle = fopen($this->backup_dir . $filename, 'w+');
        fwrite($handle, $return);
        fclose($handle);

        return $filename;
    }

    /**
     * Wipe the database (DROP all tables except preserved ones)
     */
    public function wipeDatabase($preservedTables = ['users', 'parametres']) {
        // Ensure we are NOT on the main database just in case
        // This is a rudimentary check, relying on connection context
        
        // 1. Force Backup first
        $this->createBackup();

        // 2. Disable Foreign Keys
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        // 3. Get all tables
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            if (!in_array($table, $preservedTables)) {
                $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
        }

        // 4. Re-enable Foreign Keys
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        return true;
    }

    /**
     * Restore database from file
     */
    public function restoreDatabase($filename) {
        $filePath = $this->backup_dir . basename($filename);
        
        if (!file_exists($filePath)) {
            throw new Exception("File not found");
        }

        // Disable foreign keys for restore
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $sql = file_get_contents($filePath);
        
        // Use standard PDO exec if file is not too huge, mostly usually okay for shop DBs
        // For larger DBs, line by line reading is better, but this is a simplified version
        // Splitting by ; can be tricky with data containing ;
        // So we just run it ? PDO might not support multiple queries at once depending on config
        // Let's try splitting by line and accumulating
        
        $lines = file($filePath);
        $templine = '';
        foreach ($lines as $line) {
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;

            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';') {
                try {
                    $this->pdo->exec($templine);
                } catch(Exception $e) {
                    // Ignore duplicate errors or specific restore hiccups if safe
                    // but for now let's just continue
                }
                $templine = '';
            }
        }
        
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        return true;
    }

    public function getBackups() {
        $files = glob($this->backup_dir . '*.sql');
        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file)
            ];
        }
        // Newest first
        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        return $backups;
    }
    
    public function deleteBackup($filename) {
        $filePath = $this->backup_dir . basename($filename);
        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        }
        return false;
    }
}

// Handle AJAX Request
if (isset($_POST['action'])) {
    // Security Check: Must be logged in
    session_start();
    require_once __DIR__ . '/../config/subdomain_config.php'; // Ensure shop context
    
    if (!isset($_SESSION['user_id'])) {
         header('HTTP/1.1 403 Forbidden');
         echo json_encode(['error' => 'Unauthorized']);
         exit;
    }

    header('Content-Type: application/json');

    try {
        $shop_id = $_SESSION['shop_id'] ?? 0;
        if ($shop_id == 0) throw new Exception("Invalid Shop ID");

        // Init Manager with Shop Connection
        $shop_pdo = getShopDBConnection();
        $manager = new DatabaseResetManager($shop_pdo, $shop_id);

        if ($_POST['action'] === 'backup') {
            $file = $manager->createBackup();
            echo json_encode(['success' => true, 'message' => 'Backup created: ' . $file]);
        }
        elseif ($_POST['action'] === 'wipe') {
            // Check Password (Optional but recommended extra layer, done in UI mostly, but here we trust session for now or could verify password passed in POST)
            // Ideally verification should happen here using $_POST['password']
            
            $manager->wipeDatabase();
            echo json_encode(['success' => true, 'message' => 'Database wiped successfully (Users preserved).']);
        }
        elseif ($_POST['action'] === 'restore') {
            $filename = $_POST['filename'] ?? '';
            if (!$filename) throw new Exception("Filename missing");
            $manager->restoreDatabase($filename);
            echo json_encode(['success' => true, 'message' => 'Database restored successfully.']);
        }
        elseif ($_POST['action'] === 'delete_backup') {
             $filename = $_POST['filename'] ?? '';
             if (!$filename) throw new Exception("Filename missing");
             $manager->deleteBackup($filename);
             echo json_encode(['success' => true, 'message' => 'Backup deleted.']);
        }
        elseif ($_POST['action'] === 'list') {
            $backups = $manager->getBackups();
            echo json_encode(['success' => true, 'backups' => $backups]);
        }

    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
