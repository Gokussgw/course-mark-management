# 🚀 Server Startup Guide

## Quick Start

### Option 1: Interactive Startup (Recommended)

```cmd
.\start_servers.bat
```

- Provides detailed startup information
- Shows all available URLs and login credentials
- Keeps terminal open for monitoring

### Option 2: Quick Startup (Minimal)

```cmd
.\quick_start.bat
```

- Starts servers in minimized windows
- No interactive prompts
- Good for development workflow

## Manual Startup

### Backend Server (PHP Slim 4)

```cmd
cd backend
php -S localhost:3000 router.php
```

### Frontend Server (Vue.js 3)

```cmd
cd frontend
npm run serve
```

## Server Configuration

| Service     | URL                   | Port | Purpose                     |
| ----------- | --------------------- | ---- | --------------------------- |
| Backend API | http://localhost:3000 | 3000 | PHP Slim 4 REST API         |
| Frontend    | http://localhost:8080 | 8080 | Vue.js 3 Development Server |

## Default Login Credentials

### Admin Account

- **Email:** admin@example.com
- **Password:** admin123
- **Role:** Admin

### Test Accounts

- **Lecturer:** lecturer1@example.com / password123
- **Student:** student1@example.com / password123
- **Advisor:** advisor1@example.com / password123

## Available Pages

### Admin Dashboard

- URL: http://localhost:8080/admin/dashboard
- Features: User management, system statistics, logs

### Advisor Dashboard

- URL: http://localhost:8080/advisor/dashboard
- Features: Student reports, risk assessment

### Login Page

- URL: http://localhost:8080/login
- Entry point for all users

## Troubleshooting

### Port Conflicts

If ports are in use, the Vue dev server will automatically use the next available port (8081, 8082, etc.). Check the frontend terminal for the actual URL.

### Backend Issues

- Ensure PHP is installed and in PATH
- Check that port 3000 is available
- Verify database connection in .env file

### Frontend Issues

- Run `npm install` if node_modules are missing
- Check Node.js version compatibility
- Clear npm cache if needed: `npm cache clean --force`

### Database Connection

- Ensure MySQL/MariaDB is running
- Database name: `course_mark_management`
- Check credentials in `backend/.env`

## Development Notes

- Frontend uses proxy configuration to route `/api` calls to backend
- CORS is enabled for localhost development
- Hot reload is enabled for both servers
- Source maps are generated for debugging

## Production Deployment

For production, you'll need to:

1. Build the frontend: `npm run build`
2. Configure a proper web server (Apache/Nginx)
3. Set up production database
4. Update environment variables
5. Enable HTTPS
