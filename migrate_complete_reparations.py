#!/usr/bin/env python3
"""
Script pour migrer TOUTES les réparations (841 au total)
en utilisant le pattern plus large qui capture tout
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
        ids = []
        for line in result.stdout.strip().split('\n')[1:]:  # Skip header
            if line.strip() and line.strip() != 'id':
                ids.append(int(line.strip()))
        return set(ids)
    except:
        return set()

def parse_all_reparations(sql_content):
    """Parse TOUTES les réparations avec le pattern étendu"""
    # Utiliser le pattern qui capture le plus de réparations
    pattern = r'INSERT INTO `reparations`.*?(?=INSERT INTO|--|\Z)'
    matches = re.findall(pattern, sql_content, re.DOTALL | re.IGNORECASE)
    
    all_reparations = []
    
    for match in matches:
        # Extraire la partie VALUES
        values_match = re.search(r'VALUES\s*(.*)', match, re.DOTALL | re.IGNORECASE)
        if not values_match:
            continue
        
        values_text = values_match.group(1).strip()
        
        # Nettoyer le texte (enlever les ; et commentaires en fin)
        values_text = re.sub(r';.*$', '', values_text, flags=re.MULTILINE)
        values_text = re.sub(r'--.*$', '', values_text, flags=re.MULTILINE)
        
        # Parser les tuples
        tuples = []
        level = 0
        current_tuple = ""
        i = 0
        
        while i < len(values_text):
            char = values_text[i]
            
            if char == '(':
                level += 1
                current_tuple += char
            elif char == ')':
                level -= 1
                current_tuple += char
                
                if level == 0:
                    # Fin d'un tuple
                    tuples.append(current_tuple.strip())
                    current_tuple = ""
                    # Ignorer la virgule suivante
                    i += 1
                    while i < len(values_text) and values_text[i] in [',', ' ', '\n', '\r', '\t']:
                        i += 1
                    i -= 1
            else:
                current_tuple += char
            
            i += 1
        
        # Extraire les IDs et ajouter les tuples
        for tuple_str in tuples:
            rep_id_match = re.match(r'\((\d+),', tuple_str)
            if rep_id_match:
                rep_id = int(rep_id_match.group(1))
                all_reparations.append((rep_id, tuple_str))
    
    return all_reparations

def main():
    print("🚀 Migration COMPLÈTE des réparations (841 réparations)")
    print("=" * 60)
    
    # Lire le fichier SQL
    sql_file = Path('base_client.sql')
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    # Récupérer les IDs existants
    print("📋 Récupération des réparations déjà migrées...")
    existing_ids = get_existing_reparation_ids()
    print(f"   Réparations déjà migrées : {len(existing_ids)}")
    
    # Parser toutes les réparations
    print("📖 Analyse complète du fichier...")
    all_reparations = parse_all_reparations(sql_content)
    print(f"   Réparations trouvées dans le fichier : {len(all_reparations)}")
    
    # Identifier les réparations manquantes
    missing_reparations = []
    for rep_id, tuple_str in all_reparations:
        if rep_id not in existing_ids:
            missing_reparations.append(tuple_str)
    
    print(f"   Réparations manquantes : {len(missing_reparations)}")
    
    if not missing_reparations:
        print("✅ Toutes les réparations sont déjà migrées !")
        return
    
    # Migrer les réparations manquantes
    print("🚀 Migration des réparations manquantes...")
    
    # Désactiver les contraintes
    execute_ssh_mysql("SET foreign_key_checks = 0")
    execute_ssh_mysql("SET sql_mode = ''")
    
    chunk_size = 3  # Très petits chunks pour éviter les erreurs
    success_count = 0
    
    for i in range(0, len(missing_reparations), chunk_size):
        chunk = missing_reparations[i:i + chunk_size]
        
        # Créer l'INSERT avec toutes les colonnes
        insert_query = """INSERT IGNORE INTO `reparations` 
        (`id`, `client_id`, `type_appareil`, `marque`, `modele`, `description_probleme`, `date_reception`, 
         `date_modification`, `date_fin_prevue`, `statut`, `statut_id`, `statut_categorie`, `signature`, 
         `prix`, `notes_techniques`, `notes_finales`, `photo_appareil`, `mot_de_passe`, `etat_esthetique`, 
         `prix_reparation`, `devis_envoye`, `devis_accepte`, `date_envoi_devis`, `date_reponse_devis`, 
         `photos`, `urgent`, `commande_requise`, `archive`, `employe_id`, `date_gardiennage`, 
         `gardiennage_facture`, `parrain_id`, `reduction_parrainage`, `reduction_parrainage_pourcentage`, 
         `signature_client`, `photo_signature`, `photo_client`, `accept_conditions`, `proprietaire`, 
         `signature_devis`, `date_signature_devis`) VALUES """ + ", ".join(chunk) + ";"
        
        print(f"   📦 Chunk {i//chunk_size + 1}/{(len(missing_reparations) + chunk_size - 1)//chunk_size}...", end=" ")
        
        if execute_ssh_mysql(insert_query):
            success_count += len(chunk)
            print("✅")
        else:
            print("❌")
    
    # Réactiver les contraintes
    execute_ssh_mysql("SET foreign_key_checks = 1")
    
    print(f"\n✅ Migration terminée : {success_count} réparations ajoutées")
    
    # Statistiques finales détaillées
    print("\n📊 Statistiques finales :")
    
    queries = [
        "SELECT COUNT(*) as total_reparations FROM geekboard_mdg.reparations;",
        "SELECT archive, COUNT(*) as count FROM geekboard_mdg.reparations GROUP BY archive;",
        "SELECT statut, COUNT(*) as count FROM geekboard_mdg.reparations GROUP BY statut ORDER BY count DESC LIMIT 10;"
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
            print("❌ Erreur lors de la récupération des statistiques")

if __name__ == '__main__':
    main()
