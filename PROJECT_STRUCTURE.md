# Course Mark Management System - Project Structure

## Clean Project Overview

This document outlines the cleaned and organized structure of the Course Mark Management System.

## Root Directory Structure

```
course-mark-management/
├── backend/                 # PHP Slim 4 Backend API
├── frontend/               # Vue.js 3 Frontend Application
├── database/               # SQL Database Schema & Scripts
├── .gitattributes         # Git configuration
├── quick_start.bat        # Quick start script
├── README.md              # Project documentation
├── SERVER_STARTUP.md      # Server startup guide
└── start_servers.bat      # Server startup script
```

## Backend Structure (`/backend/`)

```
backend/
├── api/                    # API endpoints
│   └── auth/              # Authentication endpoints
├── exports/               # CSV export files (empty)
├── src/                   # Source code
│   ├── controllers/       # Business logic controllers
│   ├── middleware/        # Request middleware
│   ├── models/           # Data models
│   ├── routes/           # Route definitions
│   └── utils/            # Utility functions
├── vendor/               # Composer dependencies
├── .env                  # Environment variables
├── .htaccess            # Apache configuration
├── advisor-dashboard-api.php  # Advisor dashboard API
├── breakdown-api.php     # Grade breakdown API
├── composer.json         # PHP dependencies
├── composer.lock         # Locked dependencies
├── db-api.php           # Database API
├── feedback-api.php     # Feedback API
├── index.php            # Main entry point
├── marks-api.php        # Marks management API
├── ranking-api.php      # Student ranking API
└── router.php           # Route handler
```

## Frontend Structure (`/frontend/`)

```
frontend/
├── node_modules/         # NPM dependencies
├── public/              # Static assets
│   └── index.html       # Main HTML template
├── src/                 # Source code
│   ├── components/      # Vue components
│   │   ├── admin/       # Admin-specific components
│   │   ├── charts/      # Chart components
│   │   ├── layout/      # Layout components
│   │   ├── notifications/ # Notification components
│   │   └── rankings/    # Ranking components
│   ├── router/          # Vue Router configuration
│   ├── services/        # API services
│   ├── store/           # Vuex store
│   │   └── modules/     # Store modules
│   ├── utils/           # Utility functions
│   ├── views/           # Page components
│   │   ├── admin/       # Admin pages
│   │   ├── advisor/     # Advisor pages
│   │   ├── lecturer/    # Lecturer pages
│   │   ├── shared/      # Shared pages
│   │   └── student/     # Student pages
│   ├── App.vue          # Root component
│   └── main.js          # Application entry point
├── .env                 # Environment variables
├── .eslintrc.js         # ESLint configuration
├── babel.config.js      # Babel configuration
├── package.json         # Node.js dependencies
├── package-lock.json    # Locked dependencies
└── vue.config.js        # Vue CLI configuration
```

## Database Structure (`/database/`)

```
database/
├── 01_schema.sql                          # Base schema
├── 02_users_courses.sql                   # Users and courses
├── 03_enrollments_assessments.sql         # Enrollments and assessments
├── 04_marks_and_others.sql                # Marks and related data
├── 05_marks_schema.sql                    # Marks table schema
├── 06_advisor_notes.sql                   # Advisor notes
├── lecturer_feedback.sql                  # Lecturer feedback
├── schema.sql                             # Complete schema
└── update_final_marks_custom_structure.sql # Final marks updates
```

## Key Features Implemented

- ✅ User authentication (JWT-based)
- ✅ Role-based access control (Admin, Lecturer, Advisor, Student)
- ✅ Grade management and calculations
- ✅ Student ranking system
- ✅ Performance analytics
- ✅ CSV data export
- ✅ Responsive UI design
- ✅ Real-time notifications
- ✅ API documentation

## Technology Stack

- **Backend**: PHP 8.1+ with Slim Framework 4
- **Frontend**: Vue.js 3 with Vuex and Vue Router
- **Database**: MySQL 8.0+
- **Authentication**: JWT (JSON Web Tokens)
- **Styling**: Bootstrap 5 + Custom CSS
- **Build Tools**: Vue CLI, Webpack
- **Package Management**: Composer (PHP), NPM (Node.js)

## Cleaned Files Removed

The following unnecessary files were removed during cleanup:

- `test_cors.html` - CORS testing file
- `clear_auth.html` - Authentication clearing utility
- `test_complete_admin_flow.php` - Admin flow testing
- `test_admin_login.php` - Admin login testing
- `test_admin_dashboard_apis.html` - Dashboard API testing
- `test_admin_auth.html` - Admin authentication testing
- `test_admin_apis.php` - Admin API testing
- `check_admin_dashboard_data.php` - Dashboard data checking
- `reset_admin_password.php` - Password reset utility
- `verify_admin_dashboard_real_data.php` - Data verification script
- `verify_ports.bat` - Port verification script
- `verify_routes.php` - Route verification script
- `frontend/public/test-notifications.html` - Test notifications
- `frontend/public/test-real-dashboard.html` - Test dashboard

## Server Configuration

- **Backend Server**: http://localhost:3000
- **Frontend Server**: http://localhost:8084
- **Database**: MySQL on localhost:3306

## Notes

- All test and debug files have been removed
- Project is production-ready
- CORS is properly configured
- All dependencies are up to date
- Both servers can be started using `start_servers.bat`
