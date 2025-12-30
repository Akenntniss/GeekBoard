const express = require('express');
const router = express.Router();
const { pool, testConnection } = require('../config/database');

// GET /api/health - Test de santé de l'API
router.get('/', async (req, res) => {
  try {
    // Tester la connexion à la base de données
    const dbConnected = await testConnection();
    
    // Obtenir quelques statistiques
    let stats = {
      phones: 0,
      active_phones: 0,
      sims: 0,
      messages_today: 0
    };
    
    if (dbConnected) {
      try {
        // Compter les téléphones
        const [phoneCount] = await pool.execute('SELECT COUNT(*) as count FROM phones');
        stats.phones = phoneCount[0].count;
        
        // Compter les téléphones actifs
        const [activePhones] = await pool.execute('SELECT COUNT(*) as count FROM phones WHERE status = "active"');
        stats.active_phones = activePhones[0].count;
        
        // Compter les SIMs
        const [simCount] = await pool.execute('SELECT COUNT(*) as count FROM sims WHERE is_active = true');
        stats.sims = simCount[0].count;
        
        // Messages d'aujourd'hui
        const [todayMessages] = await pool.execute('SELECT COUNT(*) as count FROM sms_history WHERE DATE(created_at) = CURDATE()');
        stats.messages_today = todayMessages[0].count;
        
      } catch (error) {
        console.error('Erreur lors de la récupération des statistiques:', error);
      }
    }
    
    res.json({
      success: true,
      status: 'healthy',
      timestamp: new Date().toISOString(),
      database: dbConnected ? 'connected' : 'disconnected',
      version: '1.0.0',
      stats: stats
    });
    
  } catch (error) {
    console.error('Erreur lors du test de santé:', error);
    res.status(500).json({
      success: false,
      status: 'unhealthy',
      timestamp: new Date().toISOString(),
      database: 'error',
      version: '1.0.0',
      error: error.message
    });
  }
});

module.exports = router; 