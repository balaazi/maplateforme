@echo off
echo ===================================================
echo Envoi des rappels 24h avant evenements
echo ===================================================
cd %~dp0
php bin/console app:send-24h-event-reminders --no-debug
echo.
echo Termine a %time%
echo ===================================================
