<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Service\AutoArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

    #[AsCommand(
        name: 'app:archive-expired-events',
        description: 'Archive automatiquement les événements dont la date est dépassée depuis plus d\'un jour'
    )]
class ArchiveExpiredEventsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private AutoArchiveService $autoArchiveService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande archive automatiquement les événements dont la date est dépassée depuis plus d\'un jour.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Archivage automatique des événements expirés');

        // Utiliser le service d'archivage automatique
        $archivedCount = $this->autoArchiveService->archiveCompletedEvents();

        if ($archivedCount === 0) {
            $io->success('Aucun événement expiré à archiver.');
            return Command::SUCCESS;
        }

        $io->success(sprintf('%d événement(s) expiré(s) archivé(s) avec succès.', $archivedCount));

        return Command::SUCCESS;
    }
} 