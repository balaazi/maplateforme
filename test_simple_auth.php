<?php

require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;

// Charger les variables d'environnement
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

echo "=== TEST SIMPLE AUTHENTIFICATION ===\n\n";

// Configuration Google
$client = new Client();
$client->setApplicationName('MaPlateforme');
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
$client->addScope(Calendar::CALENDAR);
$client->addScope(Calendar::CALENDAR_EVENTS);
$client->setAccessType('offline');
$client->setPrompt('consent');

echo "🔧 Configuration:\n";
echo "   GOOGLE_CLIENT_ID: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (isset($_ENV['GOOGLE_CLIENT_SECRET']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . (isset($_ENV['GOOGLE_REDIRECT_URI']) ? '✅ Défini' : '❌ Manquant') . "\n\n";

// Charger le token
$tokenPath = 'var/google-token.json';
if (file_exists($tokenPath)) {
    echo "📄 Fichier token trouvé\n";
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    if ($accessToken && isset($accessToken['access_token'])) {
        echo "   Token valide: ✅ Oui\n";
        $client->setAccessToken($accessToken);
        
        if ($client->isAccessTokenExpired()) {
            echo "   Token expiré: ⚠️ Oui\n";
            if ($client->getRefreshToken()) {
                echo "   Refresh token disponible: ✅ Oui\n";
                try {
                    $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($newToken));
                    echo "   Token refreshé: ✅ Succès\n";
                } catch (\Exception $e) {
                    echo "   Token refreshé: ❌ Erreur - " . $e->getMessage() . "\n";
                }
            } else {
                echo "   Refresh token disponible: ❌ Non\n";
            }
        } else {
            echo "   Token expiré: ❌ Non\n";
        }
    } else {
        echo "   Token valide: ❌ Non\n";
    }
} else {
    echo "📄 Fichier token: ❌ N'existe pas\n";
}

echo "\n🔑 État final:\n";
echo "   Access Token: " . ($client->getAccessToken() ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   isAuthenticated(): " . ($client->getAccessToken() ? '✅ Oui' : '❌ Non') . "\n";

if (!$client->getAccessToken()) {
    echo "\n❌ PROBLÈME IDENTIFIÉ: L'utilisateur n'est pas authentifié avec Google Calendar.\n";
    echo "   Solution: Aller sur /oauth/connect pour se connecter à Google\n";
} else {
    echo "\n✅ L'utilisateur est authentifié. Le problème doit être ailleurs.\n";
}

echo "\n=== FIN DU TEST ===\n";
