<?php
/**
 * Page de Gestion des Bases de Données - Admin uniquement
 * Fichier: pages/database_manager.php
 * 
 * Interface pour la gestion de la base de données du magasin:
 * - Création de backups
 * - Destruction de la database (sauf users)
 * - Restauration depuis backup
 * - Suppression de backups
 */

// Vérification de sécurité supplémentaire
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$shop_name = $_SESSION['shop_name'] ?? 'Magasin';
$shop_id = $_SESSION['shop_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo isset($_SESSION['night_mode']) && $_SESSION['night_mode'] ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Base de Données - <?php echo htmlspecialchars($shop_name); ?></title>
    <link rel="stylesheet" href="/assets/css/database_manager.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="db-manager-container">
        <!-- Header -->
        <div class="db-header">
            <div class="header-content">
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retour</span>
                </a>
                <h1 class="page-title">
                    <i class="fas fa-database"></i>
                    Gestion Base de Données
                </h1>
                <div class="shop-badge">
                    <i class="fas fa-store"></i>
                    <span><?php echo htmlspecialchars($shop_name); ?></span>
                </div>
            </div>
        </div>

        <!-- Alert Zone -->
        <div id="alertZone" class="alert-zone"></div>

        <!-- Main Actions -->
        <div class="actions-grid">
            
            <!-- Action 1: Détruire Database -->
            <div class="action-card danger-card" id="destroyCard">
                <div class="card-icon">
                    <i class="fas fa-bomb"></i>
                </div>
                <div class="card-content">
                    <h2 class="card-title">Détruire la Database</h2>
                    <p class="card-description">
                        Supprime <strong>toutes les tables</strong> du magasin.<br>
                        <span class="text-warning">⚠️ La table <code>users</code> sera préservée</span><br>
                        <small>Un backup automatique sera créé avant destruction</small>
                    </p>
                    <button class="btn btn-danger" onclick="confirmDestroy()">
                        <i class="fas fa-bomb"></i>
                        Détruire la Database
                    </button>
                </div>
            </div>

            <!-- Action 2: Restaurer Database -->
            <div class="action-card success-card" id="restoreCard">
                <div class="card-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <div class="card-content">
                    <h2 class="card-title">Restaurer la Database</h2>
                    <p class="card-description">
                        Restaure la database depuis un backup.<br>
                        <span class="text-info">ℹ️ Vos utilisateurs actuels seront préservés</span>
                    </p>
                    <button class="btn btn-success" onclick="showRestoreModal()">
                        <i class="fas fa-undo-alt"></i>
                        Restaurer la Database
                    </button>
                </div>
            </div>

            <!-- Action 3: Supprimer Sauvegarde -->
            <div class="action-card warning-card" id="deleteCard">
                <div class="card-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div class="card-content">
                    <h2 class="card-title">Supprimer une Sauvegarde</h2>
                    <p class="card-description">
                        Supprime un fichier de backup pour libérer de l'espace.<br>
                        <small>Les backups ne sont pas automatiquement supprimés</small>
                    </p>
                    <button class="btn btn-warning" onclick="showDeleteBackupModal()">
                        <i class="fas fa-trash-alt"></i>
                        Gérer les Sauvegardes
                    </button>
                </div>
            </div>

        </div>

        <!-- Section Liste des Backups -->
        <div class="backups-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-archive"></i>
                    Sauvegardes Disponibles
                </h2>
                <button class="btn btn-primary btn-sm" onclick="loadBackups()">
                    <i class="fas fa-sync-alt"></i>
                    Actualiser
                </button>
            </div>
            <div id="backupsList" class="backups-list">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Chargement...
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Restauration -->
    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-undo-alt"></i>
                    Restaurer la Database
                </h3>
                <button class="modal-close" onclick="closeModal('restoreModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-description">
                    Sélectionnez le backup à restaurer.<br>
                    <strong class="text-warning">⚠️ Cette opération remplacera toutes les données actuelles</strong><br>
                    <small class="text-info">La table users actuelle sera préservée</small>
                </p>
                <div id="restoreBackupsList" class="restore-list">
                    <!-- Liste dynamique -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Suppression Backup -->
    <div id="deleteBackupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-trash-alt"></i>
                    Supprimer une Sauvegarde
                </h3>
                <button class="modal-close" onclick="closeModal('deleteBackupModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-description">
                    Sélectionnez le backup à supprimer.
                </p>
                <div id="deleteBackupsList" class="delete-list">
                    <!-- Liste dynamique -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Destruction -->
    <div id="confirmDestroyModal" class="modal">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    CONFIRMATION REQUISE
                </h3>
            </div>
            <div class="modal-body">
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>ATTENTION!</strong></p>
                    <p>Vous êtes sur le point de <strong>DÉTRUIRE</strong> toutes les données du magasin:</p>
                    <p class="shop-name-confirm"><?php echo htmlspecialchars($shop_name); ?></p>
                </div>
                <ul class="warning-list">
                    <li><i class="fas fa-check"></i> Un backup automatique sera créé</li>
                    <li><i class="fas fa-check"></i> La table <code>users</code> sera préservée</li>
                    <li><i class="fas fa-bomb"></i> <strong>Toutes les autres données seront supprimées</strong></li>
                </ul>
                <p class="countdown-text">
                    Veuillez patienter <span id="countdown">5</span> secondes...
                </p>
                <div class="modal-actions">
                    <button id="confirmDestroyBtn" class="btn btn-danger" disabled onclick="executeDestroy()">
                        <i class="fas fa-bomb"></i>
                        CONFIRMER LA DESTRUCTION
                    </button>
                    <button class="btn btn-secondary" onclick="closeModal('confirmDestroyModal')">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let backupsData = [];
        
        // Charger les backups au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            loadBackups();
        });

        // Afficher une alerte
        function showAlert(message, type = 'info') {
            const alertZone = document.getElementById('alertZone');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            alertZone.appendChild(alert);
            
            // Auto-remove après 5 secondes
            setTimeout(() => {
                alert.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Charger la liste des backups
        function loadBackups() {
            const backupsList = document.getElementById('backupsList');
            backupsList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
            
            fetch('/api/database_operations.php?action=list_backups')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        backupsData = data.backups;
                        displayBackups(data.backups);
                    } else {
                        backupsList.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    backupsList.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Erreur de chargement</div>`;
                    console.error(error);
                });
        }

        // Afficher les backups
        function displayBackups(backups) {
            const backupsList = document.getElementById('backupsList');
            
            if (backups.length === 0) {
                backupsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-archive"></i>
                        <p>Aucune sauvegarde disponible</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="backup-items">';
            backups.forEach(backup => {
                html += `
                    <div class="backup-item">
                        <div class="backup-icon">
                            <i class="fas fa-file-archive"></i>
                        </div>
                        <div class="backup-info">
                            <div class="backup-name">${backup.filename}</div>
                            <div class="backup-meta">
                                <span><i class="fas fa-calendar"></i> ${backup.date_formatted}</span>
                                <span><i class="fas fa-hdd"></i> ${backup.size_mb} MB</span>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <button class="btn btn-sm btn-success" onclick="confirmRestore('${backup.filename}')" title="Restaurer">
                                <i class="fas fa-undo-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDeleteBackup('${backup.filename}')" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            backupsList.innerHTML = html;
        }

        // Confirmation destruction
        function confirmDestroy() {
            const modal = document.getElementById('confirmDestroyModal');
            const confirmBtn = document.getElementById('confirmDestroyBtn');
            const countdownSpan = document.getElementById('countdown');
            
            modal.style.display = 'flex';
            confirmBtn.disabled = true;
            
            let seconds = 5;
            countdownSpan.textContent = seconds;
            
            const interval = setInterval(() => {
                seconds--;
                countdownSpan.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    confirmBtn.disabled = false;
                    document.querySelector('.countdown-text').innerHTML = '<strong class="text-success">✓ Vous pouvez maintenant confirmer</strong>';
                }
            }, 1000);
        }

        // Exécuter la destruction
        function executeDestroy() {
            const confirmBtn = document.getElementById('confirmDestroyBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Destruction en cours...';
            
            const formData = new FormData();
            formData.append('action', 'destroy');
            formData.append('confirm', 'DESTROY');
            
            fetch('/api/database_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeModal('confirmDestroyModal');
                
                if (data.success) {
                    showAlert(`✓ Database détruite avec succès. ${data.deleted_tables} tables supprimées. Backup créé: ${data.backup_file}`, 'success');
                    loadBackups(); // Recharger la liste
                } else {
                    showAlert(`✗ Erreur: ${data.error}`, 'error');
                }
                
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-bomb"></i> CONFIRMER LA DESTRUCTION';
            })
            .catch(error => {
                closeModal('confirmDestroyModal');
                showAlert('✗ Erreur de communication avec le serveur', 'error');
                console.error(error);
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-bomb"></i> CONFIRMER LA DESTRUCTION';
            });
        }

        // Afficher modal restauration
        function showRestoreModal() {
            if (backupsData.length === 0) {
                showAlert('Aucune sauvegarde disponible pour la restauration', 'warning');
                return;
            }
            
            const modal = document.getElementById('restoreModal');
            const list = document.getElementById('restoreBackupsList');
            
            let html = '<div class="backup-items">';
            backupsData.forEach(backup => {
                html += `
                    <div class="backup-item selectable" onclick="confirmRestore('${backup.filename}')">
                        <div class="backup-icon">
                            <i class="fas fa-file-archive"></i>
                        </div>
                        <div class="backup-info">
                            <div class="backup-name">${backup.filename}</div>
                            <div class="backup-meta">
                                <span><i class="fas fa-calendar"></i> ${backup.date_formatted}</span>
                                <span><i class="fas fa-hdd"></i> ${backup.size_mb} MB</span>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            list.innerHTML = html;
            
            modal.style.display = 'flex';
        }

        // Confirmer restauration
        function confirmRestore(filename) {
            closeModal('restoreModal');
            
            if (!confirm(`Êtes-vous sûr de vouloir restaurer depuis:\n${filename}\n\n⚠️ Toutes les données actuelles seront remplacées!\n✓ La table users actuelle sera préservée.`)) {
                return;
            }
            
            showAlert('⏳ Restauration en cours...', 'info');
            
            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('backup_file', filename);
            
            fetch('/api/database_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(`✓ Database restaurée depuis ${data.backup_file}`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert(`✗ Erreur: ${data.error}`, 'error');
                }
            })
            .catch(error => {
                showAlert('✗ Erreur de communication avec le serveur', 'error');
                console.error(error);
            });
        }

        // Afficher modal suppression backup
        function showDeleteBackupModal() {
            if (backupsData.length === 0) {
                showAlert('Aucune sauvegarde disponible', 'warning');
                return;
            }
            
            const modal = document.getElementById('deleteBackupModal');
            const list = document.getElementById('deleteBackupsList');
            
            let html = '<div class="backup-items">';
            backupsData.forEach(backup => {
                html += `
                    <div class="backup-item">
                        <div class="backup-icon">
                            <i class="fas fa-file-archive"></i>
                        </div>
                        <div class="backup-info">
                            <div class="backup-name">${backup.filename}</div>
                            <div class="backup-meta">
                                <span><i class="fas fa-calendar"></i> ${backup.date_formatted}</span>
                                <span><i class="fas fa-hdd"></i> ${backup.size_mb} MB</span>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <button class="btn btn-sm btn-danger" onclick="confirmDeleteBackup('${backup.filename}')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            list.innerHTML = html;
            
            modal.style.display = 'flex';
        }

        // Confirmer suppression backup
        function confirmDeleteBackup(filename) {
            closeModal('deleteBackupModal');
            
            if (!confirm(`Supprimer le backup:\n${filename}\n\nCette action est irréversible!`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_backup');
            formData.append('backup_file', filename);
            
            fetch('/api/database_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(`✓ Backup ${filename} supprimé`, 'success');
                    loadBackups();
                } else {
                    showAlert(`✗ Erreur: ${data.error}`, 'error');
                }
            })
            .catch(error => {
                showAlert('✗ Erreur de communication avec le serveur', 'error');
                console.error(error);
            });
        }

        // Fermer modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fermer modal en cliquant en dehors
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
