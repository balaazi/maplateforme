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
    
        // Vérifier si l'utilisateur existe
        $user = $this->userRepository->findOneByEmail($email);
        if (!$user) {
            throw new BadCredentialsException('Utilisateur non trouvé.');
        }
    
        // Vérifier la correspondance du mot de passe
        if (!password_verify($password, $user->getPassword())) {
            throw new BadCredentialsException('Mot de passe incorrect.');
        }
    
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

        // Redirection basée sur le rôle de l'utilisateur
        $redirectRoute = match (true) {
            in_array('ROLE_ADMIN', $user->getRoles()) => 'admin_dashboard',
            in_array('ROLE_PARTICIPANT', $user->getRoles()) => 'participant_dashboard',
            in_array('ROLE_ORGANISATEUR', $user->getRoles()) => 'organisateur_dashboard',
            default => 'default_dashboard', // Redirection par défaut si aucun rôle spécifique
        };

        return new RedirectResponse($this->router->generate($redirectRoute));
    }

    // Méthode pour obtenir l'URL de connexion
    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
}
