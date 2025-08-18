@echo off
echo ========================================
echo   CONFIGURATION AUTOMATIQUE RAPPELS
echo ========================================
echo.

echo [1/4] Configuration de la tâche Windows...
echo Création de la tâche "EventHub Reminders"...

schtasks /create /tn "EventHub Reminders" /tr "C:\xampp\htdocs\new\maplateforme\process_reminders.bat" /sc daily /st 10:35 /f
if %errorlevel% equ 0 (
    echo ✅ Tâche Windows créée avec succès
) else (
    echo ⚠️  Tâche Windows déjà existante ou erreur
)

echo.
echo [2/4] Configuration du monitoring continu...
echo Création du script de monitoring...

echo @echo off > worker_monitor_continuous.bat
echo echo ======================================== >> worker_monitor_continuous.bat
echo echo   MONITORING RAPPELS CONTINU >> worker_monitor_continuous.bat
echo echo ======================================== >> worker_monitor_continuous.bat
echo echo. >> worker_monitor_continuous.bat
echo echo Démarrage du monitoring toutes les 5 minutes... >> worker_monitor_continuous.bat
echo echo. >> worker_monitor_continuous.bat
echo :loop >> worker_monitor_continuous.bat
echo echo [%date% %time%] Traitement des rappels... >> worker_monitor_continuous.bat
echo cd /d "C:\xampp\htdocs\new\maplateforme" >> worker_monitor_continuous.bat
echo php bin/console app:process-reminders ^>^> logs\reminders_output.log 2^>^&1 >> worker_monitor_continuous.bat
echo echo [%date% %time%] Traitement terminé >> worker_monitor_continuous.bat
echo echo. >> worker_monitor_continuous.bat
echo timeout /t 300 /nobreak ^>nul >> worker_monitor_continuous.bat
echo goto loop >> worker_monitor_continuous.bat

echo ✅ Script de monitoring créé

echo.
echo [3/4] Configuration du démarrage automatique...
echo Création du script de démarrage...

echo @echo off > start_automatic_reminders_complete.bat
echo echo ======================================== >> start_automatic_reminders_complete.bat
echo echo   DÉMARRAGE RAPPELS AUTOMATIQUES >> start_automatic_reminders_complete.bat
echo echo ======================================== >> start_automatic_reminders_complete.bat
echo echo. >> start_automatic_reminders_complete.bat
echo echo Démarrage du système de rappels automatiques... >> start_automatic_reminders_complete.bat
echo echo. >> start_automatic_reminders_complete.bat
echo cd /d "C:\xampp\htdocs\new\maplateforme" >> start_automatic_reminders_complete.bat
echo echo [1/3] Vérification des rappels existants... >> start_automatic_reminders_complete.bat
echo php bin/console app:create-missing-reminders --future-only >> start_automatic_reminders_complete.bat
echo echo. >> start_automatic_reminders_complete.bat
echo echo [2/3] Démarrage du monitoring continu... >> start_automatic_reminders_complete.bat
echo start "EventHub Reminders Monitor" worker_monitor_continuous.bat >> start_automatic_reminders_complete.bat
echo echo. >> start_automatic_reminders_complete.bat
echo echo [3/3] Traitement initial des rappels... >> start_automatic_reminders_complete.bat
echo php bin/console app:process-reminders >> start_automatic_reminders_complete.bat
echo echo. >> start_automatic_reminders_complete.bat
echo echo ✅ Système de rappels automatiques démarré >> start_automatic_reminders_complete.bat
echo echo. >> start_automatic_reminders_complete.bat
echo echo Le monitoring fonctionne en arrière-plan. >> start_automatic_reminders_complete.bat
echo echo Pour arrêter : fermez la fenêtre "EventHub Reminders Monitor" >> start_automatic_reminders_complete.bat
echo pause >> start_automatic_reminders_complete.bat

echo ✅ Script de démarrage créé

echo.
echo [4/4] Test du système...
echo Test du traitement des rappels...

php bin/console app:process-reminders --dry-run
if %errorlevel% equ 0 (
    echo ✅ Test réussi
) else (
    echo ❌ Erreur lors du test
)

echo.
echo ========================================
echo   CONFIGURATION TERMINÉE
echo ========================================
echo.
echo ✅ Tâche Windows configurée (quotidienne à 10:35)
echo ✅ Monitoring continu disponible
echo ✅ Script de démarrage créé
echo.
echo 📋 UTILISATION :
echo.
echo 1. Démarrage manuel :
echo    Double-cliquez sur "start_automatic_reminders_complete.bat"
echo.
echo 2. Démarrage automatique au démarrage Windows :
echo    Copiez "start_automatic_reminders_complete.bat" dans :
echo    C:\Users\%USERNAME%\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup
echo.
echo 3. Vérification des logs :
echo    Consultez "logs\reminders_output.log"
echo.
echo 4. Test immédiat :
echo    php bin/console app:process-reminders
echo.
pause 