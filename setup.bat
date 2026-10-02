@echo off
title Setup Data-Labeler Workspace
echo ======================================================
echo    SETUP DATA-LABELER UNTUK KOMPETISI BARU
echo ======================================================
echo.

if not exist ".env" (
    echo [1/5] Membuat file .env dari .env.example...
    copy .env.example .env
) else (
    echo [1/5] File .env sudah ada.
)

echo [2/5] Menginstal dependency PHP (Composer)...
call composer install --no-interaction

echo [3/5] Generate Application Key...
call php artisan key:generate

echo [4/5] Menjalankan migrasi database...
call php artisan migrate --force

echo [5/5] Membangun asset frontend...
if exist "package.json" (
    call npm install
    call npm run build
)

echo.
echo ======================================================
echo    SETUP SELESAI!
echo    Jalankan 'run-dev.bat' untuk mulai melabeli secara lokal,
echo    atau 'run-ngrok.bat' untuk sharing online ke tim!
echo ======================================================
pause
