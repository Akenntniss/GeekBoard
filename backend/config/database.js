const mysql = require('mysql2/promise');

const dbConfig = {
  host: '168.231.85.4',
  user: 'smsadmin',
  password: 'Maman01#',
  database: 'sms_gateway',
  ssl: false,
  charset: 'utf8mb4',
  timezone: '+00:00'
};

// Pool de connexions pour de meilleures performances
const pool = mysql.createPool({
  ...dbConfig,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Test de connexion
async function testConnection() {
  try {
    const connection = await pool.getConnection();
    console.log('✅ Connexion à la base de données réussie');
    connection.release();
    return true;
  } catch (error) {
    console.error('❌ Erreur de connexion à la base de données:', error.message);
    return false;
  }
}

module.exports = {
  pool,
  testConnection
}; 
 
 