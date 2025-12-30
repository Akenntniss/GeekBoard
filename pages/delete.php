<?php
// pages/delete.php
// SECURITE: Seul l'admin doit voir cette page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification supplémentaire si on veut restreindre au superadmin ou proprio
// Pour l'instant on laisse l'accès à tout utilisateur authentifié comme demandé (Admin du magasin)
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZONE DANGER - Réinitialisation Système</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-red: #ff003c;
            --neon-blue: #00f3ff;
            --dark-bg: #0a0a0a;
            --panel-bg: #121212;
            --text-color: #e0e0e0;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-family: 'Rajdhani', sans-serif;
            margin: 0;
            padding: 20px;
            overflow-x: hidden;
        }

        .danger-zone-container {
            max-width: 800px;
            margin: 0 auto;
            padding-bottom: 80px;
        }

        .header-title {
            font-family: 'Orbitron', sans-serif;
            text-align: center;
            color: var(--neon-red);
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 40px;
            text-shadow: 0 0 10px rgba(255, 0, 60, 0.5);
            border-bottom: 2px solid var(--neon-red);
            padding-bottom: 20px;
        }

        .panel {
            background: var(--panel-bg);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #333;
        }

        .panel.danger-panel {
            border-color: var(--neon-red);
        }
        .panel.danger-panel::before {
            background: var(--neon-red);
            box-shadow: 0 0 10px var(--neon-red);
        }

        .panel.restore-panel {
            border-color: var(--neon-blue);
        }
        .panel.restore-panel::before {
            background: var(--neon-blue);
            box-shadow: 0 0 10px var(--neon-blue);
        }

        h2 {
            font-family: 'Orbitron', sans-serif;
            margin-top: 0;
            font-size: 1.5rem;
        }

        .btn {
            background: transparent;
            border: 2px solid;
            padding: 15px 30px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
            width: 100%;
            display: block;
            margin-top: 20px;
        }

        .btn-danger {
            color: var(--neon-red);
            border-color: var(--neon-red);
        }

        .btn-danger:hover {
            background: var(--neon-red);
            color: #fff;
            box-shadow: 0 0 20px var(--neon-red);
        }

        .btn-info {
            color: var(--neon-blue);
            border-color: var(--neon-blue);
        }

        .btn-info:hover {
            background: var(--neon-blue);
            color: #000;
            box-shadow: 0 0 20px var(--neon-blue);
        }

        .backup-list {
            list-style: none;
            padding: 0;
        }

        .backup-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 2px solid var(--neon-blue);
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 5px 15px;
            font-size: 0.8rem;
            width: auto;
            margin-top: 0;
        }

        .alert-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #1a1a1a;
            border: 2px solid var(--neon-red);
            padding: 30px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 0 50px rgba(255, 0, 60, 0.2);
        }
        
        .modal-title {
            color: var(--neon-red);
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .warning-text {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #fff;
        }
        
        input[type="password"] {
            background: #000;
            border: 1px solid #555;
            color: #fff;
            padding: 10px;
            width: 100%;
            margin-bottom: 20px;
            font-size: 1.1rem;
            text-align: center;
        }
        
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .loader {
            border: 5px solid #333;
            border-top: 5px solid var(--neon-blue);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        .status-text {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
            color: var(--neon-blue);
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Mobile optimizations */
        @media (max-width: 600px) {
            .header-title { font-size: 1.8rem; }
            .backup-item { flex-direction: column; text-align: center; gap: 10px; }
            .backup-actions { width: 100%; justify-content: space-between; }
            .btn-sm { flex: 1; }
        }

        .return-link {
            display: inline-block;
            margin-top: 30px;
            color: #777;
            text-decoration: none;
            font-size: 0.9rem;
            border-bottom: 1px dashed #777;
        }
    </style>
</head>
<body>

<div class="danger-zone-container">
    <div class="header-title">ZONE DANGER SYSTEME</div>

    <!-- Restauration Panel -->
    <div class="panel restore-panel">
        <h2 style="color: var(--neon-blue)">RESTORE / BACKUP</h2>
        <p>Gérez les points de restauration du système avant destruction.</p>
        
        <button onclick="createBackup()" class="btn btn-info">CRÉER UNE SAUVEGARDE MAINTENANT</button>
        
        <h3 style="margin-top: 30px; border-bottom: 1px solid #333; padding-bottom: 10px;">Sauvegardes Disponibles</h3>
        <ul id="backupList" class="backup-list">
            <!-- Loaded via JS -->
            <li class="backup-item">Chargement...</li>
        </ul>
    </div>

    <!-- Destruction Panel -->
    <div class="panel danger-panel">
        <h2 style="color: var(--neon-red)">DESTRUCTION TOTALE</h2>
        <p style="color: #ff6b8b">ATTENTION : Cette action supprimera <strong>Toutes les données</strong> du magasin (Clients, Réparations, Stocks...).<br>
        <span style="color: #fff">Seuls les comptes Administrateurs seront conservés pour permettre la connexion.</span></p>
        
        <button onclick="showWipeDatabaseModal()" class="btn btn-danger">DÉTRUIRE LA DATABASE</button>
    </div>

    <div style="text-align: center;">
        <a href="/index.php" class="return-link">RETOURNER AU DASHBOARD SÉCURISÉ</a>
    </div>
</div>

<!-- Password Modal -->
<div id="wipeModal" class="alert-modal">
    <div class="modal-content">
        <div class="modal-title">CONFIRMATION REQUISE</div>
        <div class="warning-text">
            ⚠️ VOUS ÊTES SUR LE POINT DE VIDER LA BASE DE DONNÉES.<br><br>
            Une sauvegarde automatique sera effectuée avant destruction.<br>
            Entrez le mot de passe "DELETE" pour confirmer :
        </div>
        <input type="text" id="confirmPassword" placeholder="Tapez DELETE ici">
        <div style="display: flex; gap: 10px;">
            <button onclick="closeModal()" class="btn btn-info btn-sm">ANNULER</button>
            <button onclick="executeWipe()" class="btn btn-danger btn-sm">CONFIRMER DESTRUCTION</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="loader"></div>
    <div class="status-text" id="loadingText">OPÉRATION EN COURS...</div>
</div>

<script>
    const API_URL = 'actions/database_manager.php';

    document.addEventListener('DOMContentLoaded', loadBackups);

    function showLoading(text) {
        document.getElementById('loadingText').innerText = text;
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    function closeModal() {
        document.getElementById('wipeModal').style.display = 'none';
        document.getElementById('confirmPassword').value = '';
    }

    function showWipeDatabaseModal() {
        document.getElementById('wipeModal').style.display = 'flex';
    }

    async function loadBackups() {
        const formData = new FormData();
        formData.append('action', 'list');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();
            
            const list = document.getElementById('backupList');
            list.innerHTML = '';

            if (data.backups && data.backups.length > 0) {
                data.backups.forEach(backup => {
                    const date = new Date(backup.date * 1000).toLocaleString();
                    const size = (backup.size / 1024).toFixed(2) + ' KB';
                    
                    list.innerHTML += `
                        <li class="backup-item">
                            <div>
                                <strong>${date}</strong><br>
                                <span style="font-size: 0.8rem; color: #888">${backup.name} (${size})</span>
                            </div>
                            <div class="backup-actions">
                                <button onclick="restoreBackup('${backup.name}')" class="btn btn-info btn-sm">RESTAURER</button>
                                <button onclick="deleteBackup('${backup.name}')" class="btn btn-danger btn-sm">X</button>
                            </div>
                        </li>
                    `;
                });
            } else {
                list.innerHTML = '<li class="backup-item">Aucune sauvegarde trouvée.</li>';
            }
        } catch (e) {
            console.error('Erreur loading backups:', e);
        }
    }

    async function createBackup() {
        showLoading('CRÉATION SAUVEGARDE...');
        const formData = new FormData();
        formData.append('action', 'backup');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                loadBackups(); // Reload list
                alert('Sauvegarde créée avec succès !');
            } else {
                alert('Erreur: ' + data.error);
            }
        } catch (e) {
            alert('Erreur réseau');
        } finally {
            hideLoading();
        }
    }

    async function deleteBackup(filename) {
        if(!confirm('Supprimer définitivement cette sauvegarde ?')) return;

        showLoading('SUPPRESSION...');
        const formData = new FormData();
        formData.append('action', 'delete_backup');
        formData.append('filename', filename);

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) loadBackups();
        } catch (e) {
            alert('Erreur');
        } finally {
            hideLoading();
        }
    }

    async function restoreBackup(filename) {
        if(!confirm('⚠️ DANGER : Ceci va écraser la base actuelle avec la sauvegarde choisie. Êtes-vous sûr ?')) return;

        showLoading('RESTAURATION SYSTÈME...');
        const formData = new FormData();
        formData.append('action', 'restore');
        formData.append('filename', filename);

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                alert('Système restauré avec succès !');
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Inconnue'));
            }
        } catch (e) {
            alert('Erreur critique pendant la restauration');
        } finally {
            hideLoading();
        }
    }

    async function executeWipe() {
        const pass = document.getElementById('confirmPassword').value;
        if (pass !== 'DELETE') {
            alert('Mot de passe incorrect. Tapez DELETE en majuscules.');
            return;
        }

        closeModal();
        showLoading('DESTRUCTION ET NETTOYAGE EN COURS...');
        
        const formData = new FormData();
        formData.append('action', 'wipe');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                alert('✅ Base de données nettoyée avec succès.\nLes comptes administrateurs ont été conservés.');
                loadBackups(); // To show the auto-backup
            } else {
                alert('❌ Erreur: ' + (data.error || 'Erreur inconnue'));
            }
        } catch (e) {
            alert('❌ Erreur critique serveur');
        } finally {
            hideLoading();
        }
    }
</script>
</body>
</html>
