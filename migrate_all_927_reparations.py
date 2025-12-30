#!/usr/bin/env python3
"""
Script pour migrer les 927 réparations en utilisant l'analyse ligne par ligne
"""

import re
import subprocess
import sys
from pathlib import Path

def execute_ssh_mysql(query, database="geekboard_mdg"):
    """Exécute une requête MySQL via SSH"""
    escaped_query = query.replace('"', '\\"').replace('`', '\\`')
    
    ssh_cmd = [
        'sshpass', '-p', 'Mamanmaman01#',
        'ssh', '-o', 'StrictHostKeyChecking=no',
        'root@82.29.168.205',
        f'mysql -u root -pMamanmaman01# -e "{escaped_query}" {database} 2>/dev/null'
    ]
    
    try:
        result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Erreur MySQL : {e}")
        return False

def get_existing_reparation_ids():
    """Récupère les IDs des réparations déjà migrées"""
    ssh_cmd = [
        'sshpass', '-p', 'Mamanmaman01#',
        'ssh', '-o', 'StrictHostKeyChecking=no',
        'root@82.29.168.205',
        'mysql -u root -pMamanmaman01# -e "SELECT id FROM geekboard_mdg.reparations ORDER BY id;" 2>/dev/null'
    ]
    
    try:
        result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
        ids = set()
        for line in result.stdout.strip().split('\n')[1:]:  # Skip header
            if line.strip() and line.strip() != 'id':
                ids.add(int(line.strip()))
        return ids
    except Exception as e:
        print(f"❌ Erreur récupération IDs : {e}")
        return set()

def extract_all_reparations_robust(sql_content):
    """Extraction robuste de toutes les réparations"""
    lines = sql_content.split('\n')
    
    # Positions des INSERT INTO reparations
    insert_positions = [2357, 2486, 2613, 2736, 2809, 2859, 2960, 3012, 3037, 3090, 3185]
    
    all_reparations = []
    
    for i, start_line in enumerate(insert_positions):
        print(f"📖 Traitement section {i+1} (ligne {start_line})...")
        
        # Déterminer la fin de cette section
        if i < len(insert_positions) - 1:
            end_line = insert_positions[i + 1]
        else:
            # Pour la dernière section, chercher la prochaine table
            end_line = len(lines)
            for j in range(start_line, len(lines)):
                if ('CREATE TABLE' in lines[j] or 
                    ('INSERT INTO' in lines[j] and 'reparations' not in lines[j])):
                    end_line = j + 1
                    break
        
        # Extraire la section complète
        section_lines = lines[start_line-1:end_line-1]  # -1 car numérotation commence à 1
        section_content = '\n'.join(section_lines)
        
        # Extraire les tuples de cette section en utilisant une approche plus robuste
        # Chercher tous les patterns qui commencent par (ID,
        tuples_in_section = re.findall(r'\(\d+,[^)]*\)', section_content, re.DOTALL)
        
        print(f"   Tuples bruts trouvés: {len(tuples_in_section)}")
        
        # Filtrer et nettoyer les tuples
        valid_tuples = []
        for tuple_str in tuples_in_section:
            # Vérifier que c'est un tuple de réparation valide (commence par un ID numérique)
            if re.match(r'\(\d+,', tuple_str):
                # Nettoyer le tuple
                clean_tuple = tuple_str.strip()
                if len(clean_tuple) > 50:  # Ignorer les tuples trop courts
                    valid_tuples.append(clean_tuple)
        
        print(f"   Tuples valides: {len(valid_tuples)}")
        
        # Extraire les IDs et ajouter à la liste
        for tuple_str in valid_tuples:
            rep_id_match = re.match(r'\((\d+),', tuple_str)
            if rep_id_match:
                rep_id = int(rep_id_match.group(1))
                all_reparations.append((rep_id, tuple_str))
    
    return all_reparations

def main():
    print("🚀 Migration des 927 réparations détectées")
    print("=" * 60)
    
    # Lire le fichier SQL
    sql_file = Path('base_client.sql')
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    # Récupérer les IDs existants
    print("📋 Récupération des réparations déjà migrées...")
    existing_ids = get_existing_reparation_ids()
    print(f"   Réparations déjà migrées : {len(existing_ids)}")
    
    # Extraire toutes les réparations
    print("📖 Extraction robuste de toutes les réparations...")
    all_reparations = extract_all_reparations_robust(sql_content)
    print(f"\n📊 Total réparations extraites : {len(all_reparations)}")
    
    # Identifier les réparations manquantes
    missing_reparations = []
    for rep_id, tuple_str in all_reparations:
        if rep_id not in existing_ids:
            missing_reparations.append(tuple_str)
    
    print(f"   Réparations manquantes : {len(missing_reparations)}")
    
    if not missing_reparations:
        print("✅ Toutes les réparations sont déjà migrées !")
        return
    
    # Confirmer avant migration
    response = input(f"\n⚠️  Migrer {len(missing_reparations)} réparations ? (y/N): ")
    if response.lower() != 'y':
        print("❌ Migration annulée")
        return
    
    # Migrer les réparations manquantes
    print("🚀 Migration des réparations manquantes...")
    
    # Désactiver les contraintes
    execute_ssh_mysql("SET foreign_key_checks = 0")
    execute_ssh_mysql("SET sql_mode = ''")
    
    chunk_size = 1  # Un par un pour éviter les erreurs
    success_count = 0
    error_count = 0
    
    for i, tuple_str in enumerate(missing_reparations):
        # Créer l'INSERT pour une seule réparation
        insert_query = """INSERT IGNORE INTO `reparations` 
        (`id`, `client_id`, `type_appareil`, `marque`, `modele`, `description_probleme`, `date_reception`, 
         `date_modification`, `date_fin_prevue`, `statut`, `statut_id`, `statut_categorie`, `signature`, 
         `prix`, `notes_techniques`, `notes_finales`, `photo_appareil`, `mot_de_passe`, `etat_esthetique`, 
         `prix_reparation`, `devis_envoye`, `devis_accepte`, `date_envoi_devis`, `date_reponse_devis`, 
         `photos`, `urgent`, `commande_requise`, `archive`, `employe_id`, `date_gardiennage`, 
         `gardiennage_facture`, `parrain_id`, `reduction_parrainage`, `reduction_parrainage_pourcentage`, 
         `signature_client`, `photo_signature`, `photo_client`, `accept_conditions`, `proprietaire`, 
         `signature_devis`, `date_signature_devis`) VALUES """ + tuple_str + ";"
        
        print(f"   📦 Réparation {i+1}/{len(missing_reparations)}...", end=" ")
        
        if execute_ssh_mysql(insert_query):
            success_count += 1
            print("✅")
        else:
            error_count += 1
            print("❌")
            
        # Afficher le progrès tous les 50
        if (i + 1) % 50 == 0:
            print(f"   📊 Progrès: {i+1}/{len(missing_reparations)} ({success_count} succès, {error_count} erreurs)")
    
    # Réactiver les contraintes
    execute_ssh_mysql("SET foreign_key_checks = 1")
    
    print(f"\n✅ Migration terminée !")
    print(f"   Succès : {success_count} réparations")
    print(f"   Erreurs : {error_count} réparations")
    
    # Statistiques finales
    print("\n📊 Statistiques finales :")
    
    queries = [
        "SELECT COUNT(*) as total_reparations FROM geekboard_mdg.reparations;",
        "SELECT archive, COUNT(*) as count FROM geekboard_mdg.reparations GROUP BY archive;",
        "SELECT statut, COUNT(*) as count FROM geekboard_mdg.reparations WHERE archive='OUI' GROUP BY statut ORDER BY count DESC LIMIT 5;",
        "SELECT statut, COUNT(*) as count FROM geekboard_mdg.reparations WHERE archive='NON' GROUP BY statut ORDER BY count DESC LIMIT 5;"
    ]
    
    for query in queries:
        ssh_cmd = [
            'sshpass', '-p', 'Mamanmaman01#',
            'ssh', '-o', 'StrictHostKeyChecking=no',
            'root@82.29.168.205',
            f'mysql -u root -pMamanmaman01# -e "{query}" geekboard_mdg 2>/dev/null'
        ]
        
        try:
            result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
            print(result.stdout)
        except:
            print("❌ Erreur statistiques")

if __name__ == '__main__':
    main()
