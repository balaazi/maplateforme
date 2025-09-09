# Script pour configurer la tâche planifiée des rappels 24h avant événements
# À exécuter en tant qu'administrateur

# Chemin vers le script batch
$scriptPath = Join-Path $PSScriptRoot "send_24h_reminders.bat"
$scriptPath = $scriptPath.Replace("/", "\")

# Nom de la tâche
$taskName = "EventHub_Rappels_24h"

# Vérifier si la tâche existe déjà
$taskExists = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($taskExists) {
    Write-Host "La tâche $taskName existe déjà. Suppression de l'ancienne tâche..."
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Créer une action pour exécuter le script batch
$action = New-ScheduledTaskAction -Execute $scriptPath

# Créer un déclencheur pour exécuter la tâche tous les jours à 8h00
$trigger = New-ScheduledTaskTrigger -Daily -At 8am

# Configurer les paramètres de la tâche
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

# Créer la tâche planifiée
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Envoie des rappels 24h avant les événements par email et notification"

Write-Host "La tâche planifiée $taskName a été créée avec succès!"
Write-Host "La tâche s'exécutera tous les jours à 8h00 du matin."
Write-Host "Vous pouvez modifier les paramètres dans le Planificateur de tâches Windows."
