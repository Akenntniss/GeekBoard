// Script de diagnostic pour le formulaire de commande
document.addEventListener('DOMContentLoaded', function() {
    console.log('--- Diagnostics du formulaire de commande ---');
    
    // Vérifier l'état du select de fournisseur (nouvel ID)
    const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
    if (fournisseurSelect) {
        console.log('Fournisseur select trouvé (ID: fournisseur_id_ajout):', fournisseurSelect);
        console.log('  - Options disponibles:', fournisseurSelect.options.length);
        console.log('  - Value actuelle:', fournisseurSelect.value);
        console.log('  - selectedIndex:', fournisseurSelect.selectedIndex);
        
        // Créer une copie de l'ID pour compatibilité avec le code existant
        console.log('Création d\'un alias pour compatibilité avec saveCommande()');
        
        // S'assurer que fournisseur_id n'existe pas déjà
        if (!document.getElementById('fournisseur_id')) {
            // Créer un élément caché qui va pointer vers le même sélecteur
            const hiddenFournisseurId = document.createElement('input');
            hiddenFournisseurId.type = 'hidden';
            hiddenFournisseurId.id = 'fournisseur_id';
            hiddenFournisseurId.name = 'fournisseur_id_hidden';
            document.getElementById('commandeForm').appendChild(hiddenFournisseurId);
            
            // Synchroniser les valeurs
            fournisseurSelect.addEventListener('change', function() {
                hiddenFournisseurId.value = this.value;
                console.log('Synchronisation de fournisseur_id:', this.value);
            
            // Initialiser avec la valeur actuelle
            hiddenFournisseurId.value = fournisseurSelect.value;
            
            console.log('Alias créé avec succès:', hiddenFournisseurId);
            
            // Notification de succès
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.top = '10px';
            notification.style.right = '10px';
            notification.style.backgroundColor = 'rgba(0, 200, 0, 0.8)';
            notification.style.color = 'white';
            notification.style.padding = '10px';
            notification.style.borderRadius = '5px';
            notification.style.zIndex = '9999';
            notification.innerHTML = '✅ Compatibilité du sélecteur fournisseur établie!';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                setTimeout(() => document.body.removeChild(notification), 500);
            }, 3000);
        }
        
        // Lister toutes les options
        Array.from(fournisseurSelect.options).forEach((option, index) => {
            console.log(`  - Option ${index}: value="${option.value}", text="${option.text}"`);
        
        // Ajouter un événement de changement pour surveiller les modifications
        fournisseurSelect.addEventListener('change', function() {
            console.log('Fournisseur changé:', {
                value: this.value
                selectedIndex: this.selectedIndex
                selectedOption: this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : 'none'
    } else {
        console.error('Fournisseur select avec ID "fournisseur_id_ajout" NON TROUVÉ!');
        
        // Vérifier l'existence du formulaire
        const commandeForm = document.getElementById('commandeForm');
        if (commandeForm) {
            console.log('Formulaire trouvé, recherche d\'autres sélecteurs potentiels...');
            
            // Rechercher tous les selects dans le formulaire
            const selects = commandeForm.querySelectorAll('select');
            selects.forEach((select, index) => {
                console.log(`Select #${index}:`, {
                    id: select.id
                    name: select.name
                    options: select.options.length
                
                // Si un select a le nom "fournisseur_id" mais pas l'ID correct
                if (select.name === 'fournisseur_id') {
                    console.log('🔍 Sélecteur trouvé avec name="fournisseur_id" et id:', select.id);
                    
                    // Créer un alias
                    const hiddenFournisseurId = document.createElement('input');
                    hiddenFournisseurId.type = 'hidden';
                    hiddenFournisseurId.id = 'fournisseur_id';
                    hiddenFournisseurId.name = 'fournisseur_id_hidden';
                    commandeForm.appendChild(hiddenFournisseurId);
                    
                    // Synchroniser les valeurs
                    select.addEventListener('change', function() {
                        hiddenFournisseurId.value = this.value;
                        console.log('Synchronisation de fournisseur_id:', this.value);
                    
                    // Initialiser avec la valeur actuelle
                    hiddenFournisseurId.value = select.value;
                    
                    console.log('Alias créé avec succès:', hiddenFournisseurId);
                }
        } else {
            console.error('Formulaire de commande NON TROUVÉ!');
        }
    }
    
    // Ajouter un listener pour diagnostiquer le comportement lors de la sauvegarde
    const saveButton = document.querySelector('button[onclick="saveCommande()"]');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            console.log('--- Diagnostic au moment de la sauvegarde ---');
            
            // Vérifier à nouveau le sélecteur (avec le nouvel ID)
            const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
            
            if (fournisseurSelect) {
                console.log('Valeur actuelle du fournisseur:', fournisseurSelect.value);
                console.log('Option sélectionnée:', fournisseurSelect.options[fournisseurSelect.selectedIndex]?.text);
                
                // S'assurer que l'alias est à jour
                const hiddenFournisseurId = document.getElementById('fournisseur_id');
                if (hiddenFournisseurId) {
                    hiddenFournisseurId.value = fournisseurSelect.value;
                    console.log('Valeur de l\'alias mise à jour:', hiddenFournisseurId.value);
                }
                
                // Tester une sélection manuelle du fournisseur
                if (!fournisseurSelect.value && fournisseurSelect.options.length > 1) {
                    // Sélectionner la première option non vide
                    for (let i = 1; i < fournisseurSelect.options.length; i++) {
                        if (fournisseurSelect.options[i].value) {
                            console.log(`Tentative de sélection manuelle de l'option ${i}:`, fournisseurSelect.options[i].text);
                            
                            // Essayer différentes méthodes de sélection
                            fournisseurSelect.selectedIndex = i;
                            console.log('Après selectedIndex:', fournisseurSelect.value);
                            
                            fournisseurSelect.value = fournisseurSelect.options[i].value;
                            console.log('Après value=:', fournisseurSelect.value);
                            
                            // Déclencher un événement change
                            fournisseurSelect.dispatchEvent(new Event('change'));
                            console.log('Après événement change:', fournisseurSelect.value);
                            
                            break;
                        }
                    }
                }
            } else {
                console.error('Fournisseur select avec ID "fournisseur_id_ajout" toujours NON TROUVÉ au moment de la sauvegarde!');
                
                // Chercher à nouveau n'importe quel sélecteur avec le bon name
                const form = document.getElementById('commandeForm');
                if (form) {
                    const fournisseurSelectByName = form.querySelector('select[name="fournisseur_id"]');
                    if (fournisseurSelectByName) {
                        console.log('Fournisseur trouvé par name:', fournisseurSelectByName);
                        console.log('Valeur:', fournisseurSelectByName.value);
                    }
                }
            }
            
            // Vérifier tous les champs du formulaire
            const form = document.getElementById('commandeForm');
            if (form) {
                const formData = new FormData(form);
                console.log('FormData contient:');
                for (const [key, value] of formData.entries()) {
                    console.log(`  - ${key}: ${value}`);
                }
            }
    } else {
        console.error('Bouton de sauvegarde NON TROUVÉ!');
    }
    
    // Bouton pour forcer une sélection
    const commandeForm = document.getElementById('commandeForm');
    if (commandeForm) {
        // Trouver le sélecteur par son attribut name
        const fournisseurByName = commandeForm.querySelector('select[name="fournisseur_id"]');
        if (fournisseurByName && (fournisseurByName.id !== 'fournisseur_id')) {
            // Créer un bouton pour corriger l'ID
            const fixIdButton = document.createElement('button');
            fixIdButton.type = 'button';
            fixIdButton.className = 'btn btn-warning btn-sm mt-2';
            fixIdButton.textContent = 'Corriger l\'ID du sélecteur';
            fixIdButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Corriger l'ID
                fournisseurByName.id = 'fournisseur_id';
                console.log('ID du sélecteur corrigé manuellement:', fournisseurByName);
                alert('ID du sélecteur corrigé en "fournisseur_id"');
                
                // Masquer le bouton après utilisation
                this.style.display = 'none';
            
            // Insérer le bouton après le select
            const selectContainer = fournisseurByName.closest('.select-container');
            if (selectContainer) {
                selectContainer.appendChild(fixIdButton);
            } else {
                fournisseurByName.parentNode.insertBefore(fixIdButton, fournisseurByName.nextSibling);
            }
        }
        
        // Si le sélecteur existe maintenant (soit trouvé par ID ou name)
        const fournisseurSelect = fournisseurByName || document.getElementById('fournisseur_id');
        if (fournisseurSelect) {
            const fixButton = document.createElement('button');
            fixButton.type = 'button';
            fixButton.className = 'btn btn-info btn-sm mt-2';
            fixButton.textContent = 'Forcer la sélection du fournisseur';
            fixButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (fournisseurSelect.options.length > 1) {
                    fournisseurSelect.selectedIndex = 1; // Premier fournisseur réel
                    fournisseurSelect.dispatchEvent(new Event('change'));
                    console.log('Sélection forcée:', fournisseurSelect.value);
                    alert('Premier fournisseur sélectionné: ' + fournisseurSelect.options[1].text);
                }
            
            // Insérer le bouton après le select
            const selectContainer = fournisseurSelect.closest('.select-container');
            if (selectContainer) {
                selectContainer.appendChild(fixButton);
            } else {
                fournisseurSelect.parentNode.insertBefore(fixButton, fournisseurSelect.nextSibling);
            }
        }
    }
}); 