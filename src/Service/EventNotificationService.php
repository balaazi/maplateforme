<?php
// src/Service/EventNotificationService.php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EventNotificationService
{
private $mailer;
private $userRepository;

public function __construct(MailerInterface $mailer, UserRepository $userRepository)
{
$this->mailer = $mailer;
$this->userRepository = $userRepository;
}

// Cette méthode envoie une notification de mise à jour de l'événement
public function sendEventUpdateNotification(Event $event): void
{
// Récupérer tous les utilisateurs associés à l'événement (par exemple, les participants)
$users = $this->userRepository->findAll();  // Ajuster cette méthode selon la logique de ton application

// Créer et envoyer un email à chaque utilisateur
foreach ($users as $user) {
$email = (new Email())
->from('nadiabalaazi@gmail.com')  // Mettre un email autorisé par ton fournisseur
->to($user->getEmail())
->subject('🔔 L\'événement a été modifié')
->html("<p>Bonjour {$user->getFullName()},</p>
<p>L'événement <strong>{$event->getTitle()}</strong> a été modifié.</p>
<p>Nouvelle date et heure : {$event->getDateHeure()->format('d/m/Y H:i')}</p>
            <p>Lieu : " . ($event->getSalle() ? $event->getSalle()->getNom() : 'Non défini') . "</p>");

$this->mailer->send($email);
}
}
public function sendEventCancelNotification(Event $event): void
{
    /** @var Invitation $invit */
    foreach ($event->getInvitations() as $invit) {
        if ($invit->getEmail()) {
            $participant = $invit->getParticipant();
            $participantName = $participant ? $participant->getName() : 'Invité(e)';

            $email = (new Email())
                ->from('nadiabalaazi@gmail.com')
                ->to($invit->getEmail())
                ->subject('❌ Événement annulé : ' . $event->getTitle())
                ->html("
                    <p>Bonjour {$participantName},</p>
                    <p>Nous vous informons que l'événement <strong>{$event->getTitle()}</strong> prévu le <strong>{$event->getDateHeure()->format('d/m/Y H:i')}</strong> a été <strong>annulé</strong>.</p>
                    <p>Merci de votre compréhension.</p>
                ");

            $this->mailer->send($email);
        }
    }
}


}
