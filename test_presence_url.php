<?php

echo "=== TEST URL DE PRÉSENCE ===\n\n";

// URL de test (remplacer 45 par l'ID d'un événement existant)
$url = 'http://127.0.0.1:8000/event/45/presence';

echo "🌐 Test de l'URL: $url\n\n";

// Test avec cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request seulement

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Résultat:\n";
echo "   Code HTTP: $httpCode\n";
if ($error) {
    echo "   Erreur cURL: $error\n";
} else {
    echo "   ✅ Requête réussie\n";
}

echo "\n📄 Headers de réponse:\n";
echo $response;

echo "\n=== FIN DU TEST ===\n";
