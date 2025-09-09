@echo off
echo ========================================
echo DÉSACTIVÉ - Envoi des notifications d'expiration
echo ========================================
echo.
echo ⚠️  CE FICHIER EST DÉSACTIVÉ
echo ⚠️  Aucun email d'expiration ne sera envoyé
echo ⚠️  Les statuts sont mis à jour automatiquement sans notification
echo.

cd /d "%~dp0"

echo Execution de la commande d'envoi des notifications...
echo [DÉSACTIVÉ] php bin/console app:send-expiration-notifications

echo.
echo ========================================
echo Aucune notification envoyée (service désactivé)
echo ========================================
pause
