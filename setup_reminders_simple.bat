@echo off
echo 🔔 Configuration des Rappels Automatiques EventHub
echo ================================================
echo.

cd /d "%~dp0"

echo 📅 Vérification des événements futurs...
php bin/console app:send-event-reminders

echo.
echo 🔧 Activation des notifications par email...
php bin/console doctrine:query:sql "UPDATE users SET notify_by_email = 1 WHERE notify_by_email = 0"

echo.
echo ⏰ Configuration des rappels automatiques...
echo    - Rappels envoyés la veille des événements
echo    - Notifications par email activées
echo    - Vérification quotidienne

echo.
echo 📋 Pour automatiser complètement:
echo    1. Ouvrez le Planificateur de tâches Windows
echo    2. Créez une tâche qui exécute: %CD%\send_reminders.bat
echo    3. Programmez-la pour s'exécuter tous les jours à 08:00

echo.
echo 💡 Test manuel: php bin/console app:send-event-reminders
echo 💡 Voir les logs: type var\log\dev.log

echo.
echo ✅ Configuration terminée !
echo 📧 Vérifiez votre boîte email pour les rappels d'événements !
echo.
pause



