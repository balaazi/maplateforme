@echo off
echo 🔔 Test Complet du Systeme de Rappels EventHub
echo ==============================================
echo.

cd /d "%~dp0"

echo 📅 1. Verification des evenements futurs...
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM event WHERE date_heure > NOW() AND status != 'annule'"

echo.
echo 👥 2. Verification des preferences utilisateur...
php bin/console doctrine:query:sql "SELECT email, notify_by_email FROM users"

echo.
echo 📧 3. Test d'envoi d'email...
php test_email_simple.php

echo.
echo 🔔 4. Test du systeme de rappels...
php bin/console app:send-event-reminders

echo.
echo 📊 5. Verification des logs...
if exist "logs\reminders.log" (
    echo Dernieres entrees du log des rappels:
    type logs\reminders.log | findstr /C:"Rappels envoyes" | tail -5
) else (
    echo Aucun log de rappels trouve
)

echo.
echo ✅ Test termine !
echo.
echo 💡 Prochaines etapes:
echo    1. Configurer la tache planifiee Windows (voir GUIDE_CONFIGURATION_RAPPELS_AUTOMATIQUES.md)
echo    2. Tester automatiquement demain a 08:00
echo    3. Verifier la reception des emails de rappel
echo.
pause
