
# Script PowerShell pour corriger l'heure de la tache planifiee EventHub
# Executer en tant qu'administrateur

Write-Host "🔔 Correction de l'heure de la tache planifiee EventHub" -ForegroundColor Cyan
Write-Host "=====================================================" -ForegroundColor Cyan
Write-Host ""

$taskName = "EventHub Reminders"

Write-Host "🔍 Recherche de la tache existante..." -ForegroundColor Yellow

try {
    $existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    
    if ($existingTask) {
        Write-Host "✅ Tache trouvee: $($existingTask.TaskName)" -ForegroundColor Green
        Write-Host "   - Statut: $($existingTask.State)" -ForegroundColor White
        Write-Host "   - Prochaine execution: $($existingTask.NextRunTime)" -ForegroundColor White
        
        Write-Host ""
        Write-Host "🔄 Modification de l'heure d'execution..." -ForegroundColor Yellow
        
        # Obtenir les proprietes actuelles
        $task = Get-ScheduledTask -TaskName $taskName
        
        # Modifier le declencheur pour 08:00
        $trigger = New-ScheduledTaskTrigger -Daily -At "08:00"
        
        # Mettre a jour la tache
        Set-ScheduledTask -TaskName $taskName -Trigger $trigger
        
        Write-Host "✅ Heure modifiee avec succes !" -ForegroundColor Green
        Write-Host "   - Nouvelle heure: 08:00 (8:00 AM)" -ForegroundColor White
        Write-Host "   - Ancienne heure: 21:21 (9:21 PM)" -ForegroundColor White
        
        # Verifier la modification
        $updatedTask = Get-ScheduledTask -TaskName $taskName
        Write-Host ""
        Write-Host "🎯 Verification de la modification:" -ForegroundColor Yellow
        Write-Host "   - Statut: $($updatedTask.State)" -ForegroundColor White
        Write-Host "   - Prochaine execution: $($updatedTask.NextRunTime)" -ForegroundColor White
        
    } else {
        Write-Host "❌ Tache '$taskName' non trouvee" -ForegroundColor Red
        Write-Host "💡 Creer la tache manuellement ou utiliser creer_tache_simple.ps1" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "❌ Erreur lors de la modification:" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🧪 Test du systeme de rappels..." -ForegroundColor Yellow

# Tester le systeme
try {
    & .\send_reminders.bat
    Write-Host "✅ Test reussi !" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Erreur lors du test" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "✅ Configuration terminee !" -ForegroundColor Green
Write-Host "📧 Les rappels seront envoyes automatiquement tous les jours a 08:00" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 Prochaines etapes:" -ForegroundColor Yellow
Write-Host "   1. Attendez 08:00 du matin pour le premier test automatique" -ForegroundColor White
Write-Host "   2. Verifiez votre boite email pour les rappels" -ForegroundColor White
Write-Host "   3. Consultez les logs: logs\reminders_output.log" -ForegroundColor White
Write-Host ""
Write-Host "🔍 Pour verifier la tache:" -ForegroundColor Yellow
Write-Host "   Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor White
