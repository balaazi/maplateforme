# Script PowerShell pour corriger les permissions du répertoire d'uploads
# À exécuter en tant qu'administrateur

# Vérifier si le script est exécuté en tant qu'administrateur
if (-NOT ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Warning "Ce script doit être exécuté en tant qu'administrateur."
    Write-Warning "Veuillez ouvrir PowerShell en tant qu'administrateur et réexécuter le script."
    pause
    exit
}

# Définir les chemins
$uploadDir = "C:\xampp\htdocs\new\maplateforme\public\uploads\documents"
$uploadParent = "C:\xampp\htdocs\new\maplateforme\public\uploads"

# Afficher l'en-tête
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Correction des permissions d'upload" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier si le répertoire existe
if (-Not (Test-Path $uploadDir)) {
    Write-Host "Le répertoire $uploadDir n'existe pas." -ForegroundColor Yellow
    Write-Host "Création du répertoire..." -ForegroundColor Yellow
    try {
        New-Item -Path $uploadDir -ItemType Directory -Force | Out-Null
        Write-Host "Répertoire créé avec succès." -ForegroundColor Green
    } catch {
        Write-Host "Impossible de créer le répertoire: $_" -ForegroundColor Red
        pause
        exit
    }
}

# Récupérer l'utilisateur actuel
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

# Récupérer l'utilisateur du service Apache/PHP
$apacheUser = "IUSR"
$apacheGroup = "IIS_IUSRS"

# 1. Réinitialiser les ACL
Write-Host "1. Réinitialisation des ACL sur $uploadDir" -ForegroundColor Cyan
try {
    icacls $uploadDir /reset
    Write-Host "   ACL réinitialisées avec succès." -ForegroundColor Green
} catch {
    Write-Host "   Erreur lors de la réinitialisation des ACL: $_" -ForegroundColor Red
}

# 2. Configurer les nouvelles permissions
Write-Host "2. Configuration des nouvelles permissions" -ForegroundColor Cyan

# Ajouter des permissions pour l'utilisateur IUSR (utilisé par IIS)
try {
    icacls $uploadDir /grant "IUSR:(OI)(CI)(F)"
    Write-Host "   Permissions accordées à IUSR." -ForegroundColor Green
} catch {
    Write-Host "   Erreur lors de l'attribution des permissions à IUSR: $_" -ForegroundColor Red
}

# Ajouter des permissions pour le groupe IIS_IUSRS
try {
    icacls $uploadDir /grant "IIS_IUSRS:(OI)(CI)(F)"
    Write-Host "   Permissions accordées à IIS_IUSRS." -ForegroundColor Green
} catch {
    Write-Host "   Erreur lors de l'attribution des permissions à IIS_IUSRS: $_" -ForegroundColor Red
}

# Ajouter des permissions pour l'utilisateur SYSTEM
try {
    icacls $uploadDir /grant "SYSTEM:(OI)(CI)(F)"
    Write-Host "   Permissions accordées à SYSTEM." -ForegroundColor Green
} catch {
    Write-Host "   Erreur lors de l'attribution des permissions à SYSTEM: $_" -ForegroundColor Red
}

# Ajouter des permissions pour les utilisateurs authentifiés
try {
    icacls $uploadDir /grant "Utilisateurs authentifiés:(OI)(CI)(F)"
    Write-Host "   Permissions accordées aux Utilisateurs authentifiés." -ForegroundColor Green
} catch {
    Write-Host "   Erreur lors de l'attribution des permissions aux Utilisateurs authentifiés: $_" -ForegroundColor Red
}

# Ajouter des permissions pour l'utilisateur actuel
try {
    icacls $uploadDir /grant "${currentUser}:(OI)(CI)(F)"
    Write-Host "   Permissions accordées à l'utilisateur actuel ($currentUser)." -ForegroundColor Green
} catch {
    Write-Host "   Erreur lors de l'attribution des permissions à l'utilisateur actuel: $_" -ForegroundColor Red
}

# 3. Vérifier si le service Apache est en cours d'exécution
Write-Host "3. Vérification du service Apache" -ForegroundColor Cyan
$apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue

if ($apacheService) {
    Write-Host "   Service Apache trouvé: $($apacheService.Name)" -ForegroundColor Green
    Write-Host "   État actuel: $($apacheService.Status)" -ForegroundColor Yellow
    
    # Proposer de redémarrer le service
    $restart = Read-Host "Voulez-vous redémarrer le service Apache? (O/N)"
    if ($restart -eq "O" -or $restart -eq "o") {
        try {
            Restart-Service -Name $apacheService.Name -Force
            Write-Host "   Service Apache redémarré avec succès." -ForegroundColor Green
        } catch {
            Write-Host "   Erreur lors du redémarrage du service Apache: $_" -ForegroundColor Red
        }
    }
} else {
    Write-Host "   Service Apache non trouvé. Veuillez redémarrer XAMPP manuellement." -ForegroundColor Yellow
}

# 4. Afficher les permissions finales
Write-Host "4. Permissions finales du répertoire" -ForegroundColor Cyan
try {
    $acl = icacls $uploadDir
    Write-Host $acl -ForegroundColor Gray
} catch {
    Write-Host "   Erreur lors de l'affichage des permissions: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "Opération terminée." -ForegroundColor Green
Write-Host "Si les problèmes persistent, veuillez redémarrer le serveur Apache/XAMPP." -ForegroundColor Yellow
Write-Host ""

pause