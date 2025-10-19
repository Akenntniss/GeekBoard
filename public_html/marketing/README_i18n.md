# 🌍 Système d'Internationalisation MDGEEK Marketing

## ✅ Langues Implémentées (Niveau 1)

- **🇫🇷 Français** (par défaut) - `fr`
- **🇬🇧 Anglais** - `en`
- **🇪🇸 Espagnol** - `es`
- **🇩🇪 Allemand** - `de`
- **🇮🇹 Italien** - `it`

## 🏗️ Architecture

### Structure des fichiers
```
marketing/
├── includes/
│   └── i18n.php                    # Système principal
├── languages/
│   ├── fr/
│   │   ├── common.php              # Traductions communes
│   │   └── home.php                # Traductions page d'accueil
│   ├── en/
│   │   ├── common.php
│   │   └── home.php
│   ├── es/
│   │   ├── common.php
│   │   └── home.php
│   ├── de/
│   │   ├── common.php
│   │   └── home.php
│   └── it/
│       ├── common.php
│       └── home.php
├── router.php                      # Modifié pour i18n
├── shared/
│   ├── header.php                  # Modifié avec sélecteur
│   └── footer.php                  # Modifié avec traductions
└── .htaccess                       # Gestion sous-domaines
```

## 🔧 Fonctionnement

### Détection de Langue
1. **Paramètre GET** : `?lang=en`
2. **Session** : Mémorisation du choix
3. **Préfixe URL** : `servo.tools/en` → `en`
4. **Navigateur** : `Accept-Language`
5. **Défaut** : Français

### URLs Configurées
- `servo.tools/` → Français
- `servo.tools/en` → Anglais
- `servo.tools/es` → Espagnol
- `servo.tools/de` → Allemand
- `servo.tools/it` → Italien

## 🛠️ Utilisation

### Dans les templates PHP
```php
// Traduction simple
echo t('nav_features');

// Traduction avec valeur par défaut
echo t('custom_key', 'Valeur par défaut');

// Charger les traductions d'une page
loadPageTranslations('home');
```

### Fonctions disponibles
- `t($key, $default)` - Récupérer une traduction
- `getCurrentLanguage()` - Langue actuelle
- `getSupportedLanguages()` - Langues supportées
- `renderLanguageSelector()` - HTML du sélecteur
- `loadPageTranslations($page)` - Charger traductions page

## 📝 Ajout de Nouvelles Traductions

### 1. Ajouter dans `common.php`
```php
// Pour tous les langages : fr, en, es, de, it
'nouveau_terme' => 'Traduction appropriée',
```

### 2. Créer un fichier page spécifique
```php
// languages/fr/nouvelle_page.php
return [
    'titre_page' => 'Titre en français',
    'contenu' => 'Contenu...'
];
```

### 3. Charger dans le router
```php
loadPageTranslations('nouvelle_page');
```

## 🌟 Fonctionnalités

### ✅ Implémenté
- [x] Détection automatique de langue
- [x] Navigation traduite
- [x] Footer traduit
- [x] Gestion URLs avec préfixes
- [x] Traductions page d'accueil
- [x] Session persistence
- [x] URLs propres
- [x] Configuration Nginx

### 🔄 Prochaines étapes
- [ ] Sélecteur de langue dans l'interface (temporairement retiré)
- [ ] Traduction pages complètes (pricing, features, etc.)
- [ ] Gestion des formulaires multilingues
- [ ] SEO multilingue (hreflang)
- [ ] Cache des traductions
- [ ] Interface d'administration

## 📊 Tests

### URLs à tester
- `servo.tools/` → Français
- `servo.tools/?lang=en` → Anglais
- `servo.tools/en` → Anglais
- `servo.tools/es` → Espagnol
- `servo.tools/de` → Allemand
- `servo.tools/it` → Italien

### Sélecteur de langue
- ⏸️ Temporairement retiré (fonctionnalité à implémenter plus tard)
- ✅ URLs directes fonctionnelles
- ✅ Mémorisation en session

## 🚀 Déploiement

Le système est déployé et opérationnel sur `servo.tools`.

### Commandes utilisées
```bash
# Upload du système
scp -r includes/ root@82.29.168.205:/var/www/mdgeek.top/marketing/
scp -r languages/ root@82.29.168.205:/var/www/mdgeek.top/marketing/
scp router.php root@82.29.168.205:/var/www/mdgeek.top/marketing/
scp shared/* root@82.29.168.205:/var/www/mdgeek.top/marketing/shared/
scp .htaccess root@82.29.168.205:/var/www/mdgeek.top/marketing/

# Permissions
chown -R www-data:www-data /var/www/mdgeek.top/marketing/
```

---

**✨ Le système d'internationalisation MDGEEK Marketing est maintenant opérationnel avec les 4 langues essentielles du niveau 1 !**
