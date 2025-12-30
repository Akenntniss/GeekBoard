/**
 * Messagerie - JavaScript principal (v3.0)
 */

// Variables globales
let currentConversationId = null;
let lastMessageId = null;
let isLoadingMessages = false;
let searchTimeout = null;
let refreshInterval = null;
let darkMode = false;
let attachments = [];
let emojiPickerVisible = false;
let mobileView = window.innerWidth < 992;
let participantsCache = {};
let isTyping = false;
let typingTimeout = null;
let typingUsers = {};
let messageReactions = {};
let messagesPage = 1;
let hasMoreMessages = true;
// let userId = null; // ID de l'utilisateur actuel (Géré via window.userId)
// Variable userId définie globalement ou récupérée via window.userId

// DEBUG
console.log('Initialisation de la messagerie v3.0');

// Initialisation
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM chargé, démarrage de l\'initialisation...');

    // Récupérer l'ID utilisateur depuis PHP s'il est défini
    if (typeof window.userId !== 'undefined' && window.userId !== null) {
        // userId est déjà défini globalement
        console.log('ID utilisateur récupéré depuis PHP:', window.userId);
        initializeApp();
    } else {
        // Sinon, vérifier la session
        console.log('Vérification de la session...');
        checkSession();
    }
});

/**
 * Vérifie l'état de la session utilisateur
 */
function checkSession() {
    fetch('/messagerie/api/check_session.php')
        .then(response => response.json())
        .then(data => {
            console.log('Statut de la session:', data);

            if (data.logged_in) {
                window.userId = data.user_id; // Mise à jour globale
                console.log('Utilisateur connecté, ID:', window.userId);
                initializeApp();
            } else {
                console.error('Utilisateur non connecté');
                showError('Erreur de session', 'Vous n\'êtes pas connecté. Veuillez vous reconnecter.');

                const conversationsList = document.getElementById('conversationsList');
                if (conversationsList) {
                    conversationsList.innerHTML = `
                        <div class="alert alert-warning m-3">
                            <i class="fas fa-user-slash me-2"></i>
                            <strong>Session expirée</strong><br>
                            Vous n'êtes pas connecté ou votre session a expiré. 
                            <a href="../index.php" class="alert-link">Se connecter</a>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification de la session:', error);
            showError('Erreur serveur', 'Impossible de vérifier votre session. Veuillez réessayer plus tard.');
        });
}

/**
 * Initialise l'application une fois l'utilisateur identifié
 */
function initializeApp() {
    // Afficher l'ID utilisateur dans la console pour le débogage
    console.log('Initialisation de l\'application pour l\'utilisateur', userId);

    // Initialiser l'interface
    initUI();

    // Charger les conversations
    loadConversations();

    // Définir l'intervalle de rafraîchissement
    refreshInterval = setInterval(refreshData, 15000); // Toutes les 15 secondes

    // Événement de redimensionnement de la fenêtre
    window.addEventListener('resize', handleWindowResize);

    // Initialiser l'écouteur d'événements de frappe
    initTypingEventListener();

    // Initialiser le mode sombre basé sur la préférence du système
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        if (localStorage.getItem('messagerie_dark_mode') === null) {
            darkMode = true;
            document.body.classList.add('dark-theme');
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('messagerie_dark_mode', 'true');
        }
    }

    console.log('Initialisation terminée.');

    // Auto-open new conversation modal with pre-selected user if requested
    if (typeof autoNewConvUserId !== 'undefined' && autoNewConvUserId !== null) {
        console.log('Auto-opening new conversation modal for user:', autoNewConvUserId);
        // Wait a bit for everything to load
        setTimeout(function () {
            openNewConversationModalWithUser(autoNewConvUserId);
        }, 800);
    }
}

/**
 * Opens new conversation modal with a pre-selected user
 */
function openNewConversationModalWithUser(targetUserId) {
    // Reset the form
    document.getElementById('newConversationForm').reset();

    // Set type to direct
    document.querySelector('input[name="conversationType"][value="direct"]').checked = true;

    // Hide group fields
    document.querySelectorAll('.group-only').forEach(field => {
        field.style.display = 'none';
    });

    // Load users and pre-select the target user
    loadUsersForSelectWithPreselection(targetUserId);

    // Open the modal
    const modal = new bootstrap.Modal(document.getElementById('newConversationModal'));
    modal.show();

    // Handler for create button
    const createBtn = document.getElementById('createConversationBtn');
    const newCreateBtn = createBtn.cloneNode(true);
    createBtn.parentNode.replaceChild(newCreateBtn, createBtn);
    newCreateBtn.addEventListener('click', createConversation);
}

/**
 * Loads users for select with a pre-selected user
 */
function loadUsersForSelectWithPreselection(targetUserId) {
    const select = $('#participants');

    // Clear and add loading option
    select.empty().append('<option disabled>Chargement...</option>');

    fetch('/messagerie/api/get_users.php')
        .then(response => response.json())
        .then(data => {
            select.empty();

            if (data.success && data.users) {
                data.users.forEach(user => {
                    const option = new Option(user.full_name, user.id, false, user.id == targetUserId);
                    select.append(option);
                });

                // Initialize Select2
                select.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Rechercher un utilisateur...',
                    allowClear: true,
                    dropdownParent: $('#newConversationModal')
                });

                // Trigger change to update UI
                select.trigger('change');
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            select.empty().append('<option disabled>Erreur de chargement</option>');
        });
}

/**
 * Initialisation de l'interface utilisateur
 */
function initUI() {
    console.log('Initialisation de l\'UI');

    // Thème sombre/clair automatique basé sur le système
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function handleThemeChange(e) {
        const isDarkMode = e.matches;
        darkMode = isDarkMode;
        // Appliquer le thème
        document.body.classList.toggle('dark-theme', isDarkMode);
        document.documentElement.setAttribute('data-bs-theme', isDarkMode ? 'dark' : 'light');
        console.log('Thème mis à jour automatiquement:', isDarkMode ? 'Sombre' : 'Clair');
    }

    // Appliquer le thème initial
    handleThemeChange(darkModeMediaQuery);

    // Écouter les changements de préférences système
    darkModeMediaQuery.addEventListener('change', handleThemeChange);

    // Barre de recherche
    const searchToggleBtn = document.getElementById('searchToggleBtn');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    if (searchToggleBtn) {
        searchToggleBtn.addEventListener('click', toggleSearchBar);
    }
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', clearSearch);
    }

    // Bouton nouvelle conversation
    const newMessageBtn = document.getElementById('newMessageBtn');
    const newChatBtn = document.getElementById('newChatBtn');
    if (newMessageBtn) {
        newMessageBtn.addEventListener('click', openNewConversationModal);
    }
    if (newChatBtn) {
        newChatBtn.addEventListener('click', openNewConversationModal);
    }

    // Bouton d'actualisation
    const refreshAllBtn = document.getElementById('refreshAllBtn');
    if (refreshAllBtn) {
        refreshAllBtn.addEventListener('click', refreshConversations);
    }

    // Marquer tout comme lu
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllConversationsAsRead);
    }

    // Filtres de conversation
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            filterConversations(this.dataset.filter);
        });
    });

    // Recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = this.value.trim();
                searchConversations(query);
            }, 300);
        });
    }

    // Formulaire de message
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', function (e) {
            e.preventDefault();
            sendMessage();
        });
    }

    // Zone de texte du message
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keydown', function (e) {
            // Envoyer avec Ctrl+Entrée
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }

            // Ajuster la hauteur automatiquement
            autoResizeTextarea(this);
        });
        // Gestion de la barre latérale sur mobile
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebar = document.querySelector('.messagerie-sidebar'); // Nouveau sélecteur

        if (sidebarToggleBtn && sidebar) {
            sidebarToggleBtn.addEventListener('click', function () {
                sidebar.classList.add('show');
            });
        }

        if (sidebarCloseBtn && sidebar) {
            sidebarCloseBtn.addEventListener('click', function () {
                sidebar.classList.remove('show');
            });
        }
    }

    // Pièces jointes
    const attachBtn = document.getElementById('attachBtn');
    if (attachBtn) {
        attachBtn.addEventListener('click', function () {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = true;
            fileInput.accept = 'image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain';
            fileInput.onchange = handleFileSelection;
            fileInput.click();
        });
    }

    // Émojis
    const emojiBtn = document.getElementById('emojiBtn');
    if (emojiBtn) {
        emojiBtn.addEventListener('click', toggleEmojiPicker);
    }

    // Actions du chat
    const chatInfoBtn = document.getElementById('chatInfoBtn');
    if (chatInfoBtn) {
        chatInfoBtn.addEventListener('click', showConversationInfo);
    }

    const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');
    if (scrollToBottomBtn) {
        scrollToBottomBtn.addEventListener('click', scrollToBottom);
    }

    const searchMessagesBtn = document.getElementById('searchMessagesBtn');
    if (searchMessagesBtn) {
        searchMessagesBtn.addEventListener('click', searchInMessages);
    }

    const toggleFavoriteBtn = document.getElementById('toggleFavoriteBtn');
    if (toggleFavoriteBtn) {
        toggleFavoriteBtn.addEventListener('click', toggleFavorite);
    }

    const toggleMuteBtn = document.getElementById('toggleMuteBtn');
    if (toggleMuteBtn) {
        toggleMuteBtn.addEventListener('click', toggleMute);
    }

    const toggleArchiveBtn = document.getElementById('toggleArchiveBtn');
    if (toggleArchiveBtn) {
        toggleArchiveBtn.addEventListener('click', toggleArchive);
    }

    const leaveConversationBtn = document.getElementById('leaveConversationBtn');
    if (leaveConversationBtn) {
        leaveConversationBtn.addEventListener('click', leaveConversation);
    }

    console.log('UI initialisée');
}

/**
 * Chargement des conversations
 */
function loadConversations(filter = 'all', search = '') {
    console.log('Chargement des conversations...', { filter, search });

    const conversationsList = document.getElementById('conversationsList');
    conversationsList.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';

    let url = '/messagerie/api/get_conversations.php';
    let params = [];

    // Ajouter le paramètre de page si messagesPage est défini
    if (typeof messagesPage !== 'undefined') {
        params.push('page=' + messagesPage);
    }

    // Ajouter les filtres
    if (filter === 'unread') {
        params.push('unread=1');
    } else if (filter === 'direct') {
        params.push('type=direct');
    } else if (filter === 'group') {
        params.push('type=groupe');
    } else if (filter === 'favorites') {
        params.push('favorites=1');
    } else if (filter === 'archived') {
        params.push('archived=1');
    } else if (filter === 'priorite-importante') {
        params.push('priorite=importante');
    } else if (filter === 'priorite-urgente') {
        params.push('priorite=urgente');
    }

    // Ajouter la recherche
    if (search) {
        params.push(`search=${encodeURIComponent(search)}`);
    }

    // Construire l'URL complète
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    console.log('URL de chargement:', url);

    // Charger les conversations
    fetch(url)
        .then(response => {
            console.log('Statut de la réponse:', response.status, response.statusText);

            // Récupérer le texte de la réponse pour le débogage
            return response.text().then(text => {
                console.log('Réponse brute:', text);

                // Essayer de parser le JSON
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    throw new Error('Réponse invalide: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Données reçues:', data);

            if (data.success) {
                console.log('Conversations reçues:', data.conversations.length);
                displayConversations(data.conversations);
            } else {
                console.error('Erreur de l\'API:', data.message);
                showError('Erreur', data.message || 'Erreur lors du chargement des conversations');

                // Afficher un message d'erreur dans la liste
                conversationsList.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'Erreur lors du chargement des conversations'}
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary btn-sm" onclick="refreshConversations()">
                            <i class="fas fa-sync-alt me-2"></i>Réessayer
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur de chargement des conversations:', error);

            // Afficher un message d'erreur dans la liste avec détails
            conversationsList.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Impossible de charger les conversations
                    <div class="small mt-2">Détail: ${error.message}</div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-primary btn-sm" onclick="refreshConversations()">
                        <i class="fas fa-sync-alt me-2"></i>Réessayer
                    </button>
                </div>
            `;

            // Vérifier l'état de la session
            fetch('/messagerie/api/check_session.php')
                .then(response => response.json())
                .then(data => {
                    console.log('État de la session:', data);

                    if (!data.logged_in) {
                        conversationsList.innerHTML += `
                            <div class="alert alert-warning mt-3 mx-3">
                                <i class="fas fa-user-slash me-2"></i>
                                Vous n'êtes pas connecté. <a href="/login.php" class="alert-link">Se connecter</a>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error('Erreur lors de la vérification de la session:', err);
                });
        });
}

/**
 * Affiche les conversations dans la liste
 */
function displayConversations(conversations) {
    console.log('Affichage des conversations:', conversations);
    const conversationsList = document.getElementById('conversationsList');

    if (!conversations || conversations.length === 0) {
        conversationsList.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p>Aucune conversation trouvée</p>
                <button class="btn btn-sm btn-primary" onclick="openNewConversationModal()">
                    <i class="fas fa-plus me-2"></i>Nouvelle conversation
                </button>
            </div>
        `;
        return;
    }

    let html = '';

    // Valeur de secours pour userId si non défini
    const currentUserId = userId || -1;
    console.log('Utilisation de l\'ID utilisateur:', currentUserId);

    conversations.forEach(conversation => {
        try {
            // Vérifier que les propriétés essentielles existent
            if (!conversation.id) {
                console.error('Conversation sans ID', conversation);
                return;
            }

            // Valeurs par défaut pour éviter les erreurs
            const titre = conversation.titre || 'Sans titre';
            const type = conversation.type || 'direct';
            const unreadCount = parseInt(conversation.unread_count || 0);
            const estFavoris = Boolean(conversation.est_favoris);
            const estMute = Boolean(conversation.notification_mute);

            // Déterminer le type d'avatar et les informations à afficher
            let avatarClass = 'conversation-avatar';
            let avatarContent = getAvatarContent(conversation, currentUserId);

            // Formater le dernier message
            let lastMessageText = 'Pas de message';
            let lastMessageTime = '';

            if (conversation.last_message) {
                if (conversation.last_message.type === 'image') {
                    lastMessageText = '<i class="far fa-image me-1"></i> Image';
                } else if (conversation.last_message.type === 'file') {
                    lastMessageText = '<i class="far fa-file me-1"></i> Fichier';
                } else {
                    lastMessageText = conversation.last_message.contenu || '';
                }

                lastMessageTime = conversation.last_message.formatted_date || '';
            }

            // Construire les badges
            let badges = '';

            if (unreadCount > 0) {
                badges += `<span class="conversation-badge conversation-unread">${unreadCount}</span>`;
            }

            // Badge de priorité
            if (conversation.priorite && conversation.priorite !== 'normale') {
                const priorityClass = conversation.priorite === 'urgente' ? 'bg-danger' : 'bg-warning text-dark';
                const priorityIcon = conversation.priorite === 'urgente' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle';
                badges += `<span class="conversation-badge ${priorityClass} text-white" title="${conversation.priorite}"><i class="fas ${priorityIcon}"></i></span>`;
            }

            // Badge de statut archivé/fermé
            if (conversation.statut && conversation.statut !== 'active') {
                badges += `<span class="conversation-badge bg-secondary text-white" title="${conversation.statut}"><i class="fas fa-archive"></i></span>`;
            } else if (conversation.est_archive) { // Fallback pour l'ancien champ
                badges += `<span class="conversation-badge bg-secondary text-white" title="Archivée"><i class="fas fa-archive"></i></span>`;
            }

            if (estFavoris) {
                badges += '<span class="conversation-badge conversation-favorite"><i class="fas fa-star"></i></span>';
            }

            if (estMute) {
                badges += '<span class="conversation-badge conversation-muted"><i class="fas fa-bell-slash"></i></span>';
            }

            // Extraire les noms des participants pour les groupes
            let participantsText = getParticipantsText(conversation, currentUserId);

            // Construction du sous-titre : Obet ou dernier message
            let subtitle = '';
            if (conversation.objet) {
                subtitle = `<div class="conversation-subject fw-bold text-dark small mb-1"><i class="fas fa-heading me-1 text-muted"></i>${conversation.objet}</div>`;
            }

            // Construire l'élément de conversation
            html += `
                <div class="conversation-item ${unreadCount > 0 ? 'unread' : ''} ${currentConversationId == conversation.id ? 'active' : ''}" 
                     data-id="${conversation.id}" 
                     onclick="loadConversation(${conversation.id})">
                    <div class="${avatarClass}">
                        ${avatarContent}
                    </div>
                    <div class="conversation-content">
                        <div class="conversation-header">
                            <h5 class="conversation-title">${titre}</h5>
                            <span class="conversation-time">${lastMessageTime}</span>
                        </div>
                        ${subtitle}
                        <div class="conversation-preview">
                            <div class="conversation-last-message">
                                ${type === 'groupe' && !subtitle ? participantsText + ': ' : ''}${lastMessageText}
                            </div>
                            <div class="conversation-badges">
                                ${badges}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Erreur lors de l\'affichage de la conversation', error, conversation);
        }
    });

    if (html) {
        conversationsList.innerHTML = html;
    } else {
        // Si aucune conversation n'a pu être affichée malgré la présence de données
        conversationsList.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p>Impossible d'afficher les conversations</p>
                <button class="btn btn-sm btn-primary mt-2" onclick="refreshConversations()">
                    <i class="fas fa-sync-alt me-2"></i>Réessayer
                </button>
            </div>
        `;
    }
}

/**
 * Obtient le contenu de l'avatar pour une conversation
 */
function getAvatarContent(conversation, currentUserId) {
    // Valeur par défaut
    let avatarContent = '?';

    try {
        // Déterminer le contenu de l'avatar en fonction du type de conversation
        const type = conversation.type || 'direct';

        if (type === 'groupe') {
            return '<i class="fas fa-users"></i>';
        } else if (type === 'annonce') {
            return '<i class="fas fa-bullhorn"></i>';
        }

        // Pour les conversations directes
        // Cas 1: Les participants sont un tableau d'objets (format attendu)
        if (conversation.participants && Array.isArray(conversation.participants)) {
            // Filtrer les participants pour trouver le bon
            const otherParticipant = conversation.participants.find(p => {
                // Vérifier si l'objet a un user_id et full_name et n'est pas l'utilisateur actuel
                return p && p.user_id && p.user_id != currentUserId && p.full_name;
            });

            if (otherParticipant && otherParticipant.full_name) {
                avatarContent = otherParticipant.full_name.charAt(0).toUpperCase();
            }
        }
        // Cas 2: Les participants sont un tableau de chaînes (format alternatif)
        else if (conversation.participants && Array.isArray(conversation.participants) && conversation.participants.length > 0 && typeof conversation.participants[0] === 'string') {
            const name = conversation.participants[0];
            if (name) {
                avatarContent = name.charAt(0).toUpperCase();
            }
        }
        // Cas 3: Le titre de la conversation peut être utilisé comme fallback
        else if (conversation.titre) {
            avatarContent = conversation.titre.charAt(0).toUpperCase();
        }
    } catch (e) {
        console.warn('Erreur lors de la génération de l\'avatar:', e);
    }

    return avatarContent;
}

/**
 * Obtient le texte des participants pour une conversation de groupe
 */
function getParticipantsText(conversation, currentUserId) {
    let participantsText = '';

    try {
        // Cas 1: Les participants sont un tableau d'objets (format attendu)
        if (conversation.participants && Array.isArray(conversation.participants) &&
            conversation.participants.length > 0 && typeof conversation.participants[0] === 'object') {

            // Filtrer les participants valides qui ne sont pas l'utilisateur actuel
            const participantNames = conversation.participants
                .filter(p => p && p.full_name && p.user_id != currentUserId)
                .map(p => p.full_name);

            participantsText = participantNames.join(', ');
        }
        // Cas 2: Les participants sont déjà un tableau de chaînes (format alternatif)
        else if (conversation.participants && Array.isArray(conversation.participants) &&
            typeof conversation.participants[0] === 'string') {

            participantsText = conversation.participants.join(', ');
        }
        // Fallback au titre si pas de participants
        else {
            participantsText = conversation.titre || 'Conversation de groupe';
        }
    } catch (e) {
        console.warn('Erreur lors de la récupération des noms de participants:', e);
        participantsText = 'Participants...';
    }

    return participantsText;
}

/**
 * Charge une conversation spécifique et affiche ses messages
 */
function loadConversation(conversationId) {
    // Éviter le rechargement si la conversation est déjà active
    if (currentConversationId === conversationId && !isLoadingMessages) {
        // En mode mobile, masquer la sidebar
        if (mobileView) {
            document.querySelector('.messenger-sidebar').classList.add('hidden');
        }
        return;
    }

    currentConversationId = conversationId;
    lastMessageId = null;
    isLoadingMessages = true;

    // En mode mobile, masquer la sidebar
    if (mobileView) {
        document.querySelector('.messenger-sidebar').classList.add('hidden');
    }

    // Marquer comme actif dans la liste
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeItem = document.querySelector(`.conversation-item[data-id="${conversationId}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
        activeItem.classList.remove('unread');
    }

    // Afficher le conteneur de chat actif et masquer l'état vide
    document.getElementById('emptyChat').style.display = 'none';
    document.getElementById('activeChat').style.display = 'flex';

    // Afficher un indicateur de chargement
    document.getElementById('messagesContainer').innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Chargement des messages...</p>
        </div>
    `;

    // Charger les messages
    fetch(`/messagerie/api/get_messages.php?conversation_id=${conversationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des messages');
            }
            return response.json();
        })
        .then(data => {
            isLoadingMessages = false;

            if (data.success) {
                // Mettre à jour les informations de la conversation
                updateConversationHeader(data.conversation);

                // Afficher les messages
                displayMessages(data.messages);

                // Mettre à jour le dernier ID de message
                if (data.messages.length > 0) {
                    lastMessageId = Math.max(...data.messages.map(m => m.id));
                }
            } else {
                showError('Erreur', data.message || 'Erreur lors du chargement des messages');
            }
        })
        .catch(error => {
            isLoadingMessages = false;
            console.error('Erreur de chargement des messages:', error);
            document.getElementById('messagesContainer').innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Impossible de charger les messages
                </div>
            `;
        });
}

/**
 * Met à jour l'en-tête de la conversation active
 */
function updateConversationHeader(conversation) {
    if (!conversation) return;

    document.getElementById('activeChatTitle').textContent = conversation.titre;

    // Formater les participants
    let participantsText = '';
    if (conversation.participants && conversation.participants.length > 0) {
        // Maximum 3 noms, puis "et X autres"
        const names = conversation.participants.map(p => p.full_name);
        if (names.length <= 3) {
            participantsText = names.join(', ');
        } else {
            participantsText = `${names.slice(0, 3).join(', ')} et ${names.length - 3} autres`;
        }
    }

    document.getElementById('activeChatParticipants').textContent = participantsText;
}

/**
 * Affiche les messages dans le conteneur
 */
function displayMessages(messages, append = false) {
    const messagesContainer = document.getElementById('messagesContainer');

    if (messages.length === 0 && !append) {
        messagesContainer.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p>Pas encore de messages. Soyez le premier à écrire !</p>
            </div>
        `;
        return;
    }

    // Si c'est un chargement initial (non un append), ajouter le bouton "Charger plus"
    if (!append) {
        messagesContainer.innerHTML = hasMoreMessages ? `
            <div class="load-more-messages">
                <button id="loadMoreMessagesBtn">
                    <i class="fas fa-arrow-up me-2"></i>Charger les messages précédents
                </button>
            </div>
        ` : '';

        // Ajouter l'événement de chargement de messages supplémentaires
        setTimeout(() => {
            const loadMoreBtn = document.getElementById('loadMoreMessagesBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMoreMessages);
            }
        }, 100);
    }

    let html = '';
    let currentDate = '';

    messages.forEach(message => {
        // Ajouter un séparateur de date si nécessaire
        const messageDate = new Date(message.date_envoi).toLocaleDateString();

        if (messageDate !== currentDate) {
            currentDate = messageDate;
            html += `
                <div class="message-date-separator">
                    <span>${formatDateSeparator(message.date_envoi)}</span>
                </div>
            `;
        }

        // Générer le contenu du message
        const isMine = message.is_mine;
        let avatarContent = '';

        if (!isMine && message.sender_name) {
            avatarContent = message.sender_name.charAt(0).toUpperCase();
        }

        // Construire le contenu du message
        let messageContent = '';

        if (message.type === 'image' && message.attachments && message.attachments.length > 0) {
            // Message avec images
            messageContent += '<div class="message-attachments">';
            message.attachments.forEach(attachment => {
                if (attachment.est_image) {
                    messageContent += `
                        <div class="attachment-preview">
                            <a href="${attachment.file_path}" target="_blank" data-lightbox="msg-${message.id}">
                                <img src="${attachment.thumbnail_path || attachment.file_path}" alt="${attachment.file_name}">
                            </a>
                        </div>
                    `;
                }
            });
            messageContent += '</div>';

            // Ajouter le texte s'il y en a
            if (message.contenu && message.contenu.trim() !== '') {
                messageContent += `<p class="message-text">${formatMessageText(message.contenu)}</p>`;
            }
        } else if (message.type === 'file' && message.attachments && message.attachments.length > 0) {
            // Message avec fichiers
            messageContent += '<div class="message-attachments">';
            message.attachments.forEach(attachment => {
                messageContent += `
                    <div class="attachment-file">
                        <div class="attachment-icon">
                            <i class="${getFileIcon(attachment.file_type)}"></i>
                        </div>
                        <div class="attachment-info">
                            <div class="attachment-name">${attachment.file_name}</div>
                            <div class="attachment-meta">${formatFileSize(attachment.file_size)}</div>
                        </div>
                        <a href="${attachment.file_path}" download="${attachment.file_name}" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                `;
            });
            messageContent += '</div>';

            // Ajouter le texte s'il y en a
            if (message.contenu && message.contenu.trim() !== '') {
                messageContent += `<p class="message-text">${formatMessageText(message.contenu)}</p>`;
            }
        } else {
            // Message texte standard
            messageContent = `<p class="message-text">${formatMessageText(message.contenu)}</p>`;
        }

        // --- Logique Message Administratif (Signature) ---
        let adminClasses = '';
        let adminContent = '';

        if (message.requires_signature == 1) {
            adminClasses = ' administrative-message';
            messageContent = `
                <div class="admin-badge mb-2">
                    <span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> IMPORTANT</span>
                    <span class="text-danger ms-2 small fw-bold">Signature requise</span>
                </div>
                ${messageContent}
            `;

            if (message.is_signed) {
                // Déjà signé
                let signDate = message.signed_at ? formatDateSeparator(message.signed_at) : '';
                adminContent = `
                    <div class="signature-status signed mt-2 pt-2 border-top border-danger-subtle">
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="fas fa-check-circle me-1"></i> Signé ${signDate}
                        </span>
                    </div>`;
            } else if (!message.is_mine) {
                // Pas encore signé et je suis le destinataire
                adminContent = `
                    <div class="signature-status pending mt-3 pt-2 border-top border-danger-subtle text-center">
                        <button class="btn btn-danger btn-sm w-100 fw-bold shadow-sm pulsing-btn" 
                                onclick="openSignatureModal(${message.id})">
                            <i class="fas fa-file-signature me-2"></i>Je confirme et je signe
                        </button>
                    </div>`;
            } else {
                // Je suis l'émetteur (Admin)
                adminContent = `
                    <div class="mt-2 pt-1 border-top border-secondary-subtle text-muted small fst-italic">
                         En attente de signature...
                    </div>`;
            }
        }
        // -------------------------------------

        // Construire le statut du message
        let messageStatus = '';
        if (isMine) {
            if (message.read_by_all) {
                messageStatus = '<i class="fas fa-check-double text-primary"></i>';
            } else if (message.read_by_anyone) {
                messageStatus = '<i class="fas fa-check-double"></i>';
            } else {
                messageStatus = '<i class="fas fa-check"></i>';
            }
        }

        // Construire les réactions
        let reactionsHtml = '';
        if (message.reactions && message.reactions.length > 0) {
            reactionsHtml = '<div class="message-reactions">';
            const reactionCounts = {};

            message.reactions.forEach(reaction => {
                if (!reactionCounts[reaction.reaction]) {
                    reactionCounts[reaction.reaction] = {
                        count: 0,
                        users: [],
                        reacted_by_me: false
                    };
                }

                reactionCounts[reaction.reaction].count++;
                reactionCounts[reaction.reaction].users.push(reaction.user_name || 'Utilisateur');

                if (reaction.user_id === currentUserId) {
                    reactionCounts[reaction.reaction].reacted_by_me = true;
                }
            });

            for (const [emoji, data] of Object.entries(reactionCounts)) {
                const usersList = data.users.join(', ');
                reactionsHtml += `
                    <button class="message-reaction ${data.reacted_by_me ? 'reacted' : ''}" 
                            data-reaction="${emoji}" 
                            data-message-id="${message.id}"
                            title="${usersList}">
                        ${emoji} <span class="reaction-count">${data.count}</span>
                    </button>
                `;
            }

            reactionsHtml += '</div>';
        }

        // Construire l'élément de message
        html += `
            <div class="message ${isMine ? 'mine' : ''}" data-id="${message.id}">
                <div class="message-avatar">${avatarContent}</div>
                <div class="message-content">
                    <div class="message-bubble">
                        ${messageContent}
                        ${adminContent}
                        ${reactionsHtml}
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${message.formatted_date}</span>
                        <span class="message-status">${messageStatus}</span>
                        <div class="message-actions">
                            <button class="btn-reaction" data-message-id="${message.id}" title="Ajouter une réaction">
                                <i class="far fa-smile"></i>
                            </button>
                            ${isMine ? `
                                <button class="btn-edit" data-message-id="${message.id}" title="Modifier">
                                    <i class="far fa-edit"></i>
                                </button>
                            ` : ''}
                            <button class="btn-reply" data-message-id="${message.id}" title="Répondre">
                                <i class="fas fa-reply"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    // Ajouter l'indicateur de frappe si quelqu'un est en train d'écrire
    const typingUserIds = Object.keys(typingUsers);
    if (typingUserIds.length > 0) {
        const typingUserNames = typingUserIds.map(id => typingUsers[id].name);
        let typingText = '';

        if (typingUserNames.length === 1) {
            typingText = `${typingUserNames[0]} est en train d'écrire...`;
        } else if (typingUserNames.length === 2) {
            typingText = `${typingUserNames[0]} et ${typingUserNames[1]} sont en train d'écrire...`;
        } else {
            typingText = `${typingUserNames.length} personnes sont en train d'écrire...`;
        }

        html += `
            <div class="typing-indicator">
                <div class="message-avatar">${typingUserNames[0].charAt(0).toUpperCase()}</div>
                <div class="typing-dots" title="${typingText}">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        `;
    }

    // Ajouter les messages au conteneur
    if (append) {
        // Si c'est un append, ajouter à la fin
        messagesContainer.insertAdjacentHTML('beforeend', html);
    } else {
        // Sinon, remplacer le contenu existant après le bouton "Charger plus"
        const loadMoreMessagesBtn = document.querySelector('.load-more-messages');
        if (loadMoreMessagesBtn) {
            loadMoreMessagesBtn.insertAdjacentHTML('afterend', html);
        } else {
            messagesContainer.innerHTML = html;
        }
    }

    // Initialiser les événements de réaction
    document.querySelectorAll('.btn-reaction').forEach(btn => {
        btn.addEventListener('click', function () {
            showReactionPicker(this);
        });
    });

    document.querySelectorAll('.message-reaction').forEach(btn => {
        btn.addEventListener('click', function () {
            toggleReaction(this.dataset.messageId, this.dataset.reaction);
        });
    });

    // Initialiser les événements d'édition
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            editMessage(this.dataset.messageId);
        });
    });

    // Initialiser les événements de réponse
    document.querySelectorAll('.btn-reply').forEach(btn => {
        btn.addEventListener('click', function () {
            replyToMessage(this.dataset.messageId);
        });
    });

    // Faire défiler jusqu'au dernier message
    if (!append || messages.length > 0) {
        scrollToLatestMessage();
    }
}

/**
 * Formate le texte du message en détectant les URLs et emojis
 */
function formatMessageText(text) {
    if (!text) return '';

    // Échapper le HTML
    let formattedText = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Détecter les URLs et les transformer en liens
    formattedText = formattedText.replace(
        /(https?:\/\/[^\s]+)/g,
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
    );

    // Détecter les emojis et les transformer en images
    formattedText = formattedText.replace(
        /([\u{1F300}-\u{1F6FF}\u{1F900}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}])/gu,
        '<span class="emoji">$1</span>'
    );

    return formattedText;
}

/**
 * Charge plus de messages (pagination)
 */
function loadMoreMessages() {
    if (isLoadingMessages || !hasMoreMessages) return;

    isLoadingMessages = true;
    messagesPage++;

    const loadMoreBtn = document.getElementById('loadMoreMessagesBtn');
    if (loadMoreBtn) {
        loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Chargement...';
        loadMoreBtn.disabled = true;
    }

    fetch(`/messagerie/api/get_messages.php?conversation_id=${currentConversationId}&page=${messagesPage}`)
        .then(response => response.json())
        .then(data => {
            isLoadingMessages = false;

            if (data.success) {
                if (data.messages && data.messages.length > 0) {
                    // Ajouter les messages avant les messages existants
                    const messagesContainer = document.getElementById('messagesContainer');
                    let html = '';
                    let currentDate = '';

                    data.messages.forEach(message => {
                        // Ajouter un séparateur de date si nécessaire
                        const messageDate = new Date(message.date_envoi).toLocaleDateString();

                        if (messageDate !== currentDate) {
                            currentDate = messageDate;
                            html += `
                                <div class="message-date-separator">
                                    <span>${formatDateSeparator(message.date_envoi)}</span>
                                </div>
                            `;
                        }

                        // Générer le contenu du message
                        const isMine = message.is_mine;
                        let avatarContent = '';

                        if (!isMine && message.sender_name) {
                            avatarContent = message.sender_name.charAt(0).toUpperCase();
                        }

                        // Construire le contenu du message
                        let messageContent = '';

                        if (message.type === 'image' && message.attachments && message.attachments.length > 0) {
                            // Message avec images
                            messageContent += '<div class="message-attachments">';
                            message.attachments.forEach(attachment => {
                                if (attachment.est_image) {
                                    messageContent += `
                                        <div class="attachment-preview">
                                            <a href="${attachment.file_path}" target="_blank" data-lightbox="msg-${message.id}">
                                                <img src="${attachment.thumbnail_path || attachment.file_path}" alt="${attachment.file_name}">
                                            </a>
                                        </div>
                                    `;
                                }
                            });
                            messageContent += '</div>';

                            if (message.contenu && message.contenu.trim() !== '') {
                                messageContent += `<p class="message-text">${formatMessageText(message.contenu)}</p>`;
                            }
                        } else if (message.type === 'file' && message.attachments && message.attachments.length > 0) {
                            // Message avec fichiers
                            messageContent += '<div class="message-attachments">';
                            message.attachments.forEach(attachment => {
                                messageContent += `
                                    <div class="attachment-file">
                                        <div class="attachment-icon">
                                            <i class="${getFileIcon(attachment.file_type)}"></i>
                                        </div>
                                        <div class="attachment-info">
                                            <div class="attachment-name">${attachment.file_name}</div>
                                            <div class="attachment-meta">${formatFileSize(attachment.file_size)}</div>
                                        </div>
                                        <a href="${attachment.file_path}" download="${attachment.file_name}" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                `;
                            });
                            messageContent += '</div>';

                            if (message.contenu && message.contenu.trim() !== '') {
                                messageContent += `<p class="message-text">${formatMessageText(message.contenu)}</p>`;
                            }
                        } else {
                            // Message texte standard
                            messageContent = `<p class="message-text">${formatMessageText(message.contenu)}</p>`;
                        }

                        // --- Logique Message Administratif (Signature) ---
                        let adminClasses = '';
                        let adminContent = '';

                        if (message.requires_signature == 1) {
                            adminClasses = ' administrative-message';
                            // DEBUG INFO IN UI
                            const debugInfo = `(Debug: mine=${isMine}, signed=${message.is_signed})`;

                            messageContent = `
                                <div class="admin-badge mb-2">
                                    <span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> IMPORTANT</span>
                                    <span class="text-danger ms-2 small fw-bold">Signature requise <small class="text-muted" style="font-size:0.6em">${debugInfo}</small></span>
                                </div>
                                ${messageContent}
                            `;

                            if (message.is_signed) {
                                let signDate = message.signed_at ? formatDateSeparator(message.signed_at) : '';
                                adminContent = `
                                    <div class="signature-status signed mt-2 pt-2 border-top border-danger-subtle">
                                        <span class="badge bg-success-subtle text-success border border-success">
                                            <i class="fas fa-check-circle me-1"></i> Signé ${signDate}
                                        </span>
                                    </div>`;
                            } else if (!message.is_mine) {
                                adminContent = `
                                    <div class="signature-status pending mt-3 pt-2 border-top border-danger-subtle text-center">
                                        <button class="btn btn-danger btn-sm w-100 fw-bold shadow-sm pulsing-btn" 
                                                onclick="openSignatureModal(${message.id})">
                                            <i class="fas fa-file-signature me-2"></i>Je confirme et je signe
                                        </button>
                                    </div>`;
                            } else {
                                adminContent = `
                                    <div class="mt-2 pt-1 border-top border-secondary-subtle text-muted small fst-italic">
                                         En attente de signature...
                                    </div>`;
                            }
                        }

                        // Construire le statut du message
                        let messageStatus = '';
                        if (isMine) {
                            if (message.read_by_all) {
                                messageStatus = '<i class="fas fa-check-double text-primary"></i>';
                            } else if (message.read_by_anyone) {
                                messageStatus = '<i class="fas fa-check-double"></i>';
                            } else {
                                messageStatus = '<i class="fas fa-check"></i>';
                            }
                        }

                        html += `
                            <div class="message-wrapper ${isMine ? 'mine' : 'theirs'}">
                                ${!isMine ? `<div class="message-avatar">${avatarContent}</div>` : ''}
                                <div class="message ${isMine ? 'mine' : 'theirs'} ${adminClasses}" data-message-id="${message.id}">
                                    ${!isMine && message.sender_name && message.type !== 'direct' ? `<div class="sender-name">${message.sender_name}</div>` : ''}
                                    
                                    ${messageContent}
                                    ${adminContent}
                                    
                                    <div class="message-meta">
                                        <span class="message-time">${new Date(message.date_envoi).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                        ${isMine ? `<span class="message-status">${messageStatus}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    // Insérer les messages avant les messages existants
                    const firstMessage = messagesContainer.querySelector('.message, .message-date-separator');
                    if (firstMessage) {
                        firstMessage.insertAdjacentHTML('beforebegin', html);
                    }

                    // Mettre à jour le bouton "Charger plus"
                    if (loadMoreBtn) {
                        loadMoreBtn.innerHTML = '<i class="fas fa-arrow-up me-2"></i>Charger les messages précédents';
                        loadMoreBtn.disabled = false;
                    }

                    // Initialiser les événements pour les nouveaux messages
                    // [...]
                } else {
                    // Plus de messages à charger
                    hasMoreMessages = false;

                    if (loadMoreBtn) {
                        loadMoreBtn.parentNode.remove();
                    }
                }
            } else {
                // Gérer l'erreur
                if (loadMoreBtn) {
                    loadMoreBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Erreur';
                    loadMoreBtn.disabled = false;
                }

                console.error('Erreur lors du chargement des messages:', data.message);
            }
        })
        .catch(error => {
            isLoadingMessages = false;

            if (loadMoreBtn) {
                loadMoreBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Erreur';
                loadMoreBtn.disabled = false;
            }

            console.error('Erreur lors du chargement des messages:', error);
        });
}

/**
 * Envoie un message dans la conversation active
 */
function sendMessage() {
    if (!currentConversationId) {
        console.error('Aucune conversation active');
        return;
    }

    const messageInput = document.getElementById('messageInput');
    const contenu = messageInput.value.trim();

    // Vérifier qu'il y a du contenu ou des pièces jointes
    if (contenu === '' && attachments.length === 0) return;

    console.log('Envoi de message dans la conversation:', currentConversationId);

    // Créer les données du formulaire
    const formData = new FormData();
    formData.append('conversation_id', currentConversationId);
    formData.append('contenu', contenu);

    // Ajouter l'option de signature si active
    const requiresSignature = document.getElementById('signatureRequestBtn')?.dataset.active === '1' ? 1 : 0;
    formData.append('requires_signature', requiresSignature);

    // Ajouter les pièces jointes
    if (attachments.length > 0) {
        attachments.forEach((file, index) => {
            formData.append(`attachment[${index}]`, file);
        });
    }

    // Désactiver le formulaire pendant l'envoi
    messageInput.disabled = true;
    const sendButton = document.querySelector('.send-btn');
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Envoyer le message
    fetch('/messagerie/api/send_message.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Erreur lors de l\'envoi du message');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Résultat envoi message:', data);

            if (data.success) {
                // Réinitialiser le formulaire
                messageInput.value = '';
                messageInput.style.height = 'auto';

                // Vider les pièces jointes
                attachments = [];
                document.getElementById('composerAttachments').innerHTML = '';

                // Actualiser les messages
                setTimeout(() => {
                    refreshMessages();
                }, 500);
            } else {
                showError('Erreur d\'envoi', data.message || 'Impossible d\'envoyer le message');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi du message:', error);
            showError('Erreur d\'envoi', error.message || 'Une erreur s\'est produite lors de l\'envoi du message');
        })
        .finally(() => {
            // Réactiver le formulaire
            messageInput.disabled = false;
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });

    // Envoyer un événement de fin de frappe
    sendTypingEvent(false);
}

/**
 * Envoie un événement de frappe aux autres participants
 */
function sendTypingEvent(isTyping) {
    if (!currentConversationId) return;

    // Ne pas envoyer l'événement si l'état n'a pas changé
    if (this.isTyping === isTyping) return;
    this.isTyping = isTyping;

    // Envoyer l'événement via l'API
    fetch('/messagerie/api/typing_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            conversation_id: currentConversationId,
            is_typing: isTyping
        })
    }).catch(error => {
        console.error('Erreur lors de l\'envoi de l\'événement de frappe:', error);
    });
}

/**
 * Initialise l'écouteur d'événements de frappe
 */
function initTypingEventListener() {
    const messageInput = document.getElementById('messageInput');

    messageInput.addEventListener('input', function () {
        // Envoyer l'événement de frappe
        if (messageInput.value.trim().length > 0) {
            // Annuler le timeout précédent
            if (typingTimeout) {
                clearTimeout(typingTimeout);
            }

            // Envoyer l'événement de frappe
            sendTypingEvent(true);

            // Programmer l'envoi de l'événement de fin de frappe après 3 secondes
            typingTimeout = setTimeout(() => {
                sendTypingEvent(false);
            }, 3000);
        } else {
            // Si le champ est vide, envoyer l'événement de fin de frappe
            sendTypingEvent(false);

            if (typingTimeout) {
                clearTimeout(typingTimeout);
            }
        }
    });
}

/**
 * Affiche le sélecteur de réactions
 */
function showReactionPicker(button) {
    const messageId = button.dataset.messageId;

    // Créer le menu de réactions s'il n'existe pas déjà
    let reactionMenu = document.getElementById('reactionMenu');

    if (!reactionMenu) {
        reactionMenu = document.createElement('div');
        reactionMenu.id = 'reactionMenu';
        reactionMenu.className = 'reaction-menu';
        reactionMenu.innerHTML = `
            <div class="reaction-menu-content">
                <button data-reaction="👍" class="reaction-btn">👍</button>
                <button data-reaction="❤️" class="reaction-btn">❤️</button>
                <button data-reaction="😂" class="reaction-btn">😂</button>
                <button data-reaction="😮" class="reaction-btn">😮</button>
                <button data-reaction="😢" class="reaction-btn">😢</button>
                <button data-reaction="😡" class="reaction-btn">😡</button>
                <button data-reaction="🎉" class="reaction-btn">🎉</button>
                <button data-reaction="👏" class="reaction-btn">👏</button>
            </div>
        `;

        document.body.appendChild(reactionMenu);

        // Ajouter les gestionnaires d'événements aux boutons de réaction
        reactionMenu.querySelectorAll('.reaction-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const reaction = this.dataset.reaction;
                const currentMessageId = reactionMenu.dataset.messageId;

                toggleReaction(currentMessageId, reaction);
                hideReactionMenu();
            });
        });

        // Masquer le menu lorsqu'on clique ailleurs
        document.addEventListener('click', function (e) {
            if (!reactionMenu.contains(e.target) && !e.target.matches('.btn-reaction')) {
                hideReactionMenu();
            }
        });
    }

    // Positionner le menu près du bouton
    const rect = button.getBoundingClientRect();
    reactionMenu.style.top = (rect.top - 50) + 'px';
    reactionMenu.style.left = (rect.left - 100) + 'px';

    // Stocker l'ID du message
    reactionMenu.dataset.messageId = messageId;

    // Afficher le menu
    reactionMenu.classList.add('active');
}

/**
 * Masque le menu de réactions
 */
function hideReactionMenu() {
    const reactionMenu = document.getElementById('reactionMenu');
    if (reactionMenu) {
        reactionMenu.classList.remove('active');
    }
}

/**
 * Ajoute ou supprime une réaction à un message
 */
function toggleReaction(messageId, reaction) {
    if (!messageId || !reaction) return;

    fetch('/messagerie/api/toggle_reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            message_id: messageId,
            reaction: reaction
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Rafraîchir la conversation pour voir la réaction mise à jour
                refreshMessages();
            } else {
                console.error('Erreur lors de la réaction:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la réaction:', error);
        });
}

/**
 * Édite un message
 */
function editMessage(messageId) {
    // Trouver le message
    const messageElement = document.querySelector(`.message[data-id="${messageId}"]`);
    if (!messageElement) return;

    // Récupérer le contenu du message
    const messageTextElement = messageElement.querySelector('.message-text');
    if (!messageTextElement) return;

    const originalText = messageTextElement.innerText;

    // Créer un champ de saisie pour éditer le message
    const editInput = document.createElement('textarea');
    editInput.className = 'form-control edit-message-input';
    editInput.value = originalText;

    // Créer les boutons d'action
    const editActions = document.createElement('div');
    editActions.className = 'edit-message-actions';
    editActions.innerHTML = `
        <button class="btn btn-sm btn-danger cancel-edit">Annuler</button>
        <button class="btn btn-sm btn-primary save-edit">Enregistrer</button>
    `;

    // Remplacer le texte par l'éditeur
    messageTextElement.style.display = 'none';
    messageTextElement.parentNode.insertBefore(editInput, messageTextElement.nextSibling);
    messageTextElement.parentNode.insertBefore(editActions, editInput.nextSibling);

    // Focus sur le champ de saisie
    editInput.focus();

    // Gestionnaire pour annuler l'édition
    editActions.querySelector('.cancel-edit').addEventListener('click', function () {
        editInput.remove();
        editActions.remove();
        messageTextElement.style.display = '';
    });

    // Gestionnaire pour enregistrer l'édition
    editActions.querySelector('.save-edit').addEventListener('click', function () {
        const newText = editInput.value.trim();

        if (newText !== '' && newText !== originalText) {
            // Envoyer la modification à l'API
            fetch('/messagerie/api/edit_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message_id: messageId,
                    content: newText
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour l'affichage
                        messageTextElement.innerHTML = formatMessageText(newText);

                        // Ajouter une indication d'édition
                        if (!messageElement.querySelector('.message-edited-indicator')) {
                            const messageMetaElement = messageElement.querySelector('.message-meta');
                            if (messageMetaElement) {
                                const editedIndicator = document.createElement('span');
                                editedIndicator.className = 'message-edited-indicator';
                                editedIndicator.innerHTML = ' • Modifié';
                                messageMetaElement.querySelector('.message-time').appendChild(editedIndicator);
                            }
                        }
                    } else {
                        showError('Erreur', data.message || 'Impossible de modifier le message');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la modification du message:', error);
                    showError('Erreur', 'Une erreur est survenue lors de la modification du message');
                })
                .finally(() => {
                    // Nettoyer l'interface
                    editInput.remove();
                    editActions.remove();
                    messageTextElement.style.display = '';
                });
        } else {
            // Si pas de changement ou texte vide, annuler l'édition
            editInput.remove();
            editActions.remove();
            messageTextElement.style.display = '';
        }
    });
}

/**
 * Répond à un message
 */
function replyToMessage(messageId) {
    // Trouver le message
    const messageElement = document.querySelector(`.message[data-id="${messageId}"]`);
    if (!messageElement) return;

    // Récupérer le contenu du message
    const messageTextElement = messageElement.querySelector('.message-text');
    if (!messageTextElement) return;

    const messageText = messageTextElement.innerText;
    const senderName = messageElement.classList.contains('mine') ? 'vous' : messageElement.querySelector('.message-avatar').textContent;

    // Créer ou mettre à jour la zone de réponse
    let replyContainer = document.getElementById('replyContainer');

    if (!replyContainer) {
        replyContainer = document.createElement('div');
        replyContainer.id = 'replyContainer';
        replyContainer.className = 'reply-container';

        // Insérer avant la zone de composition de message
        const composerMain = document.querySelector('.composer-main');
        composerMain.parentNode.insertBefore(replyContainer, composerMain);
    }

    // Mettre à jour le contenu
    replyContainer.innerHTML = `
        <div class="reply-content">
            <div class="reply-info">
                <i class="fas fa-reply me-2"></i>
                Réponse à <strong>${senderName}</strong>
            </div>
            <div class="reply-text">${messageText.substring(0, 50)}${messageText.length > 50 ? '...' : ''}</div>
        </div>
        <button class="btn-cancel-reply" title="Annuler la réponse">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Stocker l'ID du message auquel on répond
    replyContainer.dataset.messageId = messageId;

    // Ajouter le gestionnaire pour annuler la réponse
    replyContainer.querySelector('.btn-cancel-reply').addEventListener('click', function () {
        replyContainer.remove();
    });

    // Focus sur le champ de saisie
    document.getElementById('messageInput').focus();
}

/**
 * Rafraîchit les données de la messagerie
 */
function refreshData() {
    // Actualiser les messages de la conversation active
    if (currentConversationId) {
        refreshMessages();
    }

    // Actualiser la liste des conversations
    refreshConversations();
}

/**
 * Rafraîchit uniquement les messages de la conversation active
 */
function refreshMessages() {
    if (!currentConversationId || isLoadingMessages) return;

    // Si nous avons déjà des messages, ne récupérer que les nouveaux
    if (lastMessageId) {
        fetch(`/messagerie/api/get_new_messages.php?conversation_id=${currentConversationId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    // Ajouter les nouveaux messages
                    appendNewMessages(data.messages);

                    // Mettre à jour le dernier ID
                    lastMessageId = Math.max(...data.messages.map(m => m.id), lastMessageId);
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'actualisation des messages:', error);
            });
    } else {
        // Si nous n'avons pas encore de messages, charger toute la conversation
        loadConversation(currentConversationId);
    }
}

/**
 * Ajoute de nouveaux messages au conteneur existant
 */
function appendNewMessages(messages) {
    if (!messages || messages.length === 0) return;

    const messagesContainer = document.getElementById('messagesContainer');
    let currentDateElements = messagesContainer.querySelectorAll('.message-date-separator');
    let lastDateElement = currentDateElements[currentDateElements.length - 1];
    let currentDate = lastDateElement ? lastDateElement.querySelector('span').textContent : '';

    let html = '';

    messages.forEach(message => {
        // Ajouter un séparateur de date si nécessaire
        const messageDate = formatDateSeparator(message.date_envoi);

        if (messageDate !== currentDate) {
            currentDate = messageDate;
            html += `
                <div class="message-date-separator">
                    <span>${messageDate}</span>
                </div>
            `;
        }

        // Générer le contenu du message
        const isMine = message.is_mine;
        let avatarContent = '';

        if (!isMine && message.sender_name) {
            avatarContent = message.sender_name.charAt(0).toUpperCase();
        }

        // Construire le contenu du message
        let messageContent = '';

        if (message.type === 'image' && message.attachments && message.attachments.length > 0) {
            // Message avec images
            messageContent += '<div class="message-attachments">';
            message.attachments.forEach(attachment => {
                if (attachment.est_image) {
                    messageContent += `
                        <div class="attachment-preview">
                            <a href="${attachment.file_path}" target="_blank">
                                <img src="${attachment.thumbnail_path || attachment.file_path}" alt="${attachment.file_name}">
                            </a>
                        </div>
                    `;
                }
            });
            messageContent += '</div>';

            // Ajouter le texte s'il y en a
            if (message.contenu && message.contenu.trim() !== '') {
                messageContent += `<p class="message-text">${message.contenu}</p>`;
            }
        } else if (message.type === 'file' && message.attachments && message.attachments.length > 0) {
            // Message avec fichiers
            messageContent += '<div class="message-attachments">';
            message.attachments.forEach(attachment => {
                messageContent += `
                    <div class="attachment-file">
                        <div class="attachment-icon">
                            <i class="${getFileIcon(attachment.file_type)}"></i>
                        </div>
                        <div class="attachment-info">
                            <div class="attachment-name">${attachment.file_name}</div>
                            <div class="attachment-meta">${formatFileSize(attachment.file_size)}</div>
                        </div>
                        <a href="${attachment.file_path}" download="${attachment.file_name}" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                `;
            });
            messageContent += '</div>';

            // Ajouter le texte s'il y en a
            if (message.contenu && message.contenu.trim() !== '') {
                messageContent += `<p class="message-text">${message.contenu}</p>`;
            }
        } else {
            // Message texte standard
            messageContent = `<p class="message-text">${message.contenu}</p>`;
        }

        // --- Logique Message Administratif ---
        let adminClasses = '';
        let adminContent = '';

        if (message.requires_signature == 1) {
            adminClasses = ' administrative-message';
            messageContent = `
                <div class="admin-badge mb-2">
                    <span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> IMPORTANT</span>
                    <span class="text-danger ms-2 small fw-bold">Signature requise</span>
                </div>
                ${messageContent}
            `;

            if (message.is_signed) {
                // Déjà signé
                let signDate = message.signed_at ? formatDateSeparator(message.signed_at) : '';
                adminContent = `
                    <div class="signature-status signed mt-2 pt-2 border-top border-danger-subtle">
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="fas fa-check-circle me-1"></i> Signé ${signDate}
                        </span>
                    </div>`;
            } else if (!message.is_mine) {
                // Pas encore signé et je suis le destinataire
                adminContent = `
                    <div class="signature-status pending mt-3 pt-2 border-top border-danger-subtle text-center">
                        <button class="btn btn-danger btn-sm w-100 fw-bold shadow-sm pulsing-btn" 
                                onclick="openSignatureModal(${message.id})">
                            <i class="fas fa-file-signature me-2"></i>Je confirme et je signe
                        </button>
                    </div>`;
            } else {
                // Je suis l'émetteur (Admin)
                adminContent = `
                    <div class="mt-2 pt-1 border-top border-secondary-subtle text-muted small fst-italic">
                         En attente de signature...
                    </div>`;
            }
        }
        // -------------------------------------

        // Construire le statut du message
        let messageStatus = '';
        if (isMine) {
            messageStatus = '<i class="fas fa-check-double"></i>';
        }

        // Construire l'élément de message
        html += `
            <div class="message ${isMine ? 'mine' : ''} ${adminClasses}" data-id="${message.id}">
                <div class="message-avatar">${avatarContent}</div>
                <div class="message-content">
                    <div class="message-bubble">
                        ${messageContent}
                        ${adminContent}
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${message.formatted_date}</span>
                        <span class="message-status">${messageStatus}</span>
                    </div>
                </div>
            </div>
        `;
    });

    // Ajouter les nouveaux messages
    messagesContainer.insertAdjacentHTML('beforeend', html);

    // Faire défiler jusqu'au dernier message
    scrollToLatestMessage();

    // Jouer un son de notification pour les nouveaux messages
    playNotificationSound();
}

/**
 * Rafraîchit la liste des conversations
 */
function refreshConversations() {
    // Récupérer le filtre actif
    const activeFilter = document.querySelector('.filter-btn.active');
    const filter = activeFilter ? activeFilter.dataset.filter : 'all';

    // Récupérer la recherche actuelle
    const searchValue = document.getElementById('searchInput').value.trim();

    fetch(`/messagerie/api/get_conversations.php${filter !== 'all' ? `?filter=${filter}` : ''}${searchValue ? `&search=${encodeURIComponent(searchValue)}` : ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversations(data.conversations);
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'actualisation des conversations:', error);
        });
}

/**
 * Ouvre la fenêtre modale pour créer une nouvelle conversation
 */
function openNewConversationModal() {
    console.log('Ouverture de la modale nouvelle conversation');

    // Vérifier si l'élément modal existe
    const modalElement = document.getElementById('newConversationModal');
    if (!modalElement) {
        console.error('Élément modal non trouvé: newConversationModal');
        alert('Erreur: La fenêtre modale n\'a pas été trouvée.');
        return;
    }

    // Méthode alternative pour ouvrir la modale sans utiliser l'objet bootstrap.Modal
    // Essayons d'abord avec jQuery si disponible
    if (typeof $ !== 'undefined') {
        console.log('Tentative d\'ouverture avec jQuery...');
        try {
            $('#newConversationModal').modal('show');

            // Préchargement des utilisateurs après ouverture de la modale
            loadUsersForNewConversation();
            return;
        } catch (error) {
            console.warn('Échec avec jQuery, tentative avec l\'API native de Bootstrap...');
        }
    }

    // Essayer avec l'API native de Bootstrap
    try {
        console.log('Tentative d\'ouverture avec l\'API native de Bootstrap...');
        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();

        // Préchargement des utilisateurs après ouverture de la modale
        loadUsersForNewConversation();
    } catch (error) {
        console.error('Erreur lors de l\'ouverture de la modale:', error);

        // Solution de repli : ajouter la classe show et les attributs nécessaires manuellement
        try {
            console.log('Tentative d\'ouverture manuelle...');
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            modalElement.setAttribute('aria-modal', 'true');
            modalElement.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');

            // Ajouter un backdrop manuellement
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);

            // Préchargement des utilisateurs après ouverture de la modale
            loadUsersForNewConversation();
        } catch (err) {
            console.error('Toutes les tentatives ont échoué:', err);
            alert('Impossible d\'ouvrir la fenêtre de création de conversation. Veuillez recharger la page et réessayer.');
        }
    }
}

/**
 * Charge les utilisateurs pour la nouvelle conversation
 */
function loadUsersForNewConversation() {
    console.log('Chargement des utilisateurs pour la nouvelle conversation...');

    // Charger les utilisateurs pour le sélecteur
    fetch('/messagerie/api/get_users.php')
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Erreur lors du chargement des utilisateurs');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Utilisateurs chargés:', data.users.length);
                const participantsSelect = document.getElementById('participants');
                participantsSelect.innerHTML = '';

                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    // Utiliser le full_name comme texte de l'option
                    option.textContent = user.full_name || user.username;
                    participantsSelect.appendChild(option);
                });

                // Initialiser le sélecteur avec Select2
                if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    try {
                        $(participantsSelect).select2({
                            theme: 'bootstrap-5',
                            placeholder: 'Choisissez les participants',
                            language: 'fr'
                        });
                    } catch (error) {
                        console.warn('Erreur lors de l\'initialisation de Select2:', error);
                    }
                } else {
                    console.warn('La bibliothèque Select2 n\'est pas disponible');
                }

                // Événement de soumission du formulaire
                document.getElementById('createConversationBtn').onclick = createConversation;
            } else {
                console.error('Erreur lors du chargement des utilisateurs:', data.message);
                showError('Erreur', data.message || 'Impossible de charger les utilisateurs');
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des utilisateurs:', error);
            showError('Erreur', error.message || 'Impossible de charger les utilisateurs');
        });
}

/**
 * Ouvre le modal de nouvelle conversation
 */
function openNewConversationModal() {
    // Réinitialiser le formulaire
    document.getElementById('newConversationForm').reset();
    $('#participants').val(null).trigger('change');

    // Réinitialiser les champs spécifiques
    document.getElementById('conversationTitle').value = '';
    document.getElementById('firstMessage').value = '';
    document.getElementById('conversationSubject').value = '';
    document.querySelector('input[name="conversationPriority"][value="normale"]').checked = true;

    // Gérer l'affichage des champs selon le type
    document.querySelectorAll('input[name="conversationType"]').forEach(input => {
        input.addEventListener('change', function () {
            const isGroup = this.value === 'groupe';
            const groupOnlyFields = document.querySelectorAll('.group-only');

            groupOnlyFields.forEach(field => {
                field.style.display = isGroup ? 'block' : 'none';
            });

            // Recharger les participants si nécessaire (pour exclure/inclure certains)
            loadUsersForSelect();
        });
    });

    // Déclencher le changement pour l'état initial
    document.querySelector('input[name="conversationType"]:checked').dispatchEvent(new Event('change'));

    // Charger la liste des utilisateurs
    loadUsersForSelect();

    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('newConversationModal'));
    modal.show();

    // Gestionnaire pour le bouton de création (une seule fois)
    const createBtn = document.getElementById('createConversationBtn');
    // Cloner pour supprimer les anciens écouteurs
    const newCreateBtn = createBtn.cloneNode(true);
    createBtn.parentNode.replaceChild(newCreateBtn, createBtn);

    newCreateBtn.addEventListener('click', createConversation);
}

/**
 * Crée une nouvelle conversation
 */
function createConversation() {
    const type = document.querySelector('input[name="conversationType"]:checked').value;
    const participants = $('#participants').val();
    const message = document.getElementById('firstMessage').value.trim();
    const objet = document.getElementById('conversationSubject').value.trim();
    const priorite = document.querySelector('input[name="conversationPriority"]:checked').value;
    let titre = '';

    if (type === 'groupe') {
        titre = document.getElementById('conversationTitle').value.trim();
        if (!titre) {
            showError('Erreur', 'Veuillez donner un titre au groupe');
            return;
        }
    }

    if (!participants || participants.length === 0) {
        showError('Erreur', 'Veuillez sélectionner au moins un participant');
        return;
    }

    // Désactiver le bouton
    const btn = document.getElementById('createConversationBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Création...';

    // Préparer les données
    const data = {
        type: type,
        participants: participants,
        titre: titre,
        first_message: message,
        objet: objet,
        priorite: priorite,
        requires_signature: document.getElementById('newConvSignature')?.checked ? 1 : 0
    };

    console.log('Envoi données création:', data);

    // Envoyer la requête
    fetch('/messagerie/api/create_conversation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                const modalEl = document.getElementById('newConversationModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();

                // Recharger la liste et ouvrir la conversation
                loadConversations();
                loadConversation(data.conversation_id);

                // Notification succès
                // showToast('Succès', 'Conversation créée avec succès');
            } else {
                showError('Erreur', data.message || 'Erreur lors de la création de la conversation');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur', 'Une erreur est survenue');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
}

/**
 * Fonctions utilitaires
 */

// Formater la date pour les séparateurs
function formatDateSeparator(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === now.toDateString()) {
        return 'Aujourd\'hui';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Hier';
    } else {
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    }
}

// Formater la taille d'un fichier
function formatFileSize(bytes) {
    if (bytes < 1024) {
        return bytes + ' octets';
    } else if (bytes < 1048576) {
        return Math.round(bytes / 1024) + ' Ko';
    } else {
        return Math.round(bytes / 1048576 * 10) / 10 + ' Mo';
    }
}

// Obtenir l'icône pour un type de fichier
function getFileIcon(fileType) {
    if (!fileType) return 'far fa-file';

    if (fileType.startsWith('image/')) {
        return 'far fa-file-image';
    } else if (fileType.startsWith('video/')) {
        return 'far fa-file-video';
    } else if (fileType.startsWith('audio/')) {
        return 'far fa-file-audio';
    } else if (fileType.includes('pdf')) {
        return 'far fa-file-pdf';
    } else if (fileType.includes('word') || fileType.includes('document')) {
        return 'far fa-file-word';
    } else if (fileType.includes('excel') || fileType.includes('spreadsheet')) {
        return 'far fa-file-excel';
    } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
        return 'far fa-file-powerpoint';
    } else if (fileType.includes('zip') || fileType.includes('compressed') || fileType.includes('archive')) {
        return 'far fa-file-archive';
    } else if (fileType.includes('text/')) {
        return 'far fa-file-alt';
    } else {
        return 'far fa-file';
    }
}

// Faire défiler jusqu'au dernier message
function scrollToLatestMessage() {
    const messagesContainer = document.getElementById('messagesContainer');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Ajuster automatiquement la hauteur du textarea
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

// Afficher un message d'erreur
function showError(title, message) {
    // Utiliser Bootstrap Toast ou une autre méthode pour afficher l'erreur
    console.error(`${title}: ${message}`);

    // Simple alerte pour l'instant
    alert(`${title}: ${message}`);
}

// Jouer un son de notification
function playNotificationSound() {
    // À implémenter si nécessaire
}

// Gérer le redimensionnement de la fenêtre
function handleWindowResize() {
    mobileView = window.innerWidth < 992;

    if (mobileView) {
        // Ajouter la classe hidden à la sidebar si une conversation est active
        if (currentConversationId) {
            document.querySelector('.messenger-sidebar').classList.add('hidden');
        }
    } else {
        // Retirer la classe hidden en mode desktop
        document.querySelector('.messenger-sidebar').classList.remove('hidden');
    }
}

// Filtrer les conversations
function filterConversations(filter) {
    // Mettre à jour l'état du bouton actif
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });

    // Charger les conversations filtrées
    loadConversations(filter);
}

// Basculer l'affichage de la barre de recherche
function toggleSearchBar() {
    const searchContainer = document.getElementById('searchContainer');
    searchContainer.classList.toggle('active');

    if (searchContainer.classList.contains('active')) {
        document.getElementById('searchInput').focus();
    } else {
        clearSearch();
    }
}

// Vider la recherche
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.value = '';

    // Recharger les conversations sans filtre de recherche
    const activeFilter = document.querySelector('.filter-btn.active');
    const filter = activeFilter ? activeFilter.dataset.filter : 'all';
    loadConversations(filter);
}

// Rechercher dans les conversations
function searchConversations(query) {
    if (!query) {
        // Si la requête est vide, recharger sans filtre
        const activeFilter = document.querySelector('.filter-btn.active');
        const filter = activeFilter ? activeFilter.dataset.filter : 'all';
        loadConversations(filter);
        return;
    }

    // Charger les conversations avec filtre de recherche
    const activeFilter = document.querySelector('.filter-btn.active');
    const filter = activeFilter ? activeFilter.dataset.filter : 'all';
    loadConversations(filter, query);
}

// Gérer la sélection de fichiers
function handleFileSelection(event) {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    // Ajouter les fichiers à la liste des pièces jointes
    for (let i = 0; i < files.length; i++) {
        attachments.push(files[i]);
    }

    // Afficher les aperçus
    updateAttachmentPreviews();
}

// Mettre à jour les aperçus des pièces jointes
function updateAttachmentPreviews() {
    const container = document.getElementById('composerAttachments');
    container.innerHTML = '';

    attachments.forEach((file, index) => {
        const isImage = file.type.startsWith('image/');
        const previewElement = document.createElement('div');
        previewElement.className = 'composer-attachment';

        if (isImage) {
            // Créer un aperçu d'image
            const reader = new FileReader();
            reader.onload = function (e) {
                previewElement.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <div class="composer-attachment-remove" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            // Afficher une icône pour les autres types de fichiers
            previewElement.innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100">
                    <i class="${getFileIcon(file.type)} fa-2x"></i>
                </div>
                <div class="composer-attachment-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </div>
            `;
        }

        container.appendChild(previewElement);
    });

    // Ajouter des événements pour supprimer les pièces jointes
    document.querySelectorAll('.composer-attachment-remove').forEach(btn => {
        btn.addEventListener('click', function () {
            const index = parseInt(this.dataset.index);
            attachments.splice(index, 1);
            updateAttachmentPreviews();
        });
    });
}

/**
 * Charge la liste des utilisateurs pour le sélecteur (alias pour compatibilité ou usage direct)
 */
function loadUsersForSelect() {
    console.log('Appel de loadUsersForSelect (via alias)');
    if (typeof loadUsersForNewConversation === 'function') {
        loadUsersForNewConversation();
    } else {
        console.error('Fonction loadUsersForNewConversation introuvable');
    }
}

// Fonctions non implémentées
function toggleEmojiPicker() {
    alert('Fonctionnalité non implémentée: sélecteur d\'emojis');
}

// Marquer toutes les conversations comme lues
function markAllConversationsAsRead() {
    if (confirm('Voulez-vous vraiment marquer toutes les conversations comme lues ?')) {
        fetch('/messagerie/api/mark_all_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    refreshConversations();
                    // Mettre à jour le badge de notifications global si nécessaire
                    if (typeof updateUnreadBadge === 'function') {
                        updateUnreadBadge();
                    }
                } else {
                    showError('Erreur', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showError('Erreur', 'Une erreur est survenue');
            });
    }
}

// Défiler vers le bas des messages
function scrollToBottom() {
    const messagesContainer = document.getElementById('messagesContainer');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Afficher les informations de la conversation
function showConversationInfo() {
    if (!currentConversationId) return;

    // Simuler un modal d'info ou utiliser un alert plus détaillé pour l'instant
    // Idéalement, on récupèrerait les infos via API mais on les a déjà partiellement dans l'UI
    const title = document.getElementById('activeChatTitle').innerText;
    const participants = document.getElementById('activeChatParticipants').innerText;

    alert(`Conversation: ${title}\n${participants}`);
}

// Basculer la recherche dans les messages
function searchInMessages() {
    // Si la fonction n'est pas encore implémentée côté serveur ou UI complexe
    // On peut utiliser la recherche globale filtrée sur cette conversation
    const searchQuery = prompt("Rechercher dans cette conversation :");
    if (searchQuery) {
        // Appeler une fonction de recherche spécifique ou filtrer visuellement
        console.log("Recherche de:", searchQuery);
        // À implémenter: appel API search_messages.php
    }
}

// Basculer le statut favori
function toggleFavorite() {
    if (!currentConversationId) return;
    // Appel API update_conversation_status.php avec action=favorite
    updateConversationStatus('favorite');
}

// Basculer les notifications
function toggleMute() {
    if (!currentConversationId) return;
    // Appel API update_conversation_status.php avec action=mute
    updateConversationStatus('mute');
}

// Archiver la conversation
function toggleArchive() {
    if (!currentConversationId) return;
    if (confirm('Voulez-vous vraiment archiver cette conversation ?')) {
        updateConversationStatus('archive');
    }
}

// Quitter la conversation
function leaveConversation() {
    if (!currentConversationId) return;
    if (confirm('Voulez-vous vraiment quitter cette conversation ?')) {
        fetch('/messagerie/api/leave_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: currentConversationId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger la page ou la liste
                    location.reload();
                } else {
                    showError('Erreur', data.message);
                }
            });
    }
}

// Fonction générique pour mettre à jour le statut
function updateConversationStatus(action) {
    fetch('/messagerie/api/update_conversation_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            conversation_id: currentConversationId,
            action: action
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshConversations(); // Recharger la liste pour voir les changements (icônes)
                // Optionnel: feedback visuel immédiat
            } else {
                showError('Erreur', data.message);
            }
        });
}


// Initialisation globale de Select2 au chargement
$(document).ready(function () {
    // Initialiser Select2 pour les participants si ce n'est pas déjà fait
    if (typeof $.fn.select2 !== 'undefined' && $('#participants').hasClass('select2-hidden-accessible') === false) {
        $('#participants').select2({
            theme: 'bootstrap-5',
            placeholder: 'Sélectionner des participants',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#newConversationModal')
        });
    }

    // Init admin tools
    if (typeof userRole !== 'undefined') {
        const role = userRole ? userRole.toLowerCase() : '';

        if (role === 'admin' || role === 'superadmin') {
            const sigBtn = document.getElementById('signatureRequestBtn');
            if (sigBtn) {
                sigBtn.classList.remove('d-none');

                sigBtn.addEventListener('click', function () {
                    const isActive = this.dataset.active === '1';
                    this.dataset.active = isActive ? '0' : '1';
                    this.classList.toggle('active', !isActive);
                    if (!isActive) {
                        this.style.color = '#dc3545'; // Rouge quand actif
                        this.innerHTML = '<i class="fas fa-file-signature"></i>';
                    } else {
                        this.style.color = '';
                        this.innerHTML = '<i class="fas fa-file-contract"></i>';
                    }
                });
            }
        }
    }


    // Init signature dashboard (admin)
    const viewSigBtn = document.getElementById('viewSignaturesBtn');
    if (viewSigBtn) {
        viewSigBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openSignaturesDashboard();
        });
    }

    // Init signature submit
    const submitSigBtn = document.getElementById('submitSignatureBtn');
    if (submitSigBtn) {
        submitSigBtn.addEventListener('click', submitSignature);
    }
});

/**
 * Ouvre le tableau de bord des signatures
 */
function openSignaturesDashboard() {
    const modalEl = document.getElementById('signaturesDashboardModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    loadSignatureDashboardData();
}

/**
 * Charge les données du tableau de bord
 */
function loadSignatureDashboardData() {
    const pendingList = document.getElementById('pendingList');
    const signedList = document.getElementById('signedList');

    // Loading state
    pendingList.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>';
    signedList.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>'; // status is implied text-success? No primary is fine.

    fetch('/messagerie/api/get_signature_dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSignatureList(pendingList, data.data.pending, 'pending');
                renderSignatureList(signedList, data.data.completed, 'completed');

                // Update badges
                document.getElementById('pendingCount').textContent = data.data.pending.length;
                document.getElementById('signedCount').textContent = data.data.completed.length;
            } else {
                pendingList.innerHTML = `<div class="alert alert-danger m-3">${data.message}</div>`;
                signedList.innerHTML = '';
            }
        })
        .catch(err => {
            console.error(err);
            pendingList.innerHTML = '<div class="alert alert-danger m-3">Erreur de chargement</div>';
        });
}

/**
 * Affiche une liste de demandes de signature
 */
function renderSignatureList(container, items, type) {
    if (items.length === 0) {
        container.innerHTML = `<div class="text-center p-4 text-muted font-italic">Aucun élément ${type === 'pending' ? 'en attente' : 'terminé'}.</div>`;
        return;
    }

    let html = '';
    items.forEach(item => {
        const percent = item.stats.total > 0 ? Math.round((item.stats.signed / item.stats.total) * 100) : 0;
        const color = type === 'pending' ? 'warning' : 'success';

        html += `
        <div class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">${item.conversation}</h6>
                <small class="text-muted"><i class="far fa-clock me-1"></i>${item.date}</small>
            </div>
            <p class="mb-1 small text-truncate" style="max-width: 90%; font-style: italic;">"${item.excerpt}"</p>
            <div class="mt-2 d-flex align-items-center">
                <div class="progress flex-grow-1" style="height: 6px;">
                    <div class="progress-bar bg-${color}" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="ms-2 fw-bold text-${color}">${item.stats.signed}/${item.stats.total}</small>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

/**
 * Ouvre la modale de signature (User side)
 */
function openSignatureModal(messageId) {
    document.getElementById('signatureMessageId').value = messageId;
    document.getElementById('signatureName').value = '';
    document.getElementById('confirmRead').checked = false;

    // Utiliser l'API Bootstrap pour ouvrir la modale
    const modalEl = document.getElementById('signatureModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

/**
 * Soumet la signature
 */
function submitSignature() {
    const messageId = document.getElementById('signatureMessageId').value;
    const name = document.getElementById('signatureName').value.trim();
    const confirmed = document.getElementById('confirmRead').checked;

    if (!confirmed) {
        alert('Vous devez confirmer avoir lu le message.');
        return;
    }

    if (name === '') {
        alert('Veuillez signer en tapant votre nom.');
        return;
    }

    // Désactiver le bouton
    const btn = document.getElementById('submitSignatureBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Validation...';

    fetch('/messagerie/api/sign_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message_id: messageId,
            signature_data: name
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer la modale
                const modalEl = document.getElementById('signatureModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();

                // Mise à jour visuelle immédiate du message
                const msgEl = document.querySelector(`.message[data-id="${messageId}"]`);
                if (msgEl) {
                    // Trouver le conteneur de statut signature s'il existe
                    let statusContainer = msgEl.querySelector('.signature-status');

                    // HTML du statut "Signé"
                    const signedHtml = `
                    <div class="signature-status signed mt-2 pt-2 border-top border-danger-subtle">
                        <span class="badge bg-success-subtle text-success border border-success">
                            <i class="fas fa-check-circle me-1"></i> Signé à l'instant
                        </span>
                    </div>`;

                    if (statusContainer) {
                        statusContainer.outerHTML = signedHtml;
                    } else {
                        // S'il n'existait pas (cas rare), l'ajouter
                        const bubble = msgEl.querySelector('.message-bubble');
                        if (bubble) bubble.insertAdjacentHTML('beforeend', signedHtml);
                    }
                }

                // Notification succès
                // alert('Message signé avec succès.'); 
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Une erreur est survenue lors de la signature.');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });
} 