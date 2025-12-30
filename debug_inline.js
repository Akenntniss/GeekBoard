// Script de debug à injecter dans la page
console.log('🐞 DEBUG SEARCH - Script chargé');

// Test immédiat
console.log('🐞 Test immédiat:', {
    searchInput: document.getElementById('kbSearch'),
    searchButton: document.getElementById('searchButton'),
    categoryFilter: document.getElementById('kbCategoryFilter')
});

// Test au DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🐞 DOM Content Loaded - Test éléments:');
    
    const searchInput = document.getElementById('kbSearch');
    const searchButton = document.getElementById('searchButton');
    const categoryFilter = document.getElementById('kbCategoryFilter');
    
    console.log({
        searchInput: searchInput,
        searchButton: searchButton,
        categoryFilter: categoryFilter,
        searchInputExists: !!searchInput,
        searchButtonExists: !!searchButton,
        categoryFilterExists: !!categoryFilter
    });
    
    // Test des radios
    const radios = document.querySelectorAll('input[name="search_type_ui"]');
    console.log('🐞 Radios trouvés:', radios.length, radios);
    
    // Test des event listeners
    if (searchButton) {
        console.log('🐞 Ajout event listener sur bouton');
        searchButton.addEventListener('click', function() {
            console.log('🐞 BOUTON CLIQUÉ !');
            alert('Bouton rechercher cliqué !');
        });
    } else {
        console.error('🐞 ERREUR: Bouton searchButton non trouvé !');
    }
    
    if (searchInput) {
        console.log('🐞 Ajout event listener sur input');
        searchInput.addEventListener('keypress', function(e) {
            console.log('🐞 Touche pressée:', e.key);
            if (e.key === 'Enter') {
                console.log('🐞 ENTRÉE PRESSÉE !');
                alert('Touche Entrée détectée !');
            }
        });
    } else {
        console.error('🐞 ERREUR: Input kbSearch non trouvé !');
    }
    
    // Test des radios
    radios.forEach(function(radio, index) {
        radio.addEventListener('change', function() {
            console.log('🐞 Radio changé:', this.value);
            alert('Radio sélectionné: ' + this.value);
        });
    });
    
    console.log('🐞 Tous les event listeners de debug ajoutés');
});

// Test avec un délai
setTimeout(function() {
    console.log('🐞 Test après 3 secondes:');
    console.log({
        searchInput: document.getElementById('kbSearch'),
        searchButton: document.getElementById('searchButton'),
        categoryFilter: document.getElementById('kbCategoryFilter')
    });
}, 3000);
