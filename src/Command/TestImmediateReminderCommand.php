<?php

namespace App\Command;

use App\Entity\Reminder;
use App\Repository\ReminderRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-immediate-reminder',
    description: 'Teste le déclenchement immédiat d\'un rappel pour vérifier les notifications'
)]
class TestImmediateReminderCommand extends Command
{
    public function __construct(
        private ReminderRepository $reminderRepository,
        private ReminderService $reminderService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'reminder-id',
                'r',
                InputOption::VALUE_OPTIONAL,
                'ID du rappel à tester (par défaut: le premier rappel futur)'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Affiche ce qui serait fait sans effectuer les actions'
            )
            ->setHelp('
Cette commande teste le déclenchement immédiat d\'un rappel :
- Trouve un rappel futur
- Le déclenche immédiatement
- Vérifie l\'envoi des notifications et emails

Exemples :
  php bin/console app:test-immediate-reminder
  php bin/console app:test-immediate-reminder --reminder-id=50
  php bin/console app:test-immediate-reminder --dry-run
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $reminderId = $input->getOption('reminder-id');

        $io->title('🧪 Test de déclenchement immédiat de rappel');

        if ($dryRun) {
            $io->note('Mode test activé - aucune action ne sera effectuée');
        }

        // Trouver le rappel à tester
        if ($reminderId) {
            $reminder = $this->reminderRepository->find($reminderId);
            if (!$reminder) {
                $io->error("Rappel avec l'ID {$reminderId} non trouvé");
                return Command::FAILURE;
            }
        } else {
            // Prendre le premier rappel futur
            $reminders = $this->reminderRepository->createQueryBuilder('r')
                ->join('r.user', 'u')
                ->where('r.dueDate > :now')
                ->andWhere('r.isDone = :done')
                ->andWhere('r.isTriggered = :triggered')
                ->setParameter('now', new \DateTime())
                ->setParameter('done', false)
                ->setParameter('triggered', false)
                ->orderBy('r.dueDate', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();

            if (empty($reminders)) {
                $io->error('Aucun rappel futur trouvé');
                return Command::FAILURE;
            }

            $reminder = $reminders[0];
        }

        $io->section('Rappel sélectionné');
        $io->text([
            sprintf('ID: %d', $reminder->getId()),
            sprintf('Titre: %s', $reminder->getTitle()),
            sprintf('Échéance: %s', $reminder->getDueDate()->format('d/m/Y H:i')),
            sprintf('Utilisateur: %s', $reminder->getUser()->getEmail()),
            sprintf('Événement: %s', $reminder->getEvent() ? $reminder->getEvent()->getTitle() : 'Aucun'),
            sprintf('Email activé: %s', $reminder->isSendEmail() ? 'OUI' : 'NON'),
            sprintf('Notification activée: %s', $reminder->isShowNotification() ? 'OUI' : 'NON'),
            sprintf('Son activé: %s', $reminder->isPlaySound() ? 'OUI' : 'NON'),
        ]);

        if (!$dryRun) {
            $io->section('Déclenchement du rappel');
            
            try {
                // Déclencher le rappel
                $success = $this->reminderService->triggerReminder($reminder);
                
                if ($success) {
                    $io->success('✅ Rappel déclenché avec succès');
                    
                    // Vérifier si une notification a été créée
                    $notifications = $this->entityManager->getRepository(\App\Entity\Notification::class)
                        ->createQueryBuilder('n')
                        ->where('n.user = :user')
                        ->andWhere('n.createdAt >= :since')
                        ->setParameter('user', $reminder->getUser())
                        ->setParameter('since', (new \DateTime())->modify('-1 minute'))
                        ->orderBy('n.createdAt', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getResult();

                    if (!empty($notifications)) {
                        $notification = $notifications[0];
                        $io->success(sprintf('✅ Notification créée: %s', $notification->getTitle()));
                    } else {
                        $io->warning('⚠️ Aucune notification créée');
                    }
                    
                } else {
                    $io->error('❌ Erreur lors du déclenchement du rappel');
                }
                
            } catch (\Exception $e) {
                $io->error('❌ Erreur: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->success('✅ Le rappel serait déclenché');
        }

        return Command::SUCCESS;
    }
} 