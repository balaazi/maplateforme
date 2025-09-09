<?php

// Utiliser l'autoloader de Symfony
require_once 'vendor/autoload.php';

echo "=== TEST VARIABLES D'ENVIRONNEMENT ===\n\n";

// Test 1: Charger avec Dotenv
echo "1. Test avec Dotenv:\n";
try {
    $dotenv = new \Symfony\Component\Dotenv\Dotenv();
    $dotenv->load('.env');
    echo "   ✅ Dotenv chargé\n";
} catch (\Exception $e) {
    echo "   ❌ Erreur Dotenv: " . $e->getMessage() . "\n";
}

// Test 2: Vérifier les variables
echo "\n2. Variables d'environnement:\n";
echo "   GOOGLE_CLIENT_ID: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (isset($_ENV['GOOGLE_CLIENT_SECRET']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . (isset($_ENV['GOOGLE_REDIRECT_URI']) ? '✅ Défini' : '❌ Manquant') . "\n";

// Test 3: Lire le fichier .env directement
echo "\n3. Lecture directe du fichier .env:\n";
if (file_exists('.env')) {
    $content = file_get_contents('.env');
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        if (strpos($line, 'GOOGLE_') === 0) {
            echo "   " . $line . "\n";
        }
    }
} else {
    echo "   ❌ Fichier .env non trouvé\n";
}

// Test 4: Test Google Client simple
echo "\n4. Test Google Client:\n";
try {
    $client = new \Google\Client();
    echo "   ✅ Google Client créé\n";
} catch (\Exception $e) {
    echo "   ❌ Erreur Google Client: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU TEST ===\n";
