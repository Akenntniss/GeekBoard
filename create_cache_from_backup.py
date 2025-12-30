
import csv
import json
import os

BACKUP_FILE = "utopya_catalogue_backup_20251223_214932.csv"
CACHE_FILE = "utopya_products_cache.json"

def create_cache():
    if not os.path.exists(BACKUP_FILE):
        print(f"Erreur : fichier {BACKUP_FILE} non trouvé")
        return

    urls = set()
    print(f"Lecture de {BACKUP_FILE}...")
    
    with open(BACKUP_FILE, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            if 'URL' in row and row['URL']:
                urls.add(row['URL'])
    
    print(f"Trouvé {len(urls)} URLs uniques.")
    
    if urls:
        with open(CACHE_FILE, 'w') as f:
            json.dump(list(urls), f)
        print(f"✅ Cache créé : {CACHE_FILE}")
    else:
        print("Aucune URL trouvée.")

if __name__ == "__main__":
    create_cache()
