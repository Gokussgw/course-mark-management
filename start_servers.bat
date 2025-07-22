@echo off
echo Starting Course Mark Management System...
echo.

echo 1. Starting Backend Server (PHP)...
cd backend
start "Backend Server" cmd /k "php -S localhost:8080 router.php"
cd ..

echo 2. Waiting for backend to start...
timeout /t 3 /nobreak >nul

echo 3. Starting Frontend Server (Vue.js)...
cd frontend
start "Frontend Server" cmd /k "npm run serve"
cd ..

echo.
echo ========================================
echo    Course Mark Management System
echo ========================================
echo Backend:  http://localhost:8080
echo Frontend: http://localhost:8081 (or check console)
echo Test API: Open test_login.html in browser
echo.
echo Press any key to exit this window...
pause >nul
