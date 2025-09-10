@echo off
REM Script de rappels automatiques pour les invitations EventHub (Windows)
REM Ce script doit être exécuté via le Planificateur de tâches Windows

REM Configuration
set PROJECT_DIR=C:\xampp\htdocs\new\maplateforme
set LOG_FILE=C:\xampp\htdocs\new\maplateforme\var\log\invitation_reminders.log
set PHP_BIN=C:\xampp\php\php.exe

REM Créer le répertoire de logs s'il n'existe pas
if not exist "C:\xampp\htdocs\new\maplateforme\var\log" mkdir "C:\xampp\htdocs\new\maplateforme\var\log"

REM Changer vers le répertoire du projet
cd /d "%PROJECT_DIR%"
if errorlevel 1 (
    echo [%date% %time%] ERREUR: Impossible de se deplacer vers le repertoire du projet: %PROJECT_DIR% >> "%LOG_FILE%"
    exit /b 1
)

echo [%date% %time%] === Debut du traitement des rappels d'invitations === >> "%LOG_FILE%"

REM Envoyer les rappels 24h avant
echo [%date% %time%] Envoi des rappels 24h avant... >> "%LOG_FILE%"
"%PHP_BIN%" bin/console app:send-invitation-reminders --reminder-type=24h >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo [%date% %time%] ERREUR lors de l'envoi des rappels 24h >> "%LOG_FILE%"
) else (
    echo [%date% %time%] Rappels 24h envoyes avec succes >> "%LOG_FILE%"
)

REM Envoyer les rappels 1h avant
echo [%date% %time%] Envoi des rappels 1h avant... >> "%LOG_FILE%"
"%PHP_BIN%" bin/console app:send-invitation-reminders --reminder-type=1h >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo [%date% %time%] ERREUR lors de l'envoi des rappels 1h >> "%LOG_FILE%"
) else (
    echo [%date% %time%] Rappels 1h envoyes avec succes >> "%LOG_FILE%"
)

echo [%date% %time%] === Fin du traitement des rappels d'invitations === >> "%LOG_FILE%"

REM Nettoyer les anciens logs (garder seulement les 30 derniers jours)
forfiles /p "C:\xampp\htdocs\new\maplateforme\var\log" /m *.log /d -30 /c "cmd /c del @path" 2>nul

exit /b 0
