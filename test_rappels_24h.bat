@echo off
echo ===================================================
echo Test des rappels 24h avant evenements
echo ===================================================
cd %~dp0
php test_rappels_24h.php
echo.
echo Termine a %time%
echo ===================================================
pause
