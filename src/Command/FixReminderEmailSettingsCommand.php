<?php

namespace App\Command;

use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-reminder-email-settings',
    description: 'Corrige les paramètres email des rappels futurs selon les préférences utilisateur'
)]
class FixReminderEmailSettingsCommand extends Command
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
Cette commande corrige les paramètres email des rappels futurs :
- Active l\'envoi d\'email pour les rappels futurs si l\'utilisateur a activé les notifications email
- Corrige les paramètres de notification selon les préférences utilisateur
- Ne modifie que les rappels non déclenchés

Exemples :
  php bin/console app:fix-reminder-email-settings
  php bin/console app:fix-reminder-email-settings --dry-run
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('🔧 Correction des paramètres email des rappels');

        if ($dryRun) {
            $io->note('Mode test activé - aucune modification ne sera effectuée');
        }

        // Récupérer tous les rappels futurs non déclenchés
        $now = new \DateTime();
        $reminders = $this->reminderRepository->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->where('r.dueDate > :now')
            ->andWhere('r.isTriggered = :triggered')
            ->andWhere('r.isDone = :done')
            ->setParameter('now', $now)
            ->setParameter('triggered', false)
            ->setParameter('done', false)
            ->getQuery()
            ->getResult();

        if (empty($reminders)) {
            $io->success('Aucun rappel futur non déclenché trouvé');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Traitement de %d rappel(s) futur(s)', count($reminders)));

        $updatedCount = 0;
        $errors = [];

        foreach ($reminders as $reminder) {
            $user = $reminder->getUser();
            $event = $reminder->getEvent();
            
            if (!$user || !$event) {
                $errors[] = "Rappel {$reminder->getId()} : Utilisateur ou événement manquant";
                continue;
            }

            // Vérifier les préférences utilisateur
            $userWantsEmail = $user->isNotifyByEmail();
            $userWantsVisual = $user->isEnableVisualNotifications();
            $userWantsSound = $user->isEnableSoundNotifications();

            // Déterminer les nouveaux paramètres
            $newSendEmail = $userWantsEmail;
            $newShowNotification = $userWantsVisual;
            $newPlaySound = $userWantsSound;

            $io->text(sprintf(
                '📅 Rappel %d (%s) - Utilisateur: %s',
                $reminder->getId(),
                $event->getTitle(),
                $user->getEmail()
            ));

            $io->text(sprintf(
                '   Préférences: Email=%s, Visuel=%s, Son=%s',
                $userWantsEmail ? 'OUI' : 'NON',
                $userWantsVisual ? 'OUI' : 'NON',
                $userWantsSound ? 'OUI' : 'NON'
            ));

            $io->text(sprintf(
                '   Paramètres actuels: Email=%s, Visuel=%s, Son=%s',
                $reminder->isSendEmail() ? 'OUI' : 'NON',
                $reminder->isShowNotification() ? 'OUI' : 'NON',
                $reminder->isPlaySound() ? 'OUI' : 'NON'
            ));

            // Vérifier s'il y a des changements nécessaires
            $needsUpdate = (
                $reminder->isSendEmail() !== $newSendEmail ||
                $reminder->isShowNotification() !== $newShowNotification ||
                $reminder->isPlaySound() !== $newPlaySound
            );

            if ($needsUpdate) {
                $io->text('   ✅ Mise à jour nécessaire');
                
                if (!$dryRun) {
                    try {
                        $reminder->setSendEmail($newSendEmail);
                        $reminder->setShowNotification($newShowNotification);
                        $reminder->setPlaySound($newPlaySound);
                        $updatedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Rappel {$reminder->getId()} : " . $e->getMessage();
                    }
                }
            } else {
                $io->text('   ℹ️  Aucune modification nécessaire');
            }

            $io->text(''); // Ligne vide pour séparer
        }

        if (!$dryRun && $updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('✅ %d rappel(s) mis à jour', $updatedCount));
        } elseif ($dryRun) {
            $io->success(sprintf('✅ %d rappel(s) seraient mis à jour', $updatedCount));
        }

        if (!empty($errors)) {
            $io->warning('Erreurs rencontrées :');
            foreach ($errors as $error) {
                $io->text("  • $error");
            }
        }

        return Command::SUCCESS;
    }
} 