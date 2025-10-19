<?php
// Script pour vérifier et créer le magasin phonesystem
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/public_html/config/database.php';

echo "<h1>Vérification du magasin 'phonesystem'</h1>\n";

try {
    // Connexion à la base principale
    $main_pdo = getMainDBConnection();
    
    if (!$main_pdo) {
        echo "<p style='color: red;'>❌ Impossible de se connecter à la base principale</p>\n";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Connexion à la base principale réussie</p>\n";
    
    // Vérifier si le magasin phonesystem existe
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE name = ? OR subdomain = ?");
    $stmt->execute(['phonesystem', 'phonesystem']);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        echo "<p style='color: green;'>✅ Le magasin 'phonesystem' existe déjà!</p>\n";
        echo "<pre>" . print_r($shop, true) . "</pre>\n";
        
        // Vérifier la connexion à la base du magasin
        try {
            $shop_pdo = new PDO(
                "mysql:host={$shop['db_host']};dbname={$shop['db_name']};charset=utf8mb4",
                $shop['db_user'],
                $shop['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            echo "<p style='color: green;'>✅ Connexion à la base du magasin réussie</p>\n";
            
            // Vérifier quelques tables importantes
            $tables_to_check = ['clients', 'reparations', 'users'];
            foreach ($tables_to_check as $table) {
                try {
                    $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        echo "<p style='color: green;'>✅ Table '$table' présente</p>\n";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Table '$table' manquante</p>\n";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erreur lors de la vérification de la table '$table': " . $e->getMessage() . "</p>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Impossible de se connecter à la base du magasin: " . $e->getMessage() . "</p>\n";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ Le magasin 'phonesystem' n'existe pas</p>\n";
        echo "<p>Création du magasin en cours...</p>\n";
        
        // Créer le magasin phonesystem
        $stmt = $main_pdo->prepare("
            INSERT INTO shops (name, description, subdomain, db_host, db_port, db_name, db_user, db_pass, active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $success = $stmt->execute([
            'phonesystem',
            'Magasin Phone System',
            'phonesystem',
            'localhost', // Sera mis à jour pour le serveur
            '3306',
            'geekboard_phonesystem',
            'u139954273_phonesystem', // Sera mis à jour pour le serveur
            'MotDePasseTemporaire123#'
        ]);
        
        if ($success) {
            echo "<p style='color: green;'>✅ Magasin 'phonesystem' créé avec succès!</p>\n";
            $shop_id = $main_pdo->lastInsertId();
            echo "<p>ID du magasin: $shop_id</p>\n";
            
            echo "<h2>Informations pour le serveur:</h2>\n";
            echo "<p><strong>Configuration Nginx à ajouter:</strong></p>\n";
            echo "<pre>";
echo "# phonesystem.servo.tools
server {
    listen 80;
    server_name phonesystem.servo.tools;
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name phonesystem.servo.tools;
    root /var/www/mdgeek.top;
    index index.php index.html index.htm;

    ssl_certificate /etc/letsencrypt/live/phonesystem.servo.tools/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/phonesystem.servo.tools/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    access_log /var/log/nginx/phonesystem_servo_access.log;
    error_log /var/log/nginx/phonesystem_servo_error.log;

    set \$shop_subdomain \"phonesystem\";

    location / {
        try_files \$uri \$uri/ @rewrite;
    }

    location @rewrite {
        rewrite ^/([^/]+)/?\$ /\$1.php last;
        rewrite ^(.+)\$ /index.php?\$query_string last;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param SHOP_SUBDOMAIN \$shop_subdomain;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.env { deny all; }
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control \"public, immutable\";
    }
}";
            echo "</pre>\n";
            
        } else {
            echo "<p style='color: red;'>❌ Erreur lors de la création du magasin</p>\n";
        }
    }
    
    // Afficher tous les magasins existants
    echo "<h2>Magasins existants dans la base:</h2>\n";
    $stmt = $main_pdo->query("SELECT id, name, subdomain, db_name, active FROM shops ORDER BY id");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Base de données</th><th>Actif</th></tr>\n";
    foreach ($shops as $shop) {
        $status = $shop['active'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$shop['id']}</td>";
        echo "<td>{$shop['name']}</td>";
        echo "<td>{$shop['subdomain']}</td>";
        echo "<td>{$shop['db_name']}</td>";
        echo "<td>$status</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>\n";
}
?>
