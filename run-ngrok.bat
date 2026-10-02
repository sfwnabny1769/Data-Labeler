@echo off
title Data Labeler - Online Ngrok Sharing
echo ======================================================
echo    DATA LABELER - ONLINE SHARING VIA NGROK
echo ======================================================
echo.
echo 1. Membuka server Laravel lokal di port 8000 (background window)...
start "Laravel Server" php artisan serve --port=8000

timeout /t 2 /nobreak >nul

echo 2. Menghubungkan tunnel ngrok ke port 8000...
echo Bagikan URL HTTPS dari ngrok kepada seluruh anggota tim.
echo.
ngrok http 8000
pause
