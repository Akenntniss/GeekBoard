#!/bin/bash

# ╔══════════════════════════════════════════════════════════════════╗
# ║              SERVO - Installation automatique macOS              ║
# ╚══════════════════════════════════════════════════════════════════╝

clear
echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    🚀 SERVO Installer                            ║"
echo "╠══════════════════════════════════════════════════════════════════╣"
echo "║  Ce script va installer SERVO dans votre dossier Applications   ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

# Trouver le répertoire du script (où se trouve aussi SERVO.app)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
APP_NAME="SERVO.app"
SOURCE_APP="$SCRIPT_DIR/$APP_NAME"
DEST_APP="/Applications/$APP_NAME"

# Vérifier que SERVO.app existe à côté du script
if [ ! -d "$SOURCE_APP" ]; then
    echo "❌ Erreur: $APP_NAME introuvable dans le même dossier que ce script."
    echo "   Assurez-vous que $APP_NAME est bien à côté de ce fichier."
    echo ""
    read -p "Appuyez sur Entrée pour fermer..."
    exit 1
fi

echo "📦 Application trouvée: $SOURCE_APP"
echo ""

# Vérifier si une ancienne version existe
if [ -d "$DEST_APP" ]; then
    echo "⚠️  Une version de SERVO existe déjà dans Applications."
    echo "   Elle sera remplacée par la nouvelle version."
    echo ""
    rm -rf "$DEST_APP"
fi

# Copier l'application dans /Applications
echo "📁 Copie vers /Applications..."
cp -R "$SOURCE_APP" "$DEST_APP"

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de la copie. Essai avec les droits administrateur..."
    sudo cp -R "$SOURCE_APP" "$DEST_APP"
    if [ $? -ne 0 ]; then
        echo "❌ Échec de l'installation. Vérifiez vos permissions."
        read -p "Appuyez sur Entrée pour fermer..."
        exit 1
    fi
fi

echo "✅ Application copiée avec succès!"
echo ""

# Supprimer l'attribut de quarantaine
echo "🔓 Suppression des restrictions de sécurité..."
xattr -cr "$DEST_APP" 2>/dev/null
sudo xattr -cr "$DEST_APP" 2>/dev/null

echo "✅ Restrictions supprimées!"
echo ""

# Lancer l'application
echo "🚀 Lancement de SERVO..."
echo ""
open "$DEST_APP"

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║               ✅ Installation terminée avec succès!              ║"
echo "║                                                                  ║"
echo "║  SERVO est maintenant dans votre dossier Applications.          ║"
echo "║  Vous pouvez supprimer ce dossier de téléchargement.            ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
read -p "Appuyez sur Entrée pour fermer cette fenêtre..."
