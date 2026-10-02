@echo off
setlocal enabledelayedexpansion
title Data Labeler - Cloudflare Tunnel
color 0E

REM ============================================================
REM  Data Labeler - Public sharing via Cloudflare Tunnel
REM  Menggantikan ngrok (sudah kena limit).
REM  URL: https://data-labeler.sfwn.dev  (tetap selamanya)
REM ============================================================

echo.
echo  ========================================================
echo     DATA LABELER - CLOUDFLARE TUNNEL
echo  ========================================================
echo.

REM --- Pre-flight: pastikan dependency lengkap ---
where php >nul 2>nul
if errorlevel 1 (
    echo  [X] PHP tidak ditemukan di PATH.
    echo      Pastikan PHP sudah terinstall dan ada di PATH.
    pause
    exit /b 1
)

where cloudflared >nul 2>nul
if errorlevel 1 (
    echo  [X] cloudflared tidak ditemukan di PATH.
    echo      Install dengan: winget install --id Cloudflare.cloudflared
    echo      Lalu restart PowerShell/CMD.
    pause
    exit /b 1
)

if not exist "%USERPROFILE%\.cloudflared\config.yml" (
    echo  [X] File config tunnel tidak ditemukan:
    echo      %USERPROFILE%\.cloudflared\config.yml
    echo      Jalankan: cloudflared tunnel login
    pause
    exit /b 1
)

REM --- Matikan tunnel lama supaya tidak konflik port ---
taskkill /IM cloudflared.exe /F >nul 2>nul

echo  [1/2] Menjalankan server Laravel di port 8000...
start "Laravel Server" php artisan serve --port=8000
timeout /t 3 /nubreak >nul

echo  [2/2] Menghubungkan Cloudflare Tunnel...
echo.
echo  ------------------------------------------------------------
echo   URL untuk tim : https://data-labeler.sfwn.dev
echo   Passkey        : cek di /admin - Kontrol Workspace
echo  ------------------------------------------------------------
echo.
echo  JANGAN tutup jendela ini selama tim sedang melabeli.
echo  Tekan Ctrl+C untuk menghentikan tunnel.
echo.

cloudflared tunnel run data-labeler

echo.
echo  Tunnel dihentikan. Server Laravel mungkin masih jalan.
echo  Tutup jendela "Laravel Server" juga jika tidak dipakai lagi.
pause