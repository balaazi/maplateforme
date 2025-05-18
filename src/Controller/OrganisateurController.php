<?php
// src/Controller/OrganisateurController.php
namespace App\Controller;

use App\Entity\User; 
use App\Service\MailerService; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class OrganisateurController extends AbstractController
{
// Route pour afficher le tableau de bord de l'organisateur
#[Route('/organisateur', name: 'organisateur_dashboard')]
public function dashboard(): Response
{
return $this->render('organisateur/dashboard.html.twig');
}

// Route pour envoyer une invitation par email
#[Route('/organisateur/inviter/{userId}', name: 'organisateur_inviter', methods: ['POST'])]
public function invite(Request $request, int $userId, MailerInterface $mailer): Response
{
// Récupérer l'email de l'utilisateur à inviter
$email = $request->request->get('email');

// Créer l'email d'invitation
$invitationEmail = (new Email())
->from('nadiabalaazi@gmail.com')
->to($email)
->subject('Invitation à un événement')
->html('<p>Tu es invité à participer à un événement. Clique ici pour plus de détails.</p>');

// Envoyer l'email
$mailer->send($invitationEmail);

// Message flash pour confirmer l'envoi de l'email
$this->addFlash('success', 'Invitation envoyée avec succès!');

// Rediriger vers le tableau de bord
return $this->redirectToRoute('organisateur_dashboard');
}
}
