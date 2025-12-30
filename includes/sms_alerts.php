<?php
/**
 * Système d'alertes SMS par email
 * Envoie des notifications lorsque les seuils de quota sont atteints
 */

/**
 * Envoie une alerte email de quota SMS
 * @param int $shopId ID du shop
 * @param string $alertType Type d'alerte (80_percent, 90_percent, 100_percent, hard_cap)
 * @param float $percentUsed Pourcentage du quota utilisé
 */
function sendSMSQuotaAlert($shopId, $alertType, $percentUsed) {
    try {
        $mainPdo = getMainDBConnection();
        
        // Récupérer les informations du shop
        $stmt = $mainPdo->prepare("SELECT s.name, s.email, ss.alert_email FROM shops s LEFT JOIN sms_shop_settings ss ON ss.shop_id = s.id WHERE s.id = ?");
        $stmt->execute([$shopId]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shop) {
            error_log("sendSMSQuotaAlert: Shop $shopId non trouvé");
            return false;
        }
        
        // Déterminer l'email de destination
        $toEmail = !empty($shop['alert_email']) ? $shop['alert_email'] : $shop['email'];
        if (empty($toEmail)) {
            error_log("sendSMSQuotaAlert: Pas d'email pour le shop $shopId");
            return false;
        }
        
        // Préparer le contenu selon le type d'alerte
        $alertConfig = getAlertConfig($alertType, $percentUsed);
        
        // Récupérer les données actuelles
        $smsUsage = getCurrentBillingPeriod($shopId);
        $settings = getShopSMSSettings($shopId);
        $quotaTotal = ($smsUsage['sms_included_quota'] ?? 0) + ($settings['bonus_sms'] ?? 0);
        $quotaUsed = $smsUsage['sms_from_quota'] ?? 0;
        
        // Construire l'email HTML
        $subject = $alertConfig['subject'] . " - " . $shop['name'];
        $body = buildAlertEmailBody($alertConfig, $shop, $quotaUsed, $quotaTotal, $percentUsed, $smsUsage);
        
        // Envoyer l'email
        return sendAlertEmail($toEmail, $subject, $body);
        
    } catch (Exception $e) {
        error_log("sendSMSQuotaAlert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Configuration des alertes selon le type
 */
function getAlertConfig($alertType, $percentUsed) {
    $configs = [
        '80_percent' => [
            'subject' => '⚠️ Alerte SMS: 80% du quota utilisé',
            'title' => 'Vous avez utilisé 80% de votre quota SMS',
            'icon' => '⚠️',
            'color' => '#f59e0b',
            'message' => 'Votre quota SMS approche de sa limite. Pensez à surveiller votre consommation ou à acheter un pack SMS supplémentaire.'
        ],
        '90_percent' => [
            'subject' => '🔶 Alerte SMS: 90% du quota utilisé',
            'title' => 'Vous avez utilisé 90% de votre quota SMS',
            'icon' => '🔶',
            'color' => '#ea580c',
            'message' => 'Votre quota SMS est presque épuisé. Les prochains SMS seront facturés en supplément ou bloqués selon vos paramètres.'
        ],
        '100_percent' => [
            'subject' => '🚨 Alerte SMS: Quota épuisé',
            'title' => 'Votre quota SMS mensuel est épuisé',
            'icon' => '🚨',
            'color' => '#dc2626',
            'message' => 'Vous avez atteint 100% de votre quota SMS. Les SMS suivants seront facturés en supplément ou bloqués selon vos paramètres.'
        ],
        'hard_cap' => [
            'subject' => '🛑 Alerte SMS: Plafond atteint',
            'title' => 'Plafond de dépense SMS atteint',
            'icon' => '🛑',
            'color' => '#7c2d12',
            'message' => 'Vous avez atteint votre plafond de sécurité pour les SMS supplémentaires. Les SMS sont maintenant bloqués.'
        ]
    ];
    
    return $configs[$alertType] ?? $configs['100_percent'];
}

/**
 * Construit le corps de l'email HTML
 */
function buildAlertEmailBody($alertConfig, $shop, $quotaUsed, $quotaTotal, $percentUsed, $smsUsage) {
    $periodStart = date('d/m/Y', strtotime($smsUsage['period_start'] ?? 'now'));
    $periodEnd = date('d/m/Y', strtotime($smsUsage['period_end'] ?? 'now'));
    $extraCost = number_format($smsUsage['extra_cost'] ?? 0, 2);
    $extraCount = $smsUsage['sms_extra_billed'] ?? 0;
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: {$alertConfig['color']}; color: white; padding: 30px; text-align: center; }
        .header .icon { font-size: 48px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { flex: 1; text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px; }
        .stat-value { font-size: 28px; font-weight: bold; color: {$alertConfig['color']}; }
        .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; }
        .progress { height: 20px; background: #e2e8f0; border-radius: 10px; overflow: hidden; margin: 20px 0; }
        .progress-bar { height: 100%; background: {$alertConfig['color']}; width: {$percentUsed}%; transition: width 0.3s; }
        .message { background: #fef3c7; border-left: 4px solid {$alertConfig['color']}; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
        .footer { text-align: center; padding: 20px; background: #f8fafc; color: #64748b; font-size: 12px; }
        .btn { display: inline-block; background: {$alertConfig['color']}; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">{$alertConfig['icon']}</div>
            <h1>{$alertConfig['title']}</h1>
        </div>
        <div class="content">
            <p>Bonjour <strong>{$shop['name']}</strong>,</p>
            
            <div class="message">
                {$alertConfig['message']}
            </div>
            
            <h3>📊 Votre consommation SMS</h3>
            <div class="progress">
                <div class="progress-bar"></div>
            </div>
            <p style="text-align: center; font-weight: bold;">
                {$quotaUsed} / {$quotaTotal} SMS utilisés ({$percentUsed}%)
            </p>
            
            <table style="width: 100%; margin: 20px 0; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">📅 Période</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;"><strong>{$periodStart} - {$periodEnd}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">📨 SMS envoyés</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;"><strong>{$quotaUsed}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">➕ SMS supplémentaires</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;"><strong>{$extraCount}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 10px;">💶 Coût supplémentaire</td>
                    <td style="padding: 10px; text-align: right;"><strong>{$extraCost} €</strong></td>
                </tr>
            </table>
            
            <p style="text-align: center;">
                <a href="https://mdg.geekboard.fr/index.php?page=parametre#sms_usage" class="btn">
                    Gérer mes SMS
                </a>
            </p>
        </div>
        <div class="footer">
            <p>📱 GeekBoard - Système de gestion de réparations</p>
            <p>Cet email a été envoyé automatiquement. Ne pas répondre.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Envoie un email d'alerte
 */
function sendAlertEmail($to, $subject, $body) {
    try {
        // Utiliser PHPMailer si disponible
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuration SMTP (à adapter selon votre serveur)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'notifications@maisondugeek.fr';
            $mail->Password = getenv('SMTP_PASSWORD') ?: '';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom('notifications@maisondugeek.fr', 'GeekBoard SMS Alert');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
        }
        
        // Fallback: utiliser mail() natif PHP
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: GeekBoard SMS Alert <notifications@maisondugeek.fr>',
            'Reply-To: support@maisondugeek.fr',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
        
    } catch (Exception $e) {
        error_log("sendAlertEmail Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie et envoie les alertes pour tous les shops (à appeler via CRON)
 */
function checkAllShopsAlerts() {
    try {
        $mainPdo = getMainDBConnection();
        $config = getSMSBillingConfig();
        
        // Récupérer tous les shops actifs avec alertes activées
        $stmt = $mainPdo->query("
            SELECT s.id, ss.alerts_enabled 
            FROM shops s 
            LEFT JOIN sms_shop_settings ss ON ss.shop_id = s.id 
            WHERE s.active = 1 AND (ss.alerts_enabled IS NULL OR ss.alerts_enabled = 1)
        ");
        
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $alertsSent = 0;
        
        foreach ($shops as $shop) {
            $period = getCurrentBillingPeriod($shop['id']);
            if (!$period) continue;
            
            $settings = getShopSMSSettings($shop['id']);
            $quotaTotal = ($period['sms_included_quota'] ?? 0) + ($settings['bonus_sms'] ?? 0);
            if ($quotaTotal <= 0) continue;
            
            $percentUsed = ($period['sms_from_quota'] / $quotaTotal) * 100;
            
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
                    $stmt->execute([$shop['id'], $alertType, $period['period_start']]);
                    
                    if (!$stmt->fetch()) {
                        // Envoyer l'alerte
                        if (sendSMSQuotaAlert($shop['id'], $alertType, $percentUsed)) {
                            // Enregistrer l'alerte
                            $stmt = $mainPdo->prepare("
                                INSERT INTO sms_alerts_sent (shop_id, alert_type, period_start) VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$shop['id'], $alertType, $period['period_start']]);
                            $alertsSent++;
                        }
                    }
                }
            }
        }
        
        return $alertsSent;
    } catch (Exception $e) {
        error_log("checkAllShopsAlerts Error: " . $e->getMessage());
        return 0;
    }
}
