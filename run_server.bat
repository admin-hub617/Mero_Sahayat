@echo off
echo ========================================================
echo Starting Mero Sahayata with PHP Built-in Server
echo ========================================================
cd /d "%~dp0"

echo Running on http://localhost:8000
echo Press Ctrl+C in this window to stop the server.
echo.

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000
) else (
    php -S localhost:8000
)

pause
