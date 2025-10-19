<?php
/**
 * Documentation des événements Webhook Stripe configurés pour GeekBoard
 * Ce fichier sert de référence pour comprendre quels événements sont gérés
 */

// Vérifier l'accès (supprimer en production)
$allowed_ips = ['127.0.0.1', '::1']; // Ajoutez votre IP si nécessaire
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    http_response_code(403);
    die('Accès refusé');
}

$stripe_config = include('config/stripe_config.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhooks Stripe - GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
        .event { background: #f8f9fa; border-left: 4px solid #007cba; padding: 15px; margin: 15px 0; }
        .event h3 { margin-top: 0; color: #007cba; }
        .status { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .status.configured { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .code { background: #f1f1f1; padding: 10px; border-radius: 5px; font-family: monospace; }
        .warning { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔗 Configuration Webhooks Stripe - GeekBoard</h1>
    
    <div class="success">
        <strong>✅ Configuration actuelle :</strong><br>
        <strong>Environnement :</strong> <?php echo $stripe_config['environment']; ?><br>
        <strong>URL Webhook :</strong> <?php echo $stripe_config['webhook_url']; ?><br>
        <strong>Secret configuré :</strong> <?php echo !empty($stripe_config['webhook_secret']) ? 'Oui' : 'Non'; ?>
    </div>
    
    <h2>📋 Événements Stripe configurés :</h2>
    
    <div class="event">
        <h3>checkout.session.completed <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> Quand un client termine avec succès le processus de checkout</p>
        <p><strong>Action :</strong> Création de l'abonnement dans GeekBoard et activation du shop</p>
        <p><strong>Données utilisées :</strong> shop_id, plan_id, subscription_id, customer_id</p>
    </div>
    
    <div class="event">
        <h3>customer.subscription.created <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> Quand un nouvel abonnement est créé</p>
        <p><strong>Action :</strong> Log de l'événement (géré principalement par checkout.session.completed)</p>
        <p><strong>Données utilisées :</strong> shop_id depuis metadata</p>
    </div>
    
    <div class="event">
        <h3>customer.subscription.updated <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> Quand un abonnement change (statut, plan, etc.)</p>
        <p><strong>Action :</strong> Synchronisation du statut local avec Stripe</p>
        <p><strong>Gère :</strong> Changements de plan, passage trial → active, suspensions</p>
        <div class="code">
            Statuts mappés :<br>
            • trialing → trial<br>
            • active → active<br>
            • canceled → cancelled<br>
            • past_due → past_due<br>
            • incomplete → pending
        </div>
    </div>
    
    <div class="event">
        <h3>customer.subscription.deleted <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> Quand un abonnement est annulé définitivement</p>
        <p><strong>Action :</strong> Marquer l'abonnement comme annulé dans GeekBoard</p>
        <p><strong>Effet :</strong> Le shop devient inactif</p>
    </div>
    
    <div class="event">
        <h3>customer.subscription.trial_will_end <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> 3 jours avant la fin de la période d'essai</p>
        <p><strong>Action :</strong> Enregistrement de notification + préparation rappels</p>
        <p><strong>Table créée :</strong> trial_notifications</p>
        <p><strong>Utilisation :</strong> Envoi d'emails/SMS de rappel (à implémenter)</p>
    </div>
    
    <div class="event">
        <h3>invoice.payment_succeeded <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> Quand un paiement de facture réussit</p>
        <p><strong>Action :</strong> Réactivation si l'abonnement était en souffrance</p>
        <p><strong>Transaction :</strong> Enregistrement dans payment_transactions</p>
    </div>
    
    <div class="event">
        <h3>invoice.payment_failed <span class="status configured">✅ Configuré</span></h3>
        <p><strong>Déclenchement :</strong> Quand un paiement de facture échoue</p>
        <p><strong>Action :</strong> Marquer l'abonnement en souffrance (past_due)</p>
        <p><strong>Effet :</strong> Le shop devient inactif jusqu'au paiement</p>
    </div>
    
    <h2>🔧 Configuration dans Stripe Dashboard :</h2>
    
    <div class="code">
        <strong>URL Endpoint :</strong> <?php echo $stripe_config['webhook_url']; ?><br><br>
        
        <strong>Événements à sélectionner :</strong><br>
        ✅ checkout.session.completed<br>
        ✅ customer.subscription.created<br>
        ✅ customer.subscription.updated<br>
        ✅ customer.subscription.deleted<br>
        ✅ customer.subscription.trial_will_end<br>
        ✅ invoice.payment_succeeded<br>
        ✅ invoice.payment_failed<br><br>
        
        <strong>Secret Webhook :</strong> <?php echo substr($stripe_config['webhook_secret'], 0, 20); ?>...
    </div>
    
    <h2>📊 Monitoring et Tests :</h2>
    
    <div class="event">
        <h3>Logs des Webhooks</h3>
        <p><strong>Fichier :</strong> logs/stripe_webhook.log</p>
        <p><strong>Contenu :</strong> Tous les webhooks reçus et leur traitement</p>
    </div>
    
    <div class="event">
        <h3>Test du Webhook</h3>
        <p><strong>URL GET :</strong> <a href="<?php echo $stripe_config['webhook_url']; ?>" target="_blank"><?php echo $stripe_config['webhook_url']; ?></a></p>
        <p><strong>Réponse attendue :</strong> JSON avec status "Webhook Stripe GeekBoard actif"</p>
    </div>
    
    <div class="event">
        <h3>Tables créées automatiquement</h3>
        <p><strong>trial_notifications :</strong> Notifications de fin d'essai</p>
        <div class="code">
            • shop_id : ID du magasin<br>
            • notification_type : 'trial_ending', 'trial_ended', 'payment_failed'<br>
            • trial_end_date : Date de fin d'essai<br>
            • email_sent / sms_sent : Flags d'envoi
        </div>
    </div>
    
    <div class="warning">
        <strong>⚠️ Sécurité :</strong><br>
        • Le webhook vérifie la signature Stripe<br>
        • Supprimez ce fichier de documentation en production<br>
        • Les logs peuvent contenir des données sensibles
    </div>
    
    <h2>🔄 Flux d'événements typiques :</h2>
    
    <div class="event">
        <h3>Nouvel Abonnement</h3>
        <p>1. checkout.session.completed → Création abonnement</p>
        <p>2. customer.subscription.created → Log confirmation</p>
        <p>3. customer.subscription.updated → Si changement de statut</p>
    </div>
    
    <div class="event">
        <h3>Fin d'Essai</h3>
        <p>1. customer.subscription.trial_will_end → Notification 3 jours avant</p>
        <p>2. customer.subscription.updated → Passage trial → active (si paiement configuré)</p>
        <p>3. invoice.payment_succeeded → Confirmation paiement</p>
    </div>
    
    <div class="event">
        <h3>Problème de Paiement</h3>
        <p>1. invoice.payment_failed → Marquer en souffrance</p>
        <p>2. customer.subscription.updated → Statut past_due</p>
        <p>3. invoice.payment_succeeded → Réactivation (si paiement corrigé)</p>
    </div>
    
    <p><small>Généré le <?php echo date('Y-m-d H:i:s'); ?> - GeekBoard Webhook Documentation</small></p>
</body>
</html>
