<?php

namespace App\Command;

use App\Service\EventReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:send-24h-event-reminders',
    description: 'Envoie des rappels 24 heures avant les événements par email et notification.'
)]
class Send24hEventRemindersCommand extends Command
{
    public function __construct(
        private EventReminderService $eventReminderService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'create-only',
                'c',
                InputOption::VALUE_NONE,
                'Crée uniquement les rappels sans envoyer de notifications'
            )
            ->addOption(
                'send-only',
                's',
                InputOption::VALUE_NONE,
                'Envoie uniquement les notifications sans créer de nouveaux rappels'
            )
            ->addOption(
                'days-ahead',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Nombre de jours à l\'avance pour créer les rappels',
                7
            )
            ->setHelp('
Cette commande gère les rappels 24h avant les événements :
- Crée des rappels pour les événements à venir
- Envoie des emails et notifications aux utilisateurs
- Permet de séparer la création et l\'envoi des rappels

Exemples :
  php bin/console app:send-24h-event-reminders
  php bin/console app:send-24h-event-reminders --create-only
  php bin/console app:send-24h-event-reminders --send-only
  php bin/console app:send-24h-event-reminders --days-ahead=14
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $createOnly = $input->getOption('create-only');
        $sendOnly = $input->getOption('send-only');
        $daysAhead = (int) $input->getOption('days-ahead');

        $io->title('🔔 Gestion des rappels 24h avant événements');

        $startTime = microtime(true);
        $stats = [
            'created_reminders' => 0,
            'sent_notifications' => 0,
            'errors' => 0
        ];

        try {
            // 1. Création des rappels (sauf si --send-only)
            if (!$sendOnly) {
                $io->section('🔍 Création des rappels 24h pour les événements à venir...');
                
                $createdReminders = $this->eventReminderService->createRemindersForUpcomingEvents($daysAhead);
                $stats['created_reminders'] = count($createdReminders);
                
                if ($stats['created_reminders'] > 0) {
                    $io->success(sprintf('✅ %d rappel(s) 24h créé(s)', $stats['created_reminders']));
                    $this->displayCreatedReminders($io, $createdReminders);
                } else {
                    $io->info('ℹ️  Aucun nouveau rappel 24h à créer');
                }
            }

            // 2. Envoi des notifications (sauf si --create-only)
            if (!$createOnly) {
                $io->section('📧 Envoi des rappels 24h pour les événements de demain...');
                
                $sentReminders = $this->eventReminderService->sendDailyReminders();
                $stats['sent_notifications'] = count($sentReminders);
                
                if ($stats['sent_notifications'] > 0) {
                    $io->success(sprintf('✅ %d rappel(s) 24h envoyé(s)', $stats['sent_notifications']));
                    $this->displaySentReminders($io, $sentReminders);
                } else {
                    $io->info('ℹ️  Aucun rappel 24h à envoyer aujourd\'hui');
                }
            }

            // 3. Statistiques finales
            $this->displayStatistics($io, $stats, $startTime);

            $this->logger->info('Commande send-24h-event-reminders exécutée avec succès', [
                'create_only' => $createOnly,
                'send_only' => $sendOnly,
                'days_ahead' => $daysAhead,
                'stats' => $stats,
                'execution_time' => microtime(true) - $startTime
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du traitement des rappels 24h : ' . $e->getMessage());
            $this->logger->error('Erreur dans send-24h-event-reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    private function displayCreatedReminders(SymfonyStyle $io, array $reminders): void
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
                $reminder->getEvent()->getTitle(),
                $reminder->getType()
            ];
        }

        $io->table(
            ['ID', 'Titre', 'Utilisateur', 'Date rappel', 'Événement', 'Type'],
            $tableData
        );
    }

    private function displaySentReminders(SymfonyStyle $io, array $sentReminders): void
    {
        if (empty($sentReminders)) {
            return;
        }

        $tableData = [];
        foreach ($sentReminders as $reminder) {
            $tableData[] = [
                $reminder['event'],
                $reminder['user'],
                $reminder['type']
            ];
        }

        $io->table(
            ['Événement', 'Utilisateur', 'Type'],
            $tableData
        );
    }

    private function displayStatistics(SymfonyStyle $io, array $stats, float $startTime): void
    {
        $executionTime = microtime(true) - $startTime;
        
        $io->section('📊 Statistiques');
        
        $statsTable = [
            ['Rappels 24h créés', $stats['created_reminders']],
            ['Notifications envoyées', $stats['sent_notifications']],
            ['Erreurs', $stats['errors']],
            ['Temps d\'exécution', round($executionTime, 2) . ' secondes'],
            ['Mémoire utilisée', $this->formatBytes(memory_get_peak_usage(true))]
        ];

        $io->table(['Métrique', 'Valeur'], $statsTable);

        if ($stats['created_reminders'] > 0 || $stats['sent_notifications'] > 0) {
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
