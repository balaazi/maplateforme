<?php

require_once 'vendor/autoload.php';

use App\Entity\CalendarEvent;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== TEST SIMPLE CONFIRMATION PRÉSENCE ===\n\n";

// Créer le service GoogleCalendarService directement
$googleCalendarService = new GoogleCalendarService(
    new \Doctrine\Persistence\ManagerRegistry(),
    new \Symfony\Bundle\SecurityBundle\Security(),
    new \Psr\Log\NullLogger(),
    $_ENV['GOOGLE_CLIENT_ID'] ?? '',
    $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
    $_ENV['GOOGLE_REDIRECT_URI'] ?? '',
    __DIR__
);

echo "🔧 Service GoogleCalendarService créé\n";

// Vérifier l'authentification Google
echo "🔑 Authentification Google:\n";
echo "   isAuthenticated(): " . ($googleCalendarService->isAuthenticated() ? '✅ Oui' : '❌ Non') . "\n\n";

// Simuler un événement existant
$eventTitle = 'Formation python - Test Présence';
$eventDescription = 'Test de confirmation de présence avec ajout à l\'agenda';
$eventStart = new \DateTime('2025-09-08 18:00:00');
$eventDuration = 120; // 120 minutes
$eventEnd = (clone $eventStart)->modify('+' . $eventDuration . ' minutes');

echo "📅 Événement simulé:\n";
echo "   Titre: " . $eventTitle . "\n";
echo "   Date/Heure: " . $eventStart->format('Y-m-d H:i:s') . "\n";
echo "   Durée: " . $eventDuration . " minutes\n";
echo "   Fin calculée: " . $eventEnd->format('Y-m-d H:i:s') . "\n\n";

// Simuler la logique du contrôleur
$isPresent = true; // Simuler "Je serai présent"
$agendaAjoute = false;

echo "🧪 Test de la logique du contrôleur:\n";
echo "   isPresent: " . ($isPresent ? 'true' : 'false') . "\n";
echo "   googleCalendarService->isAuthenticated(): " . ($googleCalendarService->isAuthenticated() ? 'true' : 'false') . "\n";

if ($isPresent && $googleCalendarService->isAuthenticated()) {
    echo "   ✅ Conditions remplies, tentative d'ajout à l'agenda...\n";
    
    try {
        // Créer un événement de calendrier (comme dans le contrôleur)
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle($eventTitle);
        $calendarEvent->setDescription($eventDescription);
        $calendarEvent->setStart($eventStart);
        $calendarEvent->setEnd($eventEnd);
        
        echo "   📝 CalendarEvent créé:\n";
        echo "      Titre: " . $calendarEvent->getTitle() . "\n";
        echo "      Début: " . $calendarEvent->getStart()->format('Y-m-d H:i:s') . "\n";
        echo "      Fin: " . $calendarEvent->getEnd()->format('Y-m-d H:i:s') . "\n";
        
        // Exporter vers Google Calendar
        echo "   🚀 Export vers Google Calendar...\n";
        $googleCalendarService->exportToGoogleCalendar($calendarEvent);
        $agendaAjoute = true;
        
        echo "   ✅ Événement ajouté à l'agenda avec succès!\n";
        echo "   ID Google: " . $calendarEvent->getGoogleEventId() . "\n";
        
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
