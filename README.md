# Artisan Platform - Centralized Online Platform for Artisan Empowerment and Employment in Nigeria

A comprehensive web-based platform connecting artisans and professionals with employers while providing government agencies access to reliable workforce data.

## Project Overview

The Artisan Platform replaces manual recruitment with a transparent, efficient digital system that supports nationwide scalability across Nigeria. It serves three main user roles:

- **Artisans/Professionals**: Browse jobs, apply for positions, manage profiles, and build reputation
- **Employers**: Post job vacancies, search for artisans, manage applications, and rate workers
- **Admin/Government Agencies**: Verify profiles, manage users, generate workforce reports, and monitor employment statistics

## Technology Stack

- **Backend**: Pure PHP 8.0+ (No frameworks)
- **Database**: MySQL 8.0+ or MariaDB
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Security**: PDO prepared statements, password hashing, CSRF protection, session management
- **Architecture**: MVC-inspired clean folder structure

## Features

### Authentication & Security
- Secure registration and login system
- Password hashing using bcrypt (password_hash/password_verify)
- Role-based access control (RBAC)
- Profile verification system
- Session handling and logout
- CSRF-safe form handling

### Artisan Features
- Register and login
- Create, view, update, and delete profile
- Add skills, experience, and location
- Upload documents and work samples
- Set availability status (Available/Busy/Unavailable)
- Search and filter job vacancies
- Apply for jobs
- Receive notifications
- Send and receive messages with employers
- View ratings and reviews
- Full CRUD operations for profile, skills, documents, and applications

### Employer Features
- Register as individual or company
- Create, edit, view, and delete job vacancies
- Search artisans by skill, location, and experience
- View artisan profiles and ratings
- Invite or hire artisans
- Manage job applications
- Rate and review artisans
- Full CRUD operations for profile, job postings, applications, and reviews

### Admin/Government Dashboard
- Login as admin
- View all artisans and employers
- Verify artisan and employer profiles
- View workforce statistics
- Manage users (activate, suspend, delete)
- Generate reports for:
  - Skills distribution
  - Location-based workforce data
  - Employment statistics
- Full CRUD operations for users, verification records, and reports

### Shared Features
- Dashboards for each role
- Notification system (database-based)
- Messaging system between artisans and employers
- Rating and review system
- Search and filter functionality
- Responsive design for mobile and desktop

## Database Schema

The platform uses 11 normalized tables:

1. **users** - Base user table for all roles
2. **artisan_profiles** - Artisan-specific profile information
3. **employer_profiles** - Employer-specific profile information
4. **skills** - Master list of available skills
5. **artisan_skills** - Junction table for artisan-skill relationships
6. **jobs** - Job postings
7. **job_applications** - Job applications from artisans
8. **messages** - Direct messaging between users
9. **notifications** - User notifications
10. **reviews** - Ratings and reviews
11. **documents** - Uploaded documents and certificates
12. **verification_logs** - Profile verification audit trail

## Project Structure

```
/artisan-platform
├── /config
│   └── database.php              # Database configuration and connection
├── /auth
│   ├── login.php                 # User login page
│   ├── register.php              # User registration page
│   └── logout.php                # Logout handler
├── /artisan
│   ├── dashboard.php             # Artisan dashboard
│   ├── profile.php               # Artisan profile management
│   ├── jobs.php                  # Job search and browsing
│   ├── applications.php          # Manage applications
│   ├── job-detail.php            # Job details
│   ├── apply-job.php             # Job application form
│   └── upload-document.php       # Document upload handler
├── /employer
│   ├── dashboard.php             # Employer dashboard
│   ├── profile.php               # Employer profile management
│   ├── post-job.php              # Create job posting
│   ├── my-jobs.php               # Manage job postings
│   ├── artisans.php              # Search artisans
│   ├── applications.php          # Manage applications
│   ├── job-detail.php            # Job details
│   └── application-detail.php    # Application details
├── /admin
│   ├── dashboard.php             # Admin dashboard
│   ├── users.php                 # Manage users
│   ├── verifications.php         # Manage verifications
│   ├── verification-detail.php   # Review verification
│   ├── user-detail.php           # User management
│   └── reports.php               # Generate reports
├── /user
│   ├── messages.php              # Messaging system
│   ├── notifications.php         # Notifications
│   └── settings.php              # User settings
├── /uploads                      # User uploaded files
├── /assets
│   ├── /css
│   │   ├── style.css             # Main stylesheet
│   │   └── responsive.css        # Responsive design
│   └── /js
│       └── main.js               # Client-side functionality
├── /includes
│   ├── header.php                # Header template
│   ├── footer.php                # Footer template
│   └── auth_check.php            # Authentication functions
├── index.php                     # Home page
├── database.sql                  # Database schema
└── README.md                     # This file
```

## Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or MariaDB
- Web server (Apache, Nginx, etc.)
- Command line access

### Step 1: Clone/Download the Project

```bash
cd /var/www/html
git clone <repository-url> artisan-platform
cd artisan-platform
```

### Step 2: Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database and import schema
CREATE DATABASE artisan_platform_db;
USE artisan_platform_db;
SOURCE /path/to/artisan-platform/database.sql;
```

Or import via command line:

```bash
mysql -u root -p artisan_platform_db < database.sql
```

### Step 3: Configure Database Connection

Edit `/config/database.php` and update credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'artisan_platform_db');
```

### Step 4: Set File Permissions

```bash
chmod -R 755 /artisan-platform
chmod -R 777 /artisan-platform/uploads
```

### Step 5: Access the Application

#### Using Apache (XAMPP/WAMP/LAMP)

Open your browser and navigate to:

```
http://localhost/artisan-platform
```

#### Using PHP Built-in Server

If you have XAMPP installed, start the development server:

```bash
C:\xampp\php\php.exe -S localhost:8000
```

Then navigate to:

```
http://localhost:8000
```

**Note**: If PHP is not in your system PATH, use the full path to the PHP executable as shown above.

## Default Test Accounts

After importing the database, you can create test accounts by registering through the application.

### Admin Account (Manual Creation)

To create an admin account, insert directly into the database:

```sql
INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
VALUES ('admin@artisanplatform.ng', '$2y$12$...', 'Admin', 'User', '+2341234567890', 'admin', 'active', 1, 1);
```

Use `password_hash('password', PASSWORD_BCRYPT)` to generate the hashed password.

## Usage Guide

### For Artisans

1. **Register**: Create account with email, phone, and password
2. **Complete Profile**: Add bio, location, state, experience, and hourly rate
3. **Add Skills**: Add your skills with proficiency levels
4. **Upload Documents**: Add certificates and work samples
5. **Browse Jobs**: Search and filter available jobs
6. **Apply for Jobs**: Submit applications with cover letters
7. **Manage Applications**: Track application status
8. **Messaging**: Communicate with employers
9. **Build Reputation**: Receive ratings and reviews

### For Employers

1. **Register**: Create account as individual or company
2. **Complete Profile**: Add company information and details
3. **Post Jobs**: Create job vacancies with requirements
4. **Search Artisans**: Find artisans by skills, location, experience
5. **Review Applications**: Review and manage job applications
6. **Hire Artisans**: Accept applications and hire workers
7. **Rate & Review**: Provide feedback on completed work
8. **Messaging**: Communicate with artisans

### For Admin/Government

1. **Login**: Access admin dashboard
2. **Review Verifications**: Approve or reject user profiles
3. **Manage Users**: Activate, suspend, or delete accounts
4. **View Statistics**: Monitor workforce data
5. **Generate Reports**: Create employment and skills reports
6. **Monitor Activity**: Track platform usage and matches

## Security Features

- **Password Security**: Bcrypt hashing with cost factor 12
- **SQL Injection Prevention**: PDO prepared statements for all queries
- **CSRF Protection**: Token generation and verification
- **Session Management**: Secure session handling with role-based access
- **Input Validation**: Sanitization and validation of all user inputs
- **File Upload Security**: File type and size restrictions
- **Error Handling**: Proper error messages without exposing system details

## API Endpoints (Future Enhancement)

The application is designed to support RESTful API endpoints in future versions:

- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `GET /api/jobs` - List jobs
- `POST /api/jobs` - Create job
- `GET /api/artisans` - Search artisans
- `POST /api/applications` - Submit application
- `GET /api/messages` - Get messages
- `POST /api/messages` - Send message

## Performance Optimization

- Database indexes on frequently queried columns
- Pagination for large result sets
- Caching mechanisms for static content
- Optimized SQL queries with proper joins
- Lazy loading for images and documents

## Troubleshooting

### Database Connection Error

Check database credentials in `/config/database.php` and ensure MySQL is running.

### Permission Denied on Uploads

```bash
chmod -R 777 /artisan-platform/uploads
```

### Session Issues

Ensure PHP session directory is writable:

```bash
chmod -R 777 /var/lib/php/sessions
```

### CSRF Token Errors

Clear browser cookies and cache, then try again.

## Development Guidelines

### Code Style

- Use camelCase for variables and functions
- Use snake_case for database columns
- Add comments for complex logic
- Follow DRY (Don't Repeat Yourself) principle
- Validate all user inputs

### Database Queries

- Always use prepared statements
- Use meaningful column aliases
- Include proper indexes
- Avoid N+1 query problems

### Security

- Never trust user input
- Always escape output
- Use HTTPS in production
- Keep dependencies updated
- Regular security audits

## Deployment Checklist

- [ ] Database backed up
- [ ] Configuration files secured
- [ ] File permissions set correctly
- [ ] HTTPS enabled
- [ ] Error logging configured
- [ ] Uploads directory outside webroot (optional)
- [ ] Database credentials in environment variables
- [ ] Session timeout configured
- [ ] Rate limiting implemented
- [ ] Monitoring and logging enabled

## Future Enhancements

- RESTful API for mobile apps
- Advanced analytics dashboard
- Payment integration
- Email notifications
- SMS notifications
- Two-factor authentication
- Profile recommendations
- Job matching algorithm
- Video profiles for artisans
- Dispute resolution system
- Escrow payment system

## Contributing

1. Follow the code style guidelines
2. Test all changes thoroughly
3. Document new features
4. Submit pull requests with clear descriptions
5. Ensure backward compatibility

## License

This project is licensed under the MIT License.

## Support

For issues, questions, or suggestions, please contact:

- Email: support@artisanplatform.ng
- Phone: +234 (0) 123 456 7890
- Website: https://www.artisanplatform.ng

## Acknowledgments

- Built for the National Directorate of Employment (NDE), Nigeria
- Designed to empower artisans and professionals across Nigeria
- Supporting transparent and efficient digital recruitment

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Status**: Production Ready
