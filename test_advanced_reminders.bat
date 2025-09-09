@echo off
echo ========================================
echo    TEST RAPPELS AVANCES EVENTHUB
echo ========================================
echo.

REM Vérifier si PHP est disponible
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERREUR: PHP n'est pas installé ou n'est pas dans le PATH
    pause
    exit /b 1
)

echo [%date% %time%] Démarrage des tests de rappels avancés...
echo.

REM Aller dans le répertoire du projet
cd /d "%~dp0"

echo [INFO] Répertoire de travail: %CD%
echo.

echo ========================================
echo    TEST 1: RAPPELS 24H AVANT
echo ========================================
echo.
echo [INFO] Test des rappels 24h avant (mode dry-run)...
php bin/console app:send-event-reminders-advanced --reminder-type=24h --dry-run
echo.

echo ========================================
echo    TEST 2: RAPPELS 1H AVANT
echo ========================================
echo.
echo [INFO] Test des rappels 1h avant (mode dry-run)...
php bin/console app:send-event-reminders-advanced --reminder-type=1h --dry-run
echo.

echo ========================================
echo    TEST 3: RAPPELS COMBINÉS
echo ========================================
echo.
echo [INFO] Test des rappels combinés (mode dry-run)...
php bin/console app:send-event-reminders-advanced --reminder-type=both --dry-run
echo.

echo ========================================
echo    TEST 4: RAPPELS AVEC DATE FORCÉE
echo ========================================
echo.
echo [INFO] Test avec date forcée (demain)...
set TOMORROW=%date:~6,4%-%date:~3,2%-%date:~0,2%
echo [INFO] Date de test: %TOMORROW%
php bin/console app:send-event-reminders-advanced --reminder-type=both --force-date=%TOMORROW% --dry-run
echo.

echo ========================================
echo    TEST 5: MODE TEST (SIMULATION)
echo ========================================
echo.
echo [INFO] Test en mode simulation (pas d'envoi réel)...
php bin/console app:send-event-reminders-advanced --reminder-type=both --test-mode
echo.

echo ========================================
echo    RÉSULTATS DES TESTS
echo ========================================
echo.
echo [SUCCESS] Tous les tests de rappels avancés sont terminés
echo [INFO] Vérifiez les résultats ci-dessus pour détecter d'éventuels problèmes
echo [%date% %time%] Tests terminés
echo.

echo Voulez-vous exécuter les rappels en mode réel ? (y/N)
set /p choice="Votre choix: "
if /i "%choice%"=="y" (
    echo.
    echo [INFO] Exécution des rappels en mode réel...
    php bin/console app:send-event-reminders-advanced --reminder-type=both
    echo.
    echo [SUCCESS] Rappels envoyés en mode réel
) else (
    echo [INFO] Aucun rappel envoyé en mode réel
)

echo.
pause
