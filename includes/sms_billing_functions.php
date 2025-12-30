<?php
/**
 * Fonctions de facturation SMS pour GeekBoard
 * Gestion des quotas, packs, et facturation des SMS supplémentaires
 */

/**
 * Récupère la configuration globale de facturation SMS
 */
function getSMSBillingConfig() {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->query("SELECT * FROM sms_billing_config WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            // Valeurs par défaut si pas de config
            return [
                'sms_extra_price' => 0.02,
                'quota_exceeded_action' => 'continue_bill',
                'trial_unlimited_sms' => 1,
                'alert_threshold_1' => 80,
                'alert_threshold_2' => 90,
                'alert_threshold_3' => 100
            ];
        }
        return $config;
    } catch (Exception $e) {
        error_log("Erreur getSMSBillingConfig: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les paramètres SMS d'un shop
 */
function getShopSMSSettings($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->prepare("SELECT * FROM sms_shop_settings WHERE shop_id = ?");
        $stmt->execute([$shopId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Créer les paramètres par défaut
            $stmt = $mainPdo->prepare("INSERT INTO sms_shop_settings (shop_id) VALUES (?)");
            $stmt->execute([$shopId]);
            return [
                'shop_id' => $shopId,
                'alerts_enabled' => 1,
                'alert_email' => null,
                'hard_cap_enabled' => 0,
                'hard_cap_amount' => 20.00,
                'bonus_sms' => 0
            ];
        }
        return $settings;
    } catch (Exception $e) {
        error_log("Erreur getShopSMSSettings: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les informations de l'abonnement d'un shop
 */
function getShopSubscriptionInfo($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->prepare("
            SELECT s.*, p.sms_credits, p.name as plan_name
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.shop_id = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getShopSubscriptionInfo: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère ou crée la période de facturation actuelle pour un shop
 */
function getCurrentBillingPeriod($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $subscription = getShopSubscriptionInfo($shopId);
        
        if (!$subscription) {
            // Pas d'abonnement, on utilise le mois calendaire
            $periodStart = date('Y-m-01');
            $periodEnd = date('Y-m-t');
        } else {
            // Calculer la période basée sur la date de début d'abonnement
            $subStart = new DateTime($subscription['current_period_start'] ?? $subscription['trial_start_date'] ?? date('Y-m-01'));
            $today = new DateTime();
            
            // Trouver le début de la période actuelle
            $periodStart = clone $subStart;
            while ($periodStart->modify('+1 month') <= $today) {
                // Continue
            }
            $periodStart->modify('-1 month');
            
            $periodEnd = clone $periodStart;
            $periodEnd->modify('+1 month')->modify('-1 day');
            
            $periodStart = $periodStart->format('Y-m-d');
            $periodEnd = $periodEnd->format('Y-m-d');
        }
        
        // Vérifier si la période existe, sinon la créer
        $stmt = $mainPdo->prepare("SELECT * FROM sms_usage WHERE shop_id = ? AND period_start = ?");
        $stmt->execute([$shopId, $periodStart]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usage) {
            // Créer la nouvelle période
            $smsCredits = $subscription['sms_credits'] ?? 0;
            $stmt = $mainPdo->prepare("
                INSERT INTO sms_usage (shop_id, period_start, period_end, sms_included_quota) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$shopId, $periodStart, $periodEnd, $smsCredits]);
            
            return [
                'shop_id' => $shopId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'sms_included_quota' => $smsCredits,
                'sms_sent_total' => 0,
                'sms_from_quota' => 0,
                'sms_from_packs' => 0,
                'sms_extra_billed' => 0,
                'extra_cost' => 0.00
            ];
        }
        
        return $usage;
    } catch (Exception $e) {
        error_log("Erreur getCurrentBillingPeriod: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère le solde SMS disponible dans les packs d'un shop
 */
function getPackSMSBalance($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->prepare("
            SELECT SUM(sms_remaining) as total_remaining 
            FROM sms_pack_purchases 
            WHERE shop_id = ? AND sms_remaining > 0 AND (expires_at IS NULL OR expires_at >= CURDATE())
        ");
        $stmt->execute([$shopId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_remaining'] ?? 0);
    } catch (Exception $e) {
        error_log("Erreur getPackSMSBalance: " . $e->getMessage());
        return 0;
    }
}

/**
 * Vérifie si un shop peut envoyer un SMS
 * @return array ['allowed' => bool, 'reason' => string, 'source' => 'quota'|'pack'|'extra'|'trial']
 */
function checkSMSQuota($shopId) {
    try {
        $config = getSMSBillingConfig();
        $settings = getShopSMSSettings($shopId);
        $subscription = getShopSubscriptionInfo($shopId);
        $period = getCurrentBillingPeriod($shopId);
        
        if (!$period) {
            return ['allowed' => false, 'reason' => 'Erreur de configuration de la période'];
        }
        
        // 1. Vérifier si en période d'essai avec SMS illimités
        if ($subscription && $subscription['status'] === 'trial' && $config['trial_unlimited_sms']) {
            return ['allowed' => true, 'reason' => 'Période d\'essai avec SMS illimités', 'source' => 'trial'];
        }
        
        // 2. Vérifier si le plan a des SMS illimités (-1)
        if ($subscription && $subscription['sms_credits'] == -1) {
            return ['allowed' => true, 'reason' => 'Plan avec SMS illimités', 'source' => 'unlimited'];
        }
        
        // 3. Vérifier le quota du plan
        $quotaUsed = $period['sms_from_quota'];
        $quotaTotal = $period['sms_included_quota'] + ($settings['bonus_sms'] ?? 0);
        
        if ($quotaUsed < $quotaTotal) {
            return ['allowed' => true, 'reason' => 'Quota disponible', 'source' => 'quota'];
        }
        
        // 4. Vérifier les packs SMS
        $packBalance = getPackSMSBalance($shopId);
        if ($packBalance > 0) {
            return ['allowed' => true, 'reason' => 'Pack SMS disponible', 'source' => 'pack'];
        }
        
        // 5. Vérifier le hard cap
        if ($settings['hard_cap_enabled']) {
            $currentExtraCost = $period['extra_cost'];
            $nextCost = $currentExtraCost + $config['sms_extra_price'];
            
            if ($nextCost > $settings['hard_cap_amount']) {
                return ['allowed' => false, 'reason' => 'Plafond de sécurité atteint (' . $settings['hard_cap_amount'] . '€)'];
            }
        }
        
        // 6. Vérifier l'action configurée (bloquer ou continuer)
        if ($config['quota_exceeded_action'] === 'block') {
            return ['allowed' => false, 'reason' => 'Quota SMS épuisé - Envoi bloqué'];
        }
        
        // Continue et facture
        return ['allowed' => true, 'reason' => 'SMS supplémentaire (' . $config['sms_extra_price'] . '€)', 'source' => 'extra'];
        
    } catch (Exception $e) {
        error_log("Erreur checkSMSQuota: " . $e->getMessage());
        return ['allowed' => false, 'reason' => 'Erreur technique'];
    }
}

/**
 * Incrémente le compteur SMS après un envoi réussi
 * @param string $smsType Type de SMS: 'status', 'devis', 'relance', 'manual', 'campaign', 'other'
 */
function incrementSMSUsage($shopId, $source = 'quota', $smsType = 'other') {
    try {
        $mainPdo = getMainDBConnection();
        $config = getSMSBillingConfig();
        $period = getCurrentBillingPeriod($shopId);
        
        if (!$period) return false;
        
        // Incrémenter le compteur approprié
        switch ($source) {
            case 'quota':
                $sql = "UPDATE sms_usage SET sms_sent_total = sms_sent_total + 1, sms_from_quota = sms_from_quota + 1 WHERE shop_id = ? AND period_start = ?";
                break;
            case 'pack':
                $sql = "UPDATE sms_usage SET sms_sent_total = sms_sent_total + 1, sms_from_packs = sms_from_packs + 1 WHERE shop_id = ? AND period_start = ?";
                // Décrémenter le pack
                decrementPackBalance($shopId);
                break;
            case 'extra':
                $extraPrice = $config['sms_extra_price'];
                $sql = "UPDATE sms_usage SET sms_sent_total = sms_sent_total + 1, sms_extra_billed = sms_extra_billed + 1, extra_cost = extra_cost + $extraPrice WHERE shop_id = ? AND period_start = ?";
                break;
            case 'trial':
            case 'unlimited':
                // Juste compter, pas de déduction
                $sql = "UPDATE sms_usage SET sms_sent_total = sms_sent_total + 1 WHERE shop_id = ? AND period_start = ?";
                break;
            default:
                $sql = "UPDATE sms_usage SET sms_sent_total = sms_sent_total + 1 WHERE shop_id = ? AND period_start = ?";
        }
        
        $stmt = $mainPdo->prepare($sql);
        $stmt->execute([$shopId, $period['period_start']]);
        
        // Incrémenter le détail par type
        $monthYear = date('Y-m');
        $stmt = $mainPdo->prepare("
            INSERT INTO sms_usage_details (shop_id, sms_type, month_year, count) 
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE count = count + 1
        ");
        $stmt->execute([$shopId, $smsType, $monthYear]);
        
        // Vérifier les seuils d'alerte
        checkAndSendAlerts($shopId);
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur incrementSMSUsage: " . $e->getMessage());
        return false;
    }
}

/**
 * Décrémente le solde d'un pack SMS
 */
function decrementPackBalance($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->prepare("
            UPDATE sms_pack_purchases 
            SET sms_remaining = sms_remaining - 1 
            WHERE shop_id = ? AND sms_remaining > 0 AND (expires_at IS NULL OR expires_at >= CURDATE())
            ORDER BY purchased_at ASC
            LIMIT 1
        ");
        return $stmt->execute([$shopId]);
    } catch (Exception $e) {
        error_log("Erreur decrementPackBalance: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie et envoie les alertes de seuil
 */
function checkAndSendAlerts($shopId) {
    try {
        $config = getSMSBillingConfig();
        $settings = getShopSMSSettings($shopId);
        $period = getCurrentBillingPeriod($shopId);
        
        if (!$settings['alerts_enabled'] || !$period) return;
        
        $quotaTotal = $period['sms_included_quota'] + ($settings['bonus_sms'] ?? 0);
        if ($quotaTotal <= 0) return;
        
        $percentUsed = ($period['sms_from_quota'] / $quotaTotal) * 100;
        
        $mainPdo = getMainDBConnection();
        
        // Vérifier chaque seuil
        $thresholds = [
            $config['alert_threshold_1'] => '80_percent',
            $config['alert_threshold_2'] => '90_percent',
            $config['alert_threshold_3'] => '100_percent'
        ];
        
        foreach ($thresholds as $threshold => $alertType) {
            if ($percentUsed >= $threshold) {
                // Vérifier si l'alerte a déjà été envoyée
                $stmt = $mainPdo->prepare("
                    SELECT id FROM sms_alerts_sent 
                    WHERE shop_id = ? AND alert_type = ? AND period_start = ?
                ");
                $stmt->execute([$shopId, $alertType, $period['period_start']]);
                
                if (!$stmt->fetch()) {
                    // Envoyer l'alerte et l'enregistrer
                    sendSMSQuotaAlert($shopId, $alertType, $percentUsed);
                    
                    $stmt = $mainPdo->prepare("
                        INSERT INTO sms_alerts_sent (shop_id, alert_type, period_start) VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$shopId, $alertType, $period['period_start']]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erreur checkAndSendAlerts: " . $e->getMessage());
    }
}

/**
 * Envoie une alerte email de quota SMS
 */
// Inclure le vrai système d'alertes
require_once __DIR__ . '/sms_alerts.php';

/**
 * Récupère les statistiques SMS d'un shop pour les 12 derniers mois
 */
function getSMSStats12Months($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->prepare("
            SELECT 
                month_year,
                SUM(count) as total_sms,
                MAX(CASE WHEN sms_type = 'status' THEN count ELSE 0 END) as status_sms,
                MAX(CASE WHEN sms_type = 'devis' THEN count ELSE 0 END) as devis_sms,
                MAX(CASE WHEN sms_type = 'relance' THEN count ELSE 0 END) as relance_sms,
                MAX(CASE WHEN sms_type = 'manual' THEN count ELSE 0 END) as manual_sms
            FROM sms_usage_details 
            WHERE shop_id = ? AND month_year >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
            GROUP BY month_year
            ORDER BY month_year ASC
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getSMSStats12Months: " . $e->getMessage());
        return [];
    }
}

/**
 * Ajoute des SMS bonus à un shop
 */
function addBonusSMS($shopId, $smsCount, $reason, $createdBy = null, $adjustmentType = 'bonus') {
    try {
        $mainPdo = getMainDBConnection();
        
        // Mettre à jour le bonus dans les settings
        $stmt = $mainPdo->prepare("
            INSERT INTO sms_shop_settings (shop_id, bonus_sms) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE bonus_sms = bonus_sms + ?
        ");
        $stmt->execute([$shopId, $smsCount, $smsCount]);
        
        // Logger l'ajustement
        $stmt = $mainPdo->prepare("
            INSERT INTO sms_quota_adjustments (shop_id, adjustment_type, sms_count, reason, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$shopId, $adjustmentType, $smsCount, $reason, $createdBy]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur addBonusSMS: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère l'historique des ajustements de quota d'un shop
 */
function getQuotaAdjustmentsHistory($shopId) {
    try {
        $mainPdo = getMainDBConnection();
        $stmt = $mainPdo->prepare("
            SELECT * FROM sms_quota_adjustments 
            WHERE shop_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getQuotaAdjustmentsHistory: " . $e->getMessage());
        return [];
    }
}
