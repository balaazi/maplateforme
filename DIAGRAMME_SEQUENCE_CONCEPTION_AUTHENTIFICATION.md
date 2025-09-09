# 🔧 DIAGRAMME DE SÉQUENCE - CONCEPTION D'AUTHENTIFICATION EVENTHUB

## 🎯 Vue d'Ensemble de la Conception

Ce diagramme présente les détails d'implémentation du système d'authentification EventHub, montrant les classes, méthodes et interactions techniques spécifiques.

---

## 📊 DIAGRAMME DE SÉQUENCE - CONCEPTION DÉTAILLÉE

```mermaid
sequenceDiagram
    participant U as 👤 Utilisateur
    participant B as 🌐 Navigateur
    participant SC as 🔒 SecurityController
    participant S as ⚙️ Symfony Framework
    participant A as 🔐 AppAuthenticator
    participant R as 📊 UserRepository
    participant DB as 🗄️ Base de Données
    participant H as 🚫 AccessDeniedHandler
    participant E as 📧 EmailService
    participant UH as 🔑 UserPasswordHasher

    Note over U,DB: PHASE 1: AFFICHAGE DU FORMULAIRE DE CONNEXION
    
    U->>B: Accès à /login
    activate B
    B->>SC: GET /login
    activate SC
    
    SC->>SC: getUser() - Vérification session
    alt Utilisateur déjà connecté
        SC->>SC: redirectToRoute('app_home')
        SC->>B: RedirectResponse('/')
        deactivate SC
        B-->>U: Redirection vers accueil
        deactivate B
    else Utilisateur non connecté
        SC->>SC: getLastAuthenticationError()
        SC->>SC: getLastUsername()
        SC->>B: render('security/login.html.twig', {error, last_username})
        deactivate SC
        B-->>U: Affichage formulaire avec gestion erreurs
        deactivate B
    end

    Note over U,DB: PHASE 2: SOUMISSION DES CREDENTIALS
    
    U->>B: Saisit email + mot de passe + CSRF token
    activate B
    B->>S: POST /login avec FormData
    activate S
    
    Note over S: SYMFONY SECURITY FRAMEWORK
    S->>S: Détection du firewall 'main'
    S->>S: Appel de l'authentificateur personnalisé
    
    S->>A: authenticate(Request $request)
    activate A
    
    Note over A: VALIDATION DES DONNÉES D'ENTRÉE
    A->>A: $email = $request->request->get('email')
    A->>A: $password = $request->request->get('password')
    A->>A: $csrfToken = $request->request->get('_csrf_token')
    
    A->>A: if (empty($email) || empty($password))
    alt Données manquantes
        A->>A: throw new BadCredentialsException('Email et mot de passe requis')
        A-->>S: Exception
        deactivate A
        S->>S: Gestion de l'erreur
        S-->>B: Response avec erreur
        deactivate S
        B-->>U: Affichage message d'erreur
        deactivate B
    else Données présentes
        Note over A: VALIDATION CSRF
        A->>A: Vérification CSRF token 'authenticate'
        
        Note over A: RECHERCHE UTILISATEUR
        A->>R: findOneBy(['email' => $email])
        activate R
        R->>DB: SELECT * FROM users WHERE email = ?
        activate DB
        DB-->>R: User entity ou null
        deactivate DB
        
        alt Utilisateur trouvé
            R-->>A: User object avec rôles
            A->>A: Log: "User found - ID: " . $user->getId()
        else Utilisateur non trouvé
            R-->>A: null
            A->>A: Log: "Authentication failed: User not found"
        end
        deactivate R
        
        Note over A: CRÉATION DU PASSPORT D'AUTHENTIFICATION
        A->>A: new Passport(
        A->>A:     new UserBadge($email),
        A->>A:     new PasswordCredentials($password),
        A->>A:     [new CsrfTokenBadge('authenticate', $csrfToken)]
        A->>A: )
        A-->>S: Passport object
        deactivate A
        
        Note over S: VÉRIFICATION AUTOMATIQUE DES CREDENTIALS
        S->>S: Vérification du hash du mot de passe
        S->>S: Validation du CSRF token
        
        alt Authentification réussie
            S->>A: onAuthenticationSuccess(Request, TokenInterface, string)
            activate A
            
            Note over A: RÉCUPÉRATION DES RÔLES RÉELS
            A->>A: $user = $token->getUser()
            A->>R: findOneByEmail($user->getUserIdentifier())
            activate R
            R->>DB: SELECT * FROM users WHERE email = ? WITH JOIN roles
            activate DB
            DB-->>R: User avec rôles complets depuis la DB
            deactivate DB
            R-->>A: User object avec rôles réels
            deactivate R
            
            Note over A: LOGIQUE DE REDIRECTION INTELLIGENTE
            A->>A: $roles = $user->getRoles()
            A->>A: Analyse de la hiérarchie des rôles
            
            alt ROLE_ADMIN présent
                A->>A: $targetPath = 'admin_dashboard'
            else ROLE_ORGANISATEUR présent
                A->>A: $targetPath = 'organisateur_dashboard'
            else ROLE_PARTICIPANT présent
                A->>A: $targetPath = 'participant_dashboard'
            else Rôle par défaut
                A->>A: $targetPath = 'calendar_index'
            end
            
            A->>S: new RedirectResponse($this->router->generate($targetPath))
            deactivate A
            
            S-->>B: Response avec redirection
            deactivate S
            
            B-->>U: Redirection vers dashboard approprié
            deactivate B
            
        else Authentification échouée
            S->>S: BadCredentialsException
            S->>S: Log de l'échec d'authentification
            S-->>B: Response avec erreur
            deactivate S
            
            B-->>U: Affichage message d'erreur
            deactivate B
        end
    end

    Note over U,DB: PHASE 3: GESTION DES SESSIONS ET SÉCURITÉ
    
    alt Session active
        Note over S: VALIDATION CONTINUE
        S->>S: Vérification du token d'authentification
        S->>S: Validation des permissions à chaque requête
        S->>S: Renouvellement automatique de la session
        
    else Session expirée ou invalide
        S->>S: Invalidation de la session
        S->>SC: Redirection vers /login
        activate SC
        SC->>B: render('security/login.html.twig')
        deactivate SC
        B-->>U: Formulaire de connexion
        deactivate B
    end

    Note over U,DB: PHASE 4: DÉCONNEXION SÉCURISÉE
    
    U->>B: Clic sur "Déconnexion"
    activate B
    B->>S: POST /logout avec CSRF token
    activate S
    
    Note over S: VALIDATION ET NETTOYAGE COMPLET
    S->>S: Validation du CSRF token 'logout'
    S->>S: Invalidation de la session utilisateur
    S->>S: Suppression du token d'authentification
    S->>S: Suppression des cookies de session
    S->>S: Nettoyage du cache de sécurité
    
    S->>S: redirectToRoute('app_home')
    S-->>B: RedirectResponse('/')
    deactivate S
    B-->>U: Redirection vers page d'accueil
    deactivate B
```

---

## 🏗️ ARCHITECTURE DE CONCEPTION DÉTAILLÉE

### Classes et Méthodes Principales

```
┌─────────────────────────────────────────────────────────────┐
│                CONCEPTION D'AUTHENTIFICATION                │
├─────────────────────────────────────────────────────────────┤
│  SecurityController                                         │
│  ├─ login(AuthenticationUtils) : Response                  │
│  ├─ redirectAfterLogin(Security) : Response                │
│  └─ logout() : void                                        │
├─────────────────────────────────────────────────────────────┤
│  AppAuthenticator (extends AbstractLoginFormAuthenticator) │
│  ├─ authenticate(Request) : Passport                       │
│  ├─ onAuthenticationSuccess(...) : ?Response               │
│  ├─ onAuthenticationFailure(...) : ?Response               │
│  └─ supports(Request) : bool                               │
├─────────────────────────────────────────────────────────────┤
│  UserRepository (extends ServiceEntityRepository)          │
│  ├─ findOneByEmail(string) : ?User                         │
│  ├─ findByRole(string) : array                             │
│  └─ findActiveUsers() : array                              │
├─────────────────────────────────────────────────────────────┤
│  AccessDeniedHandler (implements AccessDeniedHandlerInterface) │
│  ├─ handle(Request, AccessDeniedException) : Response     │
│  └─ determineRedirectPath(Request) : string                │
└─────────────────────────────────────────────────────────────┘
```

### Implémentation Technique

#### 1. **AppAuthenticator - Méthode authenticate()**
```php
public function authenticate(Request $request): Passport
{
    $email = $request->request->get('email');
    $password = $request->request->get('password');
    $csrfToken = $request->request->get('_csrf_token');

    // Validation des données
    if (empty($email) || empty($password)) {
        throw new BadCredentialsException('Email et mot de passe requis');
    }

    // Recherche utilisateur
    $user = $this->userRepository->findOneBy(['email' => $email]);
    
    // Création du Passport
    return new Passport(
        new UserBadge($email),
        new PasswordCredentials($password),
        [new CsrfTokenBadge('authenticate', $csrfToken)]
    );
}
```

#### 2. **AppAuthenticator - Méthode onAuthenticationSuccess()**
```php
public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    $user = $token->getUser();
    
    // Récupération des rôles réels depuis la DB
    $userFromDb = $this->userRepository->findOneByEmail($user->getUserIdentifier());
    $realRoles = $userFromDb ? $userFromDb->getRoles() : $user->getRoles();
    
    // Logique de redirection par rôle
    if (in_array('ROLE_ADMIN', $realRoles)) {
        return new RedirectResponse($this->router->generate('admin_dashboard'));
    } elseif (in_array('ROLE_ORGANISATEUR', $realRoles)) {
        return new RedirectResponse($this->router->generate('organisateur_dashboard'));
    } elseif (in_array('ROLE_PARTICIPANT', $realRoles)) {
        return new RedirectResponse($this->router->generate('participant_dashboard'));
    }
    
    return new RedirectResponse($this->router->generate('calendar_index'));
}
```

---

## 🔒 IMPLÉMENTATION DE LA SÉCURITÉ

### 1. **Protection CSRF**
```yaml
# config/packages/framework.yaml
framework:
    csrf_protection:
        enabled: true
        stateless_token_ids:
            - submit
            - authenticate
            - logout
```

### 2. **Configuration du Firewall**
```yaml
# config/packages/security.yaml
firewalls:
    main:
        lazy: true
        provider: user_provider
        entry_point: App\Security\AppAuthenticator
        custom_authenticator: App\Security\AppAuthenticator
        access_denied_handler: App\Security\AccessDeniedHandler
        logout:
            path: /logout
            target: app_home
            csrf_parameter: '_csrf_token'
            csrf_token_id: 'logout'
            invalidate_session: true
```

### 3. **Gestion des Sessions**
```yaml
# config/packages/framework.yaml
framework:
    session:
        handler_id: 'session.handler.native_file'
        save_path: '%kernel.project_dir%/var/sessions/%kernel.environment%'
        cookie_secure: auto
        cookie_httponly: true
        cookie_samesite: 'lax'
        cookie_lifetime: 3600
        enabled: true
```

---

## 📊 GESTION DES ERREURS ET LOGS

### 1. **Logs d'Authentification**
```php
// Dans AppAuthenticator
error_log("Authentication attempt - Email: " . $email);
error_log("User found - ID: " . $user->getId());
error_log("Password length: " . strlen($password));
error_log("User password hash: " . $user->getPassword());
```

### 2. **Gestion des Exceptions**
```php
try {
    // Tentative d'authentification
    $passport = $this->authenticate($request);
} catch (BadCredentialsException $e) {
    error_log("Bad credentials for email: " . $email);
    throw $e;
} catch (CsrfTokenException $e) {
    error_log("CSRF token validation failed");
    throw $e;
}
```

### 3. **Messages d'Erreur Utilisateur**
```php
// Dans SecurityController
$error = $authenticationUtils->getLastAuthenticationError();
$lastUsername = $authenticationUtils->getLastUsername();

return $this->render('security/login.html.twig', [
    'last_username' => $lastUsername,
    'error' => $error,
]);
```

---

## 🚀 OPTIMISATIONS ET PERFORMANCE

### 1. **Requêtes Optimisées**
```php
// UserRepository - Méthode optimisée
public function findOneByEmail(string $email): ?User
{
    return $this->createQueryBuilder('u')
        ->select('u', 'r') // Jointure avec les rôles
        ->leftJoin('u.roles', 'r')
        ->where('u.email = :email')
        ->setParameter('email', $email)
        ->getQuery()
        ->getOneOrNullResult();
}
```

### 2. **Cache des Rôles**
```php
// Mise en cache des rôles pour éviter les requêtes répétées
private function getUserRoles(User $user): array
{
    $cacheKey = 'user_roles_' . $user->getId();
    
    if ($this->cache->hasItem($cacheKey)) {
        return $this->cache->getItem($cacheKey)->get();
    }
    
    $roles = $user->getRoles();
    $this->cache->getItem($cacheKey)->set($roles);
    
    return $roles;
}
```

### 3. **Validation Asynchrone**
```php
// Validation des credentials en arrière-plan
public function validateCredentialsAsync(string $email, string $password): Promise
{
    return $this->asyncValidator->validate([
        'email' => $email,
        'password' => $password
    ]);
}
```

---

## 🔍 TESTS ET VALIDATION

### 1. **Tests Unitaires**
```php
// tests/Security/AppAuthenticatorTest.php
public function testAuthenticateWithValidCredentials(): void
{
    $request = new Request([], [
        'email' => 'test@example.com',
        'password' => 'password123',
        '_csrf_token' => 'valid_token'
    ]);
    
    $passport = $this->authenticator->authenticate($request);
    
    $this->assertInstanceOf(Passport::class, $passport);
    $this->assertInstanceOf(UserBadge::class, $passport->getUser());
    $this->assertInstanceOf(PasswordCredentials::class, $passport->getCredentials());
}
```

### 2. **Tests d'Intégration**
```php
// tests/Controller/SecurityControllerTest.php
public function testLoginWithValidCredentials(): void
{
    $client = static::createClient();
    
    $crawler = $client->request('GET', '/login');
    $this->assertResponseIsSuccessful();
    
    $client->submitForm('Se connecter', [
        'email' => 'admin@eventhub.com',
        'password' => 'admin123'
    ]);
    
    $this->assertResponseRedirects('/admin/dashboard');
}
```

---

## 📈 MÉTRIQUES ET MONITORING

### 1. **Métriques de Performance**
- Temps de réponse d'authentification
- Taux de succès/échec
- Utilisation de la mémoire
- Temps de requête en base

### 2. **Métriques de Sécurité**
- Tentatives de connexion échouées
- Violations CSRF détectées
- Sessions expirées
- Accès refusés par rôle

### 3. **Alertes et Notifications**
```php
// Service de monitoring
public function logSecurityEvent(string $event, array $context = []): void
{
    $this->logger->warning('Security event: ' . $event, $context);
    
    if ($this->shouldSendAlert($event)) {
        $this->alertService->sendSecurityAlert($event, $context);
    }
}
```

---

## 🎯 POINTS CLÉS DE LA CONCEPTION

### 1. **Sécurité**
- ✅ Validation stricte des données d'entrée
- ✅ Protection CSRF sur toutes les actions sensibles
- ✅ Gestion sécurisée des sessions
- ✅ Logs détaillés pour l'audit

### 2. **Performance**
- ✅ Requêtes optimisées avec jointures
- ✅ Cache des informations utilisateur
- ✅ Validation asynchrone possible
- ✅ Sessions légères

### 3. **Maintenabilité**
- ✅ Architecture modulaire et extensible
- ✅ Code documenté et testé
- ✅ Gestion centralisée des erreurs
- ✅ Configuration externalisée

---

**Ce diagramme de séquence de conception montre l'implémentation technique détaillée du système d'authentification EventHub, garantissant une architecture robuste, sécurisée et performante.**
