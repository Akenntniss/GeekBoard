#!/bin/bash

echo "=== EXÉCUTION DE TOUS LES LOTS DE RÉPARATIONS ==="

# Supprimer d'abord la réparation de test
echo "Suppression de la réparation de test..."
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mysql -u root -p'Mamanmaman01#' geekboard_mkmkmk -e 'DELETE FROM reparations WHERE id = 2000;'"

# Compter les lots disponibles
batch_count=$(ls /Users/admin/Documents/GeekBoard/batch_*.sql | wc -l)
echo "Nombre de lots trouvés: $batch_count"

success_count=0
error_count=0

# Exécuter chaque lot
for i in $(seq 1 $batch_count); do
    batch_file="/Users/admin/Documents/GeekBoard/batch_$i.sql"
    
    if [ -f "$batch_file" ]; then
        echo "Exécution du lot $i..."
        
        # Copier le lot sur le serveur et l'exécuter
        if sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "cat > /tmp/batch_$i.sql" < "$batch_file" && \
           sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mysql -u root -p'Mamanmaman01#' geekboard_mkmkmk < /tmp/batch_$i.sql" 2>/dev/null; then
            echo "✅ Lot $i exécuté avec succès"
            ((success_count++))
        else
            echo "❌ Erreur lors de l'exécution du lot $i"
            ((error_count++))
        fi
        
        # Pause courte entre les lots
        sleep 1
    else
        echo "⚠️  Fichier batch_$i.sql non trouvé"
    fi
done

echo ""
echo "=== RÉSUMÉ ==="
echo "Lots réussis: $success_count"
echo "Lots en erreur: $error_count"
echo "Total: $batch_count"

# Vérifications finales
echo ""
echo "=== VÉRIFICATIONS FINALES ==="
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mysql -u root -p'Mamanmaman01#' geekboard_mkmkmk -e 'SELECT COUNT(*) as total_clients FROM clients; SELECT COUNT(*) as total_reparations FROM reparations; SELECT COUNT(*) as clients_importes FROM clients WHERE id >= 1000; SELECT COUNT(*) as reparations_importees FROM reparations WHERE id >= 2000; SELECT COUNT(*) as orphaned_reparations FROM reparations r LEFT JOIN clients c ON r.client_id = c.id WHERE c.id IS NULL;'"

echo ""
echo "=== MIGRATION TERMINÉE ==="
