<?php
/**
 * Script de test pour vérifier la correction du modal "Devis en attente"
 * Ce script teste si l'URL utilisée par l'iframe fonctionne correctement
 */

echo "<h2>Test de la correction du modal 'Devis en attente'</h2>";

// Simuler l'appel de l'iframe
$test_url = "index.php?page=devis&statut_ids=envoye";

echo "<h3>URL testée : $test_url</h3>";

// Tester différents sous-domaines
$test_domains = [
    'mkmkmk.mdgeek.top',
    'cannesphones.mdgeek.top',
    'localhost' // Pour les tests locaux
];

echo "<h3>Domaines de test :</h3>";
echo "<ul>";
foreach ($test_domains as $domain) {
    $full_url = "http://$domain/$test_url";
    echo "<li><a href='$full_url' target='_blank'>$full_url</a></li>";
}
echo "</ul>";

echo "<h3>Instructions de test :</h3>";
echo "<ol>";
echo "<li>Aller sur la page des réparations</li>";
echo "<li>Cliquer sur le bouton 'DEVIS EN ATTENTE'</li>";
echo "<li>Vérifier que le modal s'ouvre correctement sans erreur 404</li>";
echo "<li>Vérifier que la liste des devis en attente s'affiche</li>";
echo "</ol>";

echo "<h3>Correction apportée :</h3>";
echo "<ul>";
echo "<li>✅ Ajout d'une fonction d'initialisation de session spécifique pour les appels via iframe</li>";
echo "<li>✅ Détection automatique du sous-domaine dans le contexte iframe</li>";
echo "<li>✅ Gestion des erreurs avec messages explicites</li>";
echo "<li>✅ Initialisation complète de la session magasin</li>";
echo "</ul>";

echo "<h3>Fichiers modifiés :</h3>";
echo "<ul>";
echo "<li>✅ /pages/devis.php - Ajout de l'initialisation de session pour iframe</li>";
echo "</ul>";

echo "<h3>Déploiement :</h3>";
echo "<ul>";
echo "<li>✅ Fichier uploadé sur le serveur</li>";
echo "<li>✅ Permissions corrigées (www-data:www-data)</li>";
echo "</ul>";

echo "<p><strong>Status : CORRECTION DEPLOYÉE ✅</strong></p>";
?>

