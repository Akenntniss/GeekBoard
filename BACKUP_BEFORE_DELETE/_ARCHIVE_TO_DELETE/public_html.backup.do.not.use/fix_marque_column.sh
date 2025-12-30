#!/bin/bash

# Script pour corriger automatiquement les références à la colonne r.marque
# qui n'existe pas dans la table reparations

echo "🔧 Début de la correction des références à r.marque..."

# Créer un dossier de sauvegarde
mkdir -p backups_marque_fix

# Trouver tous les fichiers PHP qui contiennent r.marque
files_to_fix=$(grep -r "r\.marque" public_html/ --include="*.php" -l)

echo "📁 Fichiers à corriger :"
echo "$files_to_fix"

# Parcourir chaque fichier et faire les corrections
for file in $files_to_fix; do
    echo "🔄 Traitement de $file..."
    
    # Créer une sauvegarde
    cp "$file" "backups_marque_fix/$(basename $file).backup"
    
    # Correction 1: Enlever r.marque des SELECT 
    sed -i.tmp 's/r\.type_appareil, r\.marque, r\.modele/r.type_appareil, r.modele/g' "$file"
    sed -i.tmp 's/r\.marque, r\.modele/r.modele/g' "$file"
    sed -i.tmp 's/r\.marque,//' "$file"
    sed -i.tmp 's/, r\.marque//' "$file"
    
    # Correction 2: Enlever les conditions WHERE avec r.marque
    sed -i.tmp 's/OR r\.marque LIKE[^)]*//g' "$file"
    sed -i.tmp 's/r\.marque LIKE[^)]*OR //g' "$file"
    sed -i.tmp 's/r\.marque LIKE[^)]*AND //g' "$file"
    
    # Correction 3: Remplacer les variables PHP qui utilisent marque
    sed -i.tmp "s/\$repair_data\['marque'\]/\$repair_data['type_appareil']/g" "$file"
    sed -i.tmp "s/\$reparation\['marque'\]/\$reparation['type_appareil']/g" "$file"
    sed -i.tmp "s/\$rep\['marque'\]/\$rep['type_appareil']/g" "$file"
    
    # Supprimer les fichiers temporaires
    rm -f "$file.tmp"
    
    echo "✅ Terminé : $file"
done

echo "🚀 Correction terminée ! Sauvegardes créées dans backups_marque_fix/"
echo "📝 Déploiement des fichiers corrigés sur le serveur..."

# Déployer les fichiers corrigés sur le serveur
sshpass -p "Mamanmaman01#" rsync -avz --exclude="*.backup" public_html/ajax/ root@82.29.168.205:/var/www/mdgeek.top/ajax/
sshpass -p "Mamanmaman01#" rsync -avz --exclude="*.backup" public_html/pages/ root@82.29.168.205:/var/www/mdgeek.top/pages/
sshpass -p "Mamanmaman01#" rsync -avz --exclude="*.backup" public_html/api/ root@82.29.168.205:/var/www/mdgeek.top/api/

echo "🎉 Tous les fichiers ont été corrigés et déployés !" 