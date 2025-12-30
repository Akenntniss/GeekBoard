<?php
/**
 * Bannière globale d'appel VOIP
 * Gère les appels entrants ET les appels en cours
 */
if (!isset($_SESSION['user_id'])) return;
$currentUserId = $_SESSION['user_id'];
?>

<!-- Bannière d'appel globale -->
<div id="voip-call-banner" class="voip-call-banner">
    <!-- Mode: Appel entrant -->
    <div class="voip-mode voip-mode-incoming" id="voip-mode-incoming">
        <div class="voip-caller-avatar" id="voip-caller-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="voip-caller-info">
            <span class="voip-caller-name" id="voip-caller-name">Appel entrant...</span>
            <span class="voip-call-status" id="voip-call-type">📞 Appel audio</span>
        </div>
        <div class="voip-actions">
            <button class="voip-btn voip-btn-reject" id="voip-reject-btn" title="Refuser">
                <i class="fas fa-phone-slash"></i>
            </button>
            <button class="voip-btn voip-btn-answer" id="voip-answer-btn" title="Répondre">
                <i class="fas fa-phone"></i>
            </button>
        </div>
    </div>
    
    <!-- Mode: Appel en cours -->
    <div class="voip-mode voip-mode-active" id="voip-mode-active" style="display: none;">
        <div class="voip-caller-avatar voip-avatar-active" id="voip-active-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="voip-caller-info">
            <span class="voip-caller-name" id="voip-active-name">En appel avec...</span>
            <span class="voip-call-status voip-status-connecting" id="voip-active-status">🔄 Connexion...</span>
        </div>
        <div class="voip-call-timer" id="voip-call-timer">00:00</div>
        <div class="voip-actions">
            <button class="voip-btn voip-btn-hangup" id="voip-hangup-btn" title="Raccrocher">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>
</div>

<!-- Audio pour la sonnerie d'appel -->
<audio id="voip-ringtone" src="/assets/sounds/ringtone.mp3" loop preload="auto"></audio>
<!-- Éléments audio/vidéo pour l'appel -->
<audio id="voip-remote-audio" autoplay style="display:none;"></audio>

<style>
.voip-call-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 99999;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    transform: translateY(-100%);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    border-bottom: 2px solid #3b82f6;
}

.voip-call-banner.active {
    transform: translateY(0);
}

.voip-call-banner.in-call {
    border-bottom-color: #22c55e;
    background: linear-gradient(135deg, #0f2e1f 0%, #0a1f15 100%);
}

.voip-mode {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    max-width: 600px;
    margin: 0 auto;
    gap: 12px;
}

.voip-caller-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    flex-shrink: 0;
    animation: pulse-ring 1.5s infinite;
}

.voip-avatar-active {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    animation: none;
}

@keyframes pulse-ring {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
    70% { box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}

.voip-caller-info {
    flex: 1;
    min-width: 0;
}

.voip-caller-name {
    display: block;
    font-size: 1rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.voip-call-status {
    display: block;
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 2px;
}

.voip-status-connecting {
    color: #fbbf24;
    animation: blink 1s infinite;
}

.voip-status-connected {
    color: #22c55e;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.voip-call-timer {
    font-family: 'Consolas', monospace;
    font-size: 1.2rem;
    font-weight: 600;
    color: #22c55e;
    padding: 0 10px;
}

.voip-actions {
    display: flex;
    gap: 10px;
}

.voip-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.voip-btn:hover { transform: scale(1.1); }

.voip-btn-reject, .voip-btn-hangup {
    background: #ef4444;
    color: white;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.5);
}

.voip-btn-answer {
    background: #22c55e;
    color: white;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.5);
    animation: answer-pulse 1s infinite;
}

@keyframes answer-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@media (max-width: 500px) {
    .voip-mode { padding: 10px 15px; }
    .voip-caller-avatar { width: 42px; height: 42px; font-size: 1.1rem; }
    .voip-btn { width: 42px; height: 42px; font-size: 1rem; }
    .voip-call-timer { font-size: 1rem; }
}
</style>

<script>
(function() {
    const VOIP_API_URL = '/api/voip/handler.php';
    const POLLING_INTERVAL = 2500;
    const CURRENT_USER_ID = <?php echo $currentUserId; ?>;
    
    // Éléments DOM
    let banner, modeIncoming, modeActive;
    let callerName, callType, callerAvatar;
    let activeName, activeStatus, activeAvatar, callTimer;
    let answerBtn, rejectBtn, hangupBtn;
    let ringtone, remoteAudio;
    
    // WebRTC
    let peerConnection = null;
    let localStream = null;
    
    // État
    let currentCall = null;
    let timerInterval = null;
    let callStartTime = null;
    let pollInterval = null;
    
    // ICE Servers
    const iceServers = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };
    
    document.addEventListener('DOMContentLoaded', init);
    
    function init() {
        // Récupérer les éléments DOM
        banner = document.getElementById('voip-call-banner');
        modeIncoming = document.getElementById('voip-mode-incoming');
        modeActive = document.getElementById('voip-mode-active');
        
        callerName = document.getElementById('voip-caller-name');
        callType = document.getElementById('voip-call-type');
        callerAvatar = document.getElementById('voip-caller-avatar');
        
        activeName = document.getElementById('voip-active-name');
        activeStatus = document.getElementById('voip-active-status');
        activeAvatar = document.getElementById('voip-active-avatar');
        callTimer = document.getElementById('voip-call-timer');
        
        answerBtn = document.getElementById('voip-answer-btn');
        rejectBtn = document.getElementById('voip-reject-btn');
        hangupBtn = document.getElementById('voip-hangup-btn');
        
        ringtone = document.getElementById('voip-ringtone');
        remoteAudio = document.getElementById('voip-remote-audio');
        
        if (!banner) return;
        
        // Event listeners
        if (answerBtn) answerBtn.onclick = handleAnswer;
        if (rejectBtn) rejectBtn.onclick = handleReject;
        if (hangupBtn) hangupBtn.onclick = handleHangup;
        
        // Ne pas démarrer le polling si on est sur la page appels
        const isOnCallsPage = window.location.href.includes('page=appels');
        if (!isOnCallsPage) {
            startPolling();
        }
        
        // Écouter les messages du Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'VOIP_INCOMING_CALL') {
                    if (ringtone) ringtone.play().catch(() => {});
                    checkIncomingCall();
                }
            });
        }
    }
    
    function startPolling() {
        checkIncomingCall();
        pollInterval = setInterval(checkIncomingCall, POLLING_INTERVAL);
    }
    
    async function checkIncomingCall() {
        if (currentCall && currentCall.status === 'active') return;
        
        try {
            const res = await fetch(VOIP_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check_incoming' }),
                credentials: 'include'
            });
            const data = await res.json();
            
            if (data.status === 'incoming' && data.call) {
                showIncomingCall(data.call, data.caller_name);
            } else if (currentCall && currentCall.status === 'incoming') {
                hideCall();
            }
        } catch (e) {
            console.error('[VOIP] Erreur polling:', e);
        }
    }
    
    function showIncomingCall(callData, name) {
        if (currentCall && currentCall.id === callData.id) return;
        
        currentCall = { ...callData, status: 'incoming', callerName: name };
        
        // Mettre à jour l'UI
        if (callerName) callerName.textContent = name || 'Inconnu';
        if (callType) {
            const type = callData.call_type || 'video';
            callType.textContent = type === 'audio' ? '📞 Appel audio' : '📹 Appel vidéo';
        }
        if (callerAvatar) {
            const initials = name ? name.substring(0, 2).toUpperCase() : '??';
            callerAvatar.innerHTML = initials;
        }
        
        // Afficher mode incoming
        modeIncoming.style.display = 'flex';
        modeActive.style.display = 'none';
        banner.classList.remove('in-call');
        banner.classList.add('active');
        
        // Jouer la sonnerie
        if (ringtone) ringtone.play().catch(() => {});
    }
    
    function hideCall() {
        currentCall = null;
        banner.classList.remove('active', 'in-call');
        if (ringtone) { ringtone.pause(); ringtone.currentTime = 0; }
        stopTimer();
        cleanupWebRTC();
    }
    
    async function handleAnswer() {
        if (!currentCall) return;
        
        // Sauvegarder l'ID avant toute modification
        const callId = currentCall.id;
        
        // Stopper la sonnerie
        if (ringtone) { ringtone.pause(); ringtone.currentTime = 0; }
        
        // Rediriger immédiatement vers la page appels
        window.location.href = '/index.php?page=appels&incoming_call=' + callId + '&auto_answer=1';
    }
    
    async function handleReject() {
        if (!currentCall) return;
        
        const callId = currentCall.id;
        hideCall();
        
        try {
            await fetch(VOIP_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject_call', call_id: callId }),
                credentials: 'include'
            });
        } catch (e) {
            console.error('[VOIP] Erreur rejet:', e);
        }
    }
    
    async function handleHangup() {
        if (!currentCall) return;
        
        const callId = currentCall.id;
        hideCall();
        
        try {
            await fetch(VOIP_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'hangup_call', call_id: callId }),
                credentials: 'include'
            });
        } catch (e) {
            console.error('[VOIP] Erreur raccrochage:', e);
        }
    }
    
    function switchToActiveMode(statusText) {
        currentCall.status = 'connecting';
        
        // Mettre à jour l'UI
        if (activeName) activeName.textContent = currentCall.callerName || 'En appel';
        if (activeStatus) {
            activeStatus.textContent = statusText;
            activeStatus.className = 'voip-call-status voip-status-connecting';
        }
        if (activeAvatar) {
            const initials = currentCall.callerName ? currentCall.callerName.substring(0, 2).toUpperCase() : '??';
            activeAvatar.innerHTML = initials;
        }
        if (callTimer) callTimer.textContent = '00:00';
        
        // Changer de mode
        modeIncoming.style.display = 'none';
        modeActive.style.display = 'flex';
        banner.classList.add('in-call');
    }
    
    function updateActiveStatus(text) {
        if (activeStatus) {
            activeStatus.textContent = text;
            activeStatus.className = 'voip-call-status ' + 
                (text.includes('Connexion') ? 'voip-status-connecting' : 'voip-status-connected');
        }
    }
    
    async function setupWebRTC() {
        peerConnection = new RTCPeerConnection(iceServers);
        
        // Obtenir le média local (audio uniquement pour la bannière)
        const isVideo = currentCall.call_type === 'video';
        localStream = await navigator.mediaDevices.getUserMedia({ 
            audio: true, 
            video: false // Pas de vidéo dans la bannière
        });
        
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        
        // Gérer le flux distant
        peerConnection.ontrack = (event) => {
            if (remoteAudio && event.streams[0]) {
                remoteAudio.srcObject = event.streams[0];
            }
        };
        
        // Gérer les ICE candidates
        peerConnection.onicecandidate = (event) => {
            if (event.candidate && currentCall) {
                sendIceCandidate(event.candidate);
            }
        };
        
        peerConnection.onconnectionstatechange = () => {
            console.log('[VOIP] Connection state:', peerConnection.connectionState);
            if (peerConnection.connectionState === 'connected') {
                updateActiveStatus('✅ Appel en cours');
            } else if (peerConnection.connectionState === 'failed' || peerConnection.connectionState === 'disconnected') {
                updateActiveStatus('❌ Déconnecté');
                setTimeout(hideCall, 2000);
            }
        };
    }
    
    async function sendIceCandidate(candidate) {
        try {
            await fetch(VOIP_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_ice_candidate',
                    call_id: currentCall.id,
                    candidate: JSON.stringify(candidate)
                }),
                credentials: 'include'
            });
        } catch (e) {}
    }
    
    function startCallPolling() {
        // Polling pour les ICE candidates et le statut
        const callPollInterval = setInterval(async () => {
            if (!currentCall || currentCall.status !== 'active') {
                clearInterval(callPollInterval);
                return;
            }
            
            try {
                const res = await fetch(VOIP_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'poll_call_status', call_id: currentCall.id }),
                    credentials: 'include'
                });
                const data = await res.json();
                
                // Si l'appel est terminé
                if (data.call_status === 'ended' || data.call_status === 'rejected') {
                    clearInterval(callPollInterval);
                    hideCall();
                    return;
                }
                
                // Ajouter les ICE candidates distants
                if (data.ice_candidates && peerConnection) {
                    for (const c of data.ice_candidates) {
                        if (c.sender_id !== CURRENT_USER_ID) {
                            try {
                                const candidate = JSON.parse(c.candidate);
                                await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                            } catch (e) {}
                        }
                    }
                }
            } catch (e) {}
        }, 1500);
    }
    
    function startTimer() {
        callStartTime = Date.now();
        timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            const min = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const sec = (elapsed % 60).toString().padStart(2, '0');
            if (callTimer) callTimer.textContent = `${min}:${sec}`;
        }, 1000);
    }
    
    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        callStartTime = null;
    }
    
    function cleanupWebRTC() {
        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
    }
    
    // Exposer pour debug
    window.VoipBanner = { show: showIncomingCall, hide: hideCall };
})();
</script>
