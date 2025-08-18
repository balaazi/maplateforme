<?php

namespace App\Service;

use App\Entity\ProcesVerbal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

class ProcesVerbalShareService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private ProcesVerbalExportService $exportService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MailerInterface $mailer, 
        Environment $twig,
        ProcesVerbalExportService $exportService,
        EntityManagerInterface $entityManager
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->exportService = $exportService;
        $this->entityManager = $entityManager;
    }

    /**
     * Partage le procès-verbal avec les participants de la réunion
     */
    public function shareWithParticipants(ProcesVerbal $procesVerbal): bool
    {
        $event = $procesVerbal->getEvent();
        $participants = [];

        // Récupérer tous les participants invités
        foreach ($event->getInvitations() as $invitation) {
            // Récupérer l'utilisateur via l'email de l'invitation
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $invitation->getEmail()]);
            if ($user && $user->getEmail()) {
                $participants[] = $user;
            }
        }

        if (empty($participants)) {
            return false;
        }

        return $this->sendToUsers($procesVerbal, $participants);
    }

    /**
     * Partage le procès-verbal avec des utilisateurs spécifiques
     */
    public function sendToUsers(ProcesVerbal $procesVerbal, array $users): bool
    {
        try {
            $event = $procesVerbal->getEvent();
            
            // Générer le contenu HTML
            $htmlContent = $this->exportService->exportToHtml($procesVerbal);
            
            // Générer le document RTF en pièce jointe
            $rtfContent = $this->exportService->exportToWord($procesVerbal)->getContent();
            $attachmentName = 'PV_' . $event->getTitle() . '_' . $procesVerbal->getDateHeure()->format('Y-m-d') . '.rtf';

            foreach ($users as $user) {
                $this->sendEmailToUser($procesVerbal, $user, $htmlContent, $rtfContent, $attachmentName);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Erreur lors du partage du PV: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie le procès-verbal par email à un utilisateur
     */
    private function sendEmailToUser(
        ProcesVerbal $procesVerbal, 
        User $user, 
        string $htmlContent, 
        string $rtfContent, 
        string $attachmentName
    ): void {
        $event = $procesVerbal->getEvent();

        $emailBody = $this->twig->render('emails/proces_verbal_share.html.twig', [
            'user' => $user,
            'procesVerbal' => $procesVerbal,
            'event' => $event,
            'redacteur' => $procesVerbal->getRedacteur()
        ]);

        $email = (new Email())
            ->from('noreply@eventhub.com') // À adapter selon votre configuration
            ->to($user->getEmail())
            ->subject('Procès-verbal - ' . $event->getTitle())
            ->html($emailBody)
            ->addPart(new DataPart($rtfContent, $attachmentName, 'application/rtf'));

        $this->mailer->send($email);
    }

    /**
     * Partage le procès-verbal avec des emails spécifiques
     */
    public function sendToEmails(ProcesVerbal $procesVerbal, array $emails): bool
    {
        try {
            $event = $procesVerbal->getEvent();
            
            // Générer le contenu HTML
            $htmlContent = $this->exportService->exportToHtml($procesVerbal);
            
            // Générer le document RTF en pièce jointe
            $rtfContent = $this->exportService->exportToWord($procesVerbal)->getContent();
            $attachmentName = 'PV_' . $event->getTitle() . '_' . $procesVerbal->getDateHeure()->format('Y-m-d') . '.rtf';

            foreach ($emails as $email) {
                $this->sendEmailToAddress($procesVerbal, $email, $htmlContent, $rtfContent, $attachmentName);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Erreur lors du partage du PV par email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie le procès-verbal à une adresse email spécifique
     */
    private function sendEmailToAddress(
        ProcesVerbal $procesVerbal, 
        string $emailAddress, 
        string $htmlContent, 
        string $rtfContent, 
        string $attachmentName
    ): void {
        $event = $procesVerbal->getEvent();

        $emailBody = $this->twig->render('emails/proces_verbal_share_external.html.twig', [
            'procesVerbal' => $procesVerbal,
            'event' => $event,
            'redacteur' => $procesVerbal->getRedacteur()
        ]);

        $email = (new Email())
            ->from('noreply@eventhub.com') // À adapter selon votre configuration
            ->to($emailAddress)
            ->subject('Procès-verbal - ' . $event->getTitle())
            ->html($emailBody)
            ->addPart(new DataPart($rtfContent, $attachmentName, 'application/rtf'));

        $this->mailer->send($email);
    }

    /**
     * Obtient la liste des participants pouvant recevoir le PV
     */
    public function getEligibleParticipants(ProcesVerbal $procesVerbal): array
    {
        $event = $procesVerbal->getEvent();
        $participants = [];

        foreach ($event->getInvitations() as $invitation) {
            // Récupérer l'utilisateur via l'email de l'invitation
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $invitation->getEmail()]);
            if ($user && $user->getEmail()) {
                $participants[] = [
                    'user' => $user,
                    'email' => $user->getEmail(),
                    'name' => $user->getPrenom() . ' ' . $user->getNom(),
                    'status' => $invitation->getStatus()
                ];
            }
        }

        return $participants;
    }
}