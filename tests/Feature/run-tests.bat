@echo off
REM Runs the smoke and logic tests against this project's own database (from .env). Nothing is saved: every test is rolled back.
set PHP=C:\xampp\php\php.exe
cd /d "%~dp0.."
for /f "tokens=1,* delims==" %%a in ('findstr /b "DB_CONNECTION= DB_DATABASE= DB_HOST= DB_PORT= DB_USERNAME= DB_PASSWORD=" .env') do set %%a=%%b
echo Testing %CD% on database %DB_DATABASE%
%PHP% artisan test tests/Feature/ProductionSmokeTest.php
pause
