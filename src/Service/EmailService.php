<?php
// src/Service/EmailService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailService
{
    private $mailer;
    private $params;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $params)
    {
        $this->mailer = $mailer;
        $this->params = $params;
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
<p>Lieu : {$event->getLieu()}</p>
<p>Date & Heure : {$event->getDateHeure()->format('d/m/Y H:i')}</p>
");

        $this->mailer->send($email);  // Envoie l'email
    }

    public function sendNewUserCredentials(string $email, string $temporaryPassword): void
    {
        $email = (new Email())
            ->from($this->params->get('app.mail_from'))
            ->to($email)
            ->subject('Vos identifiants de connexion')
            ->html(
                $this->renderTemplate('emails/new_user_credentials.html.twig', [
                    'email' => $email,
                    'password' => $temporaryPassword
                ])
            );

        $this->mailer->send($email);
    }

    private function renderTemplate(string $template, array $context): string
    {
        // Template simple pour l'exemple
        return <<<HTML
        <h1>Bienvenue sur notre plateforme</h1>
        <p>Voici vos identifiants de connexion :</p>
        <ul>
            <li>Email : {$context['email']}</li>
            <li>Mot de passe temporaire : {$context['password']}</li>
        </ul>
        <p>Nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
        HTML;
    }
}