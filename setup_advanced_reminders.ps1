# Script PowerShell pour configurer les rappels avancés automatiques
# À exécuter en tant qu'administrateur

param(
    [string]$TaskName = "EventHub Advanced Reminders",
    [string]$ProjectPath = $PSScriptRoot,
    [string]$Schedule = "Daily"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   CONFIGURATION RAPPELS AVANCES EVENTHUB" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier les privilèges administrateur
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "ERREUR: Ce script doit être exécuté en tant qu'administrateur" -ForegroundColor Red
    Write-Host "Clic droit sur PowerShell > Exécuter en tant qu'administrateur" -ForegroundColor Yellow
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

Write-Host "[INFO] Configuration des rappels avancés automatiques..." -ForegroundColor Green
Write-Host ""

# Vérifier que PHP est disponible
try {
    $phpVersion = php --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP non trouvé"
    }
    Write-Host "[OK] PHP détecté: $($phpVersion[0])" -ForegroundColor Green
} catch {
    Write-Host "[ERREUR] PHP n'est pas installé ou n'est pas dans le PATH" -ForegroundColor Red
    Write-Host "Veuillez installer PHP et réessayer" -ForegroundColor Yellow
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

# Vérifier que le projet Symfony existe
if (-not (Test-Path "$ProjectPath\bin\console")) {
    Write-Host "[ERREUR] Projet Symfony non trouvé dans: $ProjectPath" -ForegroundColor Red
    Write-Host "Vérifiez le chemin du projet" -ForegroundColor Yellow
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

Write-Host "[OK] Projet Symfony trouvé: $ProjectPath" -ForegroundColor Green
Write-Host ""

# Créer le script de rappels
$reminderScript = @"
@echo off
cd /d "$ProjectPath"
echo [%date% %time%] Démarrage des rappels avancés...
php bin/console app:send-event-reminders-advanced --reminder-type=24h
php bin/console app:send-event-reminders-advanced --reminder-type=1h
echo [%date% %time%] Rappels avancés terminés
"@

$scriptPath = "$ProjectPath\run_advanced_reminders.bat"
$reminderScript | Out-File -FilePath $scriptPath -Encoding ASCII
Write-Host "[OK] Script de rappels créé: $scriptPath" -ForegroundColor Green

# Supprimer la tâche existante si elle existe
try {
    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "[INFO] Ancienne tâche supprimée" -ForegroundColor Yellow
    }
} catch {
    # Ignorer les erreurs si la tâche n'existe pas
}

# Créer la nouvelle tâche planifiée
try {
    $action = New-ScheduledTaskAction -Execute $scriptPath
    $trigger = New-ScheduledTaskTrigger -Daily -At "08:00"
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Envoi automatique des rappels 24h et 1h avant les événements EventHub"
    
    Write-Host "[SUCCESS] Tâche planifiée créée avec succès!" -ForegroundColor Green
    Write-Host "  - Nom: $TaskName" -ForegroundColor White
    Write-Host "  - Fréquence: Quotidienne à 08:00" -ForegroundColor White
    Write-Host "  - Script: $scriptPath" -ForegroundColor White
    Write-Host ""
    
    # Tester la tâche
    Write-Host "[INFO] Test de la tâche..." -ForegroundColor Yellow
    Start-ScheduledTask -TaskName $TaskName
    Start-Sleep -Seconds 3
    
    $taskInfo = Get-ScheduledTask -TaskName $TaskName
    if ($taskInfo.State -eq "Running" -or $taskInfo.LastTaskResult -eq 0) {
        Write-Host "[OK] Tâche testée avec succès" -ForegroundColor Green
    } else {
        Write-Host "[ATTENTION] Vérifiez manuellement l'exécution de la tâche" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "[ERREUR] Échec de la création de la tâche planifiée: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   CONFIGURATION TERMINÉE AVEC SUCCÈS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Les rappels avancés sont maintenant configurés pour s'exécuter automatiquement:" -ForegroundColor Green
Write-Host "  • Rappels 24h avant les événements" -ForegroundColor White
Write-Host "  • Rappels 1h avant les événements" -ForegroundColor White
Write-Host "  • Exécution quotidienne à 08:00" -ForegroundColor White
Write-Host ""
Write-Host "Pour tester manuellement:" -ForegroundColor Yellow
Write-Host "  .\send_advanced_reminders.bat" -ForegroundColor White
Write-Host ""
Write-Host "Pour gérer la tâche planifiée:" -ForegroundColor Yellow
Write-Host "  Ouvrir le Planificateur de tâches Windows" -ForegroundColor White
Write-Host "  Rechercher: $TaskName" -ForegroundColor White
Write-Host ""

Read-Host "Appuyez sur Entrée pour quitter"
