@echo off 
REM Worker de monitoring automatique EventHub 
setlocal enabledelayedexpansion 
 
:loop 
echo [19/07/2025 11:27:50,11] Vérification des rappels... 
php bin\console app:process-reminders > logs\worker_output.log 2>&1 
timeout /t 300 /nobreak >nul 
goto loop 
