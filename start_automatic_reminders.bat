@echo off 
REM Démarrage automatique des rappels EventHub 
cd /d "C:\xampp\htdocs\new\maplateforme\" 
echo Démarrage du système de rappels automatiques... 
start /min worker_monitor.bat 
echo Worker démarré en arrière-plan 
pause 
