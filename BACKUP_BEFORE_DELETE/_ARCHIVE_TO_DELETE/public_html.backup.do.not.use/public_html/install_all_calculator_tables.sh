#!/bin/bash

# Script pour installer la table calculator_settings dans toutes les bases de données magasin
echo "=== Installation des Tables du Calculateur de Prix ==="

# Connexion à la base générale pour récupérer la liste des magasins
MYSQL_USER="root"
MYSQL_PASS="Mamanmaman01#"
GENERAL_DB="geekboard_general"

# Récupération de la liste des magasins
echo "Récupération de la liste des magasins..."
SHOPS=$(mysql -u $MYSQL_USER -p$MYSQL_PASS -D $GENERAL_DB -s -N -e "SELECT db_name FROM shops WHERE active = 1")

echo "Magasins trouvés : $(echo "$SHOPS" | wc -l)"
echo ""

# Installation dans chaque base de données
for shop_db in $SHOPS; do
    echo "=== Installation dans $shop_db ==="
    
    # Exécution du script SQL
    if mysql -u $MYSQL_USER -p$MYSQL_PASS $shop_db < /var/www/mdgeek.top/sql/calculator_settings.sql; then
        echo "✓ Table installée avec succès dans $shop_db"
    else
        echo "✗ Erreur lors de l'installation dans $shop_db"
    fi
    echo ""
done

echo "=== Installation terminée ==="
echo "Vous pouvez maintenant accéder au calculateur via : pages/CalculateurPrix.php"
