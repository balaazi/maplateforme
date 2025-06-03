<?php

namespace App\Command;

use App\Service\GoogleCalendarService;
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

    public function __construct(GoogleCalendarService $calendarService)
    {
        parent::__construct();
        $this->calendarService = $calendarService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Synchronisation avec Google Calendar');

        try {
            $result = $this->calendarService->synchronizeCalendars();

            $io->success('✅ Synchronisation terminée avec succès !');
            $io->text("📥 Importés : {$result['imported']}");
            $io->text("📤 Exportés : {$result['exported']}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('❌ Erreur de synchronisation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
