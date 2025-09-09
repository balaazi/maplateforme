@echo off
echo 🔔 Démarrage des rappels automatiques EventHub
echo =============================================
echo.

cd /d "%~dp0"

echo 📅 Vérification des événements futurs...
php bin/console app:send-event-reminders

echo.
echo ⏰ Configuration des rappels automatiques...
echo    - Rappels envoyés la veille des événements
echo    - Notifications par email activées
echo    - Vérification toutes les heures

echo.
echo ✅ Système de rappels automatiques configuré !
echo 📧 Vérifiez votre boîte email pour les rappels
echo.
echo 💡 Pour tester manuellement : php bin/console app:send-event-reminders
echo 💡 Pour voir les logs : tail -f var/log/dev.log
echo.
pause
