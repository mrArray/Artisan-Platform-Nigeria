# Artisan Platform - API Documentation

## Overview

This document outlines the planned RESTful API endpoints for the Artisan Platform. These endpoints will enable mobile app integration and third-party service connectivity.

## API Base URL

```
https://api.artisanplatform.ng/v1
```

## Authentication

All API requests require authentication via JWT (JSON Web Token) or API Key.

### JWT Authentication

```bash
# Login endpoint to get token
POST /auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

# Response
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "role": "artisan"
  }
}
```

### API Key Authentication

Include API key in request header:

```
Authorization: Bearer YOUR_API_KEY
```

## Response Format

All responses follow a standard format:

```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

Error response:

```json
{
  "success": false,
  "error": "Error code",
  "message": "Human-readable error message",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request parameters
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Access denied
- `404 Not Found` - Resource not found
- `409 Conflict` - Resource already exists
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

## Authentication Endpoints

### Register User

```
POST /auth/register
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+2341234567890",
  "password": "SecurePass123!",
  "role": "artisan"
}

Response: 201 Created
{
  "success": true,
  "data": {
    "id": 1,
    "email": "john@example.com",
    "role": "artisan"
  }
}
```

### Login

```
POST /auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "SecurePass123!"
}

Response: 200 OK
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "artisan"
    }
  }
}
```

### Logout

```
POST /auth/logout
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "message": "Logged out successfully"
}
```

## User Endpoints

### Get Current User

```
GET /user/profile
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "data": {
    "id": 1,
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+2341234567890",
    "role": "artisan",
    "status": "active"
  }
}
```

### Update User Profile

```
PUT /user/profile
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+2341234567890"
}

Response: 200 OK
{
  "success": true,
  "data": {
    "id": 1,
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+2341234567890"
  }
}
```

### Change Password

```
POST /user/change-password
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "current_password": "OldPass123!",
  "new_password": "NewPass123!"
}

Response: 200 OK
{
  "success": true,
  "message": "Password changed successfully"
}
```

## Artisan Endpoints

### Get Artisan Profile

```
GET /artisans/{id}

Response: 200 OK
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "bio": "Experienced plumber",
    "location": "Lagos",
    "state": "Lagos State",
    "years_of_experience": 5,
    "hourly_rate": 5000,
    "availability_status": "available",
    "rating": 4.5,
    "total_reviews": 12,
    "verification_status": "verified",
    "skills": [
      {
        "id": 1,
        "name": "Plumbing",
        "proficiency_level": "expert",
        "years_of_experience": 5
      }
    ]
  }
}
```

### Update Artisan Profile

```
PUT /artisans/profile
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "bio": "Experienced plumber with 5 years experience",
  "location": "Lagos",
  "state": "Lagos State",
  "years_of_experience": 5,
  "hourly_rate": 5000,
  "availability_status": "available"
}

Response: 200 OK
{
  "success": true,
  "data": { ... }
}
```

### Add Skill

```
POST /artisans/skills
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "skill_id": 1,
  "proficiency_level": "expert",
  "years_of_experience": 5
}

Response: 201 Created
{
  "success": true,
  "data": {
    "id": 1,
    "skill_id": 1,
    "proficiency_level": "expert",
    "years_of_experience": 5
  }
}
```

### Delete Skill

```
DELETE /artisans/skills/{skill_id}
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "message": "Skill removed successfully"
}
```

### Search Artisans

```
GET /artisans?skill=plumbing&location=Lagos&rating_min=4&limit=20&offset=0

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "John Doe",
      "location": "Lagos",
      "rating": 4.5,
      "skills": ["Plumbing", "Electrical"]
    }
  ],
  "pagination": {
    "total": 50,
    "limit": 20,
    "offset": 0
  }
}
```

## Job Endpoints

### Create Job

```
POST /jobs
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "title": "Experienced Plumber Needed",
  "description": "Need plumber for residential project",
  "category": "Construction",
  "location": "Lagos",
  "state": "Lagos State",
  "budget_min": 50000,
  "budget_max": 150000,
  "duration": "2 weeks",
  "experience_level": "advanced",
  "required_skills": ["Plumbing"],
  "deadline": "2024-02-15"
}

Response: 201 Created
{
  "success": true,
  "data": {
    "id": 1,
    "employer_id": 1,
    "title": "Experienced Plumber Needed",
    "status": "open",
    "budget_min": 50000,
    "budget_max": 150000,
    "posted_date": "2024-01-15T10:30:00Z"
  }
}
```

### Get Job

```
GET /jobs/{id}

Response: 200 OK
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Experienced Plumber Needed",
    "description": "Need plumber for residential project",
    "category": "Construction",
    "location": "Lagos",
    "budget_min": 50000,
    "budget_max": 150000,
    "status": "open",
    "posted_date": "2024-01-15T10:30:00Z",
    "employer": {
      "id": 1,
      "company_name": "ABC Construction",
      "rating": 4.8
    }
  }
}
```

### List Jobs

```
GET /jobs?status=open&location=Lagos&skill=plumbing&limit=20&offset=0

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Experienced Plumber Needed",
      "location": "Lagos",
      "budget_min": 50000,
      "budget_max": 150000,
      "status": "open"
    }
  ],
  "pagination": {
    "total": 100,
    "limit": 20,
    "offset": 0
  }
}
```

### Update Job

```
PUT /jobs/{id}
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "title": "Updated Job Title",
  "description": "Updated description",
  "status": "closed"
}

Response: 200 OK
{
  "success": true,
  "data": { ... }
}
```

### Delete Job

```
DELETE /jobs/{id}
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "message": "Job deleted successfully"
}
```

## Application Endpoints

### Submit Application

```
POST /applications
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "job_id": 1,
  "cover_letter": "I am interested in this position...",
  "proposed_rate": 100000
}

Response: 201 Created
{
  "success": true,
  "data": {
    "id": 1,
    "job_id": 1,
    "artisan_id": 1,
    "status": "pending",
    "applied_date": "2024-01-15T10:30:00Z"
  }
}
```

### Get Application

```
GET /applications/{id}
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "data": {
    "id": 1,
    "job_id": 1,
    "artisan_id": 1,
    "cover_letter": "I am interested...",
    "status": "pending",
    "applied_date": "2024-01-15T10:30:00Z"
  }
}
```

### List Applications

```
GET /applications?job_id=1&status=pending&limit=20&offset=0
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "artisan_id": 1,
      "artisan_name": "John Doe",
      "status": "pending",
      "applied_date": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 10,
    "limit": 20,
    "offset": 0
  }
}
```

### Update Application Status

```
PUT /applications/{id}
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "status": "accepted"
}

Response: 200 OK
{
  "success": true,
  "data": { ... }
}
```

## Messaging Endpoints

### Send Message

```
POST /messages
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "recipient_id": 2,
  "subject": "Job Inquiry",
  "message_body": "Are you interested in the plumbing job?"
}

Response: 201 Created
{
  "success": true,
  "data": {
    "id": 1,
    "sender_id": 1,
    "recipient_id": 2,
    "subject": "Job Inquiry",
    "sent_at": "2024-01-15T10:30:00Z"
  }
}
```

### Get Messages

```
GET /messages?folder=inbox&limit=20&offset=0
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sender_id": 2,
      "sender_name": "Jane Doe",
      "subject": "Job Inquiry",
      "is_read": false,
      "sent_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 50,
    "limit": 20,
    "offset": 0
  }
}
```

### Mark Message as Read

```
PUT /messages/{id}/read
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "message": "Message marked as read"
}
```

## Notification Endpoints

### Get Notifications

```
GET /notifications?limit=20&offset=0
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "new_job",
      "title": "New Job Posted",
      "message": "A new job matching your skills...",
      "is_read": false,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 30,
    "limit": 20,
    "offset": 0
  }
}
```

### Mark Notification as Read

```
PUT /notifications/{id}/read
Authorization: Bearer TOKEN

Response: 200 OK
{
  "success": true,
  "message": "Notification marked as read"
}
```

## Review Endpoints

### Submit Review

```
POST /reviews
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "reviewed_user_id": 2,
  "job_id": 1,
  "rating": 5,
  "comment": "Excellent work, highly recommended!"
}

Response: 201 Created
{
  "success": true,
  "data": {
    "id": 1,
    "reviewer_id": 1,
    "reviewed_user_id": 2,
    "rating": 5,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Get Reviews

```
GET /reviews?user_id=2&limit=20&offset=0

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "reviewer_id": 1,
      "reviewer_name": "John Doe",
      "rating": 5,
      "comment": "Excellent work...",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 12,
    "limit": 20,
    "offset": 0
  }
}
```

## Admin Endpoints

### Get All Users

```
GET /admin/users?role=artisan&status=active&limit=20&offset=0
Authorization: Bearer ADMIN_TOKEN

Response: 200 OK
{
  "success": true,
  "data": [ ... ],
  "pagination": { ... }
}
```

### Suspend User

```
PUT /admin/users/{id}/suspend
Authorization: Bearer ADMIN_TOKEN

Response: 200 OK
{
  "success": true,
  "message": "User suspended successfully"
}
```

### Get Verification Logs

```
GET /admin/verifications?status=pending&limit=20&offset=0
Authorization: Bearer ADMIN_TOKEN

Response: 200 OK
{
  "success": true,
  "data": [ ... ],
  "pagination": { ... }
}
```

### Approve Verification

```
PUT /admin/verifications/{id}/approve
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "comments": "Profile looks good"
}

Response: 200 OK
{
  "success": true,
  "message": "Profile approved successfully"
}
```

## Rate Limiting

API requests are rate limited to:
- 100 requests per minute for authenticated users
- 10 requests per minute for unauthenticated users

Rate limit headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1642245600
```

## Error Codes

| Code | Message | HTTP Status |
|------|---------|-------------|
| `AUTH_REQUIRED` | Authentication required | 401 |
| `INVALID_TOKEN` | Invalid or expired token | 401 |
| `INVALID_CREDENTIALS` | Invalid email or password | 401 |
| `ACCESS_DENIED` | Access denied | 403 |
| `NOT_FOUND` | Resource not found | 404 |
| `VALIDATION_ERROR` | Validation error | 422 |
| `DUPLICATE_EMAIL` | Email already exists | 409 |
| `SERVER_ERROR` | Internal server error | 500 |

## Pagination

All list endpoints support pagination:

```
GET /endpoint?limit=20&offset=0

Response:
{
  "data": [ ... ],
  "pagination": {
    "total": 100,
    "limit": 20,
    "offset": 0,
    "pages": 5
  }
}
```

## Filtering & Sorting

List endpoints support filtering and sorting:

```
GET /jobs?status=open&location=Lagos&sort=posted_date&order=desc&limit=20

Parameters:
- sort: Column to sort by
- order: asc or desc
- limit: Number of results (default: 20, max: 100)
- offset: Pagination offset (default: 0)
```

## Webhooks

Webhooks allow real-time notifications for events:

```
POST /webhooks/subscribe
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "event": "job.created",
  "url": "https://yourapp.com/webhook"
}

Events:
- job.created
- job.updated
- job.closed
- application.submitted
- application.accepted
- application.rejected
- message.sent
- review.submitted
```

## SDK Examples

### JavaScript/Node.js

```javascript
const ArtisanAPI = require('artisan-platform-sdk');

const client = new ArtisanAPI({
  baseURL: 'https://api.artisanplatform.ng/v1',
  apiKey: 'YOUR_API_KEY'
});

// Login
const response = await client.auth.login({
  email: 'user@example.com',
  password: 'password123'
});

// Get jobs
const jobs = await client.jobs.list({ status: 'open' });

// Apply for job
const application = await client.applications.create({
  job_id: 1,
  cover_letter: 'I am interested...'
});
```

### Python

```python
from artisan_platform import ArtisanClient

client = ArtisanClient(
    base_url='https://api.artisanplatform.ng/v1',
    api_key='YOUR_API_KEY'
)

# Login
response = client.auth.login(
    email='user@example.com',
    password='password123'
)

# Get jobs
jobs = client.jobs.list(status='open')

# Apply for job
application = client.applications.create(
    job_id=1,
    cover_letter='I am interested...'
)
```

---

**API Version**: 1.0  
**Last Updated**: December 2024  
**Status**: Planned for Future Release
