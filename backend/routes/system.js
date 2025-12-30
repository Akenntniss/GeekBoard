const express = require('express');
const router = express.Router();
const { pool } = require('../config/database');

// GET /api/system/config - Obtenir toute la configuration système
router.get('/config', async (req, res) => {
  try {
    const [configs] = await pool.execute(`
      SELECT config_key, config_value, description
      FROM system_config
      ORDER BY config_key
    `);
    
    // Convertir en objet pour faciliter l'utilisation côté client
    const configObject = {};
    configs.forEach(config => {
      configObject[config.config_key] = config.config_value;
    });
    
    res.json({
      success: true,
      data: configObject,
      details: configs
    });
  } catch (error) {
    console.error('Erreur lors de la récupération de la configuration:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// PUT /api/system/config - Mettre à jour la configuration système
router.put('/config', async (req, res) => {
  try {
    const updates = req.body;
    
    if (!updates || Object.keys(updates).length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Aucune configuration à mettre à jour'
      });
    }
    
    // Commencer une transaction
    const connection = await pool.getConnection();
    await connection.beginTransaction();
    
    try {
      const updatedKeys = [];
      
      for (const [key, value] of Object.entries(updates)) {
        // Vérifier si la clé de configuration existe
        const [existing] = await connection.execute(
          'SELECT config_key FROM system_config WHERE config_key = ?',
          [key]
        );
        
        if (existing.length > 0) {
          // Mettre à jour la configuration existante
          await connection.execute(
            'UPDATE system_config SET config_value = ?, updated_at = NOW() WHERE config_key = ?',
            [value, key]
          );
          updatedKeys.push(key);
        } else {
          // Insérer une nouvelle configuration
          await connection.execute(
            'INSERT INTO system_config (config_key, config_value, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
            [key, value, `Configuration ajoutée automatiquement: ${key}`]
          );
          updatedKeys.push(key);
        }
      }
      
      // Valider la transaction
      await connection.commit();
      
      res.json({
        success: true,
        message: `Configuration mise à jour avec succès`,
        updated_keys: updatedKeys
      });
      
    } catch (error) {
      // Annuler la transaction en cas d'erreur
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
    
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la configuration:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// GET /api/system/config/:key - Obtenir une configuration spécifique
router.get('/config/:key', async (req, res) => {
  try {
    const { key } = req.params;
    
    const [configs] = await pool.execute(`
      SELECT config_key, config_value, description, updated_at
      FROM system_config
      WHERE config_key = ?
    `, [key]);
    
    if (configs.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Configuration non trouvée'
      });
    }
    
    res.json({
      success: true,
      data: configs[0]
    });
  } catch (error) {
    console.error('Erreur lors de la récupération de la configuration:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// GET /api/system/failover/status - Obtenir le statut du basculement automatique
router.get('/failover/status', async (req, res) => {
  try {
    // Récupérer la configuration du basculement automatique
    const [configs] = await pool.execute(`
      SELECT config_key, config_value
      FROM system_config
      WHERE config_key IN ('auto_failover_enabled', 'failover_threshold', 'failover_check_interval', 'default_sim_id')
    `);
    
    const configObject = {};
    configs.forEach(config => {
      configObject[config.config_key] = config.config_value;
    });
    
    // Récupérer les informations de la SIM par défaut
    let defaultSimInfo = null;
    if (configObject.default_sim_id) {
      const [defaultSim] = await pool.execute(`
        SELECT s.*, p.model as phone_model
        FROM sims s
        LEFT JOIN phones p ON s.phone_id = p.id
        WHERE s.id = ?
      `, [configObject.default_sim_id]);
      
      if (defaultSim.length > 0) {
        defaultSimInfo = {
          ...defaultSim[0],
          is_active: Boolean(defaultSim[0].is_active),
          is_default: Boolean(defaultSim[0].is_default)
        };
      }
    }
    
    // Récupérer l'historique récent des basculements
    const [failoverHistory] = await pool.execute(`
      SELECT fh.*, 
             s1.carrier_name as from_sim_carrier, s1.phone_number as from_sim_number,
             s2.carrier_name as to_sim_carrier, s2.phone_number as to_sim_number
      FROM failover_history fh
      LEFT JOIN sims s1 ON fh.from_sim_id = s1.id
      LEFT JOIN sims s2 ON fh.to_sim_id = s2.id
      ORDER BY fh.created_at DESC
      LIMIT 10
    `);
    
    res.json({
      success: true,
      data: {
        enabled: configObject.auto_failover_enabled === 'true',
        threshold: parseInt(configObject.failover_threshold) || 90,
        check_interval: parseInt(configObject.failover_check_interval) || 5,
        default_sim: defaultSimInfo,
        recent_failovers: failoverHistory
      }
    });
  } catch (error) {
    console.error('Erreur lors de la récupération du statut de basculement:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// POST /api/system/failover/trigger - Déclencher manuellement un basculement
router.post('/failover/trigger', async (req, res) => {
  try {
    const { reason = 'Basculement manuel' } = req.body;
    
    // Récupérer la SIM par défaut actuelle
    const [currentDefault] = await pool.execute(`
      SELECT id, carrier_name, phone_number, messages_sent_month, monthly_limit, recipients_monthly, recipients_monthly_limit
      FROM sims
      WHERE is_default = TRUE AND is_active = TRUE
    `);
    
    if (currentDefault.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Aucune SIM par défaut active trouvée'
      });
    }
    
    // Trouver une SIM de remplacement disponible
    const [availableSims] = await pool.execute(`
      SELECT id, carrier_name, phone_number, messages_sent_month, monthly_limit, recipients_monthly, recipients_monthly_limit
      FROM sims
      WHERE is_active = TRUE 
        AND is_default = FALSE
        AND (messages_sent_month < monthly_limit * 0.9)
        AND (recipients_monthly < recipients_monthly_limit * 0.9)
      ORDER BY (messages_sent_month / monthly_limit) ASC
      LIMIT 1
    `);
    
    if (availableSims.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Aucune SIM de remplacement disponible'
      });
    }
    
    const newDefaultSim = availableSims[0];
    
    // Commencer une transaction
    const connection = await pool.getConnection();
    await connection.beginTransaction();
    
    try {
      // Retirer le statut par défaut de l'ancienne SIM
      await connection.execute(
        'UPDATE sims SET is_default = FALSE WHERE id = ?',
        [currentDefault[0].id]
      );
      
      // Définir la nouvelle SIM par défaut
      await connection.execute(
        'UPDATE sims SET is_default = TRUE WHERE id = ?',
        [newDefaultSim.id]
      );
      
      // Mettre à jour la configuration système
      await connection.execute(
        'UPDATE system_config SET config_value = ? WHERE config_key = "default_sim_id"',
        [newDefaultSim.id]
      );
      
      // Enregistrer l'historique du basculement
      await connection.execute(`
        INSERT INTO failover_history (from_sim_id, to_sim_id, reason, created_at)
        VALUES (?, ?, ?, NOW())
      `, [currentDefault[0].id, newDefaultSim.id, reason]);
      
      // Valider la transaction
      await connection.commit();
      
      res.json({
        success: true,
        message: 'Basculement effectué avec succès',
        data: {
          from_sim: {
            id: currentDefault[0].id,
            carrier: currentDefault[0].carrier_name,
            number: currentDefault[0].phone_number
          },
          to_sim: {
            id: newDefaultSim.id,
            carrier: newDefaultSim.carrier_name,
            number: newDefaultSim.phone_number
          },
          reason: reason
        }
      });
      
    } catch (error) {
      // Annuler la transaction en cas d'erreur
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
    
  } catch (error) {
    console.error('Erreur lors du basculement:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur lors du basculement'
    });
  }
});

// GET /api/system/stats - Obtenir les statistiques système
router.get('/stats', async (req, res) => {
  try {
    // Statistiques des SIMs
    const [simStats] = await pool.execute(`
      SELECT 
        COUNT(*) as total_sims,
        COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_sims,
        COUNT(CASE WHEN is_default = TRUE THEN 1 END) as default_sims,
        SUM(messages_sent_month) as total_messages_month,
        AVG(messages_sent_month / monthly_limit * 100) as avg_usage_percent
      FROM sims
    `);
    
    // Statistiques des téléphones
    const [phoneStats] = await pool.execute(`
      SELECT 
        COUNT(*) as total_phones,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_phones,
        COUNT(CASE WHEN last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 END) as online_phones
      FROM phones
    `);
    
    // Messages récents
    const [messageStats] = await pool.execute(`
      SELECT 
        COUNT(*) as total_messages_today,
        COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_today,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_today,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_today
      FROM sms_history
      WHERE DATE(created_at) = CURDATE()
    `);
    
    res.json({
      success: true,
      data: {
        sims: simStats[0],
        phones: phoneStats[0],
        messages: messageStats[0],
        timestamp: new Date().toISOString()
      }
    });
  } catch (error) {
    console.error('Erreur lors de la récupération des statistiques:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

module.exports = router; 