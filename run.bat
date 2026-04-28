@echo off
title Running Laravel & Vite...

echo Starting Laravel Development Server at http://127.0.0.1:8000
echo.
php artisan serve & npm run dev

pause