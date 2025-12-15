# Artisan Platform - Complete Database Schema (DDL)

## Overview

This document provides the complete Data Definition Language (DDL) for the Artisan Platform database. The schema consists of 12 normalized tables designed to support a comprehensive recruitment platform for artisans and professionals in Nigeria.

## Database Design Principles

The schema follows these design principles:

- **Normalization**: Third Normal Form (3NF) to eliminate data redundancy
- **Referential Integrity**: Foreign key constraints to maintain data consistency
- **Performance**: Strategic indexes on frequently queried columns
- **Scalability**: Designed to handle millions of records efficiently
- **Security**: Prepared statements and input validation at application level
- **Auditability**: Timestamp columns for tracking changes

## Character Set & Collation

All tables use UTF-8 encoding for international character support:
- **Character Set**: utf8mb4 (4-byte UTF-8)
- **Collation**: utf8mb4_unicode_ci (case-insensitive, Unicode)

## Engine

All tables use InnoDB engine for:
- ACID compliance
- Foreign key support
- Transaction support
- Crash recovery

---

## Table 1: users

**Purpose**: Base user table for all user types (artisans, employers, admins)

**Relationships**: Parent table for artisan_profiles, employer_profiles, messages, notifications, reviews, verification_logs

### DDL

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('artisan', 'employer', 'admin') NOT NULL,
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    profile_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| email | VARCHAR(255) | UNIQUE, NOT NULL | User email address |
| password | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| first_name | VARCHAR(100) | NOT NULL | User's first name |
| last_name | VARCHAR(100) | NOT NULL | User's last name |
| phone | VARCHAR(20) | NULL | User's phone number |
| role | ENUM | NOT NULL | User type: artisan, employer, or admin |
| status | ENUM | DEFAULT 'active' | Account status: active, suspended, or inactive |
| email_verified | BOOLEAN | DEFAULT FALSE | Email verification flag |
| profile_verified | BOOLEAN | DEFAULT FALSE | Profile verification flag |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |
| last_login | TIMESTAMP | NULL | Last login timestamp |

### Indexes

- **idx_email**: Unique index on email for fast login lookups
- **idx_role**: Index on role for filtering users by type
- **idx_status**: Index on status for filtering active/suspended users

---

## Table 2: artisan_profiles

**Purpose**: Store artisan-specific profile information

**Relationships**: Child of users (1:1), Parent of artisan_skills, job_applications, documents

### DDL

```sql
CREATE TABLE artisan_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    bio TEXT,
    location VARCHAR(255),
    state VARCHAR(100),
    availability_status ENUM('available', 'busy', 'unavailable') DEFAULT 'available',
    years_of_experience INT DEFAULT 0,
    hourly_rate DECIMAL(10, 2),
    profile_image VARCHAR(255),
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_location (location),
    INDEX idx_state (state),
    INDEX idx_availability (availability_status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique profile identifier |
| user_id | INT | UNIQUE, NOT NULL, FK | Reference to users table |
| bio | TEXT | NULL | Professional biography |
| location | VARCHAR(255) | NULL | City/location of work |
| state | VARCHAR(100) | NULL | State/province |
| availability_status | ENUM | DEFAULT 'available' | Available, busy, or unavailable |
| years_of_experience | INT | DEFAULT 0 | Years of professional experience |
| hourly_rate | DECIMAL(10,2) | NULL | Hourly rate in Naira |
| profile_image | VARCHAR(255) | NULL | Path to profile image |
| verification_status | ENUM | DEFAULT 'pending' | Pending, verified, or rejected |
| rating | DECIMAL(3,2) | DEFAULT 0.00 | Average rating (0-5) |
| total_reviews | INT | DEFAULT 0 | Total number of reviews |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Profile creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

- **idx_location**: Index on location for geographic searches
- **idx_state**: Index on state for state-based filtering
- **idx_availability**: Index on availability_status for finding available artisans
- **idx_rating**: Index on rating for sorting by ratings

### Constraints

- **UNIQUE (user_id)**: One profile per user (1:1 relationship)
- **FOREIGN KEY (user_id)**: Cascading delete when user is deleted

---

## Table 3: employer_profiles

**Purpose**: Store employer-specific profile information

**Relationships**: Child of users (1:1), Parent of jobs

### DDL

```sql
CREATE TABLE employer_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255),
    company_type ENUM('individual', 'company') DEFAULT 'individual',
    company_description TEXT,
    company_address VARCHAR(255),
    company_phone VARCHAR(20),
    company_website VARCHAR(255),
    company_logo VARCHAR(255),
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_company_name (company_name),
    INDEX idx_verification (verification_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique profile identifier |
| user_id | INT | UNIQUE, NOT NULL, FK | Reference to users table |
| company_name | VARCHAR(255) | NULL | Company or business name |
| company_type | ENUM | DEFAULT 'individual' | Individual or company |
| company_description | TEXT | NULL | Company description |
| company_address | VARCHAR(255) | NULL | Company address |
| company_phone | VARCHAR(20) | NULL | Company phone number |
| company_website | VARCHAR(255) | NULL | Company website URL |
| company_logo | VARCHAR(255) | NULL | Path to company logo |
| verification_status | ENUM | DEFAULT 'pending' | Pending, verified, or rejected |
| rating | DECIMAL(3,2) | DEFAULT 0.00 | Average rating (0-5) |
| total_reviews | INT | DEFAULT 0 | Total number of reviews |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Profile creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

- **idx_company_name**: Index on company_name for company searches
- **idx_verification**: Index on verification_status for filtering verified companies

### Constraints

- **UNIQUE (user_id)**: One profile per user (1:1 relationship)
- **FOREIGN KEY (user_id)**: Cascading delete when user is deleted

---

## Table 4: skills

**Purpose**: Master list of available skills in the platform

**Relationships**: Parent of artisan_skills

### DDL

```sql
CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique skill identifier |
| name | VARCHAR(100) | UNIQUE, NOT NULL | Skill name (e.g., "Plumbing") |
| category | VARCHAR(100) | NULL | Skill category (e.g., "Construction") |
| description | TEXT | NULL | Detailed skill description |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### Indexes

- **idx_name**: Unique index on name for fast skill lookups
- **idx_category**: Index on category for filtering by skill category

### Sample Data

```sql
INSERT INTO skills (name, category, description) VALUES
('Plumbing', 'Construction', 'Plumbing and water system installation'),
('Electrical Work', 'Construction', 'Electrical installation and repair'),
('Carpentry', 'Construction', 'Woodworking and furniture making'),
('Welding', 'Construction', 'Metal welding and fabrication'),
('Masonry', 'Construction', 'Brick laying and concrete work'),
('Painting', 'Construction', 'Interior and exterior painting'),
('Hair Styling', 'Beauty', 'Hair cutting and styling'),
('Makeup', 'Beauty', 'Professional makeup application'),
('Tailoring', 'Fashion', 'Clothing design and tailoring'),
('Shoe Making', 'Fashion', 'Shoe design and manufacturing'),
('Web Development', 'Technology', 'Website and web application development'),
('Mobile App Development', 'Technology', 'Mobile application development'),
('Graphic Design', 'Design', 'Visual design and branding'),
('Photography', 'Media', 'Professional photography services'),
('Video Production', 'Media', 'Video filming and editing');
```

---

## Table 5: artisan_skills

**Purpose**: Junction table linking artisans to their skills (many-to-many relationship)

**Relationships**: Child of artisan_profiles and skills

### DDL

```sql
CREATE TABLE artisan_skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artisan_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    years_of_experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_artisan_skill (artisan_id, skill_id),
    INDEX idx_artisan (artisan_id),
    INDEX idx_skill (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique record identifier |
| artisan_id | INT | NOT NULL, FK | Reference to artisan_profiles |
| skill_id | INT | NOT NULL, FK | Reference to skills |
| proficiency_level | ENUM | DEFAULT 'beginner' | Skill level: beginner, intermediate, advanced, expert |
| years_of_experience | INT | DEFAULT 0 | Years of experience with this skill |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### Indexes

- **unique_artisan_skill**: Ensures each artisan has each skill only once
- **idx_artisan**: Index on artisan_id for finding all skills of an artisan
- **idx_skill**: Index on skill_id for finding all artisans with a skill

### Constraints

- **UNIQUE (artisan_id, skill_id)**: Prevents duplicate skill assignments
- **FOREIGN KEY (artisan_id)**: Cascading delete when artisan is deleted
- **FOREIGN KEY (skill_id)**: Cascading delete when skill is deleted

---

## Table 6: jobs

**Purpose**: Store job postings created by employers

**Relationships**: Child of employer_profiles, Parent of job_applications and reviews

### DDL

```sql
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    location VARCHAR(255),
    state VARCHAR(100),
    budget_min DECIMAL(10, 2),
    budget_max DECIMAL(10, 2),
    duration VARCHAR(100),
    status ENUM('open', 'in_progress', 'completed', 'closed') DEFAULT 'open',
    required_skills TEXT,
    experience_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline DATE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employer_profiles(id) ON DELETE CASCADE,
    INDEX idx_employer (employer_id),
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_state (state),
    INDEX idx_posted_date (posted_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique job identifier |
| employer_id | INT | NOT NULL, FK | Reference to employer_profiles |
| title | VARCHAR(255) | NOT NULL | Job title |
| description | TEXT | NOT NULL | Detailed job description |
| category | VARCHAR(100) | NULL | Job category |
| location | VARCHAR(255) | NULL | Job location/city |
| state | VARCHAR(100) | NULL | Job state/province |
| budget_min | DECIMAL(10,2) | NULL | Minimum budget in Naira |
| budget_max | DECIMAL(10,2) | NULL | Maximum budget in Naira |
| duration | VARCHAR(100) | NULL | Job duration (e.g., "2 weeks") |
| status | ENUM | DEFAULT 'open' | Job status: open, in_progress, completed, closed |
| required_skills | TEXT | NULL | Comma-separated list of required skills |
| experience_level | ENUM | DEFAULT 'beginner' | Required experience: beginner, intermediate, advanced |
| posted_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Job posting timestamp |
| deadline | DATE | NULL | Application deadline |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

- **idx_employer**: Index on employer_id for finding jobs by employer
- **idx_status**: Index on status for filtering open/closed jobs
- **idx_location**: Index on location for geographic searches
- **idx_state**: Index on state for state-based filtering
- **idx_posted_date**: Index on posted_date for sorting by recency

### Constraints

- **FOREIGN KEY (employer_id)**: Cascading delete when employer is deleted

---

## Table 7: job_applications

**Purpose**: Store job applications submitted by artisans

**Relationships**: Child of jobs and artisan_profiles

### DDL

```sql
CREATE TABLE job_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    artisan_id INT NOT NULL,
    cover_letter TEXT,
    proposed_rate DECIMAL(10, 2),
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, artisan_id),
    INDEX idx_job (job_id),
    INDEX idx_artisan (artisan_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique application identifier |
| job_id | INT | NOT NULL, FK | Reference to jobs |
| artisan_id | INT | NOT NULL, FK | Reference to artisan_profiles |
| cover_letter | TEXT | NULL | Application cover letter |
| proposed_rate | DECIMAL(10,2) | NULL | Proposed rate in Naira |
| status | ENUM | DEFAULT 'pending' | Application status: pending, accepted, rejected, withdrawn |
| applied_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Application submission timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

- **unique_application**: Ensures each artisan applies only once per job
- **idx_job**: Index on job_id for finding all applications for a job
- **idx_artisan**: Index on artisan_id for finding all applications by an artisan
- **idx_status**: Index on status for filtering by application status

### Constraints

- **UNIQUE (job_id, artisan_id)**: Prevents duplicate applications
- **FOREIGN KEY (job_id)**: Cascading delete when job is deleted
- **FOREIGN KEY (artisan_id)**: Cascading delete when artisan is deleted

---

## Table 8: messages

**Purpose**: Store direct messages between users

**Relationships**: Child of users (two foreign keys)

### DDL

```sql
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255),
    message_body TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique message identifier |
| sender_id | INT | NOT NULL, FK | Reference to sending user |
| recipient_id | INT | NOT NULL, FK | Reference to receiving user |
| subject | VARCHAR(255) | NULL | Message subject |
| message_body | TEXT | NOT NULL | Message content |
| is_read | BOOLEAN | DEFAULT FALSE | Read status flag |
| read_at | TIMESTAMP | NULL | Timestamp when message was read |
| sent_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Message sent timestamp |

### Indexes

- **idx_sender**: Index on sender_id for finding sent messages
- **idx_recipient**: Index on recipient_id for finding received messages
- **idx_is_read**: Index on is_read for finding unread messages
- **idx_sent_at**: Index on sent_at for sorting by date

### Constraints

- **FOREIGN KEY (sender_id)**: Cascading delete when sender is deleted
- **FOREIGN KEY (recipient_id)**: Cascading delete when recipient is deleted

---

## Table 9: notifications

**Purpose**: Store user notifications for various platform events

**Relationships**: Child of users

### DDL

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(100),
    title VARCHAR(255),
    message TEXT,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique notification identifier |
| user_id | INT | NOT NULL, FK | Reference to users |
| type | VARCHAR(100) | NULL | Notification type (e.g., "new_job", "new_message") |
| title | VARCHAR(255) | NULL | Notification title |
| message | TEXT | NULL | Notification message |
| related_id | INT | NULL | ID of related entity (job, message, etc.) |
| is_read | BOOLEAN | DEFAULT FALSE | Read status flag |
| read_at | TIMESTAMP | NULL | Timestamp when notification was read |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### Indexes

- **idx_user**: Index on user_id for finding user's notifications
- **idx_is_read**: Index on is_read for finding unread notifications
- **idx_created_at**: Index on created_at for sorting by date

### Constraints

- **FOREIGN KEY (user_id)**: Cascading delete when user is deleted

### Notification Types

Common notification types include:
- `new_job` - New job posted matching artisan's skills
- `new_message` - New message received
- `application_accepted` - Job application accepted
- `application_rejected` - Job application rejected
- `profile_verified` - Profile verification approved
- `profile_rejected` - Profile verification rejected
- `review_received` - New review posted

---

## Table 10: reviews

**Purpose**: Store ratings and reviews between users

**Relationships**: Child of users and jobs

### DDL

```sql
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    job_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_type ENUM('artisan', 'employer') DEFAULT 'artisan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_reviewed (reviewed_user_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique review identifier |
| reviewer_id | INT | NOT NULL, FK | Reference to reviewing user |
| reviewed_user_id | INT | NOT NULL, FK | Reference to reviewed user |
| job_id | INT | NULL, FK | Reference to related job |
| rating | INT | CHECK (1-5) | Rating score from 1 to 5 |
| comment | TEXT | NULL | Review comment |
| review_type | ENUM | DEFAULT 'artisan' | Type of review: artisan or employer |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

- **idx_reviewer**: Index on reviewer_id for finding reviews by a user
- **idx_reviewed**: Index on reviewed_user_id for finding reviews of a user
- **idx_rating**: Index on rating for filtering by rating

### Constraints

- **CHECK (rating >= 1 AND rating <= 5)**: Ensures valid rating range
- **FOREIGN KEY (reviewer_id)**: Cascading delete when reviewer is deleted
- **FOREIGN KEY (reviewed_user_id)**: Cascading delete when reviewed user is deleted
- **FOREIGN KEY (job_id)**: Set NULL when job is deleted (preserves review)

---

## Table 11: documents

**Purpose**: Store uploaded documents and certificates from artisans

**Relationships**: Child of artisan_profiles

### DDL

```sql
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artisan_id INT NOT NULL,
    document_type VARCHAR(100),
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE,
    INDEX idx_artisan (artisan_id),
    INDEX idx_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique document identifier |
| artisan_id | INT | NOT NULL, FK | Reference to artisan_profiles |
| document_type | VARCHAR(100) | NULL | Type of document (e.g., "certificate", "portfolio") |
| file_name | VARCHAR(255) | NOT NULL | Original file name |
| file_path | VARCHAR(255) | NOT NULL | Path to stored file |
| file_size | INT | NULL | File size in bytes |
| uploaded_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Upload timestamp |

### Indexes

- **idx_artisan**: Index on artisan_id for finding all documents of an artisan
- **idx_type**: Index on document_type for filtering by document type

### Constraints

- **FOREIGN KEY (artisan_id)**: Cascading delete when artisan is deleted

### Document Types

Common document types include:
- `certificate` - Professional certificates
- `license` - Professional licenses
- `portfolio` - Work samples
- `identification` - ID documents
- `qualification` - Educational qualifications

---

## Table 12: verification_logs

**Purpose**: Store audit trail of profile verification actions by admins

**Relationships**: Child of users (two foreign keys - user and admin)

### DDL

```sql
CREATE TABLE verification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    admin_id INT,
    verification_type VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Column Descriptions

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique log entry identifier |
| user_id | INT | NOT NULL, FK | Reference to user being verified |
| admin_id | INT | NULL, FK | Reference to admin performing verification |
| verification_type | VARCHAR(100) | NULL | Type of verification (e.g., "profile", "document") |
| status | ENUM | DEFAULT 'pending' | Verification status: pending, approved, rejected |
| comments | TEXT | NULL | Admin comments on verification |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Log creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

- **idx_user**: Index on user_id for finding verification history of a user
- **idx_status**: Index on status for filtering by verification status

### Constraints

- **FOREIGN KEY (user_id)**: Cascading delete when user is deleted
- **FOREIGN KEY (admin_id)**: Set NULL when admin is deleted (preserves audit trail)

---

## Entity Relationship Diagram (ERD)

```
┌─────────────┐
│    users    │
├─────────────┤
│ id (PK)     │
│ email       │
│ password    │
│ first_name  │
│ last_name   │
│ phone       │
│ role        │
│ status      │
│ created_at  │
└─────────────┘
      │
      ├─────────────────────┬──────────────────────┬──────────────────┐
      │                     │                      │                  │
      ▼                     ▼                      ▼                  ▼
┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  ┌──────────────┐
│ artisan_profiles │  │employer_profiles │  │   messages   │  │notifications │
├──────────────────┤  ├──────────────────┤  ├──────────────┤  ├──────────────┤
│ id (PK)          │  │ id (PK)          │  │ id (PK)      │  │ id (PK)      │
│ user_id (FK)     │  │ user_id (FK)     │  │ sender_id    │  │ user_id (FK) │
│ bio              │  │ company_name     │  │ recipient_id │  │ type         │
│ location         │  │ company_type     │  │ subject      │  │ title        │
│ state            │  │ company_address  │  │ message_body │  │ message      │
│ rating           │  │ rating           │  │ is_read      │  │ is_read      │
└──────────────────┘  └──────────────────┘  │ sent_at      │  │ created_at   │
      │                     │                └──────────────┘  └──────────────┘
      │                     │
      ▼                     ▼
┌──────────────────┐  ┌──────────────┐
│ artisan_skills   │  │    jobs      │
├──────────────────┤  ├──────────────┤
│ id (PK)          │  │ id (PK)      │
│ artisan_id (FK)  │  │ employer_id  │
│ skill_id (FK)    │  │ title        │
│ proficiency_level│  │ description  │
└──────────────────┘  │ location     │
      │               │ state        │
      │               │ budget_min   │
      ▼               │ budget_max   │
┌──────────────┐     │ status       │
│    skills    │     │ deadline     │
├──────────────┤     └──────────────┘
│ id (PK)      │            │
│ name         │            ▼
│ category     │    ┌──────────────────────┐
│ description  │    │ job_applications     │
└──────────────┘    ├──────────────────────┤
                    │ id (PK)              │
                    │ job_id (FK)          │
                    │ artisan_id (FK)      │
                    │ cover_letter         │
                    │ proposed_rate        │
                    │ status               │
                    │ applied_date         │
                    └──────────────────────┘

┌──────────────────┐
│    documents     │
├──────────────────┤
│ id (PK)          │
│ artisan_id (FK)  │
│ document_type    │
│ file_name        │
│ file_path        │
│ uploaded_at      │
└──────────────────┘

┌──────────────────┐
│     reviews      │
├──────────────────┤
│ id (PK)          │
│ reviewer_id (FK) │
│ reviewed_user_id │
│ job_id (FK)      │
│ rating           │
│ comment          │
│ created_at       │
└──────────────────┘

┌──────────────────────┐
│ verification_logs    │
├──────────────────────┤
│ id (PK)              │
│ user_id (FK)         │
│ admin_id (FK)        │
│ verification_type    │
│ status               │
│ comments             │
│ created_at           │
└──────────────────────┘
```

---

## Index Summary

### Unique Indexes

| Table | Columns | Purpose |
|-------|---------|---------|
| users | email | Ensure unique email addresses |
| artisan_profiles | user_id | One profile per artisan |
| employer_profiles | user_id | One profile per employer |
| skills | name | Unique skill names |
| artisan_skills | artisan_id, skill_id | Prevent duplicate skill assignments |
| job_applications | job_id, artisan_id | Prevent duplicate applications |

### Performance Indexes

| Table | Columns | Purpose |
|-------|---------|---------|
| users | role, status | Filter by user type and account status |
| artisan_profiles | location, state, availability, rating | Search and filter artisans |
| employer_profiles | company_name, verification_status | Find employers |
| skills | name, category | Search and filter skills |
| artisan_skills | artisan_id, skill_id | Find skills by artisan or vice versa |
| jobs | employer_id, status, location, state, posted_date | Search and filter jobs |
| job_applications | job_id, artisan_id, status | Track applications |
| messages | sender_id, recipient_id, is_read, sent_at | Messaging queries |
| notifications | user_id, is_read, created_at | Notification queries |
| reviews | reviewer_id, reviewed_user_id, rating | Review queries |
| documents | artisan_id, document_type | Find documents |
| verification_logs | user_id, status | Verification tracking |

---

## Referential Integrity

### Foreign Key Relationships

| Parent Table | Child Table | Action | Purpose |
|--------------|-------------|--------|---------|
| users | artisan_profiles | CASCADE | Delete profile when user deleted |
| users | employer_profiles | CASCADE | Delete profile when user deleted |
| users | messages (sender) | CASCADE | Delete messages when user deleted |
| users | messages (recipient) | CASCADE | Delete messages when user deleted |
| users | notifications | CASCADE | Delete notifications when user deleted |
| users | reviews (reviewer) | CASCADE | Delete reviews when user deleted |
| users | reviews (reviewed) | CASCADE | Delete reviews when user deleted |
| users | verification_logs (user) | CASCADE | Delete logs when user deleted |
| users | verification_logs (admin) | SET NULL | Preserve logs when admin deleted |
| artisan_profiles | artisan_skills | CASCADE | Delete skills when artisan deleted |
| artisan_profiles | job_applications | CASCADE | Delete applications when artisan deleted |
| artisan_profiles | documents | CASCADE | Delete documents when artisan deleted |
| employer_profiles | jobs | CASCADE | Delete jobs when employer deleted |
| skills | artisan_skills | CASCADE | Delete assignments when skill deleted |
| jobs | job_applications | CASCADE | Delete applications when job deleted |
| jobs | reviews | SET NULL | Preserve reviews when job deleted |

---

## Data Types & Sizes

### String Types

| Type | Max Length | Use Case |
|------|-----------|----------|
| VARCHAR(20) | 20 chars | Phone numbers, short codes |
| VARCHAR(100) | 100 chars | Names, categories, short text |
| VARCHAR(255) | 255 chars | Email, URLs, file names |
| TEXT | 65KB | Descriptions, messages, comments |

### Numeric Types

| Type | Range | Use Case |
|------|-------|----------|
| INT | -2.1B to 2.1B | IDs, counts, years |
| DECIMAL(10,2) | Up to 99,999,999.99 | Currency (Naira) |
| DECIMAL(3,2) | 0.00 to 9.99 | Ratings (0-5) |

### Date/Time Types

| Type | Format | Use Case |
|------|--------|----------|
| DATE | YYYY-MM-DD | Deadlines, birthdays |
| TIMESTAMP | YYYY-MM-DD HH:MM:SS | Creation, updates, events |

---

## Query Performance Optimization

### Most Common Queries & Their Indexes

```sql
-- Find artisans by location and skill
SELECT * FROM artisan_profiles ap
JOIN artisan_skills asn ON ap.id = asn.artisan_id
WHERE ap.location = ? AND ap.availability_status = 'available'
-- Uses: idx_location, idx_availability, idx_artisan

-- Find open jobs by state
SELECT * FROM jobs WHERE state = ? AND status = 'open'
-- Uses: idx_state, idx_status

-- Get user's messages
SELECT * FROM messages WHERE recipient_id = ? AND is_read = FALSE
-- Uses: idx_recipient, idx_is_read

-- Get artisan's applications
SELECT * FROM job_applications WHERE artisan_id = ?
-- Uses: idx_artisan

-- Get job applications
SELECT * FROM job_applications WHERE job_id = ? AND status = 'pending'
-- Uses: idx_job, idx_status
```

---

## Backup & Recovery

### Backup Command

```bash
mysqldump -u root -p artisan_platform > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Command

```bash
mysql -u root -p artisan_platform < backup_20240101_120000.sql
```

### Incremental Backup

```bash
mysqldump -u root -p --single-transaction --quick --lock-tables=false artisan_platform > backup.sql
```

---

## Maintenance

### Check Table Integrity

```sql
CHECK TABLE users, artisan_profiles, employer_profiles, skills, 
            artisan_skills, jobs, job_applications, messages, 
            notifications, reviews, documents, verification_logs;
```

### Optimize Tables

```sql
OPTIMIZE TABLE users, artisan_profiles, employer_profiles, skills, 
             artisan_skills, jobs, job_applications, messages, 
             notifications, reviews, documents, verification_logs;
```

### Analyze Tables

```sql
ANALYZE TABLE users, artisan_profiles, employer_profiles, skills, 
            artisan_skills, jobs, job_applications, messages, 
            notifications, reviews, documents, verification_logs;
```

---

## Conclusion

This comprehensive database schema provides a robust foundation for the Artisan Platform. The normalized design ensures data integrity, the strategic indexes optimize query performance, and the foreign key relationships maintain referential integrity throughout the system.

The schema is production-ready and can handle millions of records while maintaining performance and reliability.

---

**Schema Version**: 1.0  
**Last Updated**: December 2024  
**Database Engine**: MySQL 8.0+  
**Character Set**: utf8mb4 (Unicode)
