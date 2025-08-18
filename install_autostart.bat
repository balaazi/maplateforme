@echo off
REM Installation du démarrage automatique EventHub
REM Ce script configure EventHub pour démarrer automatiquement avec Windows

echo.
echo ====================================
echo   EventHub - Installation Auto-Start
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

echo ✅ Configuration du démarrage automatique...
echo.

REM Créer un raccourci dans le dossier de démarrage
set "startup_folder=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "current_dir=%CD%"

REM Créer le script de démarrage automatique
echo @echo off > "%startup_folder%\EventHub_Reminders.bat"
echo REM Démarrage automatique EventHub Reminders >> "%startup_folder%\EventHub_Reminders.bat"
echo cd /d "%current_dir%" >> "%startup_folder%\EventHub_Reminders.bat"
echo start /min "%current_dir%\worker_monitor.bat" >> "%startup_folder%\EventHub_Reminders.bat"
echo exit >> "%startup_folder%\EventHub_Reminders.bat"

echo ✅ Raccourci créé dans le dossier de démarrage
echo    %startup_folder%\EventHub_Reminders.bat
echo.

REM Créer un script de désinstallation
echo @echo off > "uninstall_autostart.bat"
echo REM Désinstallation du démarrage automatique EventHub >> "uninstall_autostart.bat"
echo set "startup_folder=%%APPDATA%%\Microsoft\Windows\Start Menu\Programs\Startup" >> "uninstall_autostart.bat"
echo if exist "%%startup_folder%%\EventHub_Reminders.bat" ( >> "uninstall_autostart.bat"
echo     del "%%startup_folder%%\EventHub_Reminders.bat" >> "uninstall_autostart.bat"
echo     echo ✅ Démarrage automatique désactivé >> "uninstall_autostart.bat"
echo ) else ( >> "uninstall_autostart.bat"
echo     echo ℹ️  Aucun fichier de démarrage automatique trouvé >> "uninstall_autostart.bat"
echo ) >> "uninstall_autostart.bat"
echo pause >> "uninstall_autostart.bat"

echo ✅ Script de désinstallation créé: uninstall_autostart.bat
echo.

REM Créer un script de vérification du statut
echo @echo off > "check_autostart_status.bat"
echo REM Vérification du statut du démarrage automatique EventHub >> "check_autostart_status.bat"
echo set "startup_folder=%%APPDATA%%\Microsoft\Windows\Start Menu\Programs\Startup" >> "check_autostart_status.bat"
echo echo ==================================== >> "check_autostart_status.bat"
echo echo   Statut du Démarrage Automatique >> "check_autostart_status.bat"
echo echo ==================================== >> "check_autostart_status.bat"
echo echo. >> "check_autostart_status.bat"
echo if exist "%%startup_folder%%\EventHub_Reminders.bat" ( >> "check_autostart_status.bat"
echo     echo ✅ Démarrage automatique ACTIVÉ >> "check_autostart_status.bat"
echo     echo 📁 Fichier: %%startup_folder%%\EventHub_Reminders.bat >> "check_autostart_status.bat"
echo ) else ( >> "check_autostart_status.bat"
echo     echo ❌ Démarrage automatique DÉSACTIVÉ >> "check_autostart_status.bat"
echo     echo 📁 Dossier vérifié: %%startup_folder%% >> "check_autostart_status.bat"
echo ) >> "check_autostart_status.bat"
echo echo. >> "check_autostart_status.bat"
echo echo 💡 Pour activer: exécutez install_autostart.bat >> "check_autostart_status.bat"
echo echo 💡 Pour désactiver: exécutez uninstall_autostart.bat >> "check_autostart_status.bat"
echo pause >> "check_autostart_status.bat"

echo ✅ Script de vérification créé: check_autostart_status.bat
echo.

echo ====================================
echo   Installation Terminée !
echo ====================================
echo.
echo 📋 Résumé de l'installation:
echo    ✅ Démarrage automatique configuré
echo    ✅ Script de désinstallation créé
echo    ✅ Script de vérification créé
echo.
echo 🔄 Le système EventHub démarrera automatiquement
echo    au prochain redémarrage de Windows
echo.
echo 💡 Commandes disponibles:
echo    • check_autostart_status.bat - Vérifier le statut
echo    • uninstall_autostart.bat - Désactiver l'auto-start
echo    • start_automatic_reminders.bat - Démarrer manuellement
echo.
echo ⚠️  IMPORTANT: Le système fonctionnera en arrière-plan
echo    et vérifiera les rappels toutes les 5 minutes
echo.

pause 