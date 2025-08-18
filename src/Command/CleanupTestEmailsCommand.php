<?php

namespace App\Command;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-test-emails',
    description: 'Nettoie les notifications de test et garde seulement les rappels automatiques'
)]
class CleanupTestEmailsCommand extends Command
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager
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
                'Affiche ce qui serait supprimé sans effectuer la suppression'
            )
            ->setHelp('
Cette commande nettoie les notifications de test :
- Supprime les notifications avec "Test" dans le titre
- Garde seulement les rappels automatiques
- Nettoie les notifications obsolètes

Exemples :
  php bin/console app:cleanup-test-emails
  php bin/console app:cleanup-test-emails --dry-run
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('🧹 Nettoyage des notifications de test');

        if ($dryRun) {
            $io->note('Mode test activé - aucune suppression ne sera effectuée');
        }

        // Trouver les notifications de test
        $testNotifications = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.title LIKE :test OR n.message LIKE :test')
            ->setParameter('test', '%Test%')
            ->getQuery()
            ->getResult();

        if (empty($testNotifications)) {
            $io->success('Aucune notification de test trouvée');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Traitement de %d notification(s) de test', count($testNotifications)));

        $deletedCount = 0;
        foreach ($testNotifications as $notification) {
            $io->text(sprintf(
                '🗑️  Notification: %s (ID: %d)',
                $notification->getTitle(),
                $notification->getId()
            ));

            if (!$dryRun) {
                $this->entityManager->remove($notification);
                $deletedCount++;
            }
        }

        // Trouver les notifications obsolètes (plus de 7 jours)
        $sevenDaysAgo = (new \DateTime())->modify('-7 days');
        $oldNotifications = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.createdAt < :date')
            ->andWhere('n.isRead = :read')
            ->setParameter('date', $sevenDaysAgo)
            ->setParameter('read', true)
            ->getQuery()
            ->getResult();

        if (!empty($oldNotifications)) {
            $io->section(sprintf('Nettoyage de %d notification(s) ancienne(s)', count($oldNotifications)));
            
            foreach ($oldNotifications as $notification) {
                $io->text(sprintf(
                    '🗑️  Ancienne notification: %s (ID: %d, Créée: %s)',
                    $notification->getTitle(),
                    $notification->getId(),
                    $notification->getCreatedAt()->format('d/m/Y H:i')
                ));

                if (!$dryRun) {
                    $this->entityManager->remove($notification);
                    $deletedCount++;
                }
            }
        }

        if (!$dryRun && $deletedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('✅ %d notification(s) supprimée(s)', $deletedCount));
        } elseif ($dryRun) {
            $io->success(sprintf('✅ %d notification(s) seraient supprimées', count($testNotifications) + count($oldNotifications)));
        }

        // Afficher les notifications restantes
        $remainingNotifications = $this->notificationRepository->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        if (!empty($remainingNotifications)) {
            $io->section('Notifications restantes (10 plus récentes)');
            foreach ($remainingNotifications as $notification) {
                $read = $notification->isRead() ? 'LU' : 'NON LU';
                $io->text(sprintf(
                    '📧 %s (ID: %d) - %s - %s',
                    $notification->getTitle(),
                    $notification->getId(),
                    $read,
                    $notification->getCreatedAt()->format('d/m/Y H:i')
                ));
            }
        }

        return Command::SUCCESS;
    }
} 