<?php
/**
 * Widget Chatbot "ServoBot"
 * Assistant virtuel pour la pré-vente
 */
?>
<style>
/* Chatbot Styles */
#servo-chatbot {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1060;
    font-family: 'Outfit', sans-serif;
}

.chat-trigger {
    width: 60px;
    height: 60px;
    background: var(--primary);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 0 20px var(--primary-glow);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255,255,255,0.2);
}

.chat-trigger:hover {
    transform: scale(1.1);
    box-shadow: 0 0 30px var(--primary-glow);
}

.chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    display: none; /* Hidden by default */
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    animation: chatOpen 0.3s forwards;
}

@keyframes chatOpen {
    from { opacity: 0; transform: scale(0.8) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.chat-header {
    background: linear-gradient(90deg, #020617 0%, #1e1b4b 100%);
    padding: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    gap: 10px;
}

.bot-avatar {
    width: 35px;
    height: 35px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: black;
    box-shadow: 0 0 10px var(--primary-glow);
}

.chat-messages {
    flex-grow: 1;
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message {
    max-width: 80%;
    padding: 10px 15px;
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.4;
    position: relative;
    animation: msgIn 0.3s forwards;
}

.message.bot {
    background: rgba(255,255,255,0.05);
    color: var(--text-main);
    border-bottom-left-radius: 2px;
    align-self: flex-start;
    border: 1px solid rgba(255,255,255,0.05);
}

.message.user {
    background: var(--primary);
    color: black;
    border-bottom-right-radius: 2px;
    align-self: flex-end;
    font-weight: 500;
}

@keyframes msgIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.typing-indicator {
    padding: 10px 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    align-self: flex-start;
    display: none;
}

.typing-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: var(--text-muted);
    border-radius: 50%;
    margin-right: 3px;
    animation: typing 1.4s infinite ease-in-out both;
}

.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

.chat-input-area {
    padding: 1rem;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.quick-replies {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.quick-reply-btn {
    background: transparent;
    border: 1px solid var(--primary);
    color: var(--primary);
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: 0.2s;
}

.quick-reply-btn:hover {
    background: var(--primary);
    color: black;
}
</style>

<div id="servo-chatbot">
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div class="bot-avatar"><i class="fa-solid fa-robot"></i></div>
            <div>
                <div class="fw-bold text-white">Servo AI</div>
                <div class="text-success x-small" style="font-size: 0.7rem;"><i class="fa-solid fa-circle me-1"></i>En ligne</div>
            </div>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="toggleChat()"></button>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                Bonjour ! 👋 Je suis l'IA de Servo. Je peux répondre à vos questions sur les fonctionnalités ou les tarifs.
            </div>
            <div class="typing-indicator" id="typingIndicator">
                <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
            </div>
        </div>
        
        <div class="chat-input-area">
            <div class="quick-replies" id="quickReplies">
                <button class="quick-reply-btn" onclick="sendReply('Combien ça coûte ?')">Prix ?</button>
                <button class="quick-reply-btn" onclick="sendReply('Je veux une démo')">Démo</button>
                <button class="quick-reply-btn" onclick="sendReply('Migration RepairDesk ?')">Migration</button>
            </div>
            <div class="input-group">
                <input type="text" class="form-control bg-transparent text-white border-secondary" placeholder="Écrivez..." id="chatInput" style="border-color: rgba(255,255,255,0.2);">
                <button class="btn btn-primary" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <div class="chat-trigger" onclick="toggleChat()">
        <i class="fa-solid fa-message text-black fs-4"></i>
    </div>
</div>

<script>
let chatOpen = false;

function toggleChat() {
    const window = document.getElementById('chatWindow');
    chatOpen = !chatOpen;
    window.style.display = chatOpen ? 'flex' : 'none';
    if(chatOpen) {
        document.getElementById('chatInput').focus();
    }
}

function sendReply(msg) {
    document.getElementById('chatInput').value = msg;
    sendMessage();
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if(!msg) return;
    
    // Add user message
    addMessage(msg, 'user');
    input.value = '';
    
    // Simulate thinking
    showTyping();
    
    // Simple logic
    setTimeout(() => {
        hideTyping();
        let reply = "Je ne suis pas sûr de comprendre. Voulez-vous parler à un humain ?";
        const lower = msg.toLowerCase();
        
        if(lower.includes('prix') || lower.includes('coûte') || lower.includes('tarif')) {
            reply = "Notre offre est simple : <strong class='text-primary'>49€/mois tout compris</strong> ! Pas de frais cachés. <br><br>👉 <a href='/pricing' class='text-white text-decoration-underline'>Voir les détails</a>";
        } else if(lower.includes('démo') || lower.includes('demo') || lower.includes('essai')) {
            reply = "Excellent choix ! Vous pouvez tester Servo gratuitement pendant 30 jours, sans carte bancaire.<br><br>👉 <a href='/inscription' class='text-white text-decoration-underline'>Commencer mon essai</a>";
        } else if(lower.includes('migration') || lower.includes('repairdesk')) {
            reply = "On s'occupe de tout ! Notre équipe migre vos données RepairDesk gratuitement en 24h. Clients, Stocks, tout est conservé.";
        } else if(lower.includes('bonjour') || lower.includes('hello')) {
            reply = "Bonjour ! Comment puis-je vous aider à moderniser votre atelier aujourd'hui ?";
        }
        
        addMessage(reply, 'bot');
    }, 1500);
}

function addMessage(text, sender) {
    const div = document.createElement('div');
    div.className = `message ${sender}`;
    div.innerHTML = text;
    const messages = document.getElementById('chatMessages');
    const typing = document.getElementById('typingIndicator');
    messages.insertBefore(div, typing);
    messages.scrollTop = messages.scrollHeight;
}

function showTyping() {
    document.getElementById('typingIndicator').style.display = 'block';
    document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
}

function hideTyping() {
    document.getElementById('typingIndicator').style.display = 'none';
}

// Auto open after delay
setTimeout(() => {
    if(!chatOpen) {
        // Optional: toggleChat(); // Don't auto open to be less intrusive
    }
}, 5000);
</script>
