#!/bin/bash
# Script de sécurisation rapide - sans mise à jour apt

echo "=========================================="
echo "SÉCURISATION RAPIDE DU SERVEUR"
echo "=========================================="
echo ""

# 1. Bloquer les pools de minage (déjà fait mais vérifier)
echo "=== [1/4] État du firewall ==="
ufw status numbered | grep -E "DENY OUT|3333|4444|5555|7777|14444|c3pool" || echo "Aucun blocage spécifique trouvé"
echo ""

# 2. Redémarrer fail2ban s'il est en échec
echo "=== [2/4] Redémarrage de fail2ban ==="
systemctl restart fail2ban 2>/dev/null && echo "✓ fail2ban redémarré" || echo "⚠️  Problème avec fail2ban"
systemctl status fail2ban --no-pager | head -3
echo ""

# 3. Scanner avec rkhunter si disponible
echo "=== [3/4] Scan de sécurité ==="
if command -v rkhunter &> /dev/null; then
    echo "Lancement du scan rkhunter (warnings only)..."
    rkhunter --check --skip-keypress --report-warnings-only 2>&1 | head -50
else
    echo "rkhunter non installé - installation recommandée"
fi
echo ""

# 4. Vérifier les connexions réseau suspectes
echo "=== [4/4] Connexions réseau actives ==="
echo "Connexions vers les ports de minage (3333, 4444, 5555, etc.):"
netstat -tulpn 2>/dev/null | grep -E "3333|4444|5555|7777|14444" || echo "✓ Aucune connexion suspecte"
ss -tulpn 2>/dev/null | grep -E "3333|4444|5555|7777|14444|c3pool" || echo "✓ Aucune connexion vers pool de minage"
echo ""

echo "=========================================="
echo "VÉRIFICATION TERMINÉE"
echo "=========================================="
echo ""
echo "🎯 ACTIONS RECOMMANDÉES:"
echo "1. Changer le mot de passe root: passwd root"
echo "2. Surveiller l'utilisation CPU: top -bn1 | head -20"
echo "3. Installer rkhunter: apt install -y rkhunter"
echo "4. Redémarrer fail2ban: systemctl restart fail2ban"
