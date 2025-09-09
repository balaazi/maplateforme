# 🔐 DIAGRAMME DE SÉQUENCE - SYSTÈME D'AUTHENTIFICATION EVENTHUB

## 🎯 Vue d'Ensemble du Système

Ce diagramme présente le flux d'authentification au niveau système, montrant l'interaction entre tous les composants de sécurité d'EventHub.

---

## 📊 DIAGRAMME DE SÉQUENCE - SYSTÈME COMPLET

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

    Note over U,DB: PHASE 1: ACCÈS AU SYSTÈME
    
    U->>B: Accès à une page protégée
    B->>S: GET /protected-route
    activate S
    
    alt Utilisateur non authentifié
        S->>S: Détection firewall
        S->>SC: Redirection vers /login
        activate SC
        SC->>SC: Vérification getUser()
        SC->>B: render(login.html.twig)
        deactivate SC
        B-->>U: Affichage formulaire de connexion
    else Utilisateur authentifié
        S->>S: Vérification des rôles
        alt Accès autorisé
            S->>S: Traitement de la requête
            S-->>B: Response normale
            deactivate S
            B-->>U: Affichage de la page
        else Accès refusé
            S->>H: handle()
            activate H
            H->>H: Analyse du contexte
            H->>B: RedirectResponse(access_denied)
            deactivate H
            B-->>U: Page d'accès refusé
        end
    end

    Note over U,DB: PHASE 2: PROCESSUS D'AUTHENTIFICATION
    
    U->>B: Saisit credentials
    B->>S: POST /login avec données
    activate S
    
    S->>A: authenticate(Request)
    activate A
    
    Note over A: VALIDATION DES DONNÉES
    A->>A: Vérification email/password non vides
    A->>A: Validation CSRF token
    
    A->>R: findOneBy(['email' => email])
    activate R
    R->>DB: SELECT * FROM users WHERE email = ?
    activate DB
    DB-->>R: User entity ou null
    deactivate DB
    R-->>A: User object
    deactivate R
    
    Note over A: CRÉATION DU PASSPORT
    A->>A: new Passport(UserBadge, PasswordCredentials, CsrfTokenBadge)
    A-->>S: Passport object
    deactivate A
    
    Note over S: VÉRIFICATION AUTOMATIQUE
    S->>S: Vérification hash password
    S->>S: Validation CSRF token
    
    alt Authentification réussie
        S->>A: onAuthenticationSuccess()
        activate A
        
        A->>R: findOneByEmail() pour rôles réels
        activate R
        R->>DB: SELECT * FROM users WHERE email = ?
        activate DB
        DB-->>R: User avec rôles complets
        deactivate DB
        R-->>A: User object
        deactivate R
        
        Note over A: LOGIQUE DE REDIRECTION PAR RÔLE
        A->>A: Analyse des rôles utilisateur
        
        alt ROLE_ADMIN + ROLE_ORGANISATEUR
            A->>S: RedirectResponse(common_dashboard)
        else ROLE_ADMIN uniquement
            A->>S: RedirectResponse(admin_dashboard)
        else ROLE_ORGANISATEUR uniquement
            A->>S: RedirectResponse(organisateur_dashboard)
        else ROLE_PARTICIPANT uniquement
            A->>S: RedirectResponse(participant_dashboard)
        else Rôle par défaut
            A->>S: RedirectResponse(calendar_index)
        end
        
        deactivate A
        
        S-->>B: Response avec redirection
        deactivate S
        
        B-->>U: Redirection vers dashboard approprié
        
    else Authentification échouée
        S->>S: BadCredentialsException
        S-->>B: Response avec erreur
        deactivate S
        
        B-->>U: Affichage message d'erreur
    end

    Note over U,DB: PHASE 3: GESTION DES SESSIONS
    
    alt Session active
        S->>S: Validation session à chaque requête
        S->>S: Renouvellement automatique
        S->>S: Vérification des permissions
    else Session expirée
        S->>S: Invalidation session
        S->>SC: Redirection vers /login
        activate SC
        SC->>B: render(login.html.twig)
        deactivate SC
        B-->>U: Formulaire de connexion
    end

    Note over U,DB: PHASE 4: DÉCONNEXION
    
    U->>B: Clic sur "Déconnexion"
    B->>S: POST /logout avec CSRF token
    
    Note over S: VALIDATION ET NETTOYAGE
    S->>S: Validation CSRF token
    S->>S: Invalidation de la session
    S->>S: Suppression du token d'authentification
    S->>S: Suppression des cookies de session
    
    S-->>B: RedirectResponse(app_home)
    deactivate S
    B-->>U: Redirection vers page d'accueil
```

---

## 🏗️ ARCHITECTURE DU SYSTÈME D'AUTHENTIFICATION

### Composants Principaux

```
┌─────────────────────────────────────────────────────────────┐
│                    SYSTÈME D'AUTHENTIFICATION               │
├─────────────────────────────────────────────────────────────┤
│  Contrôleurs de Sécurité                                   │
│  ├─ SecurityController (Gestion des formulaires)           │
│  ├─ AdminController (Invitations et gestion)               │
│  └─ AccessDeniedHandler (Gestion des accès refusés)        │
├─────────────────────────────────────────────────────────────┤
│  Authentificateur Personnalisé                             │
│  ├─ AppAuthenticator (Logique d'authentification)          │
│  ├─ Validation des credentials                             │
│  ├─ Création des Passports                                 │
│  └─ Redirection intelligente par rôle                      │
├─────────────────────────────────────────────────────────────┤
│  Gestion des Utilisateurs                                  │
│  ├─ UserRepository (Accès aux données)                     │
│  ├─ User Entity (Modèle de données)                        │
│  └─ Hiérarchie des rôles                                   │
├─────────────────────────────────────────────────────────────┤
│  Sécurité et Protection                                    │
│  ├─ Firewall Symfony                                       │
│  ├─ Protection CSRF                                        │
│  ├─ Gestion des sessions                                   │
│  └─ Contrôle d'accès                                       │
└─────────────────────────────────────────────────────────────┘
```

### Hiérarchie des Rôles

```
ROLE_USER (base)
    ↓
ROLE_PARTICIPANT (accès aux événements)
    ↓
ROLE_ORGANISATEUR (gestion des événements)
    ↓
ROLE_ADMIN (administration complète)
```

---

## 🔒 MESURES DE SÉCURITÉ IMPLÉMENTÉES

### 1. **Protection CSRF**
- Tokens uniques pour chaque formulaire
- Validation automatique par Symfony
- Protection sur toutes les actions sensibles

### 2. **Gestion des Sessions**
- Sessions sécurisées avec cookies HttpOnly
- Expiration automatique configurable
- Invalidation à la déconnexion

### 3. **Validation des Données**
- Validation côté serveur obligatoire
- Sanitisation des entrées utilisateur
- Messages d'erreur informatifs

### 4. **Contrôle d'Accès**
- Vérification des rôles à chaque requête
- Redirection intelligente selon les permissions
- Gestion des accès refusés personnalisée

---

## 📊 FLUX DE DONNÉES ET INTERACTIONS

### Interactions Clés

1. **Utilisateur ↔ Navigateur** : Interface utilisateur
2. **Navigateur ↔ Symfony** : Requêtes HTTP
3. **Symfony ↔ AppAuthenticator** : Logique d'authentification
4. **AppAuthenticator ↔ UserRepository** : Accès aux données
5. **UserRepository ↔ Base de Données** : Persistance des données

### Points de Validation

- **Entrée** : Validation des credentials
- **Processus** : Vérification des permissions
- **Sortie** : Redirection appropriée
- **Sécurité** : Protection CSRF et sessions

---

## 🚀 AVANTAGES DU SYSTÈME

### Sécurité
- ✅ Protection CSRF intégrée
- ✅ Sessions sécurisées
- ✅ Validation stricte des données
- ✅ Gestion des accès refusés

### Performance
- ✅ Requêtes optimisées en base
- ✅ Cache des informations utilisateur
- ✅ Sessions légères
- ✅ Redirection intelligente

### Maintenabilité
- ✅ Architecture modulaire
- ✅ Code réutilisable
- ✅ Configuration centralisée
- ✅ Logs détaillés

---

## 🔍 POINTS DE CONTRÔLE ET MONITORING

### Logs d'Authentification
```php
// Dans AppAuthenticator
error_log("Authentication attempt - Email: " . $email);
error_log("User found - ID: " . $user->getId());
error_log("Authentication successful for user: " . $email);
```

### Métriques de Sécurité
- Tentatives de connexion (succès/échec)
- Violations d'accès détectées
- Sessions créées/détruites
- Temps de réponse d'authentification

---

## 📈 ÉVOLUTIONS FUTURES

### Améliorations Possibles
1. **Authentification Multi-Facteurs** (MFA)
2. **Gestion des Sessions Avancée**
3. **Intégration OAuth/SSO**
4. **Audit et Monitoring Avancé**

### Extensibilité
- Architecture modulaire permettant l'ajout de nouveaux fournisseurs d'authentification
- Support des nouveaux types de rôles
- Intégration avec des systèmes externes

---

**Ce diagramme de séquence système montre l'architecture complète et robuste du système d'authentification EventHub, garantissant sécurité, performance et maintenabilité.**
