# Script PowerShell pour créer la tâche planifiée EventHub
# Exécutez en tant qu'administrateur

Write-Host "🔔 Creation de la tache planifiee EventHub" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

$taskName = "EventHub Reminders"
$scriptPath = Join-Path $PWD "send_reminders.bat"

Write-Host "📁 Chemin du script: $scriptPath" -ForegroundColor Yellow

# Supprimer la tâche existante si elle existe
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
Write-Host "🔧 Creation de la nouvelle tache planifiee..." -ForegroundColor Yellow

try {
    # Créer la tâche planifiée
    $action = New-ScheduledTaskAction -Execute $scriptPath -WorkingDirectory $PWD
    $trigger = New-ScheduledTaskTrigger -Daily -At "08:00"
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Envoi automatique des rappels d'evenements EventHub" -User "SYSTEM"
    
    Write-Host "✅ Tache planifiee creee avec succes !" -ForegroundColor Green
    Write-Host "   - Nom: $taskName" -ForegroundColor White
    Write-Host "   - Execution: Tous les jours a 08:00" -ForegroundColor White
    Write-Host "   - Script: $scriptPath" -ForegroundColor White
    
} catch {
    Write-Host "❌ Erreur lors de la creation de la tache:" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Solution alternative manuelle:" -ForegroundColor Yellow
    Write-Host "   1. Ouvrez le Planificateur de taches Windows" -ForegroundColor White
    Write-Host "   2. Creez une tache manuellement" -ForegroundColor White
    Write-Host "   3. Programmez-la pour executer: $scriptPath" -ForegroundColor White
    Write-Host "   4. Configurez le declencheur: Tous les jours a 08:00" -ForegroundColor White
}

Write-Host ""
Write-Host "🎯 Test de la tache..." -ForegroundColor Yellow

try {
    $task = Get-ScheduledTask -TaskName $taskName
    Write-Host "✅ Tache verifiee:" -ForegroundColor Green
    Write-Host "   - Statut: $($task.State)" -ForegroundColor White
    Write-Host "   - Prochaine execution: $($task.NextRunTime)" -ForegroundColor White
} catch {
    Write-Host "⚠️  Impossible de verifier la tache" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "✅ Configuration terminee !" -ForegroundColor Green
Write-Host "📧 Les rappels seront envoyes automatiquement tous les jours a 08:00" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 Commandes utiles:" -ForegroundColor Yellow
Write-Host "   - Test manuel: .\send_reminders.bat" -ForegroundColor White
Write-Host "   - Verifier la tache: Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor White
Write-Host "   - Voir les logs: Get-Content logs\reminders.log -Tail 10" -ForegroundColor White
