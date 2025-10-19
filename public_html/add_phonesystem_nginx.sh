#!/bin/bash

# Script pour ajouter le sous-domaine phonesystem.servo.tools à la configuration Nginx
# Utilisation: bash add_phonesystem_nginx.sh

echo "🔧 Ajout du sous-domaine phonesystem.servo.tools à la configuration Nginx..."

# Configuration à ajouter
NGINX_CONFIG="
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

# Chemin du fichier de configuration
NGINX_CONF="/etc/nginx/sites-available/servo.tools"

# Vérifier si le fichier existe
if [ ! -f "$NGINX_CONF" ]; then
    echo "❌ Fichier de configuration $NGINX_CONF non trouvé!"
    exit 1
fi

# Vérifier si phonesystem.servo.tools existe déjà
if grep -q "phonesystem.servo.tools" "$NGINX_CONF"; then
    echo "⚠️ Configuration pour phonesystem.servo.tools déjà présente"
    echo "Vérification de la configuration actuelle..."
    grep -A 5 -B 5 "phonesystem.servo.tools" "$NGINX_CONF"
else
    echo "➕ Ajout de la configuration pour phonesystem.servo.tools..."
    
    # Faire une sauvegarde
    cp "$NGINX_CONF" "$NGINX_CONF.backup.$(date +%Y%m%d_%H%M%S)"
    echo "💾 Sauvegarde créée: $NGINX_CONF.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Ajouter la configuration à la fin du fichier
    echo "$NGINX_CONFIG" >> "$NGINX_CONF"
    echo "✅ Configuration ajoutée"
fi

# Tester la configuration Nginx
echo "🧪 Test de la configuration Nginx..."
if nginx -t; then
    echo "✅ Configuration Nginx valide"
    
    # Recharger Nginx
    echo "🔄 Rechargement de Nginx..."
    if systemctl reload nginx; then
        echo "✅ Nginx rechargé avec succès"
    else
        echo "❌ Erreur lors du rechargement de Nginx"
        exit 1
    fi
else
    echo "❌ Configuration Nginx invalide!"
    echo "🔄 Restauration de la sauvegarde..."
    cp "$NGINX_CONF.backup.$(date +%Y%m%d_%H%M%S)" "$NGINX_CONF"
    exit 1
fi

# Obtenir un certificat SSL pour le nouveau sous-domaine
echo "🔐 Obtention du certificat SSL pour phonesystem.servo.tools..."
if certbot --nginx -d phonesystem.servo.tools --non-interactive --agree-tos --email admin@servo.tools; then
    echo "✅ Certificat SSL obtenu avec succès"
else
    echo "⚠️ Erreur lors de l'obtention du certificat SSL"
    echo "Vous devrez peut-être obtenir le certificat manuellement:"
    echo "certbot --nginx -d phonesystem.servo.tools"
fi

echo ""
echo "🎉 Configuration terminée!"
echo "Le sous-domaine phonesystem.servo.tools devrait maintenant être accessible."
echo ""
echo "📋 Prochaines étapes:"
echo "1. Vérifiez que https://phonesystem.servo.tools/ fonctionne"
echo "2. Testez l'URL https://phonesystem.servo.tools/suivi.php?id=2"
echo "3. Vérifiez les logs en cas de problème:"
echo "   - tail -f /var/log/nginx/phonesystem_servo_access.log"
echo "   - tail -f /var/log/nginx/phonesystem_servo_error.log"
