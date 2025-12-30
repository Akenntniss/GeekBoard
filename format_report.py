
import json

with open('unused_files_raw.json', 'r') as f:
    data = json.load(f)

suspicious = sorted(data['suspicious_files'])
orphans = sorted(data['potential_orphans'])

# Filter orphans that are already in suspicious to avoid duplicates 
# (though my script separates them logic-wise, let's be safe)
orphans = [o for o in orphans if o not in suspicious]
 
# Group suspicious files
backups = []
tests = []
temps = []
others = []

for f in suspicious:
    lower = f.lower()
    if 'backup' in lower or '.bak' in lower or '.old' in lower or 'copie' in lower or 'copy' in lower or 'restored' in lower:
        backups.append(f)
    elif 'test' in lower or 'debug' in lower:
        tests.append(f)
    elif 'temp' in lower or 'tmp' in lower:
        temps.append(f)
    else:
        others.append(f)

md_content = f"""# Analyse Complète du Code Source

Voici le résultat de l'analyse de votre codebase GeekBoard.
J'ai identifié **{len(suspicious)}** fichiers suspects (backups, tests, copies) et **{len(orphans)}** fichiers potentiellement orphelins (jamais référencés dans le code).

db_structure.sql et autres fichiers SQL critiques ne sont pas inclus dans cette suppression automatique suggérée sans vérification.

## 1. Fichiers Suspects (Total: {len(suspicious)})
Ces fichiers sont identifiés par leur nom (backup, test, copy, etc.). Ils sont probablement inutiles.

### Backups & Copies ({len(backups)})
<details>
<summary>Voir la liste ({len(backups)})</summary>

```text
{chr(10).join(backups)}
```
</details>

### Tests & Debug ({len(tests)})
<details>
<summary>Voir la liste ({len(tests)})</summary>

```text
{chr(10).join(tests)}
```
</details>

### Temporaires ({len(temps)})
<details>
<summary>Voir la liste ({len(temps)})</summary>

```text
{chr(10).join(temps)}
```
</details>

### Autres Suspects (Numérotés, "fixed", "final") ({len(others)})
<details>
<summary>Voir la liste ({len(others)})</summary>

```text
{chr(10).join(others)}
```
</details>

## 2. Fichiers Orphelins ({len(orphans)})
Ces fichiers ne semblent pas être référencés (include, link, src) par d'autres fichiers scannés.
**Attention :** Certains peuvent être des points d'entrée légitimes (ex: webhooks, pages accessibles directement par URL mais non linkées).

<details>
<summary>Voir la liste COMPLÈTE ({len(orphans)})</summary>

```text
{chr(10).join(orphans)}
```
</details>
"""

with open('analysis_report.md', 'w') as f:
    f.write(md_content)

print("Report generated.")
