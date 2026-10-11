@echo off
rem Double-click to export the local WordPress and deploy it to Vercel (production).
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\publish.ps1" -Deploy
echo.
pause
