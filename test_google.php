<?php

require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;

echo "=== Test de connexion Google Calendar ===\n";

// Configuration client Google
$client = new Client();
$client->setApplicationName('MaPlateforme');
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
$client->addScope(Calendar::CALENDAR);
$client->addScope(Calendar::CALENDAR_EVENTS);
$client->setAccessType('offline');

$tokenPath = 'var/google-token.json';

if (!file_exists($tokenPath)) {
    echo "❌ Token Google Calendar introuvable\n";
    exit(1);
}

$accessToken = json_decode(file_get_contents($tokenPath), true);
if (!$accessToken || !isset($accessToken['access_token'])) {
    echo "❌ Token Google Calendar invalide\n";
    exit(1);
}

$client->setAccessToken($accessToken);

// Vérifier si le token est expiré
if ($client->isAccessTokenExpired()) {
    echo "⚠️ Token expiré\n";
    if ($client->getRefreshToken()) {
        echo "🔄 Tentative de refresh...\n";
        $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($newToken));
        echo "✅ Token refreshé\n";
    } else {
        echo "❌ Impossible de refresh le token\n";
        exit(1);
    }
} else {
    echo "✅ Token valide\n";
}

// Test API Calendar
$calendarService = new Calendar($client);

try {
    echo "🔍 Récupération des événements...\n";
    
    $timeMin = (new DateTime('-3 months'))->format(DateTime::RFC3339);
    $timeMax = (new DateTime('+12 months'))->format(DateTime::RFC3339);
    
    echo "📅 Plage: $timeMin à $timeMax\n";
    
    $events = $calendarService->events->listEvents('primary', [
        'timeMin' => (new DateTime('now'))->format(DateTime::RFC3339), // Événements à partir de maintenant
        'timeMax' => $timeMax,
        'maxResults' => 50,
        'singleEvents' => true,
        'orderBy' => 'startTime',
    ]);
    
    $items = $events->getItems();
    echo "📊 Nombre d'événements trouvés (futurs): " . count($items) . "\n";
    
    foreach ($items as $index => $event) {
        $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
        $title = $event->getSummary() ?: '(Sans titre)';
        $id = $event->getId();
        echo sprintf("%d. [%s] %s - %s\n", $index + 1, $id, $start, $title);
        
        // Afficher tous cette fois
        if ($index >= 19) {
            echo "... (et " . (count($items) - 20) . " autres)\n";
            break;
        }
    }
    
    echo "✅ Test réussi!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur API: " . $e->getMessage() . "\n";
    exit(1);
} 