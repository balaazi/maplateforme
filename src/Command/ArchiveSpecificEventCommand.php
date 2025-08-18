<?php

namespace App\Command;

use App\Service\AutoArchiveService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:archive-event',
    description: 'Archive un événement spécifique par son ID'
)]
class ArchiveSpecificEventCommand extends Command
{
    public function __construct(
        private AutoArchiveService $autoArchiveService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('event_id', InputArgument::REQUIRED, 'ID de l\'événement à archiver')
            ->setHelp('Cette commande archive un événement spécifique par son ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $eventId = (int) $input->getArgument('event_id');

        $io->title("Archivage de l'événement #{$eventId}");

        try {
            $success = $this->autoArchiveService->archiveEventById($eventId);

            if ($success) {
                $io->success("L'événement #{$eventId} a été archivé avec succès.");
                return Command::SUCCESS;
            } else {
                $io->error("Impossible d'archiver l'événement #{$eventId}. Vérifiez que l'événement existe et n'est pas déjà archivé.");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error("Erreur lors de l'archivage : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 