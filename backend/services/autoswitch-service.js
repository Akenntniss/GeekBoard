const { pool } = require('../config/database');
const { recalculateAllRecipientCounters } = require('../utils/recipient-counter');

class AutoSwitchService {
  constructor() {
    this.isRunning = false;
    this.intervalId = null;
    this.currentFrequency = null;
  }

  async start() {
    if (this.isRunning) {
      console.log('🔄 Service Auto-Switch déjà en cours d\'exécution');
      return;
    }

    console.log('🚀 Démarrage du service Auto-Switch');
    this.isRunning = true;
    
    // Corriger les compteurs de destinataires au démarrage
    await this.fixRecipientCounters();
    
    // Vérification initiale
    await this.checkAndSwitch();
    
    // Démarrer la vérification périodique avec la fréquence configurée
    await this.startPeriodicCheck();
  }

  async fixRecipientCounters() {
    try {
      console.log('🔧 Correction des compteurs de destinataires...');
      await recalculateAllRecipientCounters();
    } catch (error) {
      console.error('❌ Erreur lors de la correction des compteurs:', error);
    }
  }

  async startPeriodicCheck() {
    try {
      // Récupérer la fréquence configurée
      const [config] = await pool.execute(`
        SELECT quota_check_frequency FROM auto_switch_config ORDER BY id DESC LIMIT 1
      `);
      
      const frequency = (config.length > 0 && config[0].quota_check_frequency) 
        ? config[0].quota_check_frequency * 1000  // Convertir en millisecondes
        : 180000; // Par défaut 3 minutes si pas de configuration
      
      // Si la fréquence a changé, redémarrer l'intervalle
      if (this.currentFrequency !== frequency) {
        if (this.intervalId) {
          clearInterval(this.intervalId);
        }
        
        this.currentFrequency = frequency;
        console.log(`⏰ Configuration de la vérification automatique toutes les ${frequency / 1000} secondes`);
        
        this.intervalId = setInterval(async () => {
          await this.checkAndSwitch();
          // Vérifier si la fréquence a changé à chaque cycle
          await this.startPeriodicCheck();
        }, frequency);
      }
      
    } catch (error) {
      console.error('❌ Erreur lors de la configuration de la fréquence:', error);
      // Utiliser la fréquence par défaut en cas d'erreur
      if (!this.intervalId) {
        this.intervalId = setInterval(async () => {
          await this.checkAndSwitch();
        }, 180000); // 3 minutes par défaut
      }
    }
  }

  stop() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
    this.isRunning = false;
    console.log('⏹️ Service Auto-Switch arrêté');
  }

  async checkAndSwitch() {
    try {
      // Récupérer la configuration
      const [config] = await pool.execute(`
        SELECT * FROM auto_switch_config ORDER BY id DESC LIMIT 1
      `);
      
      if (config.length === 0 || !config[0].auto_switch_enabled) {
        return; // Auto-switch désactivé
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
        return; // Aucune SIM par défaut
      }
      
      // Vérifier si le basculement est nécessaire
      if (currentDefaultSim.max_usage_percentage < autoSwitchConfig.quota_alert_threshold) {
        return; // Quotas sous le seuil
      }
      
      console.log(`🚨 Quota critique détecté: ${currentDefaultSim.carrier_name} à ${currentDefaultSim.max_usage_percentage}%`);
      
      // Trouver la meilleure SIM alternative
      const availableSims = sims.filter(sim => 
        !sim.is_default && 
        sim.max_usage_percentage < autoSwitchConfig.quota_alert_threshold
      );
      
      if (availableSims.length === 0) {
        console.log('⚠️ Aucune SIM alternative disponible pour le basculement');
        
        // Envoyer notification d'alerte si activé
        if (autoSwitchConfig.sms_notifications_enabled && autoSwitchConfig.notification_phone_number) {
          await this.sendNotification(
            autoSwitchConfig.notification_phone_number,
            `🚨 ALERTE CRITIQUE: SIM ${currentDefaultSim.carrier_name} à ${currentDefaultSim.max_usage_percentage}% - Aucune SIM alternative disponible!`
          );
        }
        return;
      }
      
      // Effectuer le basculement
      const newDefaultSim = availableSims[0];
      const switchResult = await this.performSwitch(currentDefaultSim, newDefaultSim, autoSwitchConfig);
      
      if (switchResult.success) {
        console.log(`✅ Basculement automatique effectué: ${currentDefaultSim.carrier_name} → ${newDefaultSim.carrier_name}`);
      } else {
        console.error(`❌ Échec du basculement: ${switchResult.error}`);
      }
      
    } catch (error) {
      console.error('❌ Erreur dans le service Auto-Switch:', error);
    }
  }

  async performSwitch(currentDefaultSim, newDefaultSim, config) {
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
      
      // Créer la table de logs si elle n'existe pas
      await connection.execute(`
        CREATE TABLE IF NOT EXISTS auto_switch_logs (
          id INT AUTO_INCREMENT PRIMARY KEY,
          from_sim_id INT NOT NULL,
          to_sim_id INT NOT NULL,
          reason VARCHAR(255) NOT NULL,
          from_quota_percentage DECIMAL(5,2) NOT NULL,
          to_quota_percentage DECIMAL(5,2) NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
      `);
      
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
      if (config.switch_notifications_enabled && config.notification_phone_number) {
        const notificationMessage = `🔄 BASCULEMENT AUTO: SIM par défaut changée de ${currentDefaultSim.carrier_name} (${currentDefaultSim.phone_number}) vers ${newDefaultSim.carrier_name} (${newDefaultSim.phone_number}) - Quota atteint: ${currentDefaultSim.max_usage_percentage}%. Nouveaux quotas: SMS ${newDefaultSim.messages_sent_month}/${newDefaultSim.monthly_limit}, Destinataires ${newDefaultSim.recipients_monthly}/${newDefaultSim.recipients_monthly_limit}.`;
        
        await this.sendNotification(config.notification_phone_number, notificationMessage);
      }
      
      return { success: true };
      
    } catch (error) {
      await connection.rollback();
      return { success: false, error: error.message };
    } finally {
      connection.release();
    }
  }

  async sendNotification(phoneNumber, message) {
    try {
      const response = await fetch('http://168.231.85.4:3001/api/messages/send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          recipient: phoneNumber,
          message: message
        })
      });
      
      if (response.ok) {
        console.log(`📱 Notification envoyée à ${phoneNumber}`);
      } else {
        console.error('❌ Erreur envoi notification:', await response.text());
      }
    } catch (error) {
      console.error('❌ Erreur envoi notification:', error);
    }
  }
}

module.exports = new AutoSwitchService(); 