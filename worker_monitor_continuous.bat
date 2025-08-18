@echo off 
echo ======================================== 
echo   MONITORING RAPPELS CONTINU 
echo ======================================== 
echo. 
echo Démarrage du monitoring toutes les 5 minutes... 
echo. 
:loop 
echo [29/07/2025 21:43:03,49] Traitement des rappels... 
cd /d "C:\xampp\htdocs\new\maplateforme" 
php bin/console app:process-reminders >> logs\reminders_output.log 2>&1 
echo [29/07/2025 21:43:03,61] Traitement terminé 
echo. 
timeout /t 300 /nobreak >nul 
goto loop 
