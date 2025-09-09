# Script PowerShell pour l'expiration automatique des invitations
# Ce script s'exécute automatiquement pour marquer les invitations expirées

param(
    [int]$Days = 30,
    [switch]$Silent = $false
)

# Configuration
$LogFile = "logs\expiration_invitations.log"
$ProjectPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Créer le dossier logs s'il n'existe pas
if (!(Test-Path "logs")) {
    New-Item -ItemType Directory -Path "logs" -Force | Out-Null
}

# Fonction de logging
function Write-Log {
    param([string]$Message)
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogMessage = "[$Timestamp] $Message"
    
    if (!$Silent) {
        Write-Host $LogMessage
    }
    
    Add-Content -Path $LogFile -Value $LogMessage -Encoding UTF8
}

try {
    Write-Log "=== Début de l'expiration automatique des invitations ==="
    Write-Log "Délai d'expiration: $Days jours"
    
    # Changer vers le répertoire du projet
    Set-Location $ProjectPath
    
    # Exécuter la commande Symfony
    $Command = "php bin/console app:expire-invitations --days=$Days"
    Write-Log "Exécution de la commande: $Command"
    
    $Result = Invoke-Expression $Command 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Log "✅ Commande exécutée avec succès"
        Write-Log "Résultat: $Result"
    } else {
        Write-Log "❌ Erreur lors de l'exécution de la commande"
        Write-Log "Code de sortie: $LASTEXITCODE"
        Write-Log "Erreur: $Result"
    }
    
} catch {
    Write-Log "❌ Erreur critique: $($_.Exception.Message)"
    Write-Log "Stack trace: $($_.ScriptStackTrace)"
} finally {
    Write-Log "=== Fin de l'expiration automatique des invitations ==="
    Write-Log ""
}

# Retourner le code de sortie
exit $LASTEXITCODE
