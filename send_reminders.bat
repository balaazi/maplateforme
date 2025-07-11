@echo off
REM Script pour envoyer automatiquement les rappels d'événements EventHub
REM Doit être exécuté depuis le répertoire racine du projet

echo.
echo ====================================
echo   EventHub - Rappels automatiques
echo ====================================
echo.

REM Vérifier si nous sommes dans le bon répertoire
if not exist "bin\console" (
    echo ERREUR: Le script doit être exécuté depuis le répertoire racine du projet EventHub
    echo Assurez-vous que le fichier bin\console existe dans le répertoire courant
    pause
    exit /b 1
)

REM Afficher l'heure de début
echo Début du traitement : %date% %time%
echo.

REM Exécuter la commande de rappels
echo Envoi des rappels en cours...
php bin\console app:send-event-reminders

REM Vérifier le code de retour
if %errorlevel% neq 0 (
    echo.
    echo ERREUR: Échec de l'envoi des rappels (code d'erreur: %errorlevel%)
    echo Vérifiez la configuration PHP et les paramètres de l'application
    echo.
    pause
    exit /b %errorlevel%
)

REM Succès
echo.
echo ====================================
echo   Rappels envoyés avec succès !
echo ====================================
echo Fin du traitement : %date% %time%
echo.

REM Enregistrer dans un fichier de log (optionnel)
echo Rappels envoyés le %date% à %time% >> logs\reminders.log

REM Pause pour voir le résultat (à supprimer pour l'automatisation)
pause 