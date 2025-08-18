<?php

namespace App\Command;

use App\Entity\Reminder;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-reminder-titles',
    description: 'Corrige les titres des rappels existants pour différencier les rôles'
)]
class FixReminderTitlesCommand extends Command
{
    public function __construct(
        private ReminderRepository $reminderRepository,
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
                'Affiche les modifications qui seraient apportées sans les effectuer'
            )
            ->setHelp('
Cette commande corrige les titres des rappels existants pour mieux différencier les rôles :
- Supprime "organisateur", "participant", "invité" des titres
- Utilise un titre générique "Rappel - [Titre événement]"
- Ajoute des descriptions appropriées selon le rôle
- Met à jour les types de rappels

Exemples :
  php bin/console app:fix-reminder-titles
  php bin/console app:fix-reminder-titles --dry-run
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('🔧 Correction des titres de rappels');

        if ($dryRun) {
            $io->note('Mode test activé - aucune modification ne sera effectuée');
        }

        // Récupérer tous les rappels avec des titres incorrects
        $reminders = $this->reminderRepository->createQueryBuilder('r')
            ->where('r.title LIKE :organizer OR r.title LIKE :participant OR r.title LIKE :invite')
            ->setParameter('organizer', '%Rappel organisateur%')
            ->setParameter('participant', '%Rappel participant%')
            ->setParameter('invite', '%Rappel invité%')
            ->getQuery()
            ->getResult();

        if (empty($reminders)) {
            $io->success('Aucun rappel avec titre incorrect trouvé');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Traitement de %d rappel(s)', count($reminders)));

        $updatedCount = 0;
        $errors = [];

        foreach ($reminders as $reminder) {
            $oldTitle = $reminder->getTitle();
            $event = $reminder->getEvent();
            
            if (!$event) {
                $errors[] = "Rappel {$reminder->getId()} : Événement manquant";
                continue;
            }

            // Déterminer le nouveau titre et la description selon le rôle
            $newTitle = "Rappel - {$event->getTitle()}";
            $newDescription = $this->determineDescription($reminder, $event);
            $newType = $this->determineType($reminder, $event);

            $io->text(sprintf(
                '📅 Rappel %d : "%s" → "%s"',
                $reminder->getId(),
                $oldTitle,
                $newTitle
            ));

            if (!$dryRun) {
                try {
                    $reminder->setTitle($newTitle);
                    $reminder->setDescription($newDescription);
                    $reminder->setType($newType);
                    $updatedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Rappel {$reminder->getId()} : " . $e->getMessage();
                }
            }
        }

        if (!$dryRun && $updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('✅ %d rappel(s) corrigé(s)', $updatedCount));
        } elseif ($dryRun) {
            $io->success(sprintf('✅ %d rappel(s) seraient corrigés', count($reminders)));
        }

        if (!empty($errors)) {
            $io->warning('Erreurs rencontrées :');
            foreach ($errors as $error) {
                $io->text("  • $error");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Détermine la description appropriée selon le rôle
     */
    private function determineDescription(Reminder $reminder, $event): string
    {
        $user = $reminder->getUser();
        
        // Vérifier si l'utilisateur est l'organisateur
        if ($event->getOrganizer() && $event->getOrganizer()->getId() === $user->getId()) {
            return "Vous organisez cet événement";
        }

        // Vérifier si l'utilisateur est un participant
        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() && $participation->getUser()->getId() === $user->getId()) {
                return "Vous participez à cet événement";
            }
        }

        // Vérifier si l'utilisateur est un invité
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getInvite() && $invitation->getInvite()->getId() === $user->getId()) {
                return "Vous êtes invité à cet événement";
            }
        }

        return "Rappel pour cet événement";
    }

    /**
     * Détermine le type approprié selon le rôle
     */
    private function determineType(Reminder $reminder, $event): string
    {
        $user = $reminder->getUser();
        
        // Vérifier si l'utilisateur est l'organisateur
        if ($event->getOrganizer() && $event->getOrganizer()->getId() === $user->getId()) {
            return 'organizer_reminder';
        }

        // Vérifier si l'utilisateur est un participant
        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() && $participation->getUser()->getId() === $user->getId()) {
                return 'participant_reminder';
            }
        }

        // Vérifier si l'utilisateur est un invité
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getInvite() && $invitation->getInvite()->getId() === $user->getId()) {
                return 'invite_reminder';
            }
        }

        return 'event_reminder';
    }
} 