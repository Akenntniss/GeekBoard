# 🔒 Configuration Sécurisée - GeekBoard

## ⚠️ IMPORTANT - Clés API et Secrets

Ce repository utilise des variables d'environnement pour sécuriser les clés API et secrets.

### 🔧 Configuration sur le serveur de production

Créez un fichier `.env` dans le dossier racine avec vos vraies clés :

```bash
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_live_VOTRE_VRAIE_CLE_PUBLIQUE
STRIPE_SECRET_KEY=sk_live_VOTRE_VRAIE_CLE_SECRETE
STRIPE_WEBHOOK_SECRET=whsec_VOTRE_VRAAI_WEBHOOK_SECRET
STRIPE_ENVIRONMENT=production

# Produits Stripe
STRIPE_PRODUCT_STARTER=prod_VOTRE_PRODUIT_STARTER
STRIPE_PRODUCT_PRO=prod_VOTRE_PRODUIT_PRO
STRIPE_PRODUCT_ENTERPRISE=prod_VOTRE_PRODUIT_ENTERPRISE

# Application
APP_URL=https://servo.tools
```

### 📁 Fichiers à configurer

1. **Copiez** `config/stripe_config.example.php` vers `config/stripe_config_production.php`
2. **Remplissez** vos vraies clés dans le fichier de production
3. **Ne commitez JAMAIS** les fichiers contenant de vraies clés API

### 🚫 Fichiers exclus du Git

Ces fichiers sont automatiquement exclus :
- `config/stripe_config_production.php`
- `.env` et `*.env`
- `*_key.php`, `*_secret.php`, `*_token.php`

### 🔍 Vérification

Avant de pousser du code, vérifiez qu'aucun secret n'est présent :
```bash
git log --oneline -p | grep -i "sk_live\|pk_live\|whsec_"
```

### 📞 Support

En cas de problème de sécurité, contactez immédiatement l'administrateur.
