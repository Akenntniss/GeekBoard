import os
import re
import json

ROOT_DIR = '/Users/admin/Documents/GeekBoard'

# Entry points mapping provided by user
ENTRY_POINTS = {
    '/index.php': 'pages/accueil-modern.php', # Handled specially
    'pages/statut_rapide.php': 'pages/statut_rapide.php',
    'pages/devis.php': 'pages/devis.php',
    'pages/reparations.php': 'pages/reparations.php',
    'classes/RepairController.php': 'classes/RepairController.php',
    'pages/ajouter_reparation.php': 'pages/ajouter_reparation.php',
    'pages/commande_moderne.php': 'pages/commande_moderne.php',
    'pages/taches_moderne.php': 'pages/taches_moderne.php',
    'pages/rachat_moderne.php': 'pages/rachat_moderne.php',
    'pages/article_kb_moderne.php': 'pages/article_kb_moderne.php',
    'pages/inventaire_moderne.php': 'pages/inventaire_moderne.php',
    'pages/admin_missions_moderne.php': 'pages/admin_missions_moderne.php',
    'pages/mes_missions_moderne.php': 'pages/mes_missions_moderne.php',
    'pages/sms_templates.php': 'pages/sms_templates.php',
    'pages/campagne_sms.php': 'pages/campagne_sms.php',
    'pages/sms_historique.php': 'pages/sms_historique.php',
    'pages/clients.php': 'pages/clients.php',
    'pages/employes.php': 'pages/employes.php',
    'pages/reparation_log_moderne.php': 'pages/reparation_log_moderne.php',
    'pages/bug-reports.php': 'pages/bug-reports.php',
    'pages/presence_gestion_moderne.php': 'pages/presence_gestion_moderne.php',
    'pages/admin_timetracking_moderne.php': 'pages/admin_timetracking_moderne.php',
    'pages/parametre_moderne.php': 'pages/parametre_moderne.php',
    # Adding index.php as a primary entry point to capture global includes
    'index.php': 'index.php'
}

# Also need to make sure we include the files the user said "URL -> Source"
# The user's list implies these are the main files they care about.

all_files_to_visit = set()
for k, v in ENTRY_POINTS.items():
    if v.startswith('/'): v = v[1:]
    all_files_to_visit.add(v)
    if k.endswith('.php') and k != v:
         if k.startswith('/'): k = k[1:]
         all_files_to_visit.add(k)


visited_files = set()
found_dependencies = set()
missing_files = set()

def resolve_path(current_file, relative_path):
    # Handle BASE_PATH and __DIR__ simulation
    # If path starts with /, assume relative to root (web root)
    # If path doesn't start with /, relative to current_file directory
    
    # Clean up PHP concatenation quotes/spaces
    relative_path = relative_path.replace("'.'", "").replace('". "', "")
    
    # Handle BASE_PATH
    if relative_path.startswith('/'):
        # In web context, / usually means root.
        # But in file include context, usually / is absolute path.
        # Here we assume / refers to project root for assets, 
        # but for includes we need to be careful.
        # However, PHP includes usually use __DIR__ or BASE_PATH.
        candidate = os.path.join(ROOT_DIR, relative_path.lstrip('/'))
        if os.path.exists(candidate):
            return candidate
            
    # Relative to current file
    current_dir = os.path.dirname(os.path.join(ROOT_DIR, current_file))
    candidate = os.path.normpath(os.path.join(current_dir, relative_path))
    if os.path.exists(candidate):
        return candidate
        
    # Relative to ROOT (if not found relative to file)
    candidate_root = os.path.normpath(os.path.join(ROOT_DIR, relative_path))
    if os.path.exists(candidate_root):
        return candidate_root
        
    return None

def scan_file(file_path):
    rel_path = os.path.relpath(file_path, ROOT_DIR)
    if rel_path in visited_files:
        return
    visited_files.add(rel_path)
    
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return

    # REGEX patterns
    
    # PHP Includes
    # require_once BASE_PATH . '/config/database.php';
    # include __DIR__ . '/../includes/functions.php';
    # include('header.php');
    
    # 1. BASE_PATH . '...'
    includes_base = re.findall(r"(?:include|require)(?:_once)?\s*\(?\s*BASE_PATH\s*\.\s*['\"](.+?)['\"]", content)
    for inc in includes_base:
        full_path = os.path.normpath(os.path.join(ROOT_DIR, inc.lstrip('/')))
        if os.path.exists(full_path):
            found_dependencies.add(os.path.relpath(full_path, ROOT_DIR))
            scan_file(full_path)
        else:
            missing_files.add(inc)

    # 2. __DIR__ . '...'
    includes_dir = re.findall(r"(?:include|require)(?:_once)?\s*\(?\s*__DIR__\s*\.\s*['\"](.+?)['\"]", content)
    for inc in includes_dir:
        current_dir = os.path.dirname(file_path)
        full_path = os.path.normpath(os.path.join(current_dir, inc.lstrip('/')))
        if os.path.exists(full_path):
            found_dependencies.add(os.path.relpath(full_path, ROOT_DIR))
            scan_file(full_path)
        else:
            missing_files.add(inc + f" (from {rel_path})")

    # 3. Simple strings: include 'file.php' or include '/path/file.php'
    includes_simple = re.findall(r"(?:include|require)(?:_once)?\s*\(?\s*['\"](.+?)['\"]", content)
    for inc in includes_simple:
        # Skip if it looks like a variable concatenation or internal marker
        if '..' in inc or '/' in inc or inc.endswith('.php'):
             resolved = resolve_path(rel_path, inc)
             if resolved:
                 found_dependencies.add(os.path.relpath(resolved, ROOT_DIR))
                 scan_file(resolved)
             else:
                 # Check if it is just a filename in the same dir
                 curr_dir = os.path.dirname(file_path)
                 local_candidate = os.path.join(curr_dir, inc)
                 if os.path.exists(local_candidate):
                     found_dependencies.add(os.path.relpath(local_candidate, ROOT_DIR))
                     scan_file(local_candidate)
                 else:
                     pass
                     # print(f"Could not resolve include: {inc} in {rel_path}")

    # Assets
    # <link href="...">
    links = re.findall(r"<link[^>]+href=['\"]([^'\"]+)['\"]", content)
    for l in links:
        if not l.startswith('http'):
            # clean query params
            l = l.split('?')[0]
            resolved = resolve_path(rel_path, l)
            if resolved:
                found_dependencies.add(os.path.relpath(resolved, ROOT_DIR))

    # <script src="...">
    scripts = re.findall(r"<script[^>]+src=['\"]([^'\"]+)['\"]", content)
    for s in scripts:
        if not s.startswith('http'):
            s = s.split('?')[0]
            resolved = resolve_path(rel_path, s)
            if resolved:
                found_dependencies.add(os.path.relpath(resolved, ROOT_DIR))

    # <img src="...">
    imgs = re.findall(r"<img[^>]+src=['\"]([^'\"]+)['\"]", content)
    for i in imgs:
        if not i.startswith('http') and not i.startswith('data:'):
            i = i.split('?')[0]
            resolved = resolve_path(rel_path, i)
            if resolved:
                found_dependencies.add(os.path.relpath(resolved, ROOT_DIR))
                
    # AJAX / Fetch (Heuristic)
    # Looking for strings ending in .php inside JS/Script blocks
    # This is fuzzy but might catch things like 'ajax/update_status.php'
    
    # Find all strings that look like relative paths to .php files
    php_refs = re.findall(r"['\"](?:\.\/|\/)?([a-zA-Z0-9_\-\/]+\.php)['\"]", content)
    for p in php_refs:
        if p == 'index.php': continue # too common
        resolved = resolve_path(rel_path, p)
        if resolved and resolved != file_path:
             found_dependencies.add(os.path.relpath(resolved, ROOT_DIR))
             scan_file(resolved) # Recurse into AJAX handlers too

# Start Process
for f in all_files_to_visit:
    full_path = os.path.join(ROOT_DIR, f)
    if os.path.exists(full_path):
        found_dependencies.add(f)
        scan_file(full_path)
    else:
        print(f"Warning: Entry point not found: {f}")

# Output results
sorted_deps = sorted(list(found_dependencies))
print(json.dumps(sorted_deps, indent=2))
