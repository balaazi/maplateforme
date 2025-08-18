@echo off
cd /d "C:\xampp\htdocs\new\maplateforme"
echo Traitement des rappels automatiques...
php bin/console app:process-reminders
if errorlevel 1 (
    echo Erreur lors du traitement des rappels
    exit /b 1
)
echo Rappels traités avec succès
pause
