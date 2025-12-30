#!/usr/bin/env python3
"""
Script pour migrer TOUTES les réparations manquantes
Il y a 11 sections INSERT dans le fichier base_client.sql
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

def extract_all_reparations_by_section(sql_content):
    """Extrait toutes les réparations section par section"""
    
    # Trouver toutes les sections INSERT INTO reparations
    sections = []
    
    # Utiliser les numéros de ligne pour découper précisément
    lines = sql_content.split('\n')
    
    # Positions des INSERT INTO reparations (d'après grep -n)
    insert_positions = [2357, 2486, 2613, 2736, 2809, 2859, 2960, 3012, 3037, 3090, 3185]
    
    for i, start_line in enumerate(insert_positions):
        # Déterminer la fin de cette section
        if i < len(insert_positions) - 1:
            end_line = insert_positions[i + 1]
        else:
            # Pour la dernière section, chercher la prochaine table
            end_line = len(lines)
            for j in range(start_line, len(lines)):
                if 'CREATE TABLE' in lines[j] or 'INSERT INTO `' in lines[j] and 'reparations' not in lines[j]:
                    end_line = j
                    break
        
        # Extraire la section
        section_lines = lines[start_line-1:end_line]  # -1 car les numéros de ligne commencent à 1
        section_content = '\n'.join(section_lines)
        
        sections.append(section_content)
        print(f"Section {i+1}: lignes {start_line} à {end_line} ({end_line - start_line} lignes)")
    
    return sections

def parse_reparations_from_section(section_content):
    """Parse les réparations d'une section"""
    # Extraire la partie VALUES
    values_match = re.search(r'VALUES\s*(.*)', section_content, re.DOTALL | re.IGNORECASE)
    if not values_match:
        return []
    
    values_text = values_match.group(1).strip()
    
    # Nettoyer le texte
    values_text = re.sub(r';.*$', '', values_text, flags=re.MULTILINE)
    values_text = re.sub(r'--.*$', '', values_text, flags=re.MULTILINE)
    
    # Parser les tuples de manière robuste
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
                tuple_clean = current_tuple.strip()
                if tuple_clean and len(tuple_clean) > 10:  # Ignorer les tuples trop courts
                    tuples.append(tuple_clean)
                current_tuple = ""
                # Ignorer la virgule suivante
                i += 1
                while i < len(values_text) and values_text[i] in [',', ' ', '\n', '\r', '\t']:
                    i += 1
                i -= 1
        else:
            current_tuple += char
        
        i += 1
    
    # Extraire les IDs et retourner les tuples avec leurs IDs
    reparations = []
    for tuple_str in tuples:
        rep_id_match = re.match(r'\((\d+),', tuple_str)
        if rep_id_match:
            rep_id = int(rep_id_match.group(1))
            reparations.append((rep_id, tuple_str))
    
    return reparations

def main():
    print("🚀 Migration COMPLÈTE de TOUTES les réparations")
    print("=" * 60)
    
    # Lire le fichier SQL
    sql_file = Path('base_client.sql')
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    # Récupérer les IDs existants
    print("📋 Récupération des réparations déjà migrées...")
    existing_ids = get_existing_reparation_ids()
    print(f"   Réparations déjà migrées : {len(existing_ids)}")
    
    # Extraire toutes les sections
    print("📖 Extraction des sections INSERT...")
    sections = extract_all_reparations_by_section(sql_content)
    print(f"   Sections trouvées : {len(sections)}")
    
    # Parser toutes les réparations
    all_reparations = []
    for i, section in enumerate(sections):
        reparations = parse_reparations_from_section(section)
        all_reparations.extend(reparations)
        print(f"   Section {i+1}: {len(reparations)} réparations")
    
    print(f"\n📊 Total réparations dans le fichier : {len(all_reparations)}")
    
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
    
    chunk_size = 2  # Très petits chunks
    success_count = 0
    error_count = 0
    
    for i in range(0, len(missing_reparations), chunk_size):
        chunk = missing_reparations[i:i + chunk_size]
        
        # Créer l'INSERT
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
            error_count += len(chunk)
            print("❌")
    
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
