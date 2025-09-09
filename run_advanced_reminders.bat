@echo off
REM Script généré automatiquement pour les rappels avancés EventHub
REM Ne pas modifier manuellement - sera écrasé par setup_advanced_reminders.ps1

cd /d "C:\xampp\htdocs\new\maplateforme"
echo [%date% %time%] Démarrage des rappels avancés...
php bin/console app:send-event-reminders-advanced --reminder-type=24h
php bin/console app:send-event-reminders-advanced --reminder-type=1h
echo [%date% %time%] Rappels avancés terminés
