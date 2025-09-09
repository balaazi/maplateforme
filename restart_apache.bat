@echo off
echo ========================================
echo Redemarrage du service Apache/XAMPP
echo ========================================
echo.

:: Vérifier si le script est exécuté en tant qu'administrateur
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Ce script doit être exécuté en tant qu'administrateur.
    echo Veuillez cliquer droit sur le script et sélectionner "Exécuter en tant qu'administrateur".
    pause
    exit /b 1
)

:: Arrêter les services XAMPP
echo Arrêt des services XAMPP...
net stop Apache2.4
timeout /t 2 >nul

:: Démarrer les services XAMPP
echo Démarrage des services XAMPP...
net start Apache2.4

echo.
echo Opération terminée. Les services ont été redémarrés.
echo.

pause
