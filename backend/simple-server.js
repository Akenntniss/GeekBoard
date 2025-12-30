const express = require('express');
const cors = require('cors');
const path = require('path');
const { testConnection, pool } = require('./config/database');
const autoSwitchService = require('./services/autoswitch-service');

const app = express();
const PORT = 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Servir les fichiers statiques du frontend
app.use(express.static(path.join(__dirname, '../frontend')));

// Route de base
app.get('/', (req, res) => {
  res.json({
    message: 'SMS Gateway API',
    version: '1.0.0',
    status: 'running'
  });
});

// Route de santé
app.get('/health', async (req, res) => {
  try {
    const dbConnected = await testConnection();
    res.json({
      success: true,
      status: 'healthy',
      database: dbConnected ? 'connected' : 'disconnected',
      version: '1.0.0'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      status: 'unhealthy',
      error: error.message
    });
  }
});

// Route de santé alternative
app.get('/api/health', async (req, res) => {
  try {
    const dbConnected = await testConnection();
    res.json({
      success: true,
      status: 'healthy',
      database: dbConnected ? 'connected' : 'disconnected',
      version: '1.0.0'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      status: 'unhealthy',
      error: error.message
    });
  }
});

// Routes
app.use('/api/phones', require('./routes/phones'));
app.use('/api/messages', require('./routes/messages'));
app.use('/api/sims', require('./routes/sims'));
app.use('/api/system', require('./routes/system'));
app.use('/api/autoswitch', require('./routes/autoswitch'));

// Démarrage du serveur
async function start() {
  try {
    console.log('🔍 Test de la connexion à la base de données...');
    const dbConnected = await testConnection();
    
    if (!dbConnected) {
      console.error('❌ Impossible de se connecter à la base de données');
      process.exit(1);
    }
    
    app.listen(PORT, '0.0.0.0', () => {
      console.log('🚀 Serveur SMS Gateway démarré');
      console.log(`📡 API disponible sur: http://168.231.85.4:${PORT}`);
      console.log(`🌐 API accessible depuis le réseau: http://168.231.85.4:${PORT}`);
      console.log(`🏥 Health check: http://168.231.85.4:${PORT}/api/health`);
      console.log(`📱 Phones API: http://168.231.85.4:${PORT}/api/phones`);
      console.log(`📤 Messages API: http://168.231.85.4:${PORT}/api/messages`);
      console.log(`📟 SIMs API: http://168.231.85.4:${PORT}/api/sims`);
      console.log('✅ Prêt à recevoir des requêtes');
      
      // Démarrer le service Auto-Switch
      autoSwitchService.start();
    });
    
  } catch (error) {
    console.error('❌ Erreur:', error);
    process.exit(1);
  }
}

start(); 