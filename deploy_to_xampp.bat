@echo off
echo ========================================================
echo Deploying Mero Sahayata to XAMPP htdocs
echo ========================================================
set TARGET="C:\xampp\htdocs\mero-sahayata"

if not exist "C:\xampp\htdocs" (
    echo Error: C:\xampp\htdocs not found! Please check your XAMPP installation path.
    pause
    exit /b 1
)

echo Copying files to %TARGET% ...
xcopy /E /I /Y "%~dp0*" %TARGET%

echo.
echo ========================================================
echo Deployment Complete!
echo Open your browser and visit:
echo http://localhost/mero-sahayata/
echo ========================================================
pause
