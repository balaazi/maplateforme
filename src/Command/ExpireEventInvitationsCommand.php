<?php

namespace App\Command;

use App\Entity\Event;
use App\Enum\InvitationStatus;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:expire-event-invitations',
    description: 'Expire les invitations des événements passés',
)]
class ExpireEventInvitationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Nombre de jours dans le passé à vérifier', 30)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Exécuter sans modifier la base de données')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        $io->title('Expiration des invitations pour événements passés');
        $io->text("Vérification des événements passés (jusqu'à {$days} jours)");

        // Trouver les événements passés
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $fromDate = (clone $now)->modify("-{$days} days");
        $toDate = $now;

        $events = $this->eventRepository->findEventsEndedBetween($fromDate, $toDate);
        $eventCount = count($events);

        if ($eventCount === 0) {
            $io->success('Aucun événement passé trouvé dans la période spécifiée.');
            return Command::SUCCESS;
        }

        $io->text("Trouvé {$eventCount} événements passés.");
        $io->progressStart($eventCount);

        $totalInvitations = 0;
        $expiredInvitations = 0;

        foreach ($events as $event) {
            $io->progressAdvance();

            $invitations = $event->getInvitations();
            $totalInvitations += count($invitations);
            $eventExpiredCount = 0;

            foreach ($invitations as $invitation) {
                if ($invitation->getStatus() === InvitationStatus::PENDING->value) {
                    $eventExpiredCount++;
                    
                    if (!$dryRun) {
                        $invitation->setStatus(InvitationStatus::EXPIRED->value);
                        $invitation->setUpdatedAt(new \DateTime());
                    }
                }
            }

            $expiredInvitations += $eventExpiredCount;

            if ($eventExpiredCount > 0 && !$dryRun) {
                $this->logger->info("Invitations expirées pour événement passé", [
                    'event_id' => $event->getId(),
                    'event_title' => $event->getTitle(),
                    'expired_count' => $eventExpiredCount
                ]);
            }
        }

        if (!$dryRun && $expiredInvitations > 0) {
            $this->entityManager->flush();
        }

        $io->progressFinish();

        $actionText = $dryRun ? 'à expirer' : 'expirées';
        $io->success("Terminé ! {$expiredInvitations} invitations {$actionText} sur {$totalInvitations} invitations totales.");

        return Command::SUCCESS;
    }
}
