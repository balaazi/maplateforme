@echo off
cd /d "C:\xampp\htdocs\new\maplateforme"
echo Envoi des rappels quotidiens...
php bin/console app:send-event-reminders
if errorlevel 1 (
    echo Erreur lors de l'envoi des rappels
    exit /b 1
)
echo Rappels quotidiens envoyés avec succès
pause
