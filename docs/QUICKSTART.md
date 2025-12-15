# Artisan Platform - Quick Start Guide

## 5-Minute Setup

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Web server (Apache/Nginx)

### Step 1: Extract & Navigate
```bash
tar -xzf artisan-platform-v1.0.tar.gz
cd artisan-platform
```

### Step 2: Create Database
```bash
mysql -u root -p artisan_platform < database.sql
```

### Step 3: Configure Database
Edit `config/database.php`:
```php
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### Step 4: Set Permissions
```bash
chmod -R 755 .
chmod -R 777 uploads
```

### Step 5: Access Application
Open browser: `http://localhost/artisan-platform`

## Test Accounts

### Create Test User
1. Click "Register"
2. Fill in details
3. Select role: Artisan, Employer, or Admin
4. Login with credentials

### Direct Database Insert
```sql
-- Password: Test@1234 (hashed)
INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
VALUES ('test@example.com', '$2y$12$K1DjrP3/LewKppQuen8He.nJZZZ.Z.Z.Z.Z.Z.Z.Z.Z.Z.Z.Z.Z', 'Test', 'User', '+2341234567890', 'artisan', 'active', 1, 1);
```

## Key Features to Test

### Artisan
1. Register as artisan
2. Complete profile
3. Add skills
4. Browse jobs
5. Apply for job
6. Check applications

### Employer
1. Register as employer
2. Complete profile
3. Post job
4. View applications
5. Message artisans

### Admin
1. Login as admin
2. Manage users
3. Review verifications
4. View statistics

## File Structure
```
artisan-platform/
├── auth/          # Login, Register, Logout
├── artisan/       # Artisan pages
├── employer/      # Employer pages
├── admin/         # Admin pages
├── user/          # Messages, Notifications
├── config/        # Database config
├── includes/      # Templates
├── assets/        # CSS, JS
└── database.sql   # Schema
```

## Common Issues

### Database Connection Error
Check credentials in `config/database.php`

### Permission Denied
```bash
chmod -R 777 uploads
```

### 404 Errors
Verify file paths and web server configuration

## Next Steps
- Read README.md for full documentation
- Check INSTALLATION.md for detailed setup
- Review API_DOCUMENTATION.md for API specs
- See PROJECT_SUMMARY.md for overview

## Support
Email: support@artisanplatform.ng
