/**
 * Script simplifié pour gérer les modaux de l'application
 * Version allégée pour éviter les conflits avec le script de correction spécifique
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation simplifiée des modaux...');
    
    // Seulement des logs pour le debug, pas d'intervention sur les modaux
    const nouvelles_actions_modal = document.getElementById('nouvelles_actions_modal');
    const menu_navigation_modal = document.getElementById('menu_navigation_modal');
    
    if (nouvelles_actions_modal) {
        // Écouter les événements pour le debug uniquement
        nouvelles_actions_modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal ouvert:', this.id);
        });
        
        nouvelles_actions_modal.addEventListener('hidden.bs.modal', function() {
            console.log('Modal fermé:', this.id);
        });
    }
    
    if (menu_navigation_modal) {
        menu_navigation_modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal ouvert:', this.id);
        });
        
        menu_navigation_modal.addEventListener('hidden.bs.modal', function() {
            console.log('Modal fermé:', this.id);
        });
    }
    
    console.log('Initialisation simplifiée des modaux terminée');
});
