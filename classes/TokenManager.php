<?php
// classes/TokenManager.php

class TokenManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getMainDBConnection();
    }

    /**
     * Crée une session sécurisée et retourne le token
     */
    public function createSession($shop_id, $user_id) {
        $token = bin2hex(random_bytes(32)); // 64 chars
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours')); // Expire dans 24h

        $stmt = $this->pdo->prepare("INSERT INTO subscription_sessions (token, shop_id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$token, $shop_id, $user_id, $ip, $ua, $expires]);

        return $token;
    }

    /**
     * Vérifie le token et retourne les infos de session
     */
    public function validateSession($token) {
        // Nettoyage des sessions expirées (cleanup 1/100)
        if (rand(1, 100) === 1) {
            $this->cleanup();
        }

        $stmt = $this->pdo->prepare("SELECT * FROM subscription_sessions WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            // Mise à jour last_activity
            $update = $this->pdo->prepare("UPDATE subscription_sessions SET last_activity = NOW() WHERE token = ?");
            $update->execute([$token]);
            return $session;
        }

        return false;
    }

    /**
     * Supprime une session (Logout)
     */
    public function revokeSession($token) {
        $stmt = $this->pdo->prepare("DELETE FROM subscription_sessions WHERE token = ?");
        $stmt->execute([$token]);
    }

    /**
     * Nettoie les vieilles sessions
     */
    private function cleanup() {
        $this->pdo->query("DELETE FROM subscription_sessions WHERE expires_at < NOW()");
    }
}
?>
