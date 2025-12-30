#!/usr/bin/env python3
"""
Script pour importer le catalogue Mobilax dans la base de données
"""

import json
import mysql.connector
import sys

# Configuration de la base de données
DB_CONFIG = {
    'host': '82.29.168.205',
    'user': 'gb_mdg',
    'password': 'Admin123!',
    'database': 'geekboard_mdg',
    'charset': 'utf8mb4'
}

def main():
    print("=" * 60)
    print("🔄 Import du catalogue Mobilax dans la base de données")
    print("=" * 60)
    
    # Charger le fichier JSON
    print("\n📂 Chargement du fichier mobilax_catalogue_clean.json...")
    try:
        with open('mobilax_catalogue_clean.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
        print(f"   ✅ {len(data)} produits chargés")
    except Exception as e:
        print(f"   ❌ Erreur: {e}")
        sys.exit(1)
    
    # Connexion à la base de données
    print("\n🔌 Connexion à la base de données...")
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        print("   ✅ Connecté")
    except Exception as e:
        print(f"   ❌ Erreur de connexion: {e}")
        print("\n⚠️ Vérifiez la configuration de la base de données dans ce script")
        sys.exit(1)
    
    # Créer la table si elle n'existe pas
    print("\n📋 Création de la table catalogue_fournisseur...")
    try:
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS `catalogue_fournisseur` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `fournisseur_id` INT NOT NULL,
                `name` VARCHAR(500) NOT NULL,
                `url` VARCHAR(1000) DEFAULT NULL,
                `price` DECIMAL(10,2) DEFAULT NULL,
                `reference` VARCHAR(100) DEFAULT NULL,
                `stock` VARCHAR(50) DEFAULT NULL,
                `type` VARCHAR(100) DEFAULT NULL,
                `device_type` VARCHAR(100) DEFAULT NULL,
                `brand` VARCHAR(100) DEFAULT NULL,
                `series` VARCHAR(100) DEFAULT NULL,
                `model` VARCHAR(200) DEFAULT NULL,
                `date_import` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_fournisseur` (`fournisseur_id`),
                INDEX `idx_type` (`type`),
                INDEX `idx_brand` (`brand`),
                INDEX `idx_device_type` (`device_type`),
                INDEX `idx_reference` (`reference`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)
        conn.commit()
        print("   ✅ Table créée/vérifiée")
    except Exception as e:
        print(f"   ❌ Erreur: {e}")
    
    # Vérifier/créer le fournisseur Mobilax
    print("\n🏢 Vérification du fournisseur Mobilax...")
    try:
        cursor.execute("SELECT id FROM fournisseurs WHERE nom = 'Mobilax'")
        result = cursor.fetchone()
        
        if result:
            fournisseur_id = result[0]
            print(f"   ✅ Fournisseur existant (ID: {fournisseur_id})")
        else:
            cursor.execute("INSERT INTO fournisseurs (nom, url) VALUES ('Mobilax', 'https://www.mobilax.fr')")
            conn.commit()
            fournisseur_id = cursor.lastrowid
            print(f"   ✅ Fournisseur créé (ID: {fournisseur_id})")
    except Exception as e:
        print(f"   ❌ Erreur: {e}")
        conn.close()
        sys.exit(1)
    
    # Supprimer les anciens produits Mobilax
    print("\n🗑️ Suppression des anciens produits Mobilax...")
    try:
        cursor.execute("DELETE FROM catalogue_fournisseur WHERE fournisseur_id = %s", (fournisseur_id,))
        deleted = cursor.rowcount
        conn.commit()
        print(f"   ✅ {deleted} anciens produits supprimés")
    except Exception as e:
        print(f"   ⚠️ Erreur (table peut-être vide): {e}")
    
    # Insérer les produits
    print("\n📦 Insertion des produits...")
    
    insert_sql = """
        INSERT INTO catalogue_fournisseur 
        (fournisseur_id, name, url, price, reference, stock, type, device_type, brand, series, model)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    
    imported = 0
    errors = 0
    batch_size = 1000
    batch = []
    
    for i, item in enumerate(data):
        try:
            # Nettoyer le prix
            price = None
            if item.get('price'):
                price_str = str(item['price']).replace('€', '').replace(',', '.').strip()
                try:
                    price = float(price_str)
                except:
                    price = None
            
            batch.append((
                fournisseur_id,
                item.get('name', '')[:500],
                item.get('url', '')[:1000] if item.get('url') else None,
                price,
                item.get('reference', '')[:100] if item.get('reference') else None,
                item.get('stock', '')[:50] if item.get('stock') else None,
                item.get('type', '')[:100] if item.get('type') else None,
                item.get('device_type', '')[:100] if item.get('device_type') else None,
                item.get('brand', '')[:100] if item.get('brand') else None,
                item.get('series', '')[:100] if item.get('series') else None,
                item.get('model', '')[:200] if item.get('model') else None
            ))
            
            # Insérer par lots
            if len(batch) >= batch_size:
                cursor.executemany(insert_sql, batch)
                conn.commit()
                imported += len(batch)
                batch = []
                print(f"   📊 {imported}/{len(data)} produits importés...", end='\r')
        
        except Exception as e:
            errors += 1
            if errors < 5:
                print(f"\n   ⚠️ Erreur ligne {i}: {e}")
    
    # Insérer le reste
    if batch:
        cursor.executemany(insert_sql, batch)
        conn.commit()
        imported += len(batch)
    
    print(f"\n   ✅ {imported} produits importés ({errors} erreurs)")
    
    # Vérification finale
    print("\n📊 Vérification finale...")
    cursor.execute("SELECT COUNT(*) FROM catalogue_fournisseur WHERE fournisseur_id = %s", (fournisseur_id,))
    total = cursor.fetchone()[0]
    print(f"   ✅ Total dans la base: {total} produits")
    
    cursor.execute("""
        SELECT type, COUNT(*) as count 
        FROM catalogue_fournisseur 
        WHERE fournisseur_id = %s 
        GROUP BY type
    """, (fournisseur_id,))
    
    print("\n   Par type:")
    for row in cursor.fetchall():
        print(f"      - {row[0]}: {row[1]}")
    
    conn.close()
    
    print("\n" + "=" * 60)
    print("🎉 Import terminé avec succès !")
    print("   Accédez à la page via: ?page=catalogue_fournisseur")
    print("=" * 60)

if __name__ == "__main__":
    main()
