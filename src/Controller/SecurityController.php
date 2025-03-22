<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If the user is already logged in, redirect them to the appropriate dashboard based on their roles
        if ($this->getUser()) {
            return $this->redirectToRoute('redirect_after_login');
        }

        // Authentication error handling
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Render login form with the last username and error if any
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/redirect', name: 'redirect_after_login')]
public function redirectAfterLogin(): Response
{
    $user = $this->getUser(); // Utilisation de getUser() directement

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Redirection en fonction du rôle de l'utilisateur
    return match (true) {
        in_array('ROLE_ADMIN', $user->getRoles()) => $this->redirectToRoute('admin_dashboard'),
        in_array('ROLE_PARTICIPANT', $user->getRoles()) => $this->redirectToRoute('participant_dashboard'),
        in_array('ROLE_ORGANISATEUR', $user->getRoles()) => $this->redirectToRoute('organisateur_dashboard'),
        default => $this->redirectToRoute('app_home'),
    };
    }
}
