<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-password',
    description: 'Test password hashing and verification',
)]
class TestPasswordCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test de changement de mot de passe');
        
        // Demander l'email de l'utilisateur
        $email = $io->ask('Email de l\'utilisateur à tester');
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            $io->error("Utilisateur non trouvé avec l'email: $email");
            return Command::FAILURE;
        }
        
        $io->success("Utilisateur trouvé: " . $user->getEmail());
        $io->text("ID: " . $user->getId());
        $io->text("Hash actuel: " . $user->getPassword());
        
        // Demander le nouveau mot de passe
        $newPassword = $io->askHidden('Nouveau mot de passe (caché)');
        
        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $io->text("Nouveau hash: $hashedPassword");
        
        // Vérifier que le hash correspond
        $isValid = $this->passwordHasher->isPasswordValid($user, $newPassword);
        $io->text("Validation immédiate: " . ($isValid ? 'OK' : 'ECHEC'));
        
        // Sauvegarder
        $oldHash = $user->getPassword();
        $user->setPassword($hashedPassword);
        
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $io->success("Mot de passe sauvegardé en base de données");
            
            // Recharger depuis la base
            $this->entityManager->refresh($user);
            $newHashFromDb = $user->getPassword();
            $io->text("Hash depuis la DB: $newHashFromDb");
            
            // Vérifier à nouveau
            $isValidAfterSave = $this->passwordHasher->isPasswordValid($user, $newPassword);
            $io->text("Validation après sauvegarde: " . ($isValidAfterSave ? 'OK' : 'ECHEC'));
            
            if ($isValidAfterSave) {
                $io->success("✅ Le changement de mot de passe fonctionne correctement!");
            } else {
                $io->error("❌ Problème: le mot de passe ne valide pas après sauvegarde");
            }
            
        } catch (\Exception $e) {
            $io->error("Erreur lors de la sauvegarde: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
} 