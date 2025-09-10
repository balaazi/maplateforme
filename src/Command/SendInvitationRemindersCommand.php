<?php

namespace App\Command;

use App\Service\InvitationReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-invitation-reminders',
    description: 'Envoie des rappels automatiques 24h et 1h avant les événements UNIQUEMENT aux personnes à qui une invitation a été envoyée'
)]
class SendInvitationRemindersCommand extends Command
{
    public function __construct(
        private InvitationReminderService $invitationReminderService
    ) {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->addOption(
                'reminder-type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type de rappel à envoyer (24h, 1h, both)',
                'both'
            )
            ->addOption(
                'force-date',
                'f',
                InputOption::VALUE_REQUIRED,
                'Force l\'envoi des rappels pour une date spécifique (format: Y-m-d)'
            )
            ->addOption(
                'test-mode',
                null,
                InputOption::VALUE_NONE,
                'Mode test - n\'envoie pas réellement les emails'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche seulement les rappels qui seraient envoyés sans les envoyer'
            )
            ->addOption(
                'stats',
                's',
                InputOption::VALUE_NONE,
                'Affiche les statistiques des invitations'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Système de Rappels d\'Invitations - EventHub');
        
        $reminderType = $input->getOption('reminder-type');
        $testMode = $input->getOption('test-mode');
        $dryRun = $input->getOption('dry-run');
        $forceDate = $input->getOption('force-date');
        $showStats = $input->getOption('stats');
        
        if ($testMode || $dryRun) {
            $io->warning('⚠️ Mode ' . ($testMode ? 'test' : 'dry-run') . ' activé - Les emails ne seront pas réellement envoyés');
        }
        
        // Afficher les statistiques si demandé
        if ($showStats) {
            $this->displayStats($io);
        }
        
        // Validation du type de rappel
        if (!in_array($reminderType, ['24h', '1h', 'both'])) {
            $io->error('Type de rappel invalide. Utilisez: 24h, 1h, ou both');
            return Command::FAILURE;
        }
        
        $totalReminders = 0;
        $stats = [
            '24h' => ['events' => 0, 'reminders' => 0, 'errors' => 0],
            '1h' => ['events' => 0, 'reminders' => 0, 'errors' => 0]
        ];
        
        // Traitement des rappels 24h
        if ($reminderType === '24h' || $reminderType === 'both') {
            $io->section('📅 Traitement des rappels 24h avant');
            $result24h = $this->processReminders($io, '24h', $forceDate, $testMode, $dryRun);
            $stats['24h'] = $result24h;
            $totalReminders += $result24h['reminders'];
        }
        
        // Traitement des rappels 1h
        if ($reminderType === '1h' || $reminderType === 'both') {
            $io->section('⏰ Traitement des rappels 1h avant');
            $result1h = $this->processReminders($io, '1h', $forceDate, $testMode, $dryRun);
            $stats['1h'] = $result1h;
            $totalReminders += $result1h['reminders'];
        }
        
        // Affichage du résumé
        $io->section('📊 Résumé des rappels d\'invitations');
        $io->table(
            ['Type', 'Événements', 'Rappels envoyés', 'Erreurs'],
            [
                ['24h avant', $stats['24h']['events'], $stats['24h']['reminders'], $stats['24h']['errors']],
                ['1h avant', $stats['1h']['events'], $stats['1h']['reminders'], $stats['1h']['errors']],
                ['TOTAL', $stats['24h']['events'] + $stats['1h']['events'], $totalReminders, $stats['24h']['errors'] + $stats['1h']['errors']]
            ]
        );
        
        if ($totalReminders > 0) {
            $io->success(sprintf('✅ Processus terminé: %d rappel(s) d\'invitation envoyé(s) au total', $totalReminders));
        } else {
            $io->info('ℹ️  Aucun rappel d\'invitation à envoyer pour cette période');
        }

        return Command::SUCCESS;
    }
    
    private function processReminders(SymfonyStyle $io, string $type, ?string $forceDate, bool $testMode, bool $dryRun): array
    {
        $hoursBefore = $type === '24h' ? 24 : 1;
        
        try {
            $parsedDate = $forceDate ? new \DateTime($forceDate) : null;
            $results = $this->invitationReminderService->sendRemindersForTimeRange($type, $parsedDate);
        } catch (\Exception $e) {
            $io->error('Erreur lors du traitement des rappels: ' . $e->getMessage());
            return ['events' => 0, 'reminders' => 0, 'errors' => 1];
        }
        
        if (empty($results['details'])) {
            $io->text(sprintf('Aucun événement trouvé pour les rappels %s', $type));
            return ['events' => 0, 'reminders' => 0, 'errors' => 0];
        }
        
        $io->text(sprintf('Traitement de %d événement(s) pour rappels %s', $results['events_processed'], $type));
        
        foreach ($results['details'] as $detail) {
            $io->text(sprintf('   📅 %s: %d rappel(s) envoyé(s)', $detail['event'], $detail['reminders_sent']));
            if ($detail['errors'] > 0) {
                $io->text(sprintf('      ⚠️  %d erreur(s)', $detail['errors']));
            }
        }
        
        return [
            'events' => $results['events_processed'],
            'reminders' => $results['reminders_sent'],
            'errors' => $results['errors']
        ];
    }
    
    private function displayStats(SymfonyStyle $io): void
    {
        $io->section('📊 Statistiques des invitations');
        
        try {
            $stats = $this->invitationReminderService->getReminderStats();
            
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Total', $stats['total_invitations']],
                    ['En attente', $stats['pending']],
                    ['Acceptées', $stats['accepted']],
                    ['Déclinées', $stats['declined']],
                    ['Expirées', $stats['expired']]
                ]
            );
        } catch (\Exception $e) {
            $io->error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
        }
    }
}
