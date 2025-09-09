<?php

require_once 'vendor/autoload.php';

use App\Entity\CalendarEvent;
use App\Entity\Event;
use App\Entity\User;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== TEST SIMULATION CONFIRMATION PRÉSENCE ===\n\n";

// Créer le kernel Symfony pour accéder aux services
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer les services
$entityManager = $container->get('doctrine.orm.entity_manager');
$googleCalendarService = $container->get(GoogleCalendarService::class);

echo "🔧 Services récupérés:\n";
echo "   EntityManager: " . (($entityManager instanceof EntityManagerInterface) ? '✅' : '❌') . "\n";
echo "   GoogleCalendarService: " . (($googleCalendarService instanceof GoogleCalendarService) ? '✅' : '❌') . "\n\n";

// Vérifier l'authentification Google
echo "🔑 Authentification Google:\n";
echo "   isAuthenticated(): " . ($googleCalendarService->isAuthenticated() ? '✅ Oui' : '❌ Non') . "\n\n";

// Simuler un événement existant
$event = new Event();
$event->setTitle('Formation python - Test Présence');
$event->setDescription('Test de confirmation de présence avec ajout à l\'agenda');
$event->setDateHeure(new \DateTime('2025-09-08 18:00:00'));
$event->setDuree(120); // 120 minutes

echo "📅 Événement simulé:\n";
echo "   Titre: " . $event->getTitle() . "\n";
echo "   Date/Heure: " . $event->getDateHeure()->format('Y-m-d H:i:s') . "\n";
echo "   Durée: " . $event->getDuree() . " minutes\n";
echo "   Fin calculée: " . (clone $event->getDateHeure())->modify('+' . $event->getDuree() . ' minutes')->format('Y-m-d H:i:s') . "\n\n";

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
        $calendarEvent->setTitle($event->getTitle());
        $calendarEvent->setDescription($event->getDescription());
        $calendarEvent->setStart($event->getDateHeure());
        
        // Calculer l'heure de fin en fonction de la durée
        $end = (clone $event->getDateHeure())->modify('+' . $event->getDuree() . ' minutes');
        $calendarEvent->setEnd($end);
        
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
