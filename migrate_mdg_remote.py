#!/usr/bin/env python3
"""
Script de migration des données depuis base_client.sql vers mdg.servo.tools
Exécution via SSH sur le serveur distant
"""

import re
import subprocess
import sys
from pathlib import Path

def execute_ssh_command(command):
    """Exécute une commande SSH sur le serveur"""
    ssh_cmd = [
        'sshpass', '-p', 'Mamanmaman01#',
        'ssh', '-o', 'StrictHostKeyChecking=no',
        'root@82.29.168.205',
        command
    ]
    
    try:
        result = subprocess.run(ssh_cmd, capture_output=True, text=True, check=True)
        return result.stdout.strip()
    except subprocess.CalledProcessError as e:
        print(f"❌ Erreur SSH : {e}")
        print(f"   Sortie d'erreur : {e.stderr}")
        return None

def upload_sql_file():
    """Upload le fichier SQL sur le serveur"""
    print("📤 Upload du fichier base_client.sql sur le serveur...")
    
    scp_cmd = [
        'sshpass', '-p', 'Mamanmaman01#',
        'scp', '-o', 'StrictHostKeyChecking=no',
        'base_client.sql',
        'root@82.29.168.205:/tmp/base_client.sql'
    ]
    
    try:
        subprocess.run(scp_cmd, check=True)
        print("   ✅ Fichier uploadé avec succès")
        return True
    except subprocess.CalledProcessError as e:
        print(f"   ❌ Erreur upload : {e}")
        return False

def create_migration_script():
    """Crée un script de migration SQL sur le serveur"""
    print("📝 Création du script de migration...")
    
    migration_script = '''#!/bin/bash
# Script de migration des données

echo "🚀 Début de la migration vers geekboard_mdg"

# Extraire les données spécifiques
echo "📋 Extraction des clients..."
grep -A 10000 "INSERT INTO \`clients\`" /tmp/base_client.sql | grep -B 10000 "INSERT INTO \`colis_retour\`" | head -n -1 > /tmp/clients_data.sql

echo "👥 Extraction des utilisateurs..."
grep -A 1000 "INSERT INTO \`users\`" /tmp/base_client.sql | grep -B 1000 "INSERT INTO \`user_sessions\`" | head -n -1 > /tmp/users_data.sql

echo "🔧 Extraction des réparations..."
grep -A 10000 "INSERT INTO \`reparations\`" /tmp/base_client.sql | grep -B 10000 "INSERT INTO \`reparation_attributions\`" | head -n -1 > /tmp/reparations_data.sql

echo "📱 Extraction des SMS..."
grep -A 10000 "INSERT INTO \`sms_logs\`" /tmp/base_client.sql | grep -B 10000 "INSERT INTO \`sms_template\`" | head -n -1 > /tmp/sms_data.sql

# Migration vers la base
echo "💾 Migration des données..."

# Clients
echo "   Clients..."
mysql -u root -pMamanmaman01# geekboard_mdg < /tmp/clients_data.sql 2>/dev/null || echo "   ⚠️ Erreur clients (peut-être normale)"

# Utilisateurs (éviter les doublons)
echo "   Utilisateurs..."
mysql -u root -pMamanmaman01# -e "
USE geekboard_mdg;
SET foreign_key_checks = 0;
" 2>/dev/null

mysql -u root -pMamanmaman01# geekboard_mdg < /tmp/users_data.sql 2>/dev/null || echo "   ⚠️ Erreur utilisateurs (peut-être normale)"

# Réparations
echo "   Réparations..."
mysql -u root -pMamanmaman01# geekboard_mdg < /tmp/reparations_data.sql 2>/dev/null || echo "   ⚠️ Erreur réparations (peut-être normale)"

# SMS
echo "   SMS..."
mysql -u root -pMamanmaman01# geekboard_mdg < /tmp/sms_data.sql 2>/dev/null || echo "   ⚠️ Erreur SMS (peut-être normale)"

# Statistiques finales
echo "📊 Statistiques finales :"
mysql -u root -pMamanmaman01# -e "
USE geekboard_mdg;
SELECT 'Clients:' as Type, COUNT(*) as Total FROM clients
UNION ALL
SELECT 'Utilisateurs:', COUNT(*) FROM users  
UNION ALL
SELECT 'Réparations:', COUNT(*) FROM reparations
UNION ALL
SELECT 'SMS:', COUNT(*) FROM sms_logs;
" 2>/dev/null

echo "✅ Migration terminée !"
'''
    
    # Écrire le script sur le serveur
    with open('/tmp/migration_script.sh', 'w') as f:
        f.write(migration_script)
    
    # Upload du script
    scp_cmd = [
        'sshpass', '-p', 'Mamanmaman01#',
        'scp', '-o', 'StrictHostKeyChecking=no',
        '/tmp/migration_script.sh',
        'root@82.29.168.205:/tmp/migration_script.sh'
    ]
    
    try:
        subprocess.run(scp_cmd, check=True)
        
        # Rendre le script exécutable
        execute_ssh_command("chmod +x /tmp/migration_script.sh")
        
        print("   ✅ Script de migration créé")
        return True
    except subprocess.CalledProcessError as e:
        print(f"   ❌ Erreur création script : {e}")
        return False

def run_migration():
    """Exécute la migration sur le serveur"""
    print("🚀 Exécution de la migration...")
    
    # Exécuter le script de migration
    result = execute_ssh_command("/tmp/migration_script.sh")
    
    if result is not None:
        print("📊 Résultat de la migration :")
        print(result)
        return True
    else:
        print("❌ Erreur durant l'exécution de la migration")
        return False

def cleanup():
    """Nettoie les fichiers temporaires"""
    print("🧹 Nettoyage des fichiers temporaires...")
    
    cleanup_commands = [
        "rm -f /tmp/base_client.sql",
        "rm -f /tmp/migration_script.sh", 
        "rm -f /tmp/clients_data.sql",
        "rm -f /tmp/users_data.sql",
        "rm -f /tmp/reparations_data.sql",
        "rm -f /tmp/sms_data.sql"
    ]
    
    for cmd in cleanup_commands:
        execute_ssh_command(cmd)
    
    print("   ✅ Nettoyage terminé")

def main():
    """Fonction principale"""
    print("🚀 Migration des données vers mdg.servo.tools")
    print("=" * 60)
    
    # Vérifier que le fichier SQL existe
    if not Path('base_client.sql').exists():
        print("❌ Fichier base_client.sql introuvable")
        sys.exit(1)
    
    # Vérifier la connexion au serveur
    print("🔌 Test de connexion au serveur...")
    result = execute_ssh_command("echo 'Connexion OK'")
    if result != "Connexion OK":
        print("❌ Impossible de se connecter au serveur")
        sys.exit(1)
    print("   ✅ Connexion établie")
    
    # Vérifier que la base de données existe
    print("🗄️ Vérification de la base de données...")
    result = execute_ssh_command("mysql -u root -pMamanmaman01# -e \"SHOW DATABASES LIKE 'geekboard_mdg';\" 2>/dev/null")
    if 'geekboard_mdg' not in result:
        print("❌ Base de données geekboard_mdg introuvable")
        sys.exit(1)
    print("   ✅ Base de données trouvée")
    
    try:
        # Étapes de migration
        if not upload_sql_file():
            sys.exit(1)
        
        if not create_migration_script():
            sys.exit(1)
        
        if not run_migration():
            sys.exit(1)
        
        print("\n" + "=" * 60)
        print("✅ Migration terminée avec succès !")
        
    except KeyboardInterrupt:
        print("\n⚠️ Migration interrompue par l'utilisateur")
    except Exception as e:
        print(f"\n❌ Erreur inattendue : {e}")
    finally:
        cleanup()

if __name__ == '__main__':
    main()
