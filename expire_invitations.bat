@echo off
echo ========================================
echo Expiration automatique des invitations
echo ========================================
echo.

cd /d "%~dp0"

echo Execution de la commande d'expiration...
php bin/console app:expire-invitations

echo.
echo ========================================
echo Expiration terminee
echo ========================================
pause
