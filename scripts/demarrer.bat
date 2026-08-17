@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0.."

set APP_URL=
for /f "usebackq tokens=1,* delims==" %%A in (".env") do (
    if "%%A"=="APP_URL" set APP_URL=%%B
)
if not defined APP_URL set APP_URL=http://localhost

echo ============================================
echo  Demarrage - G-Stock Quincaillerie
echo ============================================

if exist "C:\laragon\laragon.exe" (
    tasklist /fi "imagename eq httpd.exe" 2>nul | find /i "httpd.exe" >nul
    if errorlevel 1 (
        echo Lancement de Laragon...
        start "" "C:\laragon\laragon.exe"
        echo Si Apache/MySQL ne demarrent pas tout seuls, cliquez sur "Start All" dans Laragon.
        timeout /t 5 >nul
    ) else (
        echo Laragon deja demarre.
    )
) else (
    echo [ATTENTION] Laragon introuvable a l'emplacement par defaut C:\laragon.
    echo Demarrez Apache et MySQL manuellement avant de continuer.
)

echo Ouverture de l'application : %APP_URL%
start "" "%APP_URL%"

echo.
echo Vous pouvez fermer cette fenetre.
pause
