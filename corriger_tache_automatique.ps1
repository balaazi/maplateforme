# Script PowerShell pour corriger la tache planifiee EventHub
# Executer en tant qu'administrateur

Write-Host "🔔 Correction de la Tache Planifiee EventHub" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""

$taskName = "EventHub Reminders"
$scriptPath = Join-Path $PWD "send_reminders.bat"

Write-Host "📁 Chemin du script: $scriptPath" -ForegroundColor Yellow

# Supprimer la tache existante si elle existe
try {
    $existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "🔄 Suppression de la tache existante..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
        Write-Host "✅ Tache existante supprimee" -ForegroundColor Green
    }
} catch {
    Write-Host "ℹ️  Aucune tache existante a supprimer" -ForegroundColor Blue
}

Write-Host ""
Write-Host "🔧 Creation de la nouvelle tache planifiee automatique..." -ForegroundColor Yellow

try {
    # Creer la tache planifiee avec parametres automatiques
    $action = New-ScheduledTaskAction -Execute $scriptPath -WorkingDirectory $PWD
    $trigger = New-ScheduledTaskTrigger -Daily -At "08:00"
    
    # Parametres pour execution automatique
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable -WakeToRun
    
    # Principal avec privileges eleves
    $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Envoi automatique des rappels d'evenements EventHub"
    
    Write-Host "✅ Tache planifiee automatique creee avec succes !" -ForegroundColor Green
    Write-Host "   - Nom: $taskName" -ForegroundColor White
    Write-Host "   - Execution: Tous les jours a 08:00" -ForegroundColor White
    Write-Host "   - Script: $scriptPath" -ForegroundColor White
    Write-Host "   - Mode: AUTOMATIQUE (aucune intervention requise)" -ForegroundColor Green
    
} catch {
    Write-Host "❌ Erreur lors de la creation de la tache:" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Solution alternative manuelle:" -ForegroundColor Yellow
    Write-Host "   1. Ouvrez le Planificateur de taches Windows (taskschd.msc)" -ForegroundColor White
    Write-Host "   2. Supprimez la tache 'EventHub Reminders' existante" -ForegroundColor White
    Write-Host "   3. Creez une nouvelle tache avec ces parametres:" -ForegroundColor White
    Write-Host "      - Nom: EventHub Reminders" -ForegroundColor White
    Write-Host "      - Declencheur: Quotidien a 08:00" -ForegroundColor White
    Write-Host "      - Action: $scriptPath" -ForegroundColor White
    Write-Host "      - General: Executer que l'utilisateur soit connecte ou non" -ForegroundColor White
    Write-Host "      - General: Executer avec les privileges les plus eleves" -ForegroundColor White
}

Write-Host ""
Write-Host "🎯 Test de la tache..." -ForegroundColor Yellow

try {
    $task = Get-ScheduledTask -TaskName $taskName
    Write-Host "✅ Tache verifiee:" -ForegroundColor Green
    Write-Host "   - Statut: $($task.State)" -ForegroundColor White
    Write-Host "   - Prochaine execution: $($task.NextRunTime)" -ForegroundColor White
    
    # Tester l'execution immediate
    Write-Host ""
    Write-Host "🧪 Test d'execution immediate..." -ForegroundColor Yellow
    Start-ScheduledTask -TaskName $taskName
    Start-Sleep -Seconds 2
    
    $taskInfo = Get-ScheduledTask -TaskName $taskName | Get-ScheduledTaskInfo
    Write-Host "   - Derniere execution: $($taskInfo.LastRunTime)" -ForegroundColor White
    Write-Host "   - Resultat: $($taskInfo.LastTaskResult)" -ForegroundColor White
    
} catch {
    Write-Host "⚠️  Impossible de verifier la tache" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "✅ Configuration terminee !" -ForegroundColor Green
Write-Host "📧 Les rappels seront envoyes AUTOMATIQUEMENT tous les jours a 08:00" -ForegroundColor Cyan
Write-Host "🚀 Aucune intervention manuelle requise !" -ForegroundColor Green
Write-Host ""
Write-Host "💡 Commandes utiles:" -ForegroundColor Yellow
Write-Host "   - Test manuel: .\send_reminders.bat" -ForegroundColor White
Write-Host "   - Verifier la tache: Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor White
Write-Host "   - Voir les logs: Get-Content logs\reminders_output.log -Tail 10" -ForegroundColor White
Write-Host "   - Forcer l'execution: Start-ScheduledTask -TaskName '$taskName'" -ForegroundColor White
