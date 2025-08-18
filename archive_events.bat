@echo off
echo Archivage automatique des evenements expires...
cd /d "C:\xampp\htdocs\new\maplateforme"
php bin/console app:archive-expired-events
pause 