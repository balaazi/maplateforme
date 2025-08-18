# Script PowerShell pour configurer le worker Messenger automatique - EventHub
# Usage: .\setup_messenger_worker.ps1

param(
    [string]$ProjectPath = (Get-Location).Path,
    [string]$Environment = "dev"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Configuration Worker Messenger - EventHub" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Vérification de la configuration
Write-Host "1. Vérification de la configuration..." -ForegroundColor Yellow

if (-not (Test-Path "$ProjectPath\.env")) {
    Write-Host "❌ Fichier .env non trouvé !" -ForegroundColor Red
    Write-Host "📝 Créez le fichier .env avec la configuration SMTP" -ForegroundColor Yellow
    Read-Host "Appuyez sur Entrée pour continuer"
    exit 1
}

Write-Host "✅ Fichier .env trouvé" -ForegroundColor Green

# 2. Test de la configuration Messenger
Write-Host ""
Write-Host "2. Test de la configuration Messenger..." -ForegroundColor Yellow

try {
    Set-Location $ProjectPath
    $testResult = & php bin/console messenger:consume async --time-limit=5 --limit=1 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Configuration Messenger OK" -ForegroundColor Green
    } else {
        Write-Host "❌ Erreur lors du test du worker" -ForegroundColor Red
        Write-Host "🔧 Vérifiez la configuration dans .env" -ForegroundColor Yellow
        Read-Host "Appuyez sur Entrée pour continuer"
        exit 1
    }
} catch {
    Write-Host "❌ Erreur lors du test : $_" -ForegroundColor Red
    exit 1
}

# 3. Menu de configuration
Write-Host ""
Write-Host "3. Configuration du worker automatique..." -ForegroundColor Yellow
Write-Host ""

Write-Host "📋 Options disponibles :" -ForegroundColor Cyan
Write-Host "1. Démarrer le worker en arrière-plan (recommandé)" -ForegroundColor White
Write-Host "2. Configurer comme service Windows" -ForegroundColor White
Write-Host "3. Utiliser avec Task Scheduler" -ForegroundColor White
Write-Host "4. Configuration pour production" -ForegroundColor White
Write-Host "5. Annuler" -ForegroundColor White
Write-Host ""

$choice = Read-Host "Choisissez une option (1-5)"

switch ($choice) {
    "1" { Start-BackgroundWorker }
    "2" { Install-WindowsService }
    "3" { Setup-TaskScheduler }
    "4" { Setup-Production }
    "5" { Write-Host "❌ Configuration annulée" -ForegroundColor Red; exit 0 }
    default { Write-Host "❌ Option invalide" -ForegroundColor Red; exit 1 }
}

# Fonction pour démarrer le worker en arrière-plan
function Start-BackgroundWorker {
    Write-Host ""
    Write-Host "🚀 Démarrage du worker en arrière-plan..." -ForegroundColor Green
    Write-Host ""
    Write-Host "Le worker va maintenant s'exécuter en continu." -ForegroundColor Yellow
    Write-Host "Pour l'arrêter, fermez cette fenêtre ou appuyez sur Ctrl+C" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "✅ Worker démarré ! Les emails seront envoyés automatiquement." -ForegroundColor Green
    Write-Host ""
    
    try {
        & php bin/console messenger:consume async -vv
    } catch {
        Write-Host "❌ Erreur lors du démarrage du worker : $_" -ForegroundColor Red
    }
}

# Fonction pour installer comme service Windows
function Install-WindowsService {
    Write-Host ""
    Write-Host "🔧 Configuration comme service Windows..." -ForegroundColor Green
    Write-Host ""
    Write-Host "Cette option nécessite des droits administrateur." -ForegroundColor Yellow
    Write-Host "Le service sera installé et démarré automatiquement." -ForegroundColor Yellow
    Write-Host ""
    
    $confirm = Read-Host "Continuer ? (y/n)"
    if ($confirm -eq "y" -or $confirm -eq "Y") {
        try {
            # Créer le script de service
            $serviceScript = @"
@echo off
cd /d "$ProjectPath"
php bin/console messenger:consume async
"@
            
            $serviceScriptPath = "$ProjectPath\messenger_worker.bat"
            $serviceScript | Out-File -FilePath $serviceScriptPath -Encoding ASCII
            
            # Installer le service avec NSSM (si disponible)
            if (Get-Command nssm -ErrorAction SilentlyContinue) {
                Write-Host "Installation du service avec NSSM..." -ForegroundColor Yellow
                & nssm install "EventHubMessengerWorker" $serviceScriptPath
                & nssm set "EventHubMessengerWorker" AppDirectory $ProjectPath
                & nssm start "EventHubMessengerWorker"
                Write-Host "✅ Service installé et démarré" -ForegroundColor Green
            } else {
                Write-Host "⚠️ NSSM non trouvé. Installation manuelle requise." -ForegroundColor Yellow
                Write-Host "📝 Script de service créé : $serviceScriptPath" -ForegroundColor Cyan
            }
        } catch {
            Write-Host "❌ Erreur lors de l'installation : $_" -ForegroundColor Red
        }
    } else {
        Write-Host "❌ Installation annulée" -ForegroundColor Red
    }
}

# Fonction pour configurer Task Scheduler
function Setup-TaskScheduler {
    Write-Host ""
    Write-Host "⏰ Configuration avec Task Scheduler..." -ForegroundColor Green
    Write-Host ""
    Write-Host "Création d'une tâche planifiée pour le worker..." -ForegroundColor Yellow
    Write-Host ""
    
    try {
        $action = New-ScheduledTaskAction -Execute "php" -Argument "bin/console messenger:consume async" -WorkingDirectory $ProjectPath
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
        
        Register-ScheduledTask -TaskName "EventHubMessengerWorker" -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Worker Messenger pour EventHub"
        
        Write-Host "✅ Tâche planifiée configurée" -ForegroundColor Green
        Write-Host "📝 Nom de la tâche : EventHubMessengerWorker" -ForegroundColor Cyan
    } catch {
        Write-Host "❌ Erreur lors de la configuration : $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "📝 Configuration manuelle :" -ForegroundColor Yellow
        Write-Host "1. Ouvrez 'Planificateur de tâches' (taskschd.msc)" -ForegroundColor White
        Write-Host "2. Créez une nouvelle tâche" -ForegroundColor White
        Write-Host "3. Nom : 'EventHub Messenger Worker'" -ForegroundColor White
        Write-Host "4. Déclencheur : Au démarrage" -ForegroundColor White
        Write-Host "5. Action : Démarrer un programme" -ForegroundColor White
        Write-Host "6. Programme : php" -ForegroundColor White
        Write-Host "7. Arguments : bin/console messenger:consume async" -ForegroundColor White
        Write-Host "8. Démarrer dans : $ProjectPath" -ForegroundColor White
    }
}

# Fonction pour configuration production
function Setup-Production {
    Write-Host ""
    Write-Host "🚀 Configuration pour production..." -ForegroundColor Green
    Write-Host ""
    
    Write-Host "📋 Configuration recommandée pour production :" -ForegroundColor Cyan
    Write-Host "1. Utiliser Supervisor (Linux) ou Windows Service" -ForegroundColor White
    Write-Host "2. Configurer les logs appropriés" -ForegroundColor White
    Write-Host "3. Monitoring et redémarrage automatique" -ForegroundColor White
    Write-Host "4. Configuration de la base de données" -ForegroundColor White
    Write-Host ""
    
    Write-Host "📝 Commandes utiles :" -ForegroundColor Yellow
    Write-Host "• Démarrer : php bin/console messenger:consume async -vv" -ForegroundColor White
    Write-Host "• Arrêter : Ctrl+C" -ForegroundColor White
    Write-Host "• Logs : tail -f var/log/dev.log" -ForegroundColor White
    Write-Host "• Test : php bin/console messenger:consume async --time-limit=10" -ForegroundColor White
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Configuration terminée" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Prochaines étapes :" -ForegroundColor Yellow
Write-Host "1. Testez l'envoi d'un email" -ForegroundColor White
Write-Host "2. Vérifiez les logs : var/log/dev.log" -ForegroundColor White
Write-Host "3. Pour arrêter le worker : Ctrl+C" -ForegroundColor White
Write-Host ""

Read-Host "Appuyez sur Entrée pour continuer" 