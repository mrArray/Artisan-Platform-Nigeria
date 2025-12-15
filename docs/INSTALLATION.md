# Artisan Platform - Installation & Testing Guide

## System Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx
- **Browser**: Chrome, Firefox, Safari, Edge (modern versions)
- **Disk Space**: Minimum 500MB
- **RAM**: Minimum 512MB

## Pre-Installation Checklist

- [ ] PHP 8.0+ installed and configured
- [ ] MySQL/MariaDB installed and running
- [ ] Web server installed and configured
- [ ] PHP extensions enabled: PDO, PDO_MySQL
- [ ] Write permissions on web root directory
- [ ] Command line access to MySQL

## Step-by-Step Installation

### 1. Download/Clone the Project

```bash
# Navigate to web root
cd /var/www/html

# Clone the repository (or extract if downloaded as ZIP)
git clone <repository-url> artisan-platform
cd artisan-platform

# Or if using ZIP
unzip artisan-platform.zip
cd artisan-platform
```

### 2. Create Database

#### Option A: Using Command Line

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE artisan_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE artisan_platform;
SOURCE /path/to/artisan-platform/database.sql;
EXIT;
```

#### Option B: Using MySQL Command

```bash
mysql -u root -p artisan_platform < /path/to/artisan-platform/database.sql
```

#### Option C: Using phpMyAdmin

1. Open phpMyAdmin in browser
2. Create new database: `artisan_platform`
3. Select the database
4. Go to "Import" tab
5. Select `database.sql` file
6. Click "Go"

### 3. Configure Database Connection

Edit `/config/database.php`:

```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_USER', 'root');           // Your database user
define('DB_PASS', 'your_password');  // Your database password
define('DB_NAME', 'artisan_platform'); // Database name
```

### 4. Set File Permissions

```bash
# Make all files readable
chmod -R 755 /var/www/html/artisan-platform

# Make uploads directory writable
chmod -R 777 /var/www/html/artisan-platform/uploads

# Make sure PHP can write to uploads
chown -R www-data:www-data /var/www/html/artisan-platform/uploads
```

### 5. Configure Web Server

#### Apache Configuration

Create `.htaccess` in project root (if not exists):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /artisan-platform/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?/$1 [L]
</IfModule>
```

#### Nginx Configuration

Add to your Nginx server block:

```nginx
location /artisan-platform/ {
    try_files $uri $uri/ /artisan-platform/index.php?$query_string;
}
```

### 6. Verify Installation

1. Open browser and navigate to: `http://localhost/artisan-platform`
2. You should see the home page
3. Click "Register" to create a test account
4. Test login functionality

## Creating Test Accounts

### Method 1: Register Through UI

1. Go to `http://localhost/artisan-platform/auth/register.php`
2. Fill in the form:
   - First Name: John
   - Last Name: Doe
   - Email: john@example.com
   - Phone: +2341234567890
   - Password: Test@1234
   - Role: Artisan or Employer
3. Click "Create Account"
4. Login with your credentials

### Method 2: Direct Database Insert

```sql
-- Insert Artisan User
INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
VALUES ('artisan@test.com', '$2y$12$YOUR_HASHED_PASSWORD', 'Test', 'Artisan', '+2341234567890', 'artisan', 'active', 1, 1);

-- Insert Employer User
INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
VALUES ('employer@test.com', '$2y$12$YOUR_HASHED_PASSWORD', 'Test', 'Employer', '+2341234567890', 'employer', 'active', 1, 1);

-- Insert Admin User
INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
VALUES ('admin@test.com', '$2y$12$YOUR_HASHED_PASSWORD', 'Test', 'Admin', '+2341234567890', 'admin', 'active', 1, 1);
```

To generate hashed password in PHP:

```php
<?php
echo password_hash('Test@1234', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```

### Method 3: Create Admin Account via SQL

```sql
-- Generate password hash: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
VALUES ('admin@artisanplatform.ng', '$2y$12$K1DjrP3/LewKppQuen8He.nJZZZ.Z.Z.Z.Z.Z.Z.Z.Z.Z.Z.Z.Z', 'Admin', 'User', '+2341234567890', 'admin', 'active', 1, 1);
```

## Testing Procedures

### 1. Authentication Testing

#### Test Case 1.1: User Registration
- [ ] Navigate to register page
- [ ] Fill in all required fields
- [ ] Verify validation messages for invalid input
- [ ] Submit form and verify success message
- [ ] Check database for new user record

#### Test Case 1.2: User Login
- [ ] Navigate to login page
- [ ] Enter valid credentials
- [ ] Verify redirect to appropriate dashboard
- [ ] Check session is created
- [ ] Verify user information in header

#### Test Case 1.3: Password Security
- [ ] Verify password is hashed in database
- [ ] Test password with special characters
- [ ] Verify minimum password length requirement
- [ ] Test password confirmation matching

#### Test Case 1.4: CSRF Protection
- [ ] Inspect form for CSRF token
- [ ] Verify token is different for each page load
- [ ] Try submitting form without token (should fail)
- [ ] Try submitting form with invalid token (should fail)

### 2. Role-Based Access Control Testing

#### Test Case 2.1: Artisan Access
- [ ] Login as artisan
- [ ] Verify access to artisan dashboard
- [ ] Verify access to profile page
- [ ] Verify access to job search
- [ ] Verify NO access to employer pages
- [ ] Verify NO access to admin pages

#### Test Case 2.2: Employer Access
- [ ] Login as employer
- [ ] Verify access to employer dashboard
- [ ] Verify access to post job page
- [ ] Verify access to my jobs page
- [ ] Verify NO access to artisan pages
- [ ] Verify NO access to admin pages

#### Test Case 2.3: Admin Access
- [ ] Login as admin
- [ ] Verify access to admin dashboard
- [ ] Verify access to user management
- [ ] Verify access to verification management
- [ ] Verify NO access to artisan/employer pages

### 3. Artisan Profile Testing

#### Test Case 3.1: Profile CRUD
- [ ] Create profile (register as artisan)
- [ ] Read profile (view my profile)
- [ ] Update profile (edit bio, location, state)
- [ ] Verify changes saved in database
- [ ] Verify profile displays correctly

#### Test Case 3.2: Skills Management
- [ ] Add skill to profile
- [ ] Verify skill appears in list
- [ ] Update skill proficiency level
- [ ] Delete skill
- [ ] Verify skill removed from database

#### Test Case 3.3: Document Upload
- [ ] Upload PDF document
- [ ] Upload image file
- [ ] Verify file saved in uploads directory
- [ ] Verify file path stored in database
- [ ] Download uploaded file
- [ ] Delete uploaded file

### 4. Job Management Testing

#### Test Case 4.1: Job Posting (Employer)
- [ ] Login as employer
- [ ] Navigate to post job page
- [ ] Fill in all required fields
- [ ] Select required skills
- [ ] Submit form
- [ ] Verify job appears in "My Jobs"
- [ ] Verify job in database

#### Test Case 4.2: Job Search (Artisan)
- [ ] Login as artisan
- [ ] Navigate to find jobs
- [ ] Verify all jobs display
- [ ] Test search by title
- [ ] Test filter by location
- [ ] Test filter by state
- [ ] Test filter by budget range
- [ ] Test filter by experience level
- [ ] Test sort options

#### Test Case 4.3: Job Application
- [ ] Login as artisan
- [ ] Find a job
- [ ] Click "Apply Now"
- [ ] Fill in application form
- [ ] Submit application
- [ ] Verify application in "My Applications"
- [ ] Verify employer sees application

### 5. Messaging Testing

#### Test Case 5.1: Send Message
- [ ] Login as user
- [ ] Navigate to compose message
- [ ] Select recipient
- [ ] Enter subject and message
- [ ] Submit form
- [ ] Verify message in "Sent" folder
- [ ] Verify message in recipient's inbox

#### Test Case 5.2: Read Message
- [ ] Login as recipient
- [ ] Navigate to inbox
- [ ] Verify unread count
- [ ] Click message to view
- [ ] Verify message marked as read
- [ ] Verify unread count decreased

#### Test Case 5.3: Delete Message
- [ ] Login as user
- [ ] Navigate to inbox or sent
- [ ] Delete a message
- [ ] Verify message removed from list
- [ ] Verify message removed from database

### 6. Notification Testing

#### Test Case 6.1: Receive Notification
- [ ] Perform action that creates notification (e.g., job posted)
- [ ] Login as affected user
- [ ] Navigate to notifications
- [ ] Verify notification appears
- [ ] Verify unread count

#### Test Case 6.2: Mark as Read
- [ ] Click "Mark as Read" on notification
- [ ] Verify notification status changes
- [ ] Verify unread count decreases

#### Test Case 6.3: Delete Notification
- [ ] Delete a notification
- [ ] Verify notification removed
- [ ] Verify database updated

### 7. Admin Functions Testing

#### Test Case 7.1: User Management
- [ ] Login as admin
- [ ] Navigate to manage users
- [ ] Search for user
- [ ] Filter by role
- [ ] Filter by status
- [ ] Suspend a user
- [ ] Activate a user
- [ ] Delete a user

#### Test Case 7.2: Verification Management
- [ ] Login as admin
- [ ] Navigate to verifications
- [ ] View pending verifications
- [ ] Approve a profile
- [ ] Reject a profile with reason
- [ ] Verify user receives notification

#### Test Case 7.3: Reports
- [ ] Navigate to reports page
- [ ] View skill distribution
- [ ] View location-based workforce
- [ ] View employment statistics

### 8. Data Validation Testing

#### Test Case 8.1: Form Validation
- [ ] Submit form with empty required fields
- [ ] Verify error messages display
- [ ] Submit form with invalid email
- [ ] Submit form with invalid phone
- [ ] Submit form with mismatched passwords

#### Test Case 8.2: Database Constraints
- [ ] Try to register with duplicate email
- [ ] Verify error message
- [ ] Try to add duplicate skill
- [ ] Verify error message

### 9. Security Testing

#### Test Case 9.1: SQL Injection
- [ ] Try SQL injection in search field: `' OR '1'='1`
- [ ] Verify no SQL injection occurs
- [ ] Verify results are properly escaped

#### Test Case 9.2: XSS Prevention
- [ ] Try XSS in form field: `<script>alert('XSS')</script>`
- [ ] Verify script doesn't execute
- [ ] Verify input is escaped in database

#### Test Case 9.3: Session Security
- [ ] Login and get session ID
- [ ] Try to use session in different browser
- [ ] Verify session is valid
- [ ] Logout and verify session destroyed

### 10. Performance Testing

#### Test Case 10.1: Page Load Time
- [ ] Measure home page load time (should be < 2s)
- [ ] Measure dashboard load time (should be < 2s)
- [ ] Measure job search load time (should be < 2s)

#### Test Case 10.2: Database Queries
- [ ] Enable query logging
- [ ] Check for N+1 query problems
- [ ] Verify indexes are being used
- [ ] Check query execution time

## Troubleshooting

### Database Connection Error

**Error**: "Database connection failed"

**Solution**:
1. Verify MySQL is running: `sudo service mysql status`
2. Check credentials in `/config/database.php`
3. Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`
4. Verify database user has permissions: `GRANT ALL ON artisan_platform.* TO 'user'@'localhost';`

### Permission Denied on Uploads

**Error**: "Permission denied" when uploading files

**Solution**:
```bash
chmod -R 777 /var/www/html/artisan-platform/uploads
chown -R www-data:www-data /var/www/html/artisan-platform/uploads
```

### Session Issues

**Error**: "Session data not persisting"

**Solution**:
1. Check PHP session directory exists and is writable
2. Verify `session.save_path` in php.ini
3. Check file permissions: `chmod -R 777 /var/lib/php/sessions`

### CSRF Token Errors

**Error**: "Security token invalid"

**Solution**:
1. Clear browser cookies
2. Clear browser cache
3. Restart browser
4. Try in incognito/private mode

### 404 Errors

**Error**: "Page not found"

**Solution**:
1. Verify file exists in correct location
2. Check file permissions (should be 755)
3. Verify web server configuration
4. Check .htaccess file (for Apache)

## Performance Optimization

### Database Optimization

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_job_employer ON jobs(employer_id);
CREATE INDEX idx_application_artisan ON job_applications(artisan_id);
CREATE INDEX idx_message_recipient ON messages(recipient_id);
```

### PHP Optimization

1. Enable OPcache in php.ini
2. Set appropriate memory limit (256MB+)
3. Enable gzip compression
4. Use persistent database connections

### Web Server Optimization

1. Enable gzip compression
2. Set proper cache headers
3. Use CDN for static assets
4. Enable HTTP/2 if available

## Backup & Recovery

### Database Backup

```bash
# Backup database
mysqldump -u root -p artisan_platform > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
mysql -u root -p artisan_platform < backup_20250101_120000.sql
```

### File Backup

```bash
# Backup entire project
tar -czf artisan-platform_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/html/artisan-platform

# Restore from backup
tar -xzf artisan-platform_backup_20250101_120000.tar.gz -C /var/www/html
```

## Production Deployment

### Pre-Deployment Checklist

- [ ] Update database credentials to production values
- [ ] Set PHP error reporting to production mode
- [ ] Enable HTTPS/SSL certificate
- [ ] Configure firewall rules
- [ ] Set up automated backups
- [ ] Configure error logging
- [ ] Set up monitoring and alerts
- [ ] Test all functionality in production environment
- [ ] Create admin account
- [ ] Document deployment steps

### Deployment Steps

1. Clone/download project to production server
2. Create production database
3. Import database schema
4. Update configuration files
5. Set proper file permissions
6. Configure web server
7. Enable HTTPS
8. Test all functionality
9. Set up monitoring
10. Create backup schedule

## Support & Troubleshooting

For additional help:
- Check error logs: `tail -f /var/log/apache2/error.log`
- Check PHP logs: `tail -f /var/log/php-fpm.log`
- Enable debug mode in code for detailed error messages
- Contact support: support@artisanplatform.ng

---

**Last Updated**: December 2025
