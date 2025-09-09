@echo off
echo ========================================
echo    SYSTEME DE RAPPELS AVANCES EVENTHUB
echo ========================================
echo.

REM Vérifier si PHP est disponible
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERREUR: PHP n'est pas installé ou n'est pas dans le PATH
    echo Veuillez installer PHP et réessayer
    pause
    exit /b 1
)

echo [%date% %time%] Démarrage du système de rappels avancés...
echo.

REM Aller dans le répertoire du projet
cd /d "%~dp0"

echo [INFO] Répertoire de travail: %CD%
echo.

REM Envoyer les rappels 24h avant
echo [INFO] Envoi des rappels 24h avant les événements...
php bin/console app:send-event-reminders-advanced --reminder-type=24h
if %errorlevel% neq 0 (
    echo [ERREUR] Échec de l'envoi des rappels 24h
    pause
    exit /b 1
)
echo.

REM Envoyer les rappels 1h avant
echo [INFO] Envoi des rappels 1h avant les événements...
php bin/console app:send-event-reminders-advanced --reminder-type=1h
if %errorlevel% neq 0 (
    echo [ERREUR] Échec de l'envoi des rappels 1h
    pause
    exit /b 1
)
echo.

echo [SUCCESS] Système de rappels avancés terminé avec succès
echo [%date% %time%] Processus terminé
echo.
pause
