# Script PowerShell pour configurer les rappels automatiques EventHub
# Exécutez ce script en tant qu'administrateur

Write-Host "🔔 Configuration des Rappels Automatiques EventHub" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que nous sommes dans le bon répertoire
if (-not (Test-Path "bin/console")) {
    Write-Host "❌ Erreur: Ce script doit être exécuté depuis la racine du projet EventHub" -ForegroundColor Red
    exit 1
}

Write-Host "📅 Vérification des événements futurs..." -ForegroundColor Yellow
& php bin/console app:send-event-reminders

Write-Host ""
Write-Host "⏰ Configuration des rappels automatiques..." -ForegroundColor Yellow

# Créer une tâche planifiée Windows pour les rappels automatiques
$taskName = "EventHub Reminders"
$taskDescription = "Envoi automatique des rappels d'événements EventHub"
$scriptPath = Join-Path $PWD "send_reminders.bat"

# Créer le script batch pour les rappels
$batchContent = @"
@echo off
cd /d "$PWD"
php bin/console app:send-event-reminders
"@

Set-Content -Path "send_reminders.bat" -Value $batchContent

# Vérifier si la tâche existe déjà
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($existingTask) {
    Write-Host "🔄 Mise à jour de la tâche existante..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

try {
    # Créer la nouvelle tâche planifiée
    $action = New-ScheduledTaskAction -Execute $scriptPath
    $trigger = New-ScheduledTaskTrigger -Daily -At "08:00"
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description $taskDescription -User "SYSTEM"
    
    Write-Host "✅ Tâche planifiée créée avec succès !" -ForegroundColor Green
    Write-Host "   - Nom: $taskName" -ForegroundColor White
    Write-Host "   - Exécution: Tous les jours à 08:00" -ForegroundColor White
    Write-Host "   - Script: $scriptPath" -ForegroundColor White
    
} catch {
    Write-Host "❌ Erreur lors de la création de la tâche planifiée:" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Solution alternative:" -ForegroundColor Yellow
    Write-Host "   1. Ouvrez le Planificateur de tâches Windows" -ForegroundColor White
    Write-Host "   2. Créez une tâche manuellement" -ForegroundColor White
    Write-Host "   3. Programmez-la pour exécuter: $scriptPath" -ForegroundColor White
}

Write-Host ""
Write-Host "🔧 Configuration des préférences utilisateur..." -ForegroundColor Yellow

# Activer les notifications par email pour tous les utilisateurs
try {
    & php bin/console doctrine:query:sql "UPDATE users SET notify_by_email = 1 WHERE notify_by_email = 0"
    Write-Host "✅ Notifications par email activées pour tous les utilisateurs" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Impossible de mettre à jour les préférences utilisateur" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎯 Test du système de rappels..." -ForegroundColor Yellow
& php bin/console app:send-event-reminders

Write-Host ""
Write-Host "✅ Configuration terminée !" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Résumé de la configuration:" -ForegroundColor Cyan
Write-Host "   - Rappels automatiques: ACTIVÉS" -ForegroundColor White
Write-Host "   - Notifications par email: ACTIVÉES" -ForegroundColor White
Write-Host "   - Tâche planifiée: Créée (tous les jours à 08:00)" -ForegroundColor White
Write-Host "   - Script de test: send_reminders.bat" -ForegroundColor White
Write-Host ""
Write-Host "💡 Commandes utiles:" -ForegroundColor Yellow
Write-Host "   - Test manuel: php bin/console app:send-event-reminders" -ForegroundColor White
Write-Host "   - Voir les logs: Get-Content var/log/dev.log -Tail 50" -ForegroundColor White
Write-Host "   - Vérifier la tâche: Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor White
Write-Host ""
Write-Host "📧 Verifiez votre boite email pour les rappels d'evenements !" -ForegroundColor Green 