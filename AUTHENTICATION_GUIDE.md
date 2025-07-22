# Course Mark Management - Authentication System

## Overview

The authentication system has been updated to use a unified email and password login for all user types (students, lecturers, advisors, and administrators). Upon successful authentication, users are automatically redirected to their role-specific dashboards.

## Authentication Flow

### 1. Login Process

- **Single Login Form**: All users use the same login form with email and password fields
- **Backend Validation**: The API validates credentials against the `users` table using email and password
- **JWT Token**: Upon successful login, a JWT token is generated and returned to the client
- **Role-Based Redirect**: Users are automatically redirected to their appropriate dashboard based on their role

### 2. User Roles and Redirects

- **Student** (`role: 'student'`) → `/student/dashboard`
- **Lecturer** (`role: 'lecturer'`) → `/lecturer/dashboard`
- **Advisor** (`role: 'advisor'`) → `/advisor/dashboard`
- **Administrator** (`role: 'admin'`) → `/admin/dashboard`

## API Endpoints

### Login Endpoint

```
POST /api/auth/login
```

**Request Body:**

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Success Response (200):**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "student"
  }
}
```

**Error Response (401):**

```json
{
  "error": "Invalid email or password"
}
```

## Frontend Components

### Login Component (`/src/views/Login.vue`)

- Unified login form for all user types
- Handles authentication and role-based redirection
- Clean, responsive design with proper error handling

### Auth Store (`/src/store/modules/auth.js`)

- Manages authentication state
- Handles login/logout actions
- Stores JWT token and user information in localStorage

### Router Configuration (`/src/router/index.js`)

- Protected routes with role-based access control
- Automatic redirection for unauthorized access
- Navigation guards for authentication verification

## Sample Users (from database schema)

### Test Credentials

All test users use `password` as their password (assuming the hash in the database corresponds to "password"):

| Role     | Email                 | Password |
| -------- | --------------------- | -------- |
| Admin    | admin@example.com     | password |
| Lecturer | lecturer1@example.com | password |
| Advisor  | advisor1@example.com  | password |
| Student  | student1@example.com  | password |
| Student  | student2@example.com  | password |

## Security Features

### JWT Authentication

- Tokens expire after 1 hour
- Secure token validation middleware
- Automatic token refresh handling

### Password Security

- Passwords are hashed using PHP's `password_hash()` function
- Secure password verification with `password_verify()`

### Route Protection

- All authenticated routes require valid JWT tokens
- Role-based access control prevents unauthorized access
- Automatic logout on token expiration

## Development Testing

### Test File

A test HTML file (`test_login.html`) has been created in the project root to test the authentication API directly. This file includes:

- Quick-fill buttons for all test user credentials
- Real-time API testing interface
- Response validation and error handling

### Usage

1. Start the backend server
2. Open `test_login.html` in a browser
3. Click any "Fill [Role]" button to populate credentials
4. Click "Test Login" to verify the authentication API

## Database Changes

No database schema changes were required. The existing `users` table already supports email-based authentication for all user types including students.

## Next Steps

1. **Password Reset**: Implement password reset functionality
2. **Email Verification**: Add email verification for new registrations
3. **Multi-Factor Authentication**: Consider adding 2FA for enhanced security
4. **Session Management**: Implement proper session management and logout tracking
5. **Audit Logging**: Add authentication event logging to the system logs table

## Migration Notes

If migrating from the previous dual-authentication system:

1. Ensure all students have valid email addresses in the database
2. Update any existing authentication-related code to use email/password only
3. Test all user roles to verify proper redirection
4. Update user documentation to reflect the new login process
