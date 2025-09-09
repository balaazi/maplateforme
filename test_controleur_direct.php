<?php

require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;

// Charger les variables d'environnement
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

echo "=== TEST DIRECT DU CODE DU CONTRÔLEUR ===\n\n";

// Configuration Google (comme dans GoogleCalendarService)
$client = new Client();
$client->setApplicationName('MaPlateforme');
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
$client->addScope(Calendar::CALENDAR);
$client->addScope(Calendar::CALENDAR_EVENTS);
$client->setAccessType('offline');
$client->setPrompt('consent');

$tokenPath = 'var/google-token.json';

// Charger le token
if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    if ($accessToken && isset($accessToken['access_token'])) {
        $client->setAccessToken($accessToken);
        if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($newToken));
        }
    }
}

echo "🔧 Configuration Google:\n";
echo "   Client ID: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   Client Secret: " . (isset($_ENV['GOOGLE_CLIENT_SECRET']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   Redirect URI: " . (isset($_ENV['GOOGLE_REDIRECT_URI']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   Token file: " . (file_exists($tokenPath) ? '✅ Existe' : '❌ N\'existe pas') . "\n";
echo "   Access Token: " . ($client->getAccessToken() ? '✅ Défini' : '❌ Manquant') . "\n\n";

// Simuler les données de l'événement
$eventTitle = 'Formation python - Test Direct';
$eventDescription = 'Test direct de confirmation de présence';
$eventStart = new \DateTime('2025-09-08 18:00:00');
$eventDuration = 120; // 120 minutes
$eventEnd = (clone $eventStart)->modify('+' . $eventDuration . ' minutes');

echo "📅 Événement simulé:\n";
echo "   Titre: " . $eventTitle . "\n";
echo "   Début: " . $eventStart->format('Y-m-d H:i:s') . "\n";
echo "   Fin: " . $eventEnd->format('Y-m-d H:i:s') . "\n\n";

// Simuler la logique exacte du contrôleur
$isPresent = true;
$agendaAjoute = false;

echo "🧪 Simulation du code du contrôleur:\n";
echo "   isPresent: " . ($isPresent ? 'true' : 'false') . "\n";
echo "   isAuthenticated(): " . ($client->getAccessToken() ? 'true' : 'false') . "\n";

if ($isPresent && $client->getAccessToken()) {
    echo "   ✅ Conditions remplies, tentative d'ajout à l'agenda...\n";
    
    try {
        // Créer un événement de calendrier (comme dans le contrôleur)
        $calendarEvent = new \App\Entity\CalendarEvent();
        $calendarEvent->setTitle($eventTitle);
        $calendarEvent->setDescription($eventDescription);
        $calendarEvent->setStart($eventStart);
        $calendarEvent->setEnd($eventEnd);
        
        echo "   📝 CalendarEvent créé:\n";
        echo "      Titre: " . $calendarEvent->getTitle() . "\n";
        echo "      Début: " . $calendarEvent->getStart()->format('Y-m-d H:i:s') . "\n";
        echo "      Fin: " . $calendarEvent->getEnd()->format('Y-m-d H:i:s') . "\n";
        
        // Créer l'événement Google (comme dans exportToGoogleCalendar)
        $googleEvent = new GoogleEvent([
            'summary' => $calendarEvent->getTitle(),
            'description' => $calendarEvent->getDescription(),
            'start' => ['dateTime' => $calendarEvent->getStart()->format(\DateTimeInterface::RFC3339)],
            'end' => ['dateTime' => $calendarEvent->getEnd()->format(\DateTimeInterface::RFC3339)],
        ]);
        
        echo "   📝 GoogleEvent créé:\n";
        echo "      Summary: " . $googleEvent->getSummary() . "\n";
        echo "      Start: " . $googleEvent->getStart()->getDateTime() . "\n";
        echo "      End: " . $googleEvent->getEnd()->getDateTime() . "\n";
        
        // Insérer dans Google Calendar
        $calendarService = new Calendar($client);
        echo "   🚀 Insertion dans Google Calendar...\n";
        $createdEvent = $calendarService->events->insert('primary', $googleEvent);
        
        $googleEventId = $createdEvent->getId();
        $calendarEvent->setGoogleEventId($googleEventId);
        
        $agendaAjoute = true;
        
        echo "   ✅ Événement ajouté à l'agenda avec succès!\n";
        echo "   ID Google: " . $googleEventId . "\n";
        echo "   URL: " . $createdEvent->getHtmlLink() . "\n";
        
        // Nettoyer - supprimer l'événement de test
        echo "   🧹 Nettoyage...\n";
        $calendarService->events->delete('primary', $googleEventId);
        echo "   ✅ Événement de test supprimé\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors de l'ajout à l'agenda:\n";
        echo "      Message: " . $e->getMessage() . "\n";
        echo "      Code: " . $e->getCode() . "\n";
        echo "      Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "      Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "   ❌ Conditions non remplies pour l'ajout à l'agenda\n";
}

echo "\n📊 Résultat final:\n";
echo "   isPresent: " . ($isPresent ? 'true' : 'false') . "\n";
echo "   agendaAjoute: " . ($agendaAjoute ? 'true' : 'false') . "\n";

$message = $isPresent ? 'Présence confirmée' . ($agendaAjoute ? ' et événement ajouté à votre agenda' : '') : 'Absence confirmée';
echo "   Message: " . $message . "\n";

echo "\n=== FIN DU TEST ===\n";
