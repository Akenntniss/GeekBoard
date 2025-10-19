#!/bin/bash

# Script pour corriger la configuration Nginx de phonesystem.servo.tools
echo "🔧 Correction de la configuration Nginx pour phonesystem.servo.tools..."

# Fichier de configuration
NGINX_CONF="/etc/nginx/sites-available/servo.tools.conf"

# Créer une sauvegarde
cp "$NGINX_CONF" "$NGINX_CONF.backup.$(date +%Y%m%d_%H%M%S)"
echo "💾 Sauvegarde créée"

# Supprimer l'ancienne configuration phonesystem s'il y en a une
sed -i '/# phonesystem.servo.tools/,/^}$/d' "$NGINX_CONF"
echo "🗑️ Ancienne configuration supprimée"

# Nouvelle configuration complète avec HTTPS
NEW_CONFIG="
# phonesystem.servo.tools
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
}"

# Ajouter la nouvelle configuration
echo "$NEW_CONFIG" >> "$NGINX_CONF"
echo "✅ Nouvelle configuration ajoutée"

# Tester la configuration
echo "🧪 Test de la configuration Nginx..."
if nginx -t; then
    echo "✅ Configuration valide"
    
    # Obtenir le certificat SSL si nécessaire
    if [ ! -f "/etc/letsencrypt/live/phonesystem.servo.tools/fullchain.pem" ]; then
        echo "🔐 Obtention du certificat SSL..."
        certbot --nginx -d phonesystem.servo.tools --non-interactive --agree-tos --email admin@servo.tools
    else
        echo "✅ Certificat SSL déjà présent"
    fi
    
    # Recharger Nginx
    echo "🔄 Rechargement de Nginx..."
    systemctl reload nginx
    echo "✅ Nginx rechargé"
else
    echo "❌ Configuration invalide! Restauration de la sauvegarde..."
    cp "$NGINX_CONF.backup.$(date +%Y%m%d_%H%M%S)" "$NGINX_CONF"
    exit 1
fi

echo "🎉 Configuration corrigée avec succès!"
