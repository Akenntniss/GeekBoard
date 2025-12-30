#!/usr/bin/env python3
"""
Script pour migrer TOUTES les tables liées aux réparations
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

def migrate_table_chunked(sql_content, table_name, chunk_size=5):
    """Migration d'une table par chunks"""
    print(f"📋 Migration de {table_name}...")
    
    # Extraire l'INSERT statement complet
    pattern = rf'INSERT INTO `{table_name}`[^;]+;'
    matches = re.findall(pattern, sql_content, re.DOTALL | re.IGNORECASE)
    
    if not matches:
        print(f"   ⚠️ Aucun INSERT trouvé pour {table_name}")
        return 0
    
    total_success = 0
    
    for insert_stmt in matches:
        # Extraire les colonnes
        columns_match = re.search(rf'INSERT INTO `{table_name}`\s*(\([^)]+\))', insert_stmt, re.IGNORECASE)
        if not columns_match:
            print(f"   ⚠️ Impossible d'extraire les colonnes pour {table_name}")
            continue
        
        columns = columns_match.group(1)
        
        # Parser les valeurs
        values_match = re.search(r'VALUES\s*(.*);?$', insert_stmt, re.DOTALL | re.IGNORECASE)
        if not values_match:
            continue
        
        values_text = values_match.group(1).strip()
        if values_text.endswith(';'):
            values_text = values_text[:-1]
        
        # Parser les tuples
        values_list = []
        level = 0
        current_value = ""
        i = 0
        
        while i < len(values_text):
            char = values_text[i]
            
            if char == '(':
                level += 1
                current_value += char
            elif char == ')':
                level -= 1
                current_value += char
                
                if level == 0:
                    values_list.append(current_value.strip())
                    current_value = ""
                    # Ignorer la virgule suivante
                    i += 1
                    while i < len(values_text) and values_text[i] in [',', ' ', '\n', '\r', '\t']:
                        i += 1
                    i -= 1
            else:
                current_value += char
            
            i += 1
        
        if not values_list:
            continue
        
        print(f"   📊 {len(values_list)} enregistrements à migrer")
        
        # Créer les chunks
        success_count = 0
        for i in range(0, len(values_list), chunk_size):
            chunk_values = values_list[i:i + chunk_size]
            chunk_query = f"INSERT IGNORE INTO `{table_name}` {columns} VALUES " + ", ".join(chunk_values) + ";"
            
            print(f"   📦 Chunk {i//chunk_size + 1}/{(len(values_list) + chunk_size - 1)//chunk_size}...", end=" ")
            
            if execute_ssh_mysql(chunk_query):
                success_count += 1
                print("✅")
            else:
                print("❌")
        
        total_success += success_count
        print(f"   ✅ {success_count}/{(len(values_list) + chunk_size - 1)//chunk_size} chunks exécutés avec succès")
    
    return total_success

def main():
    """Fonction principale"""
    print("🚀 Migration complète des tables réparations")
    print("=" * 60)
    
    # Vérifier le fichier SQL
    sql_file = Path('base_client.sql')
    if not sql_file.exists():
        print("❌ Fichier base_client.sql introuvable")
        sys.exit(1)
    
    # Lire le fichier
    print(f"📖 Lecture du fichier {sql_file}...")
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    # Test de connexion
    print("🔌 Test de connexion...")
    if not execute_ssh_mysql("SELECT 1 as test"):
        print("❌ Impossible de se connecter à la base")
        sys.exit(1)
    print("   ✅ Connexion OK")
    
    try:
        # Désactiver les contraintes
        print("🔓 Désactivation des contraintes...")
        execute_ssh_mysql("SET foreign_key_checks = 0")
        execute_ssh_mysql("SET sql_mode = ''")
        
        # Tables à migrer avec leurs tailles de chunks optimisées
        tables_config = [
            ('reparations', 3),           # Table principale
            ('reparation_attributions', 10), # Attributions
            ('reparation_logs', 10),      # Logs
            ('reparation_sms', 5),        # SMS spécifiques
            ('photos_reparation', 10)     # Photos
        ]
        
        total_migrated = 0
        for table_name, chunk_size in tables_config:
            migrated = migrate_table_chunked(sql_content, table_name, chunk_size)
            total_migrated += migrated
        
        # Réactiver les contraintes
        print("🔒 Réactivation des contraintes...")
        execute_ssh_mysql("SET foreign_key_checks = 1")
        
        # Statistiques finales pour toutes les tables
        print("\n📊 Statistiques finales :")
        
        for table_name, _ in tables_config:
            ssh_cmd = [
                'sshpass', '-p', 'Mamanmaman01#',
                'ssh', '-o', 'StrictHostKeyChecking=no',
                'root@82.29.168.205',
                f'mysql -u root -pMamanmaman01# -e "SELECT COUNT(*) as count FROM geekboard_mdg.{table_name};" 2>/dev/null'
            ]
            
            try:
                result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
                count = result.stdout.strip().split('\n')[-1]
                print(f"   {table_name}: {count} enregistrements")
            except:
                print(f"   {table_name}: Erreur de comptage")
        
        # Statistiques spéciales pour reparations
        print(f"\n📋 Détail réparations par statut et archive :")
        stats_queries = [
            "SELECT statut, COUNT(*) as count FROM geekboard_mdg.reparations GROUP BY statut ORDER BY count DESC;",
            "SELECT archive, COUNT(*) as count FROM geekboard_mdg.reparations GROUP BY archive;"
        ]
        
        for query in stats_queries:
            ssh_cmd = [
                'sshpass', '-p', 'Mamanmaman01#',
                'ssh', '-o', 'StrictHostKeyChecking=no',
                'root@82.29.168.205',
                f'mysql -u root -pMamanmaman01# -e "{query}" 2>/dev/null'
            ]
            
            try:
                result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
                print(result.stdout)
            except:
                print("❌ Erreur statistiques")
        
        print(f"\n✅ Migration terminée ! {total_migrated} chunks traités")
        
    except KeyboardInterrupt:
        print("\n⚠️ Migration interrompue")
    except Exception as e:
        print(f"\n❌ Erreur : {e}")

if __name__ == '__main__':
    main()
