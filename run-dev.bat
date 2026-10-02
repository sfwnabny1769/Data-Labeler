
@echo off
title Data Labeler - Local Server
echo ======================================================
echo    DATA LABELER - RUNNING LOCAL WORKSPACE
echo ======================================================
echo.
echo Akses Web: http://127.0.0.1:8000
echo Akses Admin: http://127.0.0.1:8000/admin (Password default: admin123)
echo.
php artisan serve --port=8000
pause
