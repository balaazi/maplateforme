<?php

require_once 'vendor/autoload.php';

use App\Service\GoogleCalendarService;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== VÉRIFICATION AUTHENTIFICATION GOOGLE CALENDAR ===\n\n";

// Créer le service GoogleCalendarService
$googleCalendarService = new GoogleCalendarService(
    new \Doctrine\Persistence\ManagerRegistry(),
    new \Symfony\Bundle\SecurityBundle\Security(),
    new \Psr\Log\NullLogger(),
    $_ENV['GOOGLE_CLIENT_ID'] ?? '',
    $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
    $_ENV['GOOGLE_REDIRECT_URI'] ?? '',
    __DIR__
);

echo "🔧 Configuration:\n";
echo "   GOOGLE_CLIENT_ID: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (isset($_ENV['GOOGLE_CLIENT_SECRET']) ? '✅ Défini' : '❌ Manquant') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . (isset($_ENV['GOOGLE_REDIRECT_URI']) ? '✅ Défini' : '❌ Manquant') . "\n\n";

echo "🔑 Authentification:\n";
echo "   isAuthenticated(): " . ($googleCalendarService->isAuthenticated() ? '✅ Oui' : '❌ Non') . "\n\n";

if (!$googleCalendarService->isAuthenticated()) {
    echo "❌ L'utilisateur n'est pas authentifié avec Google Calendar.\n";
    echo "   Pour résoudre ce problème:\n";
    echo "   1. Aller sur /oauth/connect pour se connecter à Google\n";
    echo "   2. Ou vérifier que le fichier var/google-token.json existe\n";
    echo "   3. Ou vérifier que le token n'est pas expiré\n\n";
    
    // Vérifier le fichier token
    $tokenPath = 'var/google-token.json';
    if (file_exists($tokenPath)) {
        echo "📄 Fichier token trouvé:\n";
        $token = json_decode(file_get_contents($tokenPath), true);
        if ($token && isset($token['access_token'])) {
            echo "   Token valide: ✅ Oui\n";
            echo "   Type: " . ($token['token_type'] ?? 'N/A') . "\n";
            echo "   Expires: " . (isset($token['expires_in']) ? $token['expires_in'] . 's' : 'N/A') . "\n";
        } else {
            echo "   Token valide: ❌ Non\n";
        }
    } else {
        echo "📄 Fichier token: ❌ N'existe pas\n";
    }
} else {
    echo "✅ L'utilisateur est authentifié avec Google Calendar.\n";
    echo "   L'ajout d'événements à l'agenda devrait fonctionner.\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";
