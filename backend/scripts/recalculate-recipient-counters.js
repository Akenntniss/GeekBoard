const { pool } = require('../config/database');

/**
 * Script pour recalculer tous les compteurs de destinataires
 * Basé sur l'historique réel des SMS envoyés
 */

async function recalculateAllRecipientCounters() {
  try {
    console.log('🔄 Début du recalcul des compteurs de destinataires...');
    
    // Récupérer toutes les SIMs
    const [sims] = await pool.execute('SELECT id, carrier_name, phone_number FROM sims');
    
    console.log(`📋 ${sims.length} SIM(s) trouvée(s)`);
    
    for (const sim of sims) {
      console.log(`\n🔍 Traitement de la SIM ${sim.id} (${sim.carrier_name} - ${sim.phone_number})`);
      
      // Compter les destinataires uniques ce mois-ci
      const [result] = await pool.execute(`
        SELECT COUNT(DISTINCT recipient) as unique_recipients
        FROM sms_history 
        WHERE sim_id = ? 
          AND YEAR(created_at) = YEAR(CURDATE()) 
          AND MONTH(created_at) = MONTH(CURDATE())
      `, [sim.id]);

      const uniqueRecipients = result[0].unique_recipients || 0;

      // Mettre à jour le compteur
      await pool.execute(`
        UPDATE sims 
        SET recipients_monthly = ? 
        WHERE id = ?
      `, [uniqueRecipients, sim.id]);

      console.log(`✅ SIM ${sim.id}: ${uniqueRecipients} destinataires uniques ce mois`);
    }
    
    console.log('\n🎉 Recalcul terminé avec succès !');
    
    // Afficher un résumé
    console.log('\n📊 RÉSUMÉ FINAL:');
    const [summary] = await pool.execute(`
      SELECT 
        s.id,
        s.carrier_name,
        s.phone_number,
        s.recipients_monthly as destinataires_uniques,
        s.recipients_monthly_limit as limite_destinataires,
        s.messages_sent_month as sms_envoyes,
        s.monthly_limit as limite_sms,
        ROUND((s.recipients_monthly / s.recipients_monthly_limit) * 100, 2) as pct_destinataires,
        ROUND((s.messages_sent_month / s.monthly_limit) * 100, 2) as pct_sms
      FROM sims s
      ORDER BY s.carrier_name
    `);
    
    console.table(summary);
    
  } catch (error) {
    console.error('❌ Erreur lors du recalcul:', error);
    throw error;
  }
}

async function main() {
  try {
    await recalculateAllRecipientCounters();
    console.log('\n✅ Script terminé avec succès');
    process.exit(0);
  } catch (error) {
    console.error('\n❌ Le script a échoué:', error);
    process.exit(1);
  }
}

// Exécuter le script si appelé directement
if (require.main === module) {
  main();
}

module.exports = { recalculateAllRecipientCounters }; 