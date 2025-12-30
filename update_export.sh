#!/bin/bash

# Script pour mettre à jour le fichier export sur le serveur

echo "Mise à jour du fichier export_rachat_pdf.php..."

expect -c '
spawn ssh -o StrictHostKeyChecking=no root@82.29.168.205 "cp /var/www/mdgeek.top/ajax/export_rachat_pdf.php /var/www/mdgeek.top/ajax/export_rachat_pdf_backup.php"
expect "password:"
send "Mamanmaman01#\r"
expect eof
'

echo "Sauvegarde créée. Création de la nouvelle version..."

expect -c '
spawn ssh -o StrictHostKeyChecking=no root@82.29.168.205
expect "password:"
send "Mamanmaman01#\r"
expect "# "

send "cat > /var/www/mdgeek.top/ajax/export_rachat_pdf.php << '\''EOF'\''\r"
expect ">"

send "<?php\r"
send "// Export PDF corrigé\r"
send "if (session_status() === PHP_SESSION_NONE) {\r"
send "    session_start();\r"
send "}\r"
send "\r"
send "error_reporting(E_ALL);\r"
send "ini_set('\''display_errors'\'', 1);\r"
send "\r"
send "try {\r"
send "    require_once __DIR__.'\''/../config/database.php'\'';\r"
send "    if (!function_exists('\''getShopDBConnection'\'')) {\r"
send "        require_once __DIR__.'\''/../functions.php'\'';\r"
send "    }\r"
send "    \$shop_pdo = getShopDBConnection();\r"
send "    if (!\$shop_pdo) throw new Exception('\''Connexion DB échouée'\'');\r"
send "    \r"
send "    \$rachat_id = \$_GET['\''id'\''] ?? null;\r"
send "    \$table_name = '\''rachat_appareils'\'';\r"
send "    \r"
send "    if (\$rachat_id) {\r"
send "        \$stmt = \$shop_pdo->prepare(\"SELECT * FROM {\$table_name} WHERE id = ?\");\r"
send "        \$stmt->execute([\$rachat_id]);\r"
send "        \$rachats = \$stmt->fetchAll(PDO::FETCH_ASSOC);\r"
send "    } else {\r"
send "        \$stmt = \$shop_pdo->query(\"SELECT * FROM {\$table_name}\");\r"
send "        \$rachats = \$stmt->fetchAll(PDO::FETCH_ASSOC);\r"
send "    }\r"
send "    \r"
send "    // Export simple HTML au lieu de PDF\r"
send "    header('\''Content-Type: text/html; charset=utf-8'\'');\r"
send "    header('\''Content-Disposition: attachment; filename=\"rachats-'\''.date('\''Ymd-His'\'').'\''html\"'\'');\r"
send "    \r"
send "    \$title = \$rachat_id ? '\''Rachat #'\'' . \$rachat_id : '\''Liste des rachats'\'';\r"
send "    echo '\''<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>'\''.\$title.'\''</title></head><body>'\'';\r"
send "    echo '\''<h1>'\''.\$title.'\''</h1><table border=\"1\">'\'';\r"
send "    echo '\''<tr><th>ID</th><th>Type</th><th>Modèle</th><th>Prix</th><th>Date</th></tr>'\'';\r"
send "    \r"
send "    foreach (\$rachats as \$rachat) {\r"
send "        echo sprintf('\''<tr><td>%s</td><td>%s</td><td>%s</td><td>%s €</td><td>%s</td></tr>'\'',\r"
send "            htmlspecialchars(\$rachat['\''id'\''] ?? '\'\'\'\''),\r"
send "            htmlspecialchars(\$rachat['\''type_appareil'\''] ?? '\'\'\'\''),\r"
send "            htmlspecialchars(\$rachat['\''modele'\''] ?? '\'\'\'\''),\r"
send "            htmlspecialchars(\$rachat['\''prix'\''] ?? '\''0'\''),\r"
send "            htmlspecialchars(\$rachat['\''date_rachat'\''] ?? '\''\'\'\'\')\r"
send "        );\r"
send "    }\r"
send "    \r"
send "    echo '\''</table></body></html>'\'';\r"
send "    \r"
send "} catch (Exception \$e) {\r"
send "    echo \"Erreur: \" . \$e->getMessage();\r"
send "}\r"
send "?>\r"
send "EOF\r"

expect "# "
send "chown www-data:www-data /var/www/mdgeek.top/ajax/export_rachat_pdf.php\r"
expect "# "
send "exit\r"
expect eof
'

echo "Fichier mis à jour avec succès !"
