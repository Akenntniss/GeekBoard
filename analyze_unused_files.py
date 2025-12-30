
import os
import re
import json

ROOT = '/Users/admin/Documents/GeekBoard'
EXCLUDE_DIRS = {'.git', 'node_modules', 'vendor', '.gemini', '.vscode', 'fpdf_temp', 'logs', 'backups', 'backup'}
EXTENSIONS = {'.php', '.html', '.js', '.css'}

files_info = {}
references = set()

suspicious_patterns = [
    r'(backup|old|copie|copy|test|temp|tmp|draft|\.bak|\.old|\.save|_\d{4,}|^[a-z]\.php$)',
    r'(2|3|4|5|fixed|final|working|new|simple|simplified|corrected|restored)\.(php|html|js|css)$'
]

def is_suspicious(filename):
    for pattern in suspicious_patterns:
        if re.search(pattern, filename, re.IGNORECASE):
            return True
    return False

def clean_path(path):
    path = path.split('?')[0]
    path = path.split('#')[0]
    return os.path.basename(path)

# 1. Scan Directory
for root, dirs, files in os.walk(ROOT):
    # Modify dirs in-place to skip excluded
    dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]
    
    for file in files:
        file_path = os.path.join(root, file)
        rel_path = os.path.relpath(file_path, ROOT)
        ext = os.path.splitext(file)[1].lower()
        
        if file.startswith('.'):
            continue

        files_info[rel_path] = {
            'path': rel_path,
            'suspicious': is_suspicious(file)
        }
        
        # 2. Parse for references
        if ext in EXTENSIONS:
            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    
                    # PHP Includes/Requires
                    # match include 'foo.php', include("foo.php")
                    matches = re.findall(r'(?:include|require)(?:_once)?\s*[\(\'"\s](.+?)[\'"\)]', content, re.IGNORECASE)
                    for m in matches:
                        references.add(clean_path(m))
                        
                    # Links (href, src, action, url)
                    matches = re.findall(r'(?:href|src|action|url)\s*=\s*["\']([^"\']+)["\']', content, re.IGNORECASE)
                    for m in matches:
                        references.add(clean_path(m))
                        
                    # Fetch / AJAX / Window.location
                    matches = re.findall(r'(?:fetch|open|window\.location\.href)\s*[\(=]\s*["\']([^"\']+)["\']', content, re.IGNORECASE)
                    for m in matches:
                        references.add(clean_path(m))
                        
            except Exception as e:
                pass

# 3. Analyze Orphans
orphans = []
for rel_path, info in files_info.items():
    basename = os.path.basename(rel_path)
    # Check if basename is in references
    # Note: this is a weak check (e.g. index.php is usually an entry point, not referenced)
    # But for "useless files" scanning, high chance an unreferenced non-index file is suspicious.
    
    if basename not in references:
        # Filter out common entry points roughly
        if basename in ['index.php', 'login.php', 'logout.php', 'cron.php', 'app.php']:
            continue
        orphans.append(rel_path)

output = {
    'total_files': len(files_info),
    'suspicious_files': [f for f, i in files_info.items() if i['suspicious']],
    'potential_orphans': orphans
}
print(json.dumps(output, indent=2))
