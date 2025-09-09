<?php

namespace App\Command;

use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-notifications',
    description: 'Create test notifications for a user',
)]
class CreateTestNotificationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email of the user', 'nadiabalaazi@gmail.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found', $email));
            return Command::FAILURE;
        }

        $io->title('Création de notifications de test');

        // Créer une notification de bienvenue
        $this->notificationService->createWelcomeNotification($user);
        $io->writeln('✅ Notification de bienvenue créée');

        // Créer une notification de rappel d'événement fictif
        $this->notificationService->createNotification(
            $user,
            "Rappel d'événement",
            "N'oubliez pas votre formation en gestion de projet demain à 14h00.",
            'event_reminder'
        );
        $io->writeln('✅ Notification de rappel créée');

        // Créer une notification d'invitation
        $this->notificationService->createNotification(
            $user,
            "Nouvelle invitation",
            "Vous avez été invité(e) à la formation 'Technologies Web Modernes' le 15 juin 2025.",
            'invitation'
        );
        $io->writeln('✅ Notification d\'invitation créée');

        // Créer une notification de mise à jour
        $this->notificationService->createNotification(
            $user,
            "Événement modifié",
            "L'atelier 'Introduction à Symfony' a été reporté au 20 juin 2025.",
            'event_update'
        );
        $io->writeln('✅ Notification de mise à jour créée');

        // Créer une notification d'annulation
        $this->notificationService->createNotification(
            $user,
            "Événement annulé",
            "Le séminaire 'Design Patterns' prévu le 10 juin a été annulé en raison de circonstances imprévues.",
            'event_cancel'
        );
        $io->writeln('✅ Notification d\'annulation créée');

        $count = $this->notificationService->getUnreadCountForUser($user);
        $io->success(sprintf('5 notifications de test créées pour %s (%d notifications non lues au total)', $email, $count));

        return Command::SUCCESS;
    }
} 