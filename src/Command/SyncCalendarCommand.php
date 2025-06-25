<?php

namespace App\Command;

use App\Service\GoogleCalendarService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-calendar',
    description: 'Synchronise les événements entre Google Calendar et la base de données locale.',
)]
class SyncCalendarCommand extends Command
{
    private GoogleCalendarService $calendarService;
    private LoggerInterface $logger;

    public function __construct(GoogleCalendarService $calendarService, LoggerInterface $logger)
    {
        parent::__construct();
        $this->calendarService = $calendarService;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Synchronisation avec Google Calendar');
        
        // Debug: Log le début de la commande
        $this->logger->info("=== Début de la commande sync-calendar ===");

        try {
            $this->logger->info("=== Avant appel synchronizeCalendars ===");
            $result = $this->calendarService->synchronizeCalendars();
            $this->logger->info("=== Après appel synchronizeCalendars ===", $result);

            $io->success('✅ Synchronisation terminée avec succès !');
            $io->text("📥 Importés : {$result['imported']}");
            $io->text("📤 Exportés : {$result['exported']}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error("=== ERREUR dans la commande ===", ['error' => $e->getMessage()]);
            $io->error('❌ Erreur de synchronisation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
