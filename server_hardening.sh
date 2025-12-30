#!/bin/bash
# Script de sécurisation du serveur contre les cryptomineurs

echo "=========================================="
echo "SÉCURISATION DU SERVEUR"
echo "=========================================="
echo ""

# 1. Bloquer les pools de minage connus
echo "=== [1/6] Blocage des pools de minage ==="

# Bloquer c3pool.org
if ! ufw status | grep -q "c3pool"; then
    echo "Blocage de auto.c3pool.org..."
    # Résoudre l'IP et bloquer
    C3POOL_IP=$(dig +short auto.c3pool.org | head -1)
    if [ -n "$C3POOL_IP" ]; then
        ufw deny out to $C3POOL_IP comment 'Block c3pool mining'
    fi
fi

# Bloquer les ports communs des pools de minage
for port in 3333 4444 5555 7777 14444; do
    if ! ufw status | grep -q "$port"; then
        echo "Blocage du port $port (mining)..."
        ufw deny out $port/tcp comment "Block mining port $port"
    fi
done

echo "✓ Pools de minage bloqués"
echo ""

# 2. Vérifier et installer fail2ban
echo "=== [2/6] Configuration de fail2ban ==="
if ! command -v fail2ban-client &> /dev/null; then
    echo "Installation de fail2ban..."
    apt-get update -qq
    apt-get install -y fail2ban
    systemctl enable fail2ban
    systemctl start fail2ban
    echo "✓ fail2ban installé et démarré"
else
    echo "✓ fail2ban déjà installé"
    systemctl status fail2ban --no-pager | head -5
fi
echo ""

# 3. Mettre à jour le système
echo "=== [3/6] Mise à jour du système ==="
echo "Vérification des mises à jour disponibles..."
apt-get update -qq
UPDATES=$(apt list --upgradable 2>/dev/null | grep -c upgradable)
echo "Mises à jour disponibles: $UPDATES paquets"

# Ne pas forcer la mise à jour automatique pour éviter les interruptions
echo "⚠️  Mises à jour disponibles mais non appliquées automatiquement"
echo "   Lancez manuellement: apt-get upgrade -y"
echo ""

# 4. Vérifier la configuration SSH
echo "=== [4/6] Vérification de la configuration SSH ==="
echo "Configuration SSH actuelle:"
grep -E "^PermitRootLogin|^PasswordAuthentication|^Port" /etc/ssh/sshd_config || echo "Configuration par défaut"
echo ""
echo "Recommandations:"
echo "  - Désactiver PermitRootLogin (après avoir configuré un utilisateur non-root)"
echo "  - Utiliser des clés SSH au lieu des mots de passe"
echo "  - Changer le port SSH par défaut"
echo ""

# 5. Installer et configurer rkhunter
echo "=== [5/6] Installation de rkhunter ==="
if ! command -v rkhunter &> /dev/null; then
    echo "Installation de rkhunter..."
    apt-get install -y rkhunter
    rkhunter --update
    echo "✓ rkhunter installé"
else
    echo "✓ rkhunter déjà installé"
    rkhunter --update
fi
echo ""

# 6. Vérifier les utilisateurs suspects
echo "=== [6/6] Vérification des utilisateurs ==="
echo "Utilisateurs avec shell de connexion:"
grep -E "/bin/bash|/bin/sh" /etc/passwd
echo ""

echo "=========================================="
echo "SÉCURISATION TERMINÉE"
echo "=========================================="
echo ""
echo "État actuel du firewall:"
ufw status numbered | head -20
echo ""
echo "⚠️  IMPORTANT: N'oubliez pas de changer le mot de passe root!"
echo "   Commande: passwd root"
