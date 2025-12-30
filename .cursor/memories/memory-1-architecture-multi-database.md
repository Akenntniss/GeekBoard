# Mémoire 1 - Architecture Multi-Database GeekBoard

Le système GeekBoard utilise une architecture multi-database basée sur les sous-domaines. Chaque magasin a sa propre base de données (ex: mkmkmk.servo.tools → geekboard_mkmkmk, cannesphones.servo.tools → geekboard_cannesphones). 

La fonction `getShopDBConnection()` dans `config/database.php` détecte automatiquement la base de données via le `SubdomainDatabaseDetector`. Le `shop_id` est initialisé automatiquement en session via `detectShopFromSubdomain()` qui lit la table `shops` de la base principale `geekboard_general`.

**JAMAIS utiliser de connexions hardcodées** comme `"geekboard_mkmkmk"` - toujours utiliser `getShopDBConnection()` pour respecter l'architecture multi-magasin. 

Les APIs doivent inclure `initializeShopSession()` si elles sont appelées directement sans passer par l'initialisation normale de session.

**Structure:** Base principale `geekboard_general` contient la table `shops` avec mappings sous-domaines → bases de données magasins.

**Mappings actuels :**
- `mkmkmk.servo.tools` → `geekboard_mkmkmk` (shop_id: 63)
- `phonesystem.servo.tools` → `geekboard_phonesystem` (shop_id: 104)
- `phoneetoile.servo.tools` → `geekboard_phoneetoile` (shop_id: 105)
- `cannesphones.servo.tools` → `geekboard_cannesphones`

Le système détecte automatiquement le sous-domaine depuis `HTTP_HOST` et lit dynamiquement les mappings depuis la table `shops` pour supporter les deux domaines (`servo.tools` et `mdgeek.top`).

