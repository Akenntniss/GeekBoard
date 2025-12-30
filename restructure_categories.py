#!/usr/bin/env python3
"""
Script pour restructurer les catégories du catalogue Mobilax
"""

import json
import csv

print('🔄 Chargement des données...')
with open('mobilax_catalogue.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

print(f'📊 {len(data)} produits chargés')
print('🔄 Restructuration des catégories...')

new_data = []

for p in data:
    cat = p.get('category', '')
    parts = [x.strip() for x in cat.split('>')]
    
    # Nouvelle structure
    new_product = {
        'name': p.get('name', ''),
        'url': p.get('url', ''),
        'price': p.get('price', ''),
        'reference': p.get('reference', ''),
        'stock': p.get('stock', ''),
        'type': '',           # Pièces détachées, Accessoires, Protections, Outillages
        'device_type': '',    # Téléphonie, Tablette, Montre, Ordinateur, Terminal
        'brand': '',          # Marque
        'series': '',         # Série/Gamme
        'model': ''           # Modèle spécifique
    }
    
    # Niveau 1: Type de produit
    if len(parts) > 1:
        type_map = {
            'Pieces-detachees': 'Pièces détachées',
            'Accessoires': 'Accessoires',
            'Protections': 'Protections',
            'Outillages': 'Outillages'
        }
        new_product['type'] = type_map.get(parts[1], parts[1])
    
    # Niveau 2: Type d'appareil
    if len(parts) > 2:
        new_product['device_type'] = parts[2]
    
    # Niveau 3: Marque
    if len(parts) > 3:
        new_product['brand'] = parts[3]
    
    # Niveau 4: Série/Gamme
    if len(parts) > 4:
        new_product['series'] = parts[4]
    
    # Niveau 5: Modèle
    if len(parts) > 5:
        new_product['model'] = parts[5]
    
    new_data.append(new_product)

# Statistiques
print(f'\n📊 Statistiques après restructuration:')
types = {}
for p in new_data:
    t = p['type']
    types[t] = types.get(t, 0) + 1

print(f'\n📦 Par type de produit:')
for t, count in sorted(types.items(), key=lambda x: -x[1]):
    print(f'  - {t}: {count}')

brands = {}
for p in new_data:
    b = p['brand']
    if b:
        brands[b] = brands.get(b, 0) + 1

print(f'\n🏷️ Top 15 marques:')
for b, count in sorted(brands.items(), key=lambda x: -x[1])[:15]:
    print(f'  - {b}: {count}')

device_types = {}
for p in new_data:
    d = p['device_type']
    if d:
        device_types[d] = device_types.get(d, 0) + 1

print(f'\n📱 Par type d\'appareil:')
for d, count in sorted(device_types.items(), key=lambda x: -x[1]):
    print(f'  - {d}: {count}')

# Sauvegarder le nouveau CSV
output_csv = 'mobilax_catalogue_clean.csv'
output_json = 'mobilax_catalogue_clean.json'

print(f'\n💾 Sauvegarde CSV...')
with open(output_csv, 'w', newline='', encoding='utf-8') as f:
    fieldnames = ['name', 'url', 'price', 'reference', 'stock', 'type', 'device_type', 'brand', 'series', 'model']
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(new_data)

print(f'💾 Sauvegarde JSON...')
with open(output_json, 'w', encoding='utf-8') as f:
    json.dump(new_data, f, ensure_ascii=False, indent=2)

print(f'\n✅ Fichiers créés:')
print(f'  - {output_csv}')
print(f'  - {output_json}')
print(f'\n🎉 {len(new_data)} produits restructurés!')
