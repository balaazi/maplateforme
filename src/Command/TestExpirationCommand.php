<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-expiration',
    description: 'Teste l\'expiration automatique des invitations',
)]
class TestExpirationCommand extends Command
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test de l\'expiration automatique des invitations');
        
        // Récupérer toutes les invitations en attente
        $pendingInvitations = $this->invitationRepository->findBy(['status' => 'pending']);
        
        $io->text("📊 Invitations en attente trouvées : " . count($pendingInvitations));
        
        if (empty($pendingInvitations)) {
            $io->info('Aucune invitation en attente trouvée.');
            return Command::SUCCESS;
        }
        
        $io->section('Détails des invitations en attente :');
        
        $expiredCount = 0;
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        
        foreach ($pendingInvitations as $invitation) {
            $createdAt = $invitation->getCreatedAt();
            $daysOld = $createdAt ? $createdAt->diff($now)->days : 0;
            $shouldExpire = $daysOld >= 30;
            
            $status = $shouldExpire ? "🔴 DEVRAIT ÊTRE EXPIRÉE" : "🟡 ENCORE VALIDE";
            
            $io->text([
                "  - ID: {$invitation->getId()}",
                "    Email: {$invitation->getEmail()}",
                "    Créée le: " . ($createdAt ? $createdAt->format('Y-m-d H:i:s') : 'N/A'),
                "    Âge: $daysOld jours",
                "    Statut: $status",
                ""
            ]);
            
            if ($shouldExpire) {
                $expiredCount++;
            }
        }
        
        if ($expiredCount > 0) {
            $io->warning("⚠️  $expiredCount invitation(s) devraient être expirées !");
            
            if ($io->confirm('Voulez-vous les expirer maintenant ?', false)) {
                $actualExpiredCount = 0;
                
                foreach ($pendingInvitations as $invitation) {
                    if ($invitation->checkAndMarkAsExpired(30)) {
                        $actualExpiredCount++;
                    }
                }
                
                if ($actualExpiredCount > 0) {
                    $this->entityManager->flush();
                    $io->success("✅ $actualExpiredCount invitation(s) marquée(s) comme expirée(s) !");
                } else {
                    $io->info('Aucune invitation expirée.');
                }
            }
        } else {
            $io->success('✅ Toutes les invitations en attente sont encore valides.');
        }
        
        // Vérifier les invitations expirées
        $expiredInvitations = $this->invitationRepository->findBy(['status' => 'expired']);
        $io->text("📊 Invitations expirées : " . count($expiredInvitations));
        
        $io->section('Résumé');
        $io->text([
            '✅ L\'expiration automatique est configurée.',
            '🔄 Les invitations seront automatiquement expirées lors de l\'accès à l\'application.',
            '📝 Vérifiez les logs pour voir l\'activité d\'expiration.',
        ]);
        
        return Command::SUCCESS;
    }
}
