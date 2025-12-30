#!/usr/bin/env python3
"""
Script pour mettre à jour les structures des tables communes
en ajoutant les colonnes manquantes depuis ajout_structure.sql
"""

import re
import sys
from pathlib import Path

def extract_column_definition(content, table_name, column_name):
    """Extrait la définition complète d'une colonne"""
    pattern = rf'CREATE TABLE `{re.escape(table_name)}`\s*\((.*?)\)\s*ENGINE'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    
    if not match:
        return None
    
    table_def = match.group(1)
    
    # Chercher la colonne spécifique
    # Pattern pour trouver la ligne de la colonne (peut être sur plusieurs lignes)
    col_pattern = rf'`{re.escape(column_name)}`\s+[^,)]+(?:\([^)]+\))?[^,)]*'
    col_match = re.search(col_pattern, table_def, re.IGNORECASE)
    
    if col_match:
        # Extraire la ligne complète jusqu'à la virgule ou la parenthèse fermante
        start = col_match.start()
        # Trouver la fin de la définition (virgule ou parenthèse)
        remaining = table_def[start:]
        end_match = re.search(r'[,)]', remaining)
        if end_match:
            return remaining[:end_match.start()].strip()
        return remaining.strip()
    
    return None

def get_table_columns(content, table_name):
    """Retourne la liste des colonnes d'une table"""
    pattern = rf'CREATE TABLE `{re.escape(table_name)}`\s*\((.*?)\)\s*ENGINE'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    
    if not match:
        return []
    
    table_def = match.group(1)
    columns = []
    
    # Extraire toutes les colonnes
    for line in table_def.split('\n'):
        line = line.strip()
        if line.startswith('`') and '`' in line:
            col_match = re.match(r'`([^`]+)`', line)
            if col_match:
                columns.append(col_match.group(1))
    
    return columns

def find_column_insert_position(table_def, after_column=None):
    """Trouve où insérer une nouvelle colonne dans la définition d'une table"""
    if after_column:
        # Chercher la colonne après laquelle insérer
        pattern = rf'`{re.escape(after_column)}`[^,)]+(?:\([^)]+\))?[^,)]*[,)]'
        match = re.search(pattern, table_def, re.IGNORECASE)
        if match:
            # Trouver la fin de cette ligne (virgule)
            pos = match.end()
            # Chercher la virgule
            comma_pos = table_def.find(',', pos)
            if comma_pos > 0:
                return comma_pos + 1
            return pos
    
    # Sinon, insérer avant la dernière parenthèse (avant PRIMARY KEY, etc.)
    # Chercher la dernière colonne avant les contraintes
    last_col_match = None
    for match in re.finditer(r'`([^`]+)`\s+[^,)]+(?:\([^)]+\))?[^,)]*[,)]', table_def, re.IGNORECASE):
        last_col_match = match
    
    if last_col_match:
        pos = last_col_match.end()
        comma_pos = table_def.find(',', pos)
        if comma_pos > 0:
            return comma_pos + 1
        return pos
    
    # Dernier recours : avant la parenthèse fermante
    last_paren = table_def.rfind(')')
    return last_paren if last_paren > 0 else len(table_def)

def update_table_structure(content, table_name, new_columns):
    """Met à jour la structure d'une table en ajoutant les colonnes manquantes"""
    # Pattern amélioré pour capturer toute la définition de table avec parenthèses imbriquées
    pattern = rf'CREATE TABLE `{re.escape(table_name)}`\s*\('
    match = re.search(pattern, content, re.IGNORECASE)
    
    if not match:
        print(f"   ⚠️  Table {table_name} non trouvée")
        return content
    
    start_pos = match.start()
    paren_start = match.end() - 1  # Position de la parenthèse ouvrante
    
    # Compter les parenthèses pour trouver la fermeture correcte
    paren_count = 0
    pos = paren_start
    while pos < len(content):
        if content[pos] == '(':
            paren_count += 1
        elif content[pos] == ')':
            paren_count -= 1
            if paren_count == 0:
                # Trouvé la parenthèse fermante
                paren_end = pos
                break
        pos += 1
    else:
        print(f"   ⚠️  Parenthèse fermante non trouvée pour {table_name}")
        return content
    
    # Extraire la définition complète
    table_def = content[start_pos:paren_end + 1]
    original_def = table_def
    
    # Extraire juste le contenu entre parenthèses
    inner_content = content[paren_start + 1:paren_end]
    
    # Ajouter chaque colonne manquante
    for col_name, col_def in new_columns.items():
        # Vérifier si la colonne existe déjà
        if f'`{col_name}`' in inner_content:
            continue
        
        # Trouver où insérer (avant la dernière parenthèse)
        # Chercher la dernière colonne
        last_col_match = None
        for match in re.finditer(r'`([^`]+)`\s+[^,)]+(?:\([^)]+\))?[^,)]*', inner_content, re.IGNORECASE):
            last_col_match = match
        
        if last_col_match:
            # Insérer après la dernière colonne
            insert_pos = last_col_match.end()
            # Chercher la virgule ou la fin
            comma_pos = inner_content.find(',', insert_pos)
            if comma_pos > 0:
                insert_pos = comma_pos + 1
            new_col = f',\n  `{col_name}` {col_def}'
            inner_content = inner_content[:insert_pos] + new_col + inner_content[insert_pos:]
        else:
            # Insérer au début si pas de colonnes trouvées
            new_col = f'`{col_name}` {col_def}'
            if inner_content.strip():
                inner_content = new_col + ',\n  ' + inner_content
            else:
                inner_content = new_col
    
    # Reconstruire la définition complète
    new_table_def = content[start_pos:paren_start + 1] + inner_content + ')' + content[paren_end + 1:paren_end + 100]
    # Trouver où se termine vraiment la définition (jusqu'à ENGINE)
    engine_match = re.search(r'\)\s*ENGINE', new_table_def, re.IGNORECASE)
    if engine_match:
        engine_pos = engine_match.end() - 6  # Position avant ENGINE
        # Trouver la fin complète
        remaining = content[paren_end + 1:]
        end_match = re.search(r'ENGINE[^;]+;', remaining, re.IGNORECASE)
        if end_match:
            new_table_def = content[start_pos:paren_start + 1] + inner_content + ')' + remaining[:end_match.end()]
        else:
            new_table_def = content[start_pos:paren_start + 1] + inner_content + ')' + remaining.split('\n')[0]
    
    # Remplacer dans le contenu
    if new_table_def != original_def:
        content = content[:start_pos] + new_table_def + content[paren_end + 1 + len(remaining.split('\n')[0]):]
        print(f"   ✅ Table {table_name} mise à jour avec {len(new_columns)} colonne(s)")
    else:
        print(f"   ⚠️  Aucune modification pour {table_name}")
    
    return content

def main():
    ajout_file = Path('ajout_structure.sql')
    complete_file = Path('geekboard_complete_structure.sql.new')
    output_file = Path('geekboard_complete_structure.sql.final')
    
    if not ajout_file.exists():
        print(f"❌ Fichier {ajout_file} introuvable")
        sys.exit(1)
    
    if not complete_file.exists():
        print(f"❌ Fichier {complete_file} introuvable")
        sys.exit(1)
    
    print("📖 Lecture des fichiers...")
    with open(ajout_file, 'r', encoding='utf-8') as f:
        ajout_content = f.read()
    
    with open(complete_file, 'r', encoding='utf-8') as f:
        complete_content = f.read()
    
    # Tables à mettre à jour avec leurs colonnes manquantes
    tables_to_update = {
        'users': ['cagnotte', 'points_experience', 'score_total'],
        'reparations': ['date_garantie_debut', 'date_garantie_fin', 'date_signature_devis', 'garantie_id', 'signature_devis']
    }
    
    print("\n🔧 Mise à jour des structures de tables...")
    
    for table_name, missing_cols in tables_to_update.items():
        print(f"\n📋 Table: {table_name}")
        
        # Obtenir les colonnes existantes
        existing_cols = get_table_columns(complete_content, table_name)
        ajout_cols = get_table_columns(ajout_content, table_name)
        
        # Identifier les colonnes vraiment manquantes
        really_missing = [col for col in missing_cols if col not in existing_cols]
        
        if not really_missing:
            print(f"   ✅ Toutes les colonnes sont déjà présentes")
            continue
        
        # Extraire les définitions des colonnes manquantes
        new_columns = {}
        for col_name in really_missing:
            col_def = extract_column_definition(ajout_content, table_name, col_name)
            if col_def:
                # Nettoyer la définition (enlever le nom de la colonne et les backticks)
                col_def = col_def.replace(f'`{col_name}`', '').strip()
                new_columns[col_name] = col_def
                print(f"   ✅ Colonne trouvée: {col_name}")
            else:
                print(f"   ⚠️  Colonne {col_name} non trouvée dans ajout_structure.sql")
        
        # Mettre à jour la table
        if new_columns:
            complete_content = update_table_structure(complete_content, table_name, new_columns)
    
    # Sauvegarder
    print(f"\n💾 Sauvegarde dans {output_file}...")
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(complete_content)
    
    print(f"\n✅ Fichier mis à jour avec succès!")
    print(f"   Fichier: {output_file}")
    print(f"   Pour remplacer: mv {output_file} geekboard_complete_structure.sql")

if __name__ == '__main__':
    main()

