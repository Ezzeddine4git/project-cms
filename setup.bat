@echo off
setlocal
cd /d "%~dp0"

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup.ps1" %*
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo.
    echo setup.ps1 failed with exit code %EXIT_CODE%.
    pause
    exit /b %EXIT_CODE%
)

echo.
echo Setup finished.
pause
exit /b 0
