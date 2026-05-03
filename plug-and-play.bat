@echo off
setlocal
cd /d "%~dp0"

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0plug-and-play.ps1" %*
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo.
    echo plug-and-play.ps1 failed with exit code %EXIT_CODE%.
    pause
    exit /b %EXIT_CODE%
)

echo.
echo Plug and play finished. The server window can stay open while you use the project.
pause
exit /b 0
