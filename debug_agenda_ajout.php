<?php

require_once 'vendor/autoload.php';

use App\Entity\CalendarEvent;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== DEBUG AJOUT ÉVÉNEMENT À L'AGENDA ===\n\n";

// Simuler un événement de test
$event = new CalendarEvent();
$event->setTitle('Formation python - Test Debug');
$event->setDescription('Test d\'ajout automatique à l\'agenda');
$event->setStart(new \DateTime('2025-09-08 18:00:00'));
$event->setEnd(new \DateTime('2025-09-08 20:00:00'));

echo "📅 Événement de test créé:\n";
echo "   Titre: " . $event->getTitle() . "\n";
echo "   Début: " . $event->getStart()->format('Y-m-d H:i:s') . "\n";
echo "   Fin: " . $event->getEnd()->format('Y-m-d H:i:s') . "\n\n";

// Vérifier la configuration Google
echo "🔧 Configuration Google Calendar:\n";
echo "   GOOGLE_CLIENT_ID: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (isset($_ENV['GOOGLE_CLIENT_SECRET']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . (isset($_ENV['GOOGLE_REDIRECT_URI']) ? '✅ Défini' : '❌ Manquant') . "\n\n";

// Vérifier le token
$tokenPath = 'var/google-token.json';
echo "🔑 Token Google Calendar:\n";
if (file_exists($tokenPath)) {
    echo "   Fichier token: ✅ Existe\n";
    $token = json_decode(file_get_contents($tokenPath), true);
    if ($token && isset($token['access_token'])) {
        echo "   Token valide: ✅ Oui\n";
        echo "   Type: " . ($token['token_type'] ?? 'N/A') . "\n";
        echo "   Expires: " . (isset($token['expires_in']) ? $token['expires_in'] . 's' : 'N/A') . "\n";
    } else {
        echo "   Token valide: ❌ Non\n";
    }
} else {
    echo "   Fichier token: ❌ N'existe pas\n";
}

echo "\n";

// Test de connexion Google Calendar
try {
    $client = new \Google\Client();
    $client->setApplicationName('MaPlateforme');
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
    $client->addScope(\Google\Service\Calendar::CALENDAR);
    $client->addScope(\Google\Service\Calendar::CALENDAR_EVENTS);
    $client->setAccessType('offline');
    
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        if ($accessToken && isset($accessToken['access_token'])) {
            $client->setAccessToken($accessToken);
            
            if ($client->isAccessTokenExpired()) {
                echo "⚠️ Token expiré, tentative de refresh...\n";
                if ($client->getRefreshToken()) {
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
        }
    }
    
    $calendarService = new \Google\Service\Calendar($client);
    
    // Test d'ajout d'événement
    echo "\n🧪 Test d'ajout d'événement:\n";
    
    $googleEvent = new \Google\Service\Calendar\Event([
        'summary' => $event->getTitle(),
        'description' => $event->getDescription(),
        'start' => ['dateTime' => $event->getStart()->format(\DateTimeInterface::RFC3339)],
        'end' => ['dateTime' => $event->getEnd()->format(\DateTimeInterface::RFC3339)],
    ]);
    
    echo "   Événement Google créé:\n";
    echo "   Summary: " . $googleEvent->getSummary() . "\n";
    echo "   Start: " . $googleEvent->getStart()->getDateTime() . "\n";
    echo "   End: " . $googleEvent->getEnd()->getDateTime() . "\n\n";
    
    echo "   Tentative d'insertion...\n";
    $createdEvent = $calendarService->events->insert('primary', $googleEvent);
    
    echo "✅ Événement ajouté avec succès!\n";
    echo "   ID Google: " . $createdEvent->getId() . "\n";
    echo "   URL: " . $createdEvent->getHtmlLink() . "\n";
    
    // Nettoyer - supprimer l'événement de test
    echo "\n🧹 Nettoyage...\n";
    $calendarService->events->delete('primary', $createdEvent->getId());
    echo "✅ Événement de test supprimé\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur lors du test:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DU DEBUG ===\n";
