#!/bin/bash

# Script pour mettre à jour les tables calculator_settings dans toutes les bases de données magasin
echo "=== Mise à jour des Tables Calculator Settings ==="

# Connexion à la base générale pour récupérer la liste des magasins
MYSQL_USER="root"
MYSQL_PASS="Mamanmaman01#"
GENERAL_DB="geekboard_general"

# Récupération de la liste des magasins
echo "Récupération de la liste des magasins..."
SHOPS=$(mysql -u $MYSQL_USER -p$MYSQL_PASS -D $GENERAL_DB -s -N -e "SELECT db_name FROM shops WHERE active = 1")

echo "Magasins trouvés : $(echo "$SHOPS" | wc -l)"
echo ""

# Mise à jour dans chaque base de données
for shop_db in $SHOPS; do
    echo "=== Mise à jour de $shop_db ==="
    
    # Exécution du script SQL
    if mysql -u $MYSQL_USER -p$MYSQL_PASS $shop_db < /var/www/mdgeek.top/sql/update_calculator_settings.sql; then
        echo "✓ Table mise à jour avec succès dans $shop_db"
    else
        echo "✗ Erreur lors de la mise à jour dans $shop_db"
    fi
    echo ""
done

echo "=== Mise à jour terminée ==="
echo "Les nouveaux multiplicateurs de difficulté sont maintenant disponibles !"
