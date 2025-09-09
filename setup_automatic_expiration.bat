@echo off
echo ========================================
echo Configuration de l'expiration automatique
echo des invitations EventHub
echo ========================================
echo.

cd /d "%~dp0"

echo 🔧 Configuration de la tâche planifiée...
echo.

REM Créer la tâche planifiée pour l'expiration quotidienne
echo Création de la tâche planifiée Windows...
schtasks /create /tn "EventHub - Expiration Invitations" /tr "powershell.exe -ExecutionPolicy Bypass -File \"%~dp0expire_invitations_auto.ps1\"" /sc daily /st 02:00 /ru "SYSTEM" /f

if %errorlevel% equ 0 (
    echo.
    echo ✅ Tâche planifiée créée avec succès !
    echo.
    echo 📋 Détails de la tâche :
    echo    - Nom : EventHub - Expiration Invitations
    echo    - Fréquence : Quotidienne à 02:00
    echo    - Script : expire_invitations_auto.ps1
    echo    - Délai d'expiration : 30 jours (configurable)
    echo.
    echo 📊 Logs disponibles dans : logs\expiration_invitations.log
    echo.
    echo 🧪 Test de la commande d'expiration...
    echo.
    php bin/console app:expire-invitations --days=30
    
    if %errorlevel% equ 0 (
        echo.
        echo ✅ Test réussi ! La commande fonctionne correctement.
    ) else (
        echo.
        echo ⚠️  Test échoué. Vérifiez la configuration PHP/Symfony.
    )
    
    echo.
    echo 💡 Commandes utiles :
    echo    - Vérifier la tâche : schtasks /query /tn "EventHub - Expiration Invitations"
    echo    - Supprimer la tâche : schtasks /delete /tn "EventHub - Expiration Invitations" /f
    echo    - Test manuel : powershell.exe -ExecutionPolicy Bypass -File "expire_invitations_auto.ps1"
    echo    - Voir les logs : type logs\expiration_invitations.log
    echo.
    echo 🎯 Résultat attendu :
    echo    Les invitations en attente depuis plus de 30 jours seront automatiquement
    echo    marquées comme "EXPIRÉES" chaque jour à 02:00.
    echo.
    
) else (
    echo.
    echo ❌ Erreur lors de la création de la tâche planifiée
    echo.
    echo 🔧 Solutions possibles :
    echo    1. Exécuter ce script en tant qu'administrateur
    echo    2. Vérifier que le Planificateur de tâches Windows est activé
    echo    3. Vérifier les permissions sur le dossier
    echo    4. Vérifier que PowerShell est installé et accessible
    echo.
    echo 🧪 Test manuel de la commande...
    echo.
    php bin/console app:expire-invitations --days=30
    
    if %errorlevel% equ 0 (
        echo.
        echo ✅ La commande fonctionne. Vous pouvez l'exécuter manuellement.
        echo    Commande : php bin/console app:expire-invitations
    ) else (
        echo.
        echo ❌ La commande ne fonctionne pas. Vérifiez la configuration.
    )
)

echo.
echo Appuyez sur une touche pour continuer...
pause > nul
