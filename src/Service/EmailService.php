<?php
// src/Service/EmailService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class EmailService
{
    private $mailer;
    private $params;
    private $twig;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $params, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->params = $params;
        $this->twig = $twig;
    }

    public function sendReminder(User $user, Event $event): void
    {
        // Utiliser la méthode getFullName() pour obtenir le nom complet de l'utilisateur
        $fullName = $user->getFullName();

        $email = (new Email())
            ->from('nadiabalaazi@gmail.com') // Remplace par ton email valide
            ->to($user->getEmail())
            ->subject('⏰ Rappel : Événement à venir')
            ->html("
<p>Bonjour {$fullName},</p>
<p>Vous avez un événement demain : <strong>{$event->getTitle()}</strong></p>
<p>Lieu : " . ($event->getSalle() ? $event->getSalle()->getNom() : 'Non défini') . "</p>
<p>Date & Heure : {$event->getDateHeure()->format('d/m/Y H:i')}</p>
");

        $this->mailer->send($email);  // Envoie l'email
    }

    public function sendNewUserCredentials(string $userEmail, string $temporaryPassword): void
    {
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to($userEmail)
            ->subject('Vos identifiants de connexion')
            ->html(
                $this->twig->render('emails/new_user_credentials.html.twig', [
                    'email' => $userEmail,
                    'password' => $temporaryPassword
                ])
            );

        $this->mailer->send($email);
    }

    public function sendTestEmail(string $email, string $subject, string $message): bool
    {
        try {
            $emailObj = (new Email())
                ->from('nadiabalaazi@gmail.com')
                ->to($email)
                ->subject($subject)
                ->html($message);

            $this->mailer->send($emailObj);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur envoi email test: ' . $e->getMessage());
            return false;
        }
    }
}