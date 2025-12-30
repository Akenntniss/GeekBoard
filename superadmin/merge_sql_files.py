#!/usr/bin/env python3
"""
Script pour fusionner ajout_structure.sql dans geekboard_complete_structure.sql
- Ajoute les tables manquantes
- Met à jour les structures des tables communes
"""

import re
import sys
from pathlib import Path

def extract_table_definition(content, table_name):
    """Extrait la définition complète d'une table (CREATE TABLE + INSERT + ALTER TABLE + index)"""
    pattern = rf'--\s*Structure de la table `{re.escape(table_name)}`.*?(?=--\s*Structure de la table `|--\s*Index pour la table|--\s*AUTO_INCREMENT|$)'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    
    if not match:
        return None
    
    table_section = match.group(0)
    
    # Extraire aussi les index et AUTO_INCREMENT pour cette table
    index_pattern = rf'--\s*Index pour la table `{re.escape(table_name)}`.*?(?=--\s*Index pour la table `|--\s*AUTO_INCREMENT|$)'
    index_match = re.search(index_pattern, content, re.DOTALL | re.IGNORECASE)
    if index_match:
        table_section += '\n' + index_match.group(0)
    
    autoinc_pattern = rf'--\s*AUTO_INCREMENT pour la table `{re.escape(table_name)}`.*?(?=--\s*AUTO_INCREMENT pour la table `|$)'
    autoinc_match = re.search(autoinc_pattern, content, re.DOTALL | re.IGNORECASE)
    if autoinc_match:
        table_section += '\n' + autoinc_match.group(0)
    
    return table_section.strip()

def extract_view_definition(content, view_name):
    """Extrait la définition complète d'une vue (doublure + vue)"""
    # Chercher la section complète de la vue
    pattern = rf'--\s*Doublure de structure pour la vue `{re.escape(view_name)}`.*?(?=--\s*Doublure de structure pour la vue `|--\s*Structure de la vue `|--\s*Structure de la table `|CREATE\s+TABLE\s+`|$)'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(0).strip()
    
    # Si pas de doublure, chercher juste la vue
    pattern = rf'--\s*Structure de la vue `{re.escape(view_name)}`.*?(?=--\s*Structure de la vue `|--\s*Structure de la table `|CREATE\s+TABLE\s+`|$)'
    match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
    return match.group(0).strip() if match else None

def get_table_names(content):
    """Extrait tous les noms de tables depuis un fichier SQL"""
    pattern = r'CREATE TABLE `([^`]+)`'
    return set(re.findall(pattern, content, re.IGNORECASE))

def find_insert_position(content, existing_tables):
    """Trouve la position où insérer les nouvelles tables (après les tables existantes)"""
    # Chercher la dernière table existante
    last_table_pos = 0
    for table in existing_tables:
        pattern = rf'--\s*Structure de la table `{re.escape(table)}`'
        matches = list(re.finditer(pattern, content, re.IGNORECASE))
        if matches:
            last_match = matches[-1]
            # Trouver la fin de cette table (jusqu'à la prochaine table ou section)
            end_pattern = r'(?=--\s*Structure de la table `|--\s*Index pour la table `|--\s*AUTO_INCREMENT|$)'
            end_match = re.search(end_pattern, content[last_match.end():], re.DOTALL | re.IGNORECASE)
            if end_match:
                pos = last_match.end() + end_match.end()
                if pos > last_table_pos:
                    last_table_pos = pos
    
    # Si on ne trouve pas, chercher la section AUTO_INCREMENT
    autoinc_match = re.search(r'--\s*AUTO_INCREMENT pour la table', content, re.IGNORECASE)
    if autoinc_match:
        return autoinc_match.start()
    
    return last_table_pos if last_table_pos > 0 else len(content)

def main():
    ajout_file = Path('ajout_structure.sql')
    complete_file = Path('geekboard_complete_structure.sql')
    output_file = Path('geekboard_complete_structure.sql.new')
    
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
    
    # Obtenir les listes de tables
    print("🔍 Analyse des tables...")
    ajout_tables = get_table_names(ajout_content)
    complete_tables = get_table_names(complete_content)
    
    missing_tables = ajout_tables - complete_tables
    common_tables = ajout_tables & complete_tables
    
    print(f"✅ Tables dans ajout_structure.sql: {len(ajout_tables)}")
    print(f"✅ Tables dans geekboard_complete_structure.sql: {len(complete_tables)}")
    print(f"❌ Tables manquantes: {len(missing_tables)}")
    print(f"🔄 Tables communes: {len(common_tables)}")
    
    # Exclure les tables de backup
    backup_tables = {t for t in missing_tables if 'backup' in t.lower()}
    missing_tables = missing_tables - backup_tables
    
    if backup_tables:
        print(f"⚠️  Exclusion des tables de backup: {len(backup_tables)}")
        for t in backup_tables:
            print(f"   - {t}")
    
    # Extraire les tables manquantes
    print(f"\n📦 Extraction des {len(missing_tables)} tables manquantes...")
    missing_definitions = {}
    
    for table in sorted(missing_tables):
        print(f"   Extraction: {table}")
        definition = extract_table_definition(ajout_content, table)
        if definition:
            missing_definitions[table] = definition
        else:
            print(f"   ⚠️  Table {table} non trouvée dans ajout_structure.sql")
    
    # Extraire les vues (qui sont dans la liste des tables manquantes)
    print("\n🔍 Recherche des vues...")
    views = ['v_combined_logs', 'vue_garanties_actives', 'mission_stats', 'time_tracking_report', 'user_mission_dashboard']
    view_definitions = {}
    for view in views:
        definition = extract_view_definition(ajout_content, view)
        if definition:
            view_definitions[view] = definition
            print(f"   ✅ Vue trouvée: {view}")
            # Retirer de missing_definitions si elle y est
            if view in missing_definitions:
                del missing_definitions[view]
        else:
            print(f"   ⚠️  Vue non trouvée: {view}")
    
    # Construire le nouveau contenu
    print("\n🔨 Construction du nouveau fichier...")
    
    # Prendre le début du fichier complet (en-tête, etc.)
    header_match = re.search(r'^.*?(?=--\s*Structure de la table)', complete_content, re.DOTALL)
    new_content = header_match.group(0) if header_match else complete_content[:500]
    
    # Trouver où insérer les nouvelles tables
    insert_pos = find_insert_position(complete_content, complete_tables)
    
    # Prendre le contenu jusqu'à la position d'insertion
    new_content = complete_content[:insert_pos]
    
    # Ajouter les tables manquantes
    if missing_definitions:
        new_content += "\n\n-- --------------------------------------------------------\n"
        new_content += "-- Tables ajoutées depuis ajout_structure.sql\n"
        new_content += "-- --------------------------------------------------------\n\n"
        
        for table in sorted(missing_definitions.keys()):
            new_content += missing_definitions[table] + "\n\n"
    
    # Ajouter les vues
    if view_definitions:
        new_content += "\n-- --------------------------------------------------------\n"
        new_content += "-- Vues ajoutées depuis ajout_structure.sql\n"
        new_content += "-- --------------------------------------------------------\n\n"
        
        for view in sorted(view_definitions.keys()):
            new_content += view_definitions[view] + "\n\n"
    
    # Ajouter le reste du fichier original (index, AUTO_INCREMENT, etc.)
    remaining_content = complete_content[insert_pos:]
    
    # Extraire les index et AUTO_INCREMENT des nouvelles tables
    new_indexes = []
    new_autoinc = []
    
    for table in missing_definitions.keys():
        # Index
        index_pattern = rf'--\s*Index pour la table `{re.escape(table)}`.*?(?=--\s*Index pour la table `|--\s*AUTO_INCREMENT|$)'
        index_match = re.search(index_pattern, ajout_content, re.DOTALL | re.IGNORECASE)
        if index_match:
            new_indexes.append(index_match.group(0))
        
        # AUTO_INCREMENT
        autoinc_pattern = rf'--\s*AUTO_INCREMENT pour la table `{re.escape(table)}`.*?(?=--\s*AUTO_INCREMENT pour la table `|$)'
        autoinc_match = re.search(autoinc_pattern, ajout_content, re.DOTALL | re.IGNORECASE)
        if autoinc_match:
            new_autoinc.append(autoinc_match.group(0))
    
    # Insérer les nouveaux index avant les AUTO_INCREMENT existants
    if new_indexes:
        autoinc_pos = remaining_content.find('--\n-- AUTO_INCREMENT')
        if autoinc_pos > 0:
            new_content += remaining_content[:autoinc_pos]
            new_content += "\n\n-- --------------------------------------------------------\n"
            new_content += "-- Index pour les nouvelles tables\n"
            new_content += "-- --------------------------------------------------------\n\n"
            new_content += "\n\n".join(new_indexes) + "\n\n"
            remaining_content = remaining_content[autoinc_pos:]
        else:
            new_content += "\n\n-- --------------------------------------------------------\n"
            new_content += "-- Index pour les nouvelles tables\n"
            new_content += "-- --------------------------------------------------------\n\n"
            new_content += "\n\n".join(new_indexes) + "\n\n"
    
    # Ajouter les nouveaux AUTO_INCREMENT
    if new_autoinc:
        autoinc_pos = remaining_content.find('--\n-- AUTO_INCREMENT')
        if autoinc_pos > 0:
            new_content += remaining_content[:autoinc_pos]
            new_content += "\n\n-- --------------------------------------------------------\n"
            new_content += "-- AUTO_INCREMENT pour les nouvelles tables\n"
            new_content += "-- --------------------------------------------------------\n\n"
            new_content += "\n\n".join(new_autoinc) + "\n\n"
            remaining_content = remaining_content[autoinc_pos:]
        else:
            new_content += "\n\n-- --------------------------------------------------------\n"
            new_content += "-- AUTO_INCREMENT pour les nouvelles tables\n"
            new_content += "-- --------------------------------------------------------\n\n"
            new_content += "\n\n".join(new_autoinc) + "\n\n"
    
    # Ajouter le reste
    new_content += remaining_content
    
    # Sauvegarder
    print(f"\n💾 Sauvegarde dans {output_file}...")
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    # Vérifier le résultat
    new_tables = get_table_names(new_content)
    print(f"\n✅ Fichier créé avec succès!")
    print(f"   Tables dans le nouveau fichier: {len(new_tables)}")
    print(f"   Tables ajoutées: {len(missing_tables)}")
    print(f"   Vues ajoutées: {len(view_definitions)}")
    
    print(f"\n📝 Fichier sauvegardé: {output_file}")
    print(f"   Pour remplacer l'original: mv {output_file} {complete_file}")

if __name__ == '__main__':
    main()

