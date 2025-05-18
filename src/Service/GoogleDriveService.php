<?php

namespace App\Service;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getClient(): Client
    {
        $client = new Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->addScope(Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function getDriveService(): Drive
    {
        $client = $this->getClient();
        return new Drive($client);
    }
public function createFolder(string $folderName, string $parentFolderId = null): ?string
{
    $client = $this->getClient();
    $accessToken = $_SESSION['google_access_token'] ?? null;

    if (!$accessToken) {
        return null;
    }

    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {
        return null;
    }

    $service = new Drive($client);

    $folderMetadata = new DriveFile([
        'name' => $folderName,
        'mimeType' => 'application/vnd.google-apps.folder',
    ]);

    if ($parentFolderId) {
        $folderMetadata->setParents([$parentFolderId]);
    }

    $folder = $service->files->create($folderMetadata, ['fields' => 'id']);

    return $folder->id ?? null;
}
}
