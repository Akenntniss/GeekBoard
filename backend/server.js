const express = require('express');
const cors = require('cors');
const path = require('path');
const { testConnection } = require('./config/database');
const autoSwitchService = require('./services/autoswitch-service');
const { retrySystem } = require('./routes/retry-management');
const DatabaseCleanupService = require('./services/database-cleanup');

const app = express();
const PORT = process.env.PORT || 3001;

// Services
const cleanupService = new DatabaseCleanupService();

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Servir les fichiers statiques (frontend)
app.use(express.static(path.join(__dirname, '../frontend')));

// Route spéciale pour télécharger l'APK
app.get('/download/apk', (req, res) => {
  const apkPath = path.join(__dirname, '../sms-gateway.apk');
  res.download(apkPath, 'SMS-Gateway.apk', (err) => {
    if (err) {
      console.error('Erreur lors du téléchargement de l\'APK:', err);
      res.status(404).json({
        success: false,
        message: 'APK non trouvé'
      });
    }
  });
});

// Logging middleware
app.use((req, res, next) => {
  console.log(`${new Date().toISOString()} - ${req.method} ${req.path}`);
  next();
});

// Routes
app.use('/api/health', require('./routes/health'));
app.use('/api/phones', require('./routes/phones'));
app.use('/api/sims', require('./routes/sims'));
app.use('/api/messages', require('./routes/messages'));
app.use('/api/system', require('./routes/system'));
app.use('/api/autoswitch', require('./routes/autoswitch'));
app.use('/api/retry', require('./routes/retry-management').router);

// Route de base
app.get('/', (req, res) => {
  res.json({
    message: 'SMS Gateway API',
    version: '1.0.0',
    status: 'running',
    timestamp: new Date().toISOString()
  });
});

// Gestion des erreurs 404
app.use('*', (req, res) => {
  res.status(404).json({
    success: false,
    message: 'Route non trouvée',
    path: req.originalUrl
  });
});

// Gestion des erreurs globales
app.use((error, req, res, next) => {
  console.error('Erreur serveur:', error);
  res.status(500).json({
    success: false,
    message: 'Erreur interne du serveur',
    error: process.env.NODE_ENV === 'development' ? error.message : undefined
  });
});

// Démarrage du serveur
async function startServer() {
  try {
    // Tester la connexion à la base de données
    console.log('🔍 Test de la connexion à la base de données...');
    const dbConnected = await testConnection();
    
    if (!dbConnected) {
      console.error('❌ Impossible de se connecter à la base de données');
      process.exit(1);
    }
    
    // Démarrer le serveur
    app.listen(PORT, () => {
      console.log('🚀 Serveur SMS Gateway démarré');
      console.log(`📡 API disponible sur: http://168.231.85.4:${PORT}`);
      console.log(`🏥 Health check: http://168.231.85.4:${PORT}/api/health`);
      console.log(`📱 Phones API: http://168.231.85.4:${PORT}/api/phones`);
      console.log(`📋 SIMs API: http://168.231.85.4:${PORT}/api/sims`);
      console.log(`💬 Messages API: http://168.231.85.4:${PORT}/api/messages`);
      console.log(`⚙️  System API: http://168.231.85.4:${PORT}/api/system`);
      console.log(`🔄 Auto-Switch API: http://168.231.85.4:${PORT}/api/autoswitch`);
      console.log(`🔁 Retry System API: http://168.231.85.4:${PORT}/api/retry`);
      console.log(`📲 APK Download: http://168.231.85.4:${PORT}/download/apk`);
      console.log('✅ Prêt à recevoir des requêtes');
      
      // Démarrer le service Auto-Switch
      autoSwitchService.start();
      
      // Démarrer le système de monitoring des timeouts
      retrySystem.startTimeoutMonitoring();
      
      // Démarrer le service de nettoyage de la base de données
      cleanupService.start();
    });
    
  } catch (error) {
    console.error('❌ Erreur lors du démarrage du serveur:', error);
    process.exit(1);
  }
}

// Gestion des signaux de fermeture
process.on('SIGINT', () => {
  console.log('\n🛑 Arrêt du serveur...');
  autoSwitchService.stop();
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('\n🛑 Arrêt du serveur...');
  autoSwitchService.stop();
  process.exit(0);
});

// Démarrer le serveur
startServer(); 
 
 