# Script PowerShell pour configurer automatiquement les rappels EventHub
# Exécuter en tant qu'administrateur

Write-Host "=========================================" -ForegroundColor Green
Write-Host "  Configuration des Rappels Automatiques" -ForegroundColor Green
Write-Host "           EventHub" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""

# Vérifier si le script est exécuté en tant qu'administrateur
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ Ce script doit être exécuté en tant qu'administrateur" -ForegroundColor Red
    Write-Host "   Clic droit sur PowerShell → 'Exécuter en tant qu'administrateur'" -ForegroundColor Yellow
    pause
    exit 1
}

# Chemin du projet
$projectPath = "C:\xampp\htdocs\new\maplateforme"
$scriptPath = "$projectPath\send_reminders.bat"

# Vérifier que le projet existe
if (-not (Test-Path $projectPath)) {
    Write-Host "❌ Le répertoire du projet n'existe pas: $projectPath" -ForegroundColor Red
    pause
    exit 1
}

# Vérifier que le script batch existe
if (-not (Test-Path $scriptPath)) {
    Write-Host "❌ Le script send_reminders.bat n'existe pas: $scriptPath" -ForegroundColor Red
    pause
    exit 1
}

Write-Host "✅ Répertoire du projet trouvé: $projectPath" -ForegroundColor Green
Write-Host "✅ Script batch trouvé: $scriptPath" -ForegroundColor Green
Write-Host ""

# Supprimer la tâche existante si elle existe
Write-Host "🗑️  Suppression de la tâche existante (si elle existe)..." -ForegroundColor Yellow
try {
    schtasks /delete /tn "EventHub Reminders" /f 2>$null
    Write-Host "✅ Tâche existante supprimée" -ForegroundColor Green
} catch {
    Write-Host "ℹ️  Aucune tâche existante à supprimer" -ForegroundColor Blue
}

# Créer la nouvelle tâche
Write-Host ""
Write-Host "🔧 Création de la nouvelle tâche planifiée..." -ForegroundColor Yellow

$action = New-ScheduledTaskAction -Execute $scriptPath -WorkingDirectory $projectPath
$trigger = New-ScheduledTaskTrigger -Daily -At "10:35"
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

try {
    Register-ScheduledTask -TaskName "EventHub Reminders" -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Envoi automatique des rappels d'événements EventHub"
    Write-Host "✅ Tâche créée avec succès!" -ForegroundColor Green
} catch {
    Write-Host "❌ Erreur lors de la création de la tâche: $($_.Exception.Message)" -ForegroundColor Red
    pause
    exit 1
}

# Vérifier que la tâche a été créée
Write-Host ""
Write-Host "🔍 Vérification de la tâche créée..." -ForegroundColor Yellow
try {
    $task = Get-ScheduledTask -TaskName "EventHub Reminders"
    Write-Host "✅ Tâche trouvée: $($task.TaskName)" -ForegroundColor Green
    Write-Host "   Statut: $($task.State)" -ForegroundColor Green
    Write-Host "   Prochaine exécution: $($task.NextRunTime)" -ForegroundColor Green
} catch {
    Write-Host "❌ Erreur lors de la vérification de la tâche" -ForegroundColor Red
}

# Tester le script batch
Write-Host ""
Write-Host "🧪 Test du script batch..." -ForegroundColor Yellow
try {
    & $scriptPath
    Write-Host "✅ Test du script réussi!" -ForegroundColor Green
} catch {
    Write-Host "❌ Erreur lors du test du script: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Green
Write-Host "  Configuration Terminée!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Résumé de la configuration:" -ForegroundColor Cyan
Write-Host "   • Tâche: EventHub Reminders" -ForegroundColor White
Write-Host "   • Fréquence: Quotidienne à 10:35" -ForegroundColor White
Write-Host "   • Script: $scriptPath" -ForegroundColor White
Write-Host "   • Répertoire: $projectPath" -ForegroundColor White
Write-Host ""
Write-Host "🔔 Les rappels seront maintenant envoyés automatiquement!" -ForegroundColor Green
Write-Host ""
Write-Host "💡 Pour vérifier manuellement:" -ForegroundColor Yellow
Write-Host "   • Ouvrir le Planificateur de tâches Windows" -ForegroundColor White
Write-Host "   • Rechercher 'EventHub Reminders'" -ForegroundColor White
Write-Host "   • Vérifier que la tâche est 'Prête'" -ForegroundColor White
Write-Host ""
Write-Host "📧 Les rappels seront envoyés à 10:35 chaque jour" -ForegroundColor Cyan
Write-Host "   pour tous les événements du lendemain." -ForegroundColor Cyan
Write-Host ""

pause 