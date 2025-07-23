@echo off
echo ========================================
echo    Course Mark Management System
echo            Server Startup
echo ========================================
echo.

echo [1/4] Checking directories...
if not exist "backend" (
    echo ERROR: Backend directory not found!
    pause
    exit /b 1
)
if not exist "frontend" (
    echo ERROR: Frontend directory not found!
    pause
    exit /b 1
)
echo ✓ Directories found

echo.
echo [2/4] Starting Backend Server (PHP Slim 4)...
cd backend
start "Backend API Server" cmd /k "php -S localhost:3000 router.php"
cd ..
echo ✓ Backend starting on http://localhost:3000

echo.
echo [3/4] Waiting for backend to initialize...
timeout /t 5 /nobreak >nul

echo.
echo [4/4] Starting Frontend Server (Vue.js 3)...
cd frontend
start "Frontend Dev Server" cmd /k "npm run serve"
cd ..
echo ✓ Frontend starting (will be available on http://localhost:8084)

echo.
echo ========================================
echo           🚀 SERVERS STARTED
echo ========================================
echo.
echo 📡 Backend API:  http://localhost:3000
echo 🌐 Frontend App: http://localhost:8084
echo.
echo 🔐 Admin Login:
echo    Email: admin@example.com
echo    Password: admin123
echo.
echo 📋 Available Pages:
echo    • Admin Dashboard: http://localhost:8084/admin/dashboard
echo    • Login Page: http://localhost:8084/login
echo    • Advisor Dashboard: http://localhost:8084/advisor/dashboard
echo.
echo ⚡ Both servers are running in separate windows.
echo    Close this window or press Ctrl+C to stop monitoring.
echo.
echo 📝 Note: If frontend port conflicts, check the terminal
echo    for the actual port (usually 8084, 8085, or 8086)
echo.
pause
