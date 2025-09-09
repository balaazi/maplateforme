<?php
/**
 * Script de test pour les rappels 24h avant événements
 * 
 * Ce script permet de tester le fonctionnement des rappels 24h
 * sans attendre l'exécution planifiée.
 */

// Charger l'environnement Symfony
require dirname(__FILE__).'/vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__FILE__).'/.env');

// Obtenir le kernel Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer le service de rappels
$eventReminderService = $container->get(\App\Service\EventReminderService::class);
$logger = $container->get('logger');

echo "=================================================\n";
echo "   TEST DES RAPPELS 24H AVANT ÉVÉNEMENTS\n";
echo "=================================================\n\n";

// 1. Créer des rappels pour les événements à venir
echo "1. Création des rappels 24h pour les événements à venir...\n";
$createdReminders = $eventReminderService->createRemindersForUpcomingEvents(7);
echo "   ✅ " . count($createdReminders) . " rappel(s) créé(s)\n\n";

// 2. Simuler l'envoi des rappels pour les événements de demain
echo "2. Simulation de l'envoi des rappels 24h...\n";
$sentReminders = $eventReminderService->sendDailyReminders();
echo "   ✅ " . count($sentReminders) . " rappel(s) envoyé(s)\n\n";

// Afficher les détails des rappels envoyés
if (count($sentReminders) > 0) {
    echo "Détails des rappels envoyés :\n";
    echo str_repeat('-', 60) . "\n";
    echo sprintf("%-30s %-20s %-10s\n", "Événement", "Utilisateur", "Type");
    echo str_repeat('-', 60) . "\n";
    
    foreach ($sentReminders as $reminder) {
        echo sprintf("%-30s %-20s %-10s\n", 
            substr($reminder['event'], 0, 28), 
            substr($reminder['user'], 0, 18), 
            $reminder['type']
        );
    }
    echo str_repeat('-', 60) . "\n\n";
}

echo "Test terminé avec succès!\n";
echo "=================================================\n";

// Enregistrer les résultats dans le log
$logger->info('Test des rappels 24h exécuté', [
    'created_reminders' => count($createdReminders),
    'sent_reminders' => count($sentReminders)
]);
