<?php

namespace App\Command;

use App\Service\ReminderService;
use App\Service\RealtimeNotificationService;
use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:process-reminders',
    description: 'Traite automatiquement les rappels en attente et envoie les notifications',
)]
class ProcessRemindersCommand extends Command
{
    public function __construct(
        private ReminderService $reminderService,
        private RealtimeNotificationService $realtimeNotificationService,
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Affiche les rappels qui seraient traités sans les exécuter réellement'
            )
            ->addOption(
                'minutes-ahead',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Nombre de minutes à l\'avance pour traiter les rappels',
                5
            )
            ->addOption(
                'cleanup',
                'c',
                InputOption::VALUE_NONE,
                'Nettoie également les anciens rappels et notifications'
            )
            ->setHelp('
Cette commande traite automatiquement tous les rappels en attente :
- Vérifie les rappels qui doivent être déclenchés
- Envoie les notifications et emails
- Marque les rappels comme traités
- Optionnellement nettoie les anciens rappels

Exemples :
  php bin/console app:process-reminders
  php bin/console app:process-reminders --dry-run
  php bin/console app:process-reminders --minutes-ahead=10 --cleanup
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $minutesAhead = (int) $input->getOption('minutes-ahead');
        $cleanup = $input->getOption('cleanup');

        $io->title('Traitement des rappels automatiques');

        if ($dryRun) {
            $io->note('Mode test activé - aucune action ne sera effectuée');
        }

        $startTime = microtime(true);
        $stats = [
            'processed_reminders' => 0,
            'sent_emails' => 0,
            'sent_notifications' => 0,
            'errors' => 0,
            'cleaned_items' => 0
        ];

        try {
            // 1. Traitement des rappels en attente
            $io->section('🔍 Recherche des rappels en attente...');
            
            if (!$dryRun) {
                $processedReminders = $this->reminderService->processPendingReminders();
                $stats['processed_reminders'] = count($processedReminders);
                
                if ($stats['processed_reminders'] > 0) {
                    $io->success(sprintf('✅ %d rappel(s) traité(s)', $stats['processed_reminders']));
                    
                    // Afficher les détails des rappels traités
                    $this->displayProcessedReminders($io, $processedReminders);
                } else {
                    $io->info('ℹ️  Aucun rappel à traiter');
                }
            } else {
                // Mode dry-run : afficher ce qui serait traité
                $pendingReminders = $this->reminderService->getAllUpcomingReminders($minutesAhead);
                $this->displayUpcomingReminders($io, $pendingReminders);
            }

            // 2. Traitement des notifications en temps réel
            $io->section('🔔 Traitement des notifications temps réel...');
            
            if (!$dryRun) {
                $realtimeReminders = $this->realtimeNotificationService->checkAndProcessReminders();
                $stats['sent_notifications'] = count($realtimeReminders);
                
                if ($stats['sent_notifications'] > 0) {
                    $io->success(sprintf('✅ %d notification(s) temps réel envoyée(s)', $stats['sent_notifications']));
                } else {
                    $io->info('ℹ️  Aucune notification temps réel à envoyer');
                }
            }

            // 3. Nettoyage (optionnel)
            if ($cleanup) {
                $io->section('🧹 Nettoyage des anciens éléments...');
                
                if (!$dryRun) {
                    $cleanedReminders = $this->reminderService->cleanupOldReminders(30);
                    $cleanedNotifications = $this->realtimeNotificationService->cleanupNotificationQueues(30);
                    $stats['cleaned_items'] = $cleanedReminders + $cleanedNotifications;
                    
                    $io->success(sprintf(
                        '✅ %d élément(s) nettoyé(s) (%d rappels + %d notifications)',
                        $stats['cleaned_items'],
                        $cleanedReminders,
                        $cleanedNotifications
                    ));
                } else {
                    $io->info('ℹ️  Le nettoyage serait effectué (anciens rappels et notifications)');
                }
            }

            // 4. Statistiques finales
            $this->displayStatistics($io, $stats, $startTime, $dryRun);

            $this->logger->info('Commande process-reminders exécutée avec succès', [
                'dry_run' => $dryRun,
                'stats' => $stats,
                'execution_time' => microtime(true) - $startTime
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du traitement des rappels : ' . $e->getMessage());
            $this->logger->error('Erreur dans process-reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    private function displayProcessedReminders(SymfonyStyle $io, array $reminders): void
    {
        if (empty($reminders)) {
            return;
        }

        $tableData = [];
        foreach ($reminders as $reminder) {
            $tableData[] = [
                $reminder->getId(),
                $reminder->getTitle(),
                $reminder->getUser()->getEmail(),
                $reminder->getDueDate()->format('d/m/Y H:i'),
                $reminder->getType(),
                $reminder->getPriority()
            ];
        }

        $io->table(
            ['ID', 'Titre', 'Utilisateur', 'Échéance', 'Type', 'Priorité'],
            $tableData
        );
    }

    private function displayUpcomingReminders(SymfonyStyle $io, array $reminders): void
    {
        if (empty($reminders)) {
            $io->info('ℹ️  Aucun rappel à venir dans les prochaines minutes');
            return;
        }

        $io->note(sprintf('📋 %d rappel(s) seraient traités :', count($reminders)));
        
        $tableData = [];
        foreach ($reminders as $reminder) {
            $tableData[] = [
                $reminder->getId(),
                $reminder->getTitle(),
                $reminder->getUser()->getEmail(),
                $reminder->getDueDate()->format('d/m/Y H:i'),
                $reminder->shouldTrigger() ? '✅ Oui' : '⏳ Pas encore',
                $reminder->getPriority()
            ];
        }

        $io->table(
            ['ID', 'Titre', 'Utilisateur', 'Échéance', 'À traiter', 'Priorité'],
            $tableData
        );
    }

    private function displayStatistics(SymfonyStyle $io, array $stats, float $startTime, bool $dryRun): void
    {
        $executionTime = microtime(true) - $startTime;
        
        $io->section('📊 Statistiques ' . ($dryRun ? '(Mode test)' : ''));
        
        $statsTable = [
            ['Rappels traités', $stats['processed_reminders']],
            ['Notifications envoyées', $stats['sent_notifications']],
            ['Éléments nettoyés', $stats['cleaned_items']],
            ['Erreurs', $stats['errors']],
            ['Temps d\'exécution', round($executionTime, 2) . ' secondes'],
            ['Mémoire utilisée', $this->formatBytes(memory_get_peak_usage(true))]
        ];

        $io->table(['Métrique', 'Valeur'], $statsTable);

        if (!$dryRun && ($stats['processed_reminders'] > 0 || $stats['sent_notifications'] > 0)) {
            $io->success('🎉 Traitement terminé avec succès !');
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 