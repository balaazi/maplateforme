# Configuration avancée des rappels automatiques
# Exécution: PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1

param(
    [switch]$Install,
    [switch]$Uninstall,
    [switch]$Status,
    [int]$ToleranceMinutes = 2,
    [string]$ReminderType = "both"
)

$TaskName = "EventRemindersAdvanced"
$CleanupTaskName = "EventRemindersCleanup"
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$ConsolePath = Join-Path $ScriptPath "bin\console"

function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    } else {
        $input | Write-Output
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Test-AdminPrivileges {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Install-ReminderTasks {
    Write-ColorOutput Green "=== Installation des tâches de rappels ==="
    
    if (-not (Test-AdminPrivileges)) {
        Write-ColorOutput Red "ERREUR: Privilèges administrateur requis"
        Write-ColorOutput Yellow "Relancez PowerShell en tant qu'administrateur"
        return $false
    }

    try {
        # Supprimer les tâches existantes
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue
        Unregister-ScheduledTask -TaskName $CleanupTaskName -Confirm:$false -ErrorAction SilentlyContinue

        # Créer l'action principale
        $Action = New-ScheduledTaskAction -Execute "php" -Argument "`"$ConsolePath`" app:send-event-reminders-advanced --reminder-type=$ReminderType --tolerance-minutes=$ToleranceMinutes"
        
        # Créer le déclencheur (chaque minute)
        $Trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 365)
        
        # Configuration de la tâche
        $Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable:$false
        $Principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

        # Enregistrer la tâche principale
        Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Principal $Principal -Description "Rappels automatiques d'événements - Vérifie chaque minute"

        Write-ColorOutput Green "[OK] Tâche principale créée: $TaskName"

        # Créer la tâche de nettoyage
        $CleanupAction = New-ScheduledTaskAction -Execute "php" -Argument "`"$ConsolePath`" app:send-event-reminders-advanced --cleanup"
        $CleanupTrigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Sunday -At "02:00"
        
        Register-ScheduledTask -TaskName $CleanupTaskName -Action $CleanupAction -Trigger $CleanupTrigger -Settings $Settings -Principal $Principal -Description "Nettoyage hebdomadaire des anciens rappels"

        Write-ColorOutput Green "[OK] Tâche de nettoyage créée: $CleanupTaskName"

        # Créer un fichier de log pour surveiller l'exécution
        $LogPath = Join-Path $ScriptPath "logs\reminders_scheduler.log"
        $LogDir = Split-Path $LogPath -Parent
        if (-not (Test-Path $LogDir)) {
            New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
        }

        $LogEntry = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - Tâches de rappels installées avec succès"
        Add-Content -Path $LogPath -Value $LogEntry

        Write-ColorOutput Green "=== Installation terminée avec succès ==="
        Write-ColorOutput Cyan "Paramètres:"
        Write-ColorOutput White "  - Type de rappel: $ReminderType"
        Write-ColorOutput White "  - Tolérance: $ToleranceMinutes minutes"
        Write-ColorOutput White "  - Fréquence: Chaque minute"
        Write-ColorOutput White "  - Nettoyage: Dimanche à 02:00"
        Write-ColorOutput White "  - Log: $LogPath"

        return $true
    }
    catch {
        Write-ColorOutput Red "ERREUR lors de l'installation: $($_.Exception.Message)"
        return $false
    }
}

function Uninstall-ReminderTasks {
    Write-ColorOutput Yellow "=== Suppression des tâches de rappels ==="
    
    try {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue
        Unregister-ScheduledTask -TaskName $CleanupTaskName -Confirm:$false -ErrorAction SilentlyContinue
        
        Write-ColorOutput Green "[OK] Tâches supprimées avec succès"
        return $true
    }
    catch {
        Write-ColorOutput Red "ERREUR lors de la suppression: $($_.Exception.Message)"
        return $false
    }
}

function Show-TaskStatus {
    Write-ColorOutput Cyan "=== Statut des tâches de rappels ==="
    
    try {
        $MainTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
        $CleanupTask = Get-ScheduledTask -TaskName $CleanupTaskName -ErrorAction SilentlyContinue
        
        if ($MainTask) {
            Write-ColorOutput Green "[INSTALLÉ] $TaskName"
            Write-ColorOutput White "  État: $($MainTask.State)"
            Write-ColorOutput White "  Dernière exécution: $($MainTask.LastRunTime)"
            Write-ColorOutput White "  Prochaine exécution: $($MainTask.NextRunTime)"
        } else {
            Write-ColorOutput Red "[NON INSTALLÉ] $TaskName"
        }
        
        if ($CleanupTask) {
            Write-ColorOutput Green "[INSTALLÉ] $CleanupTaskName"
            Write-ColorOutput White "  État: $($CleanupTask.State)"
            Write-ColorOutput White "  Prochaine exécution: $($CleanupTask.NextRunTime)"
        } else {
            Write-ColorOutput Red "[NON INSTALLÉ] $CleanupTaskName"
        }

        # Afficher les logs récents
        $LogPath = Join-Path $ScriptPath "logs\reminders_output.log"
        if (Test-Path $LogPath) {
            Write-ColorOutput Cyan "`n=== Dernières entrées de log ==="
            Get-Content $LogPath -Tail 10 | ForEach-Object { Write-ColorOutput White $_ }
        }
    }
    catch {
        Write-ColorOutput Red "ERREUR lors de la vérification: $($_.Exception.Message)"
    }
}

# Exécution principale
Write-ColorOutput Cyan "Configuration Avancée des Rappels Automatiques"
Write-ColorOutput Cyan "=============================================="

if ($Install) {
    Install-ReminderTasks
} elseif ($Uninstall) {
    Uninstall-ReminderTasks
} elseif ($Status) {
    Show-TaskStatus
} else {
    Write-ColorOutput Yellow "Usage:"
    Write-ColorOutput White "  .\setup_advanced_reminders.ps1 -Install                    # Installer les tâches"
    Write-ColorOutput White "  .\setup_advanced_reminders.ps1 -Uninstall                 # Supprimer les tâches"
    Write-ColorOutput White "  .\setup_advanced_reminders.ps1 -Status                    # Vérifier le statut"
    Write-ColorOutput White "  .\setup_advanced_reminders.ps1 -Install -ToleranceMinutes 5  # Avec tolérance personnalisée"
    Write-ColorOutput White "  .\setup_advanced_reminders.ps1 -Install -ReminderType 24h    # Seulement rappels 24h"
    Write-ColorOutput Cyan "`nExemples:"
    Write-ColorOutput Gray "  PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1 -Install"
    Write-ColorOutput Gray "  PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1 -Status"
}
