const { pool } = require('../config/database');

class SmartRetrySystem {
    constructor() {
        this.retryStrategies = {
            // Basculement immédiat vers autre SIM
            'NO_SERVICE': { switchSim: true, delay: 0, maxRetries: 2 },
            'RADIO_OFF': { switchSim: true, delay: 0, maxRetries: 2 },
            'SIM_NOT_READY': { switchSim: true, delay: 0, maxRetries: 2 },
            'NETWORK_TIMEOUT': { switchSim: true, delay: 0, maxRetries: 2 },
            'TIMEOUT': { switchSim: true, delay: 0, maxRetries: 3 },
            'OPERATOR_FAILURE': { switchSim: true, delay: 0, maxRetries: 2 },
            
            // Retry avec délai sur la même SIM
            'GENERIC_FAILURE': { switchSim: false, delay: 30000, maxRetries: 3 }, // 30s
            'SEND_TIMEOUT': { switchSim: false, delay: 60000, maxRetries: 2 }, // 1min
            
            // Basculement après plusieurs échecs
            'DELIVERY_FAILURE': { switchSim: true, delay: 0, maxRetries: 5 },
            'RATE_LIMIT': { switchSim: true, delay: 300000, maxRetries: 2 } // 5min
        };
        
        this.timeoutMessages = new Map(); // Gestion des timeouts
        this.processingQueue = new Set(); // Messages en cours de traitement
    }

    /**
     * Gère l'échec d'un message et décide de la stratégie de retry
     */
    async handleFailedMessage(messageId, errorCode, phoneId = null, simId = null) {
        console.log(`🔄 Gestion échec message ${messageId}: ${errorCode}`);
        
        try {
            // Récupérer les informations du message
            const message = await this.getMessageById(messageId);
            if (!message) {
                console.error(`❌ Message ${messageId} non trouvé`);
                return false;
            }

            // Vérifier si on a atteint le nombre max de tentatives
            if (message.retry_count >= this.getMaxRetries(errorCode)) {
                console.log(`⛔ Message ${messageId} a atteint le max de tentatives (${message.retry_count})`);
                await this.markMessageAsFinalFailure(messageId, errorCode);
                return false;
            }

            // Obtenir la stratégie de retry
            const strategy = this.getRetryStrategy(errorCode);
            console.log(`📋 Stratégie pour ${errorCode}:`, strategy);

            // Incrémenter le compteur de retry
            await this.incrementRetryCount(messageId);

            if (strategy.switchSim) {
                return await this.retryWithDifferentSim(messageId, message, strategy);
            } else {
                return await this.retryWithDelay(messageId, strategy);
            }

        } catch (error) {
            console.error(`❌ Erreur lors de la gestion d'échec:`, error);
            return false;
        }
    }

    /**
     * Retry avec basculement vers une autre SIM
     */
    async retryWithDifferentSim(messageId, message, strategy) {
        console.log(`🔄 Basculement SIM pour message ${messageId}`);
        
        try {
            // Trouver une SIM alternative
            const newSimId = await this.selectAlternativeSim(message.sim_id, message.phone_id);
            
            if (newSimId) {
                console.log(`✅ SIM alternative trouvée: ${newSimId}`);
                
                // 1. DÉSACTIVER la SIM défaillante
                await pool.execute(
                    'UPDATE sims SET is_active = 0, is_default = 0 WHERE id = ?',
                    [message.sim_id]
                );
                console.log(`❌ SIM ${message.sim_id} désactivée suite à l'échec`);
                
                // 2. ACTIVER et définir comme DÉFAUT la nouvelle SIM
                await pool.execute(
                    'UPDATE sims SET is_active = 1, is_default = 1 WHERE id = ?',
                    [newSimId]
                );
                console.log(`✅ SIM ${newSimId} activée et définie par défaut`);
                
                // 3. S'assurer qu'aucune autre SIM n'est par défaut sur ce téléphone
                await pool.execute(
                    'UPDATE sims SET is_default = 0 WHERE phone_id = ? AND id != ?',
                    [message.phone_id, newSimId]
                );
                
                // 4. Créer un NOUVEAU message avec la nouvelle SIM
                const newMessageId = await this.createNewMessageWithSim(message, newSimId);
                console.log(`📝 Nouveau message ${newMessageId} créé avec SIM ${newSimId}`);
                
                return true;
            } else {
                // Pas de SIM alternative, essayer sur un autre téléphone
                console.log(`🔄 Tentative sur autre téléphone pour message ${messageId}`);
                return await this.retryWithDifferentPhone(messageId, message, strategy);
            }
            
        } catch (error) {
            console.error(`❌ Erreur lors du basculement SIM:`, error);
            return false;
        }
    }

    /**
     * Retry avec délai sur la même SIM
     */
    async retryWithDelay(messageId, strategy) {
        console.log(`⏰ Retry avec délai ${strategy.delay}ms pour message ${messageId}`);
        
        setTimeout(() => {
            this.retryMessage(messageId);
        }, strategy.delay);
        
        return true;
    }

    /**
     * Retry sur un téléphone différent
     */
    async retryWithDifferentPhone(messageId, message, strategy) {
        console.log(`📱 Recherche téléphone alternatif pour message ${messageId}`);
        
        try {
            const alternativePhone = await this.selectAlternativePhone(message.phone_id);
            
            if (alternativePhone) {
                console.log(`✅ Téléphone alternatif trouvé: ${alternativePhone.phone_id}`);
                
                // Créer un nouveau message sur le téléphone alternatif
                const newMessageId = await this.createNewMessageWithPhone(message, alternativePhone.phone_id, alternativePhone.best_sim_id);
                console.log(`📝 Nouveau message ${newMessageId} créé sur téléphone ${alternativePhone.phone_id}`);
                
                return true;
            } else {
                console.log(`❌ Aucun téléphone alternatif disponible`);
                await this.markMessageAsFinalFailure(messageId, 'NO_ALTERNATIVE_DEVICE');
                return false;
            }
            
        } catch (error) {
            console.error(`❌ Erreur lors du changement de téléphone:`, error);
            return false;
        }
    }

    /**
     * Sélectionne la meilleure SIM alternative
     */
    async selectAlternativeSim(currentSimId, phoneId) {
        try {
            const [availableSims] = await pool.query(`
                SELECT s.id, s.carrier_name, s.messages_sent_month, s.monthly_limit,
                       s.recipients_monthly, s.recipients_monthly_limit,
                       COALESCE(ss.success_rate, 95) as success_rate,
                       COALESCE(ss.avg_response_time, 5000) as avg_response_time
                FROM sims s
                LEFT JOIN sim_stats ss ON s.id = ss.sim_id
                WHERE s.phone_id = ? 
                  AND s.id != ? 
                  AND s.is_active = true
                  AND s.messages_sent_month < s.monthly_limit * 0.9
                  AND s.recipients_monthly < s.recipients_monthly_limit * 0.9
                ORDER BY 
                    ss.success_rate DESC,
                    (s.messages_sent_month / s.monthly_limit) ASC,
                    ss.avg_response_time ASC
                LIMIT 1
            `, [phoneId, currentSimId]);

            return availableSims.length > 0 ? availableSims[0].id : null;
            
        } catch (error) {
            console.error(`❌ Erreur lors de la sélection SIM alternative:`, error);
            return null;
        }
    }

    /**
     * Sélectionne un téléphone alternatif
     */
    async selectAlternativePhone(currentPhoneId) {
        try {
            console.log(`🔍 Recherche téléphone alternatif (actuel: ${currentPhoneId})`);
            
            const [alternativePhones] = await pool.query(`
                SELECT p.id as phone_id, 
                       MIN(s.id) as best_sim_id,
                       COUNT(s.id) as sim_count,
                       AVG(COALESCE(ss.success_rate, 95)) as avg_success_rate
                FROM phones p
                INNER JOIN sims s ON p.id = s.phone_id
                LEFT JOIN sim_stats ss ON s.id = ss.sim_id
                WHERE p.id != ?
                  AND p.status = 'active'
                  AND p.last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                  AND s.is_active = true
                  AND s.messages_sent_month < s.monthly_limit * 0.9
                GROUP BY p.id
                HAVING sim_count > 0
                ORDER BY avg_success_rate DESC, sim_count DESC
                LIMIT 1
            `, [currentPhoneId]);

            console.log(`📊 Téléphones alternatifs trouvés: ${alternativePhones.length}`);
            
            if (alternativePhones.length === 0) {
                // Diagnostiquer pourquoi aucun téléphone n'est trouvé
                const [allPhones] = await pool.query(`
                    SELECT p.id, p.status, p.last_heartbeat,
                           COUNT(s.id) as total_sims,
                           COUNT(CASE WHEN s.is_active = true THEN 1 END) as active_sims
                    FROM phones p
                    LEFT JOIN sims s ON p.id = s.phone_id
                    WHERE p.id != ?
                    GROUP BY p.id, p.status, p.last_heartbeat
                `, [currentPhoneId]);
                
                console.log(`🔍 Tous les autres téléphones (${allPhones.length}):`, allPhones);
            }

            return alternativePhones.length > 0 ? alternativePhones[0] : null;
            
        } catch (error) {
            console.error(`❌ Erreur lors de la sélection téléphone alternatif:`, error);
            return null;
        }
    }

    /**
     * Crée un nouveau message avec une SIM différente
     */
    async createNewMessageWithSim(originalMessage, newSimId) {
        try {
            const [result] = await pool.execute(`
                INSERT INTO sms_history (
                    phone_id, recipient, message, sim_id, status, 
                    retry_count, created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            `, [
                originalMessage.phone_id,
                originalMessage.recipient,
                originalMessage.message,
                newSimId,
                (originalMessage.retry_count || 0) + 1
            ]);
            
            return result.insertId;
        } catch (error) {
            console.error(`❌ Erreur lors de la création du nouveau message:`, error);
            throw error;
        }
    }

    /**
     * Crée un nouveau message avec un téléphone différent
     */
    async createNewMessageWithPhone(originalMessage, newPhoneId, newSimId) {
        try {
            const [result] = await pool.execute(`
                INSERT INTO sms_history (
                    phone_id, recipient, message, sim_id, status, 
                    retry_count, created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            `, [
                newPhoneId,
                originalMessage.recipient,
                originalMessage.message,
                newSimId,
                (originalMessage.retry_count || 0) + 1
            ]);
            
            return result.insertId;
        } catch (error) {
            console.error(`❌ Erreur lors de la création du nouveau message sur autre téléphone:`, error);
            throw error;
        }
    }

    /**
     * Marque un message comme échec final
     */
    async markMessageAsFinalFailure(messageId, reason) {
        try {
            await pool.query(`
                UPDATE sms_history 
                SET status = 'failed', 
                    failure_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            `, [reason, messageId]);
            
            console.log(`⛔ Message ${messageId} marqué comme échec final: ${reason}`);
            
        } catch (error) {
            console.error(`❌ Erreur lors du marquage échec final:`, error);
        }
    }

    /**
     * Obtient la stratégie de retry pour un code d'erreur
     */
    getRetryStrategy(errorCode) {
        return this.retryStrategies[errorCode] || {
            switchSim: false,
            delay: 60000, // 1 minute par défaut
            maxRetries: 2
        };
    }

    /**
     * Obtient le nombre max de retries pour un code d'erreur
     */
    getMaxRetries(errorCode) {
        const strategy = this.getRetryStrategy(errorCode);
        return strategy.maxRetries;
    }

    /**
     * Récupère un message par son ID
     */
    async getMessageById(messageId) {
        try {
            const [messages] = await pool.query(`
                SELECT * FROM sms_history WHERE id = ?
            `, [messageId]);
            
            return messages.length > 0 ? messages[0] : null;
            
        } catch (error) {
            console.error(`❌ Erreur lors de la récupération du message:`, error);
            return null;
        }
    }

    /**
     * Incrémente le compteur de retry
     */
    async incrementRetryCount(messageId) {
        try {
            await pool.query(`
                UPDATE sms_history 
                SET retry_count = retry_count + 1,
                    updated_at = NOW()
                WHERE id = ?
            `, [messageId]);
            
        } catch (error) {
            console.error(`❌ Erreur lors de l'incrémentation retry:`, error);
        }
    }

    /**
     * Met à jour la SIM d'un message
     */
    async updateMessageSim(messageId, newSimId) {
        try {
            await pool.query(`
                UPDATE sms_history 
                SET sim_id = ?,
                    status = 'pending',
                    updated_at = NOW()
                WHERE id = ?
            `, [newSimId, messageId]);
            
        } catch (error) {
            console.error(`❌ Erreur lors de la mise à jour SIM:`, error);
        }
    }

    /**
     * Met à jour le téléphone et la SIM d'un message
     */
    async updateMessagePhone(messageId, newPhoneId, newSimId) {
        try {
            await pool.query(`
                UPDATE sms_history 
                SET phone_id = ?,
                    sim_id = ?,
                    status = 'pending',
                    updated_at = NOW()
                WHERE id = ?
            `, [newPhoneId, newSimId, messageId]);
            
        } catch (error) {
            console.error(`❌ Erreur lors de la mise à jour téléphone:`, error);
        }
    }

    /**
     * Remet un message en pending pour retry
     */
    async retryMessage(messageId) {
        try {
            await pool.query(`
                UPDATE sms_history 
                SET status = 'pending',
                    updated_at = NOW()
                WHERE id = ?
            `, [messageId]);
            
            console.log(`🔄 Message ${messageId} remis en pending pour retry`);
            
        } catch (error) {
            console.error(`❌ Erreur lors du retry message:`, error);
        }
    }

    /**
     * Démarre le système de monitoring des timeouts
     */
    startTimeoutMonitoring() {
        console.log(`🕐 Démarrage du monitoring des timeouts`);
        
        // Vérifier les messages en timeout toutes les 30 secondes
        setInterval(() => {
            this.checkTimeoutMessages();
        }, 30000);
    }

    /**
     * Vérifie les messages en timeout
     */
    async checkTimeoutMessages() {
        try {
            const [timeoutMessages] = await pool.query(`
                SELECT id, phone_id, sim_id, recipient, retry_count
                FROM sms_history 
                WHERE status = 'pending' 
                AND created_at < DATE_SUB(NOW(), INTERVAL 3 MINUTE)
                AND retry_count < 3
            `);

            for (const message of timeoutMessages) {
                console.log(`⏰ Timeout détecté pour message ${message.id}`);
                await this.handleFailedMessage(message.id, 'TIMEOUT', message.phone_id, message.sim_id);
            }
            
        } catch (error) {
            console.error(`❌ Erreur lors de la vérification des timeouts:`, error);
        }
    }
}

module.exports = SmartRetrySystem;
























