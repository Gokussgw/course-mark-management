# Course Mark Management Web Application

A comprehensive web application for managing course marks with a Vue.js frontend, Slim PHP backend, and MySQL database.

## Features

- Multi-user role system (Lecturer, Student, Academic Advisor, Admin)
- Secure authentication with JWT
- Course and assessment management
- Mark tracking and analytics
- Role-based dashboards
- Notifications system
- Student performance simulation

## Prerequisites

- PHP 7.4 or higher
- Composer
- Node.js and npm
- MySQL database server

## Setup Instructions

### 1. Database Setup

1. Create a MySQL database named `course_mark_management`
2. Import the database schema:

   ```
   mysql -u YOUR_USERNAME -p course_mark_management < database/schema.sql
   ```

   Alternatively, use phpMyAdmin or MySQL Workbench to import the schema.

### 2. Backend Setup

1. Navigate to the backend directory:

   ```
   cd backend
   ```

2. Install PHP dependencies:

   ```
   composer install
   ```

3. Configure the environment:

   - Rename `.env.example` to `.env` if it doesn't exist
   - Update the database credentials and other configuration in `.env`

4. Start the PHP development server:
   ```
   php -S localhost:8080
   ```

### 3. Frontend Setup

1. Navigate to the frontend directory:

   ```
   cd frontend
   ```

2. Install Node.js dependencies:

   ```
   npm install
   ```

3. Configure the environment:

   - Create a `.env` file if it doesn't exist
   - Set the API URL to point to your backend:
     ```
     VUE_APP_API_URL=http://localhost:8080
     ```

4. Start the Vue.js development server:

   ```
   npm run serve
   ```

5. Open the application in your browser:
   ```
   http://localhost:8081
   ```

## User Roles and Accounts

For testing purposes, the following accounts are available:

- **Lecturer**

  - Email: lecturer@example.com
  - Password: password123

- **Student**

  - Email: student@example.com
  - Password: password123

- **Academic Advisor**

  - Email: advisor@example.com
  - Password: password123

- **Admin**
  - Email: admin@example.com
  - Password: password123

## Development Notes

- The frontend is built with Vue.js 3 and uses the Composition API
- The backend uses Slim PHP framework with a RESTful API architecture
- Authentication is handled via JSON Web Tokens (JWT)
- Database interactions use PDO for secure and efficient queries

## Troubleshooting

### Backend Issues

- Check that your MySQL service is running
- Verify database credentials in `.env`
- Ensure PHP has the required extensions (PDO, JSON, etc.)

### Frontend Issues

- Make sure Node.js and npm are properly installed
- Verify the API URL in the `.env` file is correct
- Check for JavaScript console errors in the browser

## License

This project is licensed under the MIT License - see the LICENSE file for details.
