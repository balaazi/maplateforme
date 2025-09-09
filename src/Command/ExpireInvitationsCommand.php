<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use App\Service\InvitationExpirationNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExpireInvitationsCommand extends Command
{
    protected static $defaultName = 'app:expire-invitations';
    protected static $defaultDescription = 'Force l\'expiration des invitations en attente';

    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager,
        private InvitationExpirationNotifier $expirationNotifier
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Récupérer toutes les invitations en attente
            $pendingInvitations = $this->invitationRepository->findBy(['status' => 'pending']);
            
            $expiredCount = 0;
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            
            foreach ($pendingInvitations as $invitation) {
                // Forcer la vérification avec un délai de 1 jour
                if ($invitation->shouldBeExpired(1)) {
                    try {
                        $this->expirationNotifier->notifyExpiration($invitation);
                        $expiredCount++;
                        $io->text(sprintf(
                            "Invitation expirée - ID: %d, Email: %s",
                            $invitation->getId(),
                            $invitation->getEmail()
                        ));
                    } catch (\Exception $e) {
                        $io->error(sprintf(
                            "Erreur lors de l'expiration - ID: %d, Email: %s, Erreur: %s",
                            $invitation->getId(),
                            $invitation->getEmail(),
                            $e->getMessage()
                        ));
                    }
                } else {
                    $io->text(sprintf(
                        "Invitation non expirée - ID: %d, Email: %s, Créée le: %s",
                        $invitation->getId(),
                        $invitation->getEmail(),
                        $invitation->getCreatedAt()->format('Y-m-d H:i:s')
                    ));
                }
            }
            
            if ($expiredCount > 0) {
                $this->entityManager->flush();
                $io->success(sprintf('%d invitation(s) expirée(s)', $expiredCount));
            } else {
                $io->info('Aucune invitation n\'a été expirée.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}