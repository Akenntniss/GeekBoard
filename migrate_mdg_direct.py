#!/usr/bin/env python3
"""
Script de migration directe des données depuis base_client.sql vers mdg.servo.tools
Extraction et insertion directe via SSH
"""

import re
import subprocess
import sys
from pathlib import Path

def execute_ssh_mysql(query, database="geekboard_mdg"):
    """Exécute une requête MySQL via SSH"""
    ssh_cmd = [
        'sshpass', '-p', 'Mamanmaman01#',
        'ssh', '-o', 'StrictHostKeyChecking=no',
        'root@82.29.168.205',
        f'mysql -u root -pMamanmaman01# -e "{query}" {database} 2>/dev/null'
    ]
    
    try:
        result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
        return result.stdout.strip()
    except subprocess.CalledProcessError as e:
        print(f"❌ Erreur MySQL : {e}")
        return None

def extract_insert_data(sql_content, table_name):
    """Extrait les données INSERT d'une table"""
    # Pattern pour trouver les INSERT INTO
    pattern = rf'INSERT INTO `{table_name}`[^;]+;'
    matches = re.findall(pattern, sql_content, re.DOTALL | re.IGNORECASE)
    return matches

def migrate_clients_direct(sql_content):
    """Migration directe des clients"""
    print("📋 Migration des clients...")
    
    # Extraire les INSERT statements
    client_inserts = extract_insert_data(sql_content, 'clients')
    print(f"   Trouvé {len(client_inserts)} statements INSERT pour clients")
    
    if not client_inserts:
        print("   ⚠️ Aucun INSERT trouvé pour clients")
        return
    
    success_count = 0
    for insert_stmt in client_inserts:
        # Modifier l'INSERT pour utiliser INSERT IGNORE
        modified_stmt = insert_stmt.replace('INSERT INTO', 'INSERT IGNORE INTO')
        
        # Exécuter via SSH
        result = execute_ssh_mysql(modified_stmt)
        if result is not None:
            success_count += 1
    
    print(f"   ✅ {success_count} statements clients exécutés")

def migrate_users_direct(sql_content):
    """Migration directe des utilisateurs"""
    print("👥 Migration des utilisateurs...")
    
    # Extraire les INSERT statements
    user_inserts = extract_insert_data(sql_content, 'users')
    print(f"   Trouvé {len(user_inserts)} statements INSERT pour users")
    
    if not user_inserts:
        print("   ⚠️ Aucun INSERT trouvé pour users")
        return
    
    success_count = 0
    for insert_stmt in user_inserts:
        # Modifier l'INSERT pour utiliser INSERT IGNORE
        modified_stmt = insert_stmt.replace('INSERT INTO', 'INSERT IGNORE INTO')
        
        # Exécuter via SSH
        result = execute_ssh_mysql(modified_stmt)
        if result is not None:
            success_count += 1
    
    print(f"   ✅ {success_count} statements utilisateurs exécutés")

def migrate_reparations_direct(sql_content):
    """Migration directe des réparations"""
    print("🔧 Migration des réparations...")
    
    # Extraire les INSERT statements
    reparation_inserts = extract_insert_data(sql_content, 'reparations')
    print(f"   Trouvé {len(reparation_inserts)} statements INSERT pour reparations")
    
    if not reparation_inserts:
        print("   ⚠️ Aucun INSERT trouvé pour reparations")
        return
    
    success_count = 0
    for insert_stmt in reparation_inserts:
        # Modifier l'INSERT pour utiliser INSERT IGNORE
        modified_stmt = insert_stmt.replace('INSERT INTO', 'INSERT IGNORE INTO')
        
        # Exécuter via SSH
        result = execute_ssh_mysql(modified_stmt)
        if result is not None:
            success_count += 1
    
    print(f"   ✅ {success_count} statements réparations exécutés")

def migrate_sms_direct(sql_content):
    """Migration directe des SMS"""
    print("📱 Migration des SMS...")
    
    # Extraire les INSERT statements
    sms_inserts = extract_insert_data(sql_content, 'sms_logs')
    print(f"   Trouvé {len(sms_inserts)} statements INSERT pour sms_logs")
    
    if not sms_inserts:
        print("   ⚠️ Aucun INSERT trouvé pour sms_logs")
        return
    
    success_count = 0
    for insert_stmt in sms_inserts:
        # Modifier l'INSERT pour utiliser INSERT IGNORE
        modified_stmt = insert_stmt.replace('INSERT INTO', 'INSERT IGNORE INTO')
        
        # Exécuter via SSH
        result = execute_ssh_mysql(modified_stmt)
        if result is not None:
            success_count += 1
    
    print(f"   ✅ {success_count} statements SMS exécutés")

def get_final_stats():
    """Récupère les statistiques finales"""
    print("📊 Statistiques finales :")
    
    stats_query = """
    SELECT 'Clients' as Type, COUNT(*) as Total FROM clients
    UNION ALL
    SELECT 'Utilisateurs', COUNT(*) FROM users  
    UNION ALL
    SELECT 'Réparations', COUNT(*) FROM reparations
    UNION ALL
    SELECT 'SMS', COUNT(*) FROM sms_logs;
    """
    
    result = execute_ssh_mysql(stats_query)
    if result:
        print(result)
    else:
        print("   ❌ Impossible de récupérer les statistiques")

def main():
    """Fonction principale"""
    print("🚀 Migration directe des données vers mdg.servo.tools")
    print("=" * 60)
    
    # Vérifier que le fichier SQL existe
    sql_file = Path('base_client.sql')
    if not sql_file.exists():
        print("❌ Fichier base_client.sql introuvable")
        sys.exit(1)
    
    # Lire le fichier SQL
    print(f"📖 Lecture du fichier {sql_file}...")
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    print(f"   Taille : {len(sql_content)} caractères")
    
    # Test de connexion
    print("🔌 Test de connexion...")
    result = execute_ssh_mysql("SELECT 1 as test")
    if result != "test\n1":
        print("❌ Impossible de se connecter à la base")
        sys.exit(1)
    print("   ✅ Connexion OK")
    
    try:
        # Désactiver les contraintes de clés étrangères temporairement
        print("🔓 Désactivation des contraintes...")
        execute_ssh_mysql("SET foreign_key_checks = 0")
        
        # Migration des données
        migrate_clients_direct(sql_content)
        migrate_users_direct(sql_content)
        migrate_reparations_direct(sql_content)
        migrate_sms_direct(sql_content)
        
        # Réactiver les contraintes
        print("🔒 Réactivation des contraintes...")
        execute_ssh_mysql("SET foreign_key_checks = 1")
        
        # Statistiques finales
        get_final_stats()
        
        print("\n" + "=" * 60)
        print("✅ Migration directe terminée !")
        
    except KeyboardInterrupt:
        print("\n⚠️ Migration interrompue")
    except Exception as e:
        print(f"\n❌ Erreur : {e}")

if __name__ == '__main__':
    main()
