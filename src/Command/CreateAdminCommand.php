<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Adresse email de l\'administrateur')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe de l\'administrateur')
            ->addArgument('nom', InputArgument::OPTIONAL, 'Nom de famille de l\'administrateur')
            ->addArgument('prenom', InputArgument::OPTIONAL, 'Prénom de l\'administrateur')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Créer un super administrateur avec le rôle ROLE_SUPER_ADMIN')
            ->setHelp('Cette commande permet de créer un utilisateur administrateur...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupération des arguments ou demande interactive
        $email = $input->getArgument('email');
        if (!$email) {
            $email = $io->ask('Adresse email de l\'administrateur');
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->askHidden('Mot de passe de l\'administrateur (minimum 6 caractères)');
        }

        $nom = $input->getArgument('nom');
        if (!$nom) {
            $nom = $io->ask('Nom de famille de l\'administrateur');
        }

        $prenom = $input->getArgument('prenom');
        if (!$prenom) {
            $prenom = $io->ask('Prénom de l\'administrateur');
        }

        // Validation des données
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresse email invalide.');
            return Command::FAILURE;
        }

        if (!$password || strlen($password) < 6) {
            $io->error('Le mot de passe doit contenir au moins 6 caractères.');
            return Command::FAILURE;
        }

        if (!$nom || strlen(trim($nom)) < 2) {
            $io->error('Le nom doit contenir au moins 2 caractères.');
            return Command::FAILURE;
        }

        if (!$prenom || strlen(trim($prenom)) < 2) {
            $io->error('Le prénom doit contenir au moins 2 caractères.');
            return Command::FAILURE;
        }

        // Vérification si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('Un utilisateur avec l\'email "%s" existe déjà.', $email));
            return Command::FAILURE;
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setNom(trim($nom));
        $user->setPrenom(trim($prenom));
        
        // Définition des rôles
        $roles = ['ROLE_ADMIN'];
        if ($input->getOption('super-admin')) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }
        $user->setRoles($roles);

        // Hashage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Configuration par défaut
        $user->setNotifyByEmail(true);
        $user->setNotifyBySms(false);
        $user->setEnableSoundNotifications(true);
        $user->setEnableVisualNotifications(true);
        $user->setReminderFrequency(1);
        $user->setNotificationPriority('high');

        // Validation de l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getMessage());
            }
            return Command::FAILURE;
        }

        try {
            // Sauvegarde en base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $roleText = $input->getOption('super-admin') ? 'super administrateur' : 'administrateur';
            $io->success(sprintf(
                'Utilisateur %s créé avec succès !%sEmail: %s%sNom: %s %s%sRôles: %s',
                $roleText,
                PHP_EOL,
                $email,
                PHP_EOL,
                $prenom,
                $nom,
                PHP_EOL,
                implode(', ', $roles)
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}