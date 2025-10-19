# Correction du Problème de Détection du Magasin MKMKMK

## Problème Initial

Après les corrections du modal de recherche, l'accès au magasin `mkmkmk` via [https://mkmkmk.mdgeek.top/pages/login_auto.php?shop_id=63](https://mkmkmk.mdgeek.top/pages/login_auto.php?shop_id=63) retournait l'erreur :

```
❌ Magasin non reconnu
Sous-domaine détecté: mkmkmk
Domaine complet: mkmkmk.mdgeek.top
Veuillez contacter l'administrateur.
```

## Analyse du Problème

### 1. Mapping Manquant
Le fichier `pages/login_auto.php` ne contenait pas d'entrée pour le sous-domaine `mkmkmk` dans le mapping des magasins.

### 2. Problème de Détection de Sous-domaine
Le code utilisait directement `$_SERVER["SHOP_SUBDOMAIN"]` qui n'est pas définie automatiquement par le serveur web, au lieu d'extraire le sous-domaine depuis `HTTP_HOST`.

## Solutions Implementées

### 1. Ajout du Mapping pour MKMKMK

**Ajouté dans `pages/login_auto.php` :**
```php
$shop_mapping = [
    // ... autres magasins ...
    'kliop' => ['id' => 54, 'name' => 'kliop', 'db' => 'geekboard_kliop'],
    'mkmkmk' => ['id' => 63, 'name' => 'mkmkmk', 'db' => 'geekboard_mkmkmk']  // ← AJOUTÉ
];
```

### 2. Correction de la Détection de Sous-domaine

**Ancien code (défaillant) :**
```php
$shop_subdomain = $_SERVER["SHOP_SUBDOMAIN"] ?? "unknown";
$host = $_SERVER["HTTP_HOST"] ?? "";
```

**Nouveau code (fonctionnel) :**
```php
$host = $_SERVER["HTTP_HOST"] ?? "";

// Fonction de détection du sous-domaine
function detectShopSubdomain($host) {
    // Vérifier d'abord la variable d'environnement FastCGI (depuis Nginx)
    if (isset($_SERVER['SHOP_SUBDOMAIN']) && !empty($_SERVER['SHOP_SUBDOMAIN'])) {
        return $_SERVER['SHOP_SUBDOMAIN'];
    }
    
    // Sinon, analyser l'en-tête HTTP_HOST
    if (!empty($host)) {
        // Retirer le www. si présent
        $host = preg_replace('/^www\./', '', $host);
        
        // Extraire le sous-domaine pour les domaines *.mdgeek.top
        if (preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)) {
            return $matches[1];
        }
    }
    
    return 'unknown';
}

$shop_subdomain = detectShopSubdomain($host);
```

## Logique de Détection

### Processus de Détection
1. **Vérification FastCGI** : Cherche `$_SERVER['SHOP_SUBDOMAIN']` (définie par Nginx si configurée)
2. **Extraction depuis HTTP_HOST** : Utilise une regex pour extraire le sous-domaine de `*.mdgeek.top`
3. **Suppression du www** : Gère automatiquement les domaines avec `www.`
4. **Fallback** : Retourne `'unknown'` si aucune détection réussie

### Regex Utilisée
```php
preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)
```
- **`^([^.]+)`** : Capture tout caractère sauf le point au début
- **`\.mdgeek\.top$`** : Match exact avec `.mdgeek.top` à la fin

### Exemples de Détection
| Domaine d'entrée | Sous-domaine détecté | Statut |
|------------------|---------------------|--------|
| `mkmkmk.mdgeek.top` | `mkmkmk` | ✅ Reconnu |
| `www.mkmkmk.mdgeek.top` | `mkmkmk` | ✅ Reconnu |
| `pscannes.mdgeek.top` | `pscannes` | ✅ Reconnu |
| `invalid.domain.com` | `unknown` | ❌ Non reconnu |

## Configuration du Magasin MKMKMK

### Informations du Magasin
- **ID :** 63
- **Nom :** mkmkmk  
- **Base de données :** `geekboard_mkmkmk`
- **Sous-domaine :** `mkmkmk`
- **URL complète :** `https://mkmkmk.mdgeek.top`

### Vérification de la Base de Données
```sql
-- Base de données confirmée existante
SHOW DATABASES LIKE 'geekboard_mkmkmk';
-- ✅ geekboard_mkmkmk existe
```

## Processus de Déploiement

### 1. Sauvegarde
```bash
# Création d'une sauvegarde avant modification
cp pages/login_auto.php pages/login_auto.php.backup_$(date +%Y%m%d_%H%M%S)
```

### 2. Modification Locale
- Ajout de l'entrée `mkmkmk` dans le mapping
- Implémentation de la fonction `detectShopSubdomain()`
- Test local de la logique

### 3. Déploiement
```bash
# Upload du fichier modifié
scp login_auto.php root@82.29.168.205:/var/www/mdgeek.top/pages/

# Correction des permissions
chmod 644 pages/login_auto.php
chown www-data:www-data pages/login_auto.php
```

## Script de Test Créé

Un script de diagnostic a été créé : `test_detection_mkmkmk.php`

### Fonctionnalités du Test
- **Test de détection** : Vérifie différents formats de domaines
- **Validation du mapping** : Confirme que `mkmkmk` est reconnu
- **Test de connexion DB** : Vérifie l'accès à `geekboard_mkmkmk`
- **Affichage des variables** : Montre `HTTP_HOST` et `SHOP_SUBDOMAIN`

### Utilisation du Test
```
https://mkmkmk.mdgeek.top/test_detection_mkmkmk.php
```

## Résultat Final

### ✅ Fonctionnalités Corrigées
- **Détection automatique** : Le sous-domaine `mkmkmk` est correctement détecté
- **Mapping reconnu** : Le magasin ID 63 est associé à `mkmkmk`
- **Base de données** : Connexion à `geekboard_mkmkmk` fonctionnelle
- **Interface de connexion** : Formulaire d'authentification affiché
- **Gestion des erreurs** : Messages clairs en cas de problème

### URLs Fonctionnelles
- **Connexion :** `https://mkmkmk.mdgeek.top/pages/login_auto.php?shop_id=63`
- **Accueil :** `https://mkmkmk.mdgeek.top/` (après connexion)
- **Test :** `https://mkmkmk.mdgeek.top/test_detection_mkmkmk.php`

## Prévention des Erreurs Futures

### Bonnes Pratiques
1. **Toujours ajouter le mapping** lors de la création d'un nouveau magasin
2. **Tester la détection** avec le script de diagnostic
3. **Vérifier la base de données** avant ajout du mapping
4. **Utiliser la fonction de détection** au lieu de variables serveur directes

### Template pour Nouveau Magasin
```php
// À ajouter dans $shop_mapping de login_auto.php
'nouveau_shop' => ['id' => XXX, 'name' => 'Nom du Shop', 'db' => 'geekboard_nouveau_shop']
```

### Checklist de Vérification
- [ ] Base de données `geekboard_[shop]` existe
- [ ] Entrée ajoutée dans `$shop_mapping`
- [ ] ID unique assigné
- [ ] Test avec `test_detection_mkmkmk.php`
- [ ] Connexion manuelle testée

Le magasin `mkmkmk` est maintenant pleinement fonctionnel et accessible via son sous-domaine dédié ! 