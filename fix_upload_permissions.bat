@echo off
echo ========================================
echo Correction des permissions d'upload
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

:: Définir les chemins
set UPLOAD_DIR=C:\xampp\htdocs\new\maplateforme\public\uploads\documents
set UPLOAD_PARENT=C:\xampp\htdocs\new\maplateforme\public\uploads

:: Vérifier si le répertoire existe
if not exist "%UPLOAD_DIR%" (
    echo Le répertoire %UPLOAD_DIR% n'existe pas.
    echo Création du répertoire...
    mkdir "%UPLOAD_DIR%"
    if %errorLevel% neq 0 (
        echo Impossible de créer le répertoire.
        pause
        exit /b 1
    )
    echo Répertoire créé avec succès.
)

echo.
echo 1. Réinitialisation des permissions sur %UPLOAD_PARENT%
icacls "%UPLOAD_PARENT%" /reset
echo.

echo 2. Réinitialisation des permissions sur %UPLOAD_DIR%
icacls "%UPLOAD_DIR%" /reset
echo.

echo 3. Attribution des permissions complètes à IUSR (utilisateur IIS)
icacls "%UPLOAD_DIR%" /grant "IUSR:(OI)(CI)(F)"
echo.

echo 4. Attribution des permissions complètes à IIS_IUSRS (groupe IIS)
icacls "%UPLOAD_DIR%" /grant "IIS_IUSRS:(OI)(CI)(F)"
echo.

echo 5. Attribution des permissions complètes à l'utilisateur XAMPP
icacls "%UPLOAD_DIR%" /grant "SYSTEM:(OI)(CI)(F)"
echo.

echo 6. Attribution des permissions complètes aux utilisateurs authentifiés
icacls "%UPLOAD_DIR%" /grant "Utilisateurs authentifiés:(OI)(CI)(F)"
echo.

echo 7. Attribution des permissions complètes à l'utilisateur actuel
for /f "tokens=*" %%i in ('whoami') do (
    echo Attribution des permissions à %%i
    icacls "%UPLOAD_DIR%" /grant "%%i:(OI)(CI)(F)"
)
echo.

echo 8. Vérification des permissions finales
icacls "%UPLOAD_DIR%"
echo.

echo Opération terminée. Les permissions ont été correctement configurées.
echo.
echo Veuillez redémarrer Apache/XAMPP pour appliquer les changements.
echo.

pause