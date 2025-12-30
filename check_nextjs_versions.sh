#!/bin/bash
# Script de vérification et mise à jour Next.js/React pour CVE-2025-55182

echo "=========================================="
echo "CORRECTION CVE-2025-55182 (React2Shell)"
echo "=========================================="
echo ""

# Identifier les applications PM2
echo "=== Applications PM2 ===" 
pm2 list

echo ""
echo "=== Localisation des applications Next.js ==="

# Trouver maisondugeek-v2
MAISONDUGEEK_PATH=$(pm2 info maisondugeek-v2 2>/dev/null | grep "script path" | awk '{print $4}' | xargs dirname 2>/dev/null)
if [ -z "$MAISONDUGEEK_PATH" ]; then
    # Alternative: chercher dans les chemins communs
    for path in /opt/maisondugeek /opt/maisondugeek-v2 /var/www/maisondugeek /root/maisondugeek-v2; do
        if [ -f "$path/package.json" ]; then
            MAISONDUGEEK_PATH="$path"
            break
        fi
    done
fi

# Trouver sms-dashboard
SMS_DASHBOARD_PATH=$(pm2 info sms-dashboard 2>/dev/null | grep "script path" | awk '{print $4}' | xargs dirname 2>/dev/null)
if [ -z "$SMS_DASHBOARD_PATH" ]; then
    for path in /opt/sms-dashboard /var/www/sms-dashboard /root/sms-dashboard; do
        if [ -f "$path/package.json" ]; then
            SMS_DASHBOARD_PATH="$path"
            break
        fi
    done
fi

echo "maisondugeek-v2: $MAISONDUGEEK_PATH"
echo "sms-dashboard: $SMS_DASHBOARD_PATH"
echo ""

# Fonction pour vérifier et afficher les versions
check_versions() {
    local app_path=$1
    local app_name=$2
    
    echo "=== $app_name ==="
    echo "Chemin: $app_path"
    
    if [ -f "$app_path/package.json" ]; then
        echo ""
        echo "Versions actuelles:"
        cd "$app_path"
        
        # Next.js
        NEXT_VERSION=$(node -pe "try { require('./package.json').dependencies.next } catch(e) { 'non installé' }" 2>/dev/null)
        echo "  - Next.js: $NEXT_VERSION"
        
        # React
        REACT_VERSION=$(node -pe "try { require('./package.json').dependencies.react } catch(e) { 'non installé' }" 2>/dev/null)
        echo "  - React: $REACT_VERSION"
        
        # React-DOM
        REACTDOM_VERSION=$(node -pe "try { require('./package.json').dependencies['react-dom'] } catch(e) { 'non installé' }" 2>/dev/null)
        echo "  - React-DOM: $REACTDOM_VERSION"
        
        echo ""
        echo "Versions installées (node_modules):"
        if [ -d "$app_path/node_modules/next" ]; then
            INSTALLED_NEXT=$(node -pe "try { require('./node_modules/next/package.json').version } catch(e) { 'erreur' }" 2>/dev/null)
            echo "  - Next.js installé: $INSTALLED_NEXT"
        fi
        
        if [ -d "$app_path/node_modules/react" ]; then
            INSTALLED_REACT=$(node -pe "try { require('./node_modules/react/package.json').version } catch(e) { 'erreur' }" 2>/dev/null)
            echo "  - React installé: $INSTALLED_REACT"
        fi
    else
        echo "⚠️  package.json non trouvé"
    fi
    echo ""
}

# Vérifier les deux applications
if [ -n "$MAISONDUGEEK_PATH" ] && [ -d "$MAISONDUGEEK_PATH" ]; then
    check_versions "$MAISONDUGEEK_PATH" "maisondugeek-v2"
else
    echo "⚠️  Application maisondugeek-v2 non trouvée"
fi

if [ -n "$SMS_DASHBOARD_PATH" ] && [ -d "$SMS_DASHBOARD_PATH" ]; then
    check_versions "$SMS_DASHBOARD_PATH" "sms-dashboard"
else
    echo "⚠️  Application sms-dashboard non trouvée"
fi

echo "=========================================="
echo "VÉRIFICATION TERMINÉE"
echo "=========================================="
echo ""
echo "Pour mettre à jour, exécuter les commandes suivantes:"
echo "cd <app_path> && npm install next@latest react@latest react-dom@latest"
echo "pm2 restart <app_name>"
