@echo off 
echo [%date% %time%] Traitement des rappels 1h avant... 
cd /d "C:\xampp\htdocs\new\maplateforme" 
C:\xampp\php\php.exe bin/console app:process-reminders --minutes-ahead=60 
echo [%date% %time%] Traitement termine 
