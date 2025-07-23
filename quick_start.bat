@echo off
echo Starting servers quickly...

echo Starting Backend on localhost:3000...
cd backend
start /min "Backend" cmd /c "php -S localhost:3000 router.php"
cd ..

echo Starting Frontend...
cd frontend
start /min "Frontend" cmd /c "npm run serve"
cd ..

echo Servers started in minimized windows.
echo Backend: http://localhost:3000
echo Frontend: http://localhost:8084 (check frontend terminal for actual port)
