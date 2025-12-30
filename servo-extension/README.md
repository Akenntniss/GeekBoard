# SERVO Extension Chrome - Ajout Pièces Fournisseurs

Extension Chrome permettant d'ajouter des pièces directement depuis les sites fournisseurs (Utopya, Mobilax) vers le système SERVO.

## 📦 Installation

1. Ouvrir Chrome et naviguer vers `chrome://extensions`
2. Activer le **Mode développeur** (interrupteur en haut à droite)
3. Cliquer sur **Charger l'extension non empaquetée**
4. Sélectionner le dossier `servo-extension`

## ⚙️ Configuration

1. Cliquer sur l'icône SERVO dans la barre d'extensions
2. Entrer votre URL SERVO (ex: `https://mdg.servo.tools`)
3. Cliquer sur **Enregistrer**
4. Vérifier que le statut affiche "Connecté"

> **Important**: Vous devez être connecté à SERVO dans le même navigateur pour que l'extension fonctionne.

## 🛒 Utilisation

1. Naviguer vers une fiche produit sur Utopya ou Mobilax
2. Un bouton violet **"Ajouter à SERVO"** apparaît sur la page
3. Cliquer sur le bouton
4. Le produit est automatiquement ajouté comme bon de commande dans SERVO

## 📋 Données extraites

| Fournisseur | Nom produit | Référence | Prix |
|-------------|-------------|-----------|------|
| Utopya | ✅ | ✅ (SKU) | ✅ HT |
| Mobilax | ✅ | ✅ (réf) | ✅ HT |

## 🔧 Sites supportés

- **Utopya** (`utopya.fr`)
- **Mobilax** (`mobilax.fr`)

## 🔒 Sécurité

- L'extension utilise vos cookies de session SERVO existants
- Aucun mot de passe n'est stocké dans l'extension
- Les requêtes sont authentifiées via la session PHP

## 🐛 Problèmes courants

**"Non connecté à SERVO"**
- Ouvrez un nouvel onglet et connectez-vous à votre SERVO
- Revenez sur la page fournisseur et réessayez

**Le bouton n'apparaît pas**
- Assurez-vous d'être sur une page produit (pas une liste)
- Actualisez la page (F5)

**Erreur CORS**
- Vérifiez que votre backend SERVO est à jour
- Le fichier `ajax/add_catalogue_to_cart.php` doit avoir les headers CORS

## 📁 Structure

```
servo-extension/
├── manifest.json          # Configuration extension
├── icons/                 # Icônes 16/48/128px
├── popup/                 # Interface configuration
│   ├── popup.html
│   ├── popup.css
│   └── popup.js
├── content/               # Scripts injectés
│   ├── utopya.js
│   ├── mobilax.js
│   └── styles.css
└── background/
    └── service-worker.js  # Gestion API
```

## 📝 Version

- **v1.0.0** - Version initiale avec support Utopya et Mobilax
