@echo off
echo ========================================
echo Correction Simple des Permissions d'Upload
echo ========================================
echo.

REM Vérifier si le script est exécuté en tant qu'administrateur
net session >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Script exécuté en tant qu'administrateur
) else (
    echo ✗ Ce script doit être exécuté en tant qu'administrateur
    echo Veuillez faire un clic droit et "Exécuter en tant qu'administrateur"
    pause
    exit /b 1
)

echo.
echo Correction des permissions pour le répertoire uploads...
echo.

REM Définir le chemin du projet
set PROJECT_PATH=C:\xampp\htdocs\new\maplateforme

echo Étape 1: Prise de propriété des répertoires...
echo.

REM Prendre la propriété du répertoire uploads
echo Prise de propriété de: %PROJECT_PATH%\public\uploads
takeown /f "%PROJECT_PATH%\public\uploads" /r /d y >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Propriété prise pour le répertoire uploads
) else (
    echo ✗ Erreur lors de la prise de propriété
)

REM Prendre la propriété du répertoire photos
echo Prise de propriété de: %PROJECT_PATH%\public\uploads\photos
takeown /f "%PROJECT_PATH%\public\uploads\photos" /r /d y >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Propriété prise pour le répertoire photos
) else (
    echo ✗ Erreur lors de la prise de propriété
)

echo.
echo Étape 2: Attribution des permissions complètes...
echo.

REM Accorder les permissions complètes avec cacls
echo Attribution des permissions pour: %PROJECT_PATH%\public\uploads
cacls "%PROJECT_PATH%\public\uploads" /grant Everyone:F /T >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Permissions accordées pour le répertoire uploads
) else (
    echo ✗ Erreur lors de l'attribution des permissions
)

echo Attribution des permissions pour: %PROJECT_PATH%\public\uploads\photos
cacls "%PROJECT_PATH%\public\uploads\photos" /grant Everyone:F /T >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Permissions accordées pour le répertoire photos
) else (
    echo ✗ Erreur lors de l'attribution des permissions
)

echo.
echo Étape 3: Test de création de fichier...
echo.

REM Test de création de fichier
echo Test de permissions > "%PROJECT_PATH%\public\uploads\photos\test_permissions.txt"
if exist "%PROJECT_PATH%\public\uploads\photos\test_permissions.txt" (
    echo ✓ Test de création de fichier réussi
    del "%PROJECT_PATH%\public\uploads\photos\test_permissions.txt" >nul 2>&1
    echo ✓ Fichier de test supprimé
) else (
    echo ✗ Test de création de fichier échoué
)

echo.
echo ========================================
echo ✓ Correction des permissions terminée
echo ========================================
echo.
echo Les répertoires d'upload sont maintenant accessibles en écriture.
echo Vous pouvez tester le téléchargement de photos.
echo.
pause
