@echo off
echo ========================================
echo Configuration de la tâche planifiée
echo pour l'expiration des invitations
echo ========================================
echo.

echo Création de la tâche planifiée...
schtasks /create /tn "EventHub - Expiration Invitations" /tr "%~dp0expire_invitations.bat" /sc daily /st 02:00 /ru "SYSTEM" /f

if %errorlevel% equ 0 (
    echo.
    echo ✅ Tâche planifiée créée avec succès !
    echo.
    echo Détails de la tâche :
    echo - Nom : EventHub - Expiration Invitations
    echo - Fréquence : Quotidienne à 02:00
    echo - Fichier : %~dp0expire_invitations.bat
    echo.
    echo Pour vérifier la tâche :
    echo schtasks /query /tn "EventHub - Expiration Invitations"
    echo.
    echo Pour supprimer la tâche :
    echo schtasks /delete /tn "EventHub - Expiration Invitations" /f
) else (
    echo.
    echo ❌ Erreur lors de la création de la tâche
    echo.
    echo Solutions possibles :
    echo 1. Exécuter en tant qu'administrateur
    echo 2. Vérifier que le Planificateur de tâches est activé
    echo 3. Vérifier les permissions sur le dossier
)

echo.
echo Appuyez sur une touche pour continuer...
pause > nul
