const express = require('express');
const router = express.Router();
const { pool } = require('../config/database');
const { updateRecipientCounter } = require('../utils/recipient-counter');
const SmartRetrySystem = require('../services/smart-retry-system');

// Instance du système de retry
const retrySystem = new SmartRetrySystem();

// POST /api/messages/send - Envoyer un SMS
router.post('/send', async (req, res) => {
  try {
    const { recipient, message, phone_id, sim_id } = req.body;
    
    // Validation des données
    if (!recipient || !message) {
      return res.status(400).json({
        success: false,
        message: 'Destinataire et message sont requis'
      });
    }
    
    // Vérifier le format du numéro
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    if (!phoneRegex.test(recipient.replace(/\s/g, ''))) {
      return res.status(400).json({
        success: false,
        message: 'Format de numéro invalide'
      });
    }
    
    // Sélectionner la SIM et le téléphone
    let selectedPhoneId = phone_id;
    let selectedSimId = sim_id;
    let selectedSimInfo = null;
    let usedDefaultSim = false;
    
    // Si une SIM spécifique est demandée, l'utiliser en priorité
    if (selectedSimId) {
      console.log(`🎯 SIM spécifique demandée: ${selectedSimId}`);
      
      // Vérifier que la SIM existe et est disponible
      const [requestedSim] = await pool.execute(`
        SELECT p.id, s.id as sim_id, s.slot_index, s.messages_sent_month, s.monthly_limit,
               s.carrier_name, s.phone_number, s.is_default, s.is_active
        FROM phones p
        JOIN sims s ON p.id = s.phone_id
        WHERE s.id = ? AND p.status = 'active' AND s.is_active = true
        AND s.messages_sent_month < s.monthly_limit
        LIMIT 1
      `, [selectedSimId]);
      
      if (requestedSim.length > 0) {
        selectedPhoneId = requestedSim[0].id;
        selectedSimId = requestedSim[0].sim_id;
        selectedSimInfo = requestedSim[0];
        usedDefaultSim = requestedSim[0].is_default;
        console.log(`✅ SIM ${selectedSimId} sélectionnée: ${selectedSimInfo.carrier_name}`);
      } else {
        // Vérifier pourquoi la SIM n'est pas disponible
        const [simCheck] = await pool.execute(`
          SELECT s.is_active, s.messages_sent_month, s.monthly_limit, s.carrier_name, s.phone_number, p.status
          FROM sims s
          LEFT JOIN phones p ON s.phone_id = p.id
          WHERE s.id = ?
          LIMIT 1
        `, [selectedSimId]);
        
        if (simCheck.length === 0) {
          return res.status(400).json({
            success: false,
            message: `SIM avec l'ID ${selectedSimId} non trouvée.`
          });
        }
        
        const sim = simCheck[0];
        if (!sim.is_active) {
          return res.status(503).json({
            success: false,
            message: `La SIM ${selectedSimId} (${sim.carrier_name} ${sim.phone_number}) est inactive.`
          });
        } else if (sim.messages_sent_month >= sim.monthly_limit) {
          return res.status(503).json({
            success: false,
            message: `La SIM ${selectedSimId} (${sim.carrier_name} ${sim.phone_number}) a atteint son quota mensuel (${sim.monthly_limit} SMS).`
          });
        } else if (sim.status !== 'active') {
          return res.status(503).json({
            success: false,
            message: `Le téléphone associé à la SIM ${selectedSimId} n'est pas actif.`
          });
        }
      }
    }
    
    // Si aucune SIM spécifique ou si la SIM demandée n'est pas disponible, utiliser la logique par défaut
    if (!selectedPhoneId) {
      console.log('🔄 Sélection automatique de SIM...');
      
      // Prioriser la SIM par défaut
      const [defaultSim] = await pool.execute(`
        SELECT p.id, s.id as sim_id, s.slot_index, s.messages_sent_month, s.monthly_limit,
               s.carrier_name, s.phone_number, s.is_default
        FROM phones p
        JOIN sims s ON p.id = s.phone_id
        WHERE p.status = 'active' AND s.is_active = true AND s.is_default = true
        AND s.messages_sent_month < s.monthly_limit
        LIMIT 1
      `);
      
      if (defaultSim.length > 0) {
        // Utiliser la SIM par défaut si disponible
        selectedPhoneId = defaultSim[0].id;
        selectedSimId = defaultSim[0].sim_id;
        selectedSimInfo = defaultSim[0];
        usedDefaultSim = true;
        console.log(`✅ SIM par défaut sélectionnée: ${selectedSimInfo.carrier_name}`);
      } else {
        // Vérifier si la SIM par défaut existe mais n'est pas disponible
        const [defaultSimCheck] = await pool.execute(`
          SELECT s.is_active, s.messages_sent_month, s.monthly_limit, s.carrier_name, s.phone_number
          FROM sims s
          WHERE s.is_default = true
          LIMIT 1
        `);
        
        if (defaultSimCheck.length > 0) {
          const defaultSim = defaultSimCheck[0];
          if (!defaultSim.is_active) {
            return res.status(503).json({
              success: false,
              message: `La SIM par défaut (${defaultSim.carrier_name} ${defaultSim.phone_number}) est inactive. Veuillez l'activer.`
            });
          } else if (defaultSim.messages_sent_month >= defaultSim.monthly_limit) {
            return res.status(503).json({
              success: false,
              message: `La SIM par défaut (${defaultSim.carrier_name} ${defaultSim.phone_number}) a atteint son quota mensuel (${defaultSim.monthly_limit} SMS).`
            });
          }
        }
        
        // Fallback: sélectionner n'importe quelle SIM active disponible
        const [activePhones] = await pool.execute(`
          SELECT p.id, s.id as sim_id, s.slot_index, s.messages_sent_month, s.monthly_limit,
                 s.carrier_name, s.phone_number, s.is_default
          FROM phones p
          JOIN sims s ON p.id = s.phone_id
          WHERE p.status = 'active' AND s.is_active = true
          AND s.messages_sent_month < s.monthly_limit
          ORDER BY s.messages_sent_month ASC, RAND()
          LIMIT 1
        `);
        
        if (activePhones.length === 0) {
          return res.status(503).json({
            success: false,
            message: 'Aucune SIM disponible pour envoyer le SMS. Toutes les SIMs ont atteint leur quota ou sont inactives.'
          });
        }
        
        selectedPhoneId = activePhones[0].id;
        selectedSimId = activePhones[0].sim_id;
        selectedSimInfo = activePhones[0];
        usedDefaultSim = false;
        console.log(`✅ SIM alternative sélectionnée: ${selectedSimInfo.carrier_name}`);
      }
    }
    
    console.log(`📤 Envoi SMS via SIM ${selectedSimId} (${selectedSimInfo?.carrier_name})`);
    
    // Insérer le message dans la queue
    const [result] = await pool.execute(`
      INSERT INTO sms_history (phone_id, sim_id, recipient, message, status, created_at)
      VALUES (?, ?, ?, ?, 'pending', NOW())
    `, [selectedPhoneId, selectedSimId, recipient, message]);
    
    const messageId = result.insertId;
    
    // Incrémenter le compteur de messages
    if (selectedSimId) {
      await pool.execute(`
        UPDATE sims SET messages_sent_month = messages_sent_month + 1
        WHERE id = ?
      `, [selectedSimId]);
      
      // Mettre à jour le compteur de destinataires uniques
      try {
        await updateRecipientCounter(selectedSimId, recipient);
      } catch (error) {
        console.error('Erreur lors de la mise à jour du compteur de destinataires:', error);
        // Ne pas faire échouer l'envoi pour cette erreur
      }
    }
    
    res.status(201).json({
      success: true,
      message: 'SMS ajouté à la queue d\'envoi',
      data: {
        message_id: messageId,
        phone_id: selectedPhoneId,
        sim_id: selectedSimId,
        status: 'pending',
        sim_info: selectedSimInfo ? {
          carrier_name: selectedSimInfo.carrier_name,
          phone_number: selectedSimInfo.phone_number,
          is_default: usedDefaultSim,
          messages_sent: selectedSimInfo.messages_sent_month + 1,
          monthly_limit: selectedSimInfo.monthly_limit
        } : null
      }
    });
    
  } catch (error) {
    console.error('Erreur lors de l\'envoi du SMS:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// POST /api/messages/bulk - Envoi en masse
router.post('/bulk', async (req, res) => {
  try {
    const { recipients, message, phone_id } = req.body;
    
    if (!recipients || !Array.isArray(recipients) || recipients.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Liste de destinataires requise'
      });
    }
    
    if (!message) {
      return res.status(400).json({
        success: false,
        message: 'Message requis'
      });
    }
    
    if (recipients.length > 1000) {
      return res.status(400).json({
        success: false,
        message: 'Maximum 1000 destinataires par envoi'
      });
    }
    
    const results = [];
    const errors = [];
    
    // Traiter chaque destinataire
    for (const recipient of recipients) {
      try {
        // Validation du numéro
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(recipient.replace(/\s/g, ''))) {
          errors.push({ recipient, error: 'Format invalide' });
          continue;
        }
        
        // Sélectionner un téléphone disponible (prioriser la SIM par défaut)
        let selectedPhone = null;
        
        if (!phone_id) {
          // Prioriser la SIM par défaut
          const [defaultSim] = await pool.execute(`
            SELECT p.id, s.id as sim_id, s.messages_sent_month, s.monthly_limit
            FROM phones p
            JOIN sims s ON p.id = s.phone_id
            WHERE p.status = 'active' AND s.is_active = true AND s.is_default = true
            AND s.messages_sent_month < s.monthly_limit
            LIMIT 1
          `);
          
          if (defaultSim.length > 0) {
            selectedPhone = defaultSim[0];
          }
        }
        
        if (!selectedPhone) {
          // Fallback: sélectionner n'importe quelle SIM active disponible
          const [activePhones] = await pool.execute(`
            SELECT p.id, s.id as sim_id, s.messages_sent_month, s.monthly_limit
            FROM phones p
            JOIN sims s ON p.id = s.phone_id
            WHERE p.status = 'active' AND s.is_active = true
            AND s.messages_sent_month < s.monthly_limit
            ${phone_id ? 'AND p.id = ?' : ''}
            ORDER BY s.messages_sent_month ASC, RAND()
            LIMIT 1
          `, phone_id ? [phone_id] : []);
          
          if (activePhones.length === 0) {
            errors.push({ recipient, error: 'Aucun téléphone disponible' });
            continue;
          }
          
          selectedPhone = activePhones[0];
        }
        
        // Insérer le message
        const [result] = await pool.execute(`
          INSERT INTO sms_history (phone_id, sim_id, recipient, message, status, created_at)
          VALUES (?, ?, ?, ?, 'pending', NOW())
        `, [selectedPhone.id, selectedPhone.sim_id, recipient, message]);
        
        // Incrémenter le compteur
        await pool.execute(`
          UPDATE sims SET messages_sent_month = messages_sent_month + 1
          WHERE id = ?
        `, [selectedPhone.sim_id]);
        
        // Mettre à jour le compteur de destinataires uniques
        try {
          await updateRecipientCounter(selectedPhone.sim_id, recipient);
        } catch (error) {
          console.error('Erreur lors de la mise à jour du compteur de destinataires:', error);
          // Ne pas faire échouer l'envoi pour cette erreur
        }
        
        results.push({
          recipient,
          message_id: result.insertId,
          phone_id: selectedPhone.id,
          status: 'pending'
        });
        
      } catch (error) {
        errors.push({ recipient, error: error.message });
      }
    }
    
    res.status(201).json({
      success: true,
      message: `${results.length} SMS ajoutés à la queue`,
      data: {
        sent: results.length,
        errors: errors.length,
        results: results,
        errors: errors
      }
    });
    
  } catch (error) {
    console.error('Erreur lors de l\'envoi en masse:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// Route supprimée - duplication avec la route ligne 434

// PUT /api/messages/:id/status - Mettre à jour le statut d'un message
router.put('/:id/status', async (req, res) => {
  try {
    const { id } = req.params;
    const { status, failure_reason, phone_id, sim_id } = req.body;
    
    if (!['sent', 'failed', 'delivered'].includes(status)) {
      return res.status(400).json({
        success: false,
        message: 'Statut invalide'
      });
    }
    
    console.log(`📝 Mise à jour statut message ${id}: ${status}${failure_reason ? ` (${failure_reason})` : ''}`);
    
    const updateFields = ['status = ?'];
    const updateValues = [status];
    
    if (status === 'sent') {
      updateFields.push('sent_at = NOW()');
    } else if (status === 'delivered') {
      updateFields.push('delivered_at = NOW()');
    } else if (status === 'failed' && failure_reason) {
      updateFields.push('failure_reason = ?');
      updateValues.push(failure_reason);
    }
    
    updateValues.push(id);
    
    await pool.execute(`
      UPDATE sms_history 
      SET ${updateFields.join(', ')}, updated_at = NOW()
      WHERE id = ?
    `, updateValues);
    
    // Si le message a échoué, déclencher le système de retry intelligent
    if (status === 'failed') {
      // Si pas de failure_reason spécifique, utiliser un code générique pour les problèmes opérateur
      const errorCode = failure_reason || 'OPERATOR_FAILURE';
      console.log(`🔄 Déclenchement du système de retry pour message ${id} avec code: ${errorCode}`);
      
      // Traitement asynchrone pour ne pas bloquer la réponse
      setImmediate(async () => {
        try {
          await retrySystem.handleFailedMessage(id, errorCode, phone_id, sim_id);
        } catch (error) {
          console.error(`❌ Erreur lors du traitement retry pour message ${id}:`, error);
        }
      });
    }
    
    res.json({
      success: true,
      message: 'Statut mis à jour',
      retry_triggered: status === 'failed' && failure_reason ? true : false
    });
    
  } catch (error) {
    console.error('Erreur lors de la mise à jour du statut:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// GET /api/messages/history - Historique des messages
router.get('/history', async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 50;
    const offset = (page - 1) * limit;
    
    // Compter le total des messages
    const [countResult] = await pool.execute(`
      SELECT COUNT(*) as total FROM sms_history
    `);
    const total = countResult[0].total;
    
    // Récupérer les messages avec pagination
    const [messages] = await pool.execute(`
      SELECT h.*, p.model as phone_model, s.slot_index
      FROM sms_history h
      LEFT JOIN phones p ON h.phone_id = p.id
      LEFT JOIN sims s ON h.sim_id = s.id
      ORDER BY h.created_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `);
    
    res.json({
      success: true,
      data: messages,
      pagination: {
        page: page,
        limit: limit,
        total: total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Erreur lors de la récupération de l\'historique:', error);
    // Retourner des données vides au lieu d'une erreur
    res.json({
      success: true,
      data: [],
      pagination: {
        page: 1,
        limit: 50,
        total: 0,
        pages: 0
      }
    });
  }
});

// GET /api/messages/stats - Statistiques des messages
router.get('/stats', async (req, res) => {
  try {
    const [todayStats] = await pool.execute(`
      SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered
      FROM sms_history 
      WHERE DATE(created_at) = CURDATE()
    `);
    
    const [weeklyStats] = await pool.execute(`
      SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
      FROM sms_history 
      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
      GROUP BY DATE(created_at)
      ORDER BY date DESC
    `);
    
    res.json({
      success: true,
      data: {
        today: todayStats[0],
        weekly: weeklyStats
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

// GET /api/messages/test - Route de test
router.get('/test', async (req, res) => {
  res.json({
    success: true,
    message: 'Route de test fonctionnelle'
  });
});

// GET /api/messages/pending-simple/:phone_id - Route simple pour tester
router.get('/pending-simple/:phone_id', async (req, res) => {
  try {
    const { phone_id } = req.params;
    console.log('🔍 Test simple pour phone_id:', phone_id);
    
    res.json({
      success: true,
      data: [],
      phone_id: phone_id
    });
    
  } catch (error) {
    console.error('❌ Erreur test simple:', error);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur'
    });
  }
});

// GET /api/messages/pending/:phone_id - Récupérer les messages en attente pour un téléphone
router.get('/pending/:phone_id', async (req, res) => {
  try {
    const { phone_id } = req.params;
    const limit = parseInt(req.query.limit) || 10;
    
    console.log('🔍 Récupération des messages en attente pour phone_id:', phone_id);
    console.log('📊 Limite:', limit);
    
    const [messages] = await pool.execute(
      `SELECT 
        h.id, 
        h.phone_id,
        h.recipient, 
        h.message as content, 
        h.sim_id, 
        h.created_at, 
        h.status,
        h.retry_count,
        COALESCE(s.subscription_id, 0) as subscription_id,
        COALESCE(s.slot_index, 0) as slot_index,
        COALESCE(s.carrier_name, 'SIM inconnue') as carrier_name,
        s.phone_number,
        s.is_active
      FROM sms_history h
      LEFT JOIN sims s ON h.sim_id = s.id
      WHERE h.phone_id = ? AND h.status = ? 
      ORDER BY h.created_at ASC 
      LIMIT ${limit}`,
      [phone_id, 'pending']
    );
    
    console.log('📨 Messages trouvés:', messages.length);
    
    // Debug: Afficher les détails de chaque message
    messages.forEach(msg => {
      console.log(`📋 Message ${msg.id}: sim_id=${msg.sim_id}, slot_index=${msg.slot_index}, subscription_id=${msg.subscription_id}, carrier=${msg.carrier_name}`);
    });
    
    res.json({
      success: true,
      data: messages
    });
    
  } catch (error) {
    console.error('❌ Erreur lors de la récupération des messages en attente:', error);
    console.error('❌ Détails de l\'erreur:', error.message);
    res.status(500).json({
      success: false,
      message: 'Erreur serveur',
      error: error.message
    });
  }
});

module.exports = router; 
 
 