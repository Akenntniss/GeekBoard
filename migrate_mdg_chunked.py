#!/usr/bin/env python3
"""
Script de migration par chunks - découpe les gros INSERT en plus petits
"""

import re
import subprocess
import sys
from pathlib import Path

def execute_ssh_mysql(query, database="geekboard_mdg"):
    """Exécute une requête MySQL via SSH"""
    # Échapper les guillemets dans la requête
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
        if e.stderr:
            print(f"   Détail : {e.stderr[:200]}...")
        return False

def parse_values_from_insert(insert_statement):
    """Parse les valeurs d'un INSERT statement"""
    # Extraire la partie VALUES
    values_match = re.search(r'VALUES\s*(.*);?$', insert_statement, re.DOTALL | re.IGNORECASE)
    if not values_match:
        return []
    
    values_text = values_match.group(1).strip()
    if values_text.endswith(';'):
        values_text = values_text[:-1]
    
    # Diviser par les parenthèses de niveau supérieur
    values = []
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
                # Fin d'une valeur
                values.append(current_value.strip())
                current_value = ""
                # Ignorer la virgule suivante
                i += 1
                while i < len(values_text) and values_text[i] in [',', ' ', '\n', '\r', '\t']:
                    i += 1
                i -= 1  # Compenser l'incrémentation à la fin de la boucle
        else:
            current_value += char
        
        i += 1
    
    return values

def create_chunked_insert(table_name, columns, values_list, chunk_size=10):
    """Crée des INSERT statements par chunks"""
    base_query = f"INSERT IGNORE INTO `{table_name}` {columns} VALUES "
    
    chunks = []
    for i in range(0, len(values_list), chunk_size):
        chunk_values = values_list[i:i + chunk_size]
        chunk_query = base_query + ", ".join(chunk_values) + ";"
        chunks.append(chunk_query)
    
    return chunks

def migrate_table_chunked(sql_content, table_name, chunk_size=5):
    """Migration d'une table par chunks"""
    print(f"📋 Migration de {table_name} par chunks de {chunk_size}...")
    
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
        values_list = parse_values_from_insert(insert_stmt)
        if not values_list:
            print(f"   ⚠️ Aucune valeur trouvée dans l'INSERT pour {table_name}")
            continue
        
        print(f"   📊 {len(values_list)} enregistrements à migrer")
        
        # Créer les chunks
        chunks = create_chunked_insert(table_name, columns, values_list, chunk_size)
        
        # Exécuter chaque chunk
        success_count = 0
        for i, chunk in enumerate(chunks):
            print(f"   📦 Chunk {i+1}/{len(chunks)}...", end=" ")
            
            if execute_ssh_mysql(chunk):
                success_count += 1
                print("✅")
            else:
                print("❌")
        
        total_success += success_count
        print(f"   ✅ {success_count}/{len(chunks)} chunks exécutés avec succès")
    
    return total_success

def main():
    """Fonction principale"""
    print("🚀 Migration par chunks vers mdg.servo.tools")
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
    
    print(f"   Taille : {len(sql_content)} caractères")
    
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
        
        # Migration par chunks avec tailles adaptées
        tables_config = [
            ('clients', 20),      # Plus gros chunks pour les clients (données simples)
            ('users', 5),         # Petits chunks pour les utilisateurs (mots de passe)
            ('reparations', 3),   # Très petits chunks (beaucoup de colonnes)
            ('sms_logs', 2)       # Très petits chunks (messages longs)
        ]
        
        total_migrated = 0
        for table_name, chunk_size in tables_config:
            migrated = migrate_table_chunked(sql_content, table_name, chunk_size)
            total_migrated += migrated
        
        # Réactiver les contraintes
        print("🔒 Réactivation des contraintes...")
        execute_ssh_mysql("SET foreign_key_checks = 1")
        
        # Statistiques finales
        print("\n📊 Statistiques finales :")
        stats_query = """
        SELECT 'Clients' as Type, COUNT(*) as Total FROM clients
        UNION ALL
        SELECT 'Utilisateurs', COUNT(*) FROM users  
        UNION ALL
        SELECT 'Réparations', COUNT(*) FROM reparations
        UNION ALL
        SELECT 'SMS', COUNT(*) FROM sms_logs;
        """
        
        # Exécuter et afficher les stats
        ssh_cmd = [
            'sshpass', '-p', 'Mamanmaman01#',
            'ssh', '-o', 'StrictHostKeyChecking=no',
            'root@82.29.168.205',
            f'mysql -u root -pMamanmaman01# -e "{stats_query}" geekboard_mdg 2>/dev/null'
        ]
        
        try:
            result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
            print(result.stdout)
        except:
            print("   ❌ Impossible de récupérer les statistiques")
        
        print(f"\n✅ Migration terminée ! {total_migrated} chunks traités")
        
    except KeyboardInterrupt:
        print("\n⚠️ Migration interrompue")
    except Exception as e:
        print(f"\n❌ Erreur : {e}")

if __name__ == '__main__':
    main()
