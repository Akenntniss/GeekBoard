# Mémoire 5 - Domaine, SSL et Dashboard Futuriste

## Domaine Principal

Le nouveau domaine du site est **servo.tools** - ce n'est plus mdgeek.top. L'ancien domaine `mdgeek.top` reste supporté pour compatibilité.

## Certificats SSL

Les certificats SSL sont maintenant correctement configurés pour chaque sous-domaine spécifique :
- `mkmkmk.servo.tools` utilise son propre certificat
- `phonesystem.servo.tools` utilise son propre certificat
- `phoneetoile.servo.tools` utilise son propre certificat

La configuration Nginx a été corrigée pour utiliser les bons certificats SSL au lieu d'un certificat générique.

## Mappings Automatiques

Tous les mappings automatiques fonctionnent parfaitement :
- `mkmkmk.servo.tools` → `geekboard_mkmkmk` (shop_id: 63)
- `phonesystem.servo.tools` → `geekboard_phonesystem` (shop_id: 104)
- `phoneetoile.servo.tools` → `geekboard_phoneetoile` (shop_id: 105)

Le système détecte automatiquement le sous-domaine et se connecte à la base de données correspondante.

## Support Multi-Domaine

Le système détecte automatiquement le domaine (`servo.tools` ou `mdgeek.top`) et les mappings sont dynamiques via la table `shops` dans `geekboard_general`. Chaque sous-domaine peut fonctionner sur les deux domaines principaux.

## Dashboard Futuriste

Le dashboard GeekBoard utilise maintenant un design futuriste ultra-avancé avec les fichiers suivants :

### 1. CSS Futuriste
- **Fichier** : `assets/css/dashboard-futuristic.css`
- **Contenu** : Styles futuristes complets avec glassmorphism, effets néon, particules flottantes, arrière-plan gradient sombre, cartes transparentes avec bordures animées, boutons d'action avec couleurs spécifiques :
  - Rechercher = cyan
  - Nouvelle tâche = violet
  - Nouvelle réparation = vert
  - Nouvelle commande = orange
  - Boutons noirs en mode sombre

### 2. JavaScript Interactif
- **Fichier** : `assets/js/dashboard-futuristic.js`
- **Contenu** : Effets interactifs avec système de particules, sons futuristes, animations de survol, ondulations au clic, effets holographiques

### 3. Police Futuriste
- Police **Orbitron** ajoutée dans `header.php` pour l'aspect futuriste

### 4. Palette de Couleurs
- `--neon-cyan: #00ffff`
- `--neon-purple: #8a2be2`
- `--neon-pink: #ff1493`
- `--neon-blue: #0080ff`
- `--neon-green: #00ff41`
- `--neon-orange: #ff8c00`

## Animations Avancées

Chaque bouton a **3 couches d'animations simultanées** :

1. **Bande dégradée rotative** (violet→bleu→cyan) avec pseudo-élément `::before` utilisant `conic-gradient` et animation `rotatingGradientBand`
2. **Grille de points animée** en arrière-plan avec `background-image` `radial-gradient` et animation `internalGrid`
3. **Cercle rotatif coloré** au centre avec pseudo-élément `::after` et animation `buttonRotation`

**Vitesses d'animation :**
- Normal : 3s
- Survol : 1.5s-1s
- Filtres actifs : 2s pulsation

**Mode sombre :** Boutons d'action noirs (`rgba(30,30,35,0.9)` → `rgba(20,20,25,0.9)`) avec toutes les animations préservées.

**Optimisations performances :** Classe `.reduce-animations` pour appareils moins puissants.

**Compatibilité :** Mode sombre/clair et responsive.

**Important :** Inclure ces fichiers CSS/JS dans les nouvelles pages pour maintenir la cohérence visuelle.

