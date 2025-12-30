const express = require('express');
const router = express.Router();
const { pool } = require('../config/database');

// GET /api/autoswitch/config - Récupérer la configuration
router.get('/config', async (req, res) => {
  try {
    const [config] = await pool.execute(`
      SELECT * FROM auto_switch_config ORDER BY id DESC LIMIT 1
    `);
    
    if (config.length === 0) {
      // Créer une configuration par défaut si elle n'existe pas
      await pool.execute(`
        INSERT INTO auto_switch_config 
        (auto_switch_enabled, quota_alert_threshold, sms_notifications_enabled, 
         switch_notifications_enabled, notification_phone_number, quota_check_frequency)
        VALUES (false, 80, false, false, NULL, 300)
      `);
      
      const [newConfig] = await pool.execute(`
        SELECT * FROM auto_switch_config ORDER BY id DESC LIMIT 1
      `);
      
      return res.json({
        success: true,
        data: newConfig[0]
      });
    }
    
    res.json({
      success: true,
      data: config[0]
    });
    
  } catch (error) {
    console.error('Erreur lors de la récupération de la configuration:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// PUT /api/autoswitch/config - Mettre à jour la configuration
router.put('/config', async (req, res) => {
  try {
    const {
      auto_switch_enabled,
      quota_alert_threshold,
      sms_notifications_enabled,
      switch_notifications_enabled,
      notification_phone_number,
      quota_check_frequency
    } = req.body;
    
    // Validation des données
    if (quota_alert_threshold < 1 || quota_alert_threshold > 100) {
      return res.status(400).json({
        success: false,
        message: 'Le seuil d\'alerte doit être entre 1 et 100%'
      });
    }
    
    if (quota_check_frequency < 30) {
      return res.status(400).json({
        success: false,
        message: 'La fréquence de vérification doit être d\'au moins 30 secondes'
      });
    }
    
    if (notification_phone_number && !/^[\+]?[1-9][\d]{0,15}$/.test(notification_phone_number.replace(/\s/g, ''))) {
      return res.status(400).json({
        success: false,
        message: 'Format de numéro de téléphone invalide'
      });
    }
    
    // Vérifier si une configuration existe
    const [existing] = await pool.execute(`
      SELECT id FROM auto_switch_config ORDER BY id DESC LIMIT 1
    `);
    
    if (existing.length === 0) {
      // Créer une nouvelle configuration
      await pool.execute(`
        INSERT INTO auto_switch_config 
        (auto_switch_enabled, quota_alert_threshold, sms_notifications_enabled, 
         switch_notifications_enabled, notification_phone_number, quota_check_frequency)
        VALUES (?, ?, ?, ?, ?, ?)
      `, [
        auto_switch_enabled || false,
        quota_alert_threshold || 80,
        sms_notifications_enabled || false,
        switch_notifications_enabled || false,
        notification_phone_number || null,
        quota_check_frequency || 180
      ]);
    } else {
      // Mettre à jour la configuration existante
      await pool.execute(`
        UPDATE auto_switch_config 
        SET auto_switch_enabled = ?, 
            quota_alert_threshold = ?, 
            sms_notifications_enabled = ?, 
            switch_notifications_enabled = ?, 
            notification_phone_number = ?, 
            quota_check_frequency = ?,
            updated_at = NOW()
        WHERE id = ?
      `, [
        auto_switch_enabled || false,
        quota_alert_threshold || 80,
        sms_notifications_enabled || false,
        switch_notifications_enabled || false,
        notification_phone_number || null,
        quota_check_frequency || 180,
        existing[0].id
      ]);
    }
    
    // Récupérer la configuration mise à jour
    const [updatedConfig] = await pool.execute(`
      SELECT * FROM auto_switch_config ORDER BY id DESC LIMIT 1
    `);
    
    res.json({
      success: true,
      message: 'Configuration mise à jour avec succès',
      data: updatedConfig[0]
    });
    
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la configuration:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// GET /api/autoswitch/status - Statut du système d'auto-switch
router.get('/status', async (req, res) => {
  try {
    // Récupérer la configuration
    const [config] = await pool.execute(`
      SELECT * FROM auto_switch_config ORDER BY id DESC LIMIT 1
    `);
    
    if (config.length === 0) {
      return res.json({
        success: true,
        data: {
          enabled: false,
          message: 'Configuration non trouvée'
        }
      });
    }
    
    const autoSwitchConfig = config[0];
    
    // Récupérer les statistiques des SIMs
    const [sims] = await pool.execute(`
      SELECT 
        id,
        phone_number,
        carrier_name,
        is_active,
        is_default,
        monthly_limit,
        messages_sent_month,
        recipients_monthly_limit,
        recipients_monthly,
        ROUND((messages_sent_month / monthly_limit) * 100, 2) as sms_usage_percentage,
        ROUND((recipients_monthly / recipients_monthly_limit) * 100, 2) as recipients_usage_percentage,
        GREATEST(
          ROUND((messages_sent_month / monthly_limit) * 100, 2),
          ROUND((recipients_monthly / recipients_monthly_limit) * 100, 2)
        ) as max_usage_percentage
      FROM sims 
      WHERE is_active = true
      ORDER BY is_default DESC, max_usage_percentage ASC
    `);
    
    // Analyser les SIMs
    const defaultSim = sims.find(sim => sim.is_default);
    const availableSims = sims.filter(sim => 
      !sim.is_default && 
      sim.max_usage_percentage < autoSwitchConfig.quota_alert_threshold
    );
    
    let status = 'normal';
    let message = 'Système fonctionnel';
    
    if (defaultSim && defaultSim.max_usage_percentage >= autoSwitchConfig.quota_alert_threshold) {
      // Déterminer quel quota est le plus critique
      const smsQuotaReached = defaultSim.sms_usage_percentage >= autoSwitchConfig.quota_alert_threshold;
      const recipientsQuotaReached = defaultSim.recipients_usage_percentage >= autoSwitchConfig.quota_alert_threshold;
      
      let quotaType = '';
      if (smsQuotaReached && recipientsQuotaReached) {
        quotaType = 'SMS et destinataires';
      } else if (smsQuotaReached) {
        quotaType = 'SMS';
      } else if (recipientsQuotaReached) {
        quotaType = 'destinataires';
      }
      
      if (availableSims.length > 0) {
        status = 'ready_to_switch';
        message = `SIM par défaut: quota ${quotaType} à ${defaultSim.max_usage_percentage}% - ${availableSims.length} SIM(s) disponible(s)`;
      } else {
        status = 'no_alternative';
        message = `SIM par défaut: quota ${quotaType} à ${defaultSim.max_usage_percentage}% - Aucune SIM alternative disponible`;
      }
    }
    
    res.json({
      success: true,
      data: {
        enabled: autoSwitchConfig.auto_switch_enabled,
        status: status,
        message: message,
        default_sim: defaultSim ? {
          id: defaultSim.id,
          phone_number: defaultSim.phone_number,
          carrier_name: defaultSim.carrier_name,
          sms_usage_percentage: defaultSim.sms_usage_percentage,
          recipients_usage_percentage: defaultSim.recipients_usage_percentage,
          max_usage_percentage: defaultSim.max_usage_percentage,
          messages_sent: defaultSim.messages_sent_month,
          monthly_limit: defaultSim.monthly_limit,
          recipients_sent: defaultSim.recipients_monthly,
          recipients_limit: defaultSim.recipients_monthly_limit
        } : null,
        available_sims: availableSims.map(sim => ({
          id: sim.id,
          phone_number: sim.phone_number,
          carrier_name: sim.carrier_name,
          sms_usage_percentage: sim.sms_usage_percentage,
          recipients_usage_percentage: sim.recipients_usage_percentage,
          max_usage_percentage: sim.max_usage_percentage,
          messages_sent: sim.messages_sent_month,
          monthly_limit: sim.monthly_limit,
          recipients_sent: sim.recipients_monthly,
          recipients_limit: sim.recipients_monthly_limit
        })),
        config: autoSwitchConfig
      }
    });
    
  } catch (error) {
    console.error('Erreur lors de la récupération du statut:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// POST /api/autoswitch/switch - Effectuer le basculement automatique
router.post('/switch', async (req, res) => {
  try {
    // Récupérer la configuration
    const [config] = await pool.execute(`
      SELECT * FROM auto_switch_config ORDER BY id DESC LIMIT 1
    `);
    
    if (config.length === 0 || !config[0].auto_switch_enabled) {
      return res.status(400).json({
        success: false,
        message: 'Auto-switch désactivé'
      });
    }
    
    const autoSwitchConfig = config[0];
    
    // Récupérer les SIMs avec leurs quotas
    const [sims] = await pool.execute(`
      SELECT 
        id,
        phone_number,
        carrier_name,
        is_active,
        is_default,
        monthly_limit,
        messages_sent_month,
        recipients_monthly_limit,
        recipients_monthly,
        ROUND((messages_sent_month / monthly_limit) * 100, 2) as sms_usage_percentage,
        ROUND((recipients_monthly / recipients_monthly_limit) * 100, 2) as recipients_usage_percentage,
        GREATEST(
          ROUND((messages_sent_month / monthly_limit) * 100, 2),
          ROUND((recipients_monthly / recipients_monthly_limit) * 100, 2)
        ) as max_usage_percentage
      FROM sims 
      WHERE is_active = true
      ORDER BY max_usage_percentage ASC
    `);
    
    const currentDefaultSim = sims.find(sim => sim.is_default);
    
    if (!currentDefaultSim) {
      return res.status(400).json({
        success: false,
        message: 'Aucune SIM par défaut trouvée'
      });
    }
    
    // Vérifier si le basculement est nécessaire
    if (currentDefaultSim.max_usage_percentage < autoSwitchConfig.quota_alert_threshold) {
      return res.json({
        success: true,
        message: 'Basculement non nécessaire - quotas sous le seuil',
        switched: false
      });
    }
    
    // Trouver la meilleure SIM alternative
    const availableSims = sims.filter(sim => 
      !sim.is_default && 
      sim.max_usage_percentage < autoSwitchConfig.quota_alert_threshold
    );
    
    if (availableSims.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Aucune SIM alternative disponible'
      });
    }
    
    // Choisir la SIM avec le quota le plus bas
    const newDefaultSim = availableSims[0];
    
    // Effectuer le basculement dans une transaction
    const connection = await pool.getConnection();
    await connection.beginTransaction();
    
    try {
      // Retirer le statut par défaut de l'ancienne SIM
      await connection.execute(
        'UPDATE sims SET is_default = false WHERE id = ?',
        [currentDefaultSim.id]
      );
      
      // Définir la nouvelle SIM par défaut
      await connection.execute(
        'UPDATE sims SET is_default = true WHERE id = ?',
        [newDefaultSim.id]
      );
      
      // Enregistrer le basculement dans les logs
      await connection.execute(`
        INSERT INTO auto_switch_logs 
        (from_sim_id, to_sim_id, reason, from_quota_percentage, to_quota_percentage, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
      `, [
        currentDefaultSim.id,
        newDefaultSim.id,
        `Quota atteint: ${currentDefaultSim.max_usage_percentage}%`,
        currentDefaultSim.max_usage_percentage,
        newDefaultSim.max_usage_percentage
      ]);
      
      await connection.commit();
      
      // Envoyer notification SMS si activé
      if (autoSwitchConfig.switch_notifications_enabled && autoSwitchConfig.notification_phone_number) {
        const notificationMessage = `🔄 BASCULEMENT AUTO: SIM par défaut changée de ${currentDefaultSim.carrier_name} (${currentDefaultSim.phone_number}) vers ${newDefaultSim.carrier_name} (${newDefaultSim.phone_number}) - Quota atteint: ${currentDefaultSim.max_usage_percentage}%. Nouveaux quotas: SMS ${newDefaultSim.messages_sent_month}/${newDefaultSim.monthly_limit}, Destinataires ${newDefaultSim.recipients_monthly}/${newDefaultSim.recipients_monthly_limit}.`;
        
        try {
          await fetch('http://168.231.85.4:3001/api/messages/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              recipient: autoSwitchConfig.notification_phone_number,
              message: notificationMessage
            })
          });
        } catch (notifError) {
          console.error('Erreur envoi notification:', notifError);
        }
      }
      
      res.json({
        success: true,
        message: `Basculement effectué: ${currentDefaultSim.carrier_name} → ${newDefaultSim.carrier_name}`,
        switched: true,
        from_sim: {
          id: currentDefaultSim.id,
          carrier_name: currentDefaultSim.carrier_name,
          phone_number: currentDefaultSim.phone_number,
          quota_percentage: currentDefaultSim.max_usage_percentage
        },
        to_sim: {
          id: newDefaultSim.id,
          carrier_name: newDefaultSim.carrier_name,
          phone_number: newDefaultSim.phone_number,
          quota_percentage: newDefaultSim.max_usage_percentage
        }
      });
      
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
    
  } catch (error) {
    console.error('Erreur lors du basculement:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur lors du basculement automatique'
    });
  }
});

// POST /api/autoswitch/test-notification - Tester les notifications
router.post('/test-notification', async (req, res) => {
  try {
    const { phone_number, message_type } = req.body;
    
    if (!phone_number) {
      return res.status(400).json({
        success: false,
        message: 'Numéro de téléphone requis'
      });
    }
    
    let testMessage = '';
    switch (message_type) {
      case 'quota_alert':
        testMessage = '🚨 ALERTE QUOTA: La SIM par défaut (Free +33745520054) a atteint 85% de ses quotas mensuels (SMS: 25500/30000, Destinataires: 42/50). Basculement automatique activé.';
        break;
      case 'switch_notification':
        testMessage = '🔄 BASCULEMENT AUTO: SIM par défaut changée de Free +33745520054 vers Orange +33612345678 (quota atteint). Nouveaux quotas: SMS 2500/30000, Destinataires 5/50.';
        break;
      default:
        testMessage = '✅ TEST: Notification Auto-Switch SMS Gateway - Système fonctionnel (quotas SMS et destinataires surveillés).';
    }
    
    // Envoyer le SMS de test via l'API interne
    const sendResponse = await fetch('http://168.231.85.4:3001/api/messages/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        recipient: phone_number,
        message: testMessage
      })
    });
    
    if (sendResponse.ok) {
      res.json({
        success: true,
        message: 'SMS de test envoyé avec succès'
      });
    } else {
      throw new Error('Erreur lors de l\'envoi du SMS de test');
    }
    
  } catch (error) {
    console.error('Erreur lors du test de notification:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur lors de l\'envoi du SMS de test'
    });
  }
});

module.exports = router; 