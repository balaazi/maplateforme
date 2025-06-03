<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private UserRepository $userRepository;

    public function __construct(RouterInterface $router, UserRepository $userRepository)
    {
        $this->router = $router;
        $this->userRepository = $userRepository;
    }

    // Méthode d'authentification de l'utilisateur
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        if (empty($email) || empty($password)) {
            throw new BadCredentialsException('Email et mot de passe doivent être renseignés.');
        }

        // Laisser Symfony gérer la vérification du mot de passe
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    // Méthode de redirection après authentification réussie
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            throw new \Exception('L\'utilisateur n\'est pas valide.');
        }

        // Vérifier si une URL de redirection est enregistrée dans la session
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Récupérer l'utilisateur depuis la base pour avoir ses rôles réels
        $userFromDb = $this->userRepository->findOneByEmail($user->getUserIdentifier());
        $realRoles = $userFromDb ? $userFromDb->getRoles() : $user->getRoles();

        // Redirection basée sur le rôle RÉEL de l'utilisateur (pas les rôles hérités)
        $redirectRoute = match (true) {
            in_array('ROLE_ADMIN', $realRoles) => 'admin_dashboard',
            in_array('ROLE_ORGANISATEUR', $realRoles) => 'organisateur_dashboard',
            in_array('ROLE_PARTICIPANT', $realRoles) => 'participant_dashboard',
            default => 'user_home',
        };

        return new RedirectResponse($this->router->generate($redirectRoute));
    }

    // Méthode pour obtenir l'URL de connexion
    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
}
