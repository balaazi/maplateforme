@echo off
echo [%date% %time%] Execution de la maintenance des invitations...
php maintenance_invitations_auto.php >> logs/maintenance_invitations.log 2>&1
echo [%date% %time%] Maintenance terminee
