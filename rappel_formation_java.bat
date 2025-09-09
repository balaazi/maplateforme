@echo off
echo ======================================
echo  Rappel pour la Formation Java
echo ======================================
echo.

REM Définir le chemin du projet
set PROJECT_DIR=%~dp0

REM Se positionner dans le répertoire du projet
cd /d %PROJECT_DIR%

echo Envoi des rappels pour la Formation Java du 06/09/2025...
echo.

REM Exécuter la commande avec les options nécessaires
php bin/console app:send-event-reminders --force-date=2025-09-06

echo.
echo Rappels envoyés avec succès !
echo.
echo Vérifiez votre boîte email pour confirmer la réception.
echo.
pause
