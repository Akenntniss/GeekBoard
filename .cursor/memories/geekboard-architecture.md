# Mémoires GeekBoard

## Mémoire 1 - Architecture Multi-Database
Le système GeekBoard utilise une architecture multi-database basée sur les sous-domaines. Chaque magasin a sa propre base de données (ex: mkmkmk.servo.tools → geekboard_mkmkmk, cannesphones.servo.tools → geekboard_cannesphones). 

La fonction getShopDBConnection() dans config/database.php détecte automatiquement la base de données via le SubdomainDatabaseDetector. Le shop_id est initialisé automatiquement en session via detectShopFromSubdomain() qui lit la table shops de la base principale geekboard_general.

**JAMAIS utiliser de connexions hardcodées comme "geekboard_mkmkmk"** - toujours utiliser getShopDBConnection() pour respecter l'architecture multi-magasin. Les APIs doivent inclure initializeShopSession() si elles sont appelées directement sans passer par l'initialisation normale de session.

Structure: Base principale geekboard_general contient la table shops avec mappings sous-domaines → bases de données magasins.

## Mémoire 2 - API Calendar
L'API calendar_api.php fonctionne maintenant parfaitement avec le système multi-magasins. Le problème des colonnes manquantes (email, phone) dans la table users a été résolu. L'API détecte automatiquement le magasin via le sous-domaine et se connecte à la bonne base de données. L'isolation entre magasins fonctionne parfaitement : mkmkmk.servo.tools accède à geekboard_mkmkmk (7 entrées), cannesphones.servo.tools accède à geekboard_cannesphones (pas de données). Plus d'erreur 400 sur les clics de pointage.

## Mémoire 3 - Processus de Développement
Processus obligatoire pour GeekBoard :
1. Toujours faire les modifications en local d'abord dans /Users/admin/Documents/GeekBoard/
2. Uploader ensuite sur le serveur via sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205
3. À la fin de chaque modification, toujours indiquer quels fichiers ont été modifiés et quels fichiers ont été supprimés

## Mémoire 4 - Serveur et Base de Données
Le site web GeekBoard se trouve sur le serveur accessible via SSH avec la commande : sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205

Le dossier du site sur le serveur est : /var/www/mdgeek.top

La base de données utilisée est : geekboard_mkmkmk

## Mémoire 5 - Nouveau Domaine
Le nouveau domaine du site est servo.tools - ce n'est plus mdgeek.top. Les certificats SSL sont maintenant correctement configurés pour chaque sous-domaine spécifique : mkmkmk.servo.tools utilise son propre certificat, phonesystem.servo.tools et phoneetoile.servo.tools utilisent également leurs certificats spécifiques. La configuration Nginx a été corrigée pour utiliser les bons certificats SSL au lieu d'un certificat générique. 

Tous les mappings automatiques fonctionnent parfaitement : 
- mkmkmk.servo.tools → geekboard_mkmkmk (shop_id: 63)
- phonesystem.servo.tools → geekboard_phonesystem (shop_id: 104)
- phoneetoile.servo.tools → geekboard_phoneetoile (shop_id: 105)

## Mémoire 6 - Workflow Obligatoire
WORKFLOW OBLIGATOIRE pour GeekBoard :
1. TOUJOURS faire les modifications en local d'abord dans /Users/admin/Documents/GeekBoard/
2. Tester et valider les modifications localement
3. Uploader les fichiers modifiés sur le serveur via sshpass
4. Corriger les permissions avec chown www-data:www-data
5. Vider le cache PHP si nécessaire
6. À la fin de chaque session, TOUJOURS indiquer clairement quels fichiers ont été modifiés, ajoutés ou supprimés avec leurs chemins complets. Ce workflow garantit la synchronisation entre local et serveur.

## Mémoire 7 - Design Futuriste Dashboard
Le dashboard GeekBoard utilise maintenant un design futuriste ultra-avancé avec les fichiers suivants :
1. `assets/css/dashboard-futuristic.css` - Styles futuristes complets avec glassmorphism, effets néon, particules flottantes, arrière-plan gradient sombre, cartes transparentes avec bordures animées, boutons d'action avec couleurs spécifiques (Rechercher=cyan, Nouvelle tâche=violet, Nouvelle réparation=vert, Nouvelle commande=orange) ET boutons noirs en mode sombre.
2. `assets/js/dashboard-futuristic.js` - Effets interactifs avec système de particules, sons futuristes, animations de survol, ondulations au clic, effets holographiques.
3. Police Orbitron ajoutée dans header.php pour l'aspect futuriste.
4. Palette de couleurs : --neon-cyan: #00ffff, --neon-purple: #8a2be2, --neon-pink: #ff1493, --neon-blue: #0080ff, --neon-green: #00ff41, --neon-orange: #ff8c00.

ANIMATIONS AVANCÉES : Chaque bouton a 3 couches d'animations simultanées - 1) Bande dégradée rotative (violet→bleu→cyan) avec pseudo-élément ::before utilisant conic-gradient et animation rotatingGradientBand, 2) Grille de points animée en arrière-plan avec background-image radial-gradient et animation internalGrid, 3) Cercle rotatif coloré au centre avec pseudo-élément ::after et animation buttonRotation. Vitesses : normal 3s, survol 1.5s-1s, filtres actifs 2s pulsation. Mode sombre : boutons d'action noirs (rgba(30,30,35,0.9) → rgba(20,20,25,0.9)) avec toutes les animations préservées. Optimisations performances avec classe .reduce-animations pour appareils moins puissants. Compatible mode sombre/clair et responsive. Inclure ces fichiers CSS/JS dans les nouvelles pages pour maintenir la cohérence visuelle.
