/**
 * MODAL DE RECHERCHE MODERNE - JAVASCRIPT
 * Sans Bootstrap, avec onglets pour Réparations, Clients, Commandes et Tâches
 */

class RechercheModerne {
    constructor() {
        // console.log('🔍 Construction de RechercheModerne...');
        
        this.modal = document.getElementById('rechercheModalModerne');
        this.modalBox = this.modal ? this.modal.querySelector('.recherche-modal') : null;
        this.closeBtn = document.getElementById('rechercheModalClose');
        this.input = document.getElementById('rechercheInputModerne');
        this.searchBtn = document.getElementById('rechercheBtnModerne');
        this.loading = document.getElementById('rechercheLoading');
        this.tabs = document.getElementById('rechercheTabs');
        this.content = document.getElementById('rechercheContent');
        this.empty = document.getElementById('rechercheEmpty');
        
        // Debug des éléments
        // console.log('🔍 Éléments trouvés:', {
            modal: !!this.modal,
            closeBtn: !!this.closeBtn,
            input: !!this.input,
            searchBtn: !!this.searchBtn,
            loading: !!this.loading,
            tabs: !!this.tabs,
            content: !!this.content,
            empty: !!this.empty
        });
        
        this.currentTab = 'reparations';
        this.lastSearchTerm = '';
        this.searchResults = {};
        
        this.init();
    }
    
    init() {
        // console.log('🔍 Initialisation des événements...');
        
        // Event listeners avec vérification
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
            // console.log('✅ Event close attaché');
        }
        
        if (this.searchBtn) {
            this.searchBtn.addEventListener('click', () => {
                // console.log('🔍 Clic sur bouton recherche détecté');
                this.search();
            });
            // console.log('✅ Event search button attaché');
        }
        
        if (this.input) {
            this.input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    // console.log('🔍 Entrée pressée dans input');
                    e.preventDefault();
                    this.search();
                }
            });
            // console.log('✅ Event input keypress attaché');
        }
        
        // Fermer en cliquant sur l'overlay
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.close();
                }
            });
        }
        
        // Gestion des onglets
        if (this.tabs) {
            this.tabs.addEventListener('click', (e) => {
                const tabBtn = e.target.closest('.tab-btn');
                if (tabBtn) {
                    const tabName = tabBtn.dataset.tab;
                    this.switchTab(tabName);
                }
            });
        }
        
        // Échapper pour fermer
        document.addEventListener('keydown', (e) => {
            if (this.modal && e.key === 'Escape' && this.modal.style.display !== 'none') {
                this.close();
            }
        });
    }
    
    open() {
        if (!this.modal) return;
        // console.log('🔍 Ouverture du modal moderne');
        this.modal.style.display = 'flex';
        // Etat compact par défaut (surtout mobile)
        if (this.modalBox) {
            this.modalBox.classList.add('compact');
        }
        // Cacher toutes les zones (onglets, contenu, message vide) à l'ouverture
        this.hideAllStates();
        setTimeout(() => {
            this.modal.classList.add('show');
            if (this.input) this.input.focus();
        }, 10);
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        if (!this.modal) return;
        this.modal.classList.remove('show');
        setTimeout(() => {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
        
        // Reset
        if (this.input) this.input.value = '';
        this.hideAllStates();
        this.clearResults();
        if (this.modalBox) {
            this.modalBox.classList.remove('compact');
        }
    }
    
    async search() {
        // console.log('🔍 Fonction search() appelée');
        
        if (!this.input) return;
        
        const term = this.input.value.trim();
        // console.log('🔍 Terme de recherche:', term);
        
        if (term.length < 2) {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
            return;
        }
        
        this.lastSearchTerm = term;
        // console.log('🔍 Début de la recherche pour:', term);
        this.showLoading();
        
        try {
            // Recherche dans toutes les catégories
            const results = await this.performSearch(term);
            
            if (results) {
                this.searchResults = results;
                this.displayResults();
            } else {
                this.showEmpty();
            }
        } catch (error) {
            console.error('Erreur de recherche:', error);
            this.showEmpty();
        }
    }
    
    async performSearch(term) {
        // console.log('🔍 performSearch() appelée avec le terme:', term);
        
        const formData = new FormData();
        formData.append('terme', term);
        
        try {
            // console.log('🔍 Envoi de la requête AJAX...');
            const response = await fetch('ajax/recherche_universelle.php', {
                method: 'POST',
                body: formData
            });
            
            // console.log('🔍 Réponse reçue, status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // console.log('🔍 Parsing JSON...');
            const data = await response.json();
            // console.log('🔍 Données reçues:', data);
            
            const results = {
                reparations: data.reparations || [],
                clients: data.clients || [],
                commandes: data.commandes || [],
            };
            
            // console.log('🔍 Résultats formatés:', results);
            return results;
        } catch (error) {
            console.error('❌ Erreur lors de la recherche:', error);
            return null;
        }
    }
    
    async searchTaches(term) {
        // Les tâches sont maintenant incluses dans la réponse de recherche_universelle.php
        // Cette fonction n'est plus nécessaire car les tâches sont récupérées avec les autres données
        return [];
    }
    
    displayResults() {
        // console.log('🔍 displayResults() appelée avec:', this.searchResults);
        this.hideAllStates();
        if (this.modalBox) {
            this.modalBox.classList.remove('compact');
        }
        
        // Compter les résultats
        const counts = {
            reparations: this.searchResults.reparations?.length || 0,
            clients: this.searchResults.clients?.length || 0,
            commandes: this.searchResults.commandes?.length || 0,
        };
        
        // console.log('🔍 Compteurs de résultats:', counts);
        
        const totalResults = Object.values(counts).reduce((sum, count) => sum + count, 0);
        // console.log('🔍 Total des résultats:', totalResults);
        
        if (totalResults === 0) {
            // console.log('🔍 Aucun résultat, affichage du message vide');
            this.showEmpty();
            if (this.modalBox) {
                this.modalBox.classList.remove('compact');
            }
            return;
        }
        
        // Mettre à jour les compteurs des onglets (scopé au modal moderne)
        // console.log('🔍 AVANT updateTabCounts');
        this.updateTabCounts(counts);
        // console.log('🔍 APRÈS updateTabCounts');
        
        // Afficher les onglets et le contenu
        if (this.tabs) this.tabs.style.display = 'flex';
        if (this.content) this.content.style.display = 'block';
        
        // Remplir chaque onglet
        this.fillReparations(this.searchResults.reparations || []);
        this.fillClients(this.searchResults.clients || []);
        this.fillCommandes(this.searchResults.commandes || []);
        
        // Activer le premier onglet avec des résultats
        const firstTabWithResults = Object.keys(counts).find(tab => counts[tab] > 0);
        if (firstTabWithResults) {
            this.switchTab(firstTabWithResults);
        }
    }
    
    updateTabCounts(counts) {
        // console.log('🔍 updateTabCounts DÉBUT avec:', counts);
        // Scoper toutes les recherches DOM au conteneur du modal moderne
        const scope = this.modal || document;
        Object.keys(counts).forEach(tab => {
            const elementId = `${tab}Count`;
            // console.log('🔍 Recherche élément (scopé):', elementId);
            const countEl = scope.querySelector(`#${elementId}`);
            // console.log('🔍 Élément trouvé (scopé):', countEl);
            if (countEl) {
                // console.log('🔍 Mise à jour', elementId, 'avec valeur:', counts[tab]);
                countEl.textContent = counts[tab];
                // Forcer la visibilité et la mise à jour visuelle
                countEl.style.display = 'inline';
                countEl.style.opacity = '1';
                // console.log('🔍 Élément mis à jour:', countEl.textContent);
            } else {
                console.error('🔍 Élément NON TROUVÉ dans le scope:', elementId);
            }
        });
        // console.log('🔍 updateTabCounts FIN');
    }
    
    fillReparations(reparations) {
        const container = document.getElementById('reparationsList');
        if (!container) return;
        container.innerHTML = '';
        
        reparations.forEach(reparation => {
            const item = document.createElement('div');
            item.className = 'result-item';
            item.style.cursor = 'pointer';
            item.onclick = () => {
                // Rediriger vers la page des réparations avec ouverture automatique du modal
                window.location.href = `index.php?page=reparations&open_modal=${reparation.id}`;
            };
            
            const statusClass = this.getStatusBadgeClass(reparation.statut);
            
            item.innerHTML = `
                <div class="result-header">
                    <h5 class="result-title">${reparation.appareil || reparation.type_appareil || 'Appareil non spécifié'}</h5>
                    <span class="result-badge ${statusClass}">${reparation.statut || 'En cours'}</span>
                </div>
                <div class="result-details">
                    <strong>Client:</strong> ${reparation.client_nom || 'Non spécifié'}<br>
                    <strong>Problème:</strong> ${reparation.probleme || reparation.description_probleme || 'Non spécifié'}
                </div>
                <div class="result-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        ${reparation.date_creation || 'Date non spécifiée'}
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-hashtag"></i>
                        ID: ${reparation.id}
                    </div>
                </div>
            `;
            
            container.appendChild(item);
        });
    }
    
    fillClients(clients) {
        const container = document.getElementById('clientsList');
        if (!container) return;
        container.innerHTML = '';
        
        clients.forEach(client => {
            const item = document.createElement('div');
            item.className = 'result-item';
            item.onclick = () => this.openClient(client.id);
            
            item.innerHTML = `
                <div class="result-header">
                    <h5 class="result-title">${client.nom} ${client.prenom}</h5>
                </div>
                <div class="result-details">
                    <strong>Téléphone:</strong> ${client.telephone || 'Non spécifié'}<br>
                    <strong>Email:</strong> ${client.email || 'Non spécifié'}
                </div>
                <div class="result-meta">
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        ${client.telephone || 'N/A'}
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        ${client.email || 'N/A'}
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-hashtag"></i>
                        ID: ${client.id}
                    </div>
                </div>
            `;
            
            container.appendChild(item);
        });
    }
    
    fillCommandes(commandes) {
        const container = document.getElementById('commandesList');
        if (!container) return;
        container.innerHTML = '';
        
        commandes.forEach(commande => {
            const item = document.createElement('div');
            item.className = 'result-item';
            item.style.cursor = 'pointer';
            item.onclick = (event) => {
                // Utiliser la fonction existante pour ouvrir le modal de détails de commande
                if (typeof afficherDetailsCommande === 'function') {
                    afficherDetailsCommande(event, commande.id);
                } else {
                    console.warn('Fonction afficherDetailsCommande non trouvée, redirection vers la page commandes');
                    this.openCommande(commande.id);
                }
            };
            
            const statusClass = this.getStatusBadgeClass(commande.statut);
            
            item.innerHTML = `
                <div class="result-header">
                    <h5 class="result-title">Commande #${commande.reference || commande.id}</h5>
                    <span class="result-badge ${statusClass}">${commande.statut || 'En cours'}</span>
                </div>
                <div class="result-details">
                    <strong>Client:</strong> ${commande.client_nom || 'Non spécifié'}<br>
                    ${commande.montant ? `<strong>Montant:</strong> ${commande.montant}€<br>` : ''}
                    ${commande.nom_piece ? `<strong>Pièce:</strong> ${commande.nom_piece}` : ''}
                </div>
                <div class="result-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        ${commande.date_commande || commande.date_creation || 'Date non spécifiée'}
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-hashtag"></i>
                        ID: ${commande.id}
                    </div>
                </div>
            `;
            
            container.appendChild(item);
        });
    }
    
    
    getStatusBadgeClass(status) {
        if (!status) return 'badge-info';
        
        const statusLower = status.toLowerCase();
        
        if (statusLower.includes('terminé') || statusLower.includes('fini') || statusLower.includes('reçu')) {
            return 'badge-success';
        } else if (statusLower.includes('cours') || statusLower.includes('progress')) {
            return 'badge-warning';
        } else if (statusLower.includes('annulé') || statusLower.includes('échoué')) {
            return 'badge-danger';
        } else {
            return 'badge-info';
        }
    }
    
    switchTab(tabName) {
        this.currentTab = tabName;
        
        // Désactiver tous les onglets
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Cacher tous les contenus
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Activer l'onglet sélectionné
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        const activeContent = document.getElementById(`tab-${tabName}`);
        
        if (activeTab && activeContent) {
            activeTab.classList.add('active');
            activeContent.classList.add('active');
        }
    }
    
    showLoading() {
        this.hideAllStates();
        if (this.loading) this.loading.style.display = 'block';
    }
    
    showEmpty() {
        this.hideAllStates();
        if (this.empty) this.empty.style.display = 'block';
        if (this.modalBox) {
            this.modalBox.classList.remove('compact');
        }
    }
    
    hideAllStates() {
        if (this.loading) this.loading.style.display = 'none';
        if (this.tabs) this.tabs.style.display = 'none';
        if (this.content) this.content.style.display = 'none';
        if (this.empty) this.empty.style.display = 'none';
    }
    
    clearResults() {
        this.searchResults = {};
        this.lastSearchTerm = '';
        
        // Vider toutes les listes
        ['reparationsList', 'clientsList', 'commandesList'].forEach(listId => {
            const list = document.getElementById(listId);
            if (list) list.innerHTML = '';
        });
        
        // Reset des compteurs
        ['reparationsCount', 'clientsCount', 'commandesCount'].forEach(countId => {
            const count = document.getElementById(countId);
            if (count) count.textContent = '0';
        });
    }
    
    // Actions d'ouverture des éléments
    openReparation(id) {
        window.location.href = `index.php?page=details_reparation&id=${id}`;
    }
    
    openClient(id) {
        window.location.href = `index.php?page=historique_client&id=${id}`;
    }
    
    openCommande(id) {
        window.location.href = `index.php?page=commandes&id=${id}`;
    }
    
}

// Initialiser le modal moderne
let rechercheModerne;

document.addEventListener('DOMContentLoaded', function() {
    // console.log('🔍 Initialisation de RechercheModerne...');
    rechercheModerne = new RechercheModerne();
    
    // Fonction globale pour ouvrir le modal
    window.ouvrirRechercheModerne = function() {
        // console.log('🔍 ouvrirRechercheModerne() appelée');
        if (rechercheModerne) {
            rechercheModerne.open();
        } else {
            console.error('❌ rechercheModerne non initialisé');
            // Tentative de réinitialisation de secours
            rechercheModerne = new RechercheModerne();
            rechercheModerne.open();
        }
    };
    
    // console.log('✅ RechercheModerne initialisé et fonction globale créée');
});
