<?php
// Script de debug pour vérifier l'initialisation de la période d'essai
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/classes/SubscriptionManager.php');

$subdomain = 'kolipuio';

try {
    $pdo = getMainDBConnection();
    
    // Récupérer les infos du shop
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo "❌ Shop '$subdomain' non trouvé\n";
        exit(1);
    }
    
    echo "=== Informations Shop ===\n";
    echo "ID: " . $shop['id'] . "\n";
    echo "Name: " . $shop['name'] . "\n";
    echo "Subdomain: " . $shop['subdomain'] . "\n";
    echo "Subscription Status: " . ($shop['subscription_status'] ?? 'NULL') . "\n";
    echo "Trial Started At: " . ($shop['trial_started_at'] ?? 'NULL') . "\n";
    echo "Trial Ends At: " . ($shop['trial_ends_at'] ?? 'NULL') . "\n";
    echo "Active: " . ($shop['active'] ?? 'NULL') . "\n\n";
    
    // Vérifier la table subscriptions
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE shop_id = ?");
    $stmt->execute([$shop['id']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== Subscriptions Table ===\n";
    if ($subscription) {
        echo "Subscription ID: " . $subscription['id'] . "\n";
        echo "Plan ID: " . $subscription['plan_id'] . "\n";
        echo "Status: " . $subscription['status'] . "\n";
        echo "Trial Start Date: " . ($subscription['trial_start_date'] ?? 'NULL') . "\n";
        echo "Trial End Date: " . ($subscription['trial_end_date'] ?? 'NULL') . "\n";
    } else {
        echo "❌ Aucune subscription trouvée\n";
    }
    
    echo "\n=== Test initializeTrialPeriod ===\n";
    $manager = new SubscriptionManager($shop['id']);
    $result = $manager->initializeTrialPeriod($shop['id']);
    
    if ($result) {
        echo "✅ initializeTrialPeriod a réussi\n";
        
        // Re-vérifier les données
        $stmt = $pdo->prepare("SELECT trial_started_at, trial_ends_at FROM shops WHERE id = ?");
        $stmt->execute([$shop['id']]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Nouvelles dates:\n";
        echo "  Trial Started At: " . ($updated['trial_started_at'] ?? 'NULL') . "\n";
        echo "  Trial Ends At: " . ($updated['trial_ends_at'] ?? 'NULL') . "\n";
    } else {
        echo "❌ initializeTrialPeriod a échoué\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
