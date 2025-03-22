<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator; // Correct class for password hashing in Symfony 7
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class FormLoginAuthenticator extends AbstractAuthenticator
{
    private PasswordHasherInterface $passwordHasher;

    // Inject the PasswordHasherInterface in the constructor
    public function __construct(PasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username');
        $password = $request->request->get('_password');

        return new Passport(
            new UserBadge($email),  // Badge utilisateur pour l'authentification
            new PasswordCredentials($password)  // Credentials avec le mot de passe
        );
    }

    public function checkCredentials($credentials, $user): bool
    {
        // Check if the password matches using the PasswordHasherInterface
        return $this->passwordHasher->verify($user->getPassword(), $credentials->getPassword());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to the home page or wherever after login success
        return new Response('Success', Response::HTTP_OK);
        // Or you can return a redirect
        // return new RedirectResponse('/home');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Action en cas d'échec
        return new Response('Échec de l\'authentification', Response::HTTP_UNAUTHORIZED);
    }
}
