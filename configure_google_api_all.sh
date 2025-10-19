#!/bin/bash

# Script pour configurer l'API Google dans toutes les bases de données magasin
echo "=== Configuration de l'API Google pour tous les magasins ==="

# Vos informations API
API_KEY="AIzaSyBsqqE2tjgp6OY722lgUeFqJgjvNlnhyfk"
SEARCH_ENGINE_ID="424b4fb42c6ad47d5"

# Connexion à la base générale pour récupérer la liste des magasins
MYSQL_USER="root"
MYSQL_PASS="Mamanmaman01#"
GENERAL_DB="geekboard_general"

# Récupération de la liste des magasins
echo "Récupération de la liste des magasins..."
SHOPS=$(mysql -u $MYSQL_USER -p$MYSQL_PASS -D $GENERAL_DB -s -N -e "SELECT db_name FROM shops WHERE active = 1")

echo "Magasins trouvés : $(echo "$SHOPS" | wc -l)"
echo ""

# Configuration dans chaque base de données
for shop_db in $SHOPS; do
    echo "=== Configuration API dans $shop_db ==="
    
    # Mise à jour des paramètres API
    if mysql -u $MYSQL_USER -p$MYSQL_PASS $shop_db -e "UPDATE calculator_settings SET google_api_key = '$API_KEY', google_search_engine_id = '$SEARCH_ENGINE_ID' WHERE id = 1;"; then
        echo "✓ API configurée avec succès dans $shop_db"
    else
        echo "✗ Erreur lors de la configuration dans $shop_db"
    fi
    echo ""
done

echo "=== Configuration terminée ==="
echo "L'API Google est maintenant configurée pour tous les magasins !"
echo "Vous pouvez tester le calculateur : pages/CalculateurPrix.php"
