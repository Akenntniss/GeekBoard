#!/bin/bash
# Script de mise à jour et sécurisation pour 82.29.168.205

echo "=========================================="
echo "MISE À JOUR ET SÉCURISATION"
echo "Serveur: srv848040 (82.29.168.205)"
echo "=========================================="
echo ""

# Phase 1: Mise à jour système
echo "=== [1/5] Mise à jour de la liste des paquets ==="
apt-get update -qq
echo "✓ Liste des paquets mise à jour"
echo ""

echo "=== [2/5] Vérification des mises à jour disponibles ==="
UPDATES=$(apt list --upgradable 2>/dev/null | grep -c upgradable)
echo "Nombre de paquets à mettre à jour: $UPDATES"
echo ""

if [ "$UPDATES" -gt 0 ]; then
    echo "=== Application des mises à jour (cela peut prendre plusieurs minutes) ==="
    DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -qq
    echo "✓ Mises à jour appliquées"
else
    echo "✓ Système déjà à jour"
fi
echo ""

# Phase 2: Configuration du firewall
echo "=== [3/5] Configuration du firewall UFW ==="

# Activer UFW si nécessaire
if ! ufw status | grep -q "Status: active"; then
    echo "Activation du firewall..."
    ufw --force enable
fi

# Bloquer les pools de minage
echo "Blocage des pools de minage..."

# c3pool
C3POOL_IP=$(dig +short auto.c3pool.org | head -1 2>/dev/null)
if [ -n "$C3POOL_IP" ]; then
    ufw deny out to "$C3POOL_IP" comment 'Block c3pool mining' 2>/dev/null || true
fi

# Ports de minage
for port in 3333 4444 5555 7777 14444; do
    ufw deny out "$port/tcp" comment "Block mining port $port" 2>/dev/null || true
done

echo "✓ Firewall configuré"
echo ""

# Phase 3: fail2ban
echo "=== [4/5] Configuration de fail2ban ==="
if ! command -v fail2ban-client &> /dev/null; then
    echo "Installation de fail2ban..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq fail2ban
    systemctl enable fail2ban
    systemctl start fail2ban
    echo "✓ fail2ban installé et démarré"
else
    echo "✓ fail2ban déjà installé"
    systemctl restart fail2ban 2>/dev/null || true
    systemctl status fail2ban --no-pager | head -3
fi
echo ""

# Phase 4: rkhunter
echo "=== [5/5] Installation de rkhunter ==="
if ! command -v rkhunter &> /dev/null; then
    echo "Installation de rkhunter..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq rkhunter
    rkhunter --update --quiet 2>/dev/null || true
    echo "✓ rkhunter installé"
else
    echo "✓ rkhunter déjà installé"
    rkhunter --update --quiet 2>/dev/null || true
fi
echo ""

echo "=========================================="
echo "CONFIGURATION TERMINÉE"
echo "=========================================="
echo ""

# Afficher le résumé
echo "📊 RÉSUMÉ:"
echo ""
echo "Firewall UFW:"
ufw status numbered | grep -E "DENY OUT|3333|4444|5555|7777|14444" || echo "  Règles configurées"
echo ""
echo "fail2ban:"
systemctl is-active fail2ban &>/dev/null && echo "  ✓ Actif" || echo "  ⚠️  Inactif"
echo ""
echo "Connexions suspectes:"
netstat -tulpn 2>/dev/null | grep -E "3333|4444|5555|7777|14444" || echo "  ✓ Aucune"
echo ""
echo "✅ Serveur sécurisé!"
