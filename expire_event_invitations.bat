@echo off
echo ===================================================
echo Expiration des invitations pour événements passés
echo ===================================================

php bin/console app:expire-event-invitations

echo.
echo Terminé !
echo.
pause
