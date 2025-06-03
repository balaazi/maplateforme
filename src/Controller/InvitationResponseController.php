<?php
// src/Controller/InvitationResponseController.php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\User;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\EmailService;

class InvitationResponseController extends AbstractController
{
    private $passwordHasher;
    private $emailService;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EmailService $emailService)
    {
        $this->passwordHasher = $passwordHasher;
        $this->emailService = $emailService;
    }

    #[Route('/invitation/respond/{token}/{response}', name: 'invitation_respond', requirements: ['response' => 'accepted|declined'], methods: ['GET'])]
    #[Route('/organizer/invitations/respond/{token}/{response}', name: 'organizer_invitation_respond', requirements: ['response' => 'accepted|declined'], methods: ['GET'])]
    public function respond(string $token, string $response, EntityManagerInterface $entityManager): Response
    {
        $invitation = $entityManager->getRepository(Invitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            throw $this->createNotFoundException('Invitation non trouvée');
        }

        // Mettre à jour l'invitation
        $invitation->setStatus($response)
                ->setUpdatedAt(new \DateTime());

        // Trouver ou créer l'utilisateur correspondant à l'invitation
        $user = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $invitation->getEmail()]);

        if (!$user) {
            // Créer un nouvel utilisateur
            $user = new User();
            $user->setEmail($invitation->getEmail());
            $user->setRoles(['ROLE_PARTICIPANT']);
            // Ajouter les champs requis
            $user->setNom('Utilisateur'); // Nom par défaut
            $user->setPrenom('Nouveau'); // Prénom par défaut
            $user->setTelephone('Non renseigné'); // Téléphone par défaut
            // Générer un mot de passe aléatoire temporaire
            $temporaryPassword = bin2hex(random_bytes(8));
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $temporaryPassword)
            );
            $entityManager->persist($user);
            
            // Envoyer un email à l'utilisateur avec ses identifiants
            $this->emailService->sendNewUserCredentials($user->getEmail(), $temporaryPassword);
        }

        // Vérifier si la participation existe déjà
        $participation = $entityManager->getRepository(Participation::class)
            ->findOneBy([
                'user' => $user,
                'event' => $invitation->getEvent()
            ]);

        if (!$participation) {
            $participation = new Participation();
            $participation->setUser($user);
            $participation->setEvent($invitation->getEvent());
            $participation->setCreatedAt(new \DateTime());
            $entityManager->persist($participation);
        }

        // Mettre à jour l'état de participation avec les bons statuts
        if ($response === 'accepted') {
            $participation->setInvitationStatus('accepté');
            $participation->setIsPresent(true);
        } elseif ($response === 'declined') {
            $participation->setInvitationStatus('refusé');
            $participation->setIsPresent(false);
        } else {
            $participation->setInvitationStatus('en_attente');
            $participation->setIsPresent(false);
        }

        $entityManager->flush();

        return $this->render('invitation/response.html.twig', [
            'response' => $response,
            'invitation' => $invitation,
            'isNewUser' => !$user->getId(),
        ]);
    }
}
