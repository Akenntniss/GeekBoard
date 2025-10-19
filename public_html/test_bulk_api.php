<?php
// Test de l'API bulk_update_commandes.php

// Simuler une requête POST
$data = [
    'commande_ids' => [1, 2],
    'new_status' => 'commande'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://mkmkmk.mdgeek.top/api/bulk_update_commandes.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Code HTTP: " . $http_code . "\n";
echo "Réponse: " . $response . "\n";

curl_close($ch);
?>
