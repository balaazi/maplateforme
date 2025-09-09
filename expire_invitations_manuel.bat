@echo off
echo ========================================
echo Expiration manuelle des invitations
echo EventHub - Correction des statuts
echo ========================================
echo.

cd /d "%~dp0"

echo 🔍 Recherche des invitations expirées...
echo.

REM Exécuter la commande d'expiration
php bin/console app:expire-invitations --days=30

if %errorlevel% equ 0 (
    echo.
    echo ✅ Commande exécutée avec succès !
    echo.
    echo 📊 Résultat :
    echo    - Les invitations en attente depuis plus de 30 jours
    echo    - ont été automatiquement marquées comme "EXPIRÉES"
    echo.
    echo 💡 Pour automatiser ce processus :
    echo    1. Exécutez ce script quotidiennement
    echo    2. Ou configurez une tâche planifiée Windows
    echo    3. Ou utilisez un cron job sur Linux/Mac
    echo.
) else (
    echo.
    echo ❌ Erreur lors de l'exécution de la commande
    echo.
    echo 🔧 Vérifications à effectuer :
    echo    1. PHP est-il installé et accessible ?
    echo    2. Symfony est-il correctement configuré ?
    echo    3. La base de données est-elle accessible ?
    echo.
)

echo.
echo Appuyez sur une touche pour continuer...
pause > nul
