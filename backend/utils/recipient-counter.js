const { pool } = require('../config/database');

/**
 * Met à jour le compteur de destinataires uniques pour une SIM
 * @param {number} simId - ID de la SIM
 * @param {string} recipient - Numéro du destinataire
 */
async function updateRecipientCounter(simId, recipient) {
  try {
    // Vérifier si ce destinataire a déjà été contacté ce mois-ci par cette SIM
    const [existing] = await pool.execute(`
      SELECT COUNT(*) as count
      FROM sms_history 
      WHERE sim_id = ? 
        AND recipient = ? 
        AND YEAR(created_at) = YEAR(CURDATE()) 
        AND MONTH(created_at) = MONTH(CURDATE())
    `, [simId, recipient]);

    // Si c'est un nouveau destinataire ce mois-ci, incrémenter le compteur
    if (existing[0].count === 1) { // 1 car le SMS vient d'être inséré
      await pool.execute(`
        UPDATE sims 
        SET recipients_monthly = recipients_monthly + 1 
        WHERE id = ?
      `, [simId]);
      
      console.log(`✅ Nouveau destinataire ${recipient} ajouté au compteur de la SIM ${simId}`);
      return true;
    }
    
    return false;
  } catch (error) {
    console.error('Erreur lors de la mise à jour du compteur de destinataires:', error);
    throw error;
  }
}

/**
 * Recalcule le compteur de destinataires pour une SIM spécifique
 * @param {number} simId - ID de la SIM
 */
async function recalculateRecipientCounter(simId) {
  try {
    // Compter les destinataires uniques ce mois-ci
    const [result] = await pool.execute(`
      SELECT COUNT(DISTINCT recipient) as unique_recipients
      FROM sms_history 
      WHERE sim_id = ? 
        AND YEAR(created_at) = YEAR(CURDATE()) 
        AND MONTH(created_at) = MONTH(CURDATE())
    `, [simId]);

    const uniqueRecipients = result[0].unique_recipients || 0;

    // Mettre à jour le compteur
    await pool.execute(`
      UPDATE sims 
      SET recipients_monthly = ? 
      WHERE id = ?
    `, [uniqueRecipients, simId]);

    console.log(`✅ Compteur de destinataires recalculé pour SIM ${simId}: ${uniqueRecipients} destinataires uniques`);
    return uniqueRecipients;
  } catch (error) {
    console.error('Erreur lors du recalcul du compteur de destinataires:', error);
    throw error;
  }
}

/**
 * Recalcule tous les compteurs de destinataires
 */
async function recalculateAllRecipientCounters() {
  try {
    // Récupérer toutes les SIMs
    const [sims] = await pool.execute('SELECT id FROM sims');
    
    console.log(`🔄 Recalcul des compteurs de destinataires pour ${sims.length} SIMs...`);
    
    for (const sim of sims) {
      await recalculateRecipientCounter(sim.id);
    }
    
    console.log('✅ Tous les compteurs de destinataires ont été recalculés');
  } catch (error) {
    console.error('Erreur lors du recalcul de tous les compteurs:', error);
    throw error;
  }
}

module.exports = {
  updateRecipientCounter,
  recalculateRecipientCounter,
  recalculateAllRecipientCounters
}; 