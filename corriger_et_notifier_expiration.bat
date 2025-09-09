@echo off
echo ========================================
echo DÉSACTIVÉ - Correction et notification des invitations expirees
echo ========================================
echo.
echo ⚠️  CE FICHIER EST DÉSACTIVÉ
echo ⚠️  Aucun email d'expiration ne sera envoyé
echo ⚠️  Les statuts sont mis à jour automatiquement sans notification
echo.

cd /d "%~dp0"

echo 1. Execution du diagnostic et correction...
php corriger_expiration_invitations.php

echo.
echo 2. Envoi des notifications d'expiration...
echo [DÉSACTIVÉ] php bin/console app:send-expiration-notifications
echo ⚠️  Aucune notification envoyée (service désactivé)

echo.
echo ========================================
echo Processus termine (sans envoi d'email)
echo ========================================
pause
