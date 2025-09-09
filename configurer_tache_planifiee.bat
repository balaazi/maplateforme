@echo off
echo 🔔 Configuration de la Tache Planifiee EventHub
echo =============================================
echo.

cd /d "%~dp0"

echo 📋 Instructions pour configurer la tache planifiee Windows:
echo.
echo 1. Appuyez sur Windows + R
echo 2. Tapez: taskschd.msc
echo 3. Appuyez sur Entree
echo.
echo 4. Dans le Planificateur de taches:
echo    - Clic droit sur "Bibliotheque du planificateur de taches"
echo    - Selectionnez "Creer une tache de base..."
echo.
echo 5. Configuration de la tache:
echo    - Nom: EventHub Reminders
echo    - Description: Envoi automatique des rappels d'evenements
echo    - Declencheur: Quotidien a 08:00
echo    - Action: Demarrer un programme
echo    - Programme: %CD%\send_reminders.bat
echo    - Repertoire de depart: %CD%
echo.
echo 6. Parametres avances (clic droit sur la tache -> Proprietes):
echo    - General: Executer que l'utilisateur soit connecte ou non
echo    - General: Executer avec les privileges les plus eleves
echo    - Conditions: Demarrer seulement si connecte au reseau
echo    - Parametres: Redemarrer en cas d'echec (1 minute, 3 tentatives)
echo.

echo 🧪 Test du script de rappels...
echo.
echo Appuyez sur une touche pour tester le script...
pause >nul

echo.
echo 📤 Test en cours...
.\send_reminders.bat

echo.
echo ✅ Configuration terminee !
echo.
echo 💡 Prochaines etapes:
echo    1. La tache planifiee sera executee automatiquement a 08:00
echo    2. Vous recevrez des emails de rappel la veille de chaque evenement
echo    3. Consultez les logs: logs\reminders.log
echo.
echo 🆘 En cas de probleme:
echo    - Test manuel: .\send_reminders.bat
echo    - Voir les logs: type logs\reminders.log
echo    - Verifier la tache: Planificateur de taches Windows
echo.
pause
