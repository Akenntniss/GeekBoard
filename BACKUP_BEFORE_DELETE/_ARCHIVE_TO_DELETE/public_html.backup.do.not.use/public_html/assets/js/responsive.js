/**
 * Script JavaScript pour améliorer l'expérience utilisateur responsive
 * Gère les comportements spécifiques à chaque taille d'écran
 */

document.addEventListener('DOMContentLoaded', function() {
    // Détecter le type d'appareil
    const deviceType = getDeviceType();
    document.body.classList.add(deviceType);
    
    // Initialiser les fonctionnalités spécifiques à chaque appareil
    initDeviceSpecificFeatures(deviceType);
    
    // Écouter les changements de taille d'écran
    window.addEventListener('resize', function() {
        const newDeviceType = getDeviceType();
        if (newDeviceType !== deviceType) {
            document.body.classList.remove(deviceType);
            document.body.classList.add(newDeviceType);
            initDeviceSpecificFeatures(newDeviceType);
        }
    });
    
    // Initialiser les fonctionnalités communes
    initCommonFeatures();
});

/**
 * Détermine le type d'appareil en fonction de la largeur de l'écran
 */
function getDeviceType() {
    const width = window.innerWidth;
    
    if (width >= 1200) {
        return 'desktop'; // PC / MAC
    } else if (width >= 992) {
        return 'large-tablet'; // Tablette grande taille (12 pouces)
    } else if (width >= 768) {
        return 'tablet'; // Tablette taille normale (10 pouces)
    } else {
        return 'smartphone'; // Smartphone (petit écran)
    }
}

/**
 * Initialise les fonctionnalités spécifiques à chaque type d'appareil
 */
function initDeviceSpecificFeatures(deviceType) {
    // Réinitialiser les fonctionnalités spécifiques
    resetDeviceSpecificFeatures();
    
    switch (deviceType) {
        case 'desktop':
            initDesktopFeatures();
            break;
        case 'large-tablet':
            initLargeTabletFeatures();
            break;
        case 'tablet':
            initTabletFeatures();
            break;
        case 'smartphone':
            initSmartphoneFeatures();
            break;
    }
}

/**
 * Réinitialise les fonctionnalités spécifiques aux appareils
 */
function resetDeviceSpecificFeatures() {
    // Réinitialiser les tableaux
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.classList.remove('table-sm');
    });
    
    // Réinitialiser les vues mobiles
    const mobileViews = document.querySelectorAll('.mobile-card-view');
    mobileViews.forEach(view => {
        view.style.display = 'none';
    });
    
    // Réinitialiser les vues desktop
    const desktopViews = document.querySelectorAll('.desktop-view');
    desktopViews.forEach(view => {
        view.style.display = '';
    });
}

/**
 * Initialise les fonctionnalités pour les ordinateurs de bureau
 */
function initDesktopFeatures() {
    // Activer les tooltips pour plus d'informations au survol
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Améliorer les tableaux pour les grands écrans
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.classList.remove('table-sm');
    });
    
    // Afficher les colonnes supplémentaires
    const extendedInfoCells = document.querySelectorAll('.extended-info');
    extendedInfoCells.forEach(cell => {
        cell.style.display = 'table-cell';
    });
}

/**
 * Initialise les fonctionnalités pour les grandes tablettes
 */
function initLargeTabletFeatures() {
    // Ajuster les tableaux pour les grandes tablettes
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.classList.remove('table-sm');
    });
    
    // Optimiser l'affichage des formulaires
    optimizeFormLayout('large-tablet');
}

/**
 * Initialise les fonctionnalités pour les tablettes standard
 */
function initTabletFeatures() {
    // Ajuster les tableaux pour les tablettes
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.classList.add('table-sm');
    });
    
    // Optimiser l'affichage des formulaires
    optimizeFormLayout('tablet');
}

/**
 * Initialise les fonctionnalités pour les smartphones
 */
function initSmartphoneFeatures() {
    // EXCLU: Protection pour la page clients
    const isClientsPage = window.location.href.includes('page=clients') || 
                         document.title.includes('Clients') ||
                         document.querySelector('h1, h2, h3')?.textContent?.includes('Clients');
    
    // Ajuster les tableaux pour les petits écrans
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.classList.add('table-sm');
    });
    
    // Convertir les tableaux en vues de cartes pour mobile (sauf page clients)
    if (!isClientsPage) {
    convertTablesToMobileCards();
    } else {
        console.log('🚫 initSmartphoneFeatures: Conversion mobile désactivée sur page clients');
    }
    
    // Optimiser l'affichage des formulaires
    optimizeFormLayout('smartphone');
    
    // Initialiser la recherche rapide pour mobile
    initMobileSearch();
}

/**
 * Convertit les tableaux en vues de cartes pour mobile
 */
function convertTablesToMobileCards() {
    // EXCLU: Ne pas modifier les tableaux sur la page clients
    if (window.location.href.includes('page=clients') || 
        document.title.includes('Clients') ||
        document.querySelector('h1, h2, h3')?.textContent?.includes('Clients')) {
        console.log('🚫 convertTablesToMobileCards: Ignoré sur la page clients');
        return;
    }
    
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // Vérifier si la vue mobile existe déjà
        const tableContainer = table.closest('.table-responsive');
        if (!tableContainer) return;
        
        let mobileView = tableContainer.nextElementSibling;
        if (!mobileView || !mobileView.classList.contains('mobile-cards-container')) {
            // Créer un conteneur pour les cartes mobiles
            mobileView = document.createElement('div');
            mobileView.className = 'mobile-cards-container d-md-none';
            tableContainer.parentNode.insertBefore(mobileView, tableContainer.nextSibling);
        } else {
            // Vider le conteneur existant
            mobileView.innerHTML = '';
        }
        
        // Masquer le tableau sur mobile
        tableContainer.classList.add('d-none', 'd-md-block');
        
        // Obtenir les données du tableau
        const rows = table.querySelectorAll('tbody tr');
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        
        // Créer une carte pour chaque ligne
        rows.forEach(row => {
            if (row.classList.contains('no-data-row')) return;
            
            const card = document.createElement('div');
            card.className = 'card mobile-card-view mb-3';
            
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-3';
            
            // Obtenir les cellules de la ligne
            const cells = row.querySelectorAll('td');
            
            // Créer le contenu de la carte
            let cardContent = '';
            
            // Ajouter le titre (généralement le nom du client ou de l'élément)
            if (cells.length > 0) {
                cardContent += `<h5 class="card-title">${cells[1].innerHTML}</h5>`;
            }
            
            // Ajouter les autres informations
            cardContent += '<div class="card-text">';
            cells.forEach((cell, index) => {
                if (index === 1) return; // Sauter la cellule utilisée comme titre
                if (index === 0) return; // Sauter l'ID
                if (cell.classList.contains('d-none') && !cell.classList.contains('d-md-table-cell')) return;
                
                // Vérifier si la cellule contient des boutons d'action
                if (cell.querySelector('.btn-group, .btn')) {
                    cardContent += `<div class="mt-2 d-flex justify-content-end">${cell.innerHTML}</div>`;
                } else if (headers[index]) {
                    cardContent += `<p class="mb-1"><strong>${headers[index]}:</strong> ${cell.innerHTML}</p>`;
                }
            });
            cardContent += '</div>';
            
            cardBody.innerHTML = cardContent;
            card.appendChild(cardBody);
            mobileView.appendChild(card);
        });
    });
}

/**
 * Optimise la disposition des formulaires selon le type d'appareil
 */
function optimizeFormLayout(deviceType) {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const formGroups = form.querySelectorAll('.form-group, .mb-3');
        
        formGroups.forEach(group => {
            const label = group.querySelector('label');
            const input = group.querySelector('input, select, textarea');
            
            if (!label || !input) return;
            
            if (deviceType === 'smartphone') {
                // Réduire l'espacement pour les smartphones
                group.classList.remove('mb-3');
                group.classList.add('mb-2');
                
                // Réduire la taille des labels
                label.style.fontSize = '0.9rem';
            } else {
                // Réinitialiser pour les autres appareils
                group.classList.remove('mb-2');
                group.classList.add('mb-3');
                
                // Réinitialiser la taille des labels
                label.style.fontSize = '';
            }
        });
    });
}

/**
 * Initialise la recherche rapide pour mobile
 */
function initMobileSearch() {
    const searchInput = document.getElementById('searchRepair') || document.getElementById('searchClient');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Rechercher dans les cartes mobiles
            const mobileCards = document.querySelectorAll('.mobile-card-view');
            mobileCards.forEach(card => {
                const cardText = card.textContent.toLowerCase();
                if (cardText.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Rechercher dans les lignes de tableau
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

/**
 * Initialise les fonctionnalités communes à tous les appareils
 */
function initCommonFeatures() {
    // Initialiser les popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Ajouter des animations de chargement
    addLoadingAnimations();
    
    // Améliorer l'accessibilité
    improveAccessibility();
}

/**
 * Ajoute des animations de chargement pour améliorer l'UX
 */
function addLoadingAnimations() {
    // Ajouter une animation de chargement lors de la soumission des formulaires
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            
            if (submitButton) {
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
                
                // Réactiver le bouton si la soumission prend trop de temps
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }, 10000);
            }
        });
    });
    
    // Ajouter une animation lors du chargement des liens importants
    const actionLinks = document.querySelectorAll('a.btn-primary, a.btn-success, a.btn-warning, a.btn-danger');
    
    actionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Ne pas animer les liens qui ouvrent des modales
            if (this.getAttribute('data-bs-toggle') === 'modal') return;
            
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
            
            // Réinitialiser après un délai si la page ne se charge pas
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 5000);
        });
    });
}

/**
 * Améliore l'accessibilité du site
 */
function improveAccessibility() {
    // Ajouter des attributs ARIA manquants
    const buttons = document.querySelectorAll('button:not([aria-label])');
    buttons.forEach(button => {
        if (button.textContent.trim()) {
            button.setAttribute('aria-label', button.textContent.trim());
        } else if (button.title) {
            button.setAttribute('aria-label', button.title);
        }
    });
    
    // Améliorer le contraste des éléments
    const lowContrastElements = document.querySelectorAll('.text-muted');
    lowContrastElements.forEach(element => {
        element.style.color = '#6a6a6a';
    });
    
    // Ajouter des attributs alt aux images
    const images = document.querySelectorAll('img:not([alt])');
    images.forEach(img => {
        img.setAttribute('alt', 'Image');
    });
}