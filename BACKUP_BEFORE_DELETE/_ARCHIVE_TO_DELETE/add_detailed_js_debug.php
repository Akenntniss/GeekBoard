<?php
// Script pour ajouter des logs JavaScript détaillés à inscription.php

$file_path = '/var/www/mdgeek.top/inscription.php';
$content = file_get_contents($file_path);

echo "=== AJOUT LOGS JAVASCRIPT DÉTAILLÉS ===\n";

// 1. Ajouter debug au début de la soumission du formulaire
$old_form_submit = "document.getElementById('shopForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Réinitialiser les variables de progression
    currentStep = 0;
    startTime = null;";

$new_form_submit = "document.getElementById('shopForm').addEventListener('submit', function(e) {
    console.log('=== INSCRIPTION JS: DÉBUT SOUMISSION FORMULAIRE ===');
    console.log('INSCRIPTION JS: Event:', e);
    console.log('INSCRIPTION JS: Form element:', this);
    console.log('INSCRIPTION JS: Current URL:', window.location.href);
    
    e.preventDefault();
    
    // Réinitialiser les variables de progression
    currentStep = 0;
    startTime = null;
    
    console.log('INSCRIPTION JS: Variables réinitialisées');";

if (strpos($content, $old_form_submit) !== false) {
    $content = str_replace($old_form_submit, $new_form_submit, $content);
    echo "✅ Debug ajouté au début de soumission\n";
} else {
    echo "⚠️ Début soumission formulaire non trouvé\n";
}

// 2. Ajouter debug pour la préparation des données
$old_form_data = "    // Préparer les données du formulaire
    const formData = new FormData(this);";

$new_form_data = "    // Préparer les données du formulaire
    console.log('INSCRIPTION JS: Préparation des données du formulaire');
    const formData = new FormData(this);
    
    // Logger toutes les données du formulaire
    console.log('INSCRIPTION JS: Données du formulaire:');
    for (let [key, value] of formData.entries()) {
        console.log('INSCRIPTION JS: ' + key + ':', value);
    }
    console.log('INSCRIPTION JS: FormData size:', formData.entries().length);";

if (strpos($content, $old_form_data) !== false) {
    $content = str_replace($old_form_data, $new_form_data, $content);
    echo "✅ Debug ajouté à la préparation des données\n";
} else {
    echo "⚠️ Préparation données non trouvée\n";
}

// 3. Ajouter debug avant la requête AJAX
$old_ajax_start = "    // Faire la requête AJAX immédiatement mais ne pas traiter le résultat avant 30 secondes
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })";

$new_ajax_start = "    // Faire la requête AJAX immédiatement mais ne pas traiter le résultat avant 30 secondes
    console.log('INSCRIPTION JS: Début requête AJAX');
    console.log('INSCRIPTION JS: URL cible:', window.location.href);
    console.log('INSCRIPTION JS: Méthode: POST');
    console.log('INSCRIPTION JS: Headers:', {'X-Requested-With': 'XMLHttpRequest'});
    
    const startAjaxTime = Date.now();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })";

if (strpos($content, $old_ajax_start) !== false) {
    $content = str_replace($old_ajax_start, $new_ajax_start, $content);
    echo "✅ Debug ajouté avant requête AJAX\n";
} else {
    echo "⚠️ Début requête AJAX non trouvé\n";
}

// 4. Ajouter debug pour la réponse AJAX
$old_ajax_response = "    .then(response => response.json())
    .then(data => {
        formSubmissionData = data;
    })";

$new_ajax_response = "    .then(response => {
        const ajaxDuration = Date.now() - startAjaxTime;
        console.log('INSCRIPTION JS: Réponse AJAX reçue après', ajaxDuration, 'ms');
        console.log('INSCRIPTION JS: Status:', response.status);
        console.log('INSCRIPTION JS: Headers:', Object.fromEntries(response.headers.entries()));
        console.log('INSCRIPTION JS: OK:', response.ok);
        
        if (!response.ok) {
            console.error('INSCRIPTION JS: Réponse HTTP non-OK:', response.status, response.statusText);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('INSCRIPTION JS: Données JSON reçues:', data);
        console.log('INSCRIPTION JS: Type de données:', typeof data);
        console.log('INSCRIPTION JS: Success:', data.success);
        if (data.errors) {
            console.log('INSCRIPTION JS: Erreurs:', data.errors);
        }
        if (data.data) {
            console.log('INSCRIPTION JS: Data payload:', data.data);
        }
        
        formSubmissionData = data;
        console.log('INSCRIPTION JS: formSubmissionData assigné:', formSubmissionData);
    })";

if (strpos($content, $old_ajax_response) !== false) {
    $content = str_replace($old_ajax_response, $new_ajax_response, $content);
    echo "✅ Debug ajouté à la réponse AJAX\n";
} else {
    echo "⚠️ Réponse AJAX non trouvée\n";
}

// 5. Améliorer le debug des erreurs AJAX
$old_ajax_error = "    .catch(error => {
        console.error(\"INSCRIPTION JS: Erreur AJAX:\", error); console.log(\"INSCRIPTION JS: FormData:\", Object.fromEntries(formData));
        formSubmissionData = { 
            success: false, 
            errors: ['Une erreur technique s\\'est produite. Veuillez réessayer.'] 
        };
    });";

$new_ajax_error = "    .catch(error => {
        const ajaxDuration = Date.now() - startAjaxTime;
        console.error('INSCRIPTION JS: ERREUR AJAX après', ajaxDuration, 'ms');
        console.error('INSCRIPTION JS: Type d\\'erreur:', error.name);
        console.error('INSCRIPTION JS: Message:', error.message);
        console.error('INSCRIPTION JS: Stack:', error.stack);
        console.log('INSCRIPTION JS: FormData envoyée:', Object.fromEntries(formData));
        console.log('INSCRIPTION JS: URL utilisée:', window.location.href);
        
        formSubmissionData = { 
            success: false, 
            errors: ['Une erreur technique s\\'est produite. Veuillez réessayer.'] 
        };
        console.log('INSCRIPTION JS: formSubmissionData d\\'erreur assigné:', formSubmissionData);
    });";

if (strpos($content, $old_ajax_error) !== false) {
    $content = str_replace($old_ajax_error, $new_ajax_error, $content);
    echo "✅ Debug amélioré pour les erreurs AJAX\n";
} else {
    echo "⚠️ Erreurs AJAX non trouvées\n";
}

// 6. Ajouter debug pour le timeout de 30 secondes
$old_timeout = "    // Attendre exactement 30 secondes puis afficher le résultat
    setTimeout(() => {
        if (formSubmissionData) {";

$new_timeout = "    // Attendre exactement 30 secondes puis afficher le résultat
    console.log('INSCRIPTION JS: Démarrage timer 30 secondes');
    setTimeout(() => {
        console.log('INSCRIPTION JS: Timeout 30s atteint');
        console.log('INSCRIPTION JS: formSubmissionData disponible:', !!formSubmissionData);
        console.log('INSCRIPTION JS: Contenu formSubmissionData:', formSubmissionData);
        
        if (formSubmissionData) {";

if (strpos($content, $old_timeout) !== false) {
    $content = str_replace($old_timeout, $new_timeout, $content);
    echo "✅ Debug ajouté au timeout\n";
} else {
    echo "⚠️ Timeout non trouvé\n";
}

// 7. Ajouter debug pour les résultats finaux
$old_success_display = "            if (formSubmissionData.success) {
                // Succès - afficher les informations
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('successPhase').style.display = 'block';";

$new_success_display = "            if (formSubmissionData.success) {
                console.log('INSCRIPTION JS: Affichage phase de succès');
                console.log('INSCRIPTION JS: URL boutique:', formSubmissionData.data?.url);
                console.log('INSCRIPTION JS: Username:', formSubmissionData.data?.admin_username);
                
                // Succès - afficher les informations
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('successPhase').style.display = 'block';";

if (strpos($content, $old_success_display) !== false) {
    $content = str_replace($old_success_display, $new_success_display, $content);
    echo "✅ Debug ajouté à l'affichage de succès\n";
} else {
    echo "⚠️ Affichage succès non trouvé\n";
}

$old_error_display = "            } else {
                // Erreur - afficher les messages
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('errorPhase').style.display = 'block';";

$new_error_display = "            } else {
                console.log('INSCRIPTION JS: Affichage phase d\\'erreur');
                console.log('INSCRIPTION JS: Erreurs à afficher:', formSubmissionData.errors);
                
                // Erreur - afficher les messages
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('errorPhase').style.display = 'block';";

if (strpos($content, $old_error_display) !== false) {
    $content = str_replace($old_error_display, $new_error_display, $content);
    echo "✅ Debug ajouté à l'affichage d'erreur\n";
} else {
    echo "⚠️ Affichage erreur non trouvé\n";
}

// 8. Ajouter debug pour le cas où aucune réponse n'est reçue
$old_no_response = "        } else {
            // Si aucune réponse après 30 secondes, afficher une erreur
            document.getElementById('loadingPhase').style.display = 'none';
            document.getElementById('errorPhase').style.display = 'block';";

$new_no_response = "        } else {
            console.error('INSCRIPTION JS: AUCUNE RÉPONSE après 30 secondes');
            console.log('INSCRIPTION JS: formSubmissionData est null/undefined');
            console.log('INSCRIPTION JS: Vérification réseau et serveur nécessaire');
            
            // Si aucune réponse après 30 secondes, afficher une erreur
            document.getElementById('loadingPhase').style.display = 'none';
            document.getElementById('errorPhase').style.display = 'block';";

if (strpos($content, $old_no_response) !== false) {
    $content = str_replace($old_no_response, $new_no_response, $content);
    echo "✅ Debug ajouté pour absence de réponse\n";
} else {
    echo "⚠️ Absence de réponse non trouvée\n";
}

// Sauvegarder le fichier
if (file_put_contents($file_path, $content)) {
    echo "✅ Logs JavaScript détaillés ajoutés\n";
    
    // Vérifier la syntaxe
    $syntax_check = shell_exec("php -l $file_path 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "✅ Syntaxe PHP valide\n";
    } else {
        echo "❌ Erreur de syntaxe: $syntax_check\n";
        exit(1);
    }
} else {
    echo "❌ Impossible de sauvegarder\n";
    exit(1);
}

echo "\n=== LOGS JAVASCRIPT DÉTAILLÉS AJOUTÉS ===\n";
echo "Maintenant, ouvrez les outils de développement (F12 → Console)\n";
echo "et créez une boutique pour voir tous les détails du processus.\n";
?>
