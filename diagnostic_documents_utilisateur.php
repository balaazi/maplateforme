<?php
// diagnostic_documents_utilisateur.php - Diagnostic des documents utilisateur

require_once 'vendor/autoload.php';

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Document;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
try {
    if (file_exists('.env.local')) {
        $dotenv->load('.env.local');
    }
    if (file_exists('.env')) {
        $dotenv->load('.env');
    }
} catch (Exception $e) {
    // Continuer avec les valeurs par défaut
    echo "⚠️ Fichiers .env non trouvés, utilisation des valeurs par défaut\n";
}

// Configuration de la base de données
$config = \Doctrine\ORM\Tools\Setup::createAttributeMetadataConfiguration(
    [__DIR__ . '/src/Entity'],
    true
);

$connectionParams = [
    'driver'   => 'pdo_mysql',
    'host'     => $_ENV['DATABASE_HOST'] ?? 'localhost',
    'port'     => $_ENV['DATABASE_PORT'] ?? 3306,
    'dbname'   => $_ENV['DATABASE_NAME'] ?? 'eventhub',
    'user'     => $_ENV['DATABASE_USER'] ?? 'root',
    'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
    'charset'  => 'utf8mb4'
];

try {
    $entityManager = \Doctrine\ORM\EntityManager::create($connectionParams, $config);
    echo "✅ Connexion à la base de données établie\n\n";
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
    exit(1);
}

// Fonction pour diagnostiquer les documents d'un utilisateur
function diagnostiquerDocumentsUtilisateur($entityManager, $userEmail = null) {
    echo "=== DIAGNOSTIC DOCUMENTS UTILISATEUR ===\n\n";
    
    // Si aucun email spécifié, prendre le premier utilisateur trouvé
    if (!$userEmail) {
        $users = $entityManager->getRepository(User::class)->findAll();
        if (empty($users)) {
            echo "❌ Aucun utilisateur trouvé dans la base de données\n";
            return;
        }
        $user = $users[0];
        $userEmail = $user->getEmail();
        echo "🔍 Diagnostic pour le premier utilisateur trouvé: {$user->getPrenom()} {$user->getNom()} ({$userEmail})\n\n";
    } else {
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        if (!$user) {
            echo "❌ Utilisateur avec email '$userEmail' non trouvé\n";
            return;
        }
        echo "🔍 Diagnostic pour: {$user->getPrenom()} {$user->getNom()} ({$userEmail})\n\n";
    }

    // 1. Vérifier les participations de l'utilisateur
    echo "1. 📋 PARTICIPATIONS DE L'UTILISATEUR:\n";
    $participations = $entityManager->getRepository(Participation::class)->findBy(['user' => $user]);
    echo "   Nombre total de participations: " . count($participations) . "\n";
    
    if (empty($participations)) {
        echo "   ⚠️ Aucune participation trouvée - c'est pourquoi aucun document n'apparaît\n\n";
    } else {
        foreach ($participations as $participation) {
            $event = $participation->getEvent();
            if ($event) {
                echo "   - Événement: {$event->getTitle()} (ID: {$event->getId()})\n";
                echo "     Status: {$participation->getInvitationStatus()}\n";
                echo "     Documents: " . count($event->getDocuments()) . "\n";
            }
        }
        echo "\n";
    }

    // 2. Vérifier les événements créés par l'utilisateur
    echo "2. 🎯 ÉVÉNEMENTS CRÉÉS PAR L'UTILISATEUR:\n";
    $eventsCreated = $entityManager->getRepository(Event::class)->findBy(['createdBy' => $user]);
    echo "   Nombre d'événements créés: " . count($eventsCreated) . "\n";
    
    foreach ($eventsCreated as $event) {
        echo "   - Événement: {$event->getTitle()} (ID: {$event->getId()})\n";
        echo "     Documents: " . count($event->getDocuments()) . "\n";
    }
    echo "\n";

    // 3. Vérifier les événements organisés par l'utilisateur
    echo "3. 🎪 ÉVÉNEMENTS ORGANISÉS PAR L'UTILISATEUR:\n";
    $eventsOrganized = $entityManager->getRepository(Event::class)->findBy(['organizer' => $user]);
    echo "   Nombre d'événements organisés: " . count($eventsOrganized) . "\n";
    
    foreach ($eventsOrganized as $event) {
        echo "   - Événement: {$event->getTitle()} (ID: {$event->getId()})\n";
        echo "     Documents: " . count($event->getDocuments()) . "\n";
    }
    echo "\n";

    // 4. Collecter tous les événements accessibles
    $allEventsIds = [];
    $allEvents = [];
    
    // Événements participants
    foreach ($participations as $participation) {
        $event = $participation->getEvent();
        if ($event && !in_array($event->getId(), $allEventsIds)) {
            $allEventsIds[] = $event->getId();
            $allEvents[] = $event;
        }
    }
    
    // Événements créés
    foreach ($eventsCreated as $event) {
        if (!in_array($event->getId(), $allEventsIds)) {
            $allEventsIds[] = $event->getId();
            $allEvents[] = $event;
        }
    }
    
    // Événements organisés
    foreach ($eventsOrganized as $event) {
        if (!in_array($event->getId(), $allEventsIds)) {
            $allEventsIds[] = $event->getId();
            $allEvents[] = $event;
        }
    }

    echo "4. 📊 RÉSUMÉ DES ÉVÉNEMENTS ACCESSIBLES:\n";
    echo "   Total événements accessibles: " . count($allEvents) . "\n\n";

    // 5. Collecter tous les documents
    $allDocuments = [];
    foreach ($allEvents as $event) {
        $eventDocuments = $event->getDocuments();
        foreach ($eventDocuments as $document) {
            $allDocuments[] = $document;
        }
    }

    echo "5. 📄 DOCUMENTS ACCESSIBLES:\n";
    echo "   Total documents: " . count($allDocuments) . "\n";
    
    if (empty($allDocuments)) {
        echo "   ⚠️ Aucun document trouvé - Raisons possibles:\n";
        echo "      - Aucun événement avec documents uploadés\n";
        echo "      - Problème avec VichUploader\n";
        echo "      - Documents supprimés\n\n";
    } else {
        foreach ($allDocuments as $document) {
            echo "   - Document: {$document->getFileName()}\n";
            echo "     Événement: {$document->getEvent()->getTitle()}\n";
            echo "     Créé le: " . $document->getCreatedAt()->format('d/m/Y H:i') . "\n";
            echo "     Chemin: " . ($document->getFileName() ? "uploads/documents/" . $document->getFileName() : "Non défini") . "\n\n";
        }
    }

    // 6. Vérifier l'existence physique des fichiers
    echo "6. 💾 VÉRIFICATION DES FICHIERS PHYSIQUES:\n";
    $documentsDir = 'public/uploads/documents/';
    
    if (!is_dir($documentsDir)) {
        echo "   ❌ Répertoire '$documentsDir' n'existe pas\n";
    } else {
        echo "   ✅ Répertoire '$documentsDir' existe\n";
        $files = scandir($documentsDir);
        $files = array_filter($files, function($file) {
            return $file !== '.' && $file !== '..';
        });
        echo "   Fichiers physiques trouvés: " . count($files) . "\n";
        
        foreach ($files as $file) {
            echo "   - $file\n";
        }
    }
    echo "\n";

    // 7. Recommandations
    echo "7. 💡 RECOMMANDATIONS:\n";
    if (empty($participations) && empty($eventsCreated) && empty($eventsOrganized)) {
        echo "   🎯 Créer ou participer à des événements avec des documents\n";
    }
    if (count($allEvents) > 0 && empty($allDocuments)) {
        echo "   📤 Uploader des documents lors de la création d'événements\n";
    }
    if (!empty($allDocuments) && !is_dir($documentsDir)) {
        echo "   📁 Créer le répertoire de stockage des documents\n";
    }
    
    echo "\n=== FIN DU DIAGNOSTIC ===\n";
}

// Exécuter le diagnostic
try {
    // Vous pouvez spécifier un email d'utilisateur spécifique ici
    // diagnostiquerDocumentsUtilisateur($entityManager, 'email@example.com');
    
    // Ou laisser vide pour diagnostiquer le premier utilisateur trouvé
    diagnostiquerDocumentsUtilisateur($entityManager);
    
} catch (Exception $e) {
    echo "❌ Erreur lors du diagnostic: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
