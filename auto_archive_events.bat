@echo off
echo Archivage automatique des evenements expires en temps reel...
cd /d "C:\xampp\htdocs\new\maplateforme"
php bin/console app:archive-expired-events
echo Archivage termine.
pause 