<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use App\Service\InvitationExpirationNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-expiration-notifications',
    description: 'Envoie des emails de notification d\'expiration pour les invitations expirées',
)]
class SendExpirationNotificationsCommand extends Command
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private InvitationExpirationNotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'test',
                't',
                InputOption::VALUE_NONE,
                'Mode test - n\'envoie pas réellement les emails'
            )
            ->setHelp('Cette commande envoie des emails de notification d\'expiration pour toutes les invitations expirées.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isTestMode = $input->getOption('test');
        
        $io->title('Envoi des notifications d\'expiration');
        
        if ($isTestMode) {
            $io->warning('Mode test activé - Aucun email ne sera envoyé');
        }
        
        try {
            // Récupérer toutes les invitations expirées
            $expiredInvitations = $this->invitationRepository->createQueryBuilder('i')
                ->andWhere('i.status = :status')
                ->setParameter('status', 'expired')
                ->orderBy('i.updatedAt', 'DESC')
                ->getQuery()
                ->getResult();
            
            if (empty($expiredInvitations)) {
                $io->info('Aucune invitation expirée trouvée.');
                return Command::SUCCESS;
            }
            
            $io->text(sprintf('Trouvé %d invitation(s) expirée(s)', count($expiredInvitations)));
            
            // Afficher les détails des invitations
            $io->table(
                ['ID', 'Email', 'Nom', 'Événement', 'Date d\'expiration'],
                array_map(function($invitation) {
                    return [
                        $invitation->getId(),
                        $invitation->getEmail(),
                        $invitation->getName(),
                        $invitation->getEvent()?->getTitle() ?? 'N/A',
                        $invitation->getUpdatedAt()?->format('d/m/Y H:i:s') ?? 'N/A'
                    ];
                }, $expiredInvitations)
            );
            
            if (!$isTestMode) {
                // Demander confirmation
                if (!$io->confirm('Voulez-vous envoyer les notifications d\'expiration ?', false)) {
                    $io->info('Opération annulée.');
                    return Command::SUCCESS;
                }
                
                // Envoyer les notifications
                // DÉSACTIVÉ - Aucun email d'expiration n'est envoyé
                $io->warning('Service de notification désactivé - Aucun email envoyé');
                $io->info('Les statuts sont mis à jour automatiquement sans notification');
                
                $sentCount = 0; // Aucun email envoyé
            } else {
                $io->info('Mode test - Aucune notification envoyée');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi des notifications d\'expiration: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
