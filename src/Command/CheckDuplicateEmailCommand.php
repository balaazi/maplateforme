<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-duplicate-email',
    description: 'Check for duplicate emails and optionally remove specific entries',
)]
class CheckDuplicateEmailCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Specific email to check')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Remove the duplicate email entry')
            ->addOption('list-all', 'l', InputOption::VALUE_NONE, 'List all users with their emails')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $remove = $input->getOption('remove');
        $listAll = $input->getOption('list-all');

        if ($listAll) {
            $this->listAllUsers($io);
            return Command::SUCCESS;
        }

        if ($email) {
            return $this->handleSpecificEmail($io, $email, $remove);
        }

        // Check for all duplicate emails
        $this->checkAllDuplicates($io);

        return Command::SUCCESS;
    }

    private function listAllUsers(SymfonyStyle $io): void
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $io->title('📋 Liste de tous les utilisateurs');
        
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getId(),
                $user->getEmail(),
                $user->getFullName(),
                implode(', ', $user->getRoles()),
            ];
        }
        
        $io->table(
            ['ID', 'Email', 'Nom complet', 'Rôles'],
            $rows
        );
        
        $io->success(sprintf('Total: %d utilisateurs trouvés', count($users)));
    }

    private function handleSpecificEmail(SymfonyStyle $io, string $email, bool $remove): int
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['email' => $email]);
        
        if (empty($users)) {
            $io->warning("❌ Aucun utilisateur trouvé avec l'email: $email");
            return Command::SUCCESS;
        }

        if (count($users) === 1) {
            $user = $users[0];
            $io->success("✅ Un seul utilisateur trouvé avec l'email: $email");
            $io->table(
                ['ID', 'Email', 'Nom', 'Prénom', 'Rôles'],
                [[
                    $user->getId(),
                    $user->getEmail(),
                    $user->getNom(),
                    $user->getPrenom(),
                    implode(', ', $user->getRoles())
                ]]
            );
            return Command::SUCCESS;
        }

        // Multiple users with same email (should not happen with unique constraint)
        $io->error("🚨 PROBLÈME: Plusieurs utilisateurs trouvés avec l'email: $email");
        
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getId(),
                $user->getEmail(),
                $user->getNom() ?? 'N/A',
                $user->getPrenom() ?? 'N/A',
                implode(', ', $user->getRoles()),
            ];
        }
        
        $io->table(
            ['ID', 'Email', 'Nom', 'Prénom', 'Rôles'],
            $rows
        );

        if ($remove) {
            $userIdToKeep = $io->ask('Quel ID utilisateur voulez-vous GARDER? (les autres seront supprimés)');
            
            if (!$userIdToKeep || !is_numeric($userIdToKeep)) {
                $io->error('ID invalide.');
                return Command::FAILURE;
            }

            $kept = false;
            $deleted = 0;
            
            foreach ($users as $user) {
                if ($user->getId() == $userIdToKeep) {
                    $kept = true;
                    $io->info("✅ Utilisateur gardé: ID {$user->getId()} - {$user->getFullName()}");
                } else {
                    $this->entityManager->remove($user);
                    $deleted++;
                    $io->warning("🗑️ Utilisateur supprimé: ID {$user->getId()} - {$user->getFullName()}");
                }
            }

            if ($kept) {
                $this->entityManager->flush();
                $io->success("✅ Nettoyage terminé. $deleted utilisateur(s) supprimé(s).");
            } else {
                $io->error("❌ L'ID spécifié n'existe pas dans la liste.");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    private function checkAllDuplicates(SymfonyStyle $io): void
    {
        // Query to find duplicate emails
        $query = $this->entityManager->createQuery(
            'SELECT u.email, COUNT(u.email) as email_count 
             FROM App\Entity\User u 
             GROUP BY u.email 
             HAVING COUNT(u.email) > 1'
        );
        
        $duplicates = $query->getResult();
        
        if (empty($duplicates)) {
            $io->success('✅ Aucun email dupliqué trouvé dans la base de données.');
            return;
        }

        $io->warning('🚨 Emails dupliqués détectés:');
        
        foreach ($duplicates as $duplicate) {
            $io->writeln("  - {$duplicate['email']} ({$duplicate['email_count']} occurences)");
            
            // Show details for each duplicate
            $users = $this->entityManager->getRepository(User::class)->findBy(['email' => $duplicate['email']]);
            foreach ($users as $user) {
                $io->writeln("    ID: {$user->getId()} - {$user->getFullName()} - Rôles: " . implode(', ', $user->getRoles()));
            }
            $io->writeln('');
        }
        
        $io->note('💡 Utilisez --remove avec un email spécifique pour nettoyer les doublons.');
        $io->note('💡 Exemple: php bin/console app:check-duplicate-email nadabalaazi34@gmail.com --remove');
    }
} 