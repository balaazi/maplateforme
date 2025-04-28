<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Google_Client;

class GoogleTokenService
{
    private const SESSION_PREFIX = 'google_oauth.';
    private const ACCESS_TOKEN_KEY = self::SESSION_PREFIX.'access_token';
    private const REFRESH_TOKEN_KEY = self::SESSION_PREFIX.'refresh_token';

    public function __construct(
        private RequestStack $requestStack,
        private int $tokenExpiration,
        private Google_Client $googleClient // Assurez-vous d'injecter le client Google
    ) {
    }

    public function storeTokens(string $accessToken, ?string $refreshToken = null): void
    {
        $this->getSession()->set(self::ACCESS_TOKEN_KEY, [
            'token' => $accessToken,
            'expires_at' => time() + $this->tokenExpiration
        ]);

        if ($refreshToken) {
            $this->getSession()->set(self::REFRESH_TOKEN_KEY, $refreshToken);
        }
    }

    public function getAccessToken(): ?string
    {
        $tokenData = $this->getSession()->get(self::ACCESS_TOKEN_KEY);

        if (!$tokenData || $tokenData['expires_at'] < time()) {
            return $this->refreshAccessToken(); // Rafraîchir si expiré
        }

        return $tokenData['token'];
    }

    public function getRefreshToken(): ?string
    {
        return $this->getSession()->get(self::REFRESH_TOKEN_KEY);
    }

    public function clearTokens(): void
    {
        $this->getSession()->remove(self::ACCESS_TOKEN_KEY);
        $this->getSession()->remove(self::REFRESH_TOKEN_KEY);
    }

    public function hasValidToken(): bool
    {
        return $this->getAccessToken() !== null;
    }

    private function refreshAccessToken(): ?string
    {
        $refreshToken = $this->getRefreshToken();
        if ($refreshToken) {
            $this->googleClient->setAccessToken([
                'refresh_token' => $refreshToken
            ]);

            if ($this->googleClient->isAccessTokenExpired()) {
                $newToken = $this->googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
                $this->storeTokens($newToken['access_token'], $newToken['refresh_token'] ?? null);
                return $newToken['access_token'];
            }
        }

        return null; // Si aucun token de rafraîchissement ou échec du rafraîchissement
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
