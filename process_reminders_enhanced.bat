@echo off
echo ========================================
echo   TRAITEMENT RAPPELS EVENTHUB
echo ========================================
echo.
echo [%date% %time%] Démarrage du traitement...
echo.

cd /d "C:\xampp\htdocs\new\maplateforme"

echo [1/4] Vérification des rappels en attente...
php bin/console app:process-reminders --dry-run --verbose
if %errorlevel% neq 0 (
    echo ❌ Erreur lors de la vérification
    goto :error
)

echo.
echo [2/4] Création des rappels manquants...
php bin/console app:create-missing-reminders --future-only
if %errorlevel% neq 0 (
    echo ❌ Erreur lors de la création des rappels
    goto :error
)

echo.
echo [3/4] Traitement des rappels...
php bin/console app:process-reminders
if %errorlevel% neq 0 (
    echo ❌ Erreur lors du traitement
    goto :error
)

echo.
echo [4/4] Nettoyage des anciens rappels...
php bin/console app:process-reminders --cleanup
if %errorlevel% neq 0 (
    echo ⚠️  Erreur lors du nettoyage (non critique)
)

echo.
echo ========================================
echo   TRAITEMENT TERMINÉ AVEC SUCCÈS
echo ========================================
echo.
echo ✅ Rappels vérifiés
echo ✅ Rappels manquants créés
echo ✅ Rappels traités
echo ✅ Nettoyage effectué
echo.
echo 📊 Logs disponibles dans : logs\reminders_output.log
echo.
goto :end

:error
echo.
echo ========================================
echo   ERREUR LORS DU TRAITEMENT
echo ========================================
echo.
echo ❌ Une erreur s'est produite
echo 📋 Vérifiez les logs pour plus de détails
echo.

:end
echo [%date% %time%] Fin du traitement
echo.
pause 