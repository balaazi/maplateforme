@echo off 
echo ======================================== 
echo   DÉMARRAGE RAPPELS AUTOMATIQUES 
echo ======================================== 
echo. 
echo Démarrage du système de rappels automatiques... 
echo. 
cd /d "C:\xampp\htdocs\new\maplateforme" 
echo [1/3] Vérification des rappels existants... 
php bin/console app:create-missing-reminders --future-only 
echo. 
echo [2/3] Démarrage du monitoring continu... 
start "EventHub Reminders Monitor" worker_monitor_continuous.bat 
echo. 
echo [3/3] Traitement initial des rappels... 
php bin/console app:process-reminders 
echo. 
echo ✅ Système de rappels automatiques démarré 
echo. 
echo Le monitoring fonctionne en arrière-plan. 
echo Pour arrêter : fermez la fenêtre "EventHub Reminders Monitor" 
pause 
