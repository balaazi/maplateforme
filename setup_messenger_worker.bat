
@echo off
REM Configuration du Worker Automatique EventHub
REM Ce script configure un service de traitement automatique des rappels

echo.
echo ====================================
echo   EventHub - Worker Automatique
echo ====================================
echo.

REM Vérifier si nous sommes dans le bon répertoire
if not exist "bin\console" (
    echo ERREUR: Le script doit être exécuté depuis le répertoire racine du projet EventHub
    echo Assurez-vous que le fichier bin\console existe dans le répertoire courant
    echo Répertoire actuel: %CD%
    pause
    exit /b 1
)

REM Créer le dossier logs s'il n'existe pas
if not exist "logs" mkdir logs

echo ✅ Configuration du worker automatique...
echo.

REM Créer un script de monitoring continu
echo @echo off > "worker_monitor.bat"
echo REM Worker de monitoring automatique EventHub >> "worker_monitor.bat"
echo setlocal enabledelayedexpansion >> "worker_monitor.bat"
echo. >> "worker_monitor.bat"
echo :loop >> "worker_monitor.bat"
echo echo [%date% %time%] Vérification des rappels... >> "worker_monitor.bat"
echo php bin\console app:process-reminders ^> logs\worker_output.log 2^>^&1 >> "worker_monitor.bat"
echo timeout /t 300 /nobreak ^>nul >> "worker_monitor.bat"
echo goto loop >> "worker_monitor.bat"

echo ✅ Script de monitoring créé: worker_monitor.bat
echo.

REM Créer un script de démarrage automatique
echo @echo off > "start_automatic_reminders.bat"
echo REM Démarrage automatique des rappels EventHub >> "start_automatic_reminders.bat"
echo cd /d "%~dp0" >> "start_automatic_reminders.bat"
echo echo Démarrage du système de rappels automatiques... >> "start_automatic_reminders.bat"
echo start /min worker_monitor.bat >> "start_automatic_reminders.bat"
echo echo Worker démarré en arrière-plan >> "start_automatic_reminders.bat"
echo pause >> "start_automatic_reminders.bat"

echo ✅ Script de démarrage créé: start_automatic_reminders.bat
echo.

REM Créer un script de test
echo @echo off > "test_automatic_system.bat"
echo REM Test du système automatique EventHub >> "test_automatic_system.bat"
echo cd /d "%~dp0" >> "test_automatic_system.bat"
echo echo Test du système de rappels automatiques... >> "test_automatic_system.bat"
echo php bin\console app:process-reminders --dry-run >> "test_automatic_system.bat"
echo php bin\console app:send-event-reminders >> "test_automatic_system.bat"
echo echo Test terminé. Consultez les logs pour plus de détails. >> "test_automatic_system.bat"
echo pause >> "test_automatic_system.bat"

echo ✅ Script de test créé: test_automatic_system.bat
echo.

echo ====================================
echo   Configuration Terminée !
echo ====================================
echo.
echo 📋 Scripts créés:
echo    • worker_monitor.bat - Monitoring continu (toutes les 5 minutes)
echo    • start_automatic_reminders.bat - Démarrage automatique
echo    • test_automatic_system.bat - Test du système
echo.
echo 🚀 Pour démarrer le système automatique:
echo    1. Double-cliquez sur start_automatic_reminders.bat
echo    2. Ou exécutez worker_monitor.bat directement
echo.
echo 🧪 Pour tester le système:
echo    Double-cliquez sur test_automatic_system.bat
echo.
echo 📊 Pour surveiller les logs:
echo    Consultez le dossier logs\ pour les fichiers de sortie
echo.
echo ⚠️  IMPORTANT: Le worker fonctionne en continu
echo    Il vérifie les rappels toutes les 5 minutes
echo    Fermez la fenêtre pour arrêter le monitoring
echo.

pause 