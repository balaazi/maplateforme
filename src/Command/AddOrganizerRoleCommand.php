<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-organizer-role',
    description: 'Add ROLE_ORGANISATEUR to a user',
)]
class AddOrganizerRoleCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user');
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

        $roles = $user->getRoles();
        if (in_array('ROLE_ORGANISATEUR', $roles)) {
            $io->info(sprintf('User "%s" already has ROLE_ORGANISATEUR', $email));
            return Command::SUCCESS;
        }

        $roles[] = 'ROLE_ORGANISATEUR';
        $user->setRoles(array_unique($roles));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Added ROLE_ORGANISATEUR to user "%s"', $email));

        return Command::SUCCESS;
    }
} 