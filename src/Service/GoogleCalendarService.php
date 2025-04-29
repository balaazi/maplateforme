<?php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleServiceCalendar;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GoogleCalendarService
{
    private GoogleClient $client;
    private $user;
    private EntityManagerInterface $em;
    private RequestStack $requestStack;

    public function __construct(
        string $googleClientId,
        string $googleClientSecret,
        private UrlGeneratorInterface $router,
        private Security $security,
        EntityManagerInterface $em,
        RequestStack $requestStack
    ) {
        $this->user = $this->security->getUser();
        $this->em = $em;
        $this->requestStack = $requestStack;

        $this->initializeClient($googleClientId, $googleClientSecret);
        $this->handleExistingToken();
    }

    private function initializeClient(string $clientId, string $clientSecret): void
    {
        $this->client = new GoogleClient([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->generateRedirectUri(),
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'include_granted_scopes' => true
        ]);

        $this->client->setScopes([GoogleServiceCalendar::CALENDAR_EVENTS]);
        $this->client->setState($this->generateStateToken());
    }

    private function generateRedirectUri(): string
    {
        return $this->router->generate(
            'google_calendar_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function generateStateToken(): string
    {
        $state = bin2hex(random_bytes(16));
        $this->requestStack->getSession()->set('oauth_state', $state);
        return $state;
    }

    private function handleExistingToken(): void
    {
        if ($this->user && method_exists($this->user, 'getGoogleToken')) {
            $token = $this->user->getGoogleToken();
            
            if ($token && $decodedToken = json_decode($token, true)) {
                $this->client->setAccessToken($decodedToken);
                
                if ($this->isTokenExpired()) {
                    $this->refreshToken();
                }
            }
        }
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code, string $state): void
    {
        $this->validateStateToken($state);
        $token = $this->fetchAccessToken($code);
        $this->storeToken($token);
    }

    private function validateStateToken(string $state): void
    {
        $sessionState = $this->requestStack->getSession()->get('oauth_state');
        
        if (!$sessionState || $sessionState !== $state) {
            throw new AuthenticationException('Invalid state parameter');
        }
    }

    private function fetchAccessToken(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \RuntimeException($token['error_description'] ?? 'Authentication failed');
        }

        return $token;
    }

    private function storeToken(array $token): void
    {
        if ($this->user && method_exists($this->user, 'setGoogleToken')) {
            $this->client->setAccessToken($token);
            $this->user->setGoogleToken(json_encode($token));
            
            $this->em->persist($this->user);
            $this->em->flush();
        }
    }

    public function getCalendarService(): GoogleServiceCalendar
    {
        if ($this->isTokenExpired()) {
            $this->refreshToken();
        }
        
        return new GoogleServiceCalendar($this->client);
    }

    public function isTokenExpired(): bool
    {
        return $this->client->isAccessTokenExpired();
    }

    public function refreshToken(): void
    {
        if (!$refreshToken = $this->client->getRefreshToken()) {
            throw new \RuntimeException('No refresh token available');
        }

        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $this->storeToken($this->client->getAccessToken());
    }

    public function revokeToken(): bool
    {
        if (!$this->client->getAccessToken()) {
            return false;
        }
        
        return $this->client->revokeToken();
    }
}