<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security; // Nouveau namespace

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // Gestion des erreurs d'authentification
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/redirect', name: 'redirect_after_login')]
    public function redirectAfterLogin(Security $security): Response
    {
        $user = $security->getUser();
        
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        // Redirection par rôle
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        }
        
        if (in_array('ROLE_PARTICIPANT', $user->getRoles())) {
            return $this->redirectToRoute('participant_dashboard');
        }
        
        if (in_array('ROLE_ORGANISATEUR', $user->getRoles())) {
            return $this->redirectToRoute('organisateur_dashboard');
        }
        
        return $this->redirectToRoute('home');
    }
}