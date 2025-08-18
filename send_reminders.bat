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
    echo Répertoire actuel: %CD%
    pause
    exit /b 1
)

REM Vérifier si PHP est disponible
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERREUR: PHP n'est pas disponible dans le PATH
    echo Assurez-vous que PHP est installé et configuré
    pause
    exit /b 1
)

REM Afficher l'heure de début
echo Début du traitement : %date% %time%
echo.

REM Créer le dossier logs s'il n'existe pas
if not exist "logs" mkdir logs

REM Exécuter la commande de rappels avec redirection des erreurs
echo Envoi des rappels en cours...
php bin\console app:send-event-reminders > logs\reminders_output.log 2>&1

REM Vérifier le code de retour
if %errorlevel% neq 0 (
    echo.
    echo ERREUR: Échec de l'envoi des rappels (code d'erreur: %errorlevel%)
    echo Consultez le fichier logs\reminders_output.log pour plus de détails
    echo.
    type logs\reminders_output.log
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

REM Enregistrer dans un fichier de log
echo Rappels envoyés le %date% à %time% >> logs\reminders.log

REM Afficher le contenu du log de sortie
echo Détails de l'exécution :
echo ----------------------------------------
type logs\reminders_output.log
echo ----------------------------------------

echo.
echo Appuyez sur une touche pour fermer...
pause >nul 