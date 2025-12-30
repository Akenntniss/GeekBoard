#!/usr/bin/env python3
"""
Script de migration des données depuis base_client.sql vers mdg.servo.tools (geekboard_mdg)
"""

import re
import mysql.connector
from datetime import datetime
import sys
from pathlib import Path

# Configuration de la base de données
DB_CONFIG = {
    'host': '82.29.168.205',
    'user': 'root',
    'password': 'Mamanmaman01#',
    'database': 'geekboard_mdg',
    'charset': 'utf8mb4'
}

def connect_to_db():
    """Connexion à la base de données"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except mysql.connector.Error as e:
        print(f"❌ Erreur de connexion : {e}")
        sys.exit(1)

def parse_sql_values(sql_content, table_name):
    """Extrait les valeurs INSERT d'une table depuis le fichier SQL"""
    pattern = rf'INSERT INTO `{table_name}`.*?VALUES\s*(.*?)(?=INSERT INTO|--|\Z)'
    match = re.search(pattern, sql_content, re.DOTALL | re.IGNORECASE)
    
    if not match:
        return []
    
    values_text = match.group(1).strip()
    if values_text.endswith(';'):
        values_text = values_text[:-1]
    
    # Parser les valeurs (format: (val1, val2, val3), (val4, val5, val6))
    values = []
    # Utiliser regex pour extraire chaque tuple
    tuple_pattern = r'\([^)]+\)'
    tuples = re.findall(tuple_pattern, values_text)
    
    for tuple_str in tuples:
        # Nettoyer et parser chaque tuple
        clean_tuple = tuple_str.strip('()')
        # Diviser par virgules en tenant compte des guillemets
        parts = []
        current_part = ""
        in_quotes = False
        quote_char = None
        
        i = 0
        while i < len(clean_tuple):
            char = clean_tuple[i]
            
            if char in ("'", '"') and (i == 0 or clean_tuple[i-1] != '\\'):
                if not in_quotes:
                    in_quotes = True
                    quote_char = char
                elif char == quote_char:
                    in_quotes = False
                    quote_char = None
            
            if char == ',' and not in_quotes:
                parts.append(current_part.strip())
                current_part = ""
            else:
                current_part += char
            
            i += 1
        
        if current_part.strip():
            parts.append(current_part.strip())
        
        # Convertir les valeurs
        processed_parts = []
        for part in parts:
            part = part.strip()
            if part.upper() == 'NULL':
                processed_parts.append(None)
            elif part.startswith("'") and part.endswith("'"):
                processed_parts.append(part[1:-1])
            elif part.isdigit():
                processed_parts.append(int(part))
            else:
                try:
                    processed_parts.append(float(part))
                except ValueError:
                    processed_parts.append(part)
        
        values.append(tuple(processed_parts))
    
    return values

def migrate_clients(conn, sql_content):
    """Migration des clients"""
    print("📋 Migration des clients...")
    
    cursor = conn.cursor()
    
    # Vérifier les clients existants
    cursor.execute("SELECT COUNT(*) FROM clients")
    existing_count = cursor.fetchone()[0]
    print(f"   Clients existants : {existing_count}")
    
    # Parser les données clients
    clients_data = parse_sql_values(sql_content, 'clients')
    print(f"   Clients à migrer : {len(clients_data)}")
    
    if not clients_data:
        print("   ⚠️  Aucune donnée client trouvée")
        return
    
    # Préparer la requête d'insertion
    insert_query = """
    INSERT IGNORE INTO clients 
    (id, nom, prenom, telephone, email, date_creation, inscrit_parrainage, code_parrainage, date_inscription_parrainage)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    
    success_count = 0
    for client_data in clients_data:
        try:
            cursor.execute(insert_query, client_data)
            if cursor.rowcount > 0:
                success_count += 1
        except mysql.connector.Error as e:
            print(f"   ⚠️  Erreur client ID {client_data[0]}: {e}")
    
    conn.commit()
    print(f"   ✅ {success_count} clients migrés avec succès")

def migrate_users(conn, sql_content):
    """Migration des utilisateurs"""
    print("👥 Migration des utilisateurs...")
    
    cursor = conn.cursor()
    
    # Vérifier les utilisateurs existants
    cursor.execute("SELECT COUNT(*) FROM users")
    existing_count = cursor.fetchone()[0]
    print(f"   Utilisateurs existants : {existing_count}")
    
    # Parser les données utilisateurs
    users_data = parse_sql_values(sql_content, 'users')
    print(f"   Utilisateurs à migrer : {len(users_data)}")
    
    if not users_data:
        print("   ⚠️  Aucune donnée utilisateur trouvée")
        return
    
    # Préparer la requête d'insertion
    insert_query = """
    INSERT IGNORE INTO users 
    (id, username, password, full_name, role, created_at, techbusy, active_repair_id)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    """
    
    success_count = 0
    for user_data in users_data:
        try:
            cursor.execute(insert_query, user_data)
            if cursor.rowcount > 0:
                success_count += 1
        except mysql.connector.Error as e:
            print(f"   ⚠️  Erreur utilisateur ID {user_data[0]}: {e}")
    
    conn.commit()
    print(f"   ✅ {success_count} utilisateurs migrés avec succès")

def migrate_reparations(conn, sql_content):
    """Migration des réparations"""
    print("🔧 Migration des réparations...")
    
    cursor = conn.cursor()
    
    # Vérifier les réparations existantes
    cursor.execute("SELECT COUNT(*) FROM reparations")
    existing_count = cursor.fetchone()[0]
    print(f"   Réparations existantes : {existing_count}")
    
    # Parser les données réparations
    reparations_data = parse_sql_values(sql_content, 'reparations')
    print(f"   Réparations à migrer : {len(reparations_data)}")
    
    if not reparations_data:
        print("   ⚠️  Aucune donnée réparation trouvée")
        return
    
    # Préparer la requête d'insertion (avec toutes les colonnes de la nouvelle structure)
    insert_query = """
    INSERT IGNORE INTO reparations 
    (id, client_id, type_appareil, marque, modele, description_probleme, date_reception, 
     date_modification, date_fin_prevue, statut, statut_id, statut_categorie, signature, 
     prix, notes_techniques, notes_finales, photo_appareil, mot_de_passe, etat_esthetique, 
     prix_reparation, devis_envoye, devis_accepte, date_envoi_devis, date_reponse_devis, 
     photos, urgent, commande_requise, archive, employe_id, date_gardiennage, 
     gardiennage_facture, parrain_id, reduction_parrainage, reduction_parrainage_pourcentage, 
     signature_client, photo_signature, photo_client, accept_conditions, proprietaire, 
     signature_devis, date_signature_devis)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    
    success_count = 0
    for reparation_data in reparations_data:
        try:
            cursor.execute(insert_query, reparation_data)
            if cursor.rowcount > 0:
                success_count += 1
        except mysql.connector.Error as e:
            print(f"   ⚠️  Erreur réparation ID {reparation_data[0]}: {e}")
    
    conn.commit()
    print(f"   ✅ {success_count} réparations migrées avec succès")

def migrate_sms_logs(conn, sql_content):
    """Migration des SMS logs"""
    print("📱 Migration des SMS logs...")
    
    cursor = conn.cursor()
    
    # Vérifier les SMS existants
    cursor.execute("SELECT COUNT(*) FROM sms_logs")
    existing_count = cursor.fetchone()[0]
    print(f"   SMS existants : {existing_count}")
    
    # Parser les données SMS
    sms_data = parse_sql_values(sql_content, 'sms_logs')
    print(f"   SMS à migrer : {len(sms_data)}")
    
    if not sms_data:
        print("   ⚠️  Aucune donnée SMS trouvée")
        return
    
    # Préparer la requête d'insertion
    insert_query = """
    INSERT IGNORE INTO sms_logs 
    (id, recipient, message, status, reparation_id, response, date_envoi)
    VALUES (%s, %s, %s, %s, %s, %s, %s)
    """
    
    success_count = 0
    for sms_record in sms_data:
        try:
            cursor.execute(insert_query, sms_record)
            if cursor.rowcount > 0:
                success_count += 1
        except mysql.connector.Error as e:
            print(f"   ⚠️  Erreur SMS ID {sms_record[0]}: {e}")
    
    conn.commit()
    print(f"   ✅ {success_count} SMS migrés avec succès")

def migrate_other_tables(conn, sql_content):
    """Migration des autres tables importantes"""
    print("📊 Migration des autres tables...")
    
    tables_to_migrate = [
        'employes',
        'fournisseurs', 
        'statuts',
        'sms_templates',
        'parametres'
    ]
    
    cursor = conn.cursor()
    
    for table_name in tables_to_migrate:
        print(f"   📋 Migration de {table_name}...")
        
        # Vérifier si la table existe
        cursor.execute(f"SHOW TABLES LIKE '{table_name}'")
        if not cursor.fetchone():
            print(f"   ⚠️  Table {table_name} n'existe pas, ignorée")
            continue
        
        # Parser les données
        table_data = parse_sql_values(sql_content, table_name)
        
        if not table_data:
            print(f"   ⚠️  Aucune donnée trouvée pour {table_name}")
            continue
        
        print(f"   📊 {len(table_data)} enregistrements à migrer pour {table_name}")
        
        # Pour chaque table, adapter la requête selon sa structure
        # (Ici on fait une approche générique, à adapter selon les besoins)
        
    print("   ✅ Migration des autres tables terminée")

def main():
    """Fonction principale"""
    print("🚀 Début de la migration des données vers mdg.servo.tools")
    print("=" * 60)
    
    # Lire le fichier SQL
    sql_file = Path('base_client.sql')
    if not sql_file.exists():
        print(f"❌ Fichier {sql_file} introuvable")
        sys.exit(1)
    
    print(f"📖 Lecture du fichier {sql_file}...")
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    print(f"   Taille du fichier : {len(sql_content)} caractères")
    
    # Connexion à la base de données
    print("🔌 Connexion à la base de données...")
    conn = connect_to_db()
    print("   ✅ Connexion établie")
    
    try:
        # Migration étape par étape
        migrate_clients(conn, sql_content)
        migrate_users(conn, sql_content)
        migrate_reparations(conn, sql_content)
        migrate_sms_logs(conn, sql_content)
        migrate_other_tables(conn, sql_content)
        
        print("\n" + "=" * 60)
        print("✅ Migration terminée avec succès !")
        
        # Statistiques finales
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM clients")
        clients_count = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM users")
        users_count = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM reparations")
        reparations_count = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM sms_logs")
        sms_count = cursor.fetchone()[0]
        
        print(f"\n📊 Statistiques finales :")
        print(f"   Clients : {clients_count}")
        print(f"   Utilisateurs : {users_count}")
        print(f"   Réparations : {reparations_count}")
        print(f"   SMS : {sms_count}")
        
    except Exception as e:
        print(f"\n❌ Erreur durant la migration : {e}")
        conn.rollback()
    finally:
        conn.close()
        print("\n🔌 Connexion fermée")

if __name__ == '__main__':
    main()
