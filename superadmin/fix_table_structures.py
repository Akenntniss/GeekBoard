#!/usr/bin/env python3
"""
Script pour remplacer complètement les définitions de tables communes
par celles de ajout_structure.sql
"""

import re
from pathlib import Path

def extract_complete_table_definition(content, table_name):
    """Extrait la définition complète d'une table jusqu'à ENGINE"""
    pattern = rf'CREATE TABLE `{re.escape(table_name)}`.*?ENGINE[^;]+;'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    return match.group(0) if match else None

def replace_table_definition(content, table_name, new_definition):
    """Remplace la définition d'une table dans le contenu"""
    pattern = rf'CREATE TABLE `{re.escape(table_name)}`.*?ENGINE[^;]+;'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    
    if match:
        content = content[:match.start()] + new_definition + content[match.end():]
        return content, True
    return content, False

def main():
    ajout_file = Path('ajout_structure.sql')
    complete_file = Path('geekboard_complete_structure.sql.new')
    output_file = Path('geekboard_complete_structure.sql.final')
    
    print("📖 Lecture des fichiers...")
    with open(ajout_file, 'r', encoding='utf-8') as f:
        ajout_content = f.read()
    
    with open(complete_file, 'r', encoding='utf-8') as f:
        complete_content = f.read()
    
    # Tables à remplacer complètement
    tables_to_replace = ['users', 'reparations']
    
    print("\n🔧 Remplacement des définitions de tables...")
    
    for table_name in tables_to_replace:
        print(f"\n📋 Table: {table_name}")
        
        # Extraire la nouvelle définition depuis ajout_structure.sql
        new_def = extract_complete_table_definition(ajout_content, table_name)
        
        if not new_def:
            print(f"   ⚠️  Table {table_name} non trouvée dans ajout_structure.sql")
            continue
        
        # Remplacer dans le fichier complet
        complete_content, replaced = replace_table_definition(complete_content, table_name, new_def)
        
        if replaced:
            print(f"   ✅ Table {table_name} remplacée avec succès")
        else:
            print(f"   ⚠️  Table {table_name} non trouvée dans geekboard_complete_structure.sql")
    
    # Sauvegarder
    print(f"\n💾 Sauvegarde dans {output_file}...")
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(complete_content)
    
    print(f"\n✅ Fichier mis à jour avec succès!")
    print(f"   Fichier: {output_file}")

if __name__ == '__main__':
    main()

