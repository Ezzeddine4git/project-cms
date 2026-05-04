@echo off
setlocal
cd /d "%~dp0"

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0clean-database.ps1" %*
exit /b %ERRORLEVEL%
