@echo off
echo ========================================
echo Configuration des Rappels Automatiques
echo ========================================
echo.

REM Vérifier les privilèges administrateur
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [OK] Privilèges administrateur détectés
) else (
    echo [ERREUR] Ce script nécessite des privilèges administrateur
    echo Clic droit sur le fichier et "Exécuter en tant qu'administrateur"
    pause
    exit /b 1
)

echo.
echo Configuration de la tâche planifiée...

REM Supprimer la tâche existante si elle existe
schtasks /delete /tn "EventRemindersMinute" /f >nul 2>&1

REM Créer la nouvelle tâche planifiée
schtasks /create /tn "EventRemindersMinute" /tr "php \"%~dp0bin\console\" app:send-event-reminders-advanced --reminder-type=both --tolerance-minutes=2" /sc minute /mo 1 /ru "SYSTEM" /f

if %errorLevel% == 0 (
    echo [OK] Tâche planifiée créée avec succès
    echo.
    echo La commande sera exécutée chaque minute avec les paramètres suivants:
    echo - Type de rappel: both (24h et 1h)
    echo - Tolérance: 2 minutes
    echo - Utilisateur: SYSTEM
    echo.
    
    REM Créer également une tâche de nettoyage hebdomadaire
    echo Configuration du nettoyage hebdomadaire...
    schtasks /delete /tn "EventRemindersCleanup" /f >nul 2>&1
    schtasks /create /tn "EventRemindersCleanup" /tr "php \"%~dp0bin\console\" app:send-event-reminders-advanced --cleanup --dry-run" /sc weekly /d SUN /st 02:00 /ru "SYSTEM" /f
    
    if %errorLevel% == 0 (
        echo [OK] Tâche de nettoyage hebdomadaire créée
    )
    
    echo.
    echo ========================================
    echo Configuration terminée avec succès!
    echo ========================================
    echo.
    echo Pour vérifier les tâches:
    echo schtasks /query /tn "EventRemindersMinute"
    echo schtasks /query /tn "EventRemindersCleanup"
    echo.
    echo Pour désactiver:
    echo schtasks /change /tn "EventRemindersMinute" /disable
    echo.
    echo Pour supprimer:
    echo schtasks /delete /tn "EventRemindersMinute" /f
    echo schtasks /delete /tn "EventRemindersCleanup" /f
    
) else (
    echo [ERREUR] Échec de la création de la tâche planifiée
    echo Code d'erreur: %errorLevel%
)

echo.
pause