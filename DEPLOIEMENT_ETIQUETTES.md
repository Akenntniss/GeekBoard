# 📋 SYSTÈME MULTI-FORMAT D'ÉTIQUETTES - GUIDE DE DÉPLOIEMENT

## 🎯 RÉSUMÉ DU SYSTÈME

Le système d'étiquettes multi-format permet de choisir parmi **11 layouts différents** pour l'impression des étiquettes de réparation.

### ✅ LAYOUTS CRÉÉS

#### **Format 4x6" (Imprimante Thermique - Noir & Blanc)**
1. `4x6_moderne.php` - Design minimaliste et moderne
2. `4x6_business.php` - Design professionnel et structuré
3. `4x6_startup.php` - Design dynamique et créatif
4. `4x6_professional.php` - Design classique et élégant

#### **Format A4 (Imprimante Couleur)**
5. `a4_moderne.php` - Design minimaliste avec couleurs vives
6. `a4_business.php` - Design professionnel avec touches de couleur
7. `a4_startup.php` - Design dynamique et coloré
8. `a4_professional.php` - Design classique avec élégance colorée
9. `a4_split.php` - **SPÉCIAL** : Document à découper (75% Client + 25% Atelier)

#### **Mini Formats (Petites Étiquettes - Noir & Blanc)**
10. `mini_qr_only.php` - QR Code uniquement (2x2")
11. `mini_qr_number.php` - QR Code + Numéro de réparation (2x3")

---

## 📁 FICHIERS À DÉPLOYER

### **1. Layouts d'Étiquettes**
```
pages/labels/layouts/
├── 4x6_moderne.php
├── 4x6_business.php
├── 4x6_startup.php
├── 4x6_professional.php
├── a4_moderne.php
├── a4_business.php
├── a4_startup.php
├── a4_professional.php
├── a4_split.php
├── mini_qr_only.php
└── mini_qr_number.php
```

### **2. Gestionnaire de Layouts**
```
includes/label_manager.php
```

### **3. APIs**
```
ajax/preview_label.php
ajax/save_label_layout.php
```

### **4. Fichiers Modifiés**
```
pages/imprimer_etiquette.php (MODIFIÉ)
public_html/public_html/pages/parametre.php (MODIFIÉ)
```

---

## 🚀 COMMANDES DE DÉPLOIEMENT

### **Étape 1 : Créer les dossiers nécessaires**
```bash
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mkdir -p /var/www/mdgeek.top/pages/labels/layouts"
```

### **Étape 2 : Upload des layouts**
```bash
# Upload tous les layouts
sshpass -p "Mamanmaman01#" scp -r -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/pages/labels/ root@82.29.168.205:/var/www/mdgeek.top/pages/

# Corriger les permissions
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown -R www-data:www-data /var/www/mdgeek.top/pages/labels/"
```

### **Étape 3 : Upload du gestionnaire**
```bash
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/includes/label_manager.php root@82.29.168.205:/var/www/mdgeek.top/includes/

sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/includes/label_manager.php"
```

### **Étape 4 : Upload des APIs**
```bash
# Upload preview_label.php
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/ajax/preview_label.php root@82.29.168.205:/var/www/mdgeek.top/ajax/

# Upload save_label_layout.php
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/ajax/save_label_layout.php root@82.29.168.205:/var/www/mdgeek.top/ajax/

# Permissions
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/ajax/preview_label.php /var/www/mdgeek.top/ajax/save_label_layout.php"
```

### **Étape 5 : Upload des fichiers modifiés**
```bash
# imprimer_etiquette.php
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/pages/imprimer_etiquette.php root@82.29.168.205:/var/www/mdgeek.top/pages/

# parametre.php
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/public_html/pages/parametre.php root@82.29.168.205:/var/www/mdgeek.top/pages/

# Permissions
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/pages/imprimer_etiquette.php /var/www/mdgeek.top/pages/parametre.php"
```

### **Étape 6 : Vider le cache PHP**
```bash
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "php -r 'if (function_exists(\"opcache_reset\")) opcache_reset();'"
```

---

## 📊 BASE DE DONNÉES

### **Table : `parametres`**
Le système ajoute automatiquement une entrée dans la table `parametres` pour stocker le layout sélectionné :

```sql
-- Structure existante (pas besoin de créer)
-- La clé 'label_layout_default' sera créée automatiquement
```

Aucune modification de la base de données n'est nécessaire ! 

---

## 🎨 UTILISATION DU SYSTÈME

### **1. Configuration (Admin)**
1. Aller dans **Paramètres** (`index.php?page=parametre`)
2. Cliquer sur l'onglet **"Imprimante"**
3. Choisir parmi les 11 layouts disponibles
4. Cliquer sur **"Prévisualiser"** pour voir un aperçu
5. Sélectionner le layout désiré
6. Cliquer sur **"Enregistrer le Layout"**

### **2. Impression**
1. Depuis une réparation, cliquer sur **"Imprimer Étiquette"**
2. L'étiquette s'imprime automatiquement avec le layout sélectionné
3. Possibilité de forcer un layout spécifique via URL : 
   ```
   imprimer_etiquette.php?id=123&layout=a4_split
   ```

---

## 🔍 TESTS À EFFECTUER

### **Test 1 : Page Paramètres**
- [ ] La section "Imprimante" s'affiche correctement
- [ ] Les 11 layouts sont visibles et organisés par catégorie
- [ ] La sélection d'un layout fonctionne

### **Test 2 : Prévisualisation**
- [ ] Le bouton "Prévisualiser" ouvre une nouvelle fenêtre
- [ ] L'aperçu affiche correctement les données de test
- [ ] Tous les layouts se prévisualisent sans erreur

### **Test 3 : Sauvegarde**
- [ ] Le layout sélectionné se sauvegarde correctement
- [ ] Un message de succès s'affiche
- [ ] La sélection persiste après rechargement de la page

### **Test 4 : Impression**
- [ ] L'impression utilise le layout sauvegardé
- [ ] Les données de la réparation s'affichent correctement
- [ ] Le QR code se génère et scanne correctement

### **Test 5 : Formats Spéciaux**
- [ ] Format `a4_split` affiche correctement les 2 parties (client/atelier)
- [ ] Les mini formats s'impriment au bon format
- [ ] Les layouts thermiques sont en noir et blanc
- [ ] Les layouts A4 affichent les couleurs

---

## ⚠️ POINTS D'ATTENTION

### **Chemins de Fichiers**
- ✅ Les layouts utilisent des chemins relatifs
- ✅ Le LabelManager gère automatiquement les chemins
- ✅ Fonctionne depuis `index.php?page=imprimer_etiquette`

### **Sécurité**
- ✅ Vérification de session sur toutes les APIs
- ✅ Nettoyage des inputs avec `cleanInput()`
- ✅ PDO avec prepared statements

### **Performance**
- ✅ Layouts chargés uniquement quand nécessaire
- ✅ Cache des QR codes dans le navigateur
- ✅ Pas d'images externes (tout en code)

---

## 🐛 DÉPANNAGE

### **Erreur : "Layout non trouvé"**
- Vérifier que tous les fichiers de layouts sont bien uploadés
- Vérifier les permissions (www-data:www-data)
- Vérifier les chemins dans `label_manager.php`

### **Erreur : "Impossible de sauvegarder"**
- Vérifier que la table `parametres` existe
- Vérifier les droits MySQL de l'utilisateur
- Vérifier les logs PHP : `/var/log/php-errors.log`

### **Prévisualisation vide**
- Vérifier que l'API `preview_label.php` est accessible
- Ouvrir la console navigateur pour voir les erreurs
- Vérifier les permissions du fichier

### **QR Code ne se génère pas**
- Vérifier que le CDN qrcodejs est accessible
- Vérifier la console navigateur
- Tester avec un autre layout

---

## 📞 ASSISTANCE

En cas de problème :
1. Vérifier les logs PHP : `tail -f /var/log/php-errors.log`
2. Vérifier les permissions : `ls -la /var/www/mdgeek.top/`
3. Tester depuis le navigateur en mode incognito
4. Vider le cache du navigateur

---

## ✅ CHECKLIST DE DÉPLOIEMENT

- [ ] Tous les layouts uploadés (11 fichiers)
- [ ] label_manager.php uploadé
- [ ] Les 2 APIs uploadées
- [ ] imprimer_etiquette.php modifié
- [ ] parametre.php modifié
- [ ] Permissions corrigées
- [ ] Cache PHP vidé
- [ ] Tests effectués
- [ ] Documentation mise à jour

---

**🎉 SYSTÈME PRÊT À L'EMPLOI !**

